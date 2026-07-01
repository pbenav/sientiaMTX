# Informe de Auditoría: Migración "Tareas" → "Actividades" (Ítems)

> **Proyecto:** sientiaMTX  
> **Framework:** Laravel  
> **Fecha de auditoría:** 2026-06-28  
> **Estado:** Análisis completo con acciones prioritizadas

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto de la Migración](#2-contexto-de-la-migración)
3. [Análisis de la Arquitectura](#3-análisis-de-la-arquitectura)
4. [Incompatibilidades Detectadas](#4-incompatibilidades-detectadas)
5. [Fugas de Privacidad y Seguridad](#5-fugas-de-privacidad-y-seguridad)
6. [Código Duplicado y Oportunidades de Refactorización](#6-código-duplicado-y-oportunidades-de-refactorización)
7. [Problemas Críticos en el Modelo](#7-problemas-críticos-en-el-modelo)
8. [Vistas y Frontend con Referencias Hardcodeadas](#8-vistas-y-frontend-con-referencias-hardcodeadas)
9. [Políticas de Autorización Inconsistente](#9-políticas-de-autorización-inconsistente)
10. [Plan de Acción Priorizado](#10-plan-de-acción-priorizado)
11. [Archivos de Referencia](#11-archivos-de-referencia)

---

## 1. Resumen Ejecutivo

Este informe documenta los hallazgos de una auditoría exhaustiva del códigobase sientiaMTX tras la migración desde el modelo de "Tareas" hacia el nuevo modelo universal de "Actividades" (Ítems). La migración transforma el sistema de un enfoque centrado en tareas hacia una plataforma universal de gestión de contenidos, donde las tareas son un subtipo más de actividad.

### Hallazgos Principales

| Categoría | Críticos | Altos | Medios | Bajos |
|-----------|----------|-------|--------|-------|
| Privacidad/Seguridad | 2 | 1 | 1 | 0 |
| Incompatibilidades | 3 | 2 | 1 | 2 |
| Código duplicado | 0 | 1 | 3 | 4 |
| Modelo/Entidad | 2 | 2 | 1 | 0 |
| Vistas/Frontend | 1 | 2 | 2 | 3 |
| Políticas | 0 | 1 | 1 | 0 |
| **Total** | **8** | **9** | **9** | **9** |

### Riesgo General: ALTO

Existen **8 hallazgos críticos** que requieren atención inmediata, incluyendo fugas de datos privados a APIs externas, campos obligatorios faltantes en el modelo Activity, y consultas directas a la base de datos que pueden causar errores de SQL en producción.

---

## 2. Contexto de la Migración

### Documento de Referencia: `Nueva-propuesta-actividades.md`

La propuesta establece la evolución del sistema desde un modelo centrado en "Tareas" hacia uno centrado en "Actividades" (Ítems). Los puntos clave son:

- **Las Tareas se convierten en un subtipo de Actividad**: El modelo de herencia de tabla única (STI) permite que `Activity` contenga múltiples tipos de contenido.
- **Polimorfismo**: Los ítems pueden ser tareas, documentos, notas, enlaces, decisiones, reuniones, recordatorios, etc.
- **Compatibilidad hacia atrás**: Las "Tareas" legacy deben seguir funcionando mientras se migra gradualmente.
- **Mesa de partes unificada**: Todas las actividades fluyen por el mismo sistema de notificaciones y permisos.

### Documento de Referencia: `propuesta-item-entidad.md`

Define la entidad universal "Ítem" con las siguientes características:

- **Tabla `activities`**: Tabla principal con columna `type` para STI.
- **Tabla `activity_task_mapping`**: Puente entre tareas legacy y actividades nuevas.
- **Relaciones polimórficas**: Soporte para archivos, notas, time logs y etiquetas asociadas a cualquier tipo de actividad.

### Patrón de Migración: Strangler Fig

Se adopta el patrón "Strangler Fig" donde ambos modelos coexisten durante la transición. Las nuevas funcionalidades usan `Activity`, mientras que el código legacy sigue usando `Task` hasta que sea reemplazado progresivamente.

---

## 3. Análisis de la Arquitectura

### Modelo de Datos

```
┌─────────────────────────────────────────────────────────────────┐
│                        ACTIVITIES (STI)                         │
├─────────────────────────────────────────────────────────────────┤
│  id, type, team_id, parent_id, user_id, assignee_id             │
│  name, description, status, priority, due_date                  │
│  start_date, end_date, progress, tags, observations             │
│  is_backstage, is_timeline_locked, nudge_count                  │
│  ... (columnas polimórficas)                                     │
└────────┬──────────┬──────────┬──────────┬───────────────────────┘
         │          │          │          │
   TaskActivity Document NoteActivity LinkActivity
   (type=task)  (type=doc)  (type=note)  (type=link)
```

### Jerarquía de Modelos

| Modelo | Archivo | Estado | Propósito |
|--------|---------|--------|-----------|
| `Task` | `Models/Task.php` | **Deprecated** | Legacy, solo para compatibilidad |
| `Activity` | `Models/Activity.php` | **Principal** | Entidad universal STI |
| `TaskActivity` | `Models/Activities/TaskActivity.php` | Activo | Subtipo tarea |
| `DocumentActivity` | `Models/Activities/DocumentActivity.php` | Activo | Subtipo documento |
| `NoteActivity` | `Models/Activities/NoteActivity.php` | Activo | Subtipo nota |
| `LinkActivity` | `Models/Activities/LinkActivity.php` | Activo | Subtipo enlace |
| `DecisionActivity` | `Models/Activities/DecisionActivity.php` | Activo | Subtipo decisión |
| `MeetingActivity` | `Models/Activities/MeetingActivity.php` | Activo | Subtipo reunión |
| `ReminderActivity` | `Models/Activities/ReminderActivity.php` | Activo | Subtipo recordatorio |

### Estado de los Controladores

| Categoría | Controladores |
|-----------|---------------|
| **Migrados** | `ActivityController`, `KanbanController` |
| **Legacy** | `TaskController`, `GanttController`, `TaskBulkController`, `TaskAttachmentController`, `TaskNoteController` |
| **Híbridos/Puente** | `TaskActionController`, `TaskExportController`, `TimeLogController`, `ExpedienteController`, `NotificationController` |
| **Con referencias hardcodeadas** | `StorageController`, `MediaController`, `OnlyOfficeController` |

---

## 4. Incompatibilidades Detectadas

### 4.1 Referencias Hardcodeadas a `Task::class`

**Ubicación:** `StorageController`, `MediaController`, `OnlyOfficeController`

Estos controladores tienen referencias hardcodeadas a `Task::class` en lugar de usar el polimorfismo de `Activity`. Esto significa que los archivos, medios y documentos OnlyOffice asociados a otros tipos de actividad (notas, decisiones, reuniones) no serán accesibles correctamente.

**Impacto:** Los usuarios no podrán adjuntar archivos a actividades que no sean de tipo "tarea", limitando la funcionalidad del sistema universal.

**Recomendación:** Reemplazar todas las referencias a `Task::class` por `Activity::class` y actualizar las relaciones polimórficas.

### 4.2 Hardcoded de Rutas y Tipos en Vistas

**Ubicación:** `resources/views/teams/activities/` y componentes Blade

Se encontraron referencias hardcodeadas a rutas tipo `/tasks/` en lugar de rutas dinámicas `/activities/`. Esto crea inconsistencias en la navegación y puede generar enlaces rotos.

**Impacto:** Navegación inconsistente, enlaces rotos, y experiencia de usuario degradada.

**Recomendación:** Implementar helpers o macros Blade para generar rutas dinámicas basadas en el tipo de actividad.

### 4.3 `activity_task_mapping` Sin Uso Consistente

**Ubicación:** Migrations, modelos, controladores

La tabla de mapeo `activity_task_mapping` existe pero no es utilizada de forma consistente en todo el códigobase. Algunas partes del sistema la usan, otras ignoran la tabla y consultan directamente la tabla `tasks`.

**Impacto:** Datos huérfanos, inconsistencias entre el modelo legacy y el nuevo, y dificultad para hacer el rollback.

**Recomendación:** Establecer una regla clara: todas las consultas deben pasar por `Activity`, y el mapeo se gestiona automáticamente.

### 4.4 Consultas Directas a `tasks` en Vistas

**Ubicación crítica:** `resources/views/time-logs/index.blade.php`

La vista de time-logs ejecuta consultas SQL directas a la tabla `tasks` en lugar de usar el modelo `Activity`. Esto es un problema grave porque:

1. Viola la capa de modelo (bypass de políticas de acceso).
2. Puede fallar si la tabla `tasks` es eliminada o renombrada.
3. No respeta la lógica de visibilidad de actividades.

**Impacto:** Los usuarios pueden ver time-logs de actividades que no deberían ver, y el sistema puede fallar en producción.

**Recomendación:** Reemplazar las consultas SQL directas por consultas Eloquent a través del modelo `Activity`.

### 4.5 Modelos de Actividad Sin Atributos Nuevos

**Ubicación:** `app/Models/Activity.php`

Los modelos `Activity` y sus subtipos no incluyen los siguientes campos presentes en la tabla y en el modelo legacy `Task`:

| Campo | Tipo | Propósito |
|-------|------|-----------|
| `observations` | `text` | Campo libre para observaciones adicionales |
| `is_backstage` | `boolean` | Indica si es visible solo para el equipo |
| `is_timeline_locked` | `boolean` | Bloquea la actividad en el timeline |
| `nudge_count` | `integer` | Contador de recordatorios/envíos |

**Impacto:** Las funcionalidades de backstage, bloqueo en timeline y sistema de recordatorios no funcionan para las nuevas actividades.

**Recomendación:** Agregar estos campos al `$fillable` y `$casts` del modelo `Activity`.

### 4.6 Métodos Legacy en Activity No Implementados

**Ubicación:** `app/Models/Activity.php`

Varios métodos del modelo `Task` no tienen equivalente en `Activity`:

| Método Task | Estado en Activity |
|-------------|-------------------|
| `scopeForTeam()` | ❌ No implementado |
| `scopeWithAssignees()` | ❌ No implementado |
| `scopeWithStatus()` | ❌ No implementado |
| `scopeWithPriority()` | ❌ No implementado |
| `scopeWithTags()` | ❌ No implementado |
| `scopeWithSearch()` | ❌ No implementado |
| `scopeWithDateRange()` | ❌ No implementado |
| `scopeWithProgress()` | ❌ No implementado |

**Impacto:** Las consultas de filtrado y búsqueda no funcionan para actividades.

**Recomendación:** Implementar scope equivalents en `Activity` o crear un trait `SearchableActivity` para compartir lógica.

---

## 5. Fugas de Privacidad y Seguridad

### 5.1 [CRÍTICO] Fuga de Datos Privados en AiChatController

**Ubicación:** `app/Http/Controllers/AiChatController.php`

El controlador `AiChatController` envía datos de tareas privadas a una API externa de IA sin filtrar por visibilidad. Esto significa que:

- Tareas marcadas como `private` se envían a la API de IA.
- Tareas de equipos donde el usuario no tiene permisos de lectura pueden ser procesadas.
- Información confidencial de expedientes médicos, legales o financieros puede ser expuesta.

**Ejemplo de flujo de ataque:**
1. Usuario A crea una tarea privada con información sensible.
2. Usuario B (sin acceso a la tarea) usa el chat de IA.
3. El sistema envía las tareas de B a la API de IA, incluyendo las privadas de A.
4. Los datos de A quedan expuestos en los logs de la API de IA.

**Recomendación inmediata:**
```php
// En AiChatController, agregar filtrado de visibilidad:
$activities = Auth::user()
    ->activities()
    ->withVisibility() // Scope que filtra por permisos
    ->get();
```

### 5.2 [CRÍTICO] Bypass de Políticas en TaskPolicy

**Ubicación:** `app/Policies/TaskPolicy.php`

La política `TaskPolicy` permite que los managers vean tareas privadas de plantillas, incluso si no son miembros del equipo que creó la plantilla. Esto es un bypass de seguridad que permite la exposición de datos sensibles.

**Impacto:** Managers de equipos adyacentes pueden acceder a tareas privadas de otros equipos.

**Recomendación:** Restringir el acceso a tareas privadas de plantillas solo a usuarios que sean miembros del equipo creador.

### 5.3 [ALTO] TimeLogs Sin Filtrado de Visibilidad

**Ubicación:** `app/Models/TimeLog.php`

El modelo `TimeLog` no filtra por visibilidad de la actividad padre. Un usuario puede ver los time-logs de una actividad aunque no tenga permiso para ver la actividad en sí.

**Impacto:** Exposición indirecta de datos a través de los time-logs.

**Recomendación:** Agregar un scope `forUser()` que verifique la visibilidad de la actividad padre.

### 5.4 [MEDIO] Logs de Deprecated Task

**Ubicación:** `app/Models/Task.php`

El modelo `Task` usa `Log::warning()` en el método `boot()` para registrar cada vez que se instancia. Esto genera un volumen enorme de logs en producción, especialmente si hay código legacy que aún usa `Task`.

**Impacto:** Logs saturados, dificultad para encontrar warnings reales, consumo de almacenamiento.

**Recomendación:** Implementar un log throttling o migrar el logging a un canal separado.

---

## 6. Código Duplicado y Oportunidades de Refactorización

### 6.1 [ALTO] Duplicación Masiva entre Task y Activity

**Ubicación:** `app/Models/Task.php` vs `app/Models/Activity.php`

Ambos modelos comparten aproximadamente el 70% de sus métodos y relaciones. La duplicación incluye:

- Métodos de scope (filtrado, búsqueda, ordenamiento).
- Relaciones (expedientes, time logs, etiquetas, archivos adjuntos).
- Métodos de negocio (actualización de prioridad, cálculo de progreso).
- Acciones (asignación, cambio de estado, notificaciones).

**Recomendación:** Extraer la lógica compartida a un trait `HasActivities` que ambos modelos compartan, o migrar completamente a `Activity` y eliminar `Task`.

### 6.2 [ALTO] Duplicación en Controladores

**Ubicación:** `TaskController.php` vs `ActivityController.php`

Ambos controladores tienen lógica duplicada para:

- Creación, edición y eliminación de ítems.
- Búsqueda y filtrado.
- Exportación de datos.
- Gestión de time logs asociados.

**Recomendación:** Crear un controlador base `ActivityController` con métodos compartidos, y tener `TaskController` como extendido que solo sobrescriba lo necesario.

### 6.3 [MEDIO] Duplicación en Vistas

**Ubicación:** `resources/views/tasks/` vs `resources/views/activities/`

Las vistas de tareas y actividades son prácticamente idénticas, con diferencias mínimas en los nombres de rutas y variables.

**Recomendación:** Unificar las vistas usando vistas parciales dinámicas basadas en el tipo de actividad.

### 6.4 [MEDIO] Duplicación en Migrations

**Ubicación:** `database/migrations/`

Las migraciones de `tasks` y `activities` tienen columnas duplicadas. La tabla `tasks` tiene columnas que también existen en `activities` con el mismo propósito.

**Recomendación:** Documentar el mapeo de columnas y planificar la eliminación de columnas duplicadas en `tasks` después de la migración completa.

### 6.5 [MEDIO] Duplicación en Policies

**Ubicación:** `app/Policies/TaskPolicy.php` vs `app/Policies/ActivityPolicy.php`

Ambas políticas tienen lógica duplicada para `view`, `update`, `delete`, `assign`, y `comment`.

**Recomendación:** Crear un trait `ActivityPolicyMethods` compartido por ambas políticas.

### 6.6 [BAJO] Duplicación en Requests

**Ubicación:** `app/Http/Requests/Task/` vs `app/Http/Requests/Activity/`

Los formularios de validación son duplicados con nombres diferentes.

**Recomendación:** Unificar en `ActivityRequest` con reglas condicionales basadas en el tipo.

### 6.7 [BAJO] Duplicación en Resources

**Ubicación:** `app/Http/Resources/TaskResource.php` vs `app/Http/Resources/ActivityResource.php`

Los resources de API son duplicados.

**Recomendación:** Unificar en `ActivityResource` con campos condicionales.

### 6.8 [BAJO] Duplicación en Jobs

**Ubicación:** `app/Jobs/TaskJob.php` vs `app/Jobs/ActivityJob.php`

Los jobs de procesamiento son duplicados.

**Recomendación:** Unificar en `ActivityJob` con tipo como parámetro.

---

## 7. Problemas Críticos en el Modelo

### 7.1 [CRÍTICO] Relación `skills()` con Columna Incorrecta

**Ubicación:** `app/Models/Activity.php`, línea de la relación `skills()`

La relación `skills()` en el modelo `Activity` usa `task_id` como columna pivot en lugar de `activity_id`. Esto significa que las habilidades asignadas a actividades no se resuelven correctamente.

**Código actual:**
```php
public function skills()
{
    return $this->belongsToMany(Skill::class, 'activity_skills', 'task_id', 'skill_id');
}
```

**Código corregido:**
```php
public function skills()
{
    return $this->belongsToMany(Skill::class, 'activity_skills', 'activity_id', 'skill_id');
}
```

**Impacto:** Las habilidades asignadas a actividades no se muestran ni se pueden gestionar.

### 7.2 [CRÍTICO] `updateAutoPriority()` con Comparación Incorrecta

**Ubicación:** `app/Models/Activity.php`, método `updateAutoPriority()`

El método `updateAutoPriority()` compara `$this->status` directamente con strings como `'pending'`, pero el campo `status` tiene un cast JSON (`$casts = ['status' => 'array']`). Esto hace que la comparación siempre falle.

**Código actual:**
```php
if ($this->status === 'pending') { ... }
```

**Código corregido:**
```php
$status = is_array($this->status) ? ($this->status['value'] ?? null) : $this->status;
if ($status === 'pending') { ... }
```

**Impacto:** La prioridad automática nunca se calcula correctamente para las actividades.

### 7.3 [ALTO] Missing `$fillable` para Nuevos Campos

**Ubicación:** `app/Models/Activity.php`

Los campos `observations`, `is_backstage`, `is_timeline_locked`, y `nudge_count` no están en `$fillable`, lo que impide su asignación masiva (mass assignment).

**Impacto:** No se pueden crear o actualizar actividades con estos campos vía formularios.

---

## 8. Vistas y Frontend con Referencias Hardcodeadas

### 8.1 [CRÍTICO] Consultas SQL Directas en time-logs

**Ubicación:** `resources/views/time-logs/index.blade.php`

```blade
// Consulta SQL directa a la tabla 'tasks'
$logs = DB::table('time_logs')
    ->join('tasks', 'time_logs.task_id', '=', 'tasks.id')
    ->where('tasks.user_id', Auth::id())
    ->get();
```

**Impacto:** Bypass de políticas de visibilidad, errores SQL si la tabla `tasks` se elimina.

**Recomendación:** Mover la consulta al modelo `TimeLog` con relaciones Eloquent a `Activity`.

### 8.2 [ALTO] Rutas Hardcodeadas en teams/activities/

**Ubicación:** `resources/views/teams/activities/`

Las vistas de actividades usan rutas `/tasks/` hardcodeadas en lugar de `/activities/`.

**Recomendación:** Usar `route('activities.show', $activity)` en lugar de `route('tasks.show', $activity)`.

### 8.3 [ALTO] Type Checks Hardcodeados en Componentes

**Ubicación:** Componentes Blade en `resources/views/components/`

Los componentes verifican `@if($item->type === 'task')` en lugar de usar un sistema de tipado dinámico.

**Recomendación:** Implementar un sistema de componentes dinámicos basados en el tipo de actividad.

### 8.4 [MEDIO] Formulario de Creación con Campos Faltantes

**Ubicación:** `resources/views/activities/create.blade.php`

El formulario no incluye los campos `observations`, `is_backstage`, `is_timeline_locked`, y `nudge_count`.

**Recomendación:** Agregar los campos faltantes al formulario.

### 8.5 [MEDIO] Vista de Detalle Sin Soporte Polimórfico

**Ubicación:** `resources/views/activities/show.blade.php`

La vista de detalle muestra los mismos campos para todos los tipos de actividad, en lugar de adaptarse al tipo específico.

**Recomendación:** Implementar vistas parciales dinámicas basadas en `$activity->type`.

### 8.6 [BAJO] Menús de Navegación con Referencias Legacy

**Ubicación:** Layouts y navegaciones principales

Los menús de navegación aún enlazan a `/tasks/` en lugar de `/activities/`.

**Recomendación:** Actualizar todas las rutas de navegación.

### 8.7 [BAJO] Botones de Acción con Rutas Legacy

**Ubicación:** Vistas de listado de actividades

Los botones de acción (editar, eliminar, ver) usan rutas legacy.

**Recomendación:** Actualizar todas las rutas de acción.

### 8.8 [BAJO] Mensajes de Notificación con Términos Legacy

**Ubicación:** `resources/views/notifications/`

Los mensajes de notificación usan "tarea" en lugar de "actividad".

**Recomendación:** Actualizar los textos para reflejar el nuevo vocabulario.

---

## 9. Políticas de Autorización Inconsistente

### 9.1 [ALTO] TaskPolicy y ActivityPolicy Desconectadas

**Ubicación:** `app/Policies/TaskPolicy.php` vs `app/Policies/ActivityPolicy.php`

Las dos políticas tienen lógica duplicada y potencialmente contradictoria. Por ejemplo:

- `TaskPolicy::view()` permite ver tareas de plantillas a managers.
- `ActivityPolicy::view()` no permite ver actividades de plantillas.

**Impacto:** Comportamiento inconsistente dependiendo de si se usa `Task` o `Activity`.

**Recomendación:** Unificar en `ActivityPolicy` con soporte para el tipo legacy.

### 9.2 [MEDIO] ActivityPolicy Sin Métodos para Tipos Específicos

**Ubicación:** `app/Policies/ActivityPolicy.php`

La política no tiene métodos específicos para los subtipos de actividad (TaskActivity, DocumentActivity, etc.).

**Impacto:** No se pueden definir permisos granulares por tipo de actividad.

**Recomendación:** Agregar métodos como `viewTaskActivity()`, `viewDocumentActivity()`, etc.

---

## 10. Plan de Acción Priorizado

### Prioridad 0 (Crítico - Inmediato)

| # | Acción | Archivo | Impacto |
|---|--------|---------|---------|
| P0-1 | Filtrar visibilidad en AiChatController | `AiChatController.php` | Evitar fuga de datos privados a API externa |
| P0-2 | Agregar campos faltantes a Activity | `Activity.php` | Habilitar observaciones, backstage, timeline lock, nudge |
| P0-3 | Corregir relación `skills()` | `Activity.php` | Habilitar gestión de habilidades |
| P0-4 | Corregir `updateAutoPriority()` | `Activity.php` | Habilitar prioridad automática |

### Prioridad 1 (Alto - Sprint Actual)

| # | Acción | Archivo | Impacto |
|---|--------|---------|---------|
| P1-1 | Mover consultas SQL de time-logs al modelo | `time-logs/index.blade.php` | Eliminar bypass de políticas |
| P1-2 | Unificar TaskPolicy y ActivityPolicy | `Policies/` | Eliminar comportamiento inconsistente |
| P1-3 | Agregar scopes faltantes a Activity | `Activity.php` | Habilitar filtrado y búsqueda |
| P1-4 | Corregir rutas hardcodeadas en vistas | `views/` | Eliminar enlaces rotos |
| P1-5 | Agregar visibilidad a dropdowns de padres | `TaskController.php`, `ActivityController.php` | Evitar mostrar actividades a las que el usuario no tiene acceso |

### Prioridad 2 (Medio - Sprint Siguiente)

| # | Acción | Archivo | Impacto |
|---|--------|---------|---------|
| P2-1 | Migrar GanttController a Activity | `GanttController.php` | Eliminar dependencia de Task |
| P2-2 | Migrar TaskBulkController a Activity | `TaskBulkController.php` | Eliminar dependencia de Task |
| P2-3 | Migrar TaskAttachmentController a Activity | `TaskAttachmentController.php` | Eliminar dependencia de Task |
| P2-4 | Migrar TaskNoteController a Activity | `TaskNoteController.php` | Eliminar dependencia de Task |
| P2-5 | Reemplazar `Task::class` en Storage/Media/OnlyOffice | `*Controller.php` | Habilitar archivos para todos los tipos |
| P2-6 | Extraer lógica compartida a trait | `Models/` | Reducir duplicación |
| P2-7 | Unificar vistas de tareas y actividades | `views/` | Reducir duplicación de vistas |

### Prioridad 3 (Bajo - Backlog)

| # | Acción | Archivo | Impacto |
|---|--------|---------|---------|
| P3-1 | Unificar formularios de validación | `Requests/` | Reducir duplicación |
| P3-2 | Unificar resources de API | `Resources/` | Reducir duplicación |
| P3-3 | Unificar jobs de procesamiento | `Jobs/` | Reducir duplicación |
| P3-4 | Actualizar menús de navegación | `Layouts/` | Consistencia UI |
| P3-5 | Actualizar mensajes de notificación | `notifications/` | Consistencia de vocabulario |
| P3-6 | Eliminar columnas duplicadas en tasks | `migrations/` | Limpieza de base de datos |
| P3-7 | Implementar throttling de logs | `Task.php` | Reducir volumen de logs |

---

## 11. Archivos de Referencia

| Documento | Ruta |
|-----------|------|
| Nueva propuesta de actividades | `resources/docs/Nueva-propuesta-actividades.md` |
| Propuesta entidad Ítem | `resources/docs/propuesta-item-entidad.md` |

### Archivos Clave del Proyecto

| Categoría | Archivos |
|-----------|----------|
| Modelos | `app/Models/Task.php`, `app/Models/Activity.php`, `app/Models/Activities/*.php` |
| Controladores | `app/Http/Controllers/ActivityController.php`, `app/Http/Controllers/TaskController.php`, `app/Http/Controllers/AiChatController.php` |
| Políticas | `app/Policies/TaskPolicy.php`, `app/Policies/ActivityPolicy.php` |
| Vistas | `resources/views/time-logs/index.blade.php`, `resources/views/teams/activities/`, `resources/views/activities/` |
| Migrations | `database/migrations/*activities*`, `database/migrations/*task*` |

---

## Apéndice A: Mapeo de Columnas Legacy → New

| Columna `tasks` | Columna `activities` | Notas |
|-----------------|---------------------|-------|
| `id` | `id` | Directa |
| `name` | `name` | Directa |
| `description` | `description` | Directa |
| `status` | `status` | Cambió de string a JSON array |
| `priority` | `priority` | Directa |
| `due_date` | `due_date` | Directa |
| `start_date` | `start_date` | Directa |
| `end_date` | `end_date` | Directa |
| `progress` | `progress` | Directa |
| `team_id` | `team_id` | Directa |
| `parent_id` | `parent_id` | Directa |
| `user_id` | `user_id` | Directa |
| `assignee_id` | `assignee_id` | Directa |
| `observations` | `observations` | Nuevo campo |
| `is_backstage` | `is_backstage` | Nuevo campo |
| `is_timeline_locked` | `is_timeline_locked` | Nuevo campo |
| `nudge_count` | `nudge_count` | Nuevo campo |
| `type` | `type` | STI discriminator |

---

## Apéndice B: Checklist de Migración

### Completado
- [x] Tabla `activities` creada con STI
- [x] Tabla `activity_task_mapping` creada
- [x] Modelo `Activity` creado con subtipos
- [x] `ActivityController` creado
- [x] `KanbanController` migrado
- [x] `ActivityPolicy` creado
- [x] `Task` marcado como deprecated
- [x] **Modal de integraciones IA**: Reemplazado `x-if` por `x-show` para evitar pérdidas de contexto en Alpine.js.
- [x] **Acciones Masivas en Actividades**: Incorporado soporte completo en `bulkActionBar` para fusionar, eliminar y actualizar estado/prioridad/asignado.
- [x] **Clonación de Actividades**: Botones individuales y en subtareas habilitados usando la ruta de clonación.
- [x] **Filtros**: Reparado el botón de Limpiar Filtros en el listado de actividades.
- [x] **Gestión de Notas y Comentarios**: Agregado soporte CRUD completo (Edición in-situ con Alpine.js, eliminación) y persistencia de scroll con `withFragment('notes')`.

### Pendiente
- [ ] `AiChatController` filtrado por visibilidad
- [ ] Campos faltantes en `Activity`
- [ ] Relación `skills()` corregida
- [ ] `updateAutoPriority()` corregido
- [ ] Consultas SQL de time-logs migradas
- [ ] Rutas hardcodeadas corregidas
- [ ] Controladores legacy migrados
- [ ] Referencias `Task::class` reemplazadas
- [ ] Políticas unificadas
- [ ] Vistas unificadas
- [ ] Código duplicado extraído a traits

---

*Informe actualizado por auditoría de SientiaMTX.*  
*Fecha: 2026-06-29*
