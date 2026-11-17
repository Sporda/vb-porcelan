<?php
declare(strict_types=1);

/*
 * Lajky návrhů pro rozcestník design/index.html
 * (upraveno podle webtitan.cz/api/likes.php)
 *
 * Pravidla:
 *  - jeden návštěvník = nejvýš jeden lajk na jeden návrh (může lajknout více návrhů, lajk může i vzít zpět),
 *  - návštěvníka pozná podle náhodného ID v HttpOnly cookie (bez přihlášení, bez fingerprintingu),
 *  - pojistka proti mazání cookies: z jedné IP adresy může jeden návrh lajknout nejvýš IP_CAP různých návštěvníků,
 *  - IP adresy se neukládají, jen jejich HMAC otisk s tajným klíčem.
 *
 * Data (SQLite + tajný klíč) leží MIMO webroot:
 *  - cesta z proměnné prostředí VB_LIKES_DIR, jinak složka data/ vedle kořene webu
 *    (DOCUMENT_ROOT/../data – na serveru public_html/data, lokálně u `php -S -t design` VB-porcelan/data),
 *  - pojistka: pokud by složka s daty ležela uvnitř kořene webu, API odmítne pracovat.
 *
 * GET  api/likes.php                      -> {"counts":{"1":3,...},"mine":[1,4]}
 * POST api/likes.php {"design":4,"liked":true|false} (Content-Type: application/json) -> stejná odpověď
 */

const DESIGNS = [1, 2, 3, 4, 5, 6];   // nový návrh = přidat číslo sem i do index.html
const COOKIE = 'vb_voter';
const IP_CAP = 3;
const RATE_MAX = 60;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');

function out(int $code, array $body): never
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    out(405, ['error' => 'method']);
}

$docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$dataDir = getenv('VB_LIKES_DIR') ?: ($docRoot !== '' ? dirname($docRoot) : dirname(__DIR__, 2)) . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0750, true);
    // pojistka pro Apache, kdyby složka přece jen skončila ve webrootu
    @file_put_contents($dataDir . '/.htaccess', "Require all denied\n");
}
if (!is_dir($dataDir) || !is_writable($dataDir)) {
    error_log('likes: data dir missing or not writable: ' . $dataDir);
    out(500, ['error' => 'storage']);
}
$realData = realpath($dataDir);
$realRoot = $docRoot !== '' ? realpath($docRoot) : false;
if ($realData !== false && $realRoot !== false && str_starts_with($realData . '/', $realRoot . '/')) {
    error_log('likes: data dir is inside the web root, refusing: ' . $realData);
    out(500, ['error' => 'storage']);
}

$secretFile = $dataDir . '/secret.key';
if (!is_file($secretFile)) {
    file_put_contents($secretFile, bin2hex(random_bytes(32)), LOCK_EX);
    @chmod($secretFile, 0600);
}
$secret = trim((string) file_get_contents($secretFile));

try {
    $db = new PDO('sqlite:' . $dataDir . '/likes.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('PRAGMA busy_timeout = 3000');
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('CREATE TABLE IF NOT EXISTS likes (
        design  INTEGER NOT NULL,
        voter   TEXT    NOT NULL,
        ip      TEXT    NOT NULL,
        created INTEGER NOT NULL,
        PRIMARY KEY (design, voter)
    )');
    $db->exec('CREATE INDEX IF NOT EXISTS likes_design_ip ON likes (design, ip)');
    $db->exec('CREATE TABLE IF NOT EXISTS hits (ip TEXT NOT NULL, minute INTEGER NOT NULL, n INTEGER NOT NULL, PRIMARY KEY (ip, minute))');
} catch (Throwable $e) {
    error_log('likes db: ' . $e->getMessage());
    out(500, ['error' => 'storage']);
}

$ipHash = hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), $secret);

// --- rate limit (per IP, per minute) ---
$minute = intdiv(time(), 60);
$db->prepare('INSERT INTO hits (ip, minute, n) VALUES (?, ?, 1) ON CONFLICT (ip, minute) DO UPDATE SET n = n + 1')
   ->execute([$ipHash, $minute]);
$st = $db->prepare('SELECT n FROM hits WHERE ip = ? AND minute = ?');
$st->execute([$ipHash, $minute]);
if ((int) $st->fetchColumn() > RATE_MAX) {
    out(429, ['error' => 'rate']);
}
if (random_int(1, 50) === 1) {
    $db->prepare('DELETE FROM hits WHERE minute < ?')->execute([$minute - 10]);
}

// --- voter id (HttpOnly cookie) ---
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
$voter = $_COOKIE[COOKIE] ?? '';
if (!is_string($voter) || !preg_match('/^[a-f0-9]{32}$/', $voter)) {
    $voter = bin2hex(random_bytes(16));
    setcookie(COOKIE, $voter, [
        'expires'  => time() + 60 * 60 * 24 * 730,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function state(PDO $db, string $voter): array
{
    $counts = [];
    foreach (DESIGNS as $d) {
        $counts[(string) $d] = 0;
    }
    foreach ($db->query('SELECT design, COUNT(*) AS n FROM likes GROUP BY design') as $row) {
        if (in_array((int) $row['design'], DESIGNS, true)) {
            $counts[(string) $row['design']] = (int) $row['n'];
        }
    }
    $st = $db->prepare('SELECT design FROM likes WHERE voter = ? ORDER BY design');
    $st->execute([$voter]);
    $mine = array_values(array_filter(
        array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)),
        fn (int $d) => in_array($d, DESIGNS, true)
    ));
    return ['counts' => $counts, 'mine' => $mine];
}

if ($method === 'GET') {
    out(200, state($db, $voter));
}

// --- POST: like / unlike ---
// jen ze stejné domény (Origin musí odpovídat Host), bez napevno zadané adresy
$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '') {
    $o = parse_url($origin);
    $originHost = strtolower(($o['host'] ?? '') . (isset($o['port']) ? ':' . $o['port'] : ''));
    if ($originHost !== strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''))) {
        out(403, ['error' => 'origin']);
    }
}
if (stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== 0) {
    out(415, ['error' => 'content-type']);
}
$in = json_decode((string) file_get_contents('php://input'), true);
$design = is_array($in) ? ($in['design'] ?? null) : null;
$liked = is_array($in) ? ($in['liked'] ?? null) : null;
if (!is_int($design) || !in_array($design, DESIGNS, true) || !is_bool($liked)) {
    out(400, ['error' => 'input']);
}

try {
    $db->beginTransaction();
    if ($liked) {
        $st = $db->prepare('SELECT 1 FROM likes WHERE design = ? AND voter = ?');
        $st->execute([$design, $voter]);
        if (!$st->fetchColumn()) {
            $st = $db->prepare('SELECT COUNT(*) FROM likes WHERE design = ? AND ip = ?');
            $st->execute([$design, $ipHash]);
            if ((int) $st->fetchColumn() >= IP_CAP) {
                $db->rollBack();
                out(429, ['error' => 'ip_cap'] + state($db, $voter));
            }
            $db->prepare('INSERT INTO likes (design, voter, ip, created) VALUES (?, ?, ?, ?)')
               ->execute([$design, $voter, $ipHash, time()]);
        }
    } else {
        $db->prepare('DELETE FROM likes WHERE design = ? AND voter = ?')->execute([$design, $voter]);
    }
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('likes write: ' . $e->getMessage());
    out(500, ['error' => 'storage']);
}

out(200, state($db, $voter));
