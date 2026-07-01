# Propuesta de Evolución: De Tareas a Actividades

**Proyecto:** sientiaMTX — Sistema de Gestión de Información  
**Fecha:** 26 de junio de 2026  
**Estado:** Propuesta para debate del equipo de desarrollo  
**Alcance:** Flujos de trabajo, experiencias del usuario y evolución de las interacciones  

---

## 1. La Evolución del Sistema

### 1.1 ¿Qué es sientiaMTX hoy?

sientiaMTX nació como un **gestor de tareas**. Su corazón es la tarea: tiene un estado (pendiente, en progreso, completada, cancelada, bloqueada), una fecha de vencimiento, un responsable, adjuntos, notas, historial, etiquetas, valoraciones.

Sobre ese corazón se constrieron capas de funcionalidad:

- **Expedientes** como contenedores de tareas agrupadas
- **Plantillas → Instancias** como sistema de planificación jerárquica
- **Kanban** para flujo visual de trabajo
- **Matriz de Eisenhower** para priorización urgente/importante
- **Adjuntos polimórficos** (archivos locales + Google Drive)
- **Seguimiento de tiempo**, **gamificación**, **sincronización con Google Calendar**
- **Foros** por tarea para discusión
- **Auto-programación**: tareas que se generan solas (diarias, semanales, mensuales)

### 1.2 ¿Dónde nos quedamos?

El sistema creció más allá de lo que diseñamos. Los usuarios no solo gestionan tareas: gestionan **documentos, decisiones, reuniones, enlaces, notas, acuerdos**. Pero el sistema solo entiende "tareas".

El Expediente es un contenedor, pero solo de tareas. No puedes adjuntar un documento, una decisión o una reunión directamente a un expediente. Tienes que crear una tarea "ficticia" para poder adjuntar cosas, lo cual ensucia la visión y confunde.

### 1.3 ¿Hacia dónde vamos?

Una entidad única, flexible y universal: **Actividad**. Que pueda ser una tarea, un documento, una decisión, una reunión, una nota, un enlace, un recordatorio... o cualquier cosa que el usuario necesite gestionar. Que los expedientes puedan contener **cualquier tipo de actividad**, no solo tareas. Que la jerarquía (padre/hijo) funcione para cualquier combinación de tipos.

---

## 2. Flujos de Trabajo Actuales

Esta sección describe cómo funcionan las cosas **hoy**. Es el punto de partida para entender qué cambia.

### 2.1 Flujo: Creación y Gestión de una Tarea

```
Usuario crea tarea → La completa → La sigue → La termina
```

**Escenario típico:**

1. El usuario accede al listado de tareas del equipo. Ve las tareas visibles para él (las que creó, las asignadas a él, las públicas sin asignar). Las completadas y canceladas están ocultas por defecto.
2. Pulsa "Crear tarea", rellena título, descripción, prioridad, fecha de vencimiento, asignación, etiquetas.
3. La tarea aparece en el listado. El usuario puede:
   - **Arrastrarla en el Kanban** de una columna a otra (cambia estado y progreso)
   - **Arrastrarla en la Matriz de Eisenhower** de un cuadrante a otro (cambia prioridad y urgencia)
   - **Asignarla** a otro usuario
   - **Marcarla como completada** (progreso 100%)
   - **Bloquearla** (se notifica al creador y coordinadores)
   - **Enviarle un "nudge"** (recordatorio) al asignado
   - **Valorarla** (1-5 estrellas)
   - **Adjuntar archivos** (se verifican cuotas)
   - **Dejar notas privadas** (solo visibles para el creador de la nota)
   - **Fusionarla** con otra tarea (concatena descripciones, migra adjuntos, notas, historial, tags)
4. Al completarla: se otorgan puntos de gamificación, se notifica al creador y coordinadores (según la visibilidad), y se oculta del listado principal.

**Comportamientos especiales:**

- **Plantillas (Plan Maestro):** Una tarea marcada como plantilla no se ejecuta directamente. Genera **instancias** (hijos) para cada usuario asignado. Cada instancia tiene su propio estado.
- **Auto-programación:** Una tarea puede configurarse para generarse automáticamente (diaria, semanal, mensual). Se crean "ocurrencias" (hijos) según la frecuencia.
- **Cascada de completado:** Si completas una tarea padre, se completan automáticamente todos sus hijos no completados.
- **Timeline locked:** Si una tarea está bloqueada en el timeline, no se puede reprogramar.

### 2.2 Flujo: Los Expedientes

```
Usuario crea expediente → Asigna responsables → Vincula tareas → Sigue el progreso
```

**Escenario típico:**

1. El usuario crea un expediente con un código único automático (`EXP-2026-0001`).
2. Asigna usuarios y/o grupos al expediente. La privacidad es estricta: en expedientes privados, incluso coordinadores y admins NO tienen acceso automático. Necesitan asignación explícita.
3. Vincula tareas al expediente (asignando su `expediente_id`). Las instancias (hijas de plantillas) heredan el expediente del padre y no se pueden desvincular individualmente.
4. El expediente muestra un resumen: tareas raíz, tareas hijas, adjuntos, notas, expedientes relacionados.

**Comportamientos especiales:**

- **Relaciones bidireccionales:** Los expedientes pueden vincularse entre sí. La relación se refleja en ambos lados.
- **Eliminación en cascada:** Solo el owner puede eliminar físicamente todo el contenido de un expediente.
- **Vista de detalle:** Muestra tareas raíz, tareas hijas, adjuntos, notas del expediente, expedientes relacionados.

