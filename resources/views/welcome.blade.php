<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>sientiaMTX —
        {{ app()->getLocale() === 'es' ? 'Gestión de tareas con la Matriz de Eisenhower' : 'Task Management with the Eisenhower Matrix' }}
    </title>
    <meta name="description"
        content="{{ app()->getLocale() === 'es' ? 'sientiaMTX te ayuda a priorizar lo que importa de verdad usando la Matriz de Eisenhower. Organiza tu equipo, no pierdas el foco.' : 'sientiaMTX helps you prioritize what truly matters using the Eisenhower Matrix. Organize your team, stay focused.' }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700;800&display=swap"
        rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --q1: #ef4444;
            --q2: #3b82f6;
            --q3: #f59e0b;
            --q4: #6b7280;
            --bg: #030712;
            --bg2: #0f172a;
            --border: #1e293b;
            --text: #f8fafc;
            --muted: #64748b;
            --accent: #7c3aed;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Space Grotesk', sans-serif;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* NAV */
        nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(3, 7, 18, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 700;
            font-family: 'Space Grotesk', sans-serif;
        }

        .logo-text span {
            color: #a78bfa;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
            border: none;
        }

        .btn-ghost {
            background: transparent;
            color: #94a3b8;
        }

        .btn-ghost:hover {
            background: #1e293b;
            color: white;
        }

        .btn-outline {
            background: transparent;
            color: #a78bfa;
            border: 1px solid #4c1d95;
        }

        .btn-outline:hover {
            background: #4c1d95;
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            box-shadow: 0 4px 20px #7c3aed40;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 28px #7c3aed60;
        }

        /* Locale pill */
        .locale-pill {
            display: flex;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .locale-pill a {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            transition: all .2s;
        }

        .locale-pill a.active,
        .locale-pill a:hover {
            background: #1e293b;
            color: white;
        }

        /* HERO */
        .hero {
            max-width: 1200px;
            margin: 0 auto;
            padding: 96px 24px 64px;
            text-align: center;
            position: relative;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1e1b4b;
            border: 1px solid #312e81;
            color: #a5b4fc;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 99px;
            margin-bottom: 28px;
            letter-spacing: .05em;
        }

        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .hero-title .gradient {
            background: linear-gradient(135deg, #a78bfa 0%, #60a5fa 50%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-sub {
            font-size: 18px;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto 36px;
        }

        .hero-ctas {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 72px;
        }

        .btn-hero {
            padding: 14px 32px;
            font-size: 16px;
            border-radius: 14px;
        }

        /* MATRIX VISUAL */
        .matrix-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            max-width: 680px;
            margin: 0 auto;
        }

        .matrix-label-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 680px;
            margin: 0 auto 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 0 4px;
        }

        .matrix-label-side {
            display: grid;
            grid-template-columns: 20px 1fr;
            gap: 12px;
            max-width: 700px;
            margin: 0 auto;
            align-items: center;
        }

        .matrix-side-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .08em;
            text-transform: uppercase;
            text-align: center;
        }

        .q-card {
            border-radius: 16px;
            padding: 18px 16px;
            border: 1px solid;
            text-align: left;
            transition: transform .3s, box-shadow .3s;
            cursor: default;
        }

        .q-card:hover {
            transform: translateY(-3px);
        }

        .q1 {
            background: rgba(239, 68, 68, .08);
            border-color: rgba(239, 68, 68, .3);
        }

        .q1:hover {
            box-shadow: 0 12px 40px rgba(239, 68, 68, .2);
        }

        .q2 {
            background: rgba(59, 130, 246, .08);
            border-color: rgba(59, 130, 246, .3);
        }

        .q2:hover {
            box-shadow: 0 12px 40px rgba(59, 130, 246, .2);
        }

        .q3 {
            background: rgba(245, 158, 11, .08);
            border-color: rgba(245, 158, 11, .3);
        }

        .q3:hover {
            box-shadow: 0 12px 40px rgba(245, 158, 11, .2);
        }

        .q4 {
            background: rgba(107, 114, 128, .08);
            border-color: rgba(107, 114, 128, .3);
        }

        .q4:hover {
            box-shadow: 0 12px 40px rgba(107, 114, 128, .2);
        }

        .q-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .q-num {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .q-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 2px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .q-desc {
            font-size: 11px;
            color: var(--muted);
        }

        .q-tasks {
            margin-top: 10px;
            space-y: 4px;
        }

        .q-task {
            font-size: 11px;
            color: #94a3b8;
            padding: 3px 0;
            border-top: 1px solid rgba(255, 255, 255, .05);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .q-task::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
            opacity: .5;
            flex-shrink: 0;
        }

        /* SECTIONS */
        section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 96px 24px;
        }

        .section-badge {
            font-size: 12px;
            font-weight: 700;
            color: #a78bfa;
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 800;
            margin-bottom: 16px;
        }

        .section-sub {
            font-size: 16px;
            color: var(--muted);
            max-width: 560px;
        }

        .separator {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border), transparent);
            max-width: 1200px;
            margin: 0 auto;
        }

        /* HOW IT WORKS */
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            margin-top: 48px;
        }

        .step {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            transition: border-color .2s, transform .2s;
        }

        .step:hover {
            border-color: #4c1d95;
            transform: translateY(-2px);
        }

        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .step h3 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .step p {
            font-size: 13px;
            color: var(--muted);
        }

        /* FEATURES */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 48px;
        }

        .feat {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            gap: 16px;
            transition: border-color .2s;
        }

        .feat:hover {
            border-color: #7c3aed60;
        }

        .feat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .feat h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .feat p {
            font-size: 13px;
            color: var(--muted);
        }

        /* CTA */
        .cta-section {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 24px;
            max-width: 860px;
            margin: 0 auto 96px;
            padding: 64px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-glow {
            position: absolute;
            top: -80px;
            left: 50%;
            transform: translateX(-50%);
            width: 400px;
            height: 400px;
            background: radial-gradient(ellipse, #7c3aed30 0%, transparent 70%);
            pointer-events: none;
        }

        .cta-section h2 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 800;
            margin-bottom: 16px;
            position: relative;
        }

        .cta-section p {
            color: var(--muted);
            margin-bottom: 32px;
            font-size: 16px;
            position: relative;
        }

        .cta-demo {
            display: inline-block;
            margin-top: 24px;
            font-size: 13px;
            color: var(--muted);
        }

        .cta-demo code {
            background: #1e293b;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 12px;
            color: #a78bfa;
        }

        /* FOOTER */
        footer {
            border-top: 1px solid var(--border);
            padding: 32px 24px;
            text-align: center;
        }

        footer p {
            font-size: 13px;
            color: var(--muted);
        }

        /* RESPONSIVE */
        @media (max-width: 640px) {
            .matrix-grid {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                padding: 0 4px;
            }

            .q-card {
                padding: 10px 8px;
                border-radius: 12px;
            }

            .q-name {
                font-size: 12px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .q-num {
                font-size: 9px;
            }

            .q-desc {
                font-size: 8px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: block;
            }

            .q-tasks {
                display: none;
            }

            .matrix-label-top {
                display: flex;
                font-size: 9px;
                padding: 0 12px;
                margin-bottom: 4px;
            }

            .matrix-side-text {
                display: flex;
                font-size: 9px;
            }

            .matrix-label-side {
                gap: 4px;
            }
        }

        /* ANIMATIONS */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .anim {
            animation: fadeUp .6s ease forwards;
        }

        .anim-d1 {
            animation-delay: .1s;
            opacity: 0;
        }

        .anim-d2 {
            animation-delay: .2s;
            opacity: 0;
        }

        .anim-d3 {
            animation-delay: .3s;
            opacity: 0;
        }
    </style>
</head>

<body>

    <!-- NAV -->
    <nav>
        <div class="nav-inner">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="logo">
                <div class="logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="8" height="8" rx="1" />
                        <rect x="13" y="3" width="8" height="8" rx="1" />
                        <rect x="3" y="13" width="8" height="8" rx="1" />
                        <rect x="13" y="13" width="8" height="8" rx="1" />
                    </svg>
                </div>
                <div class="logo-text">sientia<span>MTX</span></div>
            </a>
            <div class="nav-links">
                <!-- Locale switcher -->
                <div class="locale-pill">
                    <a href="{{ route('locale.switch', 'en') }}"
                        class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                    <a href="{{ route('locale.switch', 'es') }}"
                        class="{{ app()->getLocale() === 'es' ? 'active' : '' }}">ES</a>
                </div>
                @auth
                    <a href="{{ route('teams.index') }}" class="btn btn-primary">
                        {{ app()->getLocale() === 'es' ? 'Ir a mis equipos' : 'My Teams' }} →
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="btn btn-ghost">{{ app()->getLocale() === 'es' ? 'Iniciar sesión' : 'Log in' }}</a>
                    <a href="{{ route('register') }}"
                        class="btn btn-primary">{{ app()->getLocale() === 'es' ? 'Empezar gratis' : 'Get started' }}</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <div class="hero">
        <div class="hero-badge anim anim-d1">
            ✦
            {{ app()->getLocale() === 'es' ? 'Productividad inteligente para equipos' : 'Smart productivity for teams' }}
        </div>
        <h1 class="hero-title anim anim-d2">
            {{ app()->getLocale() === 'es' ? 'Mucho más que una matriz.' : 'More than just a matrix.' }}<br>
            <span class="gradient">{{ app()->getLocale() === 'es' ? 'El centro de mando de tu equipo.' : 'Your team\'s command center.' }}</span>
        </h1>
        <p class="hero-sub anim anim-d3">
            {{ app()->getLocale() === 'es'
                ? 'sientiaMTX combina la Matriz de Eisenhower, Diagramas de Gantt y Tableros Kanban en un ecosistema inteligente de productividad.'
                : 'sientiaMTX combines the Eisenhower Matrix, Gantt Charts, and Kanban Boards in a smart productivity ecosystem.' }}
        </p>
        <div class="hero-ctas anim anim-d3">
            @auth
                <a href="{{ route('teams.index') }}" class="btn btn-primary btn-hero">
                    {{ app()->getLocale() === 'es' ? '→ Ir al dashboard' : '→ Go to dashboard' }}
                </a>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary btn-hero">
                    {{ app()->getLocale() === 'es' ? '→ Empezar gratis' : '→ Start for free' }}
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline btn-hero">
                    {{ app()->getLocale() === 'es' ? 'Ya tengo cuenta' : 'I have an account' }}
                </a>
            @endauth
        </div>

        <!-- Matrix visual -->
        <div>
            <div class="matrix-label-top">
                <span>{{ app()->getLocale() === 'es' ? '← No urgente' : '← Not Urgent' }}</span>
                <span>{{ app()->getLocale() === 'es' ? ' Urgente →' : 'Urgent →' }}</span>
            </div>
            <div class="matrix-label-side">
                <div class="matrix-side-text">
                    {{ app()->getLocale() === 'es' ? '← No importante · Importante →' : '← Not Important · Important →' }}
                </div>
                <div class="matrix-grid">
                    <div class="q-card q2">
                        <div class="q-num"><span class="q-dot" style="background:#3b82f6"></span>Q2</div>
                        <div class="q-name" style="color:#93c5fd">
                            {{ app()->getLocale() === 'es' ? 'Planifica' : 'Schedule' }}</div>
                        <div class="q-desc">
                            {{ app()->getLocale() === 'es' ? 'Importante · No urgente' : 'Important · Not Urgent' }}
                        </div>
                        <div class="q-tasks">
                            <div class="q-task" style="color:#3b82f6">
                                {{ app()->getLocale() === 'es' ? 'Planificar la estrategia Q2' : 'Plan Q2 strategy' }}
                            </div>
                            <div class="q-task" style="color:#3b82f6">
                                {{ app()->getLocale() === 'es' ? 'Escribir documentación' : 'Write documentation' }}
                            </div>
                        </div>
                    </div>
                    <div class="q-card q1">
                        <div class="q-num"><span class="q-dot" style="background:#ef4444"></span>Q1</div>
                        <div class="q-name" style="color:#fca5a5">
                            {{ app()->getLocale() === 'es' ? 'Haz ahora' : 'Do First' }}</div>
                        <div class="q-desc">
                            {{ app()->getLocale() === 'es' ? 'Importante · Urgente' : 'Important · Urgent' }}</div>
                        <div class="q-tasks">
                            <div class="q-task" style="color:#ef4444">
                                {{ app()->getLocale() === 'es' ? 'Resolver caída del servidor' : 'Fix server outage' }}
                            </div>
                            <div class="q-task" style="color:#ef4444">
                                {{ app()->getLocale() === 'es' ? 'Incidente de seguridad' : 'Security incident' }}
                            </div>
                        </div>
                    </div>
                    <div class="q-card q4">
                        <div class="q-num"><span class="q-dot" style="background:#6b7280"></span>Q4</div>
                        <div class="q-name" style="color:#d1d5db">
                            {{ app()->getLocale() === 'es' ? 'Elimina' : 'Eliminate' }}</div>
                        <div class="q-desc">
                            {{ app()->getLocale() === 'es' ? 'No importante · No urgente' : 'Not Important · Not Urgent' }}
                        </div>
                        <div class="q-tasks">
                            <div class="q-task" style="color:#6b7280">
                                {{ app()->getLocale() === 'es' ? 'Ordenar carpetas antiguas' : 'Sort old folders' }}
                            </div>
                        </div>
                    </div>
                    <div class="q-card q3">
                        <div class="q-num"><span class="q-dot" style="background:#f59e0b"></span>Q3</div>
                        <div class="q-name" style="color:#fcd34d">
                            {{ app()->getLocale() === 'es' ? 'Delega' : 'Delegate' }}</div>
                        <div class="q-desc">
                            {{ app()->getLocale() === 'es' ? 'No importante · Urgente' : 'Not Important · Urgent' }}
                        </div>
                        <div class="q-tasks">
                            <div class="q-task" style="color:#f59e0b">
                                {{ app()->getLocale() === 'es' ? 'Reunión de equipo' : 'Team meeting' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- HOW IT WORKS -->
    <section>
        <div class="section-badge">{{ app()->getLocale() === 'es' ? 'Cómo funciona' : 'How it works' }}</div>
        <h2 class="section-title">
            {{ app()->getLocale() === 'es' ? 'Simple, potente y visual' : 'Simple, powerful and visual' }}</h2>
        <p class="section-sub">
            {{ app()->getLocale() === 'es' ? 'En tres pasos, tu equipo sabe exactamente qué hacer.' : 'In three steps, your team knows exactly what to do.' }}
        </p>
        <div class="steps">
            @php $locale = app()->getLocale(); @endphp
            @foreach ([['1', $locale === 'es' ? 'Crea tu equipo' : 'Create your team', $locale === 'es' ? 'Invita a los miembros de tu equipo y asigna roles de coordinador o usuario.' : 'Invite your team members and assign coordinator or user roles.'], ['2', $locale === 'es' ? 'Añade tareas' : 'Add tasks', $locale === 'es' ? 'Define título, descripción, prioridad (importancia) y urgencia para cada tarea.' : 'Define title, description, priority (importance) and urgency for each task.'], ['3', $locale === 'es' ? 'Visualiza la matriz' : 'View the matrix', $locale === 'es' ? 'sientiaMTX clasifica automáticamente cada tarea en el cuadrante correcto.' : 'sientiaMTX automatically classifies each task in the right quadrant.'], ['4', $locale === 'es' ? 'Actúa con foco' : 'Act with focus', $locale === 'es' ? 'Haz primero lo urgente-importante, planifica el resto, delega y elimina el ruido.' : 'Do urgent-important things first, plan the rest, delegate and cut the noise.']] as [$num, $title, $desc])
                <div class="step">
                    <div class="step-num">{{ $num }}</div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="separator"></div>

    <!-- FEATURES -->
    <section>
        <div class="section-badge">{{ app()->getLocale() === 'es' ? 'Características' : 'Features' }}</div>
        <h2 class="section-title">{{ app()->getLocale() === 'es' ? 'Todo lo que necesitas' : 'Everything you need' }}
        </h2>
        <div class="features">
            @php
                $feats =
                    app()->getLocale() === 'es'
                        ? [
                            [
                                '🎯',
                                'bg:#312e81',
                                'Matriz de Eisenhower',
                                'Priorización inteligente: separa lo urgente de lo importante para dar el primer paso con total claridad.',
                            ],
                            [
                                '📈',
                                'bg:#14532d',
                                'Diagramas de Gantt',
                                'Planificación a largo plazo: gestiona cronogramas complejos y dependencias de forma visual y sencilla.',
                            ],
                            [
                                '📋',
                                'bg:#164e63',
                                'Método Kanban',
                                'Flujo de trabajo ágil: controla el estado de tus tareas diarias moviéndolas a través de tu tablero personalizado.',
                            ],
                            [
                                '👥',
                                'bg:#581c87',
                                'Equipos y Foros',
                                'Colaboración total: gestiona miembros, roles y mantén discusiones de equipo en tiempo real.',
                            ],
                            [
                                '🌍',
                                'bg:#1e3a5f',
                                'Multiidioma y Horas',
                                'Adaptabilidad global: soporte completo para múltiples idiomas y ajustes automáticos de zonas horarias.',
                            ],
                            [
                                '📜',
                                'bg:#7f1d1d',
                                'Auditoría y Control',
                                'Historial completo de cambios por tarea para una trazabilidad perfecta de lo que ocurre en tu equipo.',
                            ],
                        ]
                        : [
                            [
                                '🎯',
                                'bg:#312e81',
                                'Eisenhower Matrix',
                                'Smart prioritization: separate urgent from important to take the first step with total clarity.',
                            ],
                            [
                                '📈',
                                'bg:#14532d',
                                'Gantt Charts',
                                'Long-term planning: manage complex timelines and dependencies visually and easily.',
                            ],
                            [
                                '📋',
                                'bg:#164e63',
                                'Kanban Boards',
                                'Agile workflow: control the state of your daily tasks by moving them through your custom board.',
                            ],
                            [
                                '👥',
                                'bg:#581c87',
                                'Teams & Forums',
                                'Full collaboration: manage members, roles, and maintain team discussions in real time.',
                            ],
                            [
                                '🌍',
                                'bg:#1e3a5f',
                                'Multi-language & Time',
                                'Global adaptivity: full support for multiple languages and automatic timezone adjustments.',
                            ],
                            [
                                '📜',
                                'bg:#7f1d1d',
                                'Audit & Control',
                                'Full task change history for a perfect traceability of what happens in your team.',
                            ],
                        ];
            @endphp
            @foreach ($feats as [$icon, $bg, $title, $desc])
                <div class="feat">
                    <div class="feat-icon" style="{{ $bg }}">{{ $icon }}</div>
                    <div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="separator"></div>

    <!-- CTA -->
    <section style="padding-top:64px">
        <div class="cta-section">
            <div class="cta-glow"></div>
            <h2>{{ app()->getLocale() === 'es' ? '¿Listo para transformar tu productividad?' : 'Ready to transform your productivity?' }}
            </h2>
            <p>{{ app()->getLocale() === 'es' ? 'Empieza a gestionar tus proyectos con MTX, Gantt y Kanban hoy mismo.' : 'Start managing your projects with MTX, Gantt, and Kanban today.' }}
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative">
                @auth
                    <a href="{{ route('teams.index') }}" class="btn btn-primary btn-hero">→
                        {{ app()->getLocale() === 'es' ? 'Ir al dashboard' : 'Go to dashboard' }}</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary btn-hero">→
                        {{ app()->getLocale() === 'es' ? 'Registrarse gratis' : 'Register for free' }}</a>
                    <a href="{{ route('login') }}"
                        class="btn btn-outline btn-hero">{{ app()->getLocale() === 'es' ? 'Iniciar sesión' : 'Log in' }}</a>
                @endauth
            </div>
            <p class="cta-demo">
                {{ app()->getLocale() === 'es' ? 'Cuenta demo: ' : 'Demo account: ' }}
                <code>demo@sientia.com</code> / <code>12345678</code>
            </p>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <p>sientia<strong>MTX</strong> v{{ config('app.version', '0.0.1') }} ·
            {{ app()->getLocale() === 'es' ? 'MTX, Gantt y Kanban para equipos enfocados' : 'MTX, Gantt and Kanban for focused teams' }}
            · <a href="{{ route('login') }}"
                style="color:#7c3aed">{{ app()->getLocale() === 'es' ? 'Entrar' : 'Login' }}</a></p>
        
        <div style="margin-top: 12px; display: flex; justify-content: center; gap: 16px; font-size: 11px; color: var(--muted);">
            <a href="{{ route('privacy') }}" class="hover:text-white transition-colors">
                {{ app()->getLocale() === 'es' ? 'Privacidad' : 'Privacy' }}
            </a>
            <a href="{{ route('terms') }}" class="hover:text-white transition-colors">
                {{ app()->getLocale() === 'es' ? 'Términos' : 'Terms' }}
            </a>
            <a href="{{ route('cookies') }}" class="hover:text-white transition-colors">
                {{ app()->getLocale() === 'es' ? 'Cookies' : 'Cookies' }}
            </a>
        </div>
    </footer>

</body>

</html>
