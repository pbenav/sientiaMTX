# ⚡ Actividades y Flujo de Trabajo — SientiaMTX (v1.2.0)

En las primeras versiones de SientiaMTX, el trabajo giraba exclusivamente en torno a las "Tareas". A partir de la versión **v1.2.0**, SientiaMTX evoluciona hacia una **arquitectura universal de Actividades (Ítems)**. 

Una actividad no es simplemente un elemento por hacer: es un objeto polimórfico de trabajo colaborativo que modela con precisión cómo operan las organizaciones reales (documentación viva, toma de decisiones formalizada, reuniones integradas, recordatorios multicanal, enlaces compartidos y tareas operativas).

---

## 🧩 1. Los 7 Tipos de Actividades

Cada actividad en SientiaMTX pertenece a un tipo específico con su propia interfaz, ciclo de vida, metadatos y herramientas integradas:

```
                  ┌── Tarea (task)
                  ├── Documento (document)
                  ├── Nota (note)
Actividad (Item) ─┼── Enlace (link)
                  ├── Acuerdo / Decisión (agreement)
                  ├── Reunión (meeting)
                  └── Recordatorio (reminder)
```

---

### 1.1. Tarea (`task`)
El motor operativo clásico potenciado con Eisenhower.
- **Ciclo de vida**: `Pendiente` ➔ `En Progreso` ➔ `Completada` (o `Bloqueada`).
- **Priorización**: Clasificación obligatoria en Urgencia e Importancia para alimentar la Matriz de Eisenhower.
- **Subtareas**: Estructura jerárquica con subtareas anidadas e hilo de discusión dedicado.
- **Fechas**: Fecha de inicio, fecha límite (`due_date`) y registro automático del timestamp exacto de finalización (`completed_at`).
- **Seguimiento temporal**: Integración nativa con el cronómetro de tiempo real y registro de esfuerzo.

---

### 1.2. Documento (`document`)
Gestión documental colaborativa con capacidades ofimáticas completas dentro del navegador:
- **Suite Ofimática OnlyOffice**: Edición colaborativa simultánea de archivos `.docx`, `.xlsx` y `.pptx` alojados de forma segura en la infraestructura propia sin depender de nubes de terceros.
- **Editor Gráfico Filerobot**: Editor de imágenes avanzado con herramientas de recorte, rotación, filtros de color, anotaciones y marcas de agua. Integra control de capas con alto `z-index` para evitar solapamientos con modales.
- **Capítulos y Estructura**: Posibilidad de añadir capítulos estructurados de texto con autoría individual y marcas de tiempo, o inyectar secciones completas redactadas por **Ax.ia**.
- **Control de Versiones y Estados**: `draft`, `review`, `approved`, `rejected` y `archived`.

---

### 1.3. Nota (`note`)
Depósito de conocimiento, minutas rápidas e ideas:
- **Formato Rico**: Soporte nativo para Markdown estructurado y HTML seguro.
- **Notas Fijadas (`pinned`)**: Anclaje destacado en la parte superior del tablero o expediente para información crítica de referencia.
- **Notas de Voz con IA**: Grabación directa desde el micrófono con transcripción automática e instantánea al texto de la nota gracias al motor de voz de Ax.ia.

---

### 1.4. Enlace (`link`)
Repositorio inteligente de referencias y recursos web:
- **Metadatos Open Graph Automáticos**: Al introducir una URL externa, SientiaMTX consulta de forma asíncrona la página y extrae el título de la página (`og:title`), la descripción (`og:description`) y la imagen de cabecera (`og:image`), presentando una tarjeta visualmente atractiva.
- **Detección de Enlaces Rotos**: Monitorización de estado (`active`, `broken`, `archived`) para auditar la validez de los recursos compartidos.

---

### 1.5. Acuerdo y Decisión (`agreement`)
Registro formal de consensos, resoluciones de junta y directrices vinculantes para el equipo:
- **Campos Estructurados**:
  - **Motivo (`rationale`)**: Explicación de las razones técnicas o de negocio que motivaron el acuerdo.
  - **Alternativas consideradas**: Registro de opciones descartadas y pros/contras analizados.
  - **Nivel de Impacto**: Calificación de repercusión (`Bajo`, `Medio`, `Alto`).
- **Flujo de Decisión**: Estados `Propuesto` ➔ `Aprobado` ➔ `Rechazado` / `Pospuesto` / `Sustituido`.
- **Firma Interna de Miembros**: Registro de validación y consentimiento de cada uno de los responsables o miembros decisores.

---

### 1.6. Reunión (`meeting`)
Convocatoria y seguimiento de sesiones presenciales o telemáticas:
- **Modalidades Flexibles**:
  - **Presencial**: Ubicación física, sala o sede.
  - **Sientia Meet (Jitsi Integrado) 🎥**: Salas de videoconferencia instantáneas seguras sin límite de tiempo, sin instalación previa ni creación de cuentas externas.
  - **Google Meet Inteligente 🌐**: Integración con Google Calendar y asistente de enlace inteligente que detecta URLs completas, enlaces acortados o códigos simples de 10 caracteres (`abc-defg-hij`) y los expande automáticamente.