### 2.3 Flujo: Kanban

```
[Por hacer] → [En progreso] → [Hecho]
    0%              1-99%         100%
```

**Escenario típico:**

1. El usuario accede al Kanban del equipo. Ve solo tareas "hoja" (sin hijos) que no sean plantillas.
2. Las columnas se sincronizan automáticamente según el progreso: 0% = por hacer, 1-99% = en progreso, 100% = hecho.
3. El usuario arrastra tarjetas entre columnas. Esto actualiza estado y progreso.
4. Si completa una tarea al 100%: se marca como `completed`, se otorgan puntos de gamificación, se notifican al creador y coordinadores.
5. Si mueve una tarea entre cuadrantes de la Matriz: se actualiza prioridad y urgencia automáticamente.

**Limitación actual:** El Kanban solo muestra tareas. No puede mostrar documentos, decisiones, reuniones, etc.

### 2.4 Flujo: Matriz de Eisenhower

```
┌─────────────────┬─────────────────┐
│ Q1: Hacer       │ Q2: Planificar  │
│ Urgente +       │ No urgente +    │
│ Importante      │ Importante      │
├─────────────────┼─────────────────┤
│ Q3: Delegar     │ Q4: Eliminar    │
│ Urgente +       │ No urgente +    │
│ No importante   │ No importante   │
└─────────────────┴─────────────────┘
```

**Escenario típico:**

1. El usuario accede a la Matriz. Ve las tareas organizadas en 4 cuadrantes según su prioridad y urgencia.
2. El cuadrante se calcula en tiempo real. No se persiste en la base de datos.
3. El usuario arrastra tareas entre cuadrantes. Esto actualiza prioridad y urgencia automáticamente.
4. Al arrastrar, la tarea se reordena en el Kanban también.

**Limitación actual:** Solo organiza tareas. No puede organizar documentos, decisiones, reuniones, etc.

### 2.5 Flujo: Plantillas e Instancias

```
PLANTILLA (Plan Maestro)
       │
       │  Al generar ocurrencia (automática o manual)
       ▼
   OCurrencia (hija de plantilla)
       │
       │  Genera instancias para cada usuario asignado
       ▼
   INSTANCIAS (hijas de la ocurrencia)
   Cada una con su propio estado y asignado
```

**Escenario típico:**

1. El coordinador crea una tarea marcada como plantilla (Plan Maestro). Define la estructura: título, descripción, asignaciones, fechas.
2. Según la configuración de auto-programación, se generan ocurrencias (hijas) con fechas calculadas. O el usuario genera manualmente.
3. Para cada ocurrencia, se crea una instancia individual para cada usuario asignado. Cada instancia tiene su propio estado, fecha y progreso.
4. Cada usuario trabaja sus instancias de forma independiente.
5. Cambios en la plantilla se pueden empujar a todas las instancias. Completar una instancia no afecta a otras.

### 2.6 Flujo: Adjuntos

```
Subir archivo → Se almacena → Se registra en auditoría → Se accede → Se gestiona
```

**Escenario típico:**

1. El usuario sube uno o varios archivos a una tarea o expediente. Se verifica la cuota del usuario y del equipo.
2. Los archivos se guardan con prefijo de fecha (`YYYY-MM-`) en carpetas `task_{id}` o `expediente_{id}`. Soporte para Google Drive.
3. Cada acción (subir, descargar, ver, eliminar, renombrar) se registra con IP, timestamp y metadata.
4. Solo los usuarios con permiso pueden descargar o ver los archivos.

### 2.7 Flujo: Operaciones Masivas

```
Seleccionar tareas → Elegir acción → Ejecutar
```

**Acciones disponibles:**

- **Actualizar en bloque:** Cambiar estado, prioridad o asignado de múltiples tareas a la vez.
- **Eliminar en bloque:** Eliminar múltiples tareas (soft delete).
- **Fusionar en bloque:** Múltiples tareas origen en una tarea destino. Concatena descripciones, migra adjuntos, notas, historial, tags.
- **Purgar papelera:** Solo coordinadores/admins pueden borrar permanentemente las tareas eliminadas.

### 2.8 Flujo: Notificaciones

| Evento | Notificación a | Canal |
|--------|---------------|-------|
| Tarea completada | Creador, coordinadores | Email, BD, Telegram, Web Push |
| Tarea bloqueada | Creador, coordinadores | Email, BD, Telegram, Web Push |
| Nudge (recordatorio) | Asignado (o creador si no hay asignado) | BD, Telegram, Web Push |
| Rating >= 4 | Creador (si quien valora no es el creador) | BD, Telegram, Web Push |

### 2.9 Flujo: Búsqueda

```
Usuario escribe en buscador → Se buscan tareas del mismo equipo cuyo título coincide → Se muestran hasta 25 resultados
```

- La búsqueda solo funciona sobre tareas.
- Los expedientes tienen su propia búsqueda separada.
- No hay búsqueda transversal entre tareas y expedientes.

---

## 3. Flujos de Trabajo Futuros: Con Actividades

Esta sección describe cómo funcionarán las cosas **después** de la evolución a Actividades. Cada flujo se presenta con un escenario realista de uso.

### 3.1 Flujo: Creación de Actividad (Multi-tipo)

```
Usuario pulsa "Crear" → Elige tipo → Rellena campos específicos → La completa → La sigue → La termina
```

**¿Qué cambia?** El usuario ya no solo "crea una tarea". Elige qué tipo de actividad quiere crear. El sistema presenta un formulario adaptado al tipo elegido.

