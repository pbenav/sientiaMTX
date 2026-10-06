# 📖 SientiaMTX — Manual de Usuario (v1.2.0)

SientiaMTX es un **ecosistema de productividad de alto rendimiento** que fusiona la metodología de la Matriz de Eisenhower con Inteligencia Artificial avanzada, gestión documental colaborativa y herramientas operativas de equipo.

---

## 🔐 1. Acceso y Perfil

### Inicio de Sesión
Accede con tu correo electrónico y contraseña. Si tu organización ha habilitado Google Workspace, puedes iniciar sesión o vincular tu cuenta con un solo clic para sincronizar calendarios y tareas.

### Configuración del Perfil
Desde el menú de usuario (esquina superior derecha) ➔ **Perfil**:
- **Datos Personales**: Nombre completo y correo electrónico de trabajo.
- **Zona Horaria e Idioma**: Soporte completo para Español e Inglés con cambio dinámico de interfaz y documentación.
- **Integraciones de IA**: Configura tu clave de API personal de Google Gemini para Ax.ia si tu equipo no utiliza una clave centralizada.

### 🛡️ Autenticación Multifactor (2FA / MFA)
SientiaMTX protege las cuentas de usuario mediante doble factor de autenticación compatible con el Esquema Nacional de Seguridad (ENS):
1. Ve a **Perfil ➔ Configuración de Seguridad**.
2. Activa el doble factor introduciendo tu contraseña actual.
3. Elige entre verificación por **Código de Correo** o **App Autenticadora (TOTP)** (Google Authenticator, Authy, etc.).

> [!NOTE]
> **Privacidad y Generación Local:** La generación del código QR para la activación del TOTP se procesa al 100% en local en tu propio navegador, sin enviar secretos a servidores externos ni APIs de terceros.

---

## 📋 2. La Matriz de Eisenhower

El corazón metodológico de SientiaMTX prioriza el impacto real sobre la urgencia artificial:

| Cuadrante | Clasificación | Acción Recomendada | Enfoque |
|---|---|---|---|
| **Q1 — Haz Ahora** | Urgente e Importante | Resolver inmediatamente | Crisis, fechas límite inminentes |
| **Q2 — Planifica** | No Urgente pero Importante | Asignar tiempo en agenda | Estrategia, innovación, prevención |
| **Q3 — Delega** | Urgente pero No Importante | Delegar o automatizar | Interrupciones, trámites secundarios |
| **Q4 — Elimina** | Ni Urgente ni Importante | Descartar o posponer | Distracciones, tareas de nulo valor |

> [!TIP]
> Los equipos de alto rendimiento concentran más del 65% de su tiempo en **Q2**. Un tablero desbordado de Q1 suele evidenciar falta de planificación anticipada.

---

## ⚡ 3. Actividades y Gestión Operativa

A partir de la versión v1.2.0, el trabajo se estructura a través del modelo unificado de **Actividades (Ítems)**:

1. **Tareas (`task`)**: Tareas ejecutables con subtareas anidadas, fechas límite y registro de finalización (`completed_at`).
2. **Documentos (`document`)**: Archivos ofimáticos editables en vivo mediante **OnlyOffice** e imágenes retocables con el editor gráfico **Filerobot**.
3. **Notas (`note`)**: Apuntes rápidos con formato Markdown y soporte de dictado por voz con transcripción por Ax.ia.
4. **Enlaces (`link`)**: Tarjetas de recursos web con extracción automática de OpenGraph (`og:title`, `og:image`).
5. **Acuerdos (`agreement`)**: Decisiones colegiadas con registro de justificación, alternativas, nivel de impacto y firma digital interna de los miembros.
6. **Reuniones (`meeting`)**: Sesiones presenciales, salas instantáneas en **Sientia Meet (Jitsi)** o **Google Meet** con apertura automática de salas, agendas y actas de reunión.
7. **Recordatorios (`reminder`)**: Alarmas con cuenta atrás, interruptor de completado y avisos por Telegram, WhatsApp, Email y Push.

> [!NOTE]
> Para conocer a fondo cada uno de los 7 tipos y sus especificaciones técnicas, consulta la [Guía de Actividades y Flujo de Trabajo](activities.md).

---

## ⏱️ 4. Control de Tiempo y Cronómetro

1. **Cronómetro en Vivo**: Inicia el registro de tiempo en cualquier tarea haciendo clic en el icono del cronómetro. El contador mide horas, minutos y segundos exactos.
2. **Barra Superior Fija**: Un banner persistente te recuerda en todo momento qué actividad tienes activa, permitiéndote pausar o reanudar el cronómetro desde cualquier página.
3. **Widget de Contabilidad de Esfuerzo**: En tu panel de control puedes auditar las horas dedicadas por actividad y miembro, respetando siempre la privacidad de tareas confidenciales.

