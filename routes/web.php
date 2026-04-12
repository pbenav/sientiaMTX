<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumMessageController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\GDPRController;
use Illuminate\Support\Facades\Route;

// Telegram Webhook (Public)
Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

// Landing page — shown to all (auth users see a CTA to their dashboard)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// Legal pages
Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [LegalController::class, 'terms'])->name('terms');
Route::get('/cookie-policy', [LegalController::class, 'cookies'])->name('cookies');

// Legal Re-consent
Route::middleware('auth')->group(function () {
    Route::get('/legal/consent', [LegalController::class, 'reconsent'])->name('legal.reconsent');
    Route::post('/legal/consent', [LegalController::class, 'acceptConsent'])->name('legal.accept');
});

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
        return redirect()->route('teams.time-reports', $firstTeam);
    }
    
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
    Route::post('/notifications/subscribe', [\App\Http\Controllers\WebPushController::class, 'store'])->name('webpush.subscribe');
    Route::post('/notifications/unsubscribe', [\App\Http\Controllers\WebPushController::class, 'destroy'])->name('webpush.unsubscribe');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/telegram/test', [ProfileController::class, 'testTelegram'])->name('profile.telegram.test');
    Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'getUnread'])->name('notifications.unread-count');
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::match(['get', 'patch'], '/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    Route::post('/notifications/bulk', [App\Http\Controllers\NotificationController::class, 'bulkAction'])->name('notifications.bulk-action');
    Route::patch('/profile/zone', [ProfileController::class, 'updateZone'])->name('user.update-zone');
    Route::get('/profile/export', [GDPRController::class, 'export'])->name('profile.export');
    Route::post('/teams/{team}/kudos', [App\Http\Controllers\KudoController::class, 'store'])->name('teams.kudos.store');

    // Teams routes
    Route::resource('teams', TeamController::class);
    Route::post('/teams/{team}/transfer-ownership', [TeamController::class, 'transferOwnership'])->name('teams.transfer-ownership');
    Route::get('/teams/{team}/dashboard', [TeamController::class, 'dashboard'])->name('teams.dashboard');
    Route::patch('/teams/{team}/quadrants/color', [TeamController::class, 'updateQuadrantColor'])->name('teams.quadrants.color');
    Route::get('/teams/{team}/members', [TeamController::class, 'members'])->name('teams.members');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.addMember');
    Route::patch('/teams/{team}/members/{user}/role', [TeamController::class, 'updateMemberRole'])->name('teams.updateMemberRole');
    Route::patch('/teams/{team}/members/{user}/info', [TeamController::class, 'updateMemberInfo'])->name('teams.updateMemberInfo');
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.removeMember');
    Route::delete('/teams/{team}/invitations/{invitation}', [TeamController::class, 'removeInvitation'])->name('teams.invitations.destroy');
    Route::post('/teams/order', [TeamController::class, 'updateOrder'])->name('teams.update-order');

    // Groups routes
    Route::post('/teams/{team}/groups', [GroupController::class, 'store'])->name('teams.groups.store');
    Route::patch('/teams/{team}/groups/{group}', [GroupController::class, 'update'])->name('teams.groups.update');
    Route::delete('/teams/{team}/groups/{group}', [GroupController::class, 'destroy'])->name('teams.groups.destroy');
    Route::post('/teams/{team}/groups/{group}/members', [GroupController::class, 'addMember'])->name('teams.groups.addMember');
    Route::delete('/teams/{team}/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('teams.groups.removeMember');

    // Tasks routes
    Route::delete('/teams/{team}/tasks/bulk-delete', [TaskController::class, 'bulkDelete'])->name('teams.tasks.bulk-delete');
    Route::post('/teams/{team}/tasks/purge-trash', [TaskController::class, 'purgeTrash'])->name('teams.tasks.purge-trash');
    Route::resource('teams.tasks', TaskController::class)->except(['show', 'edit']);

    // Override show/edit/update/destroy to avoid Scoped Binding and handle SoftDeletes
    Route::prefix('teams/{team}')->group(function() {
        Route::get('tasks/{task}', [TaskController::class, 'show'])->name('teams.tasks.show')->withTrashed()->withoutScopedBindings();
        Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('teams.tasks.edit')->withTrashed()->withoutScopedBindings();
        Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('teams.tasks.update')->withTrashed()->withoutScopedBindings();
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('teams.tasks.destroy')->withTrashed()->withoutScopedBindings();
    });

    // Forum routes inside team
    Route::get('/teams/{team}/forum', [ForumController::class, 'index'])->name('teams.forum.index');
    Route::post('/teams/{team}/forum', [ForumController::class, 'store'])->name('teams.forum.store');
    Route::get('/teams/{team}/forum/{thread}', [ForumController::class, 'show'])->name('teams.forum.show');
    Route::patch('/teams/{team}/forum/{thread}', [ForumController::class, 'update'])->name('teams.forum.update');
    Route::delete('/teams/{team}/forum/{thread}', [ForumController::class, 'destroy'])->name('teams.forum.destroy');
    
    // Forum messages
    Route::post('/teams/{team}/forum/{thread}/messages', [ForumMessageController::class, 'store'])->name('teams.forum.messages.store');
    Route::patch('/teams/{team}/forum/messages/{message}', [ForumMessageController::class, 'update'])->name('teams.forum.messages.update');
    Route::delete('/teams/{team}/forum/messages/{message}', [ForumMessageController::class, 'destroy'])->name('teams.forum.messages.destroy');
    
    // Task Attachments
    Route::prefix('teams/{team}')->group(function () {
        Route::post('tasks/{task}/attachments', [TaskController::class, 'uploadAttachment'])->name('teams.tasks.attachments.upload');
        Route::get('attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])->name('teams.attachments.download');
        Route::patch('attachments/{attachment}', [TaskController::class, 'updateAttachment'])->name('teams.attachments.update');
        Route::delete('attachments/{attachment}', [TaskController::class, 'destroyAttachment'])->name('teams.attachments.destroy');
        Route::post('tasks/{task}/sync', [TaskController::class, 'syncToChildren'])->name('teams.tasks.sync-to-children')->withoutScopedBindings();
    });

    Route::post('/teams/{team}/tasks/{task}/nudge', [TaskController::class, 'nudge'])->name('teams.tasks.nudge');
    Route::post('/teams/{team}/tasks/{task}/move', [TaskController::class, 'move'])->name('teams.tasks.move');
    Route::get('/teams/{team}/tasks/status/{status}', [TaskController::class, 'byStatus'])->name('tasks.byStatus');
    Route::get('/teams/{team}/tasks/quadrant', [TaskController::class, 'byQuadrant'])->name('tasks.byQuadrant');
    Route::get('/teams/{team}/gantt', [\App\Http\Controllers\GanttController::class, 'index'])->name('teams.gantt');
    Route::get('/teams/{team}/gantt/data', [\App\Http\Controllers\GanttController::class, 'data'])->name('teams.gantt.data');
    
    // Kanban routes
    Route::get('/teams/{team}/kanban', [KanbanController::class, 'index'])->name('teams.kanban');
    Route::patch('/teams/{team}/tasks/{task}/kanban', [KanbanController::class, 'update'])->name('teams.tasks.kanban.update');
    Route::patch('/teams/{team}/kanban/columns/{column}', [KanbanController::class, 'updateColumn'])->name('teams.kanban.columns.update');
    Route::post('/teams/{team}/kanban/columns', [KanbanController::class, 'storeColumn'])->name('teams.kanban.columns.store');
    Route::post('/teams/{team}/kanban/columns/order', [KanbanController::class, 'updateColumnOrder'])->name('teams.kanban.columns.order');
    Route::post('/teams/{team}/kanban/tasks/order', [KanbanController::class, 'updateTasksOrder'])->name('teams.kanban.tasks.order');
    Route::delete('/teams/{team}/kanban/columns/{column}', [KanbanController::class, 'destroyColumn'])->name('teams.kanban.columns.destroy');
    
    // Theme route
    Route::post('/theme', [\App\Http\Controllers\ThemeController::class, 'update'])->name('theme.update');
    Route::post('/layout', [\App\Http\Controllers\LayoutController::class, 'update'])->name('layout.update');

    // Global Settings routes
    Route::middleware('can:admin')->group(function () {
        Route::get('/settings/teams', [\App\Http\Controllers\TeamController::class, 'indexAdmin'])->name('settings.teams');
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
        
        // Legal Settings
        Route::get('/settings/legal', [\App\Http\Controllers\SettingsController::class, 'legalSettings'])->name('settings.legal');
        Route::post('/settings/legal', [\App\Http\Controllers\SettingsController::class, 'updateLegalSettings'])->name('settings.legal.update');
        Route::post('/settings/telegram/test', [\App\Http\Controllers\SettingsController::class, 'testTelegram'])->name('settings.telegram.test');
        Route::post('/settings/telegram/register', [\App\Http\Controllers\SettingsController::class, 'registerTelegramWebhook'])->name('settings.telegram.register');

        // Global Skills Management (Global scope only)
        Route::get('/settings/skills', [\App\Http\Controllers\SkillController::class, 'index'])->name('settings.skills');
        Route::post('/settings/skills', [\App\Http\Controllers\SkillController::class, 'store'])->name('settings.skills.store');
        Route::patch('/settings/skills/{skill}', [\App\Http\Controllers\SkillController::class, 'update'])->name('settings.skills.update');
        Route::delete('/settings/skills/{skill}', [\App\Http\Controllers\SkillController::class, 'destroy'])->name('settings.skills.destroy');
    });

    // Team-specific Skills Management
    Route::prefix('teams/{team}')->name('teams.')->group(function () {
        Route::get('/skills', [\App\Http\Controllers\SkillController::class, 'index'])->name('skills.index');
        Route::post('/skills', [\App\Http\Controllers\SkillController::class, 'store'])->name('skills.store');
        Route::patch('/skills/{skill}', [\App\Http\Controllers\SkillController::class, 'update'])->name('skills.update');
        Route::delete('/skills/{skill}', [\App\Http\Controllers\SkillController::class, 'destroy'])->name('skills.destroy');
        Route::post('/skills/inherit', [\App\Http\Controllers\SkillController::class, 'inherit'])->name('skills.inherit');
    });

    // Google Services
    Route::get('/auth/google', [\App\Http\Controllers\GoogleController::class, 'redirect'])->name('google.auth');
    Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleController::class, 'callback'])->name('google.callback');
    Route::get('/google/sync', [\App\Http\Controllers\GoogleController::class, 'sync'])->name('google.sync');
    Route::post('/google/import', [\App\Http\Controllers\GoogleController::class, 'import'])->name('google.import');
    Route::post('/google/disconnect', [\App\Http\Controllers\GoogleController::class, 'disconnect'])->name('google.disconnect');
    Route::post('/teams/{team}/tasks/{task}/google-sync', [\App\Http\Controllers\GoogleController::class, 'syncTask'])->name('google.sync_task');

    // Media Management
    Route::get('/media', [\App\Http\Controllers\MediaController::class, 'index'])->name('media.index');
    Route::get('/media/{attachment}/download', [\App\Http\Controllers\MediaController::class, 'download'])->name('media.download');
    Route::delete('/media/{attachment}', [\App\Http\Controllers\MediaController::class, 'destroy'])->name('media.destroy');

    // Documentación y Manuales
    Route::get('/docs/{slug?}', [\App\Http\Controllers\DocumentationController::class, 'index'])->name('docs');

    // Créditos
    Route::get('/credits', [\App\Http\Controllers\CreditsController::class, 'index'])->name('credits');

    // Preferencias de usuario (sesión)
    Route::post('/preferences/hide-completed', [\App\Http\Controllers\TaskController::class, 'toggleHideCompleted'])->name('tasks.toggle-hide-completed');
    // --- Telegram Chat Experiment ---
    Route::prefix('telegram-chat')->name('telegram.chat.')->group(function () {
        Route::get('/messages', [\App\Http\Controllers\TelegramChatController::class, 'getMessages'])->name('messages');
        Route::post('/send', [\App\Http\Controllers\TelegramChatController::class, 'sendMessage'])->name('send');
        Route::delete('/messages/{message}', [\App\Http\Controllers\TelegramChatController::class, 'destroy'])->name('delete');
    });

    // Time Tracking routes
    Route::prefix('time-logs')->name('time-logs.')->group(function () {
        Route::post('/toggle-workday', [\App\Http\Controllers\TimeLogController::class, 'toggleWorkday'])->name('toggle-workday');
        Route::post('/toggle-task/{task}', [\App\Http\Controllers\TimeLogController::class, 'toggleTask'])->name('toggle-task');
        Route::get('/status', [\App\Http\Controllers\TimeLogController::class, 'status'])->name('status');
    });

    Route::get('/teams/{team}/time-reports', [\App\Http\Controllers\TimeLogController::class, 'index'])->name('teams.time-reports');
});

require __DIR__.'/auth.php';
