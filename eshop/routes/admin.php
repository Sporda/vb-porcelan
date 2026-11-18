<?php

use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\LoginController;
use Illuminate\Support\Facades\Route;

// Administrace: přihlášení a přehled poptávek (vše pod /admin).

Route::prefix('admin')->name('admin.')->group(function () {
    // /admin → přehled poptávek (nepřihlášeného pošle middleware auth na přihlášení)
    Route::redirect('/', '/admin/poptavky')->name('home');

    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('poptavky', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('poptavky/{inquiry:number}', [InquiryController::class, 'show'])->name('inquiries.show');
        Route::patch('poptavky/{inquiry:number}/stav', [InquiryController::class, 'updateStatus'])->name('inquiries.status');
        Route::patch('poptavky/{inquiry:number}/poznamka', [InquiryController::class, 'updateNote'])->name('inquiries.note');
        Route::post('poptavky/{inquiry:number}/odeslat-email', [InquiryController::class, 'resendMail'])->name('inquiries.resend');
    });
});