---

## 📂 5. Expedientes y Privacidad Profunda

Los **Expedientes** agrupan actividades, tareas, documentos y citas bajo un código oficial único `EXP-YYYY-NNNN`.

- **Expedientes Públicos**: Abiertos a todos los integrantes del equipo.
- **Expedientes Privados (*Strict Deep Privacy*)**: Totalmente confidenciales. Solo el creador y los miembros o grupos explícitamente asignados pueden acceder a ellos. Los administradores o coordinadores no pueden verlos a menos que formen parte de la asignación.

> [!NOTE]
> Consulta la [Guía de Gestión de Expedientes](expedientes.md) para más detalles sobre cómo vincular casos y gestionar permisos.

---

## 💬 6. Comunicación: Chat, Videollamadas y Foros

### Sientia Chat y Videollamadas
- **Mensajería Instantánea**: Chat directo entre colaboradores integrado en la barra lateral con diseño *Glassmorphism*.
- **Avisos de Llamada Inmersivos**: Las llamadas entrantes despliegan una ventana interactiva a pantalla completa con aviso acústico y avatar del emisor.
- **Sobres Bouncing (Alpine.store)**: Los mensajes no leídos se señalan mediante iconos dinámicos sobre los avatares de la Red Activa.
- **Borrado Seguro**: Limpieza completa de historial entre dos usuarios con confirmación de seguridad.
- **Videollamada en Un Clic**:
  - **Sientia Meet**: Salas de videoconferencia instantáneas seguras sobre Jitsi sin límites de tiempo.
  - **Google Meet Rápido**: Detección y saneamiento inteligente de códigos y enlaces de reunión.

### Foros de Discusión Anidados
- **Hilos Temáticos**: Conversaciones organizadas por temas para evitar la pérdida de contexto.
- **Citas y Menciones**: Responde citando fragmentos y menciona a otros usuarios (`@usuario`) para notificarles al instante.
- **Previsualización en Vivo**: Renderizado en tiempo real de Markdown e imágenes antes de enviar.

---

## 📊 7. Vistas del Espacio de Trabajo

- **Matriz de Eisenhower**: Priorización táctica diaria de Tareas y Acuerdos.
- **Tablero Kanban**: Columnas dinámicas arrastrables (*Drag & Drop*) con cálculo automático de porcentaje de progreso.
- **Diagrama de Gantt (Roadmap)**: Cronograma interactivo. Las actividades sin fecha límite se muestran con un **degradado difuminado y borde discontinuo**; arrastra su extremo derecho para fijar una fecha límite al instante.
- **Red Activa (Active Network)**: Mapa y panel de presencia en tiempo real con estados de los colaboradores: 🟢 Activo, 🔴 En Labor, 🟡 Ausente.

---

## 🤖 8. Ax.ia: Tu Copiloto de IA (Gemini)

Ax.ia (potenciada por los modelos **Gemini 2.0 Flash** y **Gemini 1.5 Pro**) está presente en todo el sistema:

- **Desglose de Objetivos**: Pide a Ax.ia que analice un objetivo complejo y lo transforme en subtareas viables.
- **Voz a Texto**: Dicta notas de audio desde tu móvil u ordenador; Ax.ia las transcribirá con precisión en segundos.
- **Transferencia de Contenidos**: Inyecta los resultados del chat de Ax.ia directamente en la descripción de una tarea, como nota de equipo o como un nuevo capítulo dentro de un documento ofimático.

---

## 📆 9. Cita Previa y Atención al Público

- **Portal Público de Reservas**: Comparte tu enlace de agenda para que los ciudadanos o clientes elijan servicio, modalidad y franja horaria.
- **Localizador Único**: Cada reserva genera un localizador (ej. `25C-B4A1`) con el que el visitante puede consultar o cancelar su cita.
- **Botón "Atender Ahora"**: Desplaza la cita a la hora actual en el momento de la llegada e inicia el cronómetro de consulta.

---

## 📡 10. Sentinel: Monitorización Colaborativa

- **Pulso de Servicios**: Consulta el estado de las herramientas críticas de tu equipo (🟢 Operativo, 🟡 Degradado, 🔴 Caído).
- **Reportar Caídas**: Si una herramienta falla, repórtalo para alertar al resto del equipo.
- **Bono Centinela**: Gana puntos de experiencia (XP) y restaura Energía Vital ayudando a monitorizar el ecosistema técnico.

---

**SientiaMTX: Elevando la productividad y la colaboración mediante la tecnología y el diseño.**
