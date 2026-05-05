# 🚀 SientiaMTX (v0.9.8.RC3)

<p align="center">
  <img src="https://raw.githubusercontent.com/pbenav/cth-mobile/main/assets/icon/icon.png" width="150" alt="SientiaMTX Logo">
</p>

<p align="center">
  <strong>Inteligencia Colectiva y Productividad de Alto Rendimiento</strong><br>
  Una plataforma de gestión de tareas potenciada por IA y basada en la metodología de Eisenhower.
</p>

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-GNU%20AGPLv3-blue.svg" alt="License"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Framework-Laravel%2011-red.svg" alt="Laravel"></a>
  <img src="https://img.shields.io/badge/status-Stable-emerald.svg" alt="Status">
  <img src="https://img.shields.io/badge/version-1.0.0--GA-violet.svg" alt="Version">
</p>

---

## 💡 El Concepto
SientiaMTX no es solo una herramienta de gestión; es un ecosistema diseñado para maximizar el **Flow** del equipo. Al combinar la **Matriz de Eisenhower** con un asistente de **Inteligencia Artificial (Ax.ia)**, el sistema no solo organiza tareas, sino que ayuda a entender el valor real de cada acción.

## ✨ Características Principales

### 🧠 IA Ax.ia (Gemini Integration)
*   **Voz a Texto**: Transcripción instantánea de notas de voz en notas de texto.
*   **Análisis Predictivo**: Desglose automático de tareas complejas en planes de acción.
*   **Payloads de IA**: Inyección directa de contenido generado por IA en el flujo de trabajo.

### 📊 Visualización Avanzada
*   **Matriz Eisenhower**: Cuadrantes dinámicos para priorización inmediata.
*   **Kanban Premium**: Gestión visual con animaciones fluidas y estados reactivos.
*   **Gantt Optimizado**: Roadmap temporal con etiquetas legibles sin interacción.
*   **Active Network**: Monitorización en tiempo real de la disponibilidad y carga del equipo.

### 💬 Comunicación Estructurada
*   **Foros Anidados**: Hilos de discusión con soporte para citas, menciones y previsualización Markdown.
*   **Notificaciones Inteligentes**: Integración total con Telegram para alertas Q1 y resúmenes matutinos.

### 🎮 Gamificación y Salud Laboral
*   **Skill Tree**: Evolución de habilidades reales (Dev, Ops, Support, etc.) basada en tareas completadas.
*   **Vital Energy**: Monitorización de la carga cognitiva para prevenir el burnout del equipo.
*   **Sentinel**: Sistema colaborativo para detectar y reportar caídas de servicios críticos.

## 🛠️ Instalación Rápida

```bash
# 1. Clonar y entrar
git clone https://github.com/pbenav/sientiaMTX.git && cd sientiaMTX

# 2. Dependencias
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 3. Entorno
cp .env.example .env
php artisan key:generate

# 4. Base de datos
php artisan migrate --force
```

## 📚 Documentación Completa

Hemos preparado manuales detallados para cada perfil:

*   📖 [**Manual de Usuario (ES)**](resources/docs/es/user-manual.md) / [**User Manual (EN)**](resources/docs/en/user-manual.md)
*   🛡️ [**Manual de Administrador (ES)**](resources/docs/es/admin-manual.md) / [**Admin Manual (EN)**](resources/docs/en/admin-manual.md)
*   🚀 [**Guía de Instalación**](resources/docs/es/installation.md)
*   🤖 [**Configuración de Telegram**](resources/docs/es/telegram.md)

## 🛡️ Seguridad
Este proyecto ha pasado por una auditoría de seguridad rigurosa, implementando:
*   Jerarquía de roles protegida.
*   Gestión de cuotas de disco por equipo.
*   Validación de membresía en todos los endpoints de archivos.
*   Protección contra inyección y escalada de privilegios.

## ⚖️ Licencia
Distribuido bajo la licencia **GNU AGPL v3**. Consulta el archivo [LICENSE](LICENSE) para más detalles.

---
**Desarrollado con ❤️ por pbenav (2022-2026)**
