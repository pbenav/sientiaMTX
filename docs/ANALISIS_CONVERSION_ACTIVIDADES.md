# SientiaMTX — Análisis Técnico: Conversión de Tipos de Actividad y Sistema de Plantillas JSON

> **Proyecto:** SientiaMTX — Sistema de Gestión de Proyectos y Productividad
> **Arquitectura:** Laravel 11+ con Single Table Inheritance (STI)
> **Fecha:** Junio 2026
> **Alcance:** (1) Conversión entre tipos de actividad, (2) Diseño de un sistema de plantillas JSON para tipos dinámicos.

---

## 1. Resumen Ejecutivo

El sistema SientiaMTX gestiona actividades mediante una arquitectura de **Single Table Inheritance (STI)** con una tabla `activities` que almacena datos compartidos (título, descripción, fecha, estado, tipo) y una columna `metadata` de tipo JSON para datos específicos de cada subtipo. Actualmente existen 7 tipos de actividad: `TaskActivity`, `DocumentActivity`, `NoteActivity`, `LinkActivity`, `DecisionActivity`, `MeetingActivity` y `ReminderActivity`.

**Problema identificado:** La conversión entre tipos de actividad es limitada y existen dependencias hardcodeadas en controladores, modelos y políticas que impiden agregar nuevos tipos o convertir entre tipos sin modificar múltiples archivos.

**Hallazgos principales:**
- La conversión actual (`ConvertActivityAction`) solo permite convertir entre tipos compatibles (Task ↔ Document, Task ↔ Note, etc.) y utiliza un mecanismo de deprecatión (marca la actividad original como `is_archived=true` y `status='converted'`), creando una nueva actividad del tipo destino.
- Existen ~40+ instancias de `match`/`switch` statements distribuidos en `Activity.php`, `KanbanController.php`, `TaskActionController.php`, `ActivityService.php` y `StoreActivityRequest.php` que mapean tipos a estados, columnas de Kanban, reglas de validación y acciones permitidas.
- El sistema de plantillas JSON propuesto permitiría definir tipos de actividad de forma declarativa, reduciendo la necesidad de código hardcodeado y facilitando la extensibilidad.

**Recomendación:** Implementar un sistema de plantillas JSON para los tipos de actividad, migrar la lógica condicional hardcodeada a un motor de reglas basado en plantillas, y mejorar el servicio de conversión para que sea consciente de las plantillas.

---

## 2. Análisis de Conversión

### 2.1 Estado Actual

La conversión de actividades se implementa en `ConvertActivityAction.php`. El flujo actual es:

```
Actividad Origen (tipo A) → [ConvertActivityAction] → Actividad Original Deprecada + Nueva Actividad (tipo B)
```

**Mecanismo de deprecatión:**
1. La actividad original se marca con `is_archived = true` y `status = 'converted'`.
2. Se crea una nueva actividad del tipo destino con los datos compartidos (título, descripción, fechas).
3. Los datos específicos del tipo destino se inicializan con valores por defecto.
4. Se preservan relaciones compartidas: tags, asignaciones, notas, adjuntos, historial, ratings.

**Reglas de conversión actuales (simplificadas):**
- `TaskActivity` puede convertirse a: `DocumentActivity`, `NoteActivity`, `DecisionActivity`
- `DocumentActivity` puede convertirse a: `TaskActivity`, `NoteActivity`
- `NoteActivity` puede convertirse a: `TaskActivity`, `DocumentActivity`, `LinkActivity`
- `LinkActivity` puede convertirse a: `NoteActivity`
- `DecisionActivity` puede convertirse a: `TaskActivity`, `NoteActivity`
- `MeetingActivity` puede convertirse a: `TaskActivity`, `NoteActivity`
- `ReminderActivity` puede convertirse a: `NoteActivity`

### 2.2 Limitaciones Identificadas

**a) Conversión con pérdida de datos:**
Al convertir de un tipo a otro, los metadatos específicos del tipo origen se pierden. Por ejemplo, convertir un `TaskActivity` (con `metadata.file_path`, `metadata.progress`, `metadata.quadrant`) a un `NoteActivity` (con `metadata.content`) resulta en pérdida de los datos de progreso y cuadrante.