**Escenario: Crear una tarea (flujo actual, idéntico)**

1. El usuario pulsa "Crear Actividad" → Selecciona "Tarea".
2. El formulario muestra los campos habituales: título, descripción, prioridad, fecha de vencimiento, asignación, etiquetas.
3. La tarea se comporta exactamente igual que antes. Kanban, Matriz, gamificación, notificaciones, todo funciona igual.

**Escenario: Crear un documento**

1. El usuario pulsa "Crear Actividad" → Selecciona "Documento".
2. El formulario muestra: título, archivo (drag & drop o selector), estado inicial (Borrador/En revisión/Aprobado/Archivado), revisores asignados.
3. El documento se adjunta automáticamente al expediente seleccionado.
4. Cuando un revisor lo aprueba, se notifica al creador y al expediente.
5. El documento aparece en la vista consolidada del expediente con su estado y una miniatura.

**Escenario: Crear una decisión**

1. El usuario pulsa "Crear Actividad" → Selecciona "Decisión".
2. El formulario muestra: título, rationale (justificación), participantes (quienes opinan y votan), estado inicial (Propuesta/En debate/Aceptada/Rechazada).
3. Los participantes dejan notas en el debate (públicas o privadas).
4. El coordinador cierra el debate y marca la decisión como Aceptada o Rechazada.
5. Se pueden generar tareas hijas directamente desde la decisión.
6. La decisión queda registrada como parte del expediente.

**Escenario: Crear una reunión**

1. El usuario pulsa "Crear Actividad" → Selecciona "Reunión".
2. El formulario muestra: título, fecha inicio, fecha fin, ubicación/enlace, agenda (lista de puntos), asistentes.
3. Durante la reunión, se toman notas (públicas o privadas) en tiempo real.
4. Después, se registran los acuerdos (que pueden convertirse en tareas hijas).
5. La reunión se marca como "Finalizada".
6. Se puede vincular al expediente correspondiente.

**Escenario: Crear una nota**

1. El usuario pulsa "Crear Actividad" → Selecciona "Nota".
2. El formulario muestra: título, contenido (editor Markdown), visibilidad (Pública/Privada), expediente vinculado.
3. La nota aparece en la vista consolidada del expediente.
4. Se pueden crear notas hijas para subtemas.
5. Se pueden crear tareas hijas para acciones derivadas.

**Escenario: Crear un enlace**

1. El usuario pulsa "Crear Actividad" → Selecciona "Enlace".
2. Introduce la URL. El sistema extrae automáticamente: título, imagen de vista previa, descripción (Open Graph).
3. Se vincula al expediente correspondiente.
4. Aparece en la vista consolidada con su imagen de vista previa.
5. Se puede marcar como procesado o archivado.

**Escenario: Crear un recordatorio**

1. El usuario pulsa "Crear Actividad" → Selecciona "Recordatorio".
2. Introduce: título, fecha/hora de disparo, repetición (diaria/semanal/mensual), canales de notificación, asignado.
3. Al llegar la fecha, se envían las notificaciones por todos los canales configurados.
4. Se puede generar automáticamente una tarea hija.
5. Se marca como "Disparado".

### 3.2 Flujo: Expediente con Actividades Mixtas

```
Expediente "Lanzamiento v2.0"
│
├── 📄 Documento: "Plan de Arquitectura.pdf" → Aprobado
│   └── 📝 Nota: "Comentario del equipo legal"
│
├── 📅 Reunión: "Kick-off con cliente" → Finalizada
│   ├── 📋 Tarea: "Juan prepara presupuesto"
│   ├── 📋 Tarea: "María define alcance"
│   └── 📋 Tarea: "Pedro configura repositorio"
│
├── 📋 Decisión: "Usar Laravel" → Aceptada
│   └── 📋 Tarea: "Actualizar dependencias"
│   └── 📋 Tarea: "Migrar sintaxis"
│   └── 📋 Tarea: "Actualizar tests"
│
├── 📋 Tarea: "Implementar autenticación" → En progreso (María, 60%)
│   └── 🔗 Enlace: "Documentación de OAuth2"
│
└── 📝 Nota: "Requisitos no funcionales" → Pública
```

**¿Qué cambia?** El expediente ya no es un contenedor de tareas. Es un contenedor de **actividades de cualquier tipo**. El usuario ve todo lo relacionado con el proyecto en un solo lugar: documentos aprobados, decisiones tomadas, reuniones planificadas, tareas en progreso, notas, enlaces.

**Vista consolidada del expediente (nueva):**

```
EXPEDIENTE: "Lanzamiento v2.0"
│
├── 📊 Resumen
│   ├── 15 Actividades totales
│   ├── 5 Tareas (2 en progreso, 3 completadas)
│   ├── 3 Documentos (1 en revisión, 2 aprobados)
│   ├── 2 Decisiones (1 aceptada, 1 en debate)
│   ├── 1 Reunión (planificada para mañana)
│   ├── 2 Notas (1 pública, 1 privada)
│   ├── 1 Enlace (procesado)
│   └── 1 Recordatorio (pendiente)
│
├── 📋 Actividades Recientes (timeline)
│   ├── [10:30] Decisión "Usar Laravel" → Aceptada
│   ├── [09:15] Documento "Especificación" → En revisión
│   ├── [08:45] Tarea "Configurar CI/CD" → En progreso
│   └── [Ayer] Reunión "Kick-off" → Finalizada
│
├── 📄 Documentos
│   ├── ✅ Arquitectura (Aprobado)
│   ├── 🔄 Especificación técnica (En revisión)
│   └── 📝 Plan de pruebas (Borrador)
│
├── 📋 Decisiones
│   ├── ✅ Usar Laravel 11
│   └── 🔄 Elegir base de datos (En debate)
│
├── 📅 Próximas Reuniones
│   └── "Revisión de sprint" — Mañana 10:00
│
├── ✅ Tareas en Progreso
│   ├── Configurar CI/CD (Juan, 40%)
│   └── Implementar autenticación (María, 60%)
│
└── 🔗 Expedientes Relacionados
    ├── "Migración de datos"
    └── "Testing de integración"
```