- **Orden del Día y Actas**: Registro del temario previo (`agenda`), asistentes confirmados y acta de acuerdos (`minutes`).
- **Cierre Automatizado**: SientiaMTX ejecuta de forma programada el comando `php artisan app:autocomplete-meetings`, marcando como completadas las reuniones cuya fecha y duración hayan finalizado con un margen de cortesía.

---

### 1.7. Recordatorio (`reminder`)
Avisos temporales y llamadas a la acción con notificación multicanal:
- **Disparo Multicanal**: Envío a través de Correo Electrónico, Bot de Telegram, WhatsApp o Notificación Web Push.
- **Temporización Inteligente**: Alerta configurada para un número determinado de minutos antes del vencimiento (`notify_before_minutes`) o a una hora fija (`notify_at_hour`).
- **Contador en Vivo y Toggle Switch**: Muestra un reloj visual de cuenta atrás. Incluye un interruptor interactivo (*Toggle Switch*) que permite marcar el recordatorio como atendido en un clic, deteniendo el contador y desactivando los disparos pendientes.
- **Motor de Disparo CLI**: Procesado en segundo plano con `php artisan reminders:trigger`.

---

## 📊 2. Vistas Especializadas del Trabajo

SientiaMTX adapta la información según el objetivo operativo del usuario:

| Vista | ¿Qué muestra? | Propósito | Características Clave |
|---|---|---|---|
| **Matriz Eisenhower** | Tareas y Acuerdos | Priorización ejecutiva diaria | 4 cuadrantes (Q1: Haz ya, Q2: Planifica, Q3: Delega, Q4: Elimina). |
| **Tablero Kanban** | Tareas, Documentos y Acuerdos | Gestión de flujo y avance ágil | Columnas arrastrables (*Drag & Drop*), animaciones FLIP, cálculo automático de progreso (0% a 100%). |
| **Gantt (Roadmap)** | Todos los tipos de actividades | Cronograma y visión estratégica | Actividades con fecha límite fija en barras sólidas; actividades abiertas representadas con **degradado difuminado y borde discontinuo**. Permite arrastrar el extremo derecho para fijar plazos. |
| **Tabla de Actividades** | Todos los tipos | Búsqueda, filtrado y auditoría masiva | Filtros por tipo, estado, responsable, etiqueta o expediente asociado. |
| **Red Activa (Active Network)** | Estado en tiempo real del equipo | Presencia y coordinación | Indicadores de conexión: 🟢 Activo, 🔴 En Labor (trabajando en una tarea), 🟡 Inactivo/Ausente. |

---

## ⏱️ 3. Contabilidad de Esfuerzo y Control Horario

Para asegurar una medición veraz de la carga de trabajo y cumplir con las normativas laborales de registro de jornada, SientiaMTX incorpora un sistema integral de **Time Tracking**:

```
[Iniciar Cronómetro] ──▶ [Banner Flotante Superior] ──▶ [Detener Cronómetro]
         │                                                      │
         ▼                                                      ▼
 Registra TimeLog (tarea/actividad)                     Calcula minutos & segundos
         │                                                      │
         └─────────────────▶ Widget de Esfuerzo ◀───────────────┘
```

### Funcionalidades de Registro:
1. **Cronómetro en Tiempo Real**: Visualización de horas, minutos y segundos transcurridos directamente en la cabecera de la actividad y en la barra superior global.
2. **Banner Global de Actividad**: Mientras un cronómetro está en marcha, se despliega una barra fija que informa de la tarea en curso y permite pausarla o cambiar de actividad sin perder el contexto.
3. **Widget de Contabilidad de Esfuerzo**:
   - Muestra el tiempo invertido desglosado por usuario y por actividad.
   - **Respeto estricto a la privacidad**: Si una actividad es privada, solo los usuarios autorizados pueden ver el título y el desglose en el widget.
   - Ordenación dinámica por el registro de tiempo más reciente del usuario.
4. **Detección de Jornadas Anómalas (`timelogs:mark-anomalous`)**:
   - Algoritmo que detecta automáticamente olvidos de fichaje o cronómetros abiertos más allá del horario laboral previsto o con un límite estricto (*hard-cap*) de **10 horas**.
   - Calcula el parámetro `effectiveMinutes` para evitar sesgos en las métricas de rendimiento y genera alertas para su corrección por parte del coordinador.

---

## 🤖 4. Integración con Ax.ia (IA)

Ax.ia interactúa directamente con el ciclo de vida de las actividades:

1. **Auto-creación Asistida**: A partir de un texto libre o de una conversación con Ax.ia, la IA puede desglosar el objetivo en actividades estructuradas (asignando título, descripción, prioridad y tipo).
2. **Transferencia de Contenido Bidireccional**: Los resultados obtenidos en el chat de Ax.ia pueden ser inyectados con un clic como:
   - Resumen o descripción de la actividad.
   - Nota interna de equipo en el hilo de trabajo.
   - Nuevo capítulo en un documento (`DocumentActivity`).
   - Nueva publicación en el foro del equipo.
