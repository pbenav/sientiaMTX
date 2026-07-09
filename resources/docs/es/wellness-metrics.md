# Métricas de Bienestar y Rendimiento del Equipo

En SientiaMTX, el Panel de Bienestar ("Wellness Dashboard") proporciona una visión integral sobre el estado de salud, estrés y carga de trabajo del equipo. A diferencia de las métricas puras de productividad, este panel busca evitar el desgaste profesional (*burnout*) y fomentar un equilibrio saludable entre el trabajo y la vida personal (*Work-Life Balance*).

## ¿De dónde se extraen los datos?

Para garantizar la privacidad y fiabilidad de los datos, las métricas del panel **no se generan automáticamente por arte de magia**. El sistema requiere la colaboración activa de los miembros del equipo:

### 1. Estado de Ánimo, Estrés y Energía (Daily Check-in)
Los gráficos de **Mapa de Calor (Heatmap)**, **Tendencia de Estrés**, **Tendencia de Energía** y **Riesgo de Burnout** se calculan a partir de los registros diarios de los usuarios (`user_mood_logs`).
*   **Lo que el equipo debe hacer:** Usar diariamente el widget o módulo de *"Daily Check-in" / "Cómo te sientes hoy"* de la plataforma. Deberán indicar su estado de ánimo general (Excelente, Bien, Regular, Mal, Terrible) y puntuar su nivel de energía del 1 al 5.
*   **Impacto:** El sistema cruza estos datos. El estrés se calcula de forma inversamente proporcional al ánimo. Si un usuario encadena varios días con estado de ánimo "Mal" y alta carga de horas, el semáforo de *Riesgo de Burnout* se pondrá en rojo y aparecerá una alerta para los responsables.

### 2. Horas Extra (Overtime) y Equilibrio Trabajo-Vida
Los gráficos de **Horas Extras Semanales** y el Radar de **Work-Life Balance** dependen del fichaje o los registros de tiempo de las Actividades (`time_logs`).
*   **Lo que el equipo debe hacer:** Fichar su inicio y fin de jornada (Clock-in / Clock-out) o usar el temporizador de tareas.
*   **Impacto:** El sistema compara los tiempos registrados con el horario laboral oficial configurado en el perfil de cada miembro (por ejemplo, de 8:00 a 18:00). 
    * Si se registra tiempo fuera de esas horas, se contabiliza automáticamente como "Hora Extra" (Overtime).
    * Si se registra tiempo durante los fines de semana (sábado/domingo), esto afectará negativamente al índice de Equilibrio Trabajo-Vida Personal.

### 3. Distribución de Carga y Productividad
Los gráficos de **Box Plot de Carga** y **Correlación Ánimo/Productividad** se extraen del volumen de actividades.
*   **Lo que el equipo debe hacer:** Crear, asignarse y cambiar de estado las Actividades en su flujo de trabajo normal.

## Notas sobre privacidad
Los paneles para responsables muestran métricas **agregadas** por defecto. Sin embargo, en caso de detectarse riesgos severos de sobrecarga (alertas de Burnout activas), el manager podrá visualizar perfiles individuales para programar reuniones de seguimiento (1-on-1) con el afectado y redistribuir la carga operativa.

---
> **Importante:** Si no se introducen registros de estado de ánimo o no se usa el temporizador, los gráficos de este panel mostrarán una advertencia de *"Sin datos suficientes"*. Es fundamental fomentar el hábito del registro diario dentro del equipo.
