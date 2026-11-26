<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDuesController;
use App\Http\Controllers\AdminStudentAccountController;
use App\Http\Controllers\AdminTournamentController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureAdmin;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1,dashboard-admin-login:')->name('login.submit');

    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard.overview');
        Route::view('/progress', 'admin.progress')->name('progress');
        Route::get('/dues', [AdminDuesController::class, 'index'])->name('dues');
        Route::post('/dues/generate', [AdminDuesController::class, 'generate'])->name('dues.generate');
        Route::post('/dues/{id}/pay', [AdminDuesController::class, 'pay'])->whereNumber('id')->name('dues.pay');
        Route::post('/students/{id}/account/reset', [AdminStudentAccountController::class, 'reset'])->whereNumber('id')->name('students.account.reset');
        Route::get('/tournaments/{id}', [AdminTournamentController::class, 'show'])->whereNumber('id')->name('tournaments.show');
        Route::post('/tournaments/{id}/state', [AdminTournamentController::class, 'updateState'])->whereNumber('id')->name('tournaments.state');
        Route::post('/tournaments/{id}/teams', [AdminTournamentController::class, 'storeTeam'])->whereNumber('id')->name('tournaments.teams.store');
        Route::post('/tournaments/{id}/teams/{team}/review', [AdminTournamentController::class, 'review'])->whereNumber(['id', 'team'])->name('tournaments.teams.review');
        Route::post('/tournaments/{id}/teams/{team}/verify', [AdminTournamentController::class, 'verify'])->whereNumber(['id', 'team'])->name('tournaments.teams.verify');
        Route::get('/tournaments/{id}/teams/{team}/receipt', [AdminTournamentController::class, 'receipt'])->whereNumber(['id', 'team'])->name('tournaments.teams.receipt');
        Route::get('/{section}/{id}/cover', [AdminController::class, 'cover'])->whereNumber('id')->name('cover');
        Route::get('/{section}', [AdminController::class, 'index'])->name('index');
        Route::post('/{section}', [AdminController::class, 'store'])->name('store');
        Route::put('/{section}/{id}', [AdminController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{section}/{id}', [AdminController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::post('/registrations/{id}/verify', [AdminController::class,'verifyRegistration'])->whereNumber('id')->name('registrations.verify');
        Route::get('/registrations/{id}/receipt', [AdminController::class, 'registrationReceipt'])->whereNumber('id')->name('registrations.receipt');
    });
});
