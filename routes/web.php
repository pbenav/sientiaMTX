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
    Route::post('/teams/{team}/tasks/{task}/move', [TaskController::class, 'move'])->name('teams.tasks.move');
    Route::get('/teams/{team}/tasks/status/{status}', [TaskController::class, 'byStatus'])->name('tasks.byStatus');
    Route::get('/teams/{team}/tasks/quadrant', [TaskController::class, 'byQuadrant'])->name('tasks.byQuadrant');
    
    // Theme route
    Route::post('/theme', [\App\Http\Controllers\ThemeController::class, 'update'])->name('theme.update');
});

require __DIR__.'/auth.php';
