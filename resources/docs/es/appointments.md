# 📆 Cita Previa y Portal Ciudadano — SientiaMTX (v1.2.0)

El módulo de **Cita Previa** de SientiaMTX conecta de forma segura la agenda de tu equipo con los ciudadanos, clientes o usuarios externos. Proporciona un portal web de reservas autoservicio, control exhaustivo de aforos y una mesa operativa con cronómetro de atención en tiempo real.

---

## 🌐 1. El Portal Público de Reservas

Cada equipo o profesional dispone de un portal de citas accesible públicamente sin que los visitantes necesiten registrar una cuenta en la plataforma:

```
[Portal Ciudadano] ➔ 1. Elige Servicio ➔ 2. Modalidad ➔ 3. Fecha & Turno ➔ 4. Datos & DNI ➔ [Localizador Único]
```

### Características del Proceso de Reserva:
- **Diseño Responsive y Accesible**: Optimizado para dispositivos móviles, tablets y ordenadores de sobremesa.
- **Contador de Plazas en Tiempo Real**: Los botones de selección de hora muestran visualmente el número de plazas disponibles restantes en cada tramo (ej. `09:30 (3 disponibles)`).
- **Control Antifraude y Prevención de Duplicados**:
  - Normalización automática de DNI/NIE/Pasaporte y correo electrónico (eliminación de espacios, mayúsculas y caracteres ambiguos).
  - Si un usuario intenta reservar dos veces para el mismo servicio en fechas conflictivas, el sistema lo detecta y le ofrece consultar o gestionar su reserva existente mediante su localizador.
- **Localizador Único Simplificado**:
  - Al completar la reserva, el ciudadano recibe en pantalla y por email un localizador amigable (ej: `25C-B4A1`).
  - Con este código y su correo, el usuario puede consultar el estado de su cita o cancelarla de forma autónoma con la debida antelación.

---

## ⚙️ 2. Configuración de Servicios (`AppointmentService`)

Los coordinadores y administradores pueden dar de alta múltiples servicios con reglas independientes:

| Parámetro | Descripción |
|---|---|
| **Modalidades** | `Presencial` (en sede), `Google Meet` (con enlace dinámico), o `Sientia Meet` (Jitsi integrado sin cuentas externas). |
| **Duración Estándar** | Duración nominal de la atención en minutos (ej: 15, 30, 60 min). |
| **Capacidad por Franja (`max_per_slot`)** | Número máximo de citas simultáneas en un mismo tramo horario. |
| **Campos Personalizados** | Configura preguntas específicas para el ciudadano (texto libre, número de expediente, motivo, selectores desplegables). |
| **Precios y Visibilidad** | Definición de coste del servicio y opción de mostrar u ocultar la tarifa en el portal. |
| **Sincronización Cloud** | Integración bidireccional opcional con Google Calendar y Google Tasks. |
| **Cláusula RGPD / Privacidad** | Texto legal específico con formato Markdown que el visitante debe aceptar antes de confirmar la cita. |

---

## ⏰ 3. Gestión de Disponibilidad y Horarios

El motor de disponibilidad calcula dinámicamente los huecos libres cruzando múltiples capas:

1. **Horarios Habituales (`AppointmentSchedule`)**: Turnos de mañana y tarde configurables por día de la semana con tiempos de descanso (*buffers*).
2. **Bloqueos de Agenda (`AppointmentBlock`)**: Días festivos, vacaciones del personal o periodos de mantenimiento.
3. **Antelación Mínima**: Evita reservas de última hora fijando un margen mínimo en horas o días antes del evento.
4. **Control Extraordinario para Administradores**:
   - Capacidad de agendar citas en fechas u horas pasadas para registrar atenciones telefónicas o presenciales imprevistas.
   - Creación de huecos extraordinarios autorizados sin alterar el horario base recurrente del servicio.

---

## 🖥️ 4. Mesa Operativa de Atención

El panel interno de gestión ofrece a los operadores y profesionales un control ágil del flujo de ciudadanos:

- **Agrupación Visual por Tramos**: Las citas se muestran organizadas por franjas horarias y días, con un esquema de colores y contrastes reforzado para facilitar la lectura en recepciones o mostradores.
- **Botón "Atender Ahora"**:
  - Si un ciudadano llega antes o después de la hora fijada, pulsar **"Atender Ahora"** ajusta automáticamente el inicio de la cita a la hora real actual.
- **Cronómetro de Consulta en Vivo**:
  - Inicia el contador de tiempo de atención en tiempo real con precisión en segundos.
  - Registra el esfuerzo dedicado directamente en las estadísticas del usuario.
- **Vinculación con Expedientes y Actividades**:
  - Posibilidad de asociar la cita a un expediente existente (`EXP-...`) o crear una actividad de seguimiento con un solo clic.
- **Notas de Atención**: Registro de observaciones privadas por parte del profesional (`member_notes`).

---

## 📈 5. Analítica de Tiempos y Dashboard

En la cabecera del panel de Cita Previa, una tarjeta analítica calcula en tiempo real el comportamiento de las consultas durante los **últimos 30 días**:

- **Volumen Total del Periodo**: Citas confirmadas, completadas, canceladas y no comparecencias (*No Show*).
- **Métricas de Duración**:
  - **Duración Mínima**: Tiempo de la consulta más rápida.
  - **Duración Media**: Promedio aritmético de atención.
  - **Moda**: Duración más frecuente entre todas las citas finalizadas.
  - **Duración Máxima**: Tiempo de la sesión más prolongada.

Estas métricas permiten a los gestores ajustar el parámetro `duration_minutes` a la realidad operativa del servicio, reduciendo colas y tiempos de espera.

---

## 🛡️ 6. Protección de Datos y Derecho al Olvido (RGPD Art. 17)

SientiaMTX implementa herramientas estrictas para cumplir con el Reglamento General de Protección de Datos:

- **Registro Aislado de Visitantes (`AppointmentVisitor`)**: Los datos identificativos del ciudadano se gestionan de forma estructurada e independiente.
- **Anonimización Definitiva**: El sistema permite procesar solicitudes del Derecho al Olvido (Art. 17 RGPD), anonimizando irreversiblemente los datos de carácter personal del visitante (nombre, DNI, teléfono, email) mientras preserva los datos numéricos y estadísticos para auditorías de servicio público.

