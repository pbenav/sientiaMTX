<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use App\Models\ForumMessage;

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
     * Mark a specific notification as read and redirect to resource.
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // PRIORIDAD 1: Comprobación de Tarea (Task)
        if (isset($notification->data['task_id']) && isset($notification->data['team_id'])) {
            $taskExists = Task::where('id', $notification->data['task_id'])->exists();
            
            if (!$taskExists) {
                return redirect()->back()->with('warning', __('notifications.resource_deleted', ['resource' => 'tarea']));
            }

            return redirect()->route('teams.tasks.show', [$notification->data['team_id'], $notification->data['task_id']]);
        }

        // PRIORIDAD 2: Comprobación de Mensaje de Foro (Forum Message)
        if (isset($notification->data['message_id']) && isset($notification->data['team_id'])) {
            $messageExists = ForumMessage::where('id', $notification->data['message_id'])->exists();
            
            if (!$messageExists) {
                return redirect()->back()->with('warning', __('notifications.resource_deleted', ['resource' => 'mensaje']));
            }

            if (isset($notification->data['thread_id'])) {
                return redirect()->route('teams.forum.threads.show', [$notification->data['team_id'], $notification->data['thread_id']]);
            }
        }

        // PRIORIDAD 3: URL explícita (Solo si no hemos redirigido por ID específico arriba)
        if (isset($notification->data['url'])) {
            return redirect($notification->data['url']);
        }

        return redirect()->back()->with('status', 'notification-read');
    }

    /**
     * Process bulk actions on notifications.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'string',
            'action' => 'required|in:mark_as_read,delete'
        ]);

        $notifications = Auth::user()->notifications()->whereIn('id', $request->notification_ids);

        if ($request->action === 'mark_as_read') {
            $notifications->update(['read_at' => now()]);
            $message = __('notifications.bulk_read_success');
        } elseif ($request->action === 'delete') {
            $notifications->delete();
            $message = __('notifications.bulk_delete_success');
        }

        return redirect()->back()->with('success', $message);
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