### 3.3 Flujo: Jerarquía Híbrida (Padre → Hijos de tipos distintos)

```
Expediente: "Lanzamiento v2.0"
│
├── Actividad: Decisión "Migrar a Laravel 11" (Aceptada)
│       └── Hijas (TODAS pueden ser de cualquier tipo):
│           ├── 📋 Tarea: "Actualizar dependencias"
│           ├── 📋 Tarea: "Migrar sintaxis"
│           └── 📋 Tarea: "Actualizar tests"
│
├── Actividad: Documento "Especificación técnica" (En revisión)
│       └── Hijas:
│           ├── 📝 Nota: "Comentario del equipo legal"
│           └── 📝 Nota: "Sugerencia de arquitectura"
│
├── Actividad: Reunión "Revisión de especificación" (Planificada)
│       ├── Hijas:
│           ├── 📋 Tarea: "Corregir sección 3"
│           └── 📋 Tarea: "Actualizar diagramas"
│
├── Actividad: Tarea "Configurar CI/CD" (En progreso)
│       └── Hijas:
│           ├── 📋 Tarea: "Configurar GitHub Actions"
│           └── 🔗 Enlace: "Documentación de GitHub Actions"
│
└── Actividad: Recordatorio "Revisión mensual"
        └── Trigger: 2026-08-01
```

**Escenarios de uso de jerarquía híbrida:**

| Escenario | Padre | Hijos | Beneficio |
|-----------|-------|-------|-----------|
| Decisión con acciones | Decisión | Tareas | La decisión genera trabajo concreto |
| Documento con comentarios | Documento | Notas | Comentarios estructurados sobre el documento |
| Reunión con acuerdos | Reunión | Tareas | Los acuerdos de la reunión se convierten en tareas |
| Tarea con referencia | Tarea | Enlace | La tarea referencia documentación externa |
| Recordatorio con contexto | Recordatorio | Nota | El recordatorio incluye contexto |
| Tarea con documentos | Tarea | Documentos | La tarea adjunta especificaciones |
| Nota con tareas | Nota | Tareas | Una idea se convierte en acciones |

### 3.4 Flujo: Kanban con Actividades

```
Kanban del Expediente "Proyecto X"
│
├── Columna: Por hacer
│       ├── 📄 Documento: "Especificación técnica" (estado: Borrador)
│       ├── 📋 Decisión: "Usar PostgreSQL" (estado: Propuesta)
│       ├── ✅ Tarea: "Configurar CI/CD" (estado: Pendiente)
│       └── 📝 Nota: "Requisitos no funcionales" (estado: Borrador)
│
├── Columna: En progreso
│       ├── 📄 Documento: "Plan de pruebas" (estado: En revisión)
│       ├── 📋 Decisión: "Usar JWT" (estado: En debate)
│       ├── ✅ Tarea: "Implementar autenticación" (estado: En progreso)
│       └── 📅 Reunión: "Kick-off" (estado: En curso)
│
└── Columna: Hecho
        ├── 📄 Documento: "Arquitectura" (estado: Aprobado)
        ├── 📋 Decisión: "Usar Laravel 11" (estado: Aceptada)
        ├── ✅ Tarea: "Configurar repositorio" (estado: Completada)
        └── 📅 Reunión: "Kick-off" (estado: Finalizada)
```

**¿Qué cambia?** El Kanban ya no solo muestra tareas. Muestra Actividades de cualquier tipo, cada una con su propio flujo de estados:

| Tipo | Columnas del Kanban |
|------|---------------------|
| Tarea | Por hacer → En progreso → Hecho |
| Documento | Borrador → En revisión → Aprobado → Archivado |
| Decisión | Propuesta → En debate → Aceptada / Rechazada |
| Reunión | Planificada → En curso → Finalizada |
| Nota | Borrador → Publicado → Archivado |
| Enlace | Guardado → Procesado → Archivado |
| Recordatorio | Pendiente → Disparado → Completado |

**Escenario típico:**

1. El usuario accede al Kanban del expediente. Ve todas las actividades del expediente organizadas en columnas.
2. Arrastra un documento de "Borrador" a "En revisión". El estado cambia y los revisores reciben una notificación.
3. Arrastra una decisión de "Propuesta" a "Aceptada". Se notifica a los participantes.
4. Arrastra una reunión de "Planificada" a "Finalizada". Se notifica a los asistentes.

### 3.5 Flujo: Matriz de Eisenhower con Actividades

```
Matriz de Eisenhower
│
├── Q1: Urgente + Importante (Hacer primero)
│       ├── ✅ Tarea: "Arreglar bug crítico en producción"
│       ├── 📋 Decisión: "Elegir proveedor de hosting" (vence mañana)
│       └── ✅ Tarea: "Deploy de hotfix"
│
├── Q2: Importante + No urgente (Planificar)
│       ├── 📄 Documento: "Especificación técnica" (plazo: 2 semanas)
│       ├── 📋 Decisión: "Elegir base de datos" (plazo: 1 mes)
│       └── ✅ Tarea: "Refactorizar módulo de pagos"
│
├── Q3: Urgente + No importante (Delegar)
│       ├── ✅ Tarea: "Responder emails del cliente"
│       └── 📅 Reunión: "Standup diario"
│
└── Q4: No urgente + No importante (Eliminar/Archivar)
        ├── 📝 Nota: "Ideas sin contexto"
        └── 🔗 Enlace: "Artículo leído pero no relevante"
```

