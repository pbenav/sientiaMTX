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
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

// Telegram Webhook (Public)
Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

// WhatsApp Webhook (Public)
Route::post('/whatsapp/webhook', [\App\Http\Controllers\WhatsappController::class, 'webhook'])->name('whatsapp.webhook');

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
    Route::delete('/profile/sessions/{sessionId}', [ProfileController::class, 'logoutSession'])->name('profile.sessions.logout');
    
    // Multi-Factor Authentication (MFA / 2FA) Routes under ENS Guidelines
    Route::post('/profile/two-factor/enable', [ProfileController::class, 'enableTwoFactor'])->name('profile.two-factor.enable');
    Route::post('/profile/two-factor/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.two-factor.confirm');
    Route::post('/profile/two-factor/disable', [ProfileController::class, 'disableTwoFactor'])->name('profile.two-factor.disable');

    Route::patch('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::patch('/profile/ai', [ProfileController::class, 'updateAi'])->name('profile.ai.update');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
    Route::patch('/profile/chat-integrations', [ProfileController::class, 'updateChatIntegrations'])->name('profile.chat-integrations.update');
    Route::post('/notifications/subscribe', [\App\Http\Controllers\WebPushController::class, 'store'])->name('webpush.subscribe');
    Route::post('/notifications/unsubscribe', [\App\Http\Controllers\WebPushController::class, 'destroy'])->name('webpush.unsubscribe');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/telegram/test', [ProfileController::class, 'testTelegram'])->name('profile.telegram.test');
    Route::post('/profile/invitations', [ProfileController::class, 'generateInvitation'])->name('profile.invitations.generate');
    Route::post('/whatsapp/restart', [\App\Http\Controllers\WhatsappController::class, 'restart'])->name('whatsapp.restart');
    Route::get('/whatsapp/status', [\App\Http\Controllers\WhatsappController::class, 'status'])->name('whatsapp.status');
    Route::get('/whatsapp/personal-status', [\App\Http\Controllers\WhatsappController::class, 'personalStatus'])->name('whatsapp.personal-status');
    Route::post('/whatsapp/personal-restart', [\App\Http\Controllers\WhatsappController::class, 'personalRestart'])->name('whatsapp.personal-restart');
    Route::get('/whatsapp/team-status', [\App\Http\Controllers\WhatsappController::class, 'teamStatus'])->name('whatsapp.team-status');
    Route::post('/whatsapp/team-restart', [\App\Http\Controllers\WhatsappController::class, 'teamRestart'])->name('whatsapp.team-restart');
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
    Route::get('/teams/{team}/dashboard', [\App\Http\Controllers\TimeLogController::class, 'index'])->name('teams.dashboard');
    Route::get('/teams/{team}/eisenhower', [TeamController::class, 'dashboard'])->name('teams.eisenhower');
    Route::get('/teams/{team}/active-network', [TeamController::class, 'activeNetwork'])->name('teams.active-network');
    Route::patch('/teams/{team}/quadrants/color', [TeamController::class, 'updateQuadrantColor'])->name('teams.quadrants.color');
    Route::get('/teams/{team}/members', [TeamController::class, 'members'])->name('teams.members');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.addMember');
    Route::post('/teams/{team}/members/bulk', [TeamController::class, 'bulkAddMembers'])->name('teams.addMembersBulk');
    Route::patch('/teams/{team}/members/{user}/role', [TeamController::class, 'updateMemberRole'])->name('teams.updateMemberRole');
    Route::patch('/teams/{team}/members/{user}/info', [TeamController::class, 'updateMemberInfo'])->name('teams.updateMemberInfo');
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.removeMember');
    Route::delete('/teams/{team}/invitations/{invitation}', [TeamController::class, 'removeInvitation'])->name('teams.invitations.destroy');
    Route::post('/teams/order', [TeamController::class, 'updateOrder'])->name('teams.update-order');
    Route::get('/teams/{team}/mentions', [TeamController::class, 'mentionUsers'])->name('teams.mentions');
    Route::post('/teams/{team}/favorite', [TeamController::class, 'toggleFavorite'])->name('teams.toggle-favorite');

    // Storage Management
    Route::get('/teams/{team}/storage', [\App\Http\Controllers\StorageController::class, 'index'])->name('teams.storage.index');
    Route::post('/teams/{team}/storage/purge', [\App\Http\Controllers\StorageController::class, 'purge'])->name('teams.storage.purge');
    Route::get('/teams/{team}/quota-status', [\App\Http\Controllers\StorageController::class, 'quotaStatus'])->name('teams.quota-status');

    // Groups routes
    Route::post('/teams/{team}/groups', [GroupController::class, 'store'])->name('teams.groups.store');
    Route::patch('/teams/{team}/groups/{group}', [GroupController::class, 'update'])->name('teams.groups.update');
    Route::delete('/teams/{team}/groups/{group}', [GroupController::class, 'destroy'])->name('teams.groups.destroy');
    Route::post('/teams/{team}/groups/{group}/members', [GroupController::class, 'addMember'])->name('teams.groups.addMember');
    Route::delete('/teams/{team}/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('teams.groups.removeMember');

    // Tasks routes
    Route::get('/teams/{team}/tasks/search', [TaskController::class, 'search'])->name('teams.tasks.search');
    Route::get('/teams/{team}/tasks/status/{status}', [TaskController::class, 'byStatus'])->name('tasks.byStatus');
    Route::get('/teams/{team}/tasks/quadrant', [TaskController::class, 'byQuadrant'])->name('tasks.byQuadrant');
    Route::post('/teams/{team}/tasks/import-json', [TaskController::class, 'importJson'])->name('teams.tasks.import-json');
    Route::patch('/teams/{team}/tasks/bulk-update', [TaskController::class, 'bulkUpdate'])->name('teams.tasks.bulk-update');
    Route::delete('/teams/{team}/tasks/bulk-delete', [TaskController::class, 'bulkDelete'])->name('teams.tasks.bulk-delete');
    Route::post('/teams/{team}/tasks/bulk-merge', [TaskController::class, 'bulkMerge'])->name('teams.tasks.bulk-merge');
    Route::post('/teams/{team}/tasks/purge-trash', [TaskController::class, 'purgeTrash'])->name('teams.tasks.purge-trash');
    Route::resource('teams.tasks', TaskController::class)->except(['show', 'edit', 'update', 'destroy']);

    // Override show/edit/update/destroy to avoid Scoped Binding and handle SoftDeletes
    Route::prefix('teams/{team}')->group(function() {
        Route::get('tasks/{task}', [TaskController::class, 'show'])->name('teams.tasks.show')->withTrashed()->withoutScopedBindings();
        Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('teams.tasks.edit')->withTrashed()->withoutScopedBindings();
        Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('teams.tasks.update')->withTrashed()->withoutScopedBindings();
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('teams.tasks.destroy')->withTrashed()->withoutScopedBindings();
    });

    // Expedientes routes
    Route::resource('teams.expedientes', \App\Http\Controllers\ExpedienteController::class);
    Route::post('teams/{team}/expedientes/{expediente}/attachments', [\App\Http\Controllers\ExpedienteController::class, 'uploadAttachment'])->name('teams.expedientes.attachments.upload');
    Route::post('teams/{team}/expedientes/{expediente}/link-tasks', [\App\Http\Controllers\ExpedienteController::class, 'linkTasks'])->name('teams.expedientes.link-tasks');
    Route::post('teams/{team}/expedientes/{expediente}/link-related', [\App\Http\Controllers\ExpedienteController::class, 'linkRelated'])->name('teams.expedientes.link-related');
    Route::post('teams/{team}/expedientes/{expediente}/unlink-task/{task}', [\App\Http\Controllers\ExpedienteController::class, 'unlinkTask'])->name('teams.expedientes.unlink-task');

    // Forum routes inside team
    Route::get('/teams/{team}/forum', [ForumController::class, 'index'])->name('teams.forum.index');
    Route::post('/teams/{team}/forum', [ForumController::class, 'store'])->name('teams.forum.store');
    Route::get('/teams/{team}/forum/{thread}', [ForumController::class, 'show'])->name('teams.forum.show');
    Route::patch('/teams/{team}/forum/{thread}', [ForumController::class, 'update'])->name('teams.forum.update');
    Route::delete('/teams/{team}/forum/{thread}', [ForumController::class, 'destroy'])->name('teams.forum.destroy');
    Route::post('/teams/{team}/forum/cleanup', [ForumController::class, 'cleanupOrphans'])->name('teams.forum.cleanup');
    
    // Forum messages
    Route::post('/teams/{team}/forum/{thread}/messages', [ForumMessageController::class, 'store'])->name('teams.forum.messages.store');
    Route::patch('/teams/{team}/forum/messages/{message}', [ForumMessageController::class, 'update'])->name('teams.forum.messages.update');
    Route::delete('/teams/{team}/forum/messages/{message}', [ForumMessageController::class, 'destroy'])->name('teams.forum.messages.destroy');
    Route::post('/teams/{team}/forum/messages/{message}/vote', [ForumMessageController::class, 'voteToggle'])->name('teams.forum.messages.vote');
    Route::post('/teams/{team}/forum/upload-image', [ForumMessageController::class, 'uploadImage'])->name('teams.forum.upload_image');
    Route::post('/teams/{team}/forum/upload-attachment', [ForumMessageController::class, 'uploadAttachment'])->name('teams.forum.upload_attachment');
    
    // Task Attachments
    Route::prefix('teams/{team}')->group(function () {
        Route::post('tasks/{task}/attachments', [TaskController::class, 'uploadAttachment'])->name('teams.tasks.attachments.upload');
        Route::get('attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])->name('teams.attachments.download');
        Route::get('attachments/{attachment}/view', [TaskController::class, 'viewAttachment'])->name('teams.attachments.view');
        Route::patch('attachments/{attachment}', [TaskController::class, 'updateAttachment'])->name('teams.attachments.update');
        Route::delete('attachments/{attachment}', [TaskController::class, 'destroyAttachment'])->name('teams.attachments.destroy');
        Route::get('attachments/history/{attachment}', [TaskController::class, 'attachmentHistory'])->name('teams.attachments.history');
        Route::post('tasks/{task}/sync', [TaskController::class, 'syncToChildren'])->name('teams.tasks.sync-to-children')->withoutScopedBindings();
        
        // Private Notes
        Route::post('tasks/{task}/private-notes', [\App\Http\Controllers\TaskNoteController::class, 'update'])->name('teams.tasks.private-notes.update');
    });

    Route::post('/teams/{team}/tasks/{task}/nudge', [TaskController::class, 'nudge'])->name('teams.tasks.nudge');
    Route::post('/teams/{team}/tasks/{task}/rate', [TaskController::class, 'rate'])->name('teams.tasks.rate');
    Route::post('/teams/{team}/tasks/bulk-nudge', [TaskController::class, 'bulkNudge'])->name('teams.tasks.bulk-nudge');
    Route::post('/teams/{team}/tasks/{task}/move', [TaskController::class, 'move'])->name('teams.tasks.move');
    Route::post('/teams/{team}/tasks/{task}/toggle-auto-priority', [TaskController::class, 'toggleAutoPriority'])->name('teams.tasks.toggle-auto-priority');
    Route::post('/teams/{team}/tasks/{task}/copy-to-team', [TaskController::class, 'copyToTeam'])->name('teams.tasks.copy-to-team');
    Route::get('/teams/{team}/tasks/{task}/export-json', [TaskController::class, 'exportJson'])->name('teams.tasks.export-json');
    Route::post('/teams/{team}/tasks/{task}/merge', [TaskController::class, 'merge'])->name('teams.tasks.merge');
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

    // Service routes
    Route::prefix('teams/{team}/services')->group(function() {
        Route::post('/', [ServiceController::class, 'store'])->name('teams.services.store');
        Route::post('/reorder', [ServiceController::class, 'reorder'])->name('teams.services.reorder');
        Route::post('/{service}/report', [ServiceController::class, 'report'])->name('teams.services.report');
        Route::get('/{service}/incidents', [ServiceController::class, 'incidents'])->name('teams.services.incidents');
        Route::patch('/{service}', [ServiceController::class, 'update'])->name('teams.services.update');
        Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('teams.services.destroy');
    });
    
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
        Route::get('/settings/users/bulk-email', [\App\Http\Controllers\SettingsController::class, 'bulkEmailComposer'])->name('settings.users.bulk-email');
        Route::post('/settings/users/bulk-email', [\App\Http\Controllers\SettingsController::class, 'sendBulkEmail'])->name('settings.users.bulk-email.send');
        Route::get('/settings/users/create', [\App\Http\Controllers\SettingsController::class, 'createUser'])->name('settings.users.create');
        Route::post('/settings/users', [\App\Http\Controllers\SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::post('/settings/users/{user}/toggle-admin', [\App\Http\Controllers\SettingsController::class, 'toggleAdmin'])->name('settings.users.toggle-admin');
        Route::get('/settings/users/{user}/edit', [\App\Http\Controllers\SettingsController::class, 'editUser'])->name('settings.users.edit');
        Route::put('/settings/users/{user}', [\App\Http\Controllers\SettingsController::class, 'updateUser'])->name('settings.users.update');
        Route::delete('/settings/users/{user}', [\App\Http\Controllers\SettingsController::class, 'destroyUser'])->name('settings.users.destroy');
        Route::post('/settings/users/{user}/force-logout', [\App\Http\Controllers\SettingsController::class, 'forceLogoutUser'])->name('settings.users.force-logout');
        Route::post('/settings/users/{user}/invitations/{invitation}/accept', [\App\Http\Controllers\SettingsController::class, 'acceptUserInvitation'])->name('settings.users.accept-invitation');
        Route::post('/settings/users/{user}/approve', [\App\Http\Controllers\SettingsController::class, 'approveUser'])->name('settings.users.approve');
        
        // Legal Settings
        Route::get('/settings/legal', [\App\Http\Controllers\SettingsController::class, 'legalSettings'])->name('settings.legal');
        Route::post('/settings/legal', [\App\Http\Controllers\SettingsController::class, 'updateLegalSettings'])->name('settings.legal.update');
        Route::post('/settings/telegram/test', [\App\Http\Controllers\SettingsController::class, 'testTelegram'])->name('settings.telegram.test');
        Route::post('/settings/telegram/register', [\App\Http\Controllers\SettingsController::class, 'registerTelegramWebhook'])->name('settings.telegram.register');
        Route::get('/settings/whatsapp', [\App\Http\Controllers\WhatsappController::class, 'index'])->name('settings.whatsapp');

        Route::get('/settings/appearance', [\App\Http\Controllers\SettingsController::class, 'appearanceSettings'])->name('settings.appearance');
        Route::post('/settings/appearance', [\App\Http\Controllers\SettingsController::class, 'updateAppearanceSettings'])->name('settings.appearance.update');
        Route::get('/settings/security', [\App\Http\Controllers\SettingsController::class, 'securityLogs'])->name('settings.security');

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

    // Surveys routes
    Route::prefix('teams/{team}')->name('teams.')->group(function () {
        Route::get('/surveys', [\App\Http\Controllers\SurveyController::class, 'index'])->name('surveys.index');
        Route::get('/surveys/create', [\App\Http\Controllers\SurveyController::class, 'create'])->name('surveys.create');
        Route::post('/surveys', [\App\Http\Controllers\SurveyController::class, 'store'])->name('surveys.store');
        Route::get('/surveys/{survey}', [\App\Http\Controllers\SurveyController::class, 'show'])->name('surveys.show');
        Route::get('/surveys/{survey}/edit', [\App\Http\Controllers\SurveyController::class, 'edit'])->name('surveys.edit');
        Route::patch('/surveys/{survey}', [\App\Http\Controllers\SurveyController::class, 'update'])->name('surveys.update');
        Route::post('/surveys/{survey}/vote', [\App\Http\Controllers\SurveyController::class, 'vote'])->name('surveys.vote');
        Route::post('/surveys/{survey}/close', [\App\Http\Controllers\SurveyController::class, 'close'])->name('surveys.close');
        Route::post('/surveys/{survey}/reactivate', [\App\Http\Controllers\SurveyController::class, 'reactivate'])->name('surveys.reactivate');
        Route::delete('/surveys/{survey}', [\App\Http\Controllers\SurveyController::class, 'destroy'])->name('surveys.destroy');
        Route::get('/surveys/{survey}/results', [\App\Http\Controllers\SurveyController::class, 'results'])->name('surveys.results');
        Route::post('/surveys/{survey}/duplicate', [\App\Http\Controllers\SurveyController::class, 'duplicate'])->name('surveys.duplicate');
    });

    // Global Surveys routes
    Route::prefix('global-surveys')->name('global-surveys.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SurveyController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SurveyController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SurveyController::class, 'store'])->name('store');
        Route::get('/{survey}', [\App\Http\Controllers\SurveyController::class, 'show'])->name('show');
        Route::get('/{survey}/edit', [\App\Http\Controllers\SurveyController::class, 'edit'])->name('edit');
        Route::patch('/{survey}', [\App\Http\Controllers\SurveyController::class, 'update'])->name('update');
        Route::post('/{survey}/vote', [\App\Http\Controllers\SurveyController::class, 'vote'])->name('vote');
        Route::post('/{survey}/close', [\App\Http\Controllers\SurveyController::class, 'close'])->name('close');
        Route::post('/{survey}/reactivate', [\App\Http\Controllers\SurveyController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{survey}', [\App\Http\Controllers\SurveyController::class, 'destroy'])->name('destroy');
        Route::get('/{survey}/results', [\App\Http\Controllers\SurveyController::class, 'results'])->name('results');
        Route::post('/{survey}/duplicate', [\App\Http\Controllers\SurveyController::class, 'duplicate'])->name('duplicate');
    });

    // Google Services
    Route::get('/auth/google', [\App\Http\Controllers\GoogleController::class, 'redirect'])->name('google.auth');
    Route::get('/google/callback', [\App\Http\Controllers\GoogleController::class, 'callback'])->name('google.callback');
    Route::get('/google/sync', [\App\Http\Controllers\GoogleController::class, 'sync'])->name('google.sync');
    Route::post('/google/import', [\App\Http\Controllers\GoogleController::class, 'import'])->name('google.import');
    Route::post('/google/disconnect', [\App\Http\Controllers\GoogleController::class, 'disconnect'])->name('google.disconnect');
    Route::post('/teams/{team}/tasks/{task}/google-sync', [\App\Http\Controllers\GoogleController::class, 'syncTask'])->name('google.sync_task');
    Route::post('/teams/{team}/tasks/{task}/google-calendar', [\App\Http\Controllers\GoogleController::class, 'exportTaskToCalendar'])->name('google.export_calendar');

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
    Route::post('/preferences/subtasks-visibility', [\App\Http\Controllers\TaskController::class, 'toggleSubtasksVisibility'])->name('tasks.toggle-subtasks-visibility');
    // --- Telegram Chat Experiment ---
    Route::prefix('telegram-chat')->name('telegram.chat.')->group(function () {
        Route::get('/messages', [\App\Http\Controllers\TelegramChatController::class, 'getMessages'])->name('messages');
        Route::get('/mentions', [\App\Http\Controllers\TelegramChatController::class, 'getMentions'])->name('mentions');
        Route::post('/send', [\App\Http\Controllers\TelegramChatController::class, 'sendMessage'])->name('send');
        Route::patch('/messages/{message}', [\App\Http\Controllers\TelegramChatController::class, 'update'])->name('update');
        Route::delete('/messages/{message}', [\App\Http\Controllers\TelegramChatController::class, 'destroy'])->name('delete');
    });

    // --- WhatsApp Chat ---
    Route::prefix('whatsapp-chat')->name('whatsapp.chat.')->group(function () {
        Route::get('/messages', [\App\Http\Controllers\WhatsappChatController::class, 'getMessages'])->name('messages');
        Route::post('/send', [\App\Http\Controllers\WhatsappChatController::class, 'sendMessage'])->name('send');
        Route::patch('/messages/{message}', [\App\Http\Controllers\WhatsappChatController::class, 'update'])->name('update');
        Route::delete('/messages/{message}', [\App\Http\Controllers\WhatsappChatController::class, 'destroy'])->name('delete');
        Route::post('/sync', [\App\Http\Controllers\WhatsappController::class, 'sync'])->name('sync');
    });

    // --- AI Assistant ---
    Route::get('/ai/models', [\App\Http\Controllers\AiChatController::class, 'getAvailableModels'])->name('ai.models');
    Route::get('/ai/history', [\App\Http\Controllers\AiChatController::class, 'getHistory'])->name('ai.history');
    Route::post('/ai/ask', [\App\Http\Controllers\AiChatController::class, 'ask'])->name('ai.ask');
    Route::post('/ai/undo', [\App\Http\Controllers\AiChatController::class, 'undoLastTransfer'])->name('ai.undo');
    Route::delete('/ai/history', [\App\Http\Controllers\AiChatController::class, 'clearHistory'])->name('ai.clear-history');
    Route::post('/teams/{team}/tasks/{task}/ai/transfer', [\App\Http\Controllers\AiChatController::class, 'transferContent'])->name('ai.transfer');
    Route::post('/teams/{team}/forum/{thread}/ai/transfer', [\App\Http\Controllers\AiChatController::class, 'transferForumContent'])->name('ai.transfer_forum');
    Route::post('/ai/transfer-global/{team?}', [\App\Http\Controllers\AiChatController::class, 'transferGlobalContent'])->name('ai.transfer_global');

    // Time Tracking routes
    Route::prefix('time-logs')->name('time-logs.')->group(function () {
        Route::post('/toggle-workday', [\App\Http\Controllers\TimeLogController::class, 'toggleWorkday'])->name('toggle-workday');
        Route::post('/toggle-task/{task}', [\App\Http\Controllers\TimeLogController::class, 'toggleTask'])->name('toggle-task');
        Route::get('/status', [\App\Http\Controllers\TimeLogController::class, 'status'])->name('status');
    });

    Route::get('/teams/{team}/time-reports', [\App\Http\Controllers\TimeLogController::class, 'index'])->name('teams.time-reports');
    // Google Drive Actions
    Route::post('/teams/{team}/attachments/{attachment}/to-drive', [GoogleDriveController::class, 'uploadToDrive'])->name('teams.attachments.to-drive');
    Route::post('/google/drive/save-response', [GoogleDriveController::class, 'saveAiResponse'])->name('google.drive.save-response');
    Route::get('/google/drive/list', [GoogleDriveController::class, 'listContents'])->name('google.drive.list');
    Route::get('/teams/{team}/google/drive/download-content', [GoogleDriveController::class, 'downloadContent'])->name('google.drive.download-content');
    Route::post('/teams/{team}/attachments/from-drive', [GoogleDriveController::class, 'attachFromDrive'])->name('teams.attachments.from-drive');

    // QuickNotes Routes
    Route::patch('quick-notes/bulk', [\App\Http\Controllers\QuickNoteController::class, 'bulkUpdate'])->name('quick-notes.bulk-update');
    Route::apiResource('quick-notes', \App\Http\Controllers\QuickNoteController::class);
    Route::post('quick-notes/{quick_note}/attachment', [\App\Http\Controllers\QuickNoteController::class, 'uploadAttachment'])->name('quick-notes.attachment');
    Route::post('quick-notes/{quick_note}/attachment/{attachment}/transcribe', [\App\Http\Controllers\QuickNoteController::class, 'transcribeAttachment'])->name('quick-notes.attachment.transcribe');
    Route::delete('quick-notes/{quick_note}/attachment/{attachment}', [\App\Http\Controllers\QuickNoteController::class, 'deleteAttachment'])->name('quick-notes.attachment.destroy');

    // Waitlist Routes
    Route::get('/waitlist', function() {
        if (auth()->user()->is_approved) {
            return redirect()->route('dashboard');
        }
        return view('auth.waitlist');
    })->name('waitlist');

    Route::post('/waitlist/redeem', function(\Illuminate\Http\Request $request) {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->code);
        $invitation = \App\Models\Invitation::where(function($query) use ($code) {
            $query->where('code', $code)
                  ->orWhere('code', strtoupper($code))
                  ->orWhere('code', strtolower($code));
        })->whereNull('used_at')->first();

        if (!$invitation) {
            return back()->withErrors(['code' => __('El código VIP no es válido o ya ha sido consumido.')]);
        }

        $user = auth()->user();
        $user->update(['is_approved' => true]);

        $invitation->update(['used_at' => now()]);
        if ($invitation->user_id) {
            $invitation->user()->decrement('invitations_left');
        }

        return redirect()->route('dashboard')->with('success', __('¡Pase VIP canjeado con éxito! Tu cuenta ha sido aprobada.'));
    })->name('waitlist.redeem');

    // --- Internal Direct Chat and Video Conference ---
    Route::get('/chat/check', [\App\Http\Controllers\ChatMessageController::class, 'check'])->name('chat.check');
    Route::get('/chat/{receiverId}', [\App\Http\Controllers\ChatMessageController::class, 'index'])->name('chat.index');
    Route::post('/chat', [\App\Http\Controllers\ChatMessageController::class, 'store'])->name('chat.store');
    Route::post('/chat/call', [\App\Http\Controllers\ChatMessageController::class, 'startCall'])->name('chat.call');
    Route::delete('/chat/clear/{receiverId}', [\App\Http\Controllers\ChatMessageController::class, 'clear'])->name('chat.clear');
});

require __DIR__.'/auth.php';

// OnlyOffice Integration Routes
use App\Http\Controllers\OnlyOfficeController;
Route::middleware(['auth'])->group(function () {
    Route::get('/attachments/{attachment}/edit', [OnlyOfficeController::class, 'edit'])->name('onlyoffice.edit');
    // Crear un documento nuevo vacío directamente desde una tarea y abrir el editor
    Route::post('/teams/{team}/tasks/{task}/documents/create', [OnlyOfficeController::class, 'createDocument'])->name('onlyoffice.create');
});
Route::get('/onlyoffice/download/{attachment}', [OnlyOfficeController::class, 'downloadFile'])->name('onlyoffice.download');
Route::post('/onlyoffice/callback/{attachment}', [OnlyOfficeController::class, 'callback'])->name('onlyoffice.callback');

