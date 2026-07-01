<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Dashboard routes - protected by authentication and verified email middleware
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return inertia('Dashboard', ['tab' => 'dashboard']);
    })->name('dashboard');

    Route::get('/dashboard/stok', function () {
        return inertia('Dashboard', ['tab' => 'stok']);
    })->name('dashboard.stok');

    Route::get('/dashboard/jadwal', function () {
        return inertia('Dashboard', ['tab' => 'jadwal']);
    })->name('dashboard.jadwal');

    Route::get('/dashboard/riwayat', function () {
        return inertia('Dashboard', ['tab' => 'riwayat']);
    })->name('dashboard.riwayat');

    Route::get('/dashboard/status', function () {
        return inertia('Dashboard', ['tab' => 'status']);
    })->name('dashboard.status');

    Route::get('/simulasi-sistem', function () {
        return inertia('SimulasiSistem');
    })->name('simulasi-sistem');

    Route::get('/parameter-pakan', function () {
        return inertia('ParameterPakan');
    })->name('parameter-pakan');

    Route::get('/periode-pemeliharaan', function () {
        return inertia('PeriodePemeliharaan');
    })->name('periode-pemeliharaan');
});

Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok'
    ]);
});

require __DIR__.'/settings.php';