```php
// ConvertActivityAction.php - Línea aproximada 45-60
public function execute(Activity $activity, string $targetType): Activity
{
    // Depreca la actividad original
    $activity->update([
        'is_archived' => true,
        'status' => 'converted',
        'converted_to_type' => $targetType,
    ]);

    // Crea nueva actividad del tipo destino
    $data = [
        'user_id' => $activity->user_id,
        'project_id' => $activity->project_id,
        'title' => $activity->title,
        'description' => $activity->description,
        'type' => $targetType,
        'status' => $this->getDefaultStatus($targetType),
        'metadata' => $this->getDefaultMetadata($targetType), // ¡Pérdida de datos originales!
    ];

    return Activity::create($data);
}
```

**b) Hardcoded conversion rules:**
Las reglas de conversión están hardcodeadas en el action, lo que impide agregar nuevas reglas sin modificar código.

**c) Falta de trazabilidad:**
Aunque se registra `converted_to_type`, no se mantiene un enlace explícito (`converted_from_id`) que permita navegar de la actividad convertida a su origen.

**d) Estados no mapeados:**
Algunos tipos de actividad tienen estados propios (ej. `TaskActivity` tiene `pending`, `in_progress`, `completed`, `cancelled`) pero la conversión no considera la compatibilidad de estados entre tipos.

### 2.3 Propuestas de Mejora

**a) Conversión con preservación de metadatos:**
Almacenar los metadatos originales en un campo `original_metadata` antes de la conversión, permitiendo auditar y restaurar datos si es necesario.

```php
// Propuesta: ConvertActivityAction mejorado
public function execute(Activity $activity, string $targetType): Activity
{
    $activity->update([
        'is_archived' => true,
        'status' => 'converted',
        'converted_to_type' => $targetType,
        'converted_from_id' => $activity->id, // Nuevo campo en la tabla activities
        'original_metadata' => $activity->metadata, // Preservar metadatos originales
    ]);

    // ... creación de nueva actividad
}
```

**b) Reglas de conversión declarativas:**
Mover las reglas de conversión a un archivo de configuración (ej. `config/activity_conversions.php`) en lugar de código hardcodeado.

```php
// config/activity_conversions.php
return [
    'TaskActivity' => [
        'allowed_conversions' => ['DocumentActivity', 'NoteActivity', 'DecisionActivity'],
        'preserve_metadata' => ['title', 'description', 'metadata.tags', 'metadata.assignments'],
        'default_status' => 'pending',
    ],
    'DocumentActivity' => [
        'allowed_conversions' => ['TaskActivity', 'NoteActivity'],
        'preserve_metadata' => ['title', 'description'],
        'default_status' => 'uploaded',
    ],
    // ...
];
```

**c) Validación de conversión:**
Agregar validación antes de la conversión para verificar:
- Si el tipo destino es permitido desde el tipo origen.
- Si el usuario tiene permisos para convertir (verificar `ActivityPolicy`).
- Si los datos requeridos del tipo destino están disponibles (o pueden tener valores por defecto).

---

## 3. Análisis de Propiedades por Tipo de Actividad

### 3.1 Tabla de Propiedades por Tipo

| Propiedad | TaskActivity | DocumentActivity | NoteActivity | LinkActivity | DecisionActivity | MeetingActivity | ReminderActivity |
|---|---|---|---|---|---|---|---|
| **Comunes (tabla `activities`)** | | | | | | | |
| id | X | X | X | X | X | X | X |
| user_id | X | X | X | X | X | X | X |
| project_id | X | X | X | X | X | X | X |
| title | X | X | X | X | X | X | X |
| description | X | X | X | X | X | X | X |
| type | X | X | X | X | X | X | X |
| status | X | X | X | X | X | X | X |
| metadata (JSON) | X | X | X | X | X | X | X |
| due_date | X | X | X | X | X | X | X |
| is_archived | X | X | X | X | X | X | X |
| **Específicos (en `metadata`)** | | | | | | | |
| content | - | - | X | - | - | - | - |
| file_path | - | X | - | - | - | - | - |
| file_size | - | X | - | - | - | - | - |
| mime_type | - | X | - | - | - | - | - |
| url | - | - | - | X | - | - | - |
| og_title | - | - | - | X | - | - | - |
| og_description | - | - | - | X | - | - | - |
| quadrant | X | - | - | - | - | - | - |
| priority | X | - | - | - | - | - | - |
| progress | X | - | - | - | - | - | - |
| status (task) | X | - | - | - | - | - | - |
| meeting_location | - | - | - | - | - | X | - |
| meeting_start_time | - | - | - | - | - | X | - |
| meeting_end_time | - | - | - | - | - | X | - |
| decision_question | - | - | - | - | X | - | - |
| decision_options | - | - | - | - | X | - | - |
| decision_voting_rule | - | - | - | - | X | - | - |
| reminder_message | - | - | - | - | - | - | X |
| reminder_channel | - | - | - | - | - | - | X |
| reminder_repeat | - | - | - | - | - | - | X |
| **Relaciones Compartidas** | | | | | | | |
| tags (activity_tags) | X | X | X | X | X | X | X |
| assignments | X | X | X | X | X | X | X |
| notes (activity_notes) | X | X | X | X | X | X | X |
| attachments | X | X | X | X | X | X | X |
| histories | X | X | X | X | X | X | X |
| ratings | X | X | X | X | X | X | X |

