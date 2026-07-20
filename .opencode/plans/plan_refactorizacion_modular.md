# Plan de Refactorización Modular — SientiaMTX

> **Objetivo:** Dividir los archivos grandes en componentes pequeños y cohesivos usando Traits,
  Service Classes, Action Classes, Blade Components, Events/Listeners y Repository Pattern.
>
> **Regla de oro:** NO romper funcionalidad existente. Cada paso mantiene comportamiento idéntico.
>
> **Stack:** Laravel 11 + Blade + Alpine.js + Eloquent ORM
>
> **Ruta base:** `~/Desarrollo/Sientia/Laravel/sientiaMTX`

---

## Contenido

1. Resumen ejecutivo
2. Análisis de archivos críticos
3. Estrategia de refactorización
4. Fase 1 — Traits para Models
5. Fase 2 — Dividir Services
6. Fase 3 — Extraer Actions y Form Requests
7. Fase 4 — Blade Components y Partials
8. Fase 5 — JavaScript Modular
9. Fase 6 — Events/Listeners
10. Orden de ejecución (Sprints 1–8)
11. Checklist y criterios de aceptación

---

## 1. Resumen ejecutivo

### Problema

Varios "god objects" violan el Principio de Responsabilidad Única (SRP):

**Archivos más grandes:**

- `resources/views/tasks/show.blade.php` — 3,652 líneas
  ~8 responsabilidades

- `resources/views/layouts/app.blade.php` — 3,293 líneas
  ~10 responsabilidades

- `resources/views/teams/activities/edit.blade.php` — 2,117 líneas
  ~6 responsabilidades

- `app/Http/Controllers/ActivityController.php` — 1,026 líneas
  ~25 responsabilidades

- `app/Models/Task.php` — 1,110 líneas
  ~25 responsabilidades

- `app/Models/Activity.php` — 909 líneas
  ~30 responsabilidades

- `app/Services/Ai/GeminiService.php` — 909 líneas
  ~25 responsabilidades

- `app/Services/ActivityService.php` — 888 líneas
  ~20 responsabilidades

- `app/Models/User.php` — 757 líneas
  ~20 responsabilidades

### Solución

Extracción progresiva en 6 fases:

1. **Traits** — Lógica reutilizable cruzada entre modelos
2. **Service Classes** — Dividir servicios monolíticos por dominio
3. **Action Classes** — Operaciones de dominio atómicas
4. **Blade Components/Partials** — Fragmentos de vista reutilizables
5. **JavaScript Modules** — Lógica de cliente separada en archivos
6. **Events/Listeners** — Comportamientos reactivos desacoplados

---

## 2. Análisis de archivos críticos

### 2.1 Activity.php (909 líneas)

**Responsabilidades agrupadas:**

- Relaciones Eloquent (~120 líneas)
  team(), creator(), children(), assignments(), assignedTo(), tags(),
  histories(), attachments(), ratings(), notes(), timeLogs()

- Accessors/Mutators (~170 líneas)
  isInstance, isAutoprogrammable, privacyLevel, avgQualityScore,
  assignedUser, statusValue, progress, assignedUserId, urgency,
  isInKanban, isInMatrix, isInGantt, ganttColorClass, typeIcon,
  typeLabel, typeBadgeColor, serviceId, skillId

- Scopes (~220 líneas)
  scopeOfType, scopeOfTypes, scopeByTeam, scopeActive, scopeArchived,
  scopeOverdue, scopeDueToday, scopeForKanban, scopeForMatrix,
  scopeForGantt, scopeVisibleTo, scopeNotEphemeral, scopeFocusedFor,
  scopeOperationalFor, scopeOperationalForKanban

- Business Logic (~150 líneas)
  updateAutoPriority(), syncKanbanColumn(), asSubtype(),
  getStatusValue, isCompleted, isPending, isPublic, isVisibleTo,
  totalTrackedSeconds, totalTrackedTimeHuman

- Notificaciones (~70 líneas)
  notifyCreatorAndCoordinators(), notifyCoordinatorsIfCompleted()

- Conversión (~40 líneas)
  isDeprecatedByConversion(), getConvertedToActivity,
  getConvertedFromActivity, getAllAttachments

**Plan de extracción:**

```
Activity.php (reducido a ~350 líneas)
├── Relaciones Eloquent (inline)
├── Accessors básicos (inline)
│
├── app/Traits/ActivityScopes.php          (~220 líneas)
├── app/Traits/ActivityAccessors.php       (~130 líneas)
├── app/Traits/ActivityVisibility.php      (~50 líneas)
├── app/Traits/ActivityTracking.php        (~80 líneas)
├── app/Traits/ActivityMatrix.php          (~50 líneas)
├── app/Traits/ActivityConversion.php      (~40 líneas)
├── app/Services/ActivityNotificationService.php (~80 líneas)
├── app/Services/ActivitySearchService.php (~200 líneas)
└── app/Services/ActivityStatisticsService.php (~60 líneas)
```

### 2.2 Task.php (1,110 líneas)

**Responsabilidades agrupadas:**

- Relaciones Eloquent (~140 líneas)
  service(), team(), creator(), assignments(), assignedTo(), tags(),
  histories(), attachments(), ratings(), privateNotes(), timeLogs(),
  kanbanColumn(), forumThread(), instances(), children(), parent(),
  appointment(), calendarEvent()

- Accessors (~50 líneas)
  isEffectivelyPrivate, privacyLevel, progress, ganttColorClass

- Scopes (~280 líneas)
  scopeNotEphemeral, scopeByTeam, scopeByStatus, scopeByPriority,
  scopeOverdue, scopeDueToday, scopeVisibleTo, scopeOperationalFor,
  scopeFocusedFor, scopeOperationalForKanban, scopeDueThisWeek

- Business Logic (~130 líneas)
  updateAutoPriority(), syncKanbanColumn(), autoWakeup(),
  isInstance(), assignedUser(), getActivity()

- Ocurrences/Instances (~180 líneas) — ya existe TaskOccurrences trait
- Time Tracking (~80 líneas)
- Notificaciones (~60 líneas)

