# Propuesta Arquitectónica: Entidad "Ítem" — Sistema Universal de Gestión de Contenidos

**Versión:** 1.0  
**Fecha:** 26 de junio de 2026  
**Proyecto:** sientiaMTX — Laravel Task Management  
**Estado:** Propuesta para revisión y debate

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto del Sistema Actual](#2-contexto-del-sistema-actual)
3. [Problema Identificado](#3-problema-identificado)
4. [Solución Propuesta: Entidad "Ítem"](#4-solución-propuesta-entidad-ítem)
5. [Modelo de Datos](#5-modelo-de-datos)
6. [Tipos de Ítem y Metadatos](#6-tipos-de-ítem-y-metadatos)
7. [Estrategia de Migración](#7-estrategia-de-migración)
8. [Compatibilidad Hacia Atrás](#8-compatibilidad-hacia-atrás)
9. [Beneficios](#9-beneficios)
10. [Riesgos y Desafíos](#10-riesgos-y-desafíos)
11. [Cronograma Recomendado](#11-cronograma-recomendado)
12. [Decisiones Pendientes](#12-decisiones-pendientes)
13. [Anexos](#13-anexos)

---

## 1. Resumen Ejecutivo

Esta propuesta introduce una nueva entidad fundamental llamada **"Ítem"** que transforma el sistema de gestión de tareas sientiaMTX en una **plataforma universal de gestión de contenidos**. La entidad Ítem actúa como un contenedor polimórfico y extensible que puede albergar cualquier tipo de contenido: tareas, documentos, notas, enlaces, decisiones, reuniones, recordatorios y tipos personalizados.

El diseño prioriza la **compatibilidad hacia atrás** con el sistema existente, la **extensibilidad** para agregar nuevos tipos de contenido sin modificaciones al código, y la **reutilización de infraestructura** compartida (archivos adjuntos, notas privadas, historial, etiquetas, asignaciones, niveles de visibilidad).

Las "Tareas" se convierten en un **subtipo más** dentro de la entidad Ítem, conservando toda su funcionalidad actual pero beneficiándose de las nuevas capacidades jerárquicas y de contenido compartido.

---

## 2. Contexto del Sistema Actual

sientiaMTX es un sistema de gestión de tareas construido sobre Laravel con las siguientes capacidades principales:

### 2.1 Ciclo de Vida de Tareas

| Estado | Descripción |
|--------|-------------|
| `pending` | Tarea creada, pendiente de acción |
| `in_progress` | Tarea en ejecución |
| `completed` | Tarea finalizada exitosamente |
| `cancelled` | Tarea cancelada |
| `blocked` | Tarea bloqueada por dependencia externa |

### 2.2 Funcionalidades Actuales

- **Expedientes:** Contenedores jerárquicos de tareas relacionadas
- **Archivos adjuntos:** Polimórficos, con soporte para almacenamiento local y Google Drive
- **Notas privadas:** Comentarios confidenciales en tareas y expedientes
- **Historial/Auditoría:** Registro de cambios en tareas
- **Etiquetas:** Clasificación de tareas con tags
- **Asignaciones:** Asignación a usuarios individuales o grupos
- **Columnas Kanban:** Con tipos personalizados por columna
- **Matriz de Eisenhower:** Organización por cuadrantes (urgente/importante)
- **Foro/Hilos de discusión:** Por tarea
- **Seguimiento de tiempo:** Registro de horas dedicadas
- **Gamificación:** Habilidades, XP, kudos
- **Jerarquía de tareas:** Plantillas → Instancias
- **Tareas recurrentes auto-programables:** Programación automática de tareas repetitivas
- **Sincronización con Google:** Google Tasks + Google Calendar
- **Equipos con roles:** Admin, Coordinador, Miembro
- **Niveles de visibilidad:** Público, Privado, Semi-privado
- **Notas rápidas:** Widgets personales de escritorio
- **Registro de actividad:** Logging de acciones y adjuntos

### 2.3 Modelo de Datos Actual (Resumen)

```
teams
├── users (team_id)
├── expedientes (team_id)
│   └── tasks (expediente_id, team_id)
│       ├── task_attachments (task_id, attachable_type)
│       ├── task_private_notes (task_id)
│       ├── task_histories (task_id)
│       ├── task_tags (task_id, tag_id)
│       ├── task_assignments (task_id, user_id, group_id)
│       ├── task_ratings (task_id, user_id)
│       ├── forum_threads (task_id)
│       └── time_entries (task_id)
├── kanban_columns (team_id)
├── tags (team_id)
├── skills (team_id)
└── quick_notes (user_id)
```

**Limitación crítica:** Los Expedientes **solo pueden contener tareas**. No existe un mecanismo para adjuntar documentos, decisiones, notas o cualquier otro tipo de contenido de forma estructurada.

---

## 3. Problema Identificado

### 3.1 Rigidez de la Entidad "Tarea"

Las tareas poseen un conjunto fijo de campos y un ciclo de vida rígido, diseñadas exclusivamente para elementos del tipo "to-do". Sin embargo, el sistema ha evolucionado para contener:

- Documentos y archivos
- Decisiones del equipo
- Notas de reuniones
- Enlaces referenciales
- Entradas de base de conocimiento
- Recordatorios
- Hilos de discusión

### 3.2 El Expediente como Contenedor Limitado

Aunque el Expediente actúa como contenedor jerárquico, su restricción a **únicamente tareas** impide su uso como sistema de gestión documental o de conocimiento.

### 3.3 Fragmentación de Funcionalidades

Las funcionalidades como adjuntos, notas, historial, etiquetas y asignaciones están acopladas a la entidad Tarea. Para que cualquier otro tipo de contenido las disfrute, se requiere duplicación de código o soluciones alternativas.

### 3.4 Necesidad de Extensibilidad

Agregar un nuevo tipo de contenido (por ejemplo, "encuestas" o "presupuestos") requiere:
1. Crear una nueva tabla en la base de datos
2. Crear modelos, controladores, vistas, políticas
3. Implementar manualmente adjuntos, notas, historial, etc.

Esto genera **fricción técnica** y **desincentiva** la evolución del producto.

---

## 4. Solución Propuesta: Entidad "Ítem"

### 4.1 Concepto Central

Introducir una entidad polimórfica y flexible llamada **"Ítem"** que sirva como contenedor universal para cualquier tipo de contenido, donde las tareas sean solo un subtipo más.

### 4.2 Principios de Diseño

| Principio | Descripción |
|-----------|-------------|
| **Compatibilidad hacia atrás** | Las tareas, expedientes y toda la funcionalidad existente deben continuar funcionando sin cambios visibles |
| **Extensibilidad** | Nuevos tipos de contenido deben ser agregables sin cambios en el código (donde sea posible) |
| **Jerárquico** | Los ítems pueden tener hijos de cualquier tipo (no solo tareas) |
| **Infraestructura compartida** | Reutilizar adjuntos, notas, historial, etiquetas, asignaciones y visibilidad |
| **Metadatos por tipo** | Cada subtipo tiene sus propios campos, almacenados en una columna JSON |

### 4.3 Visión General de la Arquitectura

```
                        ┌─────────────────┐
                        │     ITEM        │
                        │  (tabla base)   │
                        └────────┬────────┘
                                 │
            ┌────────────────────┼────────────────────┐
            │                    │                    │
     ┌──────┴──────┐    ┌───────┴───────┐   ┌────────┴────────┐
     │   Task       │    │  Document     │   │  Decision       │
     │ (polimórfico)│    │  (polimórfico)│   │  (polimórfico)  │
     └─────────────┘    └───────────────┘   └─────────────────┘
            │                    │                    │
     ┌──────┴──────┐    ┌───────┴───────┐   ┌────────┴────────┐
     │   Note       │    │   Link        │   │  Meeting        │
     │ (polimórfico)│    │ (polimórfico) │   │ (polimórfico)   │
     └─────────────┘    └───────────────┘   └─────────────────┘
                                 │
                         ┌───────┴───────┐
                         │   Reminder    │
                         │ (polimórfico) │
                         └───────────────┘
```

### 4.4 Relaciones con Infraestructura Existente

```
┌──────────┐     ┌──────────┐     ┌──────────────────────┐
│  ITEM    │     │  EXPEDIENTE│   │  TEAM                │
├──────────┤     ├──────────┤   ├──────────────────────┤
│ id       │     │ id       │   │ id                   │
│ uuid     │     │ uuid     │   │ name                 │
│ team_id  │─────│ team_id  │   ├──────────────────────┤
│ parent_id│     │          │   │ items()              │
│ type     │     │ items()──│───│ users()              │
│ title    │     └──────────┘   │ kanban_columns()     │
│ metadata │                    └──────────────────────┘
│ status   │
└────┬─────┘
     │
     ├─────────────────────────────────────────────────┐
     │                                                 │
     ▼                                                 ▼
┌────────────┐  ┌──────────────┐  ┌────────────────────────┐
│ Attach-    │  │ Item         │  │ Item                   │
│ ments      │  │ Tags         │  │ Histories              │
│(polimórf.) │  │(polimórf.)   │  │(polimórfico)           │
└────────────┘  └──────────────┘  └────────────────────────┘

┌────────────────────────┐  ┌────────────────────────┐  ┌──────────────┐
│ Item                 │  │ Item                 │  │ Quick Notes │
│ Assignments          │  │ Ratings              │  │ (sin cambio)│
│(polimórfico)          │  │(polimórfico)         │  │             │
└────────────────────────┘  └────────────────────────┘  └──────────────┘
```

**Nota importante:** Los adjuntos (`task_attachments`), foros (`forum_threads`) y seguimiento de tiempo (`time_entries`) **permanecen como entidades independientes** y se vinculan al `Item` mediante relaciones polimórficas o foreign keys actualizadas.

---

## 5. Modelo de Datos

### 5.1 Tabla `items`

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT | Identificador numérico |
| `uuid` | `STRING(36)` | UNIQUE, NOT NULL | Identificador único universal |
| `team_id` | `BIGINT UNSIGNED` | FK → teams, NOT NULL | Equipo propietario |
| `created_by_id` | `BIGINT UNSIGNED` | FK → users, NOT NULL | Usuario creador |
| `parent_id` | `BIGINT UNSIGNED` | FK → items.id, NULLABLE | Ítem padre (auto-referencia) |
| `expediente_id` | `BIGINT UNSIGNED` | FK → expedientes, NULLABLE | Expediente contenedor |
| `type` | `STRING(50)` | NOT NULL | Tipo de ítem (ver sección 6) |
| `title` | `STRING(255)` | NOT NULL | Título del ítem |
| `description` | `LONGTEXT` | NULLABLE | Descripción detallada |
| `status` | `JSON` | NULLABLE | Definición flexible de estado por tipo |
| `metadata` | `JSON` | NULLABLE | Campos específicos del tipo |
| `visibility` | `ENUM` | NOT NULL, DEFAULT 'private' | 'public', 'private', 'semi-private' |
| `due_date` | `DATETIME` | NULLABLE | Fecha límite |
| `scheduled_date` | `DATETIME` | NULLABLE | Fecha programada |
| `priority` | `ENUM` | NOT NULL, DEFAULT 'medium' | 'low', 'medium', 'high', 'critical' |
| `progress_percentage` | `INTEGER` | DEFAULT 0, CHECK 0-100 | Progreso en porcentaje |
| `matrix_order` | `INTEGER` | NULLABLE | Orden en la Matriz de Eisenhower |
| `kanban_column_id` | `BIGINT UNSIGNED` | FK → kanban_columns, NULLABLE | Columna Kanban asignada |
| `is_archived` | `BOOLEAN` | DEFAULT FALSE | Estado de archivado |
| `is_template` | `BOOLEAN` | DEFAULT FALSE | Es una plantilla |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | `TIMESTAMP` | ON UPDATE | Fecha de última actualización |
| `deleted_at` | `TIMESTAMP` | NULLABLE | Fecha de eliminación (soft delete) |

### 5.2 Índices Recomendados

```sql
-- Acceso rápido por equipo
INDEX idx_items_team_id (team_id)

-- Acceso por expediente
INDEX idx_items_expediente_id (expediente_id)

-- Acceso por tipo
INDEX idx_items_type (type)

-- Jerarquía
INDEX idx_items_parent_id (parent_id)

-- Búsquedas por creador
INDEX idx_items_created_by (created_by_id)

-- Consultas de fechas
INDEX idx_items_due_date (due_date)
INDEX idx_items_scheduled_date (scheduled_date)

-- Visibilidad y estado
INDEX idx_items_visibility (visibility)
INDEX idx_items_is_archived (is_archived)

-- UUID (implícito por UNIQUE)
UNIQUE INDEX idx_items_uuid (uuid)
```

### 5.3 Tabla `item_tags` (Pivot Polimórfico)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `item_id` | `BIGINT UNSIGNED` | FK → items.id |
| `item_type` | `STRING(50)` | Tipo de ítem (para polimorfismo) |
| `tag` | `STRING(100)` | Nombre de la etiqueta |
| `color_hex` | `STRING(7)` | Color de la etiqueta en formato HEX (ej. '#FF5733') |

```sql
UNIQUE INDEX idx_item_tags_unique (item_id, item_type, tag)
INDEX idx_item_tags_tag (tag)
INDEX idx_item_tags_item_type (item_type)
```

### 5.4 Tabla `item_histories` (Historial Polimórfico)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `item_id` | `BIGINT UNSIGNED` | FK → items.id |
| `item_type` | `STRING(50)` | Tipo de ítem (para polimorfismo) |
| `user_id` | `BIGINT UNSIGNED` | FK → users.id | Usuario que realizó el cambio |
| `action` | `STRING(100)` | Acción realizada (create, update, delete, status_change, etc.) |
| `old_values` | `JSON` | Valores anteriores (null en creación) |
| `new_values` | `JSON` | Valores nuevos (null en eliminación) |
| `notes` | `TEXT` | Notas opcionales del usuario |
| `created_at` | `TIMESTAMP` | Fecha del cambio |

```sql
INDEX idx_item_histories_item (item_id, item_type)
INDEX idx_item_histories_user (user_id)
INDEX idx_item_histories_action (action)
```

### 5.5 Tabla `item_assignments` (Asignaciones Polimórficas)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `item_id` | `BIGINT UNSIGNED` | FK → items.id |
| `item_type` | `STRING(50)` | Tipo de ítem (para polimorfismo) |
| `user_id` | `BIGINT UNSIGNED` | FK → users.id, NULLABLE | Usuario asignado |
| `group_id` | `BIGINT UNSIGNED` | FK → groups.id, NULLABLE | Grupo asignado |
| `assigned_by_id` | `BIGINT UNSIGNED` | FK → users.id | Quién asignó |
| `assigned_at` | `DATETIME` | Fecha de asignación |
| `completed_at` | `DATETIME` | NULLABLE | Fecha de cumplimiento |

```sql
INDEX idx_item_assignments_item (item_id, item_type)
INDEX idx_item_assignments_user (user_id)
INDEX idx_item_assignments_group (group_id)
```

### 5.6 Tabla `item_ratings` (Calificaciones Polimórficas)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `item_id` | `BIGINT UNSIGNED` | FK → items.id |
| `item_type` | `STRING(50)` | Tipo de ítem (para polimorfismo) |
| `user_id` | `BIGINT UNSIGNED` | FK → users.id | Quién califica |
| `rating` | `INTEGER` | Calificación (1-5) |
| `kudos_message` | `TEXT` | NULLABLE | Mensaje opcional de kudos |
| `created_at` | `TIMESTAMP` | Fecha de calificación |

```sql
UNIQUE INDEX idx_item_ratings_unique (item_id, item_type, user_id)
INDEX idx_item_ratings_item (item_id, item_type)
```

### 5.7 Tabla `item_notes` (Notas Privadas Polimórficas)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | `BIGINT UNSIGNED` | PK, AUTO_INCREMENT |
| `item_id` | `BIGINT UNSIGNED` | FK → items.id |
| `item_type` | `STRING(50)` | Tipo de ítem (para polimorfismo) |
| `user_id` | `BIGINT UNSIGNED` | FK → users.id | Autor de la nota |
| `content` | `LONGTEXT` | Contenido de la nota |
| `is_internal` | `BOOLEAN` | DEFAULT FALSE | Si es visible para el equipo |
| `created_at` | `TIMESTAMP` | Fecha de creación |
| `updated_at` | `TIMESTAMP` | Fecha de actualización |

```sql
INDEX idx_item_notes_item (item_id, item_type)
INDEX idx_item_notes_user (user_id)
```

### 5.8 Diagrama ER Simplificado

```
┌──────────┐       1:N       ┌───────────┐
│  teams   │─────────────────│  items    │
└────┬─────┘                 └──────┬────┘
     │                             │
     │        1:N                  │ 1:N
     │       ┌─────────────────────┤──────────┐
     │       │                     │          │
     ▼       ▼                     ▼          ▼
┌────────┐ ┌────────┐    ┌───────────┐ ┌──────────┐
│ users  │ │exped-  │    │item_tags  │ │item_hist- │
│        │ │ientes  │    └───────────┘ │ories     │
└────────┘ └────────┘           │            │
          │                     │            │
          │ 1:N                 │ 1:N        │ 1:N
          │   ┌────────┐        │            │
          └───│ items  │◄───────┘            │
              │(hijos) │                     │
              └────────┘                     │
                     │                       │
                     │ 1:N                   │
                     │   ┌────────┐          │
                     └───│ items  │◄─────────┘
                         │(self)  │
                         └────────┘
```

---

## 6. Tipos de Ítem y Metadatos

### 6.1 Resumen de Tipos

| Tipo | Descripción | Uso Principal |
|------|-------------|---------------|
| `task` | Tarea tradicional (comportamiento existente) | Gestión de tareas |
| `document` | Documento adjunto o enlace externo | Gestión documental |
| `note` | Nota de texto libre (markdown, HTML, plano) | Anotaciones rápidas |
| `link` | Enlace web con metadatos obtenidos | Curación de contenido |
| `decision` | Decisión del equipo con justificación | Registro de decisiones |
| `meeting` | Reunión programada con participantes | Gestión de reuniones |
| `reminder` | Recordatorio con notificaciones | Recordatorios |
| `custom` | Tipo personalizable por el equipo | Extensibilidad |

### 6.2 Detalle por Tipo

#### 6.2.1 `task` — Tarea

Comportamiento idéntico al actual. Todos los campos existentes se migran al JSON `metadata`.

```json
{
  "assigned_user_id": 1,
  "urgency": "high",
  "is_template": false,
  "autoprogram_settings": {
    "frequency": "weekly",
    "day_of_week": 1,
    "month_day": null,
    "start_date": "2026-01-01",
    "end_date": null
  },
  "autoprogrammable": false,
  "skill_id": 1,
  "cognitive_load": 3,
  "is_out_of_skill_tree": false,
  "is_backstage": false,
  "impact_human_metric": 0,
  "auto_priority": false,
  "is_timeline_locked": false,
  "nudge_count": 0,
  "google_task_id": null,
  "google_task_list_id": null,
  "google_calendar_event_id": null,
  "google_synced_at": null,
  "original_due_date": null,
  "kanban_type": "todo"
}
```

#### 6.2.2 `document` — Documento

```json
{
  "file_path": "storage/expedientes/PROY-2026-001/informe-final.pdf",
  "mime_type": "application/pdf",
  "file_size": 1024000,
  "version": 1,
  "external_url": null,
  "storage_provider": "local",
  "provider_file_id": null,
  "web_view_link": null,
  "thumbnail_path": null,
  "is_reviewable": true,
  "review_status": "pending"
}
```

**Campos adicionales:**
- `thumbnail_path`: Ruta a miniatura generada
- `is_reviewable`: Indica si el documento requiere revisión
- `review_status`: Estado del proceso de revisión

#### 6.2.3 `note` — Nota

```json
{
  "content": "# Reunión de planificación\n\n- [ ] Definir alcance\n- [ ] Asignar recursos\n- [ ] Estimar tiempos",
  "is_public": false,
  "format": "markdown",
  "pinned": false,
  "color": "#3498db"
}
```

**Campos adicionales:**
- `pinned`: Indica si la nota está anclada en primer plano
- `color`: Color de la nota (para visualización tipo widget)

#### 6.2.4 `link` — Enlace

```json
{
  "url": "https://github.com/laravel/framework",
  "fetched_og_title": "The PHP Framework For Laravel",
  "fetched_og_image": "https://laravel.com/img/logo.png",
  "fetched_at": "2026-06-26T10:00:00Z",
  "fetched_description": "Laravel is a web application framework with expressive, elegant syntax.",
  "is_favorited": false,
  "folder": "Recursos de desarrollo"
}
```

**Campos adicionales:**
- `fetched_description`: Descripción obtenida de Open Graph
- `is_favorited`: Indica si el enlace es favorito
- `folder`: Carpeta de organización de enlaces

#### 6.2.5 `decision` — Decisión

```json
{
  "rationale": "Se eligió Laravel por su ecosistema maduro y la curva de aprendizaje del equipo.",
  "participants": [1, 2, 3],
  "date": "2026-06-26",
  "status": "accepted",
  "alternatives_considered": ["Django", "Rails", "NestJS"],
  "review_date": "2026-12-26",
  "reversible": true
}
```

**Campos adicionales:**
- `alternatives_considered`: Lista de alternativas evaluadas
- `review_date`: Fecha de revisión programada de la decisión
- `reversible`: Indica si la decisión puede revertirse

#### 6.2.6 `meeting` — Reunión

```json
{
  "start_time": "2026-06-25T14:00:00Z",
  "end_time": "2026-06-25T15:00:00Z",
  "location": "Zoom",
  "zoom_link": "https://zoom.us/j/123456789",
  "agenda": "Discutir objetivos del Q3",
  "attendees": [1, 2, 3],
  "meeting_notes": null,
  "calendar_event_id": null,
  "recurring_pattern": null,
  "status": "scheduled"
}
```

**Campos adicionales:**
- `zoom_link`: Enlace a la videollamada
- `calendar_event_id`: ID del evento sincronizado con Google Calendar
- `recurring_pattern`: Patrón de recurrencia (iCal format)
- `status`: Estado de la reunión (scheduled, in_progress, completed, cancelled)

#### 6.2.7 `reminder` — Recordatorio

```json
{
  "trigger_at": "2026-06-26T09:00:00Z",
  "repeats": "weekly",
  "repeats_interval": 1,
  "notification_channels": ["mail", "database", "telegram"],
  "triggered": false,
  "last_triggered_at": null,
  "max_triggers": null,
  "message": "Revisar el estado del proyecto"
}
```

**Campos adicionales:**
- `last_triggered_at`: Última vez que se activó
- `max_triggers`: Límite de activaciones (null = infinito)
- `message`: Mensaje personalizado del recordatorio

#### 6.2.8 `custom` — Tipo Personalizado

```json
{
  "definition_key": "survey",
  "definition_schema": {
    "type": "object",
    "properties": {
      "question": {"type": "string"},
      "options": {"type": "array", "items": {"type": "string"}},
      "response_type": {"type": "string", "enum": ["single", "multiple", "text"]}
    }
  },
  "definition_data": {
    "question": "¿Cuál es el estado del sprint?",
    "options": ["A tiempo", "Retrasado", "Bloqueado"],
    "response_type": "single"
  }
}
```

**Campos adicionales:**
- `definition_key`: Clave única del tipo personalizado
- `definition_schema`: Esquema JSON Schema para validación
- `definition_data`: Datos específicos de la definición

---

## 7. Estrategia de Migración

La migración se divide en **5 fases**, cada una independiente y verificable.

### Fase 1: Fundación (Semanas 1-2)

#### 7.1.1 Migraciones de Base de Datos

**Migración 1: Crear tabla `items`**

```php
// database/migrations/2026_06_26_000001_create_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams');
            $table->foreignId('created_by_id')->constrained('users');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes');
            $table->string('type', 50);
            $table->string('title', 255);
            $table->longText('description')->nullable();
            $table->json('status')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('visibility', ['public', 'private', 'semi-private'])->default('private');
            $table->dateTime('due_date')->nullable();
            $table->dateTime('scheduled_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->integer('progress_percentage')->default(0)->unsigned()->checkBetween([0, 100]);
            $table->integer('matrix_order')->nullable();
            $table->foreignId('kanban_column_id')->nullable()->constrained('kanban_columns');
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_template')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('team_id');
            $table->index('expediente_id');
            $table->index('type');
            $table->index('parent_id');
            $table->index('created_by_id');
            $table->index('due_date');
            $table->index('scheduled_date');
            $table->index('visibility');
            $table->index('is_archived');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
```

**Migración 2: Crear tabla `item_tags`**

```php
// database/migrations/2026_06_26_000002_create_item_tags_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_type', 50);
            $table->string('tag', 100);
            $table->string('color_hex', 7)->default('#6b7280');

            $table->unique(['item_id', 'item_type', 'tag']);
            $table->index(['item_id', 'item_type']);
            $table->index('tag');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_tags');
    }
};
```

**Migración 3: Crear tabla `item_histories`**

```php
// database/migrations/2026_06_26_000003_create_item_histories_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_type', 50);
            $table->foreignId('user_id')->constrained('users');
            $table->string('action', 100);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'item_type']);
            $table->index('user_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_histories');
    }
};
```

**Migración 4: Crear tabla `item_assignments`**

```php
// database/migrations/2026_06_26_000004_create_item_assignments_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_type', 50);
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('group_id')->nullable()->constrained('groups');
            $table->foreignId('assigned_by_id')->constrained('users');
            $table->dateTime('assigned_at');
            $table->dateTime('completed_at')->nullable();

            $table->index(['item_id', 'item_type']);
            $table->index('user_id');
            $table->index('group_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_assignments');
    }
};
```

**Migración 5: Crear tabla `item_ratings`**

```php
// database/migrations/2026_06_26_000005_create_item_ratings_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_type', 50);
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedTinyInteger('rating')->default(3);
            $table->text('kudos_message')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'item_type', 'user_id']);
            $table->index(['item_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_ratings');
    }
};
```

**Migración 6: Crear tabla `item_notes`**

```php
// database/migrations/2026_06_26_000006_create_item_notes_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_type', 50);
            $table->foreignId('user_id')->constrained('users');
            $table->longText('content');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['item_id', 'item_type']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_notes');
    }
};
```

**Migración 7: Actualizar `task_attachments` para soportar polimorfismo**

```php
// database/migrations/2026_06_26_000007_update_task_attachments_for_polymorphism.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            // Renombrar task_id para claridad (opcional, puede mantenerse)
            // Cambiar attachable_type para soportar 'App\Models\Item'
            $table->renameColumn('task_id', 'attachable_id');
        });

        // Actualizar registros existentes
        DB::table('task_attachments')->update([
            'attachable_type' => 'App\\Models\\Task'
        ]);
    }

    public function down(): void
    {
        DB::table('task_attachments')->update([
            'attachable_type' => 'App\\Models\\TaskAttachment' // o eliminar prefijo
        ]);

        Schema::table('task_attachments', function (Blueprint $table) {
            $table->renameColumn('attachable_id', 'task_id');
        });
    }
};
```

**Migración 8: Actualizar `task_private_notes` para soportar polimorfismo**

```php
// database/migrations/2026_06_26_000008_update_task_private_notes_for_polymorphism.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_private_notes', function (Blueprint $table) {
            $table->renameColumn('task_id', 'item_id');
        });

        // Migrar datos al nuevo sistema item_notes si se decide unificar
        // O mantener ambas tablas durante la transición
    }

    public function down(): void
    {
        Schema::table('task_private_notes', function (Blueprint $table) {
            $table->renameColumn('item_id', 'task_id');
        });
    }
};
```

> **Nota sobre la migración de adjuntos y notas:** Se recomienda mantener `task_attachments` y `task_private_notes` como tablas existentes durante la transición, y gradualmente migrar el código para usar las nuevas tablas polimórficas (`item_*`). Esto permite una transición gradual sin romper funcionalidad existente.

#### 7.1.2 Modelos

**Modelo `Item`:**

```php
// app/Models/Item.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'team_id', 'created_by_id', 'parent_id', 'expediente_id',
        'type', 'title', 'description', 'status', 'metadata',
        'visibility', 'due_date', 'scheduled_date', 'priority',
        'progress_percentage', 'matrix_order', 'kanban_column_id',
        'is_archived', 'is_template',
    ];

    protected $casts = [
        'metadata' => 'array',
        'status' => 'array',
        'due_date' => 'datetime',
        'scheduled_date' => 'datetime',
        'progress_percentage' => 'integer',
        'is_archived' => 'boolean',
        'is_template' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    // Relaciones base

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Item::class, 'parent_id');
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function kanbanColumn(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class);
    }

    // Relaciones polimórficas

    public function attachments(): MorphMany
    {
        return $this->morphMany(TaskAttachment::class, 'attachable');
    }

    public function histories(): MorphMany
    {
        return $this->morphMany(ItemHistory::class, 'item');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'item_tags', 'item_id', 'tag_id')
            ->withPivot('item_type', 'color_hex')
            ->withTimestamps();
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(ItemAssignment::class, 'item');
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(ItemRating::class, 'item');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(ItemNote::class, 'item');
    }

    // Accesores y mutadores

    public function getSubtypeAttribute(): ?Model
    {
        return $this->subtype;
    }

    // Métodos de conveniencia

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    public function hasChild(Item $item): bool
    {
        return $this->children()->where('id', $item->id)->exists();
    }

    public function getDescendantCount(): int
    {
        return $this->children()->with('children')->count();
    }
}
```

**Modelo `ItemHistory`:**

```php
// app/Models/ItemHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemHistory extends Model
{
    protected $table = 'item_histories';

    protected $fillable = [
        'item_id', 'item_type', 'user_id', 'action',
        'old_values', 'new_values', 'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

**Modelo `ItemTag`:**

```php
// app/Models/ItemTag.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTag extends Model
{
    protected $table = 'item_tags';

    protected $fillable = [
        'item_id', 'item_type', 'tag', 'color_hex',
    ];
}
```

**Modelo `ItemAssignment`:**

```php
// app/Models/ItemAssignment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemAssignment extends Model
{
    protected $table = 'item_assignments';

    protected $fillable = [
        'item_id', 'item_type', 'user_id', 'group_id',
        'assigned_by_id', 'assigned_at', 'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
```

**Modelo `ItemRating`:**

```php
// app/Models/ItemRating.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemRating extends Model
{
    protected $table = 'item_ratings';

    protected $fillable = [
        'item_id', 'item_type', 'user_id', 'rating', 'kudos_message',
    ];

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

**Modelo `ItemNote`:**

```php
// app/Models/ItemNote.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemNote extends Model
{
    protected $table = 'item_notes';

    protected $fillable = [
        'item_id', 'item_type', 'user_id', 'content', 'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Fase 2: Modelo Ítem y Servicios (Semanas 3-4)

#### 7.2.1 Modelo `Item` — Continuación

**Subtype Models (Modelos de Subtipo):**

Cada subtipo se implementa como un modelo thin que hereda del comportamiento base de `Item` y agrega la lógica específica. Se utiliza **Single Table Inheritance (STI)** a través del campo `type`.

```php
// app/Models/Items/TaskItem.php

namespace App\Models\Items;

use App\Models\Item;
use App\Models\Task;

class TaskItem extends Item
{
    protected $type = 'task';

    /**
     * Obtener la relación con el modelo Task existente.
     * Esto permite que el código existente siga funcionando.
     */
    public function task()
    {
        // En la Fase 3, cuando se migre Task, esto será:
        // return $this->hasOne(Task::class, 'item_id');
        // Por ahora, delega al modelo Task existente
        return $this->belongsTo(Task::class, 'id', 'id');
    }

    /**
     * Obtener el valor de urgencia desde metadata
     */
    public function getUrgencyAttribute()
    {
        return $this->metadata['urgency'] ?? null;
    }

    /**
     * Establecer el valor de urgencia en metadata
     */
    public function setUrgencyAttribute($value)
    {
        $this->metadata['urgency'] = $value;
        $this->save();
    }
}
```

```php
// app/Models/Items/DocumentItem.php

namespace App\Models\Items;

use App\Models\Item;

class DocumentItem extends Item
{
    protected $type = 'document';

    public function getFilePathAttribute()
    {
        return $this->metadata['file_path'] ?? null;
    }

    public function getMimeTypeAttribute()
    {
        return $this->metadata['mime_type'] ?? null;
    }

    public function getFileSizeAttribute()
    {
        return $this->metadata['file_size'] ?? null;
    }

    public function getStorageProviderAttribute()
    {
        return $this->metadata['storage_provider'] ?? 'local';
    }

    public function getExternalUrlAttribute()
    {
        return $this->metadata['external_url'] ?? null;
    }

    /**
     * Obtener la URL para descargar el documento
     */
    public function getDownloadUrlAttribute(): ?string
    {
        $provider = $this->storage_provider;

        return match ($provider) {
            'google' => $this->metadata['web_view_link'],
            default => $this->file_path ? asset($this->file_path) : null,
        };
    }

    /**
     * Verificar si el documento es descargable
     */
    public function isDownloadable(): bool
    {
        return in_array($this->storage_provider, ['local', 'google'])
            && ($this->file_path || $this->external_url);
    }
}
```

```php
// app/Models/Items/NoteItem.php

namespace App\Models\Items;

use App\Models\Item;

class NoteItem extends Item
{
    protected $type = 'note';

    public function getContentAttribute($value)
    {
        return $this->metadata['content'] ?? $value;
    }

    public function setContentAttribute($value)
    {
        $this->metadata['content'] = $value;
        $this->save();
    }

    public function getFormatAttribute()
    {
        return $this->metadata['format'] ?? 'markdown';
    }

    public function getParsedContentAttribute(): string
    {
        return match ($this->format) {
            'html' => $this->content,
            'markdown' => \Markdown::parse($this->content),
            default => nl2br(e($this->content)),
        };
    }
}
```

```php
// app/Models/Items/LinkItem.php

namespace App\Models\Items;

use App\Models\Item;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class LinkItem extends Item
{
    protected $type = 'link';

    public function getUrlAttribute()
    {
        return $this->metadata['url'] ?? null;
    }

    public function setUrlAttribute($value)
    {
        $this->metadata['url'] = $value;
        $this->save();
    }

    public function getOgTitleAttribute()
    {
        return $this->metadata['fetched_og_title'] ?? null;
    }

    public function getOgImageAttribute()
    {
        return $this->metadata['fetched_og_image'] ?? null;
    }

    public function getFetchedAtAttribute()
    {
        return $this->metadata['fetched_at'] ?? null;
    }

    /**
     * Obtener y almacenar metadatos Open Graph de la URL
     */
    public function fetchOpenGraphData(): void
    {
        $html = file_get_contents($this->url);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);

        $title = $this->extractOgProperty($dom, 'og:title');
        $image = $this->extractOgProperty($dom, 'og:image');
        $description = $this->extractOgProperty($dom, 'og:description');

        $this->metadata['fetched_og_title'] = $title;
        $this->metadata['fetched_og_image'] = $image;
        $this->metadata['fetched_og_description'] = $description;
        $this->metadata['fetched_at'] = now()->toIso8601String();

        $this->save();
    }

    private function extractOgProperty(\DOMDocument $dom, string $property): ?string
    {
        $metaTags = $dom->getElementsByTagName('meta');
        foreach ($metaTags as $meta) {
            if ($meta->getAttribute('property') === $property) {
                return $meta->getAttribute('content');
            }
        }
        return null;
    }
}
```

> **Nota:** Los modelos `DecisionItem`, `MeetingItem` y `ReminderItem` siguen el mismo patrón: heredan de `Item`, definen `$type`, y agregan accesores/mutadores para los campos relevantes en `metadata`.

#### 7.2.2 Service: `ItemService`

```php
// app/Services/ItemService.php

namespace App\Services;

use App\Models\Item;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ItemService
{
    /**
     * Crear un nuevo ítem
     */
    public function create(
        string $type,
        string $title,
        array $metadata = [],
        array $options = []
    ): Item {
        return DB::transaction(function () use ($type, $title, $metadata, $options) {
            $teamId = $options['team_id'] ?? Auth::user()->team_id;
            $parentId = $options['parent_id'] ?? null;
            $expedienteId = $options['expediente_id'] ?? null;

            $item = Item::create([
                'type' => $type,
                'title' => $title,
                'metadata' => $metadata,
                'team_id' => $teamId,
                'created_by_id' => Auth::id(),
                'parent_id' => $parentId,
                'expediente_id' => $expedienteId,
                'visibility' => $options['visibility'] ?? 'private',
                'priority' => $options['priority'] ?? 'medium',
                'due_date' => $options['due_date'] ?? null,
                'scheduled_date' => $options['scheduled_date'] ?? null,
                'description' => $options['description'] ?? null,
            ]);

            // Registrar en historial
            $item->histories()->create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'new_values' => $item->getAttributes(),
            ]);

            return $item;
        });
    }

    /**
     * Actualizar un ítem existente
     */
    public function update(Item $item, array $data): Item
    {
        return DB::transaction(function () use ($item, $data) {
            $oldValues = $item->getAttributes();

            $item->update($data);

            $item->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $item->getAttributes(),
            ]);

            return $item->fresh();
        });
    }

    /**
     * Eliminar un ítem (soft delete)
     */
    public function delete(Item $item): void
    {
        DB::transaction(function () use ($item) {
            $item->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'old_values' => $item->getAttributes(),
            ]);

            $item->delete();
        });
    }

    /**
     * Obtener un ítem por tipo y ID
     */
    public function findByType(int $id): ?Item
    {
        return Item::with(['team', 'createdBy', 'parent', 'children', 'expediente'])
            ->find($id);
    }

    /**
     * Buscar ítems con filtros
     */
    public function search(
        Team $team,
        array $filters = []
    ): \Illuminate\Database\Eloquent\Builder {
        $query = Item::where('team_id', $team->id)
            ->where('is_archived', false)
            ->with(['team', 'createdBy', 'parent', 'expediente']);

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['visibility'])) {
            $query->byVisibility($filters['visibility']);
        }

        if (!empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (!empty($filters['expediente_id'])) {
            $query->where('expediente_id', $filters['expediente_id']);
        }

        if (!empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tag', $filters['tags']);
            });
        }

        return $query;
    }

    /**
     * Determinar el modelo de subtipo basado en el tipo
     */
    public function resolveSubtype(string $type): string
    {
        return match ($type) {
            'task' => \App\Models\Items\TaskItem::class,
            'document' => \App\Models\Items\DocumentItem::class,
            'note' => \App\Models\Items\NoteItem::class,
            'link' => \App\Models\Items\LinkItem::class,
            'decision' => \App\Models\Items\DecisionItem::class,
            'meeting' => \App\Models\Items\MeetingItem::class,
            'reminder' => \App\Models\Items\ReminderItem::class,
            'custom' => \App\Models\Items\CustomItem::class,
            default => throw new \InvalidArgumentException("Tipo de ítem no soportado: {$type}"),
        };
    }
}
```

#### 7.2.3 Policy: `ItemPolicy`

```php
// app/Policies/ItemPolicy.php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Determinar si el usuario puede ver el ítem
     */
    public function view(User $user, Item $item): bool
    {
        // El usuario debe pertenecer al mismo equipo
        if ($user->team_id !== $item->team_id) {
            return false;
        }

        // Público: cualquiera del equipo puede ver
        if ($item->visibility === 'public') {
            return true;
        }

        // Semi-privado: cualquiera del equipo puede ver
        if ($item->visibility === 'semi-private') {
            return true;
        }

        // Privado: solo el creador o asignados
        if ($item->visibility === 'private') {
            return $user->id === $item->created_by_id
                || $item->assignments()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determinar si el usuario puede actualizar el ítem
     */
    public function update(User $user, Item $item): bool
    {
        if ($user->team_id !== $item->team_id) {
            return false;
        }

        // Admin y coordinador pueden editar cualquier ítem del equipo
        if ($user->hasRole('admin') || $user->hasRole('coordinator')) {
            return true;
        }

        // Miembros pueden editar sus propios ítems
        return $user->id === $item->created_by_id;
    }

    /**
     * Determinar si el usuario puede eliminar el ítem
     */
    public function delete(User $user, Item $item): bool
    {
        if ($user->team_id !== $item->team_id) {
            return false;
        }

        // Solo admin puede eliminar cualquier ítem
        if ($user->hasRole('admin')) {
            return true;
        }

        // Coordinador puede eliminar ítems del equipo
        if ($user->hasRole('coordinator')) {
            return true;
        }

        // Miembros solo pueden eliminar sus propios ítems
        return $user->id === $item->created_by_id;
    }

    /**
     * Determinar si el usuario puede asignar a otro usuario/grupo
     */
    public function assign(User $user, Item $item): bool
    {
        return $this->update($user, $item);
    }

    /**
     * Determinar si el usuario puede adjuntar archivos
     */
    public function attach(User $user, Item $item): bool
    {
        return $this->view($user, $item);
    }

    /**
     * Determinar si el usuario puede agregar notas privadas
     */
    public function addNote(User $user, Item $item): bool
    {
        return $this->view($user, $item);
    }
}
```

### Fase 3: Migración de Tareas (Semanas 5-6)

#### 7.3.1 Migración de Datos de Tareas a Ítems

```php
// database/migrations/2026_07_10_000001_migrate_tasks_to_items.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrar tasks a items (type='task')
        DB::transaction(function () {
            $tasks = DB::table('tasks')->get();
            $batchSize = 500;
            $batch = [];

            foreach ($tasks as $task) {
                $batch[] = [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'team_id' => $task->team_id,
                    'created_by_id' => $task->created_by_id,
                    'parent_id' => $task->parent_id, // Auto-referencia de tareas
                    'expediente_id' => $task->expediente_id,
                    'type' => 'task',
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => json_encode(['value' => $task->status]),
                    'metadata' => json_encode([
                        'assigned_user_id' => $task->assigned_to,
                        'urgency' => $task->urgency ?? null,
                        'is_template' => $task->is_template ?? false,
                        'autoprogram_settings' => $task->autoprogram_settings ?? null,
                        'autoprogrammable' => $task->autoprogrammable ?? false,
                        'skill_id' => $task->skill_id ?? null,
                        'cognitive_load' => $task->cognitive_load ?? 0,
                        'is_out_of_skill_tree' => $task->is_out_of_skill_tree ?? false,
                        'is_backstage' => $task->is_backstage ?? false,
                        'impact_human_metric' => $task->impact_human_metric ?? 0,
                        'auto_priority' => $task->auto_priority ?? false,
                        'is_timeline_locked' => $task->is_timeline_locked ?? false,
                        'nudge_count' => $task->nudge_count ?? 0,
                        'google_task_id' => $task->google_task_id ?? null,
                        'google_task_list_id' => $task->google_task_list_id ?? null,
                        'google_calendar_event_id' => $task->google_calendar_event_id ?? null,
                        'google_synced_at' => $task->google_synced_at ?? null,
                        'original_due_date' => $task->original_due_date ?? null,
                        'kanban_type' => $task->kanban_type ?? null,
                    ]),
                    'visibility' => $task->visibility ?? 'private',
                    'due_date' => $task->due_date,
                    'scheduled_date' => $task->scheduled_date ?? $task->due_date,
                    'priority' => $task->priority ?? 'medium',
                    'progress_percentage' => $task->progress_percentage ?? 0,
                    'matrix_order' => $task->matrix_order ?? null,
                    'kanban_column_id' => $task->kanban_column_id ?? null,
                    'is_archived' => $task->is_archived ?? false,
                    'is_template' => $task->is_template ?? false,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                    'deleted_at' => $task->deleted_at,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('items')->insert($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('items')->insert($batch);
            }

            // 2. Migrar tags
            $taskTags = DB::table('task_tags')->get();
            $tagBatch = [];

            foreach ($taskTags as $taskTag) {
                $tag = DB::table('tags')->where('id', $taskTag->tag_id)->first();
                if ($tag) {
                    $tagBatch[] = [
                        'item_id' => $taskTag->task_id,
                        'item_type' => 'task',
                        'tag' => $tag->name,
                        'color_hex' => $tag->color_hex ?? '#6b7280',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($tagBatch) >= $batchSize) {
                        DB::table('item_tags')->insert($tagBatch);
                        $tagBatch = [];
                    }
                }
            }

            if (!empty($tagBatch)) {
                DB::table('item_tags')->insert($tagBatch);
            }

            // 3. Migrar asignaciones
            $assignments = DB::table('task_assignments')->get();
            $assignBatch = [];

            foreach ($assignments as $assignment) {
                $assignBatch[] = [
                    'item_id' => $assignment->task_id,
                    'item_type' => 'task',
                    'user_id' => $assignment->user_id,
                    'group_id' => $assignment->group_id,
                    'assigned_by_id' => $assignment->assigned_by_id,
                    'assigned_at' => $assignment->assigned_at ?? now(),
                    'completed_at' => $assignment->completed_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($assignBatch) >= $batchSize) {
                    DB::table('item_assignments')->insert($assignBatch);
                    $assignBatch = [];
                }
            }

            if (!empty($assignBatch)) {
                DB::table('item_assignments')->insert($assignBatch);
            }

            // 4. Migrar historial
            $histories = DB::table('task_histories')->get();
            $historyBatch = [];

            foreach ($histories as $history) {
                $historyBatch[] = [
                    'item_id' => $history->task_id,
                    'item_type' => 'task',
                    'user_id' => $history->user_id,
                    'action' => $history->action ?? 'update',
                    'old_values' => $history->old_values ?? null,
                    'new_values' => $history->new_values ?? null,
                    'notes' => $history->notes ?? null,
                    'created_at' => $history->created_at,
                ];

                if (count($historyBatch) >= $batchSize) {
                    DB::table('item_histories')->insert($historyBatch);
                    $historyBatch = [];
                }
            }

            if (!empty($historyBatch)) {
                DB::table('item_histories')->insert($historyBatch);
            }

            // 5. Migrar calificaciones
            $ratings = DB::table('task_ratings')->get();
            $ratingBatch = [];

            foreach ($ratings as $rating) {
                $ratingBatch[] = [
                    'item_id' => $rating->task_id,
                    'item_type' => 'task',
                    'user_id' => $rating->user_id,
                    'rating' => $rating->rating,
                    'kudos_message' => $rating->kudos_message ?? null,
                    'created_at' => $rating->created_at,
                ];

                if (count($ratingBatch) >= $batchSize) {
                    DB::table('item_ratings')->insert($ratingBatch);
                    $ratingBatch = [];
                }
            }

            if (!empty($ratingBatch)) {
                DB::table('item_ratings')->insert($ratingBatch);
            }

            // 6. Migrar adjuntos
            DB::table('task_attachments')->update([
                'attachable_type' => 'App\\Models\\Task'
            ]);

            // 7. Migrar notas privadas
            DB::table('task_private_notes')->update([
                'item_type' => 'task'
            ]);
        });

        // 8. Crear tabla de mapeo task_id → item_id
        Schema::create('task_item_mapping', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id')->primary();
            $table->unsignedBigInteger('item_id')->unique();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });

        // 9. Poblar el mapeo
        DB::table('task_item_mapping')->insertFromSelect(
            DB::table('tasks')->select('id as task_id', 'id as item_id')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('task_item_mapping');
        Schema::dropIfExists('item_notes');
        Schema::dropIfExists('item_ratings');
        Schema::dropIfExists('item_assignments');
        Schema::dropIfExists('item_histories');
        Schema::dropIfExists('item_tags');
        Schema::dropIfExists('items');
    }
};
```

#### 7.3.2 Actualizar Modelo `Task`

El modelo `Task` existente se convierte en un **wrapper** sobre `Item`:

```php
// app/Models/Task.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $table = 'tasks';

    // Deshabilitar auto-increment (los IDs se derivan de items.id)
    public $incrementing = false;

    /**
     * Obtener el Item padre
     */
    public function item()
    {
        return $this->hasOne(Item::class, 'id', 'id');
    }

    /**
     * Delegar todas las operaciones al Item
     */
    public function scopeWithItem($query)
    {
        return $query->with('item');
    }

    // ... mantener todos los métodos existentes que deleguen al Item
}
```

**Delegación de métodos en Task:**

```php
// En app/Models/Task.php, agregar:

/**
 * Delegar propiedades al Item
 */
public function __get($key)
{
    if ($this->relationLoaded('item') && $this->item) {
        return $this->item->$key ?? null;
    }
    return $this->$key ?? null;
}

public function __set($key, $value)
{
    if ($this->relationLoaded('item') && $this->item) {
        $this->item->$key = $value;
    }
}

/**
 * Delegar relaciones al Item
 */
public function attachments()
{
    return $this->item()->morphMany(TaskAttachment::class, 'attachable');
}

public function histories()
{
    return $this->item()->morphMany(ItemHistory::class, 'item');
}

public function tags()
{
    return $this->item()->morphToMany(Tag::class, 'taggable', 'item_tags')
        ->withPivot('item_type', 'color_hex');
}

public function assignments()
{
    return $this->item()->morphMany(ItemAssignment::class, 'item');
}

public function ratings()
{
    return $this->item()->morphMany(ItemRating::class, 'item');
}

public function notes()
{
    return $this->item()->morphMany(ItemNote::class, 'item');
}
```

### Fase 4: Nuevos Subtipos (Semanas 7-10)

Para cada nuevo subtipo, se siguen los mismos pasos:

1. **Controlador:** `DocumentController`, `NoteController`, etc.
2. **Policy:** Extender `ItemPolicy` con reglas específicas
3. **Vistas:** Blade components para renderizado por tipo
4. **Validadores:** Form request con reglas específicas del tipo
5. **Rutas:** Agregar rutas al `routes/web.php`

**Ejemplo: DocumentController**

```php
// app/Http/Controllers/DocumentController.php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Team;
use App\Services\ItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        protected ItemService $itemService
    ) {}

    /**
     * Mostrar lista de documentos
     */
    public function index(Team $team)
    {
        $documents = $this->itemService->search($team, [
            'type' => 'document',
        ])->paginate(20);

        return view('documents.index', compact('documents', 'team'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(Team $team)
    {
        return view('documents.create', compact('team'));
    }

    /**
     * Almacenar un nuevo documento
     */
    public function store(Request $request, Team $team)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:10240', // 10MB max
            'expediente_id' => 'nullable|exists:expedientes,id',
            'parent_id' => 'nullable|exists:items,id',
            'priority' => 'in:low,medium,high,critical',
            'visibility' => 'in:public,private,semi-private',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/' . $team->id, 'public');

        $item = $this->itemService->create('document', $validated['title'], [
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'storage_provider' => 'local',
        ], [
            'team_id' => $team->id,
            'expediente_id' => $validated['expediente_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'visibility' => $validated['visibility'] ?? 'private',
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('documents.show', [$team, $item->id])
            ->with('success', 'Documento creado exitosamente.');
    }

    /**
     * Mostrar documento
     */
    public function show(Team $team, Item $item)
    {
        Gate::authorize('view', $item);

        if ($item->type !== 'document') {
            abort(404, 'El ítem no es un documento.');
        }

        return view('documents.show', compact('item', 'team'));
    }

    /**
     * Descargar documento
     */
    public function download(Team $team, Item $item)
    {
        Gate::authorize('view', $item);

        if ($item->type !== 'document') {
            abort(404, 'El ítem no es un documento.');
        }

        $filePath = $item->metadata['file_path'];

        return Storage::disk('public')->download($filePath);
    }

    /**
     * Eliminar documento
     */
    public function destroy(Team $team, Item $item)
    {
        Gate::authorize('delete', $item);

        if ($item->type !== 'document') {
            abort(404, 'El ítem no es un documento.');
        }

        // Eliminar archivo del almacenamiento
        if ($item->storage_provider === 'local' && $item->file_path) {
            Storage::disk('public')->delete($item->file_path);
        }

        $this->itemService->delete($item);

        return redirect()->route('documents.index', $team)
            ->with('success', 'Documento eliminado.');
    }
}
```

### Fase 5: Integración con Expedientes y UI (Semanas 11-12)

#### 7.5.1 Actualizar Modelo `Expediente`

```php
// app/Models/Expediente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expediente extends Model
{
    protected $fillable = [
        'team_id', 'name', 'description', 'status', 'visibility',
    ];

    /**
     * Relación con ítems (reemplaza la relación con tasks)
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Alias para compatibilidad con código existente
     */
    public function tasks(): HasMany
    {
        return $this->items()->where('type', 'task');
    }

    /**
     * Obtener todos los hijos (cualquier tipo de ítem)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Item::class)->whereNull('parent_id');
    }

    // ... mantener métodos existentes
}
```

#### 7.5.2 Actualizar Rutas

```php
// routes/web.php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\TaskController;

// Rutas existentes de tareas (redirigen a Item)
Route::resource('teams.{team}/tasks', TaskController::class)->names('tasks');

// Nuevas rutas para tipos de ítem
Route::resource('teams.{team}/documents', DocumentController::class)->names('documents');
Route::resource('teams.{team}/notes', NoteController::class)->names('notes');
Route::resource('teams.{team}/links', LinkController::class)->names('links');
Route::resource('teams.{team}/decisions', DecisionController::class)->names('decisions');
Route::resource('teams.{team}/meetings', MeetingController::class)->names('meetings');
Route::resource('teams.{team}/reminders', ReminderController::class)->names('reminders');

// Ruta genérica para ítems (renderizado por tipo)
Route::get('teams.{team}/items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('teams.{team}/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
Route::patch('teams.{team}/items/{item}', [ItemController::class, 'update'])->name('items.update');
Route::delete('teams.{team}/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
```

#### 7.5.3 Componente Blade para Renderizado por Tipo

```blade
{{-- resources/views/components/item-card.blade.php --}}

@props(['item'])

@php
    $component = match($item->type) {
        'task' => 'components.task-card',
        'document' => 'components.document-card',
        'note' => 'components.note-card',
        'link' => 'components.link-card',
        'decision' => 'components.decision-card',
        'meeting' => 'components.meeting-card',
        'reminder' => 'components.reminder-card',
        default => 'components.item-card',
    };
@endphp

<x-dynamic component="{{ $component }}" :item="$item" {{ $attributes }} />
```

```blade
{{-- resources/views/components/task-card.blade.php --}}

@props(['item'])

<div class="item-card task-card" data-item-id="{{ $item->id }}" data-type="{{ $item->type }}">
    <div class="card-header">
        <span class="type-badge task">{{ __('Tarea') }}</span>
        <span class="priority-badge priority-{{ $item->priority }}">
            {{ ucfirst($item->priority) }}
        </span>
    </div>

    <div class="card-body">
        <h3 class="card-title">
            <a href="{{ route('items.show', [$item->team_id, $item->id]) }}">
                {{ $item->title }}
            </a>
        </h3>

        @if($item->description)
            <p class="card-description">{{ Str::limit($item->description, 100) }}</p>
        @endif

        @if($item->due_date)
            <div class="card-meta">
                <span class="due-date">
                    Vence: {{ $item->due_date->format('d/m/Y H:i') }}
                </span>
            </div>
        @endif
    </div>

    <div class="card-footer">
        @if($item->assignments->isNotEmpty())
            <div class="assignees">
                @foreach($item->assignments->take(3) as $assignment)
                    <x-user-avatar :user="$assignment->user" size="sm" />
                @endforeach
            </div>
        @endif

        <div class="tags">
            @foreach($item->tags->take(3) as $tag)
                <span class="tag" style="background-color: {{ $tag->pivot->color_hex }}">
                    {{ $tag->name }}
                </span>
            @endforeach
        </div>
    </div>
</div>
```

#### 7.5.4 Actualizar Búsqueda Global

```php
// app/Services/ItemService.php (método search ya incluido en Fase 2)

/**
 * Buscar ítems con filtros avanzados
 */
public function searchAdvanced(
    Team $team,
    array $filters = [],
    string $sort = 'created_at',
    string $direction = 'desc'
): \Illuminate\Pagination\LengthAwarePaginator {
    $query = $this->search($team, $filters);

    // Ordenamiento
    $allowedSorts = [
        'created_at', 'updated_at', 'title', 'due_date',
        'priority', 'progress_percentage', 'type',
    ];

    $sortField = in_array($sort, $allowedSorts) ? $sort : 'created_at';
    $sortDirection = in_array($direction, ['asc', 'desc']) ? $direction : 'desc';

    $query->orderBy($sortField, $sortDirection);

    return $query->paginate($filters['per_page'] ?? 20);
}
```

---

## 8. Compatibilidad Hacia Atrás

### 8.1 Opción Recomendada: Base Polimórfica con Wrapper

Se adopta la **Opción A** (Polymorphic Base) porque:

| Criterio | Opción A (Wrapper) | Opción B (Migración Directa) |
|----------|-------------------|------------------------------|
| **Riesgo** | Bajo — las tareas existen en paralelo | Alto — todo cambia simultáneamente |
| **Tiempo de migración** | Gradual, por módulo | Todo a la vez |
| **Rollback** | Simple (eliminar tablas nuevas) | Complejo (restaurar tasks table) |
| **Testing** | Incremental | Exhaustivo de una sola vez |
| **Complejidad** | Media | Alta |
| **Limpieza final** | Requiere refactorizar Task | Limpio desde el inicio |

### 8.2 Mecanismo de Compatibilidad

#### 8.2.1 Mapeo Bidireccional

```
┌─────────────┐         ┌─────────────┐
│   TASKS     │         │    ITEMS    │
│  (legacy)   │◄───────►│ (new base)  │
└─────────────┘  Mapeo  └─────────────┘
                    1:1
```

La tabla `task_item_mapping` mantiene la correspondencia durante toda la transición:

```php
// app/Models/Task.php

/**
 * Obtener el Item correspondiente
 */
public function toItem(): Item
{
    $mapping = DB::table('task_item_mapping')
        ->where('task_id', $this->id)
        ->first();

    if (!$mapping) {
        // Si no existe mapeo, crear uno
        $item = Item::create([
            'uuid' => (string) Str::uuid(),
            'team_id' => $this->team_id,
            'created_by_id' => $this->created_by_id,
            'parent_id' => $this->parent_id,
            'expediente_id' => $this->expediente_id,
            'type' => 'task',
            'title' => $this->title,
            'description' => $this->description,
            'status' => json_encode(['value' => $this->status]),
            'metadata' => $this->getMetadataArray(),
            'visibility' => $this->visibility ?? 'private',
            'due_date' => $this->due_date,
            'scheduled_date' => $this->scheduled_date ?? $this->due_date,
            'priority' => $this->priority ?? 'medium',
            'progress_percentage' => $this->progress_percentage ?? 0,
            'matrix_order' => $this->matrix_order,
            'kanban_column_id' => $this->kanban_column_id,
            'is_archived' => $this->is_archived ?? false,
            'is_template' => $this->is_template ?? false,
        ]);

        DB::table('task_item_mapping')->insert([
            'task_id' => $this->id,
            'item_id' => $item->id,
        ]);

        return $item;
    }

    return Item::find($mapping->item_id);
}
```

#### 8.2.2 Controlador Intermediario

```php
// app/Http/Controllers/TaskController.php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Task;
use App\Services\ItemService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        protected ItemService $itemService
    ) {}

    /**
     * Mostrar lista de tareas (redirige a la vista de ítems filtrada)
     */
    public function index(Request $request, $teamId)
    {
        $team = Team::findOrFail($teamId);

        // Usar ItemService con filtro type='task'
        $tasks = $this->itemService->search($team, [
            'type' => 'task',
            'search' => $request->input('search'),
            'status' => $request->input('status'),
        ])->paginate(20);

        return view('tasks.index', compact('tasks', 'team'));
    }

    /**
     * Mostrar tarea individual (resuelve a Item)
     */
    public function show($teamId, $taskId)
    {
        $task = Task::with(['item', 'item.assignments', 'item.tags'])->findOrFail($taskId);
        $item = $task->item;

        if (!$item) {
            // Caso de tarea sin mapeo (pre-migración)
            return redirect()->route('items.show', [$teamId, $task->id]);
        }

        return view('tasks.show', compact('task', 'item', 'teamId'));
    }

    /**
     * Actualizar tarea (delega a ItemService)
     */
    public function update(Request $request, $teamId, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $item = $task->item;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'in:pending,in_progress,completed,cancelled,blocked',
            'priority' => 'in:low,medium,high,critical',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $item = $this->itemService->update($item, [
            'title' => $validated['title'],
            'status' => json_encode(['value' => $validated['status']]),
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'metadata' => array_merge(
                $item->metadata ?? [],
                ['urgency' => $validated['priority']]
            ),
        ]);

        return redirect()->route('tasks.show', [$teamId, $taskId])
            ->with('success', 'Tarea actualizada.');
    }

    // ... métodos store, destroy, etc. siguen el mismo patrón
}
```

### 8.3 Período de Transición

| Fase | Duración | Acción |
|------|----------|--------|
| **Transición A** | Semanas 1-6 | Tablas nuevas creadas, tareas en paralelo |
| **Transición B** | Semanas 7-12 | Tareas migradas, Task como wrapper |
| **Estabilización** | Semanas 13-14 | Verificación de funcionalidad |
| **Refactorización** | Semanas 15-16 | Eliminación gradual de código legacy |
| **Descomisión** | Semana 17 | Eliminación de tabla `tasks` (opcional) |

---

## 9. Beneficios

### 9.1 Técnicos

| Beneficio | Impacto |
|-----------|---------|
| **Unificación del modelo de datos** | Una sola entidad para todo el contenido |
| **Infraestructura compartida** | Adjuntos, notas, historial, etiquetas, asignaciones funcionan en cualquier tipo |
| **Extensibilidad** | Nuevos tipos de contenido sin nuevas tablas |
| **Jerarquía universal** | Cualquier ítem puede contener cualquier otro ítem |
| **Consistencia** | Mismas políticas, mismas relaciones, misma API |

### 9.2 De Negocio

| Beneficio | Impacto |
|-----------|---------|
| **Gestión documental** | Los expedientes pueden contener documentos, decisiones, reuniones |
| **Base de conocimiento** | Notas, enlaces y decisiones organizados jerárquicamente |
| **Gestión de reuniones** | Reuniones con agenda, asistentes y notas integradas |
| **Registro de decisiones** | Historial de decisiones con justificación y participantes |
| **Curación de contenido** | Enlaces organizados con metadatos y carpetas |
| **Recordatorios inteligentes** | Recordatorios con canales de notificación múltiples |

### 9.3 De Desarrollo

| Beneficio | Impacto |
|-----------|---------|
| **Menos código duplicado** | Lógica compartida en ItemService |
| **Testing más sencillo** | Un conjunto de tests para todos los tipos |
| **Mantenimiento simplificado** | Cambios en infraestructura afectan todos los tipos |
| **Onboarding más fácil** | Un modelo principal para aprender |

---

## 10. Riesgos y Desafíos

### 10.1 Riesgos Técnicos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| **Complejidad de consultas** | Alta | Medio | Índices adecuados, queries optimizadas, caching |
| **Rendimiento de JSON** | Media | Medio | Columnas generadas para campos frecuentemente consultados |
| **Riesgo de migración de datos** | Alta | Alto | Scripts de migración con rollback, pruebas exhaustivas |
| **Complejidad de UI** | Alta | Medio | Componentes Blade reutilizables, diseño modular |
| **Autorización cruzada** | Media | Medio | Policy bien diseñado, tests de autorización |

### 10.2 Desafíos Específicos

#### 10.2.1 Consultas Polimórficas

Las consultas polimórficas en Laravel pueden ser lentas con grandes volúmenes de datos.

**Mitigación:**
```php
// Usar índices compuestos
$table->index(['item_id', 'item_type']);

// Para consultas frecuentes, usar columnas generadas (MySQL 5.7+)
$table->json('metadata')->virtualAs('JSON_EXTRACT(metadata, "$.urgency")');
$table->index('urgency_generated');
```

#### 10.2.2 Validación de Metadatos

Los metadatos JSON requieren validación flexible pero segura.

**Mitigación:**
```php
// Usar reglas de validación con esquemas
class StoreDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'metadata' => [
                'required',
                'json',
                function ($attribute, $value, $fail) {
                    $data = json_decode($value, true);
                    if (!isset($data['file_path'])) {
                        $fail('El campo file_path es requerido.');
                    }
                    if (!isset($data['mime_type'])) {
                        $fail('El campo mime_type es requerido.');
                    }
                },
            ],
        ];
    }
}
```

#### 10.2.3 Renderizado Dinámico

La UI debe renderizar diferentes tipos de ítems de forma coherente.

**Mitigación:**
```blade
{{-- resources/views/items/show.blade.php --}}

<x-item-layout :item="$item">
    @match($item->type)
        @case('task')
            <x-tasks.show :item="$item" />
            @break
        @case('document')
            <x-documents.show :item="$item" />
            @break
        @case('note')
            <x-notes.show :item="$item" />
            @break
        @case('link')
            <x-links.show :item="$item" />
            @break
        @case('decision')
            <x-decisions.show :item="$item" />
            @break
        @case('meeting')
            <x-meetings.show :item="$item" />
            @break
        @case('reminder')
            <x-reminders.show :item="$item" />
            @break
        @default
            <x-items.unknown :item="$item" />
    @endmatch

    {{-- Secciones comunes --}}
    <x-items.comments :item="$item" />
    <x-items.history :item="$item" />
    <x-items.attachments :item="$item" />
    <x-items.tags :item="$item" />
</x-item-layout>
```

#### 10.2.4 Rendimiento de Búsquedas

Buscar en todos los tipos de ítems simultáneamente puede ser costoso.

**Mitigación:**
```php
// Implementar caché para búsquedas frecuentes
public function searchAdvanced(Team $team, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
{
    $cacheKey = 'items.search.' . md5(json_encode($filters));

    return Cache::remember($cacheKey, 300, function () use ($team, $filters) {
        return $this->executeSearch($team, $filters);
    });
}
```

---

## 11. Cronograma Recomendado

### 11.1 Resumen del Cronograma

| Semana | Fase | Entregables |
|--------|------|-------------|
| **1-2** | Fase 1: Fundación | Tablas de base de datos, modelos base |
| **3-4** | Fase 2: Modelo Ítem | Item model, ItemService, ItemPolicy, subtype models |
| **5-6** | Fase 3: Migración de Tareas | Migración de datos, Task como wrapper, controladores actualizados |
| **7-8** | Fase 4a: Document + Note | DocumentController, NoteController, vistas, policies |
| **9-10** | Fase 4b: Link + Decision + Meeting + Reminder | Controladores, vistas, policies para los 4 tipos restantes |
| **11-12** | Fase 5: Integración | Expedientes actualizados, UI unificada, búsqueda, filtros |
| **13-14** | Pruebas y Stabilización | Testing completo, corrección de bugs, optimización |
| **15-16** | Refactorización | Limpieza de código legacy, eliminación de duplicados |
| **17** | Descomisión (opcional) | Eliminación de tabla `tasks`, documentación final |

### 11.2 Hitos Clave

| Hito | Semana | Descripción |
|------|--------|-------------|
| **M1: Fundación lista** | 2 | Base de datos y modelos base funcionales |
| **M2: Tareas operativas en Items** | 6 | Todas las tareas existentes migradas y funcionando |
| **M3: Documentos operativos** | 8 | Creación, visualización y gestión de documentos |
| **M4: Todos los tipos operativos** | 10 | Los 8 tipos de ítem disponibles |
| **M5: Integración completa** | 12 | Expedientes, búsqueda, filtros, notificaciones |
| **M6: Producción** | 14 | Sistema estable, listo para producción |

### 11.3 Recursos Necesarios

| Rol | Cantidad | Tiempo |
|-----|----------|--------|
| **Backend Developer (Laravel)** | 2 | Tiempo completo, 17 semanas |
| **Frontend Developer (Blade/JS)** | 1 | Tiempo completo, 8 semanas (Fase 4-5) |
| **QA Engineer** | 1 | Tiempo completo, 4 semanas (Fase 5 + testing) |
| **DevOps** | 0.5 | Parcial, 2 semanas (migración de datos) |

---

## 12. Decisiones Pendientes

Las siguientes decisiones requieren consenso del equipo antes de iniciar la implementación:

### 12.1 Opción de Migración

- **[ ] Opción A (Recomendada):** Wrapper polimórfico con tabla de mapeo
- **[ ] Opción B:** Migración directa, reemplazo total de `tasks` por `items`

### 12.2 Almacenamiento de Metadatos

- **[ ] JSON nativo de MySQL/PostgreSQL:** Más flexible, menos consultas eficientes
- **[ ] Columnas separadas + JSON:** Más complejo, mejor rendimiento para consultas frecuentes
- **[ ] Hybrid approach:** Campos comunes en columnas, extras en JSON

### 12.3 Gestión de Tipos Personalizados

- **[ ] Sistema de definición JSON Schema completo:** Requiere más desarrollo inicial, máximo flexibility
- **[ ] Tipos predefinidos + campos extra limitados:** Menos flexible, más simple

### 12.4 Sincronización con Google

- **[ ] Reutilizar la infraestructura actual de Google Sync:** Menos trabajo, puede requerir adaptaciones
- **[ ] Nueva implementación para Items:** Más limpio, más trabajo

### 12.5 Gamificación

- **[ ] Extender XP/Kudos a todos los tipos de ítem:** Consistente con la visión
- **[ ] Mantener gamificación solo para tareas:** Más simple, menos coherente

### 12.6 Descomisión de Tabla `tasks`

- **[ ] Sí, eliminar la tabla `tasks` al final:** Código más limpio
- **[ ] No, mantener `tasks` como tabla legacy:** Menos riesgo, más deuda técnica

---

## 13. Anexos

### 13.1 Glosario

| Término | Definición |
|---------|-----------|
| **Ítem** | Entidad universal que puede contener cualquier tipo de contenido |
| **Expediente** | Contenedor jerárquico que agrupa ítems relacionados |
| **Subtipo** | Tipo específico de ítem (task, document, note, etc.) |
| **Metadata** | Campos específicos del tipo, almacenados en JSON |
| **Polimórfico** | Relación que permite que un modelo pertenezca a más de un tipo |
| **STI** | Single Table Inheritance — patrón de herencia en una sola tabla |
| **Soft Delete** | Eliminación lógica que marca el registro como eliminado sin borrarlo |

### 13.2 Referencias

| Recurso | Descripción |
|---------|-------------|
| Laravel Eloquent Relationships | Documentación oficial de relaciones polimórficas |
| Laravel Policies | Documentación de autorización basada en políticas |
| MySQL JSON Functions | Referencia de funciones JSON en MySQL |
| PostgreSQL JSONB | Referencia de tipos JSON en PostgreSQL |

### 13.3 Checklist de Implementación

#### Fase 1: Fundación
- [ ] Crear migración `items`
- [ ] Crear migración `item_tags`
- [ ] Crear migración `item_histories`
- [ ] Crear migración `item_assignments`
- [ ] Crear migración `item_ratings`
- [ ] Crear migración `item_notes`
- [ ] Crear migración de actualización `task_attachments`
- [ ] Crear migración de actualización `task_private_notes`
- [ ] Crear modelo `Item`
- [ ] Crear modelo `ItemHistory`
- [ ] Crear modelo `ItemTag`
- [ ] Crear modelo `ItemAssignment`
- [ ] Crear modelo `ItemRating`
- [ ] Crear modelo `ItemNote`
- [ ] Ejecutar migraciones en ambiente de desarrollo
- [ ] Verificar que las tablas se crearon correctamente

#### Fase 2: Modelo Ítem
- [ ] Crear `ItemService`
- [ ] Crear `ItemPolicy`
- [ ] Crear modelos de subtipo (TaskItem, DocumentItem, etc.)
- [ ] Escribir tests unitarios para ItemService
- [ ] Escribir tests de políticas
- [ ] Escribir tests de modelos de subtipo

#### Fase 3: Migración de Tareas
- [ ] Crear migración de datos de tasks a items
- [ ] Crear tabla `task_item_mapping`
- [ ] Actualizar modelo `Task` como wrapper
- [ ] Actualizar `TaskController` para usar ItemService
- [ ] Migrar datos de prueba en ambiente de desarrollo
- [ ] Verificar integridad de datos post-migración
- [ ] Escribir tests de migración
- [ ] Planificar migración en producción (ventana de mantenimiento)

#### Fase 4: Nuevos Subtipos
- [ ] DocumentController + vistas
- [ ] NoteController + vistas
- [ ] LinkController + vistas
- [ ] DecisionController + vistas
- [ ] MeetingController + vistas
- [ ] ReminderController + vistas
- [ ] Policies para cada tipo
- [ ] Tests de cada controlador
- [ ] Tests de integración

#### Fase 5: Integración
- [ ] Actualizar modelo `Expediente` para relacionar con Items
- [ ] Actualizar vistas de Expediente para mostrar todos los tipos
- [ ] Implementar renderizado por tipo en UI
- [ ] Actualizar búsqueda global
- [ ] Actualizar filtros
- [ ] Actualizar notificaciones
- [ ] Testing completo de integración
- [ ] Pruebas de rendimiento
- [ ] Documentación de usuario

---

## Apéndice A: Ejemplo de Flujo Completo

### 13.A.1 Crear un Documento dentro de un Expediente

```php
// En un controlador o servicio

$expediente = Expediente::findOrFail($expedienteId);

// Crear ítem de tipo documento
$item = app(ItemService::class)->create('document', 'Contrato Final', [
    'file_path' => 'storage/contratos/contrato-2026-001.pdf',
    'mime_type' => 'application/pdf',
    'file_size' => 245760,
    'storage_provider' => 'local',
], [
    'team_id' => $expediente->team_id,
    'expediente_id' => $expediente->id,
    'priority' => 'high',
    'due_date' => now()->addDays(7),
]);

// Adjuntar archivo
$item->attachments()->create([
    'file_path' => $item->metadata['file_path'],
    'file_name' => 'contrato-2026-001.pdf',
    'file_size' => $item->metadata['file_size'],
    'mime_type' => $item->metadata['mime_type'],
    'attachable_type' => get_class($item),
    'attachable_id' => $item->id,
]);

// Asignar a usuario
$item->assignments()->create([
    'user_id' => $userId,
    'assigned_by_id' => auth()->id(),
    'assigned_at' => now(),
]);

// Agregar etiqueta
$item->tags()->attach($tagId, [
    'item_type' => get_class($item),
    'color_hex' => '#FF5733',
]);

// Registrar en historial
$item->histories()->create([
    'user_id' => auth()->id(),
    'action' => 'created',
    'new_values' => $item->getAttributes(),
]);
```

### 13.A.2 Consultar Todos los Ítems de un Expediente

```php
$expediente = Expediente::with([
    'items' => function ($query) {
        $query->orderBy('priority')
              ->orderBy('due_date');
    },
    'items.createdBy',
    'items.assignments.user',
    'items.tags',
])->findOrFail($expedienteId);

// En la vista
@foreach($expediente->items as $item)
    <x-item-card :item="$item" />
@endforeach
```

### 13.A.3 Consultar Tareas de un Expediente (Compatibilidad)

```php
// Código existente sigue funcionando
$tasks = $expediente->tasks()->with('assignments')->get();

// O usando la nueva infraestructura
$tasks = $expediente->items()->where('type', 'task')->with('assignments')->get();
```

---

## Apéndice B: Comandos de Migración en Producción

```bash
# 1. Backup de la base de datos
mysqldump -u root -p sientia_mtx > backup_before_items_$(date +%Y%m%d).sql

# 2. Ejecutar migraciones
php artisan migrate --force

# 3. Verificar integridad
php artisan tinker
>>> DB::table('tasks')->count();
>>> DB::table('items')->where('type', 'task')->count();
>>> DB::table('task_item_mapping')->count();
>>> exit

# 4. Verificar conteos
>>> DB::table('tasks')->count() === DB::table('items')->where('type', 'task')->count()
>>> DB::table('task_item_mapping')->count() === DB::table('tasks')->count()

# 5. Si hay problemas, revertir
php artisan migrate:rollback --step=8

# 6. Si todo está bien, limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## Apéndice C: Pruebas de Integridad de Datos

```php
// tests/Feature/ItemMigrationTest.php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_task_item()
    {
        $team = Team::factory()->create();
        $user = User::factory()->for($team)->create();

        $item = Item::create([
            'team_id' => $team->id,
            'created_by_id' => $user->id,
            'type' => 'task',
            'title' => 'Tarea de prueba',
            'metadata' => ['urgency' => 'high'],
        ]);

        $this->assertEquals('task', $item->type);
        $this->assertEquals('Tarea de prueba', $item->title);
        $this->assertEquals('high', $item->metadata['urgency']);
    }

    /** @test */
    public function it_can_create_a_document_item()
    {
        $team = Team::factory()->create();
        $user = User::factory()->for($team)->create();

        $item = Item::create([
            'team_id' => $team->id,
            'created_by_id' => $user->id,
            'type' => 'document',
            'title' => 'Contrato',
            'metadata' => [
                'file_path' => 'storage/contratos/1.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 102400,
                'storage_provider' => 'local',
            ],
        ]);

        $this->assertEquals('document', $item->type);
        $this->assertEquals('storage/contratos/1.pdf', $item->metadata['file_path']);
    }

    /** @test */
    public function it_can_create_hierarchical_items()
    {
        $team = Team::factory()->create();
        $user = User::factory()->for($team)->create();

        $parent = Item::create([
            'team_id' => $team->id,
            'created_by_id' => $user->id,
            'type' => 'task',
            'title' => 'Tarea padre',
        ]);

        $child1 = Item::create([
            'team_id' => $team->id,
            'created_by_id' => $user->id,
            'type' => 'document',
            'title' => 'Documento hijo',
            'parent_id' => $parent->id,
        ]);

        $child2 = Item::create([
            'team_id' => $team->id,
            'created_by_id' => $user->id,
            'type' => 'note',
            'title' => 'Nota hija',
            'parent_id' => $parent->id,
        ]);

        $this->assertCount(2, $parent->children);
        $this->assertEquals($parent->id, $child1->parent_id);
        $this->assertEquals($parent->id, $child2->parent_id);
    }

    /** @test */
    public function it_can_add_tags_to_any_item_type()
    {
        $team = Team::factory()->create();
        $user = User::factory()->for($team)->create();

        $item = Item::create([
            'team_id' => $team->id,
            'created_by_id' => $user->id,
            'type' => 'document',
            'title' => 'Documento etiquetado',
        ]);

        $item->tags()->attach(1, [
            'item_type' => 'document',
            'color_hex' => '#FF5733',
        ]);

        $this->assertCount(1, $item->tags);
    }

    /** @test */
    public function it_can_add_history_to_any_item_type()
    {
        $team = Team::factory()->create();
        $user = User::factory()->for($team)->create();

        $item = Item::create([
            'team_id' => $team->id,
            'created_by_id' => $user->id,
            'type' => 'meeting',
            'title' => 'Reunión de equipo',
        ]);

        $item->histories()->create([
            'user_id' => $user->id,
            'action' => 'created',
            'new_values' => $item->getAttributes(),
        ]);

        $this->assertCount(1, $item->histories);
    }

    /** @test */
    public function item_policy_allows_team_members_to_view_public_items()
    {
        $team = Team::factory()->create();
        $member = User::factory()->for($team)->create();

        $item = Item::create([
            'team_id' => $team->id,
            'created_by_id' => $member->id,
            'type' => 'task',
            'title' => 'Tarea pública',
            'visibility' => 'public',
        ]);

        $this->assertTrue(policy($item)->view($member, $item));
    }

    /** @test */
    public function item_policy_prevents_outside_team_from_viewing_items()
    {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();
        $user1 = User::factory()->for($team1)->create();
        $user2 = User::factory()->for($team2)->create();

        $item = Item::create([
            'team_id' => $team1->id,
            'created_by_id' => $user1->id,
            'type' => 'task',
            'title' => 'Tarea privada',
            'visibility' => 'private',
        ]);

        $this->assertFalse(policy($item)->view($user2, $item));
    }
}
```

---

**Fin del Documento**

---

*Esta propuesta está sujeta a revisión y aprobación por el equipo de desarrollo. Las decisiones marcadas en la sección 12 deben resolverse antes de iniciar la implementación.*

*Para preguntas o comentarios, contactar al equipo de arquitectura del proyecto.*
