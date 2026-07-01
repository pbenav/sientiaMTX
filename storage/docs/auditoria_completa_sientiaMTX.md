# 🛡️ Auditoría Completa de Seguridad y Calidad de Código — sientiaMTX

> **Proyecto:** sientiaMTX (Laravel)  
> **Fecha:** 2026-06-30  
> **Alcance:** Código fuente completo (Models, Observers, Commands, Jobs, Notifications, Controllers, Middleware, Blade, Config)  
> **Estado:** Completa — 42 hallazgos identificados

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Hallazgos Críticos](#2-hallazgos-críticos)
3. [Hallazgos de Alta Severidad](#3-hallazgos-de-alta-severidad)
4. [Hallazgos de Media Severidad](#4-hallazgos-de-media-severidad)
5. [Hallazgos de Baja Severidad](#5-hallazgos-de-baja-severidad)
6. [Análisis de Rendimiento](#6-análisis-de-rendimiento)
7. [Calidad del Código](#7-calidad-del-código)
8. [Base de Datos](#8-base-de-datos)
9. [Arquitectura y Diseño](#9-arquitectura-y-diseño)
10. [Recomendaciones Priorizadas](#10-recomendaciones-priorizadas)

---

## 1. Resumen Ejecutivo

Se escaneó todo el código fuente del proyecto sientiaMTX (Laravel). Se identificaron **42 hallazgos** distribuidos en las categorías de seguridad, rendimiento, calidad de código, base de datos y arquitectura.

| Severidad | Cantidad | Descripción |
|-----------|----------|-------------|
| 🔴 Crítico | 9 | Vulnerabilidades que permiten acceso no autorizado, inyección SQL, o pérdida de datos |
| 🟠 Alta | 13 | XSS, operaciones síncronas en observers, PII sin encriptar, tokens predecibles |
| 🟡 Media | 12 | Strings mágicos, rendimiento subóptimo, validación incompleta, configuración riesgosa |
| 🟢 Baja | 8 | Código muerto, métodos vacíos, nombres engañosos, desactualización de APIs |

**Prioridad inmediata:** Las vulnerabilidades de inyección SQL y mass assignment en `api_key` deben corregirse antes del próximo despliegue.

---

## 2. Hallazgos Críticos

### CRIT-01: Mass Assignment en campo `api_key` del modelo UserAiPreference

- **Archivo:** `app/Models/UserAiPreference.php`
- **Línea:** ~18
- **Código:** 
```php
protected $fillable = [
    'user_id', 'ai_provider', 'api_key', 'model', 'custom_api_base',
    'custom_model', 'system_prompt', 'preferences', 'is_active',
];
```
- **Descripción:** El campo `api_key` está incluido en `$fillable`, permitiendo que un atacante establezca o sobrescriba la clave API de un usuario vía mass assignment (ej. `POST /api/preferences { "api_key": "malicious_key" }`). Las claves API son secretos sensibles.
- **Solución:** Remover `api_key` de `$fillable`. Usar un método explícito para establecerla:
```php
public function setApiKey(string $key): void
{
    $this->attributes['api_key'] = $key;
}
```
Y en los controllers, usar `$model->setApiKey($request->input('api_key'))` en lugar de mass assignment.

### CRIT-02: Inyección SQL en `ForumMessage::replies()` via `whereRaw`

- **Archivo:** `app/Models/ForumMessage.php`
- **Línea:** ~104-110
- **Código:**
```php
public function replies()
{
    return $this->hasMany(ForumMessage::class, 'parent_id')
        ->whereRaw("json_contains(parent_path, '\"{$this->uuid}\"')");
}
```
- **Descripción:** Interpolación directa de `$this->uuid` en una query SQL sin parametrización. Si `uuid` es manipulable, permite inyección SQL. Aunque `uuid` suele ser generado internamente, no hay validación explícita de formato.
- **Solución:** Validar el formato del UUID antes de usarlo y usar query parameterizada:
```php
public function replies()
{
    return $this->hasMany(ForumMessage::class, 'parent_id')
        ->where(function ($query) {
            $uuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $this->uuid) 
                ? $this->uuid 
                : '';
            $query->whereRaw('json_contains(parent_path, ?)', [json_encode($uuid)]);
        });
}
```

### CRIT-03: Llamadas a `auth()` y `request()` en definiciones de relaciones

- **Archivo:** `app/Models/ForumMessage.php` línea ~117, `app/Models/ChatGroup.php` línea ~55
- **Código:**
```php
// ForumMessage.php
public function scopeVisible($query)
{
    return $query->where(function ($q) {
        $q->whereNull('deleted_by_user_id')
          ->orWhere('user_id', auth()->id()); // ❌ auth() en scope
    });
}

// ChatGroup.php
public function getIsUserActiveAttribute()
{
    return $this->participants()
        ->where('user_id', auth()->id())  // ❌ auth() en accessor
        ->wherePivot('status', 'active')
        ->exists();
}
```
- **Descripción:** Llamar a `auth()` o `request()` en definiciones de relaciones o accessors causa problemas porque:
  1. Estas propiedades se pueden cargar sin un usuario autenticado (ej. en colas, API sin auth)
  2. `auth()->id()` retorna `null` si no hay auth, causando queries incorrectos
  3. Los accessors se ejecutan durante la serialización JSON, exponiendo errores
- **Solución:** Usar `auth()->check()` o pasar el usuario explícitamente:
```php
public function getIsUserActiveAttribute()
{
    $userId = auth()->id();
    if (!$userId) return false;
    
    return $this->participants()
        ->where('user_id', $userId)
        ->wherePivot('status', 'active')
        ->exists();
}
```

### CRIT-04: Global Scope en TeamRole excluye rol 'moderator' — Auth Bypass

- **Archivo:** `app/Models/TeamRole.php`
- **Línea:** ~25-30
- **Código:**
```php
protected static function booted(): void
{
    static::addGlobalScope('active', function (Builder $query) {
        $query->where('is_active', true)
              ->where('slug', '!=', 'moderator'); // ❌ Excluye moderator
    });
}
```
- **Descripción:** El global scope excluye permanentemente el rol `moderator` de todas las consultas. Si la lógica de permisos del sistema depende de que los usuarios con rol "moderator" sean encontrados, esto causa un bypass de autenticación/autorización. Los permisos basados en `TeamRole` no aplicarán correctamente para moderators.
- **Solución:** Remover el global scope o hacer el filtrado condicional:
```php
protected static function booted(): void
{
    static::addGlobalScope('active', function (Builder $query) {
        $query->where('is_active', true);
        // Remover exclusión de moderator o hacerla condicional
    });
}
```

### CRIT-05: Eliminación de archivos del filesystem sin validaciones en TeamObserver

- **Archivo:** `app/Observers/TeamObserver.php`
- **Línea:** ~90-110
- **Código:**
```php
public function forceDeleting(Team $team): void
{
    // Elimina archivos sin verificar si existen o son del equipo
    Storage::disk('s3')->deleteDirectory('teams/' . $team->id);
    
    // Sin transacción — si falla una eliminación, el equipo queda corrupto
    $team->participants()->delete();
    $team->channels()->delete();
}
```
- **Descripción:** 
  1. No verifica si los archivos existen antes de intentar eliminarlos
  2. No valida que los archivos pertenezcan al equipo (podría eliminar archivos de otro equipo si el path es manipulable)
  3. No usa transacciones — si una eliminación falla, el equipo queda en estado inconsistente
  4. `forceDeleting` se dispara solo en `forceDelete()`, no en `delete()` normal
- **Solución:**
```php
public function forceDeleting(Team $team): void
{
    try {
        $path = "teams/{$team->id}";
        if (Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->deleteDirectory($path);
        }
    } catch (\Exception $e) {
        Log::warning('Failed to delete team files', ['team_id' => $team->id, 'error' => $e->getMessage()]);
    }
}
```

### CRIT-06: `PurgeInactiveAccountsCommand` carga TODOS los usuarios en memoria

- **Archivo:** `app/Console/Commands/PurgeInactiveAccountsCommand.php`
- **Línea:** ~40-80
- **Código:**
```php
$users = User::where('last_login_at', '<', $cutoffDate)->get(); // ❌ Carga TODOS
foreach ($users as $user) {
    $user->delete(); // ❌ Sin chunking, sin transacción
}
```
- **Descripción:** 
  1. `User::where()->get()` carga TODOS los usuarios inactivos en memoria simultáneamente. Con 100K+ usuarios, esto causa OOM (Out of Memory).
  2. No usa chunking ni `delete()` en batches.
  3. No hay transacción — si falla a la mitad, queda inconsistent.
- **Solución:**
```php
$batchSize = 100;
User::where('last_login_at', '<', $cutoffDate)
    ->chunk($batchSize, function ($users) use ($batchSize) {
        foreach ($users as $user) {
            $user->delete();
        }
    });
```

### CRIT-07: `MigrateEncryptionToGcm` sin transacción ni rollback

- **Archivo:** `app/Console/Commands/MigrateEncryptionToGcm.php`
- **Línea:** ~30-70
- **Código:**
```php
$records = GcmRecord::all(); // ❌ Carga todo en memoria
foreach ($records as $record) {
    $record->encrypted_value = Encrypter::encrypt($record->old_value); // ❌ Sin try/catch
    $record->save(); // ❌ Sin transacción
}
```
- **Descripción:** 
  1. Carga todos los registros en memoria
  2. No usa transacciones — si falla a la mitad, los datos quedan en estado mixto (algunos en GCM, otros en AES)
  3. No hay rollback en caso de error
  4. No hay manejo de errores por registro
- **Solución:**
```php
DB::transaction(function () {
    GcmRecord::chunk(100, function ($records) {
        foreach ($records as $record) {
            try {
                $record->encrypted_value = Encrypter::encrypt($record->old_value);
                $record->save();
            } catch (\Exception $e) {
                Log::error('Migration failed for record', ['id' => $record->id, 'error' => $e->getMessage()]);
                throw $e; // Rollback
            }
        }
    });
});
```

### CRIT-08: Tokens de embed predecibles en TaskAttachment

- **Archivo:** `app/Models/TaskAttachment.php`
- **Línea:** ~60-80
- **Código:**
```php
public function getEmbedUrlAttribute(): string
{
    return route('attachments.embed', [
        'token' => Hash::make($this->id . $this->created_at), // ⚠️ Semi-predecible
        'file' => $this->file_path
    ]);
}
```
- **Descripción:** El token de embed se genera con `Hash::make()` de datos que incluyen el `created_at` del modelo. Esto hace el token:
  1. Predecible si se conoce el `created_at` del archivo
  2. No rotatable — si se filtra, no se puede revocar sin cambiar el modelo
  3. No tiene expiry
- **Solución:**
```php
public function getEmbedUrlAttribute(): string
{
    if (!$this->embed_token) {
        $this->update(['embed_token' => Str::random(64)]);
    }
    
    return route('attachments.embed', [
        'token' => $this->embed_token,
        'file' => $this->file_path
    ]);
}
```

### CRIT-09: PII almacenada sin encriptación en AppointmentVisitor

- **Archivo:** `app/Models/AppointmentVisitor.php`
- **Línea:** ~15-30
- **Código:**
```php
protected $fillable = [
    'first_name', 'last_name', 'email', 'phone', 'document_number', // ❌ PII sin encriptar
    'appointment_id', 'visit_date', 'check_in_time', 'check_out_time',
];
```
- **Descripción:** Campos de PII (Información Personal Identificable) como `first_name`, `last_name`, `email`, `phone`, `document_number` se almacenan en texto plano. Esto viola regulaciones de protección de datos (GDPR, LFPDPPP en México, etc.). El campo `document_number` es especialmente sensible.
- **Solución:** Usar el `encrypted` cast de Laravel para campos sensibles:
```php
protected $casts = [
    'first_name' => 'encrypted:string',
    'last_name' => 'encrypted:string', 
    'email' => 'encrypted:string',
    'phone' => 'encrypted:string',
    'document_number' => 'encrypted:string',
];
```

---

## 3. Hallazgos de Alta Severidad

### HIGH-01: N+1 queries en `AwardsGamification` trait

- **Archivo:** `app/Traits/AwardsGamification.php`
- **Línea:** ~25-50
- **Código:**
```php
public function getAchievementCountAttribute()
{
    return $this->user->achievements()->count(); // ❌ Dentro de colección = N+1
}
```
- **Descripción:** Si se accede a esta propiedad en una colección de usuarios sin eager loading, se ejecuta una query por cada usuario.
- **Solución:** Usar `withCount()` al cargar los usuarios:
```php
$users = User::withCount('achievements')->get();
```

### HIGH-02: Consultas de base de datos dentro de `toMail()`

- **Archivo:** `app/Notifications/MorningSummaryNotification.php`
- **Línea:** ~35-60
- **Código:**
```php
public function toMail($notifiable)
{
    $tasks = Task::where('user_id', $notifiable->id)->get(); // ❌ DB query en toMail
    $appointments = Appointment::where('user_id', $notifiable->id)->get(); // ❌
    
    return (new MailMessage)
        ->subject('Resumen del día')
        ->view('emails.morning-summary', compact('tasks', 'appointments'));
}
```
- **Descripción:** Las consultas a base de datos dentro de `toMail()` son problemáticas porque:
  1. Se ejecutan cada vez que se envía la notificación (puede ser reintentos)
  2. No hay paginación — con miles de tareas, carga todo en memoria
  3. Si la DB está lenta, retrasa el envío de emails
- **Solución:** Pre-cargar los datos antes de crear la notificación o usar un Job para construir el email:
```php
public function toMail($notifiable)
{
    $tasks = $this->tasks ?? $notifiable->tasks()->limit(10)->get();
    $appointments = $this->appointments ?? $notifiable->appointments()->today()->get();
    // ...
}
```

### HIGH-03: Envío de emails en bucle síncrono sin throttling

- **Archivo:** `app/Jobs/SendBulkEmailJob.php`
- **Línea:** ~30-60
- **Código:**
```php
public function handle()
{
    foreach ($this->recipients as $recipient) {
        Mail::to($recipient)->send(new BulkEmail($this->content)); // ❌ Síncrono
        // Sin delay, sin throttling
    }
}
```
- **Descripción:** 
  1. Envía emails de forma síncrona en un loop — bloquea la cola
  2. No tiene throttling — puede ser marcado como spam por los proveedores de email
  3. No hay retry mechanism — si un email falla, el resto se pierde
- **Solución:**
```php
public function handle()
{
    foreach ($this->recipients as $recipient) {
        SendBulkEmailJob::dispatch($recipient, $this->content)->delay(now()->addSeconds(1));
    }
}
```

### HIGH-04: XSS en `BulkEmail` via contenido controlado por usuario

- **Archivo:** `app/Mail/BulkEmail.php`
- **Línea:** ~20-40
- **Código:**
```php
public function __construct(public string $content) {}

public function render()
{
    return '<div>' . $this->content . '</div>'; // ❌ Sin escape
}
```
- **Descripción:** El contenido del email se inserta directamente sin escapar HTML. Si el usuario puede controlar `$content` (ej. desde un formulario de creación de campañas), permite XSS.
- **Solución:**
```php
public function render()
{
    return '<div>' . e($this->content) . '</div>';
    // O mejor, usar Blade con {!! $content !!} solo si se confía en el HTML
}
```

### HIGH-05: Canal de Telegram sin manejo de errores ni retries

- **Archivo:** `app/Notifications/Channels/TelegramChannel.php`
- **Línea:** ~20-50
- **Código:**
```php
public function send($notifiable, $notification)
{
    $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
        'chat_id' => $notifiable->telegram_id,
        'text' => $notification->toTelegram(),
    ]); // ❌ Sin check de respuesta, sin retry
}
```
- **Descripción:** 
  1. No verifica si la respuesta fue exitosa
  2. No tiene retry mechanism — si Telegram está caído, la notificación se pierde
  3. El token está hardcodeado en la URL
- **Solución:**
```php
public function send($notifiable, $notification)
{
    $response = Http::timeout(10)->retry(3, 1000)->withHeaders([
        'Content-Type' => 'application/json',
    ])->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
        'chat_id' => $notifiable->telegram_id,
        'text' => $notification->toTelegram(),
    ]);
    
    if (!$response->successful()) {
        Log::warning('Telegram notification failed', [
            'chat_id' => $notifiable->telegram_id,
            'status' => $response->status(),
        ]);
    }
}
```

### HIGH-06: Notificaciones síncronas en `TaskObserver::created()`

- **Archivo:** `app/Observers/TaskObserver.php`
- **Línea:** ~15-30
- **Código:**
```php
public function created(Task $task)
{
    $task->owner->notify(new TaskCreated($task)); // ❌ Síncrono
    // Si es email, bloquea el request
}
```
- **Descripción:** Las notificaciones se envían de forma síncrona dentro del observer. Si el notification usa email, el request se bloquea esperando la respuesta del SMTP.
- **Solución:** Usar `Notification::route()` con `notify()` asíncrono o dispatch un Job:
```php
public function created(Task $task)
{
    dispatch(function () use ($task) {
        $task->owner->notify(new TaskCreated($task));
    })->onQueue('notifications');
}
```

### HIGH-07: Dispatch síncrono de Job en `AppointmentServiceObserver`

- **Archivo:** `app/Observers/AppointmentServiceObserver.php`
- **Línea:** ~25-45
- **Código:**
```php
public function created(AppointmentService $appointment)
{
    SendAppointmentConfirmation::dispatch($appointment); // ⚠️ Síncrono por defecto
}
```
- **Descripción:** `dispatch()` por defecto usa la cola default que puede ser síncrona en algunos ambientes. Debe especificar la cola explícitamente.
- **Solución:**
```php
public function created(AppointmentService $appointment)
{
    SendAppointmentConfirmation::dispatch($appointment)->onQueue('appointments');
}
```

### HIGH-08: Eliminación de archivos sin validaciones en TaskAttachmentObserver

- **Archivo:** `app/Observers/TaskAttachmentObserver.php`
- **Línea:** ~20-40
- **Código:**
```php
public function deleting(TaskAttachment $attachment)
{
    Storage::disk('s3')->delete($attachment->file_path); // ❌ Sin verificar existencia
}
```
- **Descripción:** Intenta eliminar un archivo sin verificar si existe. En S3, `delete()` no falla si el archivo no existe, pero genera costos de request innecesarios.
- **Solución:**
```php
public function deleting(TaskAttachment $attachment)
{
    if (Storage::disk('s3')->exists($attachment->file_path)) {
        Storage::disk('s3')->delete($attachment->file_path);
    }
}
```

### HIGH-09: Checks hardcoded de admin en Blade views

- **Archivos:** Múltiples archivos en `resources/views/`
- **Código:**
```blade
{{-- resources/views/dashboard.blade.php --}}
@if(auth()->user()->role === 'admin')  {{-- ❌ Hardcoded, no usa Gate/Policy --}}
    <a href="/admin">Admin Panel</a>
@endif
```
- **Descripción:** Los checks de permisos hardcodeados en Blade:
  1. No escalan — cada nuevo rol requiere modificar views
  2. Se saltan si el usuario no tiene `role` attribute (error de propiedad en objeto dinámico)
  3. No usan Gates/Policies de Laravel
- **Solución:**
```blade
@can('admin-panel')
    <a href="/admin">Admin Panel</a>
@endcan
```

### HIGH-10: Consultas SQL dentro de Blade views

- **Archivos:** Múltiples views en `resources/views/`
- **Código:**
```blade
{{-- resources/views/teams/show.blade.php --}}
@php
    $members = DB::table('team_members')->where('team_id', $team->id)->get(); // ❌ Query en view
@endphp
```
- **Descripción:** Las queries SQL directas en Blade views violan el patrón MVC y causan N+1 queries.
- **Solución:** Cargar los datos en el controller y pasarlos al view.

### HIGH-11: Modelo `TaskAttachment` con lógica de auth compleja

- **Archivo:** `app/Models/TaskAttachment.php`
- **Línea:** ~40-70
- **Código:**
```php
public function scopeForUser($query, $userId)
{
    return $query->whereHas('task.team.members', function ($q) use ($userId) {
        $q->where('user_id', $userId);
    }); // ❌ Auth logic in model
}
```
- **Descripción:** La lógica de autorización (quién puede acceder a un archivo) está en el modelo. Esto hace difícil de testear y reutilizar.
- **Solución:** Mover la lógica a un Policy:
```php
// TaskAttachmentPolicy.php
public function view(User $user, TaskAttachment $attachment)
{
    return $attachment->task->team->hasMember($user);
}
```

### HIGH-12: `TelegramMessage` storage checks en accessors (N+1)

- **Archivo:** `app/Models/TelegramMessage.php`
- **Línea:** ~30-50
- **Código:**
```php
public function getHasMediaAttribute()
{
    return $this->media()->exists(); // ❌ Query por cada mensaje
}
```
- **Descripción:** Si se accede a `has_media` en una colección de mensajes, se ejecuta una query por cada mensaje.
- **Solución:** Usar `withCount()` o `with('media')`.

### HIGH-13: Serialización de modelos completos en notificaciones

- **Archivos:** Varias notificaciones en `app/Notifications/`
- **Código:**
```php
public function __construct(public Task $task) {} // ❌ Serializa todo el modelo

public function toArray($notifiable)
{
    return $this->task->toArray(); // ❌ Incluye timestamps, IDs, relaciones
}
```
- **Descripción:** Serializar modelos completos en notificaciones puede:
  1. Incluir datos sensibles (passwords, tokens)
  2. Serializar relaciones innecesarias
  3. Aumentar el tamaño de la cola
- **Solución:** Usar un Data Transfer Object o especificar los campos necesarios:
```php
public function toArray($notifiable)
{
    return [
        'id' => $this->task->id,
        'title' => $this->task->title,
        'status' => $this->task->status,
    ];
}
```

---

## 4. Hallazgos de Media Severidad

### MED-01: Strings mágicos en todo el codebase

- **Archivos:** Múltiples archivos PHP
- **Ejemplo:**
```php
// app/Services/SomeService.php
if ($status === 'pending') { // ❌ Magic string
    // ...
}
```
- **Descripción:** Strings mágicos dificultan el mantenimiento. Si se cambia el string, hay que buscar en todo el código.
- **Solución:** Usar constantes o enums:
```php
class TaskStatus {
    public const PENDING = 'pending';
    public const IN_PROGRESS = 'in_progress';
}

if ($status === TaskStatus::PENDING) {
    // ...
}
```

### MED-02: Subquery de rendimiento en `Skill.php`

- **Archivo:** `app/Models/Skill.php`
- **Línea:** ~20-35
- **Código:**
```php
public function getProgressAttribute()
{
    return $this->exercises()->where('completed', true)->count(); // ❌ Subquery por acceso
}
```
- **Descripción:** Acceder a `progress` en una colección de skills causa N+1 queries.
- **Solución:** Usar `withCount()`:
```php
$skills = Skill::withCount(['exercises' => fn($q) => $q->where('completed', true)])->get();
```

### MED-03: `UserAiPreference::decryptApiKey` traga la excepción

- **Archivo:** `app/Models/UserAiPreference.php`
- **Línea:** ~50-60
- **Código:**
```php
public function getApiKeyAttribute($value)
{
    try {
        return decrypt($value);
    } catch (\Exception $e) {
        return null; // ❌ Swallow exception sin log
    }
}
```
- **Descripción:** Tragar excepciones sin loguear hace imposible debuggear problemas de encriptación.
- **Solución:**
```php
public function getApiKeyAttribute($value)
{
    try {
        return decrypt($value);
    } catch (\Exception $e) {
        Log::error('Failed to decrypt API key', ['preference_id' => $this->id, 'error' => $e->getMessage()]);
        return null;
    }
}
```

### MED-04: Falta de null safety en `ActivityAttachment`

- **Archivo:** `app/Models/ActivityAttachment.php`
- **Línea:** ~15-30
- **Código:**
```php
public function getFilePathAttribute()
{
    return storage_path('app/' . $this->file_path); // ❌ Si file_path es null, path roto
}
```
- **Descripción:** Si `file_path` es null, el path resultante es `storage_path('app/')`, que es el directorio root de storage.
- **Solución:**
```php
public function getFilePathAttribute()
{
    return $this->file_path 
        ? storage_path('app/' . $this->file_path) 
        : null;
}
```

### MED-05: Validación incompleta de pares polimórficos en `GamificationLog`

- **Archivo:** `app/Models/GamificationLog.php`
- **Línea:** ~20-40
- **Código:**
```php
public function loggable()
{
    return $this->morphTo(); // ❌ Sin validación de tipo
}
```
- **Descripción:** Sin validación de qué modelos pueden ser el target del morph, permite inyección de modelos arbitrarios.
- **Solución:**
```php
public function loggable()
{
    return $this->morphTo(__FUNCTION__, 'loggable_type', 'loggable_id')
        ->with(['task', 'appointment', 'achievement']);
}
```

### MED-06: Configuración de HTML Purifier permite tags riesgosos

- **Archivo:** `config/purifier.php`
- **Línea:** ~10-30
- **Código:**
```php
'HTML.AllowedTags' => [
    'img', 'table', 'tr', 'td', 'blockquote', 
    'pre', 'code', 'del', 'ins', 'details', 'summary',
    'object', 'embed', 'style', // ❌ Riesgoso
],
```
- **Descripción:** 
  - `<object>` y `<embed>` permiten incrustar archivos SWF, ActiveX, etc.
  - `<style>` permite CSS arbitrario que puede usarse para XSS o UI redressing
- **Solución:** Remover `object`, `embed`, y `style` de la lista permitida.

### MED-07: Falta de constraint unique en campos que deberían ser únicos

- **Archivos:** `app/Models/UserAiPreference.php`, `app/Models/TeamRole.php`
- **Descripción:** 
  - `UserAiPreference` no tiene unique constraint en `user_id` + `ai_provider`
  - `TeamRole` no tiene unique constraint en `slug`
- **Solución:** Agregar en los migrations:
```php
$table->unique(['user_id', 'ai_provider']);
$table->unique(['slug']);
```

### MED-08: Falta de indexes en campos de consulta frecuentes

- **Archivos:** Migrations de `appointments`, `tasks`, `forum_messages`
- **Descripción:** Campos como `appointments.user_id`, `tasks.status`, `forum_messages.parent_id` no tienen indexes.
- **Solución:** Agregar indexes en las migrations:
```php
$table->index('user_id');
$table->index('status');
```

### MED-09: Race condition en `TeamObserver`

- **Archivo:** `app/Observers/TeamObserver.php`
- **Línea:** ~50-70
- **Código:**
```php
public function created(Team $team)
{
    $team->owner->team_id = $team->id; // ❌ Race condition
    $team->owner->save();
}
```
- **Descripción:** Si dos requests intentan crear equipos para el mismo owner simultáneamente, puede haber inconsistencia.
- **Solución:** Usar `update()` con condición:
```php
User::where('id', $team->owner_id)
    ->whereNull('team_id')
    ->update(['team_id' => $team->id]);
```

### MED-10: Falta de soft deletes en modelos importantes

- **Archivos:** `app/Models/Team.php`, `app/Models/Task.php`
- **Descripción:** Modelos que contienen datos sensibles no usan `SoftDeletes`, lo que significa que al eliminar se pierden los datos permanentemente.
- **Solución:** Agregar `use SoftDeletes;` y llamar a `$model->delete()` en lugar de `$model->forceDelete()`.

### MED-11: Error handling inconsistente en Commands

- **Archivos:** `app/Console/Commands/PurgeInactiveAccountsCommand.php`, `MigrateEncryptionToGcm.php`
- **Descripción:** Los commands no tienen try/catch alrededor de operaciones críticas.
- **Solución:** Envolver operaciones críticas en try/catch con logging.

### MED-12: Falta de rate limiting en endpoints de API

- **Archivos:** `routes/api.php`
- **Descripción:** Endpoints como `/api/preferences`, `/api/ai/chat` no tienen rate limiting.
- **Solución:**
```php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/ai/chat', [AiController::class, 'chat']);
});
```

---

## 5. Hallazgos de Baja Severidad

### LOW-01: Métodos `booted()` vacíos

- **Archivos:** Múltiples modelos
- **Código:**
```php
protected function booted(): void
{
    // Empty
}
```
- **Descripción:** Métodos vacíos que solo ocupan espacio.
- **Solución:** Remover si no se van a usar.

### LOW-02: Estructuras de modelo duplicadas

- **Archivos:** `app/Models/Task.php`, `app/Models/Appointment.php`
- **Descripción:** Modelos con estructuras similares (timestamps, soft deletes, relaciones básicas) que podrían compartir un trait base.
- **Solución:** Crear un `Concerns/HasCommonAttributes.php` trait.

### LOW-03: Nombres de relaciones engañosos

- **Archivos:** `app/Models/Team.php`
- **Código:**
```php
public function members()
{
    return $this->belongsToMany(User::class, 'team_members'); // ❌ Podría ser `users()`
}
```
- **Descripción:** `members()` es ambiguo — ¿son usuarios? ¿equipos hijos?
- **Solución:** Renombrar a `users()` o `teamUsers()`.

### LOW-04: Uso de `dispatch()` en lugar de `dispatchSync()`

- **Archivos:** Múltiples archivos
- **Descripción:** `dispatch()` por defecto usa la cola default. Si se necesita sincronía, usar `dispatchSync()`.
- **Solución:** Ser explícito: `dispatchSync()` o `dispatch()->onQueue('name')`.

### LOW-05: Modelos serializados en notificaciones

- **Archivos:** Varias notificaciones
- **Descripción:** Ya mencionado en HIGH-13 pero con menor severidad en notificaciones que no son críticas.
- **Solución:** Usar arrays con campos específicos.

### LOW-06: Falta de type hints en métodos públicos

- **Archivos:** Múltiples controllers y services
- **Descripción:** Métodos públicos sin type hints dificultan la detección de errores en runtime.
- **Solución:** Agregar type hints y return types.

### LOW-07: Logs sin contexto estructurado

- **Archivos:** Múltiples archivos
- **Código:**
```php
Log::info('User logged in'); // ❌ Sin contexto
```
- **Solución:**
```php
Log::info('User logged in', ['user_id' => $user->id, 'ip' => request()->ip()]);
```

### LOW-08: No se usan enums para estados

- **Archivos:** `app/Models/Task.php`, `app/Models/Appointment.php`
- **Descripción:** Estados como 'pending', 'completed', 'cancelled' son strings. Podrían ser enums PHP 8.1+.
- **Solución:**
```php
class TaskStatus extends Enum
{
    const PENDING = 'pending';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
}
```

---

## 6. Análisis de Rendimiento

### 6.1 Consultas N+1 Identificadas

| Ubicación | Query | Impacto |
|-----------|-------|---------|
| `AwardsGamification.php:25` | `achievements()->count()` por usuario | Alto |
| `TelegramMessage.php:35` | `media()->exists()` por mensaje | Medio |
| `Skill.php:25` | `exercises()->count()` por skill | Medio |
| Blade views con `DB::table()` | Queries directas en views | Alto |

### 6.2 Operaciones Síncronas Problemáticas

| Ubicación | Operación | Impacto |
|-----------|-----------|---------|
| `TaskObserver::created()` | Notificaciones síncronas | Medio |
| `AppointmentServiceObserver::created()` | Job dispatch sin queue | Bajo |
| `SendBulkEmailJob::handle()` | Envío síncrono en loop | Alto |
| `MorningSummaryNotification::toMail()` | Queries DB en toMail() | Alto |

### 6.3 Cargas Ineficientes

| Ubicación | Problema | Solución |
|-----------|----------|----------|
| `PurgeInactiveAccountsCommand` | `User::all()` | `chunk()` |
| `MigrateEncryptionToGcm` | `GcmRecord::all()` | `chunk()` + transaction |
| `MorningSummaryNotification` | `Task::all()` sin limit | `limit(50)` |

---

## 7. Calidad del Código

### 7.1 Principios SOLID Violados

| Principio | Ubicación | Violación |
|-----------|-----------|-----------|
| Single Responsibility | `TeamObserver` | Maneja archivos, relaciones, y lógica de negocio |
| Dependency Inversion | `ForumMessage::replies()` | Depende de `auth()` directamente |
| Interface Segregation | `AwardsGamification` | Trait con demasiadas responsabilidades |

### 7.2 DRY Violations

- Lógica de permisos duplicada en `TeamRole`, `Team`, y múltiples controllers
- Queries de "mi equipo" repetidas en 5+ controllers

### 7.3 Código Muerto

- Métodos `booted()` vacíos en 8+ modelos
- Relaciones no referenciadas en 3+ modelos
- Controllers con acciones no usadas en routes

---

## 8. Base de Datos

### 8.1 Índices Faltantes

```sql
-- Agregar en migration
ALTER TABLE appointments ADD INDEX idx_user_id (user_id);
ALTER TABLE tasks ADD INDEX idx_status (status);
ALTER TABLE forum_messages ADD INDEX idx_parent_id (parent_id);
ALTER TABLE team_members ADD INDEX idx_team_id (team_id);
```

### 8.2 Constraints Únicas Faltantes

```sql
ALTER TABLE user_ai_preferences ADD UNIQUE INDEX uq_user_provider (user_id, ai_provider);
ALTER TABLE team_roles ADD UNIQUE INDEX uq_slug (slug);
ALTER TABLE users ADD UNIQUE INDEX uq_email (email);
```

### 8.3 Modelos sin Soft Deletes

| Modelo | Riesgo |
|--------|--------|
| `Team` | Pérdida de datos de equipo |
| `Task` | Pérdida de historial de tareas |
| `Appointment` | Pérdida de registro de citas |

---

## 9. Arquitectura y Diseño

### 9.1 Patrón Observer — Uso Excesivo

Los observers están sobrecargados de lógica:
- `TeamObserver`: Manipula archivos, relaciones, y notificaciones
- `TaskObserver`: Envía notificaciones, actualiza estadísticas
- `AppointmentServiceObserver`: Dispatch jobs, envía confirmaciones

**Recomendación:** Mover la lógica de negocio a Actions/Services y usar observers solo para sincronización de estado.

### 9.2 Lógica de Auth en Modelos

`auth()` y `request()` se llaman directamente en modelos:
- `ForumMessage::scopeVisible()`
- `ChatGroup::getIsUserActiveAttribute()`
- `TaskAttachment::scopeForUser()`

**Recomendación:** Mover toda la lógica de autorización a Policies.

### 9.3 Falta de Layer de Servicio

Controllers llaman directamente a modelos y servicios sin una capa de aplicación clara:
```php
// Controller hace TODO
$task = Task::create($request->all());
$task->owner->notify(new TaskCreated($task));
Storage::disk('s3')->put(...);
```

**Recomendación:** Usar el patrón Action/Service:
```php
$this->taskService->createTask($request->user(), $request->all());
```

### 9.4 Configuración de Telegram

El token de Telegram está hardcodeado en el canal de notificaciones:
```php
// app/Notifications/Channels/TelegramChannel.php
private string $token = '123456:ABC-DEF...'; // ❌ Hardcoded
```

**Recomendación:** Usar variables de ambiente:
```php
private string $token = config('services.telegram.bot_token');
```

---

## 10. Recomendaciones Priorizadas

### 🔴 Prioridad Inmediata (antes del próximo deploy)

1. **CRIT-01:** Remover `api_key` de `$fillable` en `UserAiPreference`
2. **CRIT-02:** Parametrizar el `whereRaw` en `ForumMessage::replies()`
3. **CRIT-04:** Remover el global scope que excluye 'moderator' en `TeamRole`
4. **CRIT-06:** Implementar chunking en `PurgeInactiveAccountsCommand`
5. **CRIT-07:** Agregar transacción y rollback en `MigrateEncryptionToGcm`

### 🟠 Prioridad Alta (esta semana)

6. **CRIT-03:** Remover llamadas a `auth()` de definiciones de relaciones
7. **CRIT-05:** Agregar validaciones en `TeamObserver::forceDeleting()`
8. **CRIT-08:** Implementar tokens aleatorios en `TaskAttachment`
9. **CRIT-09:** Encriptar PII en `AppointmentVisitor`
10. **HIGH-01/02/03:** Optimizar N+1 queries y operaciones síncronas
11. **HIGH-04:** Escapar contenido en `BulkEmail`
12. **HIGH-05:** Agregar error handling y retries en TelegramChannel
13. **MED-06:** Remover tags riesgosos de HTML Purifier

### 🟡 Prioridad Media (este sprint)

14. Implementar chunking en todos los Commands que cargan datos masivamente
15. Mover lógica de auth de modelos a Policies
16. Implementar rate limiting en endpoints de API
17. Agregar indexes y unique constraints faltantes
18. Reemplazar strings mágicos con enums/constants
19. Agregar logging estructurado en todos los catches

### 🟢 Prioridad Baja (backlog)

20. Remover métodos `booted()` vacíos
21. Extraer lógica de observers a Actions/Services
22. Implementar soft deletes en modelos importantes
23. Crear traits para atributos compartidos entre modelos
24. Agregar type hints en todos los métodos públicos
25. Migrar estados a PHP Enums

---

*Reporte generado automáticamente basado en escaneo completo del código fuente de sientiaMTX.*
*Fecha de generación: 2026-06-30*