**Plan de extracción:**

```
Task.php (reducido a ~400 líneas)
├── Relaciones Eloquent (inline)
├── Accessors básicos (inline)
│
├── app/Traits/TaskScopes.php              (~280 líneas)
├── app/Traits/TaskTracking.php            (~80 líneas)
├── app/Traits/TaskOccurrences.php         (ya existe ✓)
├── app/Traits/TaskVisibility.php          (~40 líneas)
├── app/Services/TaskSearchService.php     (~200 líneas)
├── app/Services/TaskNotificationService.php (~70 líneas)
└── app/Services/TaskStatusService.php     (~100 líneas)
```

### 2.3 User.php (757 líneas)

**Responsabilidades agrupadas:**

- Relaciones Eloquent (~140 líneas)
  teams(), groups(), createdTeams(), chatGroups(), appointmentSettings(),
  appointments(), assignedTasks(), createdTasks(), timeLogs(), quickNotes(),
  attachments(), forumThreads(), forumMessages(), skills(),
  receivedKudos(), givenKudos(), gamificationLogs(), aiPreferences(), moodLogs()

- Authentication/Session (~100 líneas)
  wantsNotification(), isInQuietHours(), isOnline(), isWorking(),
  getStatusInfo(), activeWorkdayLog(), activeTaskLog(), isTrackingTask()

- Team Context (~60 líneas)
  getRole(), isCoordinator(), hasAppointmentsEnabled(), hasMicrositesEnabled()

- AI/Analytics (~60 líneas)
  getAiContextStats(), aiPreferences(), moodLogs()

- Profile (~50 líneas)
  profilePhotoUrl(), defaultProfilePhotoUrl(), profilePhotoDisk(),
  googleToken(), googleRefreshToken()

- Storage (~20 líneas)
  hasAvailableQuota(), getDiskUsagePercentageAttribute()

**Plan de extracción:**

```
User.php (reducido a ~300 líneas)
├── Relaciones básicas (inline)
├── Accessors básicos (inline)
│
├── app/Traits/UserPresence.php          (~100 líneas)
├── app/Traits/UserTeamContext.php       (~80 líneas)
├── app/Traits/UserAiStats.php           (~60 líneas)
├── app/Traits/UserProfile.php           (~50 líneas)
├── app/Traits/UserStorage.php           (~20 líneas)
├── app/Services/UserSearchService.php   (~100 líneas)
└── app/Services/UserSessionService.php  (~80 líneas)
```

### 2.4 ActivityController.php (1,026 líneas)

**Responsabilidades agrupadas:**

- CRUD básico (~200 líneas)
  index(), show(), create(), store(), edit(), update(), destroy(),
  archive(), unarchive()

- Notas (~100 líneas)
  addNote(), updateNote(), updatePrivateNote(), deleteNote()

- Adjuntos (~200 líneas)
  uploadAttachment(), deleteAttachment(), downloadAttachment(),
  viewAttachment(), updateAttachment(), replaceAttachmentContent()

- Acciones (~150 líneas)
  changeStatus(), convert(), restoreDeprecated(), cloneDeprecated(),
  mergeDeprecated()

- Capítulos (~130 líneas)
  addChapter(), updateChapter(), deleteChapter()

- Metadata (~30 líneas)
- Búsqueda (~70 líneas)

**Plan de extracción:**

```
ActivityController.php (reducido a ~300 líneas)
├── CRUD básico (inline)
├── Delegar acciones a Action classes
│
├── ActivityNoteController.php           (~100 líneas)
├── ActivityAttachmentController.php     (~200 líneas)
├── ActivityChapterController.php        (~130 líneas)
├── Actions/Activities/ChangeStatusAction.php (~50 líneas)
├── Actions/Activities/ConvertActivityAction.php (ya existe ✓)
├── Actions/Activities/ArchiveActivityAction.php (~30 líneas)
├── Actions/Activities/UnarchiveActivityAction.php (~30 líneas)
└── Actions/Activities/RestoreMetadataAction.php (~40 líneas)
```

### 2.5 ActivityService.php (888 líneas)

**Responsabilidades agrupadas:**

- CRUD (~200 líneas)
  create(), update(), delete(), restore(), archive(), unarchive()

- Relaciones (~150 líneas)
  syncAssignments(), syncTags(), handleAttachments()

- Búsqueda/Paginación (~200 líneas)
  search(), paginate(), buildInitialStatus(), buildMetadata()

- Lógica de negocio (~100 líneas)
  changeStatus(), cascadeCompletion()

- Historial/Notificaciones (~40 líneas)
  recordHistory(), notifyGuests()

**Plan de extracción:**

```
ActivityService.php (reducido a ~200 líneas)
├── CRUD orquestación (inline)
│
├── Activity/ActivityCreateService.php   (~100 líneas)
├── Activity/ActivityUpdateService.php   (~80 líneas)
├── Activity/ActivitySearchService.php   (~200 líneas)
├── Activity/ActivityAssignmentService.php (~110 líneas)
├── Activity/ActivityTagService.php      (~30 líneas)
├── Activity/ActivityAttachmentService.php (~80 líneas)
├── Activity/ActivityStatusService.php   (~80 líneas)
└── Activity/ActivityHistoryService.php  (~40 líneas)
```

### 2.6 GeminiService.php (909 líneas)

**Responsabilidades agrupadas:**

- API Core (~350 líneas)
  callGemini(), handleFallback(), generateText()

- Context Building (~130 líneas)
  withTaskContext(), withAttachmentContext(), withForumContext(),
  withHistory(), withTasksContext(), withFile()

- AI Features (~120 líneas)
  analyzeEnergyLevel(), simplifyText(), generateStructuredData(),
  generateMotivationalPhrase()

- Model Mgmt (~80 líneas)
  getTargetModel(), listAvailableModels(), clearWorkingModelCache()

- Utilities (~180 líneas)
  isMultimodalMime(), resizeImageIfNeeded(), getToolsDefinition(),
  getMicrositeDesignInstructions()

**Plan de extracción:**