### 3.2 Análisis de Superposición

**Alta superposición (datos compartidos):**
- Todas las actividades comparten: `id`, `user_id`, `project_id`, `title`, `description`, `type`, `status`, `due_date`, `is_archived`.
- Todas las actividades comparten las mismas relaciones: `tags`, `assignments`, `notes`, `attachments`, `histories`, `ratings`.

**Superposición media (datos en `metadata`):**
- `title` y `description` están en la tabla principal pero también podrían referenciarse en `metadata` para tipos que necesiten versiones específicas.
- `due_date` es universal pero su semántica varía: para `TaskActivity` es la fecha límite, para `MeetingActivity` es el inicio de la reunión, para `ReminderActivity` es la fecha de notificación.

**Baja superposición (datos únicos por tipo):**
- Cada tipo tiene propiedades únicas en `metadata` que no se comparten con otros tipos. Esta es la principal fuente de complejidad en la conversión.

### 3.3 Matriz de Compatibilidad de Conversión

```
Tipo Origen → Tipo Destino:
                           Task  Doc   Note  Link  Decis  Meet  Remind
TaskActivity              |  -    Y     Y     N     Y      Y     N
DocumentActivity          |  Y    -     Y     N     N      N     N
NoteActivity              |  Y    Y     -     Y     N      N     N
LinkActivity              |  N    N     Y     -     N      N     N
DecisionActivity          |  Y    N     Y     N     -      N     N
MeetingActivity           |  Y    N     Y     N     N      -     N
ReminderActivity          |  N    N     Y     N     N      N     -
```

**Y = Conversión permitida | N = Conversión no permitida | - = Mismo tipo**

---

## 4. Diseño del Sistema de Plantillas JSON

### 4.1 Concepto General

El sistema de plantillas JSON propone definir cada tipo de actividad como una plantilla declarativa que describe:
1. **Propiedades:** Campos disponibles (nombre, tipo, requerido, valor por defecto).
2. **Reglas de validación:** Constraints por propiedad (regex, rango, lista de valores).
3. **Reglas de conversión:** Tipos destino permitidos y mapeo de propiedades.
4. **Estados:** Estados válidos y transiciones permitidas.
5. **Acciones:** Acciones permitidas (crear, actualizar, eliminar, convertir, archivar).

### 4.2 Estructura de la Plantilla JSON

