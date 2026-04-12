<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the notifications.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Si la notificación tiene una URL de destino en sus datos, redirigir allí
        if (isset($notification->data['url'])) {
            return redirect($notification->data['url']);
        }

        // Si es una tarea, forum, etc, intentar construir la URL si no viene explícita
        if (isset($notification->data['task_id']) && isset($notification->data['team_id'])) {
            return redirect()->route('teams.tasks.show', [$notification->data['team_id'], $notification->data['task_id']]);
        }

        return redirect()->back()->with('status', 'notification-read');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('status', 'all-notifications-read');
    }
}