```
GeminiService.php (reducido a ~250 líneas)
├── API Core (inline)
├── Fluente builder (inline)
│
├── Ai/AiContextBuilder.php              (~150 líneas)
├── Ai/AiImageProcessor.php              (~80 líneas)
├── Ai/AiToolsRegistry.php               (~100 líneas)
├── Ai/AiFeatureService.php              (~120 líneas)
└── Ai/AiModelService.php                (~60 líneas)
```

### 2.7 layouts/app.blade.php (3,293 líneas)

**Secciones identificadas:**

- `<head>` setup (~120 líneas)
  Meta tags, FOUC prevention, CDN scripts (SweetAlert2, marked),
  Alpine stores (chatStore, sientiaChat)

- Theme/Layout init (~650 líneas)
  Theme detection, layout system, global CSS variables,
  Alpine theme/layout stores

- Navigation sidebar (~400 líneas)
  `@include('layouts.navigation-sidebar')` — ya parcializado

- Header/Toolbar (~450 líneas)
  Horizontal nav, vertical nav, theme toggles, language toggle,
  zoom controls, workday timer, system tools

- Main content area (~50 líneas)
  @yield('content') + aside + modals

- Global JS (~600 líneas)
  confirmDelete, openGoogleAuth, zoom logic, floating draggable,
  toast notifications, SientiaPrint, chat widget, timer logic

- Widgets (~150 líneas)
  Telegram widget, WhatsApp widget

- Footer scripts (~150 líneas)
  Lottie, final initialization

**Plan de extracción:**

```
layouts/app.blade.php (reducido a ~800 líneas)
├── Estructura HTML master
├── @yield('content')
├── @stack('scripts')
│
├── layouts/partials/head-scripts.blade.php   (~150 líneas)
├── layouts/partials/theme-init.blade.php     (~200 líneas)
├── layouts/partials/header-horizontal.blade.php (~200 líneas)
├── layouts/partials/header-vertical.blade.php (~200 líneas)
├── layouts/partials/global-js.blade.php      (~300 líneas)
├── layouts/partials/widgets.blade.php        (~150 líneas)
└── components/global-sientia-print.blade.php (ya existe inline)
```

### 2.8 tasks/show.blade.php (3,652 líneas)

**Secciones identificadas:**

- Header (~50 líneas)
- Task detail view (~400 líneas)
- Private notes (~200 líneas)
- Task instances (~350 líneas)
- Activity timeline (~300 líneas)
- Print modals (~400 líneas)
- Edit mode toggle (~200 líneas)
- Kanban preview (~150 líneas)
- JS logic (~300 líneas)
- Forms (~200 líneas)
- Chapters (~200 líneas)
- Attachments (~200 líneas)

**Plan de extracción:**

```
tasks/show.blade.php (reducido a ~800 líneas)
├── Estructura principal
├── Header + breadcrumb
├── @include para cada sección
│
├── tasks/partials/task-info.blade.php          (~250 líneas)
├── tasks/partials/task-private-notes.blade.php (~150 líneas)
├── tasks/partials/task-instances.blade.php     (~250 líneas)
├── tasks/partials/task-timeline.blade.php      (~200 líneas)
├── tasks/partials/task-print-modals.blade.php  (~300 líneas)
├── tasks/partials/task-edit-fields.blade.php   (~200 líneas)
├── tasks/partials/task-kanban-preview.blade.php (~100 líneas)
├── tasks/partials/task-chapters.blade.php      (~200 líneas)
├── tasks/partials/task-attachments.blade.php   (~150 líneas)
├── tasks/partials/task-actions.blade.php       (~150 líneas)
└── tasks/partials/task-js.blade.php            (~200 líneas)
```

### 2.9 teams/activities/edit.blade.php (2,117 líneas)

**Secciones identificadas:**

- Header (~40 líneas)
- Form structure (~200 líneas)
- Type-specific fields (~600 líneas)
- Attachments (~300 líneas)
- Notes (~200 líneas)
- Chapters (~200 líneas)
- Print modal (~300 líneas)
- JS logic (~300 líneas)
- Footer (~100 líneas)

**Plan de extracción:**

```
teams/activities/edit.blade.php (reducido a ~500 líneas)
├── Estructura principal + header
├── Form wrapper
├── Footer buttons
├── @include para cada sección
│
├── teams/activities/partials/edit-type-fields.blade.php (~350 líneas)
├── teams/activities/partials/edit-attachments.blade.php (~200 líneas)
├── teams/activities/partials/edit-notes.blade.php (~150 líneas)
├── teams/activities/partials/edit-chapters.blade.php (~150 líneas)
├── teams/activities/partials/edit-print-modal.blade.php (~250 líneas)
└── teams/activities/partials/edit-js.blade.php (~200 líneas)
```

---

## 3. Estrategia de refactorización

### Principios rectores

1. Extracción incremental: cada fase es independiente y reversible.
2. Comportamiento idéntico: sin cambios visibles ni en la API.
3. Pruebas después de cada cambio: `php artisan test`.
4. Un commit por extracción: cada clase nueva se commitea por separado.
5. Deprecar, no borrar: marcar métodos como `@deprecated` antes de eliminarlos.
6. Invertir dependencias: nuevas clases dependen de interfaces, no de implementaciones.

### Patrones a aplicar

**Trait** — Lógica reutilizable entre modelos o accesors/scopes comunes.
  Ejemplo: ActivityScopes, TaskOccurrences (ya existe)

**Service Class** — Lógica de negocio compleja que no pertenece a un modelo.
  Ejemplo: ActivitySearchService, TaskStatusService

**Action Class** — Operaciones de dominio específicas y atómicas.
  Ejemplo: ChangeStatusAction, ConvertActivityAction (ya existe)

**Blade Component** — UI reutilizable con props.
  Ejemplo: `<x-task-info :task="$task" />`

**Blade Partial** — Fragmentos de vista sin lógica de componente.
  Ejemplo: `partials/edit-notes.blade.php`

**Event/Listener** — Comportamientos reactivos desacoplados.
  Ejemplo: ActivityCreated → SendNotificationListener