```json
{
  "type": "TaskActivity",
  "display_name": "Tarea",
  "description": "Actividad para gestionar tareas con seguimiento de progreso",
  "icon": "icon-task",
  "color": "#3498db",
  "properties": {
    "title": {
      "type": "string",
      "required": true,
      "max_length": 255,
      "searchable": true
    },
    "description": {
      "type": "text",
      "required": false,
      "max_length": 5000,
      "searchable": true
    },
    "quadrant": {
      "type": "integer",
      "required": false,
      "default": 1,
      "enum": [1, 2, 3, 4],
      "validation": {
        "min": 1,
        "max": 4
      }
    },
    "priority": {
      "type": "string",
      "required": false,
      "default": "medium",
      "enum": ["low", "medium", "high", "urgent"]
    },
    "progress": {
      "type": "integer",
      "required": false,
      "default": 0,
      "validation": {
        "min": 0,
        "max": 100
      }
    },
    "status": {
      "type": "string",
      "required": false,
      "default": "pending",
      "enum": ["pending", "in_progress", "completed", "cancelled"]
    },
    "skills_required": {
      "type": "array",
      "required": false,
      "default": []
    }
  },
  "states": {
    "pending": {
      "transitions": ["in_progress", "cancelled"]
    },
    "in_progress": {
      "transitions": ["completed", "in_progress"]
    },
    "completed": {
      "transitions": []
    },
    "cancelled": {
      "transitions": ["pending"]
    }
  },
  "conversions": {
    "allowed_targets": ["DocumentActivity", "NoteActivity", "DecisionActivity", "MeetingActivity"],
    "property_mapping": {
      "title": "title",
      "description": "description",
      "due_date": "due_date"
    },
    "preserve_metadata": ["tags", "assignments", "attachments"]
  },
  "actions": {
    "create": true,
    "update": true,
    "delete": true,
    "convert": true,
    "archive": true,
    "assign": true,
    "rate": true
  },
  "kanban": {
    "columns": [
      {
        "id": "backlog",
        "name": "Backlog",
        "status_filter": ["pending"]
      },
      {
        "id": "in_progress",
        "name": "En Progreso",
        "status_filter": ["in_progress"]
      },
      {
        "id": "done",
        "name": "Hecho",
        "status_filter": ["completed"]
      }
    ]
  }
}
```

### 4.3 Almacenamiento de Plantillas

**Opción A: Archivos JSON en filesystem (recomendado para MVP)**
```
config/activity_templates/
├── task_activity.json
├── document_activity.json
├── note_activity.json
├── link_activity.json
├── decision_activity.json
├── meeting_activity.json
└── reminder_activity.json
```

**Opción B: Tabla de base de datos `activity_templates`**
```sql
CREATE TABLE activity_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    template JSON NOT NULL,
    version INT NOT NULL DEFAULT 1,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Opción C: Caché con fallback a filesystem/DB**
- Cargar plantillas desde archivos JSON al inicio de la aplicación.
- Cachear en memoria (Laravel Cache) con TTL de 1 hora.
- Invalidar caché cuando se modifique una plantilla.

### 4.4 Motor de Validación Basado en Plantillas

```php
class ActivityTemplateValidator
{
    protected array $templates;

    public function __construct()
    {
        $this->templates = $this->loadTemplates();
    }

    public function validate(string $type, array $data): bool
    {
        $template = $this->getTemplate($type);
        if (!$template) {
            throw new InvalidActivityTypeException("Tipo '$type' no encontrado");
        }

        foreach ($template['properties'] as $fieldName => $rules) {
            $value = $data[$fieldName] ?? $rules['default'] ?? null;

            // Validar requerido
            if ($rules['required'] && ($value === null || $value === '')) {
                throw new ValidationException("El campo '$fieldName' es requerido");
            }

            // Validar tipo
            if ($value !== null && !$this->validateType($value, $rules['type'])) {
                throw new ValidationException("El campo '$fieldName' debe ser de tipo {$rules['type']}");
            }

            // Validar enum
            if (isset($rules['enum']) && !in_array($value, $rules['enum'])) {
                throw new ValidationException("El campo '$fieldName' debe ser uno de: " . implode(', ', $rules['enum']));
            }

            // Validar rango numérico
            if (isset($rules['validation'])) {
                if (isset($rules['validation']['min']) && $value < $rules['validation']['min']) {
                    throw new ValidationException("El campo '$fieldName' debe ser >= {$rules['validation']['min']}");
                }
                if (isset($rules['validation']['max']) && $value > $rules['validation']['max']) {
                    throw new ValidationException("El campo '$fieldName' debe ser <= {$rules['validation']['max']}");
                }
            }

            // Validar longitud máxima
            if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                throw new ValidationException("El campo '$fieldName' debe tener máximo {$rules['max_length']} caracteres");
            }
        }

