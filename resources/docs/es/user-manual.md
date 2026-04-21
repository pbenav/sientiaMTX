# SientiaMTX - Manual de Usuario (v0.9.1Beta)

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

## 📑 3. Crear y Gestionar Tareas

### Crear una tarea

1. Dentro de un equipo, haz clic en el botón **"+ Nueva Tarea"**.
2. Rellena: título, descripción, prioridad y urgencia.
3. Elige la **visibilidad**:
   - **Pública**: La verá todo el equipo.
   - **Privada**: Solo visible para ti (el creador). El sistema igualmente te enviará alertas si es urgente.
4. Asigna una **fecha límite** para activar el sistema de recordatorios.
5. Guarda con **"Crear Tarea"**.

### Estados de una Tarea
- **Pendiente**: Aún sin comenzar.
- **En Progreso**: Alguien está trabajando en ella.
- **Completada**: Finalizada. Puede ocultarse del tablero principal.
- **Bloqueada**: Un colaborador ha marcado un impedimento. El coordinador recibe un aviso automático.

---

## 🤝 4. Tareas Colaborativas

Cuando un coordinador asigna una tarea pública a varios miembros, el sistema crea una **instancia individual** para cada persona. Esto permite:
- Seguimiento de progreso independiente por persona.
- Notificaciones separadas para cada asignado.
- Visión global para el coordinador desde el dashboard del equipo.

---

## 📊 5. Vistas Disponibles

### Tablero Eisenhower (Matriz)
Vista principal con los cuatro cuadrantes. Ideal para la revisión diaria de prioridades.

### Lista de Tareas
Vista tabular con filtros por estado, urgencia y responsable. Exportable.

### Diagrama de Gantt
Visualización temporal de tareas con fechas de inicio y fin. Muestra dependencias entre tareas.

### Tablero Kanban
Columnas personalizables (por defecto: Pendiente, En Progreso, Completada). Soporta **arrastrar y soltar**.

---

## 🔔 6. Notificaciones Telegram

Para recibir alertas en tu móvil:

1. Busca tu bot de empresa en Telegram y escribe `/start`.
2. Copia el **Chat ID** que te responde el bot.
3. En tu **Perfil → Configuración de Notificaciones**, pega ese ID y activa la opción **"Recibir avisos por Telegram"**.
4. Elige en cuántas horas de antelación quieres los recordatorios (por defecto: 24h).

Recibirás automáticamente:
- **Resumen matutino** con tus tareas del día y una cita motivacional.
- **Alerta urgente** cuando una tarea de Q1 está próxima a vencer.

---

## 🌓 7. Temas Visuales

Desde tu perfil puedes elegir entre tres modos visuales:
- ☀️ **Claro**: Limpio y profesional.
- 🌙 **Oscuro**: Para trabajar de noche o en entornos oscuros.
- ✨ **Sistema**: Se adapta automáticamente a la preferencia de tu dispositivo.

---

## ⌨️ 8. Atajos y Productividad

- **Arrastrar y Soltar** en Kanban: Mueve tareas entre columnas con el ratón.
- **Filtros rápidos**: En la lista de tareas, usa la barra superior para buscar por título, etiqueta o responsable.
- **Símbolo de Bloqueo** 🔒: Indica que una tarea es privada en la vista del equipo.
- **Indicador Q1** 🔴: Las tareas críticas se muestran con borde rojo en todas las vistas.
- **Scroll del Roadmap**: En tareas con muchos miembros, la lista es scrollable para mantener la usabilidad.

---

## 🛠️ 9. Monitorización de Servicios (Sentinel)

## 📡 Sentinel: Monitorización Colaborativa
SientiaMTX incluye un sistema de alerta temprana para servicios críticos (Telegram, Google, Portales Oficiales, etc.). 
- **Reportar Caída**: Cualquier miembro puede alertar sobre una interrupción de servicio.
- **Validación**: La comunidad confirma o desmiente la caída, evitando falsas alarmas.
- **Bono Centinela**: Reportar caídas validadas otorga bonus de Energía/XP por cuidar del equipo.

## 😊 Selector de Iconos
En todos los campos de texto y descripciones, ahora dispones de un selector rápido de iconos UTF-8 para categorizar visualmente tus tareas y proyectos.

### Estados de un Servicio
- 🟢 **Activo**: El servicio funciona con normalidad.
- 🟡 **Inestable**: Alguien ha reportado una incidencia, pero aún no ha sido confirmada por otros miembros.
- 🔴 **Caído**: El sistema ha verificado la caída tras múltiples reportes coincidentes.

### Cómo colaborar
1. Si detectas que una herramienta no funciona, haz clic en **"Reportar Caída"** en su tarjeta correspondiente.
2. Si un servicio está caído y ves que ya funciona, haz clic en **"Confirmar Recuperación"**.
3. **Bono Centinela**: Ser el primero en reportar una alerta que luego es verificada por el equipo otorga puntos de XP y Energía (ver sección de Gamificación).

### Dependencias en Tareas
Al crear o editar una tarea, puedes seleccionar un servicio del que dependa:
- Si el servicio asociado cae, la tarea mostrará automáticamente un **aviso de bloqueo técnico** en el Dashboard.
- Esto permite al equipo identificar rápidamente que el retraso en esa tarea se debe a una causa externa y técnica.

> [!IMPORTANT]
> Solo los coordinadores del equipo pueden añadir o eliminar nuevos servicios de la lista de monitorización.