**¿Qué cambia?** La Matriz puede organizar Actividades de tipo tarea y decisión (cualquier cosa con prioridad y urgencia). Documentos, reuniones, notas y enlaces también pueden tener prioridad y urgencia, por lo que también pueden organizarse en la Matriz.

### 3.6 Flujo: Operaciones Masivas Unificadas

```
Selección masiva en Expediente "Proyecto X"
│
├── Seleccionar: Todas las actividades de tipo "Documento"
│       └── Acción: Marcar como "Archivadas"
│
├── Seleccionar: Todas las actividades vencidas
│       └── Acción: Cambiar prioridad a "Crítica"
│
├── Seleccionar: Todas las tareas asignadas a Juan
│       └── Acción: Reasignar a María
│
└── Seleccionar: Todas las actividades del Expediente X
        └── Acción: Mover al Expediente Y
```

**¿Qué cambia?** Las operaciones masivas ya no solo funcionan sobre tareas. Pueden funcionar sobre Actividades de cualquier tipo.

### 3.7 Flujo: Búsqueda Unificada

```
BÚSQUEDA GLOBAL en Expediente "Proyecto X"
│
├── Título contiene "autenticación"
│       ├── ✅ Tarea: "Implementar autenticación OAuth2"
│       ├── ✅ Documento: "Especificación de autenticación"
│       ├── ✅ Nota: "Decisiones sobre autenticación"
│       └── ✅ Enlace: "Documentación de OAuth2"
│
├── Tipo = "Decisión"
│       ├── ✅ Decisión: "Usar JWT"
│       └── ✅ Decisión: "Usar Laravel 11"
│
├── Estado = "En revisión"
│       ├── ✅ Documento: "Especificación técnica"
│       └── ✅ Documento: "Plan de pruebas"
│
└── Fecha vence esta semana
        ├── ✅ Tarea: "Configurar CI/CD"
        ├── ✅ Reunión: "Revisión de sprint"
        └── ✅ Recordatorio: "Entrega de milestone 2"
```

**¿Qué cambia?** La búsqueda funciona sobre Actividades de todos los tipos, no solo tareas. Un solo campo de búsqueda encuentra tareas, documentos, decisiones, notas, enlaces, reuniones y recordatorios.

### 3.8 Flujo: Notificaciones Unificadas

| Tipo de Actividad | Evento | Notificación a |
|-------------------|--------|----------------|
| Tarea | Completada | Creador, coordinadores |
| Tarea | Bloqueada | Creador, coordinadores |
| Documento | Revisión completada | Autor del documento |
| Documento | Aprobado | Todos los del expediente |
| Decisión | Aceptada/Rechazada | Participantes |
| Reunión | Próxima (1h antes) | Asistentes |
| Reunión | Finalizada | Asistentes (con resumen) |
| Recordatorio | Trigger | Asignado |
| Nota | Publicada | Miembros del expediente |

**¿Qué cambia?** Las notificaciones ya no se generan solo desde tareas. Se generan desde cualquier Actividad, según su tipo y eventos específicos.

### 3.9 Flujo: Plantillas con Actividades Mixtas

```
PLANTILLA: "Onboarding de nuevo empleado" (Plan Maestro)
       │
       │  Al generar ocurrencia (automática o manual)
       ▼
   OCurrencia: "Onboarding - Juan - Julio 2026"
       │
       │  Genera instancias para cada usuario asignado
       ▼
   INSTANCIAS:
       ├── 📋 Tarea: "Configurar cuenta de email" (Juan)
       ├── 📋 Tarea: "Asignar equipo de trabajo" (RRHH)
       ├── 📄 Documento: "Manual de bienvenida" (Juan)
       ├── 📅 Reunión: "Presentación al equipo" (Juan, María, Pedro)
       ├── 📋 Decisión: "Definir stack tecnológico" (Juan)
       └── 📝 Nota: "Checklist de onboarding" (RRHH)
```

**¿Qué cambia?** Las plantillas pueden generar ocurrencias que contienen actividades de tipos distintos. Un proceso de onboarding no es solo tareas: incluye documentos, reuniones, decisiones y notas.

### 3.10 Flujo: Revisión de Documentos

```
1. Subir documento → 2. Marcar "En revisión" → 3. Asignar revisores
       │
       ▼
4. Revisores dejan notas sobre el documento
       │
       ▼
5. Autor corrige según las notas
       │
       ▼
6. Revisor aprueba o rechaza
       │
       ▼
7. Si aprobado → "Aprobado" + notifica al expediente
   Si rechazado → "Borrador" + notifica al autor
```

**¿Qué cambia?** Los documentos tienen su propio ciclo de vida con estados y flujos de aprobación. No son solo "tareas con un archivo adjunto".

### 3.11 Flujo: Decisiones con Debate

```
1. Coordinador crea decisión con título y rationale
       │
       ▼
2. Se asignan participantes (quienes pueden opinar y votar)
       │
       ▼
3. Participantes dejan notas en el debate
       │
       ▼
4. Coordinador cierra el debate y marca la decisión
       │
       ▼
5. Se generan tareas hijas directamente desde la decisión
       │
       ▼
6. La decisión queda registrada como parte del expediente
```

