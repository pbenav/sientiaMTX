# 📂 Gestión de Expedientes — SientiaMTX (v1.2.0)

En organizaciones, despachos y equipos de alto rendimiento, el trabajo rara vez ocurre de manera aislada. Los **Expedientes** en SientiaMTX actúan como carpetas maestras y contenedores unificados de gestión documental y operativa, centralizando todas las actividades, citas previas, notas y adjuntos relacionados con un mismo caso o proyecto.

---

## 🏛️ 1. Concepto y Anatomía de un Expediente

Un expediente reúne bajo un mismo paraguas todo el ciclo de vida de un procedimiento:

```
┌─────────────────────────────────────────────────────────────┐
│                   EXPEDIENTE (EXP-2026-0014)                │
├─────────────────────────────────────────────────────────────┤
│ ├── Tareas y Actividades vinculadas                         │
│ ├── Documentos (OnlyOffice) e Imágenes (Filerobot)          │
│ ├── Citas Previas asociadas con ciudadanos/clientes         │
│ ├── Notas internas y bitácora de seguimiento                │
│ ├── Enlaces a otros Expedientes Relacionados                │
│ └── Registro de Miembros con Acceso (Deep Privacy)          │
└─────────────────────────────────────────────────────────────┘
```

### Código Oficial Único (`EXP-YYYY-NNNN`)
Cada expediente recibe un identificador secuencial oficial generado automáticamente por el sistema con formato:
$$\text{EXP} - \text{AÑO} - \text{NÚMERO}$$
*(Por ejemplo: `EXP-2026-0001`, `EXP-2026-0002`)*. Este código es inmutable, garantiza la trazabilidad administrativa e impide duplicidades.

---

## 🔒 2. Privacidad Profunda (*Deep Privacy*)

Uno de los pilares de seguridad más estrictos de SientiaMTX es su arquitectura de **Privacidad Profunda**:

### Expedientes Públicos
- Visibles y accesibles para todos los miembros integrantes del equipo de trabajo.
- Ideales para proyectos transversales, procedimientos abiertos y documentación general.

### Expedientes Privados (*Strict Deep Privacy*)
- Diseñados para asuntos confidenciales (recursos humanos, auditorías legales, quejas, casos médicos o secretos comerciales).
- **Regla de Aislamiento Estricto**: **Solo el creador, el responsable y los miembros/grupos asignados explícitamente pueden ver o buscar el expediente**.
- **Jerarquía Administrativa Blindada**: Ni los Propietarios de Equipo (*Team Owners*), ni los Coordinadores, ni los Administradores Globales pueden acceder ni listar expedientes privados salvo que hayan sido invitados o asignados expresamente.
- **Trazabilidad de Acceso**: La ficha del expediente lista con total transparencia a cada colaborador con su motivo de acceso correspondiente:
  - `Creador`: Usuario que dio de alta el expediente.
  - `Responsable`: Encargado principal de la tramitación.
  - `Asignado`: Miembro con asignación directa.
  - `Grupo`: Miembro con acceso derivado de un grupo de trabajo asignado.
  - `Tarea`: Miembro asignado a una tarea interna vinculada al expediente.

---

## 🛠️ 3. Barra de Control y Búsqueda Inteligente

La vista principal de expedientes cuenta con una barra de herramientas unificada y reactiva compartida con el diseño de Foros y Actividades:

- **Buscador Reactivo**: Búsqueda instantánea por código oficial (`EXP-...`), palabras clave en el título o términos de la memoria descriptiva.
- **Filtros por Estado**:
  - `Borrador / Iniciado`: Fase preliminar de recopilación de antecedentes.
  - `En Tramitación`: Trabajo activo en curso.
  - `En Pausa / Espera`: A la espera de documentación externa o resolución judicial/administrativa.
  - `Resuelto / Cerrado`: Procedimiento concluido satisfactoriamente.
  - `Archivado`: Histórico inactivo conservado para auditoría.
- **Clasificación por Prioridad**: Niveles de atención Urgente, Alta, Media y Baja.
- **Pantalla de Vacío Coherente**: Ilustraciones y llamadas a la acción claras cuando no existen registros coincidentes con los filtros aplicados.

---

## 🔗 4. Relaciones y Trazabilidad entre Expedientes

Los expedientes pueden vincularse entre sí de forma bidireccional (`relatedExpedientes`):
- Si el *Expediente A* está relacionado con el *Expediente B*, la ficha de ambos mostrará automáticamente el enlace directo al otro.
- Esta capacidad permite interconectar expedientes matriz con subexpedientes derivados o casos con repercusión jurídica cruzada.

---

## 📆 5. Integración con Cita Previa

Cuando un ciudadano o cliente agenda una cita en el portal público o es atendido en despacho:
- La cita puede asociarse directamente a un expediente existente desde la ficha de atención.
- Los profesionales pueden consultar el historial completo de citas atendidas en el contexto del expediente, midiendo tiempos acumulados y notas de cada sesión presencial o por videoconferencia.

