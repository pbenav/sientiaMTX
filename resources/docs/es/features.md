# Sientia MTX: Guía Maestra de Funcionalidades (v1.2.0)

SientiaMTX es un ecosistema de productividad de alto rendimiento que fusiona la metodología de Eisenhower con Inteligencia Artificial avanzada, gestión documental colaborativa y atención ciudadana.

---

## 🚀 1. Dashboard Operativo de Alto Impacto

El centro de mando donde converge el esfuerzo del equipo y el pulso de los servicios.

### 📊 La Matriz de Eisenhower
*   **Visualización Inteligente**: Clasificación automática de tareas en Q1 (Urgente/Importante), Q2 (Planificación), Q3 (Delegación) y Q4 (Eliminación).
*   **Foco en el Valor**: Diseño optimizado para reducir la fatiga visual y priorizar el trabajo que genera impacto real.

### 🛡️ Sentinel: Monitorización Colaborativa
*   **Pulso de Servicios**: Monitorización en tiempo real de herramientas críticas con indicadores visuales de estado (🟢, 🟡, 🔴).
*   **Bono Centinela**: Sistema de incentivos gamificado para los miembros que reportan y validan caídas de servicio.

### 🔋 Gestión de Energía (Flow)
*   **Energía Vital**: Cada usuario tiene un nivel de energía que se consume según la carga cognitiva de las tareas, previniendo el burnout mediante un sistema de "Fresh Start" matutino.

---

## 💬 2. Comunicación en Tiempo Real e IA

### 💬 Sientia Chat y Videoconferencia Integrada
*   **Chat Instantáneo**: Sistema de mensajería instantánea directa entre colaboradores integrado en la barra lateral con diseño ultra-moderno *Glassmorphism* y scrollbars optimizados.
*   **Limpieza de Historial 🧹**: Función avanzada de borrado seguro que elimina por completo el historial de conversación entre dos colaboradores con confirmación visual de SweetAlert2.
*   **Suites de Videollamada de Un Clic**:
    *   **Sientia Meet (Jitsi) 🎥**: Videoconferencias instantáneas ilimitadas sin necesidad de cuentas ni registros de ningún tipo, integradas al vuelo.
    *   **Google Meet Rápido 🌐**: Apertura de salas con **saneamiento inteligente de enlaces** (permite pegar URLs, enlaces acortados o simples códigos de 10 letras y los expande automáticamente).
*   **Alertas Inmersivas y Notificaciones Reactivas**:
    *   **Llamadas en Foco**: Las llamadas entrantes despliegan un SweetAlert interactivo centrado en toda la pantalla con el avatar del emisor, tono de aviso sintetizado por audio-frecuencia y destellos de pestaña.
    *   **Mensajes No Intrusivos**: Mensajes ordinarios notificados por Toasts interactivos con opción *"Haz clic para responder"*.
    *   **Bouncing Badges de Pendientes ✉️**: Un almacén de estado global (`Alpine.store`) sincroniza mensajes sin leer y coloca sobres rojos dinámicos botando en los avatares de la "Red Activa" hasta que abres el chat.

### 🏛️ Foros Anidados (Threads)
*   **Discusiones Profundas**: Soporte para hilos de conversación anidados, citas directas y menciones de usuarios.
*   **Previsualización Premium**: Sistema de previsualización antes de publicar con renderizado completo de Markdown e imágenes.

### 🤖 Ax.ia: Tu Copiloto de Productividad
*   **IA Everywhere**: Integrada en tareas, foros y notas rápidas con soporte para modelos Gemini 2.0 Flash y 1.5 Pro.
*   **Voz a Texto**: Transcripción automática de notas de voz en segundos.
*   **AI Content Transfer**: Inyección de resúmenes, actas y desgloses directamente en descripciones, notas internas, capítulos de documentos o foros.

---

## 📋 3. Modelo Universal de Actividades y Expedientes

### ⚡ Los 7 Tipos de Actividades
*   **Tareas (`task`)**: Ejecución operativa, subtareas jerárquicas y cálculo de fecha de finalización (`completed_at`).
*   **Documentos (`document`)**: Suite OnlyOffice integrada en vivo y editor de imágenes Filerobot.
*   **Notas (`note`)**: Apuntes rápidos con dictado por voz y fijado (`pinned`).
*   **Enlaces (`link`)**: Tarjetas de recursos web con extracción automática de OpenGraph.
*   **Acuerdos (`agreement`)**: Registro de decisiones de equipo con justificación, alternativas, nivel de impacto y firmas digitales internas.
*   **Reuniones (`meeting`)**: Sesiones presenciales, salas Jitsi o Google Meet con agendas y actas.
*   **Recordatorios (`reminder`)**: Notificaciones multicanal (Telegram, WhatsApp, Mail, Push) con cuenta atrás y toggle switch interactivo.

### 📂 Expedientes y Privacidad Profunda (Deep Privacy)
*   **Código Oficial**: Identificador único secuencial `EXP-YYYY-NNNN`.
*   **Aislamiento Estricto**: Los expedientes privados solo son visibles para su creador y los colaboradores expresamente asignados, quedando protegidos incluso frente a administradores no participantes.
*   **Relaciones Cruzadas**: Vinculación bidireccional entre expedientes relacionados.

---

## ⏱️ 4. Control de Tiempo y Jornadas

*   **Cronómetro en Tiempo Real**: Medición con precisión en segundos y banner flotante superior de actividad en curso.
*   **Widget de Contabilidad de Esfuerzo**: Auditoría del tiempo dedicado por usuario y actividad con filtrado estricto de tareas privadas.
*   **Detección de Jornadas Anómalas**: Normalización de olvidos de fichaje con límite estricto de 10 horas y cálculo de `effectiveMinutes` (`timelogs:mark-anomalous`).

---

## 📆 5. Cita Previa y Atención al Público

*   **Portal Público de Reservas**: Agendamiento autoservicio con selección de modalidad (presencial, videollamada) y selector de huecos con aforo en tiempo real.
*   **Localizador Único**: Código simplificado (ej: `25C-B4A1`) para consultar o cancelar la cita.
*   **Mesa Operativa**: Botón "Atender Ahora" para ajustar el inicio a la hora real, cronómetro de consulta y notas de atención.
*   **Analítica de Duración**: Cálculo de tiempos mínimo, medio, moda y máximo en los últimos 30 días.

---

## 🎮 6. Gamificación: El Skill Tree

*   **Evolución Profesional**: Las actividades alimentan un árbol de habilidades (Soporte, Desarrollo, Sistemas, etc.), permitiendo ver el crecimiento real de cada colaborador.
*   **Puntos de Resiliencia**: Reconocimiento especial para quienes aceptan retos fuera de su área de especialidad.

---

**Sientia MTX: Inteligencia Colectiva para Equipos de Alto Rendimiento.**