**¿Qué cambia?** Las decisiones tienen estructura propia: rationale, participantes, debate, estado. No se mencionan en notas o foros sin estructura.

### 3.12 Flujo: Reuniones con Acuerdos

```
1. Crear reunión con fecha, hora, ubicación y agenda
       │
       ▼
2. Asignar asistentes
       │
       ▼
3. Durante la reunión: tomar notas (públicas o privadas)
       │
       ▼
4. Después: registrar los acuerdos
       │
       ▼
5. Crear tareas hijas para los acuerdos
       │
       ▼
6. Marcar la reunión como "Finalizada"
       │
       ▼
7. Vincular al expediente correspondiente
```

**¿Qué cambia?** Las reuniones se gestionan dentro del sistema, no fuera. Tienen estructura (agenda, asistentes, notas, acuerdos) y generan trabajo concreto (tareas hijas).

### 3.13 Flujo: Enlaces con Metadata Automática

```
1. Pegar URL
       │
       ▼
2. Sistema extrae metadata automáticamente (título, imagen, descripción)
       │
       ▼
3. Vincular al expediente correspondiente
       │
       ▼
4. Aparece en la vista consolidada con imagen de vista previa
       │
       ▼
5. Marcar como procesado o archivado
```

**¿Qué cambia?** Los enlaces ya no se pegan en descripciones de tareas o notas. Son actividades con metadata automática y estado propio.

### 3.14 Flujo: Recordatorios con Trigger

```
1. Crear recordatorio con fecha/hora de disparo
       │
       ▼
2. Configurar canales de notificación (email, BD, Telegram, Web Push)
       │
       ▼
3. Configurar repetición (diaria, semanal, mensual)
       │
       ▼
4. Al llegar la fecha: enviar notificaciones
       │
       ▼
5. Generar automáticamente una tarea hija (opcional)
       │
       ▼
6. Marcar como "Disparado"
```

**¿Qué cambia?** Los recordatorios son actividades independientes, no solo auto-programación de tareas. Pueden configurarse con múltiples canales de notificación y trigger temporal.

---

## 4. Comparativa: Antes vs. Después

### 4.1 Vista de Expediente

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| Contenido | Solo tareas | Tareas, documentos, decisiones, reuniones, notas, enlaces, recordatorios |
| Vista consolidada | Tareas raíz, tareas hijas, adjuntos, notas, expedientes relacionados | Resumen por tipo, timeline de actividades recientes, secciones por tipo |
| Búsqueda | Solo sobre tareas | Sobre todas las actividades |
| Operaciones masivas | Solo sobre tareas | Sobre todas las actividades |
| Kanban | Solo tareas | Todas las actividades (cada una con su flujo) |
| Matriz | Solo tareas | Tareas, decisiones, documentos, reuniones, notas, enlaces |
| Notificaciones | Solo desde tareas | Desde todas las actividades |
| Plantillas | Solo tareas | Todas las actividades |

### 4.2 Flujo de Creación

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| Opciones | Solo "Crear tarea" | "Crear Actividad" → Elige tipo: Tarea, Documento, Decisión, Reunión, Nota, Enlace, Recordatorio |
| Formulario | Mismo para todo | Adaptado al tipo elegido |
| Campos obligatorios | Siempre los mismos (prioridad, fecha límite, etc.) | Solo los relevantes para el tipo |
| Adjuntos | Se suben como "adjuntos" | El documento ES el adjunto con metadatos |

### 4.3 Jerarquía

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| Padre → Hijos | Tarea → Tarea | Cualquier tipo → Cualquier tipo |
| Ejemplo | Tarea → Subtarea | Decisión → Tareas, Documento → Notas, Reunión → Tareas |
| Flexibilidad | Rígida | Total |

### 4.4 Casos de Uso Nuevos

| Caso de Uso | ANTES | DESPUÉS |
|-------------|-------|---------|
| Gestionar contratos | Tarea "Firmar contrato" | Documento "Contrato X" con estado |
| Registrar decisiones | Nota o foro sin estructura | Decisión con rationale y participantes |
| Planificar reuniones | Tarea "Reunión X" | Reunión con agenda, asistentes, notas, acuerdos |
| Organizar recursos externos | Pegar URL en descripción | Enlace con metadata automática |
| Tomar notas estructuradas | QuickNote (widget suelto) | Nota vinculada a expediente |
| Recordatorios independientes | Auto-programación de tareas | Recordatorio con trigger y canales |

---

## 5. Compatibilidad: Lo que NO Cambia

### 5.1 Para el usuario actual de tareas

- **Crear tareas:** El flujo es idéntico. El usuario pulsa "Crear Actividad" → Selecciona "Tarea" → Rellena igual.
- **Kanban:** Las tareas siguen apareciendo en el Kanban igual que antes.
- **Matriz de Eisenhower:** Las tareas siguen apareciendo en la Matriz igual que antes.
- **Gamificación:** Completar tareas sigue otorgando puntos igual que antes.
- **Notificaciones de tareas:** Completado, bloqueo, nudge, rating: todo igual.
- **Plantillas e instancias:** Funcionan igual que antes.
- **Auto-programación:** Las tareas recurrentes se generan igual que antes.
- **Adjuntos:** Los archivos adjuntos a tareas funcionan igual que antes.
- **Fusiones:** Fusionar tareas funciona igual que antes.
- **Operaciones masivas:** Actualizar, eliminar, fusionar en bloque funciona igual que antes.
- **Búsqueda de tareas:** Buscar por título funciona igual que antes.
- **Vinculación a expedientes:** Las tareas siguen vinculándose a expedientes igual que antes.

