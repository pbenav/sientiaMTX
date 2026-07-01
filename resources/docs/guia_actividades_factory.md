# 🚀 Guía Maestra: Motor Plug-and-Play de Actividades (Factory Pattern & Esquema v2)

Esta documentación define la nueva arquitectura de **SientiaMTX** para la creación, importación y exportación de Actividades Dinámicas.

## 🌟 Concepto Base
El sistema abandona el esquema monolítico de tareas (`sientia_task_v1`) y evoluciona a un modelo polimórfico universal (`sientia_activity_v2`). 

Cualquier entidad de trabajo (tarea, documento, reunión, decisión, enlace, encuesta, evento de streaming, ticket de soporte...) comparte el mismo núcleo de gestión (Core) pero mantiene de forma estricta su propia identidad y metadatos (Specs).

---

## 🏗️ La Arquitectura de 3 Pilares

### 1. El Esquema Universal (`sientia_activity_v2`)
El JSON separa de forma escrupulosa el motor común de las especificaciones concretas:
```json
{
  "$schema": "https://sientia.com/schemas/activity-v2.json",
  "version": "sientia_activity_v2",
  "exported_at": "2026-06-29T21:30:00Z",
  "core": {
    "type": "document",
    "title": "Arquitectura MTX v2",
    "description": "Definición del Factory Pattern",
    "priority": "high",
    "urgency": "medium",
    "visibility": "semiprivate",
    "status": { "value": "in_progress", "label": "En Progreso" },
    "tags": [{ "tag": "Arquitectura", "color_hex": "#6366f1" }],
    "skills": [{ "name": "Backend Development", "category": "Ingeniería" }]
  },
  "specs": {
    "file_path": "storage/docs/mtx_v2.pdf",
    "version": "2.0.0",
    "chapters": [
      { "title": "1. Introducción", "content": "..." }
    ]
  }
}
```

### 2. El Contrato: `ExportableActivityInterface`
Todos los subtipos ubicados en `app/Models/Activities/` implementan `App\Contracts\ExportableActivityInterface`.
Esto garantiza que cada modelo sea dueño absoluto de sus datos específicos mediante tres métodos:
* `getSpecsSchema()`: Define las reglas y tipos de datos esperados.
* `exportSpecs()`: Decide qué metadatos se empaquetan al exportar.
* `importSpecs(array $specs)`: Recibe e inyecta los datos de forma segura en el atributo JSON `metadata`.

### 3. La Factoría: `ActivityFactory`
Ubicada en `app/Factories/ActivityFactory.php`, actúa como orquestador central:
* Valida e importa esquemas `v2`.
* **Soporte Legacy Garantizado**: Si recibe un JSON antiguo `sientia_task_v1`, lo traduce automáticamente al vuelo sin generar errores ni pérdidas de información.
* Orquesta dentro de transacciones de base de datos (`DB::transaction`) la sincronización de etiquetas, especialidades, historial de auditoría y su posicionamiento en tableros Kanban y Matriz de Eisenhower.

---

## 🛠️ ¿Cómo añadir un nuevo tipo de actividad "insospechada" en 5 minutos?

Si necesitas crear un nuevo tipo de actividad (por ejemplo, `streaming_event` o `survey`), sigue estos 3 sencillos pasos:

### Paso 1: Crear el Modelo Subtipo
Crea el archivo `app/Models/Activities/StreamingEventActivity.php`:

```php
namespace App\Models\Activities;

use App\Models\Activity;
use App\Contracts\ExportableActivityInterface;

class StreamingEventActivity extends Activity implements ExportableActivityInterface
{
    protected static function booted(): void
    {
        static::creating(fn(self $m) => $m->type = 'streaming_event');
    }

    public static function getSpecsSchema(): array
    {
        return [
            'stream_url'  => 'string|url|nullable',
            'host_id'     => 'integer|nullable',
            'max_viewers' => 'integer',
        ];
    }

    public function exportSpecs(): array
    {
        $meta = $this->metadata ?? [];
        return [
            'stream_url'  => $meta['stream_url'] ?? null,
            'host_id'     => $meta['host_id'] ?? null,
            'max_viewers' => (int) ($meta['max_viewers'] ?? 100),
        ];
    }

    public function importSpecs(array $specs): void
    {
        $meta = $this->metadata ?? [];
        foreach (array_keys(self::getSpecsSchema()) as $key) {
            if (array_key_exists($key, $specs)) {
                $meta[$key] = $specs[$key];
            }
        }
        $this->metadata = $meta;
    }
}
```

### Paso 2: Registrar en el Mapa Polimórfico
En `app/Models/Activity.php`, añade tu modelo a la constante `SUBTYPES`:
```php
public const SUBTYPES = [
    // ...
    'streaming_event' => \App\Models\Activities\StreamingEventActivity::class,
];
```

### Paso 3: Crear su Vista Partial
Crea el archivo `resources/views/teams/activities/partials/streaming_event.blade.php` para renderizar tu interfaz personalizada en el frontend.

**¡Y LISTO!**
A partir de ese instante, el sistema es capaz de crear, clonar, importar, exportar, colocar en Kanban y Matriz Eisenhower tus eventos de streaming sin tocar un solo controlador. ¡Puro Plug-and-Play!
