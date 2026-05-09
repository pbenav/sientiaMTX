# Sistema de Gamificación y Energía Vital

Este documento detalla la lógica de cálculo para los puntos de experiencia (XP), resiliencia y la gestión de la Energía Vital de los miembros en la plataforma.

## 1. Conceptos Fundamentales

### Carga Cognitiva (Multiplier)
Cada tarea tiene asignada una **Carga Cognitiva** (del 1 al 5). Este valor actúa como el multiplicador base para casi todas las recompensas y consumos de energía.
- **Carga 1:** Tarea rutinaria / baja concentración.
- **Carga 5:** Tarea crítica / máxima concentración.

---

## 2. Cálculo de Experiencia (XP)

La XP base por completar una tarea se calcula de la siguiente manera:
`XP Base = 10 * Multiplicador`

### Bonus Adicionales:
1. **Backstage Bonus:** Si la tarea está marcada como "Backstage" (preparación), se añaden **+5 * Multiplicador** extra.
2. **Fuera de Skill Tree:** Si la tarea no pertenece a las especialidades principales del usuario, la XP base se reduce a **5 * Multiplicador**, pero se compensa con puntos de Resiliencia.

### Master Plan Race (Carrera de Velocidad):
Para incentivar la agilidad en tareas compartidas o derivadas de un Plan Maestro, se otorgan bonus por orden de finalización:
- **1º Lugar:** +15 XP
- **2º Lugar:** +10 XP
- **3º Lugar:** +5 XP

---

## 3. Puntos de Resiliencia (RP)

Los **Resilience Points (RP)** miden la capacidad del usuario para adaptarse a entornos desconocidos, superar desafíos técnicos y apoyar la cohesión del equipo a través del compañerismo.

A diferencia de la XP estándar, los RP se obtienen principalmente por dos vías específicas:

### A) Retos de Resiliencia (Fuera de Zona de Confort)
Cuando un usuario asume y completa una tarea que está marcada como **fuera de su árbol de habilidades**, el sistema lo premia por su versatilidad:
- **Recompensa:** `20 * Multiplicador` de Carga Cognitiva.
- **Objetivo:** Incentivar el aprendizaje transversal y evitar el estancamiento en tareas repetitivas.

### B) Reconocimiento de Compañeros (Kudos)
La resiliencia del equipo también se construye mediante el apoyo mutuo. Cada vez que otro miembro del equipo otorga un reconocimiento público o gratitud:
- **Acción:** Recibir un Kudo.
- **Recompensa:** `+5 RP` directos.
- **Objetivo:** Fomentar una cultura corporativa de agradecimiento y visibilidad del esfuerzo conjunto.

---

## 4. Gestión de Energía Vital (Flow)
La energía es un recurso dinámico que oscila entre 0 y 100. El cálculo se ha rediseñado para evitar el agotamiento asfixiante y premiar la "finalización del círculo".

### El Balance Neto:
En lugar de basarse en el tiempo real (que castiga olvidos del cronómetro), el sistema utiliza la Carga Cognitiva para predecir el desgaste:

1. **Drenaje por Esfuerzo:** `Carga Cognitiva * 2` (Energía que se consume al trabajar).
2. **Recompensa por Cierre:** `+5` puntos fijos al marcar como completada.
3. **Bonus por Alta Carga:** Si la Carga Cognitiva es > 3, se añaden `+2` puntos extra de recompensa.

**Ejemplo de flujo (Carga 5):**
- Desgaste: `-10` (5 * 2)
- Recompensa: `+7` (5 + 2)
- **Resultado:** Pérdida neta de `-3` de energía.

---

## 5. Progresión de Habilidades (Leveling)
Cada tarea otorga XP a las especialidades (Skills) asociadas. El sistema de niveles para cada habilidad sigue esta progresión:

| Nivel | XP Acumulada Necesaria |
| :--- | :--- |
| **Nivel 1** | 0 XP |
| **Nivel 2** | 30 XP |
| **Nivel 3** | 100 XP |
| **Nivel 4** | 300 XP |
| **Nivel 5** | 1000 XP |

---

## 6. Bono Centinela (Monitorización)
Para premiar el compromiso con la disponibilidad del equipo, se otorgan puntos por el reporte de servicios:
- **Acción:** Ser el primer miembro en reportar una caída de servicio que posteriormente sea verificada por otros miembros.
- **Recompensa:** +20 XP y +5 puntos de Energía Vital.
- **Limitación:** Aplica una vez por hora por servicio para evitar abusos.
