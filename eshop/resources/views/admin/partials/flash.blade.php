{{-- Hláška po akci: session('success') / session('error') / session('status') --}}
@if (session('error'))
    <div class="flash flash-error" role="alert">
        <svg class="ic" aria-hidden="true"><use href="#i-alert"/></svg>
        <p>{{ session('error') }}</p>
    </div>
@elseif (session('success') || session('status'))
    <div class="flash flash-success" role="status">
        <svg class="ic" aria-hidden="true"><use href="#i-check"/></svg>
        <p>{{ session('success') ?? session('status') }}</p>
    </div>
@endif