**Form Request** — Validación específica por acción.
  Ejemplo: StoreActivityRequest (ya existe)

**Repository** (opcional) — Cuando se necesite intercambiar fuente de dato.
  Ejemplo: ActivityRepository interface

### Reglas de nomenclatura

```
Traits:       app/Traits/{Name}.php
              Ej: ActivityScopes.php

Services:     app/Services/{Domain}/{Name}Service.php
              Ej: ActivitySearchService.php

Actions:      app/Actions/{Domain}/{Name}Action.php
              Ej: ChangeStatusAction.php

Events:       app/Events/{Domain}/{Name}Event.php

Listeners:    app/Listeners/{Domain}/{Name}Listener.php

Components:   resources/views/components/{domain}/{name}.blade.php
              Ej: components/task/info.blade.php

Partials:     resources/views/{domain}/partials/{name}.blade.php
              Ej: tasks/partials/task-info.blade.php
```

---

## 4. Fase 1 — Traits para Models

### 4.1 ActivityScopes

**Archivo:** `app/Traits/ActivityScopes.php`

**Contenido:** Todos los `scope*` de Activity.php:
- scopeOfType, scopeOfTypes
- scopeByTeam, scopeActive, scopeArchived
- scopeOverdue, scopeDueToday
- scopeForKanban, scopeForMatrix, scopeForGantt
- scopeVisibleTo, scopeNotEphemeral
- scopeFocusedFor, scopeOperationalFor, scopeOperationalForKanban

**Uso en Activity.php:**
```php
use App\Traits\ActivityScopes;

class Activity extends Model {
    use HasFactory, SoftDeletes, HasUuid, HandlesEisenhowerMatrix,
        \App\Traits\ActivityOccurrences, ActivityScopes;
    // eliminar todos los scope* del cuerpo de la clase
}
```

### 4.2 ActivityAccessors

**Archivo:** `app/Traits/ActivityAccessors.php`

**Contenido:** Todos los `get*Attribute` y `set*Attribute`:
- isInstance, isAutoprogrammable, privacyLevel, avgQualityScore
- assignedUser, statusValue, progress, assignedUserId
- urgency, isInKanban, isInMatrix, isInGantt
- ganttColorClass, typeIcon, typeLabel, typeBadgeColor
- serviceId, skillId

### 4.3 ActivityTracking

**Archivo:** `app/Traits/ActivityTracking.php`

**Contenido:**
- totalTrackedSeconds(), totalTrackedTimeHuman()
- totalTrackedTimeTodaySeconds(), totalTrackedTimeTodayHuman()

### 4.4 ActivityVisibility

**Archivo:** `app/Traits/ActivityVisibility.php`

**Contenido:**
- isPublic(), isVisibleTo(User $user)

### 4.5 ActivityConversion

**Archivo:** `app/Traits/ActivityConversion.php`

**Contenido:**
- isDeprecatedByConversion(), getConvertedToActivityAttribute()
- getConvertedFromActivityAttribute(), getAllAttachmentsAttribute()

### 4.6 TaskScopes

**Archivo:** `app/Traits/TaskScopes.php`

**Contenido:** Todos los `scope*` de Task.php:
- scopeNotEphemeral, scopeByTeam, scopeByStatus, scopeByPriority
- scopeOverdue, scopeDueToday, scopeDueThisWeek
- scopeVisibleTo, scopeOperationalFor, scopeFocusedFor
- scopeOperationalForKanban

### 4.7 TaskTracking

**Archivo:** `app/Traits/TaskTracking.php`

**Contenido:**
- timeLogs(), totalTrackedSeconds(), totalTrackedTimeHuman()
- trackedTimeTodaySeconds(), trackedTimeTodayHuman()
- totalTrackedTimeTodaySeconds(), totalTrackedTimeTodayHuman()

### 4.8 TaskVisibility

**Archivo:** `app/Traits/TaskVisibility.php`

**Contenido:**
- isEffectivelyPrivate, privacyLevel

### 4.9 UserPresence

**Archivo:** `app/Traits/UserPresence.php`

**Contenido:**
- wantsNotification(), isInQuietHours(), isOnline(), isWorking()
- getStatusInfo(), activeWorkdayLog(), activeTaskLog()
- isTrackingTask(), getTaskTrackingSeconds()

### 4.10 UserTeamContext

**Archivo:** `app/Traits/UserTeamContext.php`

**Contenido:**
- getRole(Team $team), isCoordinator(Team $team)
- hasAppointmentsEnabled(), hasAppointmentsEnabledForTeam()
- firstTeamWithAppointments()
- hasMicrositesEnabled(), hasMicrositesEnabledForTeam()
- firstTeamWithMicrosites()

### 4.11 UserAiStats

**Archivo:** `app/Traits/UserAiStats.php`

**Contenido:**
- getAiContextStats(), aiPreferences(), moodLogs()

### 4.12 UserProfile

**Archivo:** `app/Traits/UserProfile.php`

**Contenido:**
- profilePhotoUrl(), defaultProfilePhotoUrl(), profilePhotoDisk()
- googleToken(), googleRefreshToken()

### 4.13 UserStorage

**Archivo:** `app/Traits/UserStorage.php`

**Contenido:**
- hasAvailableQuota(), getDiskUsagePercentageAttribute()

### 4.14 UserNotifications

**Archivo:** `app/Traits/UserNotifications.php`

**Contenido:**
- defaultNotificationSettings()

---

## 5. Fase 2 — Dividir Services

### 5.1 ActivitySearchService

**Archivo:** `app/Services/ActivitySearchService.php`

**Responsabilidad:** Búsqueda y filtrado de actividades.

**Métodos:**
```php
class ActivitySearchService {
    public function search(Team $team, array $filters,
        string $sort, string $dir): Builder
    public function paginate(Team $team, array $filters,
        int $perPage, string $sort, string $dir): LengthAwarePaginator
    public function buildInitialStatus(string $type, array $data): array
    public function buildMetadata(string $type, array $data,
        bool $isUpdate): array
}
```

### 5.2 ActivityAssignmentService

