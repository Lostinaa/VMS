<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitDocumentController;
use App\Http\Controllers\QrCheckInController;

Route::get('/', function () {
    return redirect('/visit-request');
});

// Public visit request portal (no auth)
Route::get('/visit-request', \App\Livewire\PublicVisitRequest::class)->name('visit.request');
Route::get('/group-visit', \App\Livewire\GroupVisitRequest::class)->name('visit.group');

// QR scan kiosk page (FR-005 / FR-014)
Route::get('/kiosk', [QrCheckInController::class, 'scanPage'])->name('kiosk');

// QR check-in / check-out API (FR-005)
Route::get('/api/qr/lookup', [QrCheckInController::class, 'lookupQr'])->name('api.qr.lookup');
Route::post('/api/qr/check-in', [QrCheckInController::class, 'checkIn'])->name('api.qr.checkin');
Route::post('/api/qr/check-out', [QrCheckInController::class, 'checkOut'])->name('api.qr.checkout');

// Screening questions API (FR-001)
Route::get('/api/screening-questions', [QrCheckInController::class, 'screeningQuestions'])->name('api.screening');

// Available escorts API (FR-008)
Route::get('/api/escorts', [QrCheckInController::class, 'availableEscorts'])->name('api.escorts');

// Visit document routes (protected by auth)
Route::middleware('auth')->group(function () {
    Route::get('/visit/{visitRequest}/qr', [VisitDocumentController::class, 'qr'])->name('visit.qr');
    Route::get('/visit/{visitRequest}/badge', [VisitDocumentController::class, 'badge'])->name('visit.badge');
});

// Public QR code access (FR-005)
Route::get('/visit/qr/{qr_code}', [VisitDocumentController::class, 'publicQr'])->name('visit.qr.public');