        return true;
    }

    protected function validateType($value, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => is_string($value),
            'text' => is_string($value),
            'integer' => is_int($value),
            'float' => is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            default => false,
        };
    }
}
```

### 4.5 Integración con `StoreActivityRequest`

Reemplazar las reglas de validación hardcodeadas en `StoreActivityRequest.php` con el motor basado en plantillas:

```php
// StoreActivityRequest.php - Versión mejorada
public function rules(): array
{
    $validator = app(ActivityTemplateValidator::class);
    $template = $validator->getTemplate($this->route('activity')?->type ?? $this->input('type'));

    $rules = [
        'title' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string', 'max:5000'],
        'type' => ['required', 'in:' . implode(',', array_keys(TemplateLoader::allTypes()))],
        'due_date' => ['nullable', 'date'],
    ];

    // Agregar reglas específicas del tipo desde la plantilla
    if (isset($template['properties'])) {
        foreach ($template['properties'] as $fieldName => $fieldRules) {
            if ($fieldRules['required']) {
                $rules[$fieldName] = ['required'];
            }

            switch ($fieldRules['type']) {
                case 'string':
                case 'text':
                    $rules[$fieldName][] = 'string';
                    if (isset($fieldRules['max_length'])) {
                        $rules[$fieldName][] = "max:{$fieldRules['max_length']}";
                    }
                    break;
                case 'integer':
                    $rules[$fieldName][] = 'integer';
                    if (isset($fieldRules['validation']['min'])) {
                        $rules[$fieldName][] = "min:{$fieldRules['validation']['min']}";
                    }
                    if (isset($fieldRules['validation']['max'])) {
                        $rules[$fieldName][] = "max:{$fieldRules['validation']['max']}";
                    }
                    break;
                case 'array':
                    $rules[$fieldName][] = 'array';
                    break;
            }

            if (isset($fieldRules['enum'])) {
                $rules[$fieldName][] = 'in:' . implode(',', $fieldRules['enum']);
            }
        }
    }

    return $rules;
}
```

### 4.6 Motor de Conversión Basado en Plantillas

```php
class TemplateAwareConvertActivityAction
{
    protected ActivityTemplateValidator $validator;
    protected TemplateLoader $templateLoader;