**Archivo:** `app/Services/ActivityAssignmentService.php`

**Responsabilidad:** Sincronización de asignaciones.

**Métodos:**
```php
class ActivityAssignmentService {
    public function sync(Activity $activity, array $data): array
    protected function getUniqueAssignedUserIds(Activity $activity): array
    public function syncDistributedInstances(Activity $parent,
        array $userIds, array $data): void
}
```

### 5.3 ActivityTagService

**Archivo:** `app/Services/ActivityTagService.php`

**Responsabilidad:** Gestión de etiquetas.

**Métodos:**
```php
class ActivityTagService {
    public function sync(Activity $activity, array $tags): void
}
```

### 5.4 ActivityAttachmentService

**Archivo:** `app/Services/ActivityAttachmentService.php`

**Responsabilidad:** Upload, delete, update, replace de adjuntos.

**Métodos:**
```php
class ActivityAttachmentService {
    public function handle(Activity $activity, array $files): void
    public function delete(ActivityAttachment $attachment): void
}
```

### 5.5 ActivityStatusService

**Archivo:** `app/Services/ActivityStatusService.php`

**Responsabilidad:** Cambios de estado y cascada de completado.

**Métodos:**
```php
class ActivityStatusService {
    public function change(Activity $activity,
        string $statusValue): Activity
    public function cascadeCompletion(Activity $parent): void
}
```

### 5.6 ActivityHistoryService

**Archivo:** `app/Services/ActivityHistoryService.php`

**Responsabilidad:** Registro de historial y notificaciones de invitados.

**Métodos:**
```php
class ActivityHistoryService {
    public function record(Activity $activity,
        string $action, ?string $details): void
    public function notifyGuests(Activity $activity): void
}
```

### 5.7 ActivityNotificationService

**Archivo:** `app/Services/ActivityNotificationService.php`

**Responsabilidad:** Notificaciones a creadores y coordinadores.

**Métodos:**
```php
class ActivityNotificationService {
    public function notifyCreatorAndCoordinators(
        Activity $activity, string $notification): void
    public function notifyCoordinatorsIfCompleted(
        Activity $activity): void
}
```

### 5.8 TaskSearchService

**Archivo:** `app/Services/TaskSearchService.php`

**Responsabilidad:** Búsqueda y filtrado de tareas.

**Métodos:**
```php
class TaskSearchService {
    public function scopeVisibleTo(Team $team, User $user,
        bool $isManager): Builder
    public function scopeOperationalFor(Team $team, User $user,
        bool $includeFuture): Builder
    public function scopeFocusedFor(Team $team, User $user,
        bool $includeFuture): Builder
    public function scopeOperationalForKanban(Team $team, User $user,
        bool $includeFuture): Builder
}
```

### 5.9 TaskNotificationService

**Archivo:** `app/Services/TaskNotificationService.php`

**Responsabilidad:** Notificaciones de tareas.

**Métodos:**
```php
class TaskNotificationService {
    public function notifyCreatorAndCoordinators(
        Task $task, string $notification): void
    public function notifyCoordinatorsIfCompleted(Task $task): void
    public function updateQualityCache(Task $task): void
}
```

### 5.10 TaskStatusService

**Archivo:** `app/Services/TaskStatusService.php`

**Responsabilidad:** Transiciones de estado, prioridad, Kanban sync.

**Métodos:**
```php
class TaskStatusService {
    public function updateAutoPriority(Task $task): void
    public function syncKanbanColumn(Task $task): void
    public function autoWakeup(Task $task): void
}
```

### 5.11 AiContextBuilder

**Archivo:** `app/Services/Ai/AiContextBuilder.php`

**Responsabilidad:** Construcción de contexto para API Gemini.

**Métodos:**
```php
class AiContextBuilder {
    public function forTask(Activity|Task $task): array
    public function forAttachment(Attachment $attachment): array
    public function forForum(ForumThread $thread,
        ?ForumMessage $message): array
    public function forHistory(Collection $messages): array
    public function forTasks(Collection $tasks): array
}
```

### 5.12 AiImageProcessor

**Archivo:** `app/Services/Ai/AiImageProcessor.php`

**Responsabilidad:** Procesamiento de imágenes para Gemini API.

**Métodos:**
```php
class AiImageProcessor {
    public function isMultimodalMime(string $mime): bool
    public function resizeIfNeeded(string $path,
        string $mime): string
}
```

### 5.13 AiToolsRegistry

**Archivo:** `app/Services/Ai/AiToolsRegistry.php`

**Responsabilidad:** Registro de herramientas para Gemini API.

**Métodos:**
```php
class AiToolsRegistry {
    public function getToolsDefinition(): array
    public function getMicrositeDesignInstructions(): string
}
```

### 5.14 AiFeatureService

**Archivo:** `app/Services/Ai/AiFeatureService.php`

**Responsabilidad:** Funcionalidades AI de alto nivel.

**Métodos:**
```php
class AiFeatureService {
    public function analyzeEnergyLevel(User $user,
        array $recentData): int
    public function simplifyText(string $complexText): string
    public function generateStructuredData(string $prompt,
        array $schema, string $systemInstruction): array
    public function generateMotivationalPhrase(
        int $taskCount, string $userName, string $locale): string
}
```

---

## 6. Fase 3 — Extraer Actions y Form Requests

### 6.1 ActivityNoteController

**Archivo:** `app/Http/Controllers/ActivityNoteController.php`

**Responsabilidad:** Notas públicas y privadas de actividades.

**Métodos:**
```php
class ActivityNoteController extends Controller {
    public function add(Request $request,
        Team $team, Activity $activity)
    public function update(Request $request,
        Team $team, Activity $activity, ActivityNote $note)
    public function updatePrivateNote(
        Request $request, Team $team, Activity $activity)
    public function delete(Team $team,
        Activity $activity, ActivityNote $note)
}
```

