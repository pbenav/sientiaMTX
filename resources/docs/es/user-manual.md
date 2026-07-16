# SientiaMTX - Manual de Usuario (v0.9.8.RC3)

SientiaMTX no es solo una lista de tareas. Es un **gestor de productividad inteligente** basado en la Matriz de Eisenhower que te ayuda a diferenciar lo urgente de lo importante para que nunca pierdas el foco en lo que realmente importa.

---

## 🔐 1. Acceso y Perfil

### Inicio de Sesión
Accede con tu correo electrónico y contraseña. Si tu administrador ha configurado Google, también puedes usar el botón **"Conectar Google"** para vincular tu cuenta y sincronizar calendarios.

### Configuración de tu Perfil
En el menú de usuario (esquina superior derecha) → **Perfil**:

- **Nombre y correo**: Datos de identificación en el equipo.
- **Contraseña**: Cámbiala periódicamente por seguridad.
- **Zona horaria**: Importante para que las fechas de las tareas sean correctas.
- **Idioma**: Cambia entre Español e Inglés; los manuales también cambiarán automaticamente.

### 🔐 Seguridad Avanzada (MFA)
Protege tu cuenta mediante Autenticación de Doble Factor (2FA):
1. Ve a tu Perfil → Configuración de Seguridad.
2. Activa el doble factor introduciendo tu contraseña actual.
3. Escanea el código QR que aparece en pantalla mediante Google Authenticator o Authy.

> [!NOTE]
> **Privacidad Reforzada:** En SientiaMTX, la generación del código QR para la activación del 2FA se realiza de forma 100% local en tu navegador, sin enviar secretos a APIs externas, garantizando una seguridad offline absoluta.


---

## 📋 2. La Matriz de Eisenhower

Todas tus tareas se clasifican automáticamente en cuatro cuadrantes:

| Cuadrante | Descripción | Acción Recomendada |
|---|---|---|
| **Q1 — Haz Ahora** | Urgente e Importante | Máxima prioridad inmediata |
| **Q2 — Planifica** | No Urgente pero Importante | Agenda un tiempo específico |
| **Q3 — Delega** | Urgente pero No Importante | Asigna a otro miembro |
| **Q4 — Elimina** | Ni Urgente ni Importante | Descártala o pospónla indefinidamente |

> [!TIP]
> Los mejores equipos trabajan principalmente en **Q2**. Si tu tablero está lleno de Q1, es señal de que se necesita más planificación estratégica.

---

## 📑 3. Expedientes y Tareas

### Expedientes y Privacidad Profunda (Deep Privacy)
Los **Expedientes** actúan como proyectos contenedores para agrupar tareas relacionadas.
- **Expedientes Públicos**: Disponibles para que cualquier miembro del equipo colabore.
- **Expedientes Privados (Deep Privacy)**: Un expediente privado es estrictamente confidencial. **Solo el creador y los miembros explícitamente asignados** pueden verlo. Gracias a la política de *Privacidad Profunda*, ni siquiera los Administradores o Coordinadores del equipo pueden husmear en expedientes o tareas privadas en los que no participan.

### Crear una Tarea

1. Dentro de un equipo o un Expediente, haz clic en el botón **"+ Nueva Tarea"**.
2. Rellena: título, descripción, prioridad y urgencia.
3. Elige la **visibilidad**:
   - **Pública**: La verá el resto del equipo.
   - **Privada**: Solo la verán el creador y los asignados. Mantendrá la Privacidad Profunda independientemente de la jerarquía administrativa.
4. Asigna una **fecha límite** para activar el sistema de recordatorios.

### Estados de una Tarea
- **Pendiente**: Aún sin comenzar.
- **En Progreso**: Alguien está trabajando en ella.
- **Completada**: Finalizada.
- **Bloqueada**: Se ha marcado un impedimento técnico o de otro tipo.

---

## 🏛️ 4. Foros de Discusión Anidados

El Foro de Equipo permite una comunicación estructurada y profunda:

- **Hilos y Temas**: Las discusiones se organizan por hilos para mantener el orden.
- **Citas y Referencias**: Responde a mensajes específicos citando el texto original para mantener el hilo de la conversación.
- **Previsualización en Tiempo Real**: Comprueba cómo quedará tu mensaje (Markdown e imágenes) antes de publicarlo.
- **Menciones**: Etiqueta a otros miembros (@usuario) para que reciban una notificación directa.
- **Archivos Adjuntos**: Sube imágenes y documentos directamente a la conversación.

---

## 📊 5. Vistas y Visualización

SientiaMTX soporta diferentes tipos de actividades (Tareas, Documentos, Acuerdos, Recordatorios, Reuniones), y cada vista está especializada en mostrar solo la información que es relevante para su propósito. A continuación se detalla cómo funciona cada vista:

### Lista de Actividades
Es la vista tradicional en formato de tabla.
- **Qué muestra**: Depende del filtro que tengas activo (por defecto, suele mostrar solo "Tareas"). Puedes cambiar el selector desplegable en la parte superior derecha para ver Documentos o Acuerdos.
- **Uso ideal**: Búsquedas rápidas, revisión del listado completo y filtrado específico.

### Tablero Eisenhower (Matriz)
Vista principal para la revisión diaria de prioridades. Se basa exclusivamente en la urgencia y la importancia, por lo que no requiere que las actividades tengan fechas.
- **Qué muestra**: Tareas y Acuerdos.
- **Qué NO muestra**: Documentos, Recordatorios ni Reuniones (estos elementos no se ajustan a la priorización de la matriz).

### Tablero Kanban
Gestión visual mediante columnas (Pendiente > En Progreso > Completado) con soporte para **Arrastrar y Soltar** (Drag & Drop). Representa el ciclo de vida del trabajo.
- **Qué muestra**: Tareas, Documentos y Acuerdos.
- **Qué NO muestra**: Recordatorios ni Reuniones (ya que no tienen un flujo de estados prolongado en el tiempo).

### Diagrama de Gantt (Roadmap)
Visualización temporal optimizada. Las etiquetas de las tareas son visibles incluso en barras cortas, permitiendo una lectura fluida del cronograma.
- **⚠️ REQUISITO OBLIGATORIO**: Para que cualquier actividad aparezca en el Gantt, **debe tener asignada una fecha de vencimiento (`due_date`)**. Si creas una tarea sin fecha, el sistema no podrá ubicarla en la línea de tiempo y **no se mostrará** en esta vista.
- **Qué muestra**: Todos los tipos de actividades (Tareas, Acuerdos, Documentos, Recordatorios, Reuniones), siempre que tengan fecha y no estén completadas.

### Red Activa (Active Network)
Un widget en tiempo real que muestra qué miembros del equipo están conectados, en qué ubicación están trabajando y si tienen alguna tarea activa en ese momento (indicador verde/rojo).

---

## 🤖 6. Ax.ia: Inteligencia Artificial Asistente

Ax.ia (potenciada por Gemini) está integrada en todo el flujo de trabajo:

- **Análisis de Tareas**: Pide a Ax.ia que resuma una tarea compleja o que cree subtareas a partir de una descripción.
- **Transcripción de Voz**: Graba una nota de voz en tus "Notas Rápidas" y deja que Ax.ia la transcriba a texto automáticamente.
- **Generación de Contenido**: Crea borradores profesionales o respuestas para el foro en segundos.

---

## 📝 7. Notas Rápidas (Post-its)

Captura ideas al vuelo sin salir de donde estés:
- **Notas Flotantes**: Arrástralas por la pantalla y minimízalas según necesites.
- **Notas de Voz**: Graba audios cortos y transcríbelos con Ax.ia.
- **Sincronización**: Tus notas te acompañan en todos tus dispositivos.

---

## 🔔 8. Notificaciones y Telegram

Vincula tu cuenta de Telegram para recibir:
- **Resumen matutino** personalizado.
- **Alertas de Q1** próximas a vencer.
- **Menciones en Foros** y avisos de nuevas tareas asignadas.

---

## 📡 9. Sentinel: Monitorización

SientiaMTX incluye un sistema de alerta temprana para servicios críticos:
- **Reportar Caída**: Informa al equipo si un servicio (ej. Google Drive) no funciona.
- **Validación Colectiva**: Otros miembros confirman la incidencia para generar una alerta global.
- **Bonus Centinela**: Recibe puntos de XP y Energía Vital por ayudar a monitorizar el ecosistema del equipo.

---

## 📆 10. Citas Previas y Servicios

El sistema permite ofrecer agendas públicas de reserva para atención a clientes o soporte:
- **Configuración de Disponibilidad**: Define tu horario de atención, descansos, antelación mínima y duración de las reuniones en la configuración de Citas Previas.
- **Portal Público de Reservas**: Cada miembro (o el equipo global) puede compartir un enlace público optimizado donde los externos pueden agendar reuniones disponibles.
- **Localizadores Únicos**: Al confirmarse la cita, el cliente recibe un localizador (ej. `25C-B4A1`) para seguimiento y eventual cancelación.
- **Gestión Integrada**: Las citas reservadas aparecen en tu panel de control, notificándote y bloqueando tu horario automáticamente para evitar solapamientos.

---
**Sientia MTX: Elevando la productividad mediante la IA y una experiencia de usuario excepcional.**