**En resumen:** La experiencia del usuario con las tareas es **idéntica**. No nota ningún cambio.

### 5.2 Para el sistema

- **Task model:** Se convierte en un "wrapper" del Activity model. `Task::find(1)` sigue funcionando.
- **TaskController:** Rutea a través de Activity pero responde igual.
- **TaskObserver:** Sigue sincronizando con Google, citas, etc.
- **TaskPolicy:** Sigue funcionando igual.
- **TaskAttachmentController:** Sigue funcionando igual.
- **TaskBulkController:** Sigue funcionando igual.
- **TaskActionController:** Sigue funcionando igual.

---

## 6. Decisiones para Debate del Equipo

### Decisión 1: ¿Mantener el wrapper `Task` o eliminarlo?

| Opción | Descripción | Pros | Contras |
|--------|-------------|------|---------|
| **Wrapper (A)** | `Task` envuelve `Activity` (type='task') | Cero cambios visibles, rollback trivial, migración gradual | Ligero overhead de indirección |
| **Directo (B)** | Eliminar `Task`, todo va por `Activity` | Arquitectura limpia, sin indirección | Cambio masivo, alto riesgo, posible downtime |

**Posición del autor:** Opción A. Menor riesgo, rollback trivial, migración gradual. Mantener 6 meses mínimo.

### Decisión 2: ¿Cuántos tipos incluir en la primera versión?

| Opción | Tipos incluidos | Pros | Contras |
|--------|----------------|------|---------|
| **Todos (A)** | 9 tipos (task, document, note, link, decision, meeting, reminder, forum_thread, custom) | Funcionalidad completa desde el inicio | Más trabajo, más testing, más riesgo |
| **Core (B)** | Solo task, document, note | Funcionalidad esencial, menor riesgo | Los usuarios echan de menos decision, meeting, etc. |
| **Incremental (C)** | task + document primero, luego los demás por fases | Balance entre funcionalidad y riesgo | Complejidad de tener tipos parciales |

**Posición del autor:** Opción C. Task (ya existe), Document (el más solicitado), Note (el más simple). Luego Decision, Meeting, Reminder, Link, Forum_thread, Custom en fases posteriores.

### Decisión 3: ¿Cómo se crean las actividades hijas?

| Opción | Descripción | Pros | Contras |
|--------|-------------|------|---------|
| **Formulario dedicado (A)** | Cada tipo tiene su propio formulario de creación | UX clara, validación específica | Más formularios, más código |
| **Formulario genérico (B)** | Un solo formulario que se adapta al tipo | Menos código, más flexible | UX menos clara, validación condicional compleja |
| **Híbrido (C)** | Formulario genérico para tipos simples, dedicado para complejos | Balance entre flexibilidad y UX | Complejidad de mantener dos tipos de formulario |

**Posición del autor:** Opción C. Tarea, Documento, Decisión, Reunión: formularios dedicados. Nota, Enlace, Recordatorio: formulario genérico.

### Decisión 4: ¿Qué pasa con las plantillas?

| Opción | Descripción | Pros | Contras |
|--------|-------------|------|---------|
| **Plantillas de cualquier tipo (A)** | Una plantilla puede generar ocurrencias de cualquier tipo | Máxima flexibilidad | Complejidad de diseño |
| **Solo plantillas de tareas (B)** | Las plantillas siguen siendo solo de tareas | Simplicidad, compatibilidad total | No se pueden plantillas de reuniones, documentos, etc. |
| **Híbrido (C)** | Plantillas de tareas (legacy) + Plantillas de nuevos tipos | Balance entre compatibilidad y flexibilidad | Dos sistemas de plantillas |

**Posición del autor:** Opción C. Las plantillas de tareas existentes siguen funcionando. Se añaden plantillas para tipos nuevos (reunión, documento, decisión) por separado.

### Decisión 5: ¿Cuándo se activa la compatibilidad hacia atrás?

| Opción | Descripción | Pros | Contras |
|--------|-------------|------|---------|
| **Desde el día 1 (A)** | Las tareas existentes se migran inmediatamente a Activity | Los datos están en un solo lugar desde el inicio | Riesgo en la migración |
| **Después de la Fase 3 (B)** | Primero se crean los nuevos tipos, luego se migran las tareas | Menos riesgo, testing aislado | Doble capa de abstracción por más tiempo |
| **Después de la Fase 4 (C)** | Se integra todo primero, luego se migran las tareas | Testing completo de la integración antes de tocar datos | Complejidad de mantener dos sistemas |

**Posición del autor:** Opción B. Primero crear la infraestructura y los nuevos tipos, luego migrar las tareas. Así se aísla el riesgo de la migración de datos.

### Decisión 6: ¿Cómo se maneja el Kanban de los nuevos tipos?

| Opción | Descripción | Pros | Contras |
|--------|-------------|------|---------|
| **Columnas por estado (A)** | Cada tipo tiene sus propias columnas en el Kanban | Flujo natural para cada tipo | Kanban más complejo, más columnas |
| **Columnas genéricas (B)** | Todas las actividades comparten las mismas 3 columnas (Por hacer, En progreso, Hecho) | Kanban simple, familiar | No todos los tipos encajan en 3 estados |
| **Columnas dinámicas (C)** | Las columnas se generan automáticamente según los estados del tipo | Flexible y automático | Complejidad de diseño y UX |