**Rutas:**
```php
Route::post('teams/{team}/activities/{activity}/notes',
    [ActivityNoteController::class, 'add'])
    ->name('activities.notes.add');

Route::put('teams/{team}/activities/{activity}/notes/{note}',
    [ActivityNoteController::class, 'update'])
    ->name('activities.notes.update');

Route::patch('teams/{team}/activities/{activity}/private-note',
    [ActivityNoteController::class, 'updatePrivateNote'])
    ->name('activities.private-note.update');

Route::delete('teams/{team}/activities/{activity}/notes/{note}',
    [ActivityNoteController::class, 'delete'])
    ->name('activities.notes.delete');
```

### 6.2 ActivityAttachmentController

**Archivo:** `app/Http/Controllers/ActivityAttachmentController.php`

**Responsabilidad:** Gestión de adjuntos de actividades.

**Métodos:**
```php
class ActivityAttachmentController extends Controller {
    public function upload(Request $request,
        Team $team, Activity $activity)
    public function delete(Team $team,
        Activity $activity, ActivityAttachment $attachment)
    public function download(Team $team,
        Activity $activity, ActivityAttachment $attachment)
    public function view(Team $team,
        Activity $activity, ActivityAttachment $attachment)
    public function update(Request $request,
        Team $team, Activity $activity, ActivityAttachment $attachment)
    public function replaceContent(Request $request,
        Team $team, Activity $activity, ActivityAttachment $attachment)
}
```

### 6.3 ActivityChapterController

**Archivo:** `app/Http/Controllers/ActivityChapterController.php`

**Responsabilidad:** Gestión de capítulos de actividades.

**Métodos:**
```php
class ActivityChapterController extends Controller {
    public function add(Request $request,
        Team $team, Activity $activity)
    public function update(Request $request,
        Team $team, Activity $activity, $chapterId)
    public function delete(Request $request,
        Team $team, Activity $activity, $chapterId)
}
```

### 6.4 Action Classes

**ChangeStatusAction:**
```php
class ChangeStatusAction {
    public function execute(Activity $activity,
        string $status): Activity
}
```

**ArchiveActivityAction:**
```php
class ArchiveActivityAction {
    public function execute(Activity $activity): void
}
```

**UnarchiveActivityAction:**
```php
class UnarchiveActivityAction {
    public function execute(Activity $activity): void
}
```

**RestoreMetadataAction:**
```php
class RestoreMetadataAction {
    public function execute(Activity $activity,
        Request $request): Activity
}
```

### 6.5 Form Request Classes

**ChangeStatusRequest:**
```php
class ChangeStatusRequest extends FormRequest {
    public function rules(): array {
        return [
            'status' => ['required', 'string',
                'in:pending,in_progress,completed,archived,cancelled'],
        ];
    }
}
```

**NoteRequest:**
```php
class NoteRequest extends FormRequest {
    public function rules(): array {
        return [
            'content' => ['required', 'string', 'max:5000'],
        ];
    }
}
```

---

## 7. Fase 4 — Blade Components y Partials

### 7.1 Partials para tasks/show.blade.php

**Archivos a crear:**

- `resources/views/tasks/partials/task-info.blade.php`
  Información principal (título, descripción, estado, prioridad,
  asignado, fechas, progreso)

- `resources/views/tasks/partials/task-private-notes.blade.php`
  Notas privadas (display + edit)

- `resources/views/tasks/partials/task-instances.blade.php`
  Lista y gestión de instancias

- `resources/views/tasks/partials/task-timeline.blade.php`
  Feed de actividad

- `resources/views/tasks/partials/task-print-modals.blade.php`
  Modales de impresión (printFullTask, printDocumentBook)

- `resources/views/tasks/partials/task-edit-fields.blade.php`
  Campos de edición inline

- `resources/views/tasks/partials/task-kanban-preview.blade.php`
  Vista previa Kanban

- `resources/views/tasks/partials/task-chapters.blade.php`
  Gestión de capítulos

- `resources/views/tasks/partials/task-attachments.blade.php`
  Visualización de adjuntos

- `resources/views/tasks/partials/task-actions.blade.php`
  Formularios (cambio de estado, prioridad, asignación)

- `resources/views/tasks/partials/task-js.blade.php`
  Todo el JavaScript/Alpine.js inline

### 7.2 Partials para teams/activities/edit.blade.php

**Archivos a crear:**

- `resources/views/teams/activities/partials/edit-type-fields.blade.php`
  Campos específicos por tipo de actividad

- `resources/views/teams/activities/partials/edit-attachments.blade.php`
  Gestión de adjuntos

- `resources/views/teams/activities/partials/edit-notes.blade.php`
  Notas públicas y privadas

- `resources/views/teams/activities/partials/edit-chapters.blade.php`
  Gestión de capítulos

- `resources/views/teams/activities/partials/edit-print-modal.blade.php`
  Modal de impresión

- `resources/views/teams/activities/partials/edit-js.blade.php`
  JavaScript/Alpine.js inline

### 7.3 Partials para layouts/app.blade.php

**Archivos a crear:**

- `resources/views/layouts/partials/head-scripts.blade.php`
  FOUC prevention, CDN scripts, Alpine stores init

- `resources/views/layouts/partials/theme-init.blade.php`
  Theme detection, layout system, CSS variables

- `resources/views/layouts/partials/header-horizontal.blade.php`
  Navegación horizontal + toolbar

- `resources/views/layouts/partials/header-vertical.blade.php`
  Navegación vertical + toolbar

- `resources/views/layouts/partials/global-js.blade.php`
  confirmDelete, openGoogleAuth, zoom logic, floating draggable,
  toast notifications, SientiaPrint

- `resources/views/layouts/partials/widgets.blade.php`
  Telegram + WhatsApp widgets

### 7.4 Blade Components reutilizables

**components/task/info.blade.php:**
```blade
@props(['task', 'showActions' => false])
<div {{ $attributes->class('...') }}>
    {{-- Task info display --}}
    {{ $slot }}
</div>
```

**components/activity/attachments.blade.php:**
```blade
@props(['activity'])
<div {{ $attributes->class('...') }}>
    {{-- Attachment list --}}
    {{ $slot }}
</div>
```

