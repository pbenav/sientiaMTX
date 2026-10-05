<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamRole;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Traits\HandlesEisenhowerMatrix;
use Illuminate\Http\Request;

/**
 * Controlador de gestión de equipos.
 *
 * Maneja:
 *   - Listado, creación, edición y eliminación de equipos
 *   - Transferencia de propiedad
 *   - Dashboard con matriz de Eisenhower por equipo
 *   - Colores de cuadrantes y orden de equipos
 *   - Red activa, mención de usuarios, favoritos
 *   - Configuraciones premium por equipo y masivas
 */
class TeamController extends Controller
{
    use HandlesEisenhowerMatrix;
    /**
     * Listado de equipos del usuario autenticado.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $teams = auth()->user()->teams()
            ->with(['members'])
            ->orderByPivot('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('teams.index', compact('teams'));
    }

    /**
     * Listado de todos los equipos para administradores del sitio.
     *
     * Soporta búsqueda por nombre/descripción, ordenamiento y paginación.
     * Requiere autorización admin.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function indexAdmin(Request $request)
    {
        $this->authorize('admin'); // Ensure only global admins can access

        $query = Team::with(['creator'])->withCount(['members', 'tasks']);

        // Sorting
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $query->orderBy($sort, $direction);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
        }

        $perPage = $request->get('per_page', 25);
        if ($perPage === 'all') {
            $perPage = $query->count() ?: 1;
        }

        $teams = $query->paginate($perPage)->withQueryString();

        return view('settings.teams', [
            'teams' => $teams,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo equipo.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('teams.create');
    }

    /**
     * Almacena un nuevo equipo y asigna al creador como coordinador.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['slug'] = str($validated['name'])->slug();
        $validated['created_by_id'] = auth()->id();

        $team = Team::create($validated);

        // Add creator as coordinator
        $coordinatorRole = TeamRole::where('name', 'coordinator')->first();
        $team->members()->attach(auth()->id(), ['role_id' => $coordinatorRole->id]);

        return redirect()->route('teams.show', $team)
            ->with('success', __('teams.created'));
    }

    /**
     * Muestra un equipo, redirigiendo al listado de actividades.
     *
     * @param  Team  $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        return redirect()->route('teams.activities.index', $team);
    }

    /**
     * Muestra el formulario de edición de un equipo.
     *
     * @param  Team  $team
     * @return \Illuminate\View\View
     */
    public function edit(Team $team)
    {
        $this->authorize('update', $team);
        
        $skills = $team->skills()->withCount('tasks')->orderBy('name')->get();
        $teams = collect([$team]); // Solo este equipo es relevante en este contexto

        return view('teams.edit', compact('team', 'skills', 'teams'));
    }

    /**
     * Actualiza un equipo: nombre, descripción, chat IDs, cuotas de disco y configuraciones premium.
     *
     * Protege los flags has_whatsapp y has_appointments para que solo admins globales puedan modificarlos.
     * Normaliza colores hexadecimales de cuadrantes y calcula soft_disk_quota en bytes.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Team $team, \App\Actions\Teams\UpdateTeamAction $action)
    {
        $this->authorize('update', $team);

        $rules = [
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'description' => 'nullable|string|max:1000',
            'telegram_chat_id' => 'nullable|string|max:255',
            'whatsapp_chat_id' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'soft_disk_quota_gb' => 'nullable|numeric|min:0.1',
        ];

        $isAdmin = auth()->user()->is_admin;
        if ($isAdmin) {
            $rules['disk_quota_gb'] = 'required|numeric|min:0.1';
        }

        $validated = $request->validate($rules);
        
        $action->execute($team, $validated, (bool)$isAdmin, $request->soft_disk_quota_gb);

        return redirect()->back()->with('success', __('teams.updated'));
    }

    /**
     * Elimina permanentemente un equipo (forceDelete).
     *
     * @param  Team  $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        $team->forceDelete();

        $redirectRoute = auth()->user()->is_admin ? 'settings.teams' : 'teams.index';
        return redirect()->route($redirectRoute)
            ->with('success', __('teams.deleted'));
    }



    /**
     * Dashboard con matriz de Eisenhower para un equipo.
     *
     * Carga actividades con relaciones, aplica filtros de visibilidad según rol (manager vs member),
     * agrupa por cuadrante, maneja tareas completadas con límite configurable, y carga servicios
     * con sus reportes recientes.
     *
     * @param  Team  $team
     * @return \Illuminate\View\View
     */
    public function dashboard(Team $team, \App\Services\TeamDashboardService $dashboardService)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $user = auth()->user();
        $isManager = $team->isManager($user);
        
