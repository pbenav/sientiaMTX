# 🚀 SientiaMTX (v0.9.9RC4)

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

### 📆 Citas Previas y Portales de Servicio
*   **Portales Públicos Integrados**: Páginas de reserva con URLs amigables, conectadas directamente al mapa organizativo.
*   **Gestión de Citas Granular**: Definición de servicios, horarios de trabajo, pausas, duración y bloqueos de agenda.
*   **Avisos y Confirmaciones**: Emisión de localizadores de cita (ej. `25C-B4A1`), notificaciones de estado y cancelación rápida.

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

## 🛡️ Seguridad y Cumplimiento (ENS)
Este proyecto está alineado con las directrices del **Esquema Nacional de Seguridad (ENS)** de España (Real Decreto 311/2022) para niveles Medio/Alto, implementando:
*   **Autenticación Multifactor (MFA/2FA) Dual**: Soporte nativo para TOTP (Google Authenticator, Authy, etc.) y Correo Electrónico.
*   **Logs de Auditoría de Seguridad**: Registro unificado e inmutable de accesos y eventos críticos en la base de datos (`security_logs`).
*   **Encriptación en Reposo**: Cifrado automático AES-256-CBC de credenciales de integración de IA y tokens mediante el motor nativo de Laravel.
*   **Deep Privacy (Privacidad Profunda)**: Aislamiento jerárquico estricto donde los expedientes y tareas privadas son 100% invisibles para propietarios o administradores, a menos que sean explícitamente asignados, asegurando máxima confidencialidad en ejecución.
*   **Control de Roles y Cuotas**: Jerarquía de roles protegida y gestión estricta de cuotas de disco por equipo.
*   **Validación de Membresía**: Protección de acceso a adjuntos de tareas y endpoints de archivos para evitar fugas de información.

## ⚖️ Licencia
Distribuido bajo la licencia **GNU AGPL v3**. Consulta el archivo [LICENSE](LICENSE) para más detalles.

---
**Desarrollado con ❤️ por pbenav (2022-2026)**
