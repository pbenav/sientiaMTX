<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

// Landing page — shown to all (auth users see a CTA to their dashboard)
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Team Invitation Acceptance
Route::get('/invitations/{token}', [\App\Http\Controllers\TeamInvitationController::class, 'accept'])
    ->name('invitations.accept');

// Locale switcher
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch')
    ->where('locale', 'en|es');


Route::get('/dashboard', function () {
    $user = auth()->user();
    $firstTeam = $user->teams()->first();
    
    if ($firstTeam) {
        return redirect()->route('teams.dashboard', $firstTeam);
    }
    
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Teams routes
    Route::resource('teams', TeamController::class);
    Route::get('/teams/{team}/dashboard', [TeamController::class, 'dashboard'])->name('teams.dashboard');
    Route::get('/teams/{team}/members', [TeamController::class, 'members'])->name('teams.members');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.addMember');
    Route::patch('/teams/{team}/members/{user}/role', [TeamController::class, 'updateMemberRole'])->name('teams.updateMemberRole');
    Route::patch('/teams/{team}/members/{user}/info', [TeamController::class, 'updateMemberInfo'])->name('teams.updateMemberInfo');
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.removeMember');
    Route::delete('/teams/{team}/invitations/{invitation}', [TeamController::class, 'removeInvitation'])->name('teams.invitations.destroy');

    // Groups routes
    Route::post('/teams/{team}/groups', [GroupController::class, 'store'])->name('teams.groups.store');
    Route::patch('/teams/{team}/groups/{group}', [GroupController::class, 'update'])->name('teams.groups.update');
    Route::delete('/teams/{team}/groups/{group}', [GroupController::class, 'destroy'])->name('teams.groups.destroy');
    Route::post('/teams/{team}/groups/{group}/members', [GroupController::class, 'addMember'])->name('teams.groups.addMember');
    Route::delete('/teams/{team}/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('teams.groups.removeMember');

    // Tasks routes (nested under teams)
    Route::resource('teams.tasks', TaskController::class);
    
    // Task Attachments
    Route::prefix('teams/{team}')->group(function () {
        Route::post('tasks/{task}/attachments', [TaskController::class, 'uploadAttachment'])->name('teams.tasks.attachments.upload');
        Route::get('attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])->name('teams.attachments.download');
        Route::delete('attachments/{attachment}', [TaskController::class, 'destroyAttachment'])->name('teams.attachments.destroy');
    });

    Route::post('/teams/{team}/tasks/{task}/nudge', [TaskController::class, 'nudge'])->name('teams.tasks.nudge');
    Route::post('/teams/{team}/tasks/{task}/move', [TaskController::class, 'move'])->name('teams.tasks.move');
    Route::get('/teams/{team}/tasks/status/{status}', [TaskController::class, 'byStatus'])->name('tasks.byStatus');
    Route::get('/teams/{team}/tasks/quadrant', [TaskController::class, 'byQuadrant'])->name('tasks.byQuadrant');
    Route::get('/teams/{team}/gantt', [\App\Http\Controllers\GanttController::class, 'index'])->name('teams.gantt');
    Route::get('/teams/{team}/gantt/data', [\App\Http\Controllers\GanttController::class, 'data'])->name('teams.gantt.data');
    
    // Theme route
    Route::post('/theme', [\App\Http\Controllers\ThemeController::class, 'update'])->name('theme.update');

    // Global Settings routes
    Route::middleware('can:admin')->group(function () {
        Route::get('/settings/mail', [\App\Http\Controllers\SettingsController::class, 'mailSettings'])->name('settings.mail');
        Route::post('/settings/mail', [\App\Http\Controllers\SettingsController::class, 'updateMailSettings'])->name('settings.mail.update');
        Route::post('/settings/mail/test', [\App\Http\Controllers\SettingsController::class, 'testMail'])->name('settings.mail.test');
        Route::get('/settings/users', [\App\Http\Controllers\SettingsController::class, 'users'])->name('settings.users');
        Route::get('/settings/users/create', [\App\Http\Controllers\SettingsController::class, 'createUser'])->name('settings.users.create');
        Route::post('/settings/users', [\App\Http\Controllers\SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::post('/settings/users/{user}/toggle-admin', [\App\Http\Controllers\SettingsController::class, 'toggleAdmin'])->name('settings.users.toggle-admin');
        Route::get('/settings/users/{user}/edit', [\App\Http\Controllers\SettingsController::class, 'editUser'])->name('settings.users.edit');
        Route::put('/settings/users/{user}', [\App\Http\Controllers\SettingsController::class, 'updateUser'])->name('settings.users.update');
        Route::delete('/settings/users/{user}', [\App\Http\Controllers\SettingsController::class, 'destroyUser'])->name('settings.users.destroy');
        Route::post('/settings/users/{user}/invitations/{invitation}/accept', [\App\Http\Controllers\SettingsController::class, 'acceptUserInvitation'])->name('settings.users.accept-invitation');
    });

    // Google Services
    Route::get('/auth/google', [\App\Http\Controllers\GoogleController::class, 'redirect'])->name('google.auth');
    Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleController::class, 'callback'])->name('google.callback');
    Route::get('/google/sync', [\App\Http\Controllers\GoogleController::class, 'sync'])->name('google.sync');
    Route::post('/google/import', [\App\Http\Controllers\GoogleController::class, 'import'])->name('google.import');
    Route::post('/teams/{team}/tasks/{task}/google-export', [\App\Http\Controllers\GoogleController::class, 'export'])->name('google.export');
});

require __DIR__.'/auth.php';
