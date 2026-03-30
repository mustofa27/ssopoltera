<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\ProfileSyncController;
use App\Http\Controllers\ProgramStudyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SsoBackchannelLogoutController;
use App\Http\Controllers\SsoSessionController;
use App\Http\Controllers\SupportUnitController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['ip.policy', 'guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
    Route::get('/admin/login', [LoginController::class, 'show']);
});

// Microsoft OAuth Routes
Route::middleware('ip.policy')->group(function () {
    Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])->name('auth.microsoft');
    Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])->name('auth.microsoft.callback');
});

Route::post('/sso/backchannel/logout', SsoBackchannelLogoutController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('sso.backchannel.logout');

Route::middleware(['ip.policy', 'auth', 'active', 'idle.timeout', 'password.expiry'])->group(function () {
    Route::get('/oauth/authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');
});

Route::post('/oauth/token', [OAuthController::class, 'token'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('oauth.token');

Route::match(['GET', 'POST'], '/oauth/userinfo', [OAuthController::class, 'userinfo'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('oauth.userinfo');

Route::middleware(['ip.policy', 'auth', 'active', 'idle.timeout', 'password.expiry'])->group(function () {
    Route::get('/admin', DashboardController::class)->name('dashboard');

    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('/program-studies', [ProgramStudyController::class, 'index'])->name('program-studies.index');
        Route::get('/program-studies/create', [ProgramStudyController::class, 'create'])->name('program-studies.create');
        Route::post('/program-studies', [ProgramStudyController::class, 'store'])->name('program-studies.store');
        Route::get('/program-studies/{programStudy}/edit', [ProgramStudyController::class, 'edit'])->name('program-studies.edit');
        Route::put('/program-studies/{programStudy}', [ProgramStudyController::class, 'update'])->name('program-studies.update');
        Route::delete('/program-studies/{programStudy}', [ProgramStudyController::class, 'destroy'])->name('program-studies.destroy');

        Route::get('/support-units', [SupportUnitController::class, 'index'])->name('support-units.index');
        Route::get('/support-units/create', [SupportUnitController::class, 'create'])->name('support-units.create');
        Route::post('/support-units', [SupportUnitController::class, 'store'])->name('support-units.store');
        Route::get('/support-units/{supportUnit}/edit', [SupportUnitController::class, 'edit'])->name('support-units.edit');
        Route::put('/support-units/{supportUnit}', [SupportUnitController::class, 'update'])->name('support-units.update');
        Route::delete('/support-units/{supportUnit}', [SupportUnitController::class, 'destroy'])->name('support-units.destroy');

        Route::get('/profile-sync', [ProfileSyncController::class, 'index'])->name('profile-sync.index');
        Route::post('/profile-sync/import-all', [ProfileSyncController::class, 'importAll'])->name('profile-sync.import-all');
        Route::post('/profile-sync/sync-all', [ProfileSyncController::class, 'syncAll'])->name('profile-sync.sync-all');
        Route::post('/profile-sync/{user}', [ProfileSyncController::class, 'syncUser'])->name('profile-sync.sync-user');
    });

    Route::middleware('permission:manage_roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('permission:manage_applications')->group(function () {
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/create', [ApplicationController::class, 'create'])->name('applications.create');
        Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('/applications/{application}', [ApplicationController::class, 'view'])->name('applications.view');
        Route::get('/applications/{application}/edit', [ApplicationController::class, 'edit'])->name('applications.edit');
        Route::put('/applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
        Route::post('/applications/{application}/regenerate-secret', [ApplicationController::class, 'regenerateSecret'])->name('applications.regenerate-secret');
        Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
    });

    Route::middleware('permission:manage_sessions')->group(function () {
        Route::get('/sessions', [SsoSessionController::class, 'index'])->name('sessions.index');
        Route::delete('/sessions/{session}', [SsoSessionController::class, 'destroy'])->name('sessions.destroy');
        Route::post('/sessions/clear', [SsoSessionController::class, 'destroyAll'])->name('sessions.clear');

        Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
        Route::patch('/tokens/{session}/expires', [TokenController::class, 'updateExpiry'])->name('tokens.update-expiry');
        Route::delete('/tokens/{session}', [TokenController::class, 'destroy'])->name('tokens.destroy');
    });

    Route::middleware('permission:view_audit_logs')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

