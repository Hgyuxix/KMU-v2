<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\PermohonanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\VerifikasiController;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'kecamatan'
            ? redirect()->route('dashboard')
            : redirect()->route('layanan.index');
    }

    return redirect()->route('login');
});

Route::get('/layanan', [LayananController::class, 'index'])->name('layanan.index');
Route::get('/layanan/{layanan}', [LayananController::class, 'show'])->name('layanan.show');
Route::get('/verifikasi/{nomorSurat}', [VerifikasiController::class, 'show'])->name('verifikasi.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {return view('auth.login');})->name('login');

    Route::post('/login', [AuthController::class, 'login'])->name('login.process');
});

Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users',[UserManagementController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create',[UserManagementController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users',[UserManagementController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit',[UserManagementController::class, 'edit'])->name('admin.users.edit');
        Route::patch('/admin/users/{user}',[UserManagementController::class, 'update'])->name('admin.users.update');
        Route::patch('/admin/users/{user}/toggle-status',[UserManagementController::class, 'toggleStatus'])->name('admin.users.toggle-status');
    });

    Route::middleware('role:kelurahan')->group(function () {
        Route::get('/kelurahan/pengajuan', [PermohonanController::class, 'index'])->name('kelurahan.index');
        Route::get('/layanan/{layanan}/ajukan', [PermohonanController::class, 'create'])->name('permohonan.create');
        Route::post('/layanan/{layanan}/ajukan', [PermohonanController::class, 'store'])->name('permohonan.store');
        Route::get('/kelurahan/pengajuan/{permohonan}/revisi',[PermohonanController::class, 'editRevisi'])->name('kelurahan.pengajuan.revisi');
        Route::patch('/kelurahan/pengajuan/{permohonan}/revisi',[PermohonanController::class, 'updateRevisi'])->name('kelurahan.pengajuan.revisi.update');
        Route::post('/kelurahan/ocr-ktp', [\App\Http\Controllers\OcrController::class, 'scanKtp'])->name('kelurahan.ocr-ktp');
    });

    Route::middleware('role:kelurahan,kecamatan')->group(function () {
        Route::get('/permohonan/{permohonan}/preview', [PermohonanController::class, 'preview'])->name('permohonan.preview');
        Route::get('/dokumen/{dokumen}/file', [PermohonanController::class, 'lihatDokumen'])->name('dokumen.file');
    });

    Route::middleware('role:kecamatan')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/pengajuan/{permohonan}', [DashboardController::class, 'show'])->name('dashboard.pengajuan.show');
        Route::get('/dashboard/dokumen/{dokumen}/lihat', [DashboardController::class, 'lihatDokumen'])->name('dashboard.dokumen.lihat');
        Route::patch('/dashboard/pengajuan/{permohonan}/approve', [DashboardController::class, 'approve'])->name('dashboard.pengajuan.approve');
        Route::patch('/dashboard/dokumen/{dokumen}/status', [DashboardController::class, 'updateDokumenStatus'])->name('dashboard.dokumen.status');
        Route::patch('/dashboard/pengajuan/{permohonan}/revisi',[DashboardController::class, 'requestRevision'])->name('dashboard.pengajuan.revisi');
        Route::patch('/dashboard/pengajuan/{permohonan}/selesai',[DashboardController::class, 'markComplete'])->name('dashboard.pengajuan.selesai');
        Route::patch('/dashboard/pengajuan/{permohonan}/buka-kembali',[DashboardController::class, 'reopenForRevision'])->name('dashboard.pengajuan.reopen');
    });

});