**components/activity/timeline.blade.php:**
```blade
@props(['activity'])
<div {{ $attributes->class('...') }}>
    {{-- Activity timeline --}}
    {{ $slot }}
</div>
```

---

## 8. Fase 5 — JavaScript Modular

### 8.1 Estructura propuesta

```
public/js/
├── app.js                        # Entry point
├── stores/
│   ├── chatStore.js              # Alpine store: chatStore
│   ├── timerStore.js             # Alpine store: timer
│   ├── notificationStore.js      # Alpine store: notifications
│   └── themeStore.js             # Alpine store: theme/layout
├── modules/
│   ├── confirm.js                # confirmDelete, handleGlobalDelete
│   ├── google-auth.js            # openGoogleAuth, ghost popup
│   ├── zoom.js                   # Zoom logic
│   ├── draggable.js              # Floating draggable
│   ├── toasts.js                 # Toast notifications
│   ├── sientia-print.js          # SientiaPrint utility
│   ├── chat.js                   # sientiaChat Alpine component
│   └── timer.js                  # Workday timer logic
└── utils/
    ├── api.js                    # Fetch helpers
    └── helpers.js                # Common utilities
```

### 8.2 Mapeo de secciones actuales a archivos destino

- `Alpine.store('chatStore')` → `stores/chatStore.js`
- `Alpine.data('sientiaChat')` → `modules/chat.js`
- `Alpine.store('timer')` → `stores/timerStore.js`
- `Alpine.store('notifications')` → `stores/notificationStore.js`
- Theme/layout Alpine stores → `stores/themeStore.js`
- `window.confirmDelete` → `modules/confirm.js`
- `window.openGoogleAuth` → `modules/google-auth.js`
- Zoom logic → `modules/zoom.js`
- Floating draggable → `modules/draggable.js`
- Toast notifications → `modules/toasts.js`
- SientiaPrint → `modules/sientia-print.js`
- Workday timer → `modules/timer.js`

---

## 9. Fase 6 — Events/Listeners

### 9.1 Eventos propuestos

**ActivityCreated**
- Trigger: ActivityService::create()
- Listeners: SendCreatedNotification, UpdateSearchIndex

**ActivityUpdated**
- Trigger: ActivityService::update()
- Listeners: SendUpdateNotification, UpdateSearchIndex

**ActivityStatusChanged**
- Trigger: ActivityStatusService::change()
- Listeners: NotifyAssignees, CascadeDependencies

**ActivityDeleted**
- Trigger: ActivityService::delete()
- Listeners: RemoveFromSearchIndex, CleanupAttachments

**TaskCompleted**
- Trigger: TaskStatusService
- Listeners: SendCompletionNotification, UpdateMetrics

**UserLoggedIn / UserLoggedOut**
- Trigger: Auth
- Listeners: UpdateOnlineStatus, UpdateOfflineStatus

### 9.2 Registro en EventServiceProvider

```php
protected $listen = [
    ActivityCreated::class => [
        SendCreatedNotification::class,
        UpdateSearchIndex::class,
    ],
    ActivityUpdated::class => [
        SendUpdateNotification::class,
        UpdateSearchIndex::class,
    ],
    ActivityStatusChanged::class => [
        NotifyAssignees::class,
        CascadeDependencies::class,
    ],
    ActivityDeleted::class => [
        RemoveFromSearchIndex::class,
        CleanupAttachments::class,
    ],
    TaskCompleted::class => [
        SendCompletionNotification::class,
        UpdateMetrics::class,
    ],
];
```

---

## 10. Orden de ejecución (Sprints 1–8)

### Sprint 1 — Traits para Activity y Task

> **Objetivo:** Activity.php 909→400 líneas, Task.php 1110→500 líneas

1. Crear ActivityScopes trait y mover scopes
2. Crear ActivityAccessors trait y mover accessors
3. Crear ActivityTracking trait y mover time tracking
4. Crear ActivityVisibility trait y mover visibilidad
5. Crear ActivityConversion trait y mover conversión
6. Verificar: `php artisan test` + `route:clear` + `view:clear`
7. Crear TaskScopes trait y mover scopes
8. Crear TaskTracking trait y mover time tracking
9. Crear TaskVisibility trait y mover visibilidad
10. Verificar: `php artisan test`

### Sprint 2 — Traits para User

> **Objetivo:** User.php 757→350 líneas

11. Crear UserPresence trait y mover presencia
12. Crear UserTeamContext trait y mover contexto de equipo
13. Crear UserAiStats trait y mover stats AI
14. Crear UserProfile trait y mover perfil
15. Crear UserStorage trait y mover storage
16. Crear UserNotifications trait y mover notificaciones
17. Verificar: `php artisan test`

### Sprint 3 — Service Extraction (Activity)

> **Objetivo:** ActivityService.php 888→200 líneas

18. Crear ActivitySearchService y extraer búsqueda
19. Crear ActivityAssignmentService y extraer asignaciones
20. Crear ActivityTagService y extraer tags
21. Crear ActivityAttachmentService y extraer adjuntos
22. Crear ActivityStatusService y extraer cambios de estado
23. Crear ActivityHistoryService y extraer historial
24. Crear ActivityNotificationService y extraer notificaciones
25. Inyectar nuevos servicios en ActivityService via DI
26. Verificar: `php artisan test`

### Sprint 4 — Service Extraction (Task + AI)

> **Objetivo:** Reducir GeminiService.php 909→250 líneas

27. Crear TaskSearchService y extraer búsqueda
28. Crear TaskNotificationService y extraer notificaciones
29. Crear TaskStatusService y extraer status transitions
30. Crear AiContextBuilder y extraer context building
31. Crear AiImageProcessor y extraer image processing
32. Crear AiToolsRegistry y extraer tools definition
33. Crear AiFeatureService y extraer features
34. Verificar: `php artisan test`

### Sprint 5 — Controller Extraction

> **Objetivo:** ActivityController.php 1026→300 líneas

35. Crear ActivityNoteController y extraer notas
36. Crear ActivityAttachmentController y extraer adjuntos
37. Crear ActivityChapterController y extraer capítulos
38. Crear Action classes (ChangeStatusAction, etc.)
39. Crear Form Request classes (ChangeStatusRequest, etc.)
40. Inyectar actions en ActivityController
41. Actualizar rutas
42. Verificar: `php artisan test`

