<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AdministrasiController,
    Auth\AuthController,
    BerandaController,
    PengumumanController,
    ProfilebsiController,
    DashboardController,
    SocialiteController,
    UserProfileController
};
use App\Http\Controllers\Admin\{
    TahunAjarController,
    ProgramStudiController,
    BimbinganController,
    UserController
};
use App\Http\Controllers\Dosen\{
    DashboardDosenController,
    DosenProfileController,
    DosenBimbinganController,
    KomentarBimbinganController,
};
use App\Http\Controllers\mhs\{
    DashboardMhsController,
    MhsBimbinganController,
    KomentarMahasiswaController,
    MhsProfileController,
};

/*
|--------------------------------------------------------------------------
| RUTE PUBLIK
|--------------------------------------------------------------------------
| Rute yang dapat diakses oleh semua pengunjung tanpa autentikasi
*/
Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('/profil-ubsi', [ProfilebsiController::class, 'index'])->name('profil.index');
Route::get('/ubsi', [ProfilebsiController::class, 'index'])->name('peraturan');

/*
|--------------------------------------------------------------------------
| RUTE TERAUTENTIKASI
|--------------------------------------------------------------------------
| Rute yang memerlukan login untuk mengakses
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| RUTE ADMIN
|--------------------------------------------------------------------------
| Rute khusus untuk pengguna dengan role admin (role:1)
*/
Route::middleware(['auth', 'role:1'])->prefix('admin')->group(function () {
    // Dashboard Admin
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Manajemen Profil Admin
    Route::get('/profil', [UserProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profil', [UserProfileController::class, 'update'])->name('profile.update');

    // Manajemen Pengguna
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');

    // Create Routes
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::get('/users/create/admin', [UserController::class, 'createAdmin'])->name('admin.users.create.admin');
    Route::get('/users/create/dosen', [UserController::class, 'createDosen'])->name('admin.users.create.dosen');
    Route::get('/users/create/mahasiswa', [UserController::class, 'createMahasiswa'])->name('admin.users.create.mahasiswa');

    // Store Routes
    Route::post('/users/store', [UserController::class, 'store'])->name('admin.store');
    Route::post('/users/store/admin', [UserController::class, 'storeAdmin'])->name('admin.store.admin');
    Route::post('/users/store/dosen', [UserController::class, 'storeDosen'])->name('admin.store.dosen');
    Route::post('/users/store/mahasiswa', [UserController::class, 'storeMahasiswa'])->name('admin.store.mahasiswa');

    // Edit Routes
    Route::get('/users/{user}/edit/admin', [UserController::class, 'editAdmin'])->name('admin.edit.admin');
    Route::get('/users/{user}/edit/dosen', [UserController::class, 'editDosen'])->name('admin.edit.dosen');
    Route::get('/users/{user}/edit/mahasiswa', [UserController::class, 'editMahasiswa'])->name('admin.edit.mahasiswa');

    // Update Routes
    Route::put('/users/{user}/update/admin', [UserController::class, 'updateAdmin'])->name('admin.update.admin');
    Route::put('/users/{user}/update/dosen', [UserController::class, 'updateDosen'])->name('admin.update.dosen');
    Route::put('/users/{user}/update/mahasiswa', [UserController::class, 'updateMahasiswa'])->name('admin.update.mahasiswa');

    // Standard CRUD Routes
    Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.destroy');

    // Resource Routes (CRUD Lengkap)
    Route::resource('tahun_ajar', TahunAjarController::class);
    Route::resource('program_studi', ProgramStudiController::class);
    Route::resource('bimbingan', BimbinganController::class);
});

/*
|--------------------------------------------------------------------------
| RUTE DOSEN
|--------------------------------------------------------------------------
| Rute khusus untuk pengguna dengan role dosen (role:2)
*/
Route::middleware(['auth', 'role:2'])->prefix('dosen')->group(function () {
    // Dashboard Dosen
    Route::get('/dashboard', [DashboardDosenController::class, 'index'])->name('dosen.dashboard');

    Route::prefix('dashboard')->group(function () {
        Route::get('/check-new-bimbingan', [DashboardDosenController::class, 'checkNewBimbingan'])->name('dosen.check-new-bimbingan');
        Route::post('/tandai-dibaca/{id}', [DashboardDosenController::class, 'tandaiSudahDibaca'])->name('dosen.mark-as-read');
        Route::post('/tandai-semua-dibaca', [DashboardDosenController::class, 'tandaiSemuaDibaca'])->name('dosen.mark-all-read');
        Route::get('/statistik', [DashboardDosenController::class, 'getStatistik'])->name('dosen.statistik');
        Route::get('/bimbingan/{status}', [DashboardDosenController::class, 'getBimbinganByStatus'])->name('dosen.bimbingan-by-status');
    });

    // Manajemen Profil Dosen
    Route::get('/profil', [DosenProfileController::class, 'edit'])->name('dosen.profile.edit');
    Route::post('/profil', [DosenProfileController::class, 'update'])->name('dosen.profile.update');

    // Manajemen Bimbingan
    Route::get('/bimbingan', [DosenBimbinganController::class, 'index'])->name('dosen.bimbingan.index');
    Route::get('/bimbingan/{id}', [DosenBimbinganController::class, 'show'])->name('dosen.bimbingan.show');
    Route::get('/bimbingan/{id}/edit', [DosenBimbinganController::class, 'edit'])->name('dosen.bimbingan.edit');
    Route::put('/bimbingan/{id}', [DosenBimbinganController::class, 'update'])->name('dosen.bimbingan.update');

    // Hapus Dokumen Bimbingan
    Route::delete('/bimbingan/dokumen/{id}', [DosenBimbinganController::class, 'deleteDokumen'])->name('dosen.bimbingan.dokumen.delete');

    // Manajemen Komentar Bimbingan
    Route::post('/bimbingan/{id_bimbingan}/komentar', [KomentarBimbinganController::class, 'store'])->name('bimbingan.komentar.store');
    Route::get('/bimbingan/{id_bimbingan}/komentar/{id_komentar}', [KomentarBimbinganController::class, 'show'])->name('bimbingan.komentar.show');
});

/*
|--------------------------------------------------------------------------
| RUTE MAHASISWA
|--------------------------------------------------------------------------
| Rute khusus untuk pengguna dengan role mahasiswa (role:3)
*/
Route::middleware(['auth', 'role:3'])->prefix('mhs')->group(function () {
    // Dashboard Mahasiswa
    Route::get('/dashboard', [DashboardMhsController::class, 'index'])->name('mahasiswa.dashboard');

    // Manajemen Profil Mahasiswa
    Route::get('/profil', [MhsProfileController::class, 'edit'])->name('mhs.profile.edit');
    Route::post('/profil', [MhsProfileController::class, 'update'])->name('mhs.profile.update');

    // Manajemen Bimbingan
    Route::get('/bimbingan', [MhsBimbinganController::class, 'index'])->name('mhs.bimbingan.index');
    Route::get('/bimbingan/create', [MhsBimbinganController::class, 'create'])->name('mhs.bimbingan.create');
    Route::post('/bimbingan', [MhsBimbinganController::class, 'store'])->name('mhs.bimbingan.store');
    Route::get('/bimbingan/{id}', [MhsBimbinganController::class, 'show'])->name('mhs.bimbingan.show');
    Route::get('/bimbingan/{id}/edit', [MhsBimbinganController::class, 'edit'])->name('mhs.bimbingan.edit');
    Route::put('/bimbingan/{id}', [MhsBimbinganController::class, 'update'])->name('mhs.bimbingan.update');
    Route::delete('/bimbingan/{id}', [MhsBimbinganController::class, 'destroy'])->name('mhs.bimbingan.destroy');

    // Manajemen Komentar Bimbingan
    Route::post('/bimbingan/{id_bimbingan}/komentar', [KomentarMahasiswaController::class, 'store'])->name('bim.komentar.store');
});