        $hideCompleted = request()->has('filter_matrix') ? request()->has('hide_completed') : session('hide_completed_tasks', true);
        if (request()->has('filter_matrix')) {
            session(['hide_completed_tasks' => $hideCompleted]);
        }

        $data = $dashboardService->getDashboardData($team, $user, $isManager, request('skill_id'), $hideCompleted);
        
        $quadrants = $data['quadrants'];
        $tasks = $data['tasks'];
        $completedTasks = $data['completedTasks'];
        $completedLimit = $data['completedLimit'];
        
        $skills = \App\Models\Skill::forTeam($team->id)->get();
        $services = $team->services()->with(['reports' => function($q) {
            $q->latest()->limit(5);
        }])->get();

        return view('teams.dashboard', compact('team', 'quadrants', 'tasks', 'hideCompleted', 'skills', 'completedTasks', 'completedLimit', 'services'));
    }

    /**
     * Transfiere la propiedad de un equipo a otro usuario.
     *
     * Asigna el rol de coordinador al nuevo y antiguo propietario.
     * Requiere autorización transferOwnership.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function transferOwnership(Request $request, Team $team)
    {
        $this->authorize('transferOwnership', $team);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $newOwner = User::findOrFail($validated['user_id']);

        // Check if user is member of the team
        if (!$team->members()->where('user_id', $newOwner->id)->exists()) {
            return back()->withErrors(['user_id' => 'El nuevo propietario debe ser miembro del equipo.']);
        }

        $oldOwnerId = auth()->id();

        // Transfer ownership
        $team->update(['created_by_id' => $newOwner->id]);

        // Ensure both new and old owner have coordinator role
        $coordinatorRole = TeamRole::where('name', 'coordinator')->first();
        if ($coordinatorRole) {
            $team->members()->updateExistingPivot($newOwner->id, ['role_id' => $coordinatorRole->id]);
            $team->members()->updateExistingPivot($oldOwnerId, ['role_id' => $coordinatorRole->id]);
        }

        // Si el usuario es administrador y no es miembro del equipo, volvemos a la gestión global
        if (auth()->user()->is_admin && !$team->members()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('settings.teams')->with('success', __('teams.ownership_transferred'));
        }

        return redirect()->route('teams.show', $team)
            ->with('success', __('teams.ownership_transferred'));
    }

    /**
     * Actualiza el color de un cuadrante de la matriz de Eisenhower para el equipo.
     *
     * Normaliza colores hexadecimales de 4 a 6 dígitos. Requiere autorización update.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuadrantColor(Request $request, Team $team, \App\Actions\Teams\UpdateQuadrantColorAction $action)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'quadrant' => 'required|integer|between:1,4',
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);

        $color = $validated['color'];
        if (strlen($color) === 4) {
            $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        $action->execute($team, 'q'.$validated['quadrant'], $color);

        return response()->json(['success' => true]);
    }
    /**
     * Actualiza el orden de arrastre de equipos para el usuario autenticado.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:teams,id',
        ]);

        $user = auth()->user();
        foreach ($validated['order'] as $index => $teamId) {
            $user->teams()->updateExistingPivot($teamId, ['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
    /**
     * Obtiene la lista parcial de miembros activos de la red para actualizaciones en tiempo real.
     *
     * Devuelve HTML parcial y/o datos JSON de heatmap con ubicación, estado y contadores.
     * Requiere autorización view.
     *
     * @param  Team  $team
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function activeNetwork(Team $team, \Illuminate\Http\Request $request)
    {
        $this->authorize('view', $team);

        $members = $team->getActiveMembers();

        if ($request->wantsJson() || $request->input('json')) {
            $heatmapData = $members->whereNotNull('location_lat')->map(function($u) {
                return [
                    'user_id' => $u->id,
                    'photo' => $u->profile_photo_url,
                    'lat' => (float)$u->location_lat,
                    'lng' => (float)$u->location_lng,
                    'count' => max(10, $u->experience_points / 2),
                    'name' => app(\App\Services\DemoModeService::class)->isActive() ? app(\App\Services\DemoModeService::class)->mask($u->getRawOriginal('name') ?? $u->name, 'name') : $u->name,
                    'area' => $u->working_area_name,
                    'radius' => (int)($u->impact_radius ?? 10) * 1000,
                    'is_working' => clone $u, // Hack to use isWorking correctly
                    'is_active' => $u->last_activity_at && $u->last_activity_at->gt(now()->subMinutes(15))
                ];
            })->map(function($data) {
                $u = $data['is_working'];
                $data['is_working'] = $u->last_login_at ? $u->isWorking() : false;
                return $data;
            })->values();

            $counts = ['working' => 0, 'online' => 0, 'sleeping' => 0, 'offline' => 0];
            foreach ($members as $m) {
                $status = $m->getStatusInfo()['status'];
                if (isset($counts[$status])) $counts[$status]++;
            }

            return response()->json([
                'html' => view('teams.partials.active-network-list', compact('members'))->render(),
                'mapData' => $heatmapData,
                'counts' => $counts
            ]);
        }

        return view('teams.partials.active-network-list', compact('members'));
    }

    /**
     * Obtiene miembros del equipo para menciones en formato JSON.
     *
     * @param  Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function mentionUsers(Team $team)
    {
        $this->authorize('view', $team);

        $members = $team->members()
            ->select('users.id', 'users.name')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->profile_photo_url,
                ];
            });

        return response()->json($members);
    }

    /**
     * Alterna si un equipo es favorito del usuario autenticado.
     *
     * @param  Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFavorite(Team $team)
    {
        $this->authorize('view', $team);
        $user = auth()->user();
        
        $isCurrentFavorite = $user->favorite_team_id === $team->id;
        
        $user->update([
            'favorite_team_id' => $isCurrentFavorite ? null : $team->id
        ]);

        return response()->json([
            'success' => true,
            'is_favorite' => !$isCurrentFavorite
        ]);
    }

    /**
     * Alterna configuración individual (Premium) de un equipo.
     *
     * Solo admin. Alterna has_appointments, has_whatsapp o microsites_enabled.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleSetting(Request $request, Team $team)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'setting' => 'required|string|in:has_appointments,has_whatsapp,microsites_enabled,surveys_enabled',
        ]);

        $setting = $validated['setting'];
        $settings = $team->settings ?? [];
        $currentValue = $settings[$setting] ?? false;
        
        $settings[$setting] = !$currentValue;
        $team->update(['settings' => $settings]);

        $label = match($setting) {
            'has_appointments' => 'Citas Previas',
            'microsites_enabled' => 'Micrositios',
            'surveys_enabled' => 'Encuestas',
            default => 'WhatsApp'
        };
        $statusText = $settings[$setting] ? 'habilitada' : 'deshabilitada';

        return back()->with('success', "Funcionalidad de {$label} {$statusText} para el equipo {$team->name}.");
    }

    /**
     * Activa o desactiva una configuración premium en todos los equipos del sistema.
     *
     * Solo admin. Aplica a has_appointments, has_whatsapp o microsites_enabled.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkSettings(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'setting' => 'required|string|in:has_appointments,has_whatsapp,microsites_enabled,surveys_enabled',
            'value' => 'required|boolean',
        ]);

        $setting = $validated['setting'];
        $value = (bool)$validated['value'];

        $teams = Team::all();
        foreach ($teams as $team) {
            $settings = $team->settings ?? [];
            $settings[$setting] = $value;
            $team->update(['settings' => $settings]);
        }

        $label = match($setting) {
            'has_appointments' => 'Citas Previas',
            'microsites_enabled' => 'Micrositios',
            'surveys_enabled' => 'Encuestas',
            default => 'WhatsApp'
        };
        $statusText = $value ? 'habilitado' : 'deshabilitado';

        return back()->with('success', "Se ha {$statusText} el Portal de {$label} para todos los equipos del sistema.");
    }
}