### Sprint 6 — Blade View Refactoring

> **Objetivo:** Vistas principales a ~25% de su tamaño

43. Crear directorio `resources/views/tasks/partials/`
44. Extraer task-info.blade.php partial
45. Extraer task-private-notes.blade.php partial
46. Extraer task-instances.blade.php partial
47. Extraer task-timeline.blade.php partial
48. Extraer task-print-modals.blade.php partial
49. Extraer task-edit-fields.blade.php partial
50. Extraer task-kanban-preview.blade.php partial
51. Extraer task-chapters.blade.php partial
52. Extraer task-attachments.blade.php partial
53. Extraer task-actions.blade.php partial
54. Extraer task-js.blade.php partial
55. Refactorizar tasks/show.blade.php con @include
56. Verificar: manual testing en browser
57. Repetir para teams/activities/edit.blade.php
58. Repetir para layouts/app.blade.php

### Sprint 7 — JavaScript Modularization

> **Objetivo:** Separar JS inline en módulos ES6

59. Crear estructura public/js/ con stores, modules, utils
60. Extraer chatStore, sientiaChat, timerStore, etc.
61. Configurar Vite para compilar módulos
62. Actualizar app.blade.php para cargar módulos compilados
63. Verificar: manual testing en browser

### Sprint 8 — Events/Listeners

> **Objetivo:** Desacoplar comportamientos reactivos

64. Crear ActivityCreated event + listeners
65. Crear ActivityUpdated event + listeners
66. Crear ActivityStatusChanged event + listeners
67. Crear ActivityDeleted event + listeners
68. Crear TaskCompleted event + listeners
69. Registrar en EventServiceProvider
70. Actualizar services para disparar eventos
71. Verificar: `php artisan test`

---

## 11. Checklist y criterios de aceptación

---

## 12. Progreso actualizado

### Sprint 1 — Traits para Activity y Task

**Activity.php: 938 → 481 líneas (48.7% reducción) ✅**

- [x] ActivityScopes trait creado e integrado
- [x] ActivityAccessors trait creado e integrado
- [x] ActivityTracking trait creado e integrado
- [x] ActivityVisibility trait creado e integrado
- [x] ActivityConversion trait creado e integrado
- [x] Todos los traits verificados con `php -l`
- [x] Aplicación arranca correctamente (`php artisan about`)
- [ ] `php artisan test` ejecutado
- [ ] Commit realizado

**Task.php: pendiente**

### Checklist de verificación (por cada paso)

- [ ] Nuevo archivo creado en ubicación correcta
- [ ] Archivo original actualizado para usar nuevo componente
- [ ] `php artisan view:clear` ejecutado
- [ ] `php artisan route:clear` ejecutado
- [ ] `php artisan config:clear` ejecutado
- [ ] `php artisan cache:clear` ejecutado
- [ ] `php artisan test` (todos los tests pasan)
- [ ] Verificación manual en browser (si aplica)
- [ ] Commit del cambio realizado
- [ ] Sin nuevos warnings de PHP/PHPStan

### Métricas de éxito

**Líneas de código por archivo:**

- Activity.php: 909 → <400 líneas
- Task.php: 1,110 → <500 líneas
- User.php: 757 → <350 líneas
- ActivityController.php: 1,026 → <350 líneas
- ActivityService.php: 888 → <250 líneas
- GeminiService.php: 909 → <300 líneas
- app.blade.php: 3,293 → <1,200 líneas
- tasks/show.blade.php: 3,652 → <1,000 líneas
- teams/activities/edit.blade.php: 2,117 → <600 líneas
- Método más largo: ~100 → <30 líneas
- Archivo más largo en app/: 1,110 → <500 líneas

**Nuevos archivos por crear:**

- Traits en app/Traits/: 8 → ~15
- Services en app/Services/: 18 → ~28
- Actions en app/Actions/: 2 → ~10
- Events en app/Events/: 0 → ~10
- Listeners en app/Listeners/: 0 → ~15
- Controllers en app/Http/Controllers/: 20 → ~25
- Blade Components: ~25 → ~40
- Blade Partials: ~20 → ~60

### Reglas no negociables

1. NUNCA modificar la API de respuesta (JSON o HTML idéntico).
2. NUNCA cambiar nombres de rutas existentes.
3. NUNCA cambiar nombres de fields en forms existentes.
4. SIEMPRE ejecutar `php artisan test` antes de commitear.
5. SIEMPRE ejecutar `php artisan view:clear` después de tocar vistas.
6. SIEMPRE mantener backward compatibility con `@deprecated` durante 2 sprints.
7. SIEMPRE documentar cambios en este archivo.

---

## Dependencias entre fases

```
Fase 1 (Traits) ──┐
                   │
Fase 2 (Services) ─┼──→ Fase 5 (JS) ──→ Fase 8 (Events)
                   │                      │
Fase 3 (Controllers) ──→ Fase 6 (Views) ──┘
                   │
Fase 4 (More Services) ──┘
```

- Fases 1-4 son paralelizables (traits y services no se afectan).
- Fase 3 depende de Fase 2 (controllers inyectan services).
- Fase 6 depende de Fase 3 (controllers pasan datos a vistas).
- Fase 5 (JS modular) es independiente.
- Fase 8 (Events) puede hacerse en paralelo con fases 4-7.

### Commands útiles

```bash
# Limpiar caches
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

# Tests
php artisan test
php artisan test --filter=ActivityTest
php artisan test --filter=TaskTest

# Linting
php artisan lint
phpstan analyse app/

# Verificar tamaño de archivos
find app -name "*.php" -exec wc -l {} + | sort -rn | head -20
find resources/views -name "*.blade.php" -exec wc -l {} + | sort -rn | head -20
```

---

> **Versión:** v1.0
> **Fecha:** 2025-07-19
> **Proyecto:** SientiaMTX
> **Estado:** Pendiente de aprobación
