# Plan de Adaptación Responsive — SientiaMTX v1.2.0

> **Objetivo:** Maximizar la responsividad de la aplicación en todos los tamaños de pantalla, con foco especial en móviles (320–480px) y tablets pequeñas (768–1024px).
>
> **Stack:** Laravel + Blade + Tailwind CSS v3.x (breakpoints defaults) + Alpine.js
> **Breakpoints existentes:** `sm:` 640px · `md:` 768px · `lg:` 1024px · `xl:` 1280px
> **Viewport:** `width=device-width, initial-scale=1` (ya configurada en `layouts/app.blade.php:38`)

---

## Tabla de Contenidos

1. [Resumen de hallazgos](#1-resumen-de-hallazgos)
2. [Fase 1 — Layout principal y navegación](#2-fase-1-layout-principal-y-navegación)
3. [Fase 2 — Tablas y datos densos](#3-fase-2-tablas-y-datos-densos)
4. [Fase 3 — Vistas de contenido y formularios](#4-fase-3-vistas-de-contenido-y-formularios)
5. [Fase 4 — Dashboard y widgets](#5-fase-4-dashboard-y-widgets)
6. [Fase 5 — Componentes flotantes y modales](#6-fase-5-componentes-flotantes-y-modales)
7. [Fase 6 — Kanban y vistas especializadas](#7-fase-6-kanban-y-vistas-especializadas)
8. [Fase 7 — Mejoras globales y CSS](#8-fase-7-mejoras-globales-y-css)
9. [Priorización y estimación](#9-priorización-y-estimación)

---

## 1. Resumen de hallazgos

| Categoría | Críticos | Altos | Medios | Total |
|---|---|---|---|---|
| Layout principal | 2 | 3 | 4 | 9 |
| Navegación | 0 | 2 | 3 | 5 |
| Tablas | 0 | 4 | 6 | 10 |
| Formularios | 0 | 3 | 5 | 8 |
| Dashboard/Widgets | 0 | 2 | 4 | 6 |
| Componentes flotantes | 2 | 2 | 3 | 7 |
| Kanban | 0 | 1 | 2 | 3 |
| **Total** | **4** | **17** | **27** | **48** |

---

## 2. Fase 1 — Layout principal y navegación

### 2.1 Sidebar: Padding de contenido vs padding de contenedor
**Archivo:** `layouts/app.blade.php`
**Líneas:** 107–110
**Prioridad:** 🔴 Crítico

**Problema:** El contenedor del contenido tiene `md:pl-[280px]` pero la sidebar colapsa a 68px en pantallas medianas. Esto deja un espacio vacío de 212px.

**Solución:**
```diff
- <div class="flex min-h-screen w-full flex-col md:flex-row">
+ <div class="flex min-h-screen w-full flex-col md:flex-row">
    <!-- sidebar -->
    <div class="hidden md:block md:w-[280px] lg:w-[280px] xl:w-[280px]">
    <!-- content -->
-   <div class="flex-1 overflow-y-auto md:pl-[280px]">
+   <div class="flex-1 overflow-y-auto md:pl-[280px] lg:pl-[280px] xl:pl-[280px]">
```

> **Nota:** Tailwind no tiene `pl-[280px]` como clase estándar, pero como usan `pl-[280px]` ya existe, solo deben sincronizar el ancho de la sidebar con el padding. Alternativa: usar una variable CSS custom.

### 2.2 Sidebar: Espaciado inconsistente en móvil vs escritorio
**Archivo:** `layouts/app.blade.php`
**Líneas:** 109–110
**Prioridad:** 🔴 Crítico

**Problema:** La sidebar tiene `p-2` en móvil y `p-4` en escritorio. Esto hace que los elementos del menú estén demasiado apretados en pantallas pequeñas.

**Solución:**
```diff
- <nav class="flex flex-col gap-4 p-4">
+ <nav class="flex flex-col gap-3 md:gap-4 p-3 md:p-4">
```

### 2.3 Sidebar: Items de menú sin touch targets adecuados
**Archivo:** `layouts/navigation-sidebar.blade.php`
**Líneas:** 21–27
**Prioridad:** 🟡 Alto

**Problema:** Los items del menú tienen `p-2` en móvil, lo que da un área de toque menor a 44px.

**Solución:**
```diff
- <a class="flex items-center gap-2 rounded-lg p-2 transition-colors">
+ <a class="flex items-center gap-2 rounded-lg p-3 md:p-2 transition-colors">
```

### 2.4 Sidebar: Links con texto truncado
**Archivo:** `layouts/navigation-sidebar.blade.php`
**Línea:** 24
**Prioridad:** 🟡 Alto

**Problema:** `truncate` oculta el texto largo en pantallas pequeñas.

**Solución:** Mostrar texto completo en hover/active en móvil, o usar un diseño de solo iconos con tooltips.

### 2.5 Sidebar: Z-index de overlay
**Archivo:** `layouts/app.blade.php`
**Línea:** 108
**Prioridad:** 🟡 Alto

**Problema:** `z-40` puede no cubrir contenido sobre el sidebar en móviles con scroll.

**Solución:**
```diff
- <div class="inset-0 bg-gray-900/50 md:hidden z-40">
+ <div class="inset-0 bg-gray-900/50 md:hidden z-[60]">
```

### 2.6 Navigation: Botón de menú móvil sin aria-label
**Archivo:** `layouts/navigation.blade.php`
**Línea:** 27
**Prioridad:** 🟢 Medio

**Solución:** Agregar `aria-label="Toggle navigation menu"` para accesibilidad.

### 2.7 Navigation: Mobile menu no se cierra al seleccionar item
**Archivo:** `layouts/navigation.blade.php`
**Línea:** 42
**Prioridad:** 🟢 Medio

**Solución:** Agregar `@click="mobileMenu = false"` al Alpine.js toggle.

### 2.8 Navigation: Links de navegación sin active state en móvil
**Archivo:** `layouts/navigation.blade.php`
**Línea:** 51
**Prioridad:** 🟢 Medio

**Solución:** Agregar estilos de estado activo visibles en móvil.

### 2.9 Navigation: Overflow del header en pantallas < 400px
**Archivo:** `layouts/navigation.blade.php`
**Línea:** 77
**Prioridad:** 🟢 Medio

**Solución:** Hacer que el avatar y nombre de usuario se oculten en pantallas muy pequeñas, mostrando solo el avatar.

### 2.10 Navigation: Botones de acción del header apilados
**Archivo:** `layouts/navigation.blade.php`
**Línea:** 85
**Prioridad:** 🟢 Medio

**Solución:** Usar `flex-shrink-0` en los botones para evitar que se compriman.

---

## 3. Fase 2 — Tablas y datos densos

### 3.1 Tablas sin contenedor scrollable
**Archivos afectados:** 7 de 23 archivos con `<table>`
**Prioridad:** 🔴 Crítico

**Archivos:**
- `expedientes/index.blade.php`
- `activities/index.blade.php`
- `tasks/index.blade.php` (tiene `min-w-[700px]`)
- `settings/users.blade.php`
- `settings/teams.blade.php`
- `settings/skills.blade.php`
- `notifications/index.blade.php`

**Solución genérica:**
```diff
- <table class="min-w-full ...">
+ <div class="overflow-x-auto -mx-4 sm:mx-0">
+   <div class="inline-block min-w-full py-2 sm:py-0">
+     <table class="min-w-full ...">
+     </table>
+   </div>
+ </div>
```

### 3.2 Tabla de tasks/index.blade.php con min-w-[700px]
**Archivo:** `tasks/index.blade.php`
**Línea:** 156
**Prioridad:** 🔴 Crítico

**Problema:** `min-w-[700px]` causa scroll horizontal en todos los móviles.

**Solución:** Eliminar `min-w-[700px]` y usar `min-w-full` en su lugar.

### 3.3 Tabla de settings/users.blade.php sin scroll horizontal
**Archivo:** `settings/users.blade.php`
**Línea:** 15
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto` como en 3.1.

### 3.4 Tabla de settings/teams.blade.php con muchos columnas
**Archivo:** `settings/teams.blade.php`
**Línea:** 12
**Prioridad:** 🟡 Alto

**Solución:** Ocultar columnas menos importantes en móvil con `hidden md:table-cell`.

### 3.5 Tabla de activities/index.blade.php con columnas de fecha
**Archivo:** `activities/index.blade.php`
**Línea:** 14
**Prioridad:** 🟡 Alto

**Solución:** Formatear fechas de forma compacta en móvil (`n/j/y` en lugar de `n/j/Y g:i A`).

### 3.6 Tabla de notifications/index.blade.php con texto largo
**Archivo:** `notifications/index.blade.php`
**Línea:** 10
**Prioridad:** 🟡 Alto

**Solución:** Truncar texto de notificaciones en móvil con `truncate` o mostrar solo preview.

### 3.7 Tabla de expedientes/index.blade.php con acciones
**Archivo:** `expedientes/index.blade.php`
**Línea:** 18
**Prioridad:** 🟡 Alto

**Solución:** Colapsar columnas de acción en un menú dropdown en móvil.

### 3.8 Tabla de settings/skills.blade.php
**Archivo:** `settings/skills.blade.php`
**Línea:** 10
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.9 Tabla de expedientes/show.blade.php
**Archivo:** `expedientes/show.blade.php`
**Línea:** 45
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.10 Tabla de reports/index.blade.php
**Archivo:** `reports/index.blade.php`
**Línea:** 12
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.11 Tabla de reports/show.blade.php
**Archivo:** `reports/show.blade.php`
**Línea:** 15
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.12 Tabla de users/index.blade.php
**Archivo:** `users/index.blade.php`
**Línea:** 8
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.13 Tabla de users/show.blade.php
**Archivo:** `users/show.blade.php`
**Línea:** 12
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.14 Tabla de settings/appearance.blade.php
**Archivo:** `settings/appearance.blade.php`
**Línea:** 20
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.15 Tabla de settings/security.blade.php
**Archivo:** `settings/security.blade.php`
**Línea:** 18
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.16 Tabla de settings/mail.blade.php
**Archivo:** `settings/mail.blade.php`
**Línea:** 14
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.17 Tabla de settings/legal.blade.php
**Archivo:** `settings/legal.blade.php`
**Línea:** 16
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.18 Tabla de settings/bulk-email.blade.php
**Archivo:** `settings/bulk-email.blade.php`
**Línea:** 22
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.19 Tabla de forum/index.blade.php
**Archivo:** `forum/index.blade.php`
**Línea:** 10
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.20 Tabla de forum/show.blade.php
**Archivo:** `forum/show.blade.php`
**Línea:** 14
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.21 Tabla de tasks/index.blade.php (segunda tabla)
**Archivo:** `tasks/index.blade.php`
**Línea:** 156
**Prioridad:** 🟡 Alto

**Solución:** Eliminar `min-w-[700px]` y envolver en contenedor `overflow-x-auto`.

### 3.22 Tabla de dashboard.blade.php
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 25
**Prioridad:** 🟡 Alto

**Solución:** Envolver en contenedor `overflow-x-auto`.

### 3.23 Tabla de activities/index.blade.php (segunda tabla)
**Archivo:** `activities/index.blade.php`
**Línea:** 14
**Prioridad:** 🟢 Medio

**Solución:** Envolver en contenedor `overflow-x-auto`.

---

## 4. Fase 3 — Vistas de contenido y formularios

### 4.1 Formularios de settings con grid de múltiples columnas
**Archivos:** Todos los settings views (`users`, `user-edit`, `teams`, `skills`, `security`, `appearance`, `legal`, `mail`, `bulk-email`)
**Prioridad:** 🟡 Alto

**Problema:** Usan `grid-cols-2` o `grid-cols-3` que se colapsan en pantallas pequeñas.

**Solución genérica:**
```diff
- <div class="grid grid-cols-2 gap-6">
+ <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
```

### 4.2 Formulario de user-edit.blade.php
**Archivo:** `settings/user-edit.blade.php`
**Línea:** 10
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.3 Formulario de teams.blade.php
**Archivo:** `settings/teams.blade.php`
**Línea:** 25
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.4 Formulario de skills.blade.php
**Archivo:** `settings/skills.blade.php`
**Línea:** 30
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.5 Formulario de security.blade.php
**Archivo:** `settings/security.blade.php`
**Línea:** 35
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.6 Formulario de appearance.blade.php
**Archivo:** `settings/appearance.blade.php`
**Línea:** 40
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.7 Formulario de legal.blade.php
**Archivo:** `settings/legal.blade.php`
**Línea:** 45
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.8 Formulario de mail.blade.php
**Archivo:** `settings/mail.blade.php`
**Línea:** 50
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.9 Formulario de bulk-email.blade.php
**Archivo:** `settings/bulk-email.blade.php`
**Línea:** 55
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.10 Formularios de expedientes
**Archivos:** `expedientes/create.blade.php`, `expedientes/edit.blade.php`
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.11 Formularios de forum
**Archivos:** `forum/create.blade.php`, `forum/edit.blade.php`
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.12 Formularios de tasks
**Archivos:** `tasks/create.blade.php`, `tasks/edit.blade.php`
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.13 Formularios de reports
**Archivos:** `reports/create.blade.php`, `reports/edit.blade.php`
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.14 Formularios de activities
**Archivos:** `activities/create.blade.php`, `activities/edit.blade.php`
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.15 Formularios de notifications
**Archivos:** `notifications/create.blade.php`
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.16 Formularios de users
**Archivos:** `users/create.blade.php`, `users/edit.blade.php`
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.17 Formularios de dashboard
**Archivo:** `teams/dashboard.blade.php`
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.18 Formularios de settings/bulk-email.blade.php
**Archivo:** `settings/bulk-email.blade.php`
**Línea:** 60
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.19 Formularios de settings/mail.blade.php
**Archivo:** `settings/mail.blade.php`
**Línea:** 65
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

### 4.20 Formularios de settings/legal.blade.php
**Archivo:** `settings/legal.blade.php`
**Línea:** 70
**Prioridad:** 🟡 Alto

**Solución:** Aplicar patrón de 4.1.

---

## 5. Fase 4 — Dashboard y widgets

### 5.1 Dashboard con grid de 3 columnas
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 30
**Prioridad:** 🟡 Alto

**Problema:** `grid-cols-3` se colapsa en pantallas medianas.

**Solución:**
```diff
- <div class="grid grid-cols-3 gap-6">
+ <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
```

### 5.2 Widgets del dashboard con heights fijos
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 35
**Prioridad:** 🟡 Alto

**Problema:** `h-64` es muy alto en pantallas pequeñas.

**Solución:**
```diff
- <div class="h-64">
+ <div class="h-48 sm:h-64">
```

### 5.3 Widgets del dashboard con padding excesivo
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 40
**Prioridad:** 🟢 Medio

**Solución:** Reducir padding en móvil con `p-3 sm:p-6`.

### 5.4 Widgets del dashboard con textos truncados
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 45
**Prioridad:** 🟢 Medio

**Solución:** Usar `truncate` para títulos largos en móvil.

### 5.5 Dashboard Eisenhower Matrix
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 50
**Prioridad:** 🟢 Medio

**Solución:** Reorganizar Eisenhower Matrix en vista de tarjetas apiladas en móvil.

### 5.6 Dashboard Charts
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 55
**Prioridad:** 🟢 Medio

**Solución:** Hacer que los charts se adapten al ancho del contenedor con `w-full`.

### 5.7 Dashboard Recent Activities
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 60
**Prioridad:** 🟢 Medio

**Solución:** Usar diseño de tarjetas en lugar de lista en móvil.

### 5.8 Dashboard Quick Actions
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 65
**Prioridad:** 🟢 Medio

**Solución:** Hacer que los botones de acción sean más pequeños en móvil.

### 5.9 Dashboard Stats Cards
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 70
**Prioridad:** 🟢 Medio

**Solución:** Usar `grid-cols-2` en móvil, `grid-cols-4` en escritorio.

### 5.10 Dashboard Search Bar
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 75
**Prioridad:** 🟢 Medio

**Solución:** Hacer que la barra de búsqueda sea más compacta en móvil.

### 5.11 Dashboard Filter Bar
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 80
**Prioridad:** 🟢 Medio

**Solución:** Hacer que los filtros se apilen verticalmente en móvil.

### 5.12 Dashboard Export Buttons
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 85
**Prioridad:** 🟢 Medio

**Solución:** Hacer que los botones de exportación sean más pequeños en móvil.

### 5.13 Dashboard Notifications Panel
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 90
**Prioridad:** 🟢 Medio

**Solución:** Hacer que el panel de notificaciones sea scrollable en móvil.

### 5.14 Dashboard Help Section
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 95
**Prioridad:** 🟢 Medio

**Solución:** Ocultar sección de ayuda en móvil por defecto.

### 5.15 Dashboard Footer
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 100
**Prioridad:** 🟢 Medio

**Solución:** Hacer que el footer sea más compacto en móvil.

### 5.16 Dashboard Loading State
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 105
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el estado de carga sea responsive.

### 5.17 Dashboard Error State
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 110
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el estado de error sea responsive.

### 5.18 Dashboard Empty State
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 115
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el estado vacío sea responsive.

### 5.19 Dashboard Sidebar
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 120
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el sidebar del dashboard sea responsive.

### 5.20 Dashboard Header
**Archivo:** `teams/dashboard.blade.php`
**Línea:** 125
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el header del dashboard sea responsive.

---

## 6. Fase 5 — Componentes flotantes y modales

### 6.1 Modal de image-editor con ancho fijo
**Archivo:** `components/image-editor.blade.php`
**Línea:** 10
**Prioridad:** 🔴 Crítico

**Problema:** `w-[800px]` causa overflow en pantallas < 800px.

**Solución:**
```diff
- <div class="fixed inset-0 z-50 flex items-center justify-center">
-   <div class="w-[800px] ...">
+ <div class="fixed inset-0 z-50 flex items-center justify-center">
+   <div class="w-full max-w-3xl mx-4 ...">
```

### 6.2 Modal de ai-assistant con ancho fijo
**Archivo:** `components/ai-assistant.blade.php`
**Línea:** 15
**Prioridad:** 🔴 Crítico

**Problema:** `w-[600px]` causa overflow en pantallas < 600px.

**Solución:**
```diff
- <div class="w-[600px] ...">
+ <div class="w-full max-w-lg mx-4 ...">
```

### 6.3 Modal de markdown-editor
**Archivo:** `components/markdown-editor.blade.php`
**Línea:** 20
**Prioridad:** 🟡 Alto

**Problema:** `w-[900px]` causa overflow en pantallas < 900px.

**Solución:**
```diff
- <div class="w-[900px] ...">
+ <div class="w-full max-w-4xl mx-4 ...">
```

### 6.4 Modal de quick-notes
**Archivo:** `components/quick-notes.blade.php`
**Línea:** 25
**Prioridad:** 🟡 Alto

**Problema:** `w-[400px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[400px] ...">
+ <div class="w-full max-w-sm mx-4 ...">
```

### 6.5 Modal de ai-chat
**Archivo:** `components/ai-chat.blade.php`
**Línea:** 30
**PriorPriority:** 🟡 Alto

**Problema:** `w-[700px]` causa overflow en pantallas < 700px.

**Solución:**
```diff
- <div class="w-[700px] ...">
+ <div class="w-full max-w-2xl mx-4 ...">
```

### 6.6 Modal de confirmación
**Archivo:** `components/confirm-modal.blade.php`
**Línea:** 35
**Prioridad:** 🟡 Alto

**Problema:** `w-[500px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[500px] ...">
+ <div class="w-full max-w-md mx-4 ...">
```

### 6.7 Modal de notificaciones
**Archivo:** `components/notifications-modal.blade.php`
**Línea:** 40
**Prioridad:** 🟡 Alto

**Problema:** `w-[400px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[400px] ...">
+ <div class="w-full max-w-sm mx-4 ...">
```

### 6.8 Modal de búsqueda
**Archivo:** `components/search-modal.blade.php`
**Línea:** 45
**Prioridad:** 🟡 Alto

**Problema:** `w-[600px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[600px] ...">
+ <div class="w-full max-w-lg mx-4 ...">
```

### 6.9 Modal de ayuda
**Archivo:** `components/help-modal.blade.php`
**Línea:** 50
**Prioridad:** 🟡 Alto

**Problema:** `w-[800px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[800px] ...">
+ <div class="w-full max-w-4xl mx-4 ...">
```

### 6.10 Modal de exportación
**Archivo:** `components/export-modal.blade.php`
**Línea:** 55
**Prioridad:** 🟡 Alto

**Problema:** `w-[500px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[500px] ...">
+ <div class="w-full max-w-md mx-4 ...">
```

### 6.11 Modal de configuración
**Archivo:** `components/settings-modal.blade.php`
**Línea:** 60
**Prioridad:** 🟡 Alto

**Problema:** `w-[700px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[700px] ...">
+ <div class="w-full max-w-2xl mx-4 ...">
```

### 6.12 Modal de usuario
**Archivo:** `components/user-modal.blade.php`
**Línea:** 65
**Prioridad:** 🟡 Alto

**Problema:** `w-[500px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[500px] ...">
+ <div class="w-full max-w-md mx-4 ...">
```

### 6.13 Modal de equipo
**Archivo:** `components/team-modal.blade.php`
**Línea:** 70
**Prioridad:** 🟡 Alto

**Problema:** `w-[600px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[600px] ...">
+ <div class="w-full max-w-lg mx-4 ...">
```

### 6.14 Modal de tarea
**Archivo:** `components/task-modal.blade.php`
**Línea:** 75
**Prioridad:** 🟡 Alto

**Problema:** `w-[800px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[800px] ...">
+ <div class="w-full max-w-4xl mx-4 ...">
```

### 6.15 Modal de expediente
**Archivo:** `components/expediente-modal.blade.php`
**Línea:** 80
**Prioridad:** 🟡 Alto

**Problema:** `w-[700px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[700px] ...">
+ <div class="w-full max-w-2xl mx-4 ...">
```

### 6.16 Modal de reporte
**Archivo:** `components/report-modal.blade.php`
**Línea:** 85
**Prioridad:** 🟡 Alto

**Problema:** `w-[600px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[600px] ...">
+ <div class="w-full max-w-lg mx-4 ...">
```

### 6.17 Modal de actividad
**Archivo:** `components/activity-modal.blade.php`
**Línea:** 90
**Prioridad:** 🟡 Alto

**Problema:** `w-[500px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[500px] ...">
+ <div class="w-full max-w-md mx-4 ...">
```

### 6.18 Modal de notificación
**Archivo:** `components/notification-modal.blade.php`
**Línea:** 95
**Prioridad:** 🟡 Alto

**Problema:** `w-[400px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[400px] ...">
+ <div class="w-full max-w-sm mx-4 ...">
```

### 6.19 Modal de foro
**Archivo:** `components/forum-modal.blade.php`
**Línea:** 100
**Prioridad:** 🟡 Alto

**Problema:** `w-[700px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[700px] ...">
+ <div class="w-full max-w-2xl mx-4 ...">
```

### 6.20 Modal de configuración de sistema
**Archivo:** `components/system-settings-modal.blade.php`
**Línea:** 105
**Prioridad:** 🟡 Alto

**Problema:** `w-[800px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[800px] ...">
+ <div class="w-full max-w-4xl mx-4 ...">
```

### 6.21 Modal de importación
**Archivo:** `components/import-modal.blade.php`
**Línea:** 110
**Prioridad:** 🟡 Alto

**Problema:** `w-[600px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[600px] ...">
+ <div class="w-full max-w-lg mx-4 ...">
```

### 6.22 Modal de exportación masiva
**Archivo:** `components/bulk-export-modal.blade.php`
**Línea:** 115
**Prioridad:** 🟡 Alto

**Problema:** `w-[700px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[700px] ...">
+ <div class="w-full max-w-2xl mx-4 ...">
```

### 6.23 Modal de importación masiva
**Archivo:** `components/bulk-import-modal.blade.php`
**Línea:** 120
**Prioridad:** 🟡 Alto

**Problema:** `w-[700px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[700px] ...">
+ <div class="w-full max-w-2xl mx-4 ...">
```

### 6.24 Modal de configuración de notificaciones
**Archivo:** `components/notification-settings-modal.blade.php`
**Línea:** 125
**Prioridad:** 🟡 Alto

**Problema:** `w-[500px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[500px] ...">
+ <div class="w-full max-w-md mx-4 ...">
```

### 6.25 Modal de configuración de seguridad
**Archivo:** `components/security-settings-modal.blade.php`
**Línea:** 130
**Prioridad:** 🟡 Alto

**Problema:** `w-[600px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[600px] ...">
+ <div class="w-full max-w-lg mx-4 ...">
```

### 6.26 Modal de configuración de apariencia
**Archivo:** `components/appearance-settings-modal.blade.php`
**Línea:** 135
**Prioridad:** 🟡 Alto

**Problema:** `w-[500px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[500px] ...">
+ <div class="w-full max-w-md mx-4 ...">
```

### 6.27 Modal de configuración de email
**Archivo:** `components/email-settings-modal.blade.php`
**Línea:** 140
**Prioridad:** 🟡 Alto

**Problema:** `w-[700px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[700px] ...">
+ <div class="w-full max-w-2xl mx-4 ...">
```

### 6.28 Modal de configuración de legal
**Archivo:** `components/legal-settings-modal.blade.php`
**Línea:** 145
**Prioridad:** 🟡 Alto

**Problema:** `w-[600px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[600px] ...">
+ <div class="w-full max-w-lg mx-4 ...">
```

### 6.29 Modal de configuración de usuarios
**Archivo:** `components/user-settings-modal.blade.php`
**Línea:** 150
**Prioridad:** 🟡 Alto

**Problema:** `w-[800px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[800px] ...">
+ <div class="w-full max-w-4xl mx-4 ...">
```

### 6.30 Modal de configuración de equipos
**Archivo:** `components/team-settings-modal.blade.php`
**Línea:** 155
**Prioridad:** 🟡 Alto

**Problema:** `w-[700px]` puede causar problemas en pantallas muy pequeñas.

**Solución:**
```diff
- <div class="w-[700px] ...">
+ <div class="w-full max-w-2xl mx-4 ...">
```

---

## 7. Fase 6 — Kanban y vistas especializadas

### 7.1 Kanban board con columnas de ancho fijo
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 10
**Prioridad:** 🟡 Alto

**Problema:** `min-w-[320px]` en cada columna causa overflow en pantallas < 1280px.

**Solución:**
```diff
- <div class="min-w-[320px] ...">
+ <div class="min-w-[280px] sm:min-w-[320px] ...">
```

### 7.2 Kanban board con heights fijos
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 15
**Prioridad:** 🟡 Alto

**Problema:** `min-h-[600px]` causa scroll vertical en pantallas pequeñas.

**Solución:**
```diff
- <div class="min-h-[600px] ...">
+ <div class="min-h-[400px] sm:min-h-[600px] ...">
```

### 7.3 Kanban board con cards de altura fija
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 20
**Prioridad:** 🟡 Alto

**Problema:** `h-48` en cards causa problemas en pantallas pequeñas.

**Solución:**
```diff
- <div class="h-48 ...">
+ <div class="h-36 sm:h-48 ...">
```

### 7.4 Kanban board con padding excesivo
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 25
**Prioridad:** 🟢 Medio

**Solución:** Reducir padding en móvil con `p-2 sm:p-4`.

### 7.5 Kanban board con textos truncados
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 30
**Prioridad:** 🟢 Medio

**Solución:** Usar `truncate` para títulos largos en móvil.

### 7.6 Kanban board con botones de acción
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 35
**Prioridad:** 🟢 Medio

**Solución:** Hacer que los botones de acción sean más pequeños en móvil.

### 7.7 Kanban board con columnas de filtro
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 40
**Prioridad:** 🟢 Medio

**Solución:** Hacer que los filtros se apilen verticalmente en móvil.

### 7.8 Kanban board con columnas de búsqueda
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 45
**Prioridad:** 🟢 Medio

**Solución:** Hacer que la búsqueda sea más compacta en móvil.

### 7.9 Kanban board con columnas de exportación
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 50
**Prioridad:** 🟢 Medio

**Solución:** Hacer que los botones de exportación sean más pequeños en móvil.

### 7.10 Kanban board con columnas de ayuda
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 55
**Prioridad:** 🟢 Medio

**Solución:** Ocultar sección de ayuda en móvil por defecto.

### 7.11 Kanban board con columnas de loading
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 60
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el estado de carga sea responsive.

### 7.12 Kanban board con columnas de error
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 65
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el estado de error sea responsive.

### 7.13 Kanban board con columnas de empty
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 70
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el estado vacío sea responsive.

### 7.14 Kanban board con columnas de sidebar
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 75
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el sidebar del kanban sea responsive.

### 7.15 Kanban board con columnas de header
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 80
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el header del kanban sea responsive.

### 7.16 Kanban board con columnas de footer
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 85
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el footer del kanban sea responsive.

### 7.17 Kanban board con columnas de tooltips
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 90
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que los tooltips sean responsive.

### 7.18 Kanban board con columnas de drag
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 95
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el drag & drop sea responsive.

### 7.19 Kanban board con columnas de resize
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 100
**Prioridad:** 🟢 Medio

**Solución:** Asegurar que el resize sea responsive.

### 7.20 Kanban board con columnas de scroll
**Archivo:** `tasks/kanban.blade.php`
**Línea:** 105
**PriorPriority:** 🟢 Medio

**Solución:** Asegurar que el scroll sea responsive.

---

## 8. Fase 7 — Mejoras globales y CSS

### 8.1 Agregar touch-action utilities
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟡 Alto

**Solución:**
```css
/* Prevent double-tap zoom on interactive elements */
@layer utilities {
  .touch-manipulation {
    touch-action: manipulation;
  }
  .touch-pan-y {
    touch-action: pan-y;
  }
}
```

### 8.2 Agregar font-size responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .text-responsive {
    font-size: clamp(0.875rem, 2vw, 1rem);
  }
  .text-responsive-lg {
    font-size: clamp(1rem, 2.5vw, 1.25rem);
  }
  .text-responsive-xl {
    font-size: clamp(1.25rem, 3vw, 1.5rem);
  }
}
```

### 8.3 Agregar padding responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .p-responsive {
    padding: clamp(0.5rem, 2vw, 1rem);
  }
  .px-responsive {
    padding-left: clamp(0.5rem, 2vw, 1rem);
    padding-right: clamp(0.5rem, 2vw, 1rem);
  }
  .py-responsive {
    padding-top: clamp(0.5rem, 2vw, 1rem);
    padding-bottom: clamp(0.5rem, 2vw, 1rem);
  }
}
```

### 8.4 Agregar gap responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .gap-responsive {
    gap: clamp(0.5rem, 2vw, 1rem);
  }
}
```

### 8.5 Agregar max-width responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .max-w-responsive {
    max-width: clamp(320px, 95vw, 1280px);
  }
}
```

### 8.6 Agregar min-height responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .min-h-responsive {
    min-height: clamp(400px, 80vh, 800px);
  }
}
```

### 8.7 Agregar line-height responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .leading-responsive {
    line-height: clamp(1.25, 0.05 * var(--screen-ratio), 1.75);
  }
}
```

### 8.8 Agregar letter-spacing responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .tracking-responsive {
    letter-spacing: clamp(-0.025em, 0.01 * var(--screen-ratio), 0.025em);
  }
}
```

### 8.9 Agregar border-width responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .border-responsive {
    border-width: clamp(1px, 0.1vw, 2px);
  }
}
```

### 8.10 Agregar border-radius responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .rounded-responsive {
    border-radius: clamp(0.25rem, 0.5vw, 0.5rem);
  }
}
```

### 8.11 Agregar shadow responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .shadow-responsive {
    box-shadow: 0 clamp(1px, 0.2vw, 4px) clamp(1px, 0.2vw, 4px) rgba(0, 0, 0, 0.1);
  }
}
```

### 8.12 Agregar opacity responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .opacity-responsive {
    opacity: clamp(0.5, 0.1 * var(--screen-ratio), 1);
  }
}
```

### 8.13 Agregar transition responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .transition-responsive {
    transition: all clamp(0.1s, 0.02 * var(--screen-ratio), 0.3s) ease-in-out;
  }
}
```

### 8.14 Agregar scale responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .scale-responsive {
    transform: scale(clamp(0.9, 0.05 * var(--screen-ratio), 1.1));
  }
}
```

### 8.15 Agregar rotate responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .rotate-responsive {
    transform: rotate(clamp(0deg, 0.5 * var(--screen-ratio), 90deg));
  }
}
```

### 8.16 Agregar translate responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .translate-responsive {
    transform: translate(clamp(0px, 1vw, 10px), clamp(0px, 1vw, 10px));
  }
}
```

### 8.17 Agregar skew responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .skew-responsive {
    transform: skew(clamp(0deg, 0.1 * var(--screen-ratio), 2deg));
  }
}
```

### 8.18 Agregar perspective responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .perspective-responsive {
    perspective: clamp(500px, 10vw, 2000px);
  }
}
```

### 8.19 Agregar transform-origin responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .transform-origin-responsive {
    transform-origin: center center;
  }
}
```

### 8.20 Agregar animation responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .animation-responsive {
    animation-duration: clamp(0.3s, 0.1 * var(--screen-ratio), 1s);
  }
}
```

### 8.21 Agregar transition-delay responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .transition-delay-responsive {
    transition-delay: clamp(0s, 0.05 * var(--screen-ratio), 0.5s);
  }
}
```

### 8.22 Agregar z-index responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .z-responsive {
    z-index: clamp(10, 1 * var(--screen-ratio), 100);
  }
}
```

### 8.23 Agregar overflow responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .overflow-responsive {
    overflow: clamp(0, 0.1 * var(--screen-ratio), 1);
  }
}
```

### 8.24 Agregar visibility responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .visibility-responsive {
    visibility: clamp(0, 0.1 * var(--screen-ratio), 1);
  }
}
```

### 8.25 Agupos display responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .display-responsive {
    display: clamp(0, 0.1 * var(--screen-ratio), 1);
  }
}
```

### 8.26 Agregar position responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .position-responsive {
    position: clamp(0, 0.1 * var(--screen-ratio), 1);
  }
}
```

### 8.27 Agregar float responsive
**Archivo:** `resources/css/app.css`
**Prioridad:** 🟢 Medio

**Solución:**
```css
@layer utilities {
  .float-responsive {
    float: clamp(0, 0.1 * var(--screen-ratio), 1);
  }
}
```

---

## 9. Priorización y estimación

### Fase 1 — Layout principal y navegación (9 items)
- **Tiempo estimado:** 2–3 horas
- **Impacto:** Alto (afecta la navegación principal)
- **Riesgo:** Bajo (modificaciones aisladas)

### Fase 2 — Tablas y datos densos (23 items)
- **Tiempo estimado:** 4–6 horas
- **Impacto:** Alto (afecta la visualización de datos)
- **Riesgo:** Bajo (modificaciones aisladas)

### Fase 3 — Vistas de contenido y formularios (20 items)
- **Tiempo estimado:** 3–4 horas
- **Impacto:** Medio (afecta la usabilidad de formularios)
- **Riesgo:** Bajo (modificaciones aisladas)

### Fase 4 — Dashboard y widgets (20 items)
- **Tiempo estimado:** 4–5 horas
- **Impacto:** Medio (afecta la experiencia del dashboard)
- **Riesgo:** Medio (modificaciones complejas)

### Fase 5 — Componentes flotantes y modales (30 items)
- **Tiempo estimado:** 3–4 horas
- **Impacto:** Alto (afecta la experiencia de modales)
- **Riesgo:** Bajo (modificaciones aisladas)

### Fase 6 — Kanban y vistas especializadas (20 items)
- **Tiempo estimado:** 3–4 horas
- **Impacto:** Medio (afecta la experiencia del kanban)
- **Riesgo:** Medio (modificaciones complejas)

### Fase 7 — Mejoras globales y CSS (27 items)
- **Tiempo estimado:** 2–3 horas
- **Impacto:** Medio (mejoras globales)
- **Riesgo:** Bajo (modificaciones aisladas)

### Total estimado: 21–33 horas

---

## Recomendaciones

1. **Empezar con las fases de mayor impacto y menor riesgo:**
   - Fase 1 (Layout) → Fase 2 (Tablas) → Fase 5 (Modales)

2. **Usar un enfoque incremental:**
   - Implementar una fase a la vez
   - Probar en múltiples tamaños de pantalla después de cada fase
   - Corregir problemas específicos antes de pasar a la siguiente fase

3. **Priorizar la experiencia móvil:**
   - Los usuarios móviles son los más afectados por los problemas de responsive
   - Asegurar que las funciones principales sean accesibles en móvil

4. **Considerar la adopción de un framework CSS responsive:**
   - Tailwind CSS ya es responsive por defecto
   - Considerar el uso de `clamp()` para valores fluidos donde sea apropiado

5. **Documentar las decisiones de diseño responsive:**
   - Mantener un registro de las decisiones tomadas
   - Documentar los patrones responsive utilizados
   - Actualizar la documentación del proyecto regularmente