    public function execute(Activity $activity, string $targetType): Activity
    {
        $sourceTemplate = $this->templateLoader->getTemplate($activity->type);
        $targetTemplate = $this->templateLoader->getTemplate($targetType);

        // Validar conversión permitida
        if (!in_array($targetType, $sourceTemplate['conversions']['allowed_targets'])) {
            throw new InvalidConversionException(
                "No se permite convertir {$activity->type} a $targetType"
            );
        }

        // Deprecar actividad original
        $activity->update([
            'is_archived' => true,
            'status' => 'converted',
            'converted_to_type' => $targetType,
            'converted_from_id' => $activity->id,
            'original_metadata' => $activity->metadata,
        ]);

        // Mapear propiedades según la plantilla
        $mapping = $sourceTemplate['conversions']['property_mapping'];
        $metadata = [];
        foreach ($mapping as $sourceField => $targetField) {
            if (isset($activity->metadata[$sourceField])) {
                $metadata[$targetField] = $activity->metadata[$sourceField];
            } elseif (isset($targetTemplate['properties'][$targetField]['default'])) {
                $metadata[$targetField] = $targetTemplate['properties'][$targetField]['default'];
            }
        }

        // Crear nueva actividad
        $data = [
            'user_id' => $activity->user_id,
            'project_id' => $activity->project_id,
            'title' => $activity->title,
            'description' => $activity->description,
            'type' => $targetType,
            'status' => $targetTemplate['conversions']['default_status'] ?? 'pending',
            'metadata' => $metadata,
            'due_date' => $activity->due_date,
        ];

        return Activity::create($data);
    }
}
```

---

## 5. Análisis de Factibilidad

### 5.1 Complejidad Estimada

| Componente | Complejidad | Tiempo Estimado |
|---|---|---|
| Diseño de estructura de plantillas JSON | Baja | 1-2 días |
| Implementación de TemplateLoader | Baja | 2-3 días |
| Implementación de ActivityTemplateValidator | Media | 3-5 días |
| Migración de StoreActivityRequest | Media | 2-3 días |
| Implementación de TemplateAwareConvertActivityAction | Media | 3-4 días |
| Migración de KanbanController | Alta | 5-7 días |
| Migración de TaskActionController | Alta | 4-6 días |
| Migración de ActivityService | Alta | 5-7 días |
| Migración de ActivityPolicy | Media | 3-4 días |
| Migración de modelos (booted, relationships) | Media | 3-5 días |
| Pruebas unitarias y de integración | Media-Alta | 5-7 días |
| Pruebas de regresión | Alta | 3-5 días |
| Documentación y deploy | Baja | 2-3 días |
| **Total estimado** | | **39-53 días hábles (~2-2.5 meses)** |

### 5.2 Riesgos Identificados

**Riesgo Alto:**
- **Breaking changes:** La migración de `KanbanController` y `TaskActionController` puede romper funcionalidades existentes si no se prueban exhaustivamente.
- **Performance:** El uso de plantillas JSON para validación puede agregar overhead si no se cachea adecuadamente.

**Riesgo Medio:**
- **Dependencias entre tipos:** Algunos tipos de actividad tienen dependencias implícitas (ej. `TaskActivity` depende de `ActivityService` para actualizaciones de progreso) que pueden no estar documentadas.
- **Migración de datos:** Si se decide agregar los campos `converted_from_id` y `original_metadata` a la tabla `activities`, se necesitará una migración que puede afectar el rendimiento en producción.

**Riesgo Bajo:**
- **Resistencia al cambio:** Los desarrolladores pueden preferir el enfoque actual más simple.
- **Mantenimiento de plantillas:** Las plantillas JSON deben mantenerse sincronizadas con el código PHP.

### 5.3 Precondiciones

1. **Cobertura de pruebas:** Asegurar que el 80%+ del código existente tiene pruebas unitarias antes de comenzar la migración.
2. **Base de datos:** Agregar los campos `converted_from_id` y `original_metadata` a la tabla `activities`.
3. **Configuración:** Crear el directorio `config/activity_templates/` con las plantillas JSON iniciales.
4. **Deprecación gradual:** Mantener el sistema actual durante la migración y usar feature flags para activar gradualmente el nuevo sistema.

### 5.4 Estrategia de Migración Recomendada

**Fase 1: Fundamentos (Semana 1-2)**
- Crear estructura de plantillas JSON.
- Implementar `TemplateLoader` con caché.
- Agregar campos `converted_from_id` y `original_metadata` a `activities`.

**Fase 2: Validación (Semana 3-4)**
- Implementar `ActivityTemplateValidator`.
- Migrar `StoreActivityRequest` gradualmente (primero nuevos tipos, luego existentes).
- Agregar pruebas unitarias.

**Fase 3: Conversión (Semana 5-6)**
- Implementar `TemplateAwareConvertActivityAction`.
- Migrar `ConvertActivityAction` gradualmente.
- Agregar pruebas de conversión.

**Fase 4: Controladores (Semana 7-10)**
- Migrar `KanbanController` (el más complejo con ~40+ líneas de match/switch).
- Migrar `TaskActionController`.
- Migrar `ActivityService`.
- Agregar pruebas de integración.

**Fase 5: Políticas y Modelos (Semana 11-12)**
- Migrar `ActivityPolicy`.
- Simplificar modelos STI (eliminar `booted()` con match/switch).
- Agregar pruebas de autorización.

**Fase 6: Limpieza y Deploy (Semana 13-14)**
- Eliminar código obsoleto.
- Pruebas de regresión completas.
- Deploy gradual con feature flags.
- Documentación final.

---

## 6. Hoja de Ruta

### 6.1 Corto Plazo (1-2 meses)

**Objetivo:** Implementar el sistema de plantillas JSON y migrar la conversión de actividades.

**Entregables:**
1. [x] Estructura de plantillas JSON para los 7 tipos existentes.
2. [ ] `TemplateLoader` con caché en memoria.
3. [ ] `ActivityTemplateValidator` con validación declarativa.
4. [ ] `StoreActivityRequest` migrado a validación basada en plantillas.
5. [ ] `TemplateAwareConvertActivityAction` con preservación de metadatos.
6. [ ] Migración de base de datos: `converted_from_id`, `original_metadata`.
7. [ ] Pruebas unitarias para validación y conversión.

### 6.2 Mediano Plazo (2-4 meses)

**Objetivo:** Migrar controladores y servicios al sistema de plantillas.

**Entregables:**
1. [ ] `KanbanController` migrado a lógica basada en plantillas.
2. [ ] `TaskActionController` migrado a lógica basada en plantillas.
3. [ ] `ActivityService` migrado a lógica basada en plantillas.
4. [ ] `ActivityPolicy` migrado a lógica basada en plantillas.
5. [ ] Modelos STI simplificados (eliminar `booted()` con match/switch).
6. [ ] Pruebas de integración para controladores.
7. [ ] Pruebas de regresión completas.

### 6.3 Largo Plazo (4-6 meses)

**Objetivo:** Extensibilidad completa y soporte para tipos dinámicos.

**Entregables:**
1. [ ] API para crear tipos de actividad personalizados vía UI.
2. [ ] Sistema de plugins para extensiones de tipos.
3. [ ] Dashboard de métricas por tipo de actividad.
4. [ ] Exportación/importación de plantillas.
5. [ ] Documentación completa del sistema.
6. [ ] Migración de frontend para soportar tipos dinámicos.

---

## 7. Conclusiones

### 7.1 Resumen de Hallazgos

1. **La arquitectura STI con columna `metadata` JSON es sólida** para soportar tipos de actividad diversos sin necesidad de múltiples tablas. Sin embargo, la dependencia de lógica hardcodeada (`match`/`switch`) en controladores, modelos y servicios limita la extensibilidad.

2. **La conversión de actividades actual es funcional pero limitada:**
   - No preserva metadatos originales.
   - No mantiene trazabilidad explícita entre actividad origen y destino.
   - Reglas de conversión hardcodeadas.

3. **Existen ~40+ instancias de código condicional hardcodeado** distribuidos en `Activity.php`, `KanbanController.php`, `TaskActionController.php`, `ActivityService.php`, `StoreActivityRequest.php` y `ActivityPolicy.php` que mapean tipos a estados, columnas, reglas y acciones.

4. **El sistema de plantillas JSON propuesto reduce significativamente la deuda técnica:**
   - Centraliza la definición de tipos de actividad.
   - Elimina la necesidad de modificar código PHP para agregar nuevos tipos.
   - Facilita la validación, conversión y autorización de forma declarativa.

### 7.2 Recomendaciones

1. **Implementar el sistema de plantillas JSON como prioridad:** Es la base para toda la extensibilidad futura del sistema.

2. **Migrar gradualmente:** No reemplazar todo el código de una vez. Usar feature flags y migrar módulo por módulo.

3. **Mantener backwards compatibility:** Durante la migración, mantener la compatibilidad con el sistema actual para no romper funcionalidades existentes.

4. **Invertir en pruebas:** El sistema actual tiene poca cobertura de pruebas. Antes de la migración, asegurar que el 80%+ del código tiene pruebas unitarias.

5. **Documentar las plantillas:** Cada plantilla JSON debe incluir comentarios explicando el propósito de cada propiedad y regla.

6. **Considerar un enfoque híbrido:** Para los tipos existentes (7), mantener el código actual como fallback mientras se migra gradualmente al sistema de plantillas.

### 7.3 Impacto Esperado

| Métrica | Antes | Después (estimado) |
|---|---|---|
| Tiempo para agregar un nuevo tipo de actividad | 2-3 días (modificar múltiples archivos) | 1-2 horas (crear plantilla JSON) |
| Líneas de código condicional hardcodeado | ~400+ líneas | ~50 líneas (motor genérico) |
| Cobertura de pruebas | <20% | >80% (objetivo) |
| Complejidad ciclomática promedio | 12-15 | 5-8 |
| Riesgo de breaking change al agregar tipo | Alto | Bajo |

### 7.4 Próximos Pasos Inmediatos

1. **Aprobar el diseño de plantillas JSON** con el equipo de desarrollo.
2. **Crear el repositorio de plantillas** (`config/activity_templates/`) con los 7 tipos existentes.
3. **Implementar `TemplateLoader`** con caché en memoria.
4. **Agregar campos a `activities`**: `converted_from_id` (foreign key), `original_metadata` (JSON).
5. **Escribir pruebas unitarias** para el sistema actual antes de comenzar la migración.

---

*Documento generado el 29 de junio de 2026. Revisar y actualizar trimestralmente o cuando se agreguen nuevos tipos de actividad.*

### Tareas Pendientes (Roadmap)
- **Conversión Bulk de Actividades:** (Sugerencia de usuario) Implementar una acción de conversión en lote (`bulk`) desde el listado unificado de actividades. Esto requerirá adaptar `ConvertActivityAction` para manejar colecciones y colas en background (jobs) dado el peso computacional de clonar relaciones y metadatos en múltiples registros a la vez, además del UI para la selección del "tipo de destino común".
