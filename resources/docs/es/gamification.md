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

## 3. Reto de Resiliencia
Cuando un usuario completa una tarea que está **fuera de su árbol de habilidades**, el sistema lo reconoce como un "Reto de Resiliencia":
- **Puntos de Resiliencia:** `20 * Multiplicador`
- **Objetivo:** Premiar la adaptabilidad y el esfuerzo de salir de la zona de confort técnica.

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