**Posición del autor:** Opción C. Kanban dinámico: las columnas se generan automáticamente según los estados de cada tipo de actividad. El usuario puede personalizar las columnas si lo desea.

---

## 7. Cronograma de Implementación

### Fase 1: Cimientos (2 semanas)
- Crear infraestructura de Actividades (modelo, policies, servicios)
- Tablas polimórficas (activity_tags, activity_histories, activity_assignments, etc.)
- Migración de datos de Task → Activity (type='task')
- Wrapper Task → Activity
- Testing: todas las operaciones de Task siguen funcionando

### Fase 2: Tipos Core (4 semanas)
- Implementar tipo `document` (modelo, controlador, vistas, policies)
- Implementar tipo `note` (modelo, controlador, vistas, policies)
- Implementar tipo `decision` (modelo, controlador, vistas, policies)
- Implementar tipo `meeting` (modelo, controlador, vistas, policies)
- Testing: cada tipo funcional de forma independiente

### Fase 3: Integración (3 semanas)
- Actualizar Expediente para enlazar con Activity
- Actualizar UI para renderizar por tipo
- Actualizar búsqueda (unificada)
- Actualizar Kanban (dinámico)
- Actualizar Matriz (todos los tipos con prioridad/urgencia)
- Actualizar notificaciones (todos los tipos)
- Testing: integración completa

### Fase 4: Tipos Avanzados (3 semanas)
- Implementar tipo `link` (con scraper Open Graph)
- Implementar tipo `reminder` (con sistema de colas)
- Implementar tipo `forum_thread` (reutilizar infraestructura existente)
- Implementar tipo `custom` (tipos definidos por el usuario)
- Testing: tipos avanzados

### Fase 5: Limpieza (2 semanas)
- Deprecar modelo Task (wrapper delgado)
- Eliminar tablas legacy
- Optimizar queries polimórficas
- Benchmarking de rendimiento
- Documentación

**Total: 14 semanas** (3 meses y medio)

---

## 8. Evaluación de Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Migración de datos falla | Baja | Crítico | Backup + staging + validación por conteo |
| Kanban complejo para nuevos tipos | Media | Medio | Columnas dinámicas + personalización |
| UI confusa con tantos tipos | Media | Alto | Feature flags + rollout gradual + onboarding |
| Rendimiento degrade con polimorfismo | Media | Medio | Índices + columnas generadas + caching |
| Resistencia del equipo | Baja | Bajo | Demostraciones + feedback loops cortos |

---

## 9. Próximos Pasos Inmediatos

1. **Sesión de debate del equipo** — Revisar las 6 decisiones de la sección 8
2. **Definir cronograma** — Confirmar las 14 semanas y asignar responsables
3. **Configurar ambiente de staging** — Preparar entorno con datos anonimizados
4. **Establecer feature flags** — Bandera para rollout gradual
5. **Plan de comunicación** — Informar a usuarios sobre los cambios
6. **Demostración** — Mostrar ejemplos visuales de los nuevos flujos

---

## 10. Apéndice Técnico: Estabilización y Blindaje de Privacidad (v1.0.2+)

Con la finalización de la transición hacia el modelo unificado `Activity`, se han consolidado las siguientes normas arquitectónicas y capas de compatibilidad para garantizar la integridad funcional y la privacidad estricta en Sientia MTX:

### 10.1. Persistencia Híbrida en la Matriz de Eisenhower
Para compaginar la rigidez del modelo legacy `Task` con el esquema JSON flexible `metadata` de `Activity`, se establece el siguiente contrato de mutadores:
- **Prioridad (`priority`):** Mantiene su columna física nativa (`low`, `medium`, `high`, `critical`).
- **Urgencia (`urgency`):** Persiste dinámicamente en el almacenamiento JSON (`metadata->urgency`). Se ha inyectado una capa de accesores y mutadores (`getUrgencyAttribute` / `setUrgencyAttribute`) en `Activity.php` para que Eloquent intercepte asignaciones directas (`$task->urgency = 'high'`) durante los eventos de reordenación AJAX (`TaskActionController@move`).
- **Migración Inversa:** El comando `mtx:migrate-tasks-to-activities` preserva íntegramente la urgencia histórica encapsulándola en el array `metadata`.

### 10.2. Blindaje Estricto de Privacidad para Managers
Se deroga de forma definitiva el patrón permisivo `if ($isManager) { return $query; }` que vulneraba la privacidad de las tareas ajenas. Todos los controladores, servicios y modelos (`Task.php`, `Activity.php`, `ActivityService.php`) aplican el **Contrato de Privacidad Blindada de Sientia MTX**:
1. **Separación de Intereses (Gestión vs. Ejecución):** Un Manager o Coordinador tiene pleno acceso a las tareas públicas del equipo y a las plantillas/planes maestros estructurales (`is_template = true`), **siempre y cuando no posean visibilidad explícitamente PRIVADA**.
2. **Inviolabilidad de Actividades Privadas (`visibility = 'private'`):** Ningún usuario (con independencia de su rol administrativo en el equipo) podrá visualizar, puntuar o analizar con Inteligencia Artificial una actividad privada de la que no sea creador o asignado directo/grupal.
3. **Controladores Segurizados:** Se extienden estas comprobaciones de autorización explícita a los endpoints del Asistente de Inteligencia Artificial (`AiChatController.php`) y al sistema de puntuación y calidad (`TaskActionController.php@rate`).

---

*Documento generado como propuesta para debate del equipo de desarrollo. Todas las secciones están abiertas a discusión y modificación según el consenso del equipo.*
