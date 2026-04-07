<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitDocumentController;

Route::get('/', function () {
    return redirect('/admin');
});

// Visit document routes (protected by auth)
Route::middleware('auth')->group(function () {
    Route::get('/visit/{visitRequest}/qr', [VisitDocumentController::class, 'qr'])->name('visit.qr');
    Route::get('/visit/{visitRequest}/badge', [VisitDocumentController::class, 'badge'])->name('visit.badge');
});
