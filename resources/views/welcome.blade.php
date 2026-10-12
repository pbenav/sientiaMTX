<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>sientiaMTX —
        {{ __('Unified Activity & Productivity Management') }}
    </title>
    <meta name="description"
        content="{{ __('Manage much more than tasks: activities, surveys, appointments, documents, and collaboration, all powered by AI.') }}">

    <!-- Metadatos de Nombre de Sitio para Google (SEO / Open Graph) -->
    <meta property="og:site_name" content="Sientia Open Labs">
    <meta property="og:title" content="sientiaMTX — {{ __('Unified Activity & Productivity Management') }}">
    <meta property="og:description" content="{{ __('Manage much more than tasks: activities, surveys, appointments, documents, and collaboration, all powered by AI.') }}">
    <meta property="og:url" content="https://mtx.sientia.com/">
    <meta property="og:type" content="website">

    <!-- Datos Estructurados Schema.org para especificar el Nombre del Sitio oficial a Google -->
    <script type="application/ld+json">
    {
      "@@context" : "https://schema.org",
      "@@type" : "WebSite",
      "name" : "Sientia Open Labs",
      "alternateName": ["sientiaMTX", "Sientia Open Source Lab"],
      "url" : "https://mtx.sientia.com/"
    }
    </script>

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
                        {{ __('My Teams') }} →
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="btn btn-ghost">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}"
                        class="btn btn-primary">{{ __('Get started') }}</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <div class="hero">
        <div class="hero-badge anim anim-d1">
            ✦
            {{ __('Smart productivity for teams') }}
        </div>
        <h1 class="hero-title anim anim-d2">
            {{ __('More than just a matrix.') }}<br>
            <span class="gradient">{{ __('Your team\'s command center.') }}</span>
        </h1>
        <p class="hero-sub anim anim-d3">
            {{ __('sientiaMTX evolves classic management. Centralize activities, surveys, documents, and appointments in a smart productivity ecosystem.') }}
        </p>
        <div class="hero-ctas anim anim-d3">
            @auth
                <a href="{{ route('teams.index') }}" class="btn btn-primary btn-hero">
                    {{ __('→ Go to dashboard') }}
                </a>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary btn-hero">
                    {{ __('→ Start for free') }}
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline btn-hero">
                    {{ __('I have an account') }}
                </a>
            @endauth
        </div>

        <!-- Matrix visual -->
        <div>
            <div class="matrix-label-top">
                <span>{{ __('← Not Urgent') }}</span>
                <span>{{ __('Urgent →') }}</span>
            </div>
            <div class="matrix-label-side">
                <div class="matrix-side-text">
                    {{ __('← Not Important · Important →') }}
                </div>
                <div class="matrix-grid">
                    <div class="q-card q2">
                        <div class="q-num"><span class="q-dot" style="background:#3b82f6"></span>Q2</div>
                        <div class="q-name" style="color:#93c5fd">
                            {{ __('Schedule') }}</div>
                        <div class="q-desc">
                            {{ __('Important · Not Urgent') }}
                        </div>
                        <div class="q-tasks">
                            <div class="q-task" style="color:#3b82f6">
                                {{ __('Agreement: Q2 Strategy') }}
                            </div>
                            <div class="q-task" style="color:#3b82f6">
                                {{ __('Document: User manual') }}
                            </div>
                        </div>
                    </div>
                    <div class="q-card q1">
                        <div class="q-num"><span class="q-dot" style="background:#ef4444"></span>Q1</div>
                        <div class="q-name" style="color:#fca5a5">
                            {{ __('Do First') }}</div>
                        <div class="q-desc">
                            {{ __('Important · Urgent') }}</div>
                        <div class="q-tasks">
                            <div class="q-task" style="color:#ef4444">
                                {{ __('Meeting: Critical Incident') }}
                            </div>
                            <div class="q-task" style="color:#ef4444">
                                {{ __('Appointment: Security Audit') }}
                            </div>
                        </div>
                    </div>
                    <div class="q-card q4">
                        <div class="q-num"><span class="q-dot" style="background:#6b7280"></span>Q4</div>
                        <div class="q-name" style="color:#d1d5db">
                            {{ __('Eliminate') }}</div>
                        <div class="q-desc">
                            {{ __('Not Important · Not Urgent') }}
                        </div>
                        <div class="q-tasks">
                            <div class="q-task" style="color:#6b7280">
                                {{ __('Library: Organize files') }}
                            </div>
                        </div>
                    </div>
                    <div class="q-card q3">
                        <div class="q-num"><span class="q-dot" style="background:#f59e0b"></span>Q3</div>
                        <div class="q-name" style="color:#fcd34d">
                            {{ __('Delegate') }}</div>
                        <div class="q-desc">
                            {{ __('Not Important · Urgent') }}
                        </div>
                        <div class="q-tasks">
                            <div class="q-task" style="color:#f59e0b">
                                {{ __('Survey: Work environment') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- HOW IT WORKS -->
    <section>
        <div class="section-badge">{{ __('How it works') }}</div>
        <h2 class="section-title">
            {{ __('Simple, powerful and visual') }}</h2>
        <p class="section-sub">
            {{ __('In three steps, your team knows exactly what to do.') }}
        </p>
        <div class="steps">
            @foreach ([
                ['1', __('Create your team'), __('Invite members and organize work through detailed roles and permissions.')],
                ['2', __('Add activities'), __('Log documents, meetings, surveys, or appointments, and define their priority and urgency.')],
                ['3', __('View the ecosystem'), __('sientiaMTX automatically classifies each activity into matrices, boards, and timelines.')],
                ['4', __('Act with AI'), __('Delegate to Ax.ia, handle appointments and surveys, and close the most urgent agreements first.')]
            ] as [$num, $title, $desc])
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
        <div class="section-badge">{{ __('Features') }}</div>
        <h2 class="section-title">{{ __('Everything you need') }}
        </h2>
        <div class="features">
            @php
                $feats = [
                    ['🎯', 'bg:#312e81', __('Eisenhower Matrix'), __('Smart prioritization: separate urgent from important to take the first step with total clarity.')],
                    ['📈', 'bg:#14532d', __('Gantt Charts'), __('Long-term planning: manage complex timelines and dependencies visually and easily.')],
                    ['📋', 'bg:#164e63', __('Kanban Boards'), __('Agile workflow: control the state of your daily activities by moving them through your custom board.')],
                    ['⚡', 'bg:#7c2d12', __('Flexible Activities'), __('More than tasks: manage documents, meetings, reminders, and agreements in a unified way.')],
                    ['📚', 'bg:#4a044e', __('Document Library'), __('Knowledge hub: store, organize, and share your team\'s essential documents seamlessly.')],
                    ['📝', 'bg:#064e3b', __('Survey System'), __('Continuous feedback: create custom surveys, publish them, and analyze results instantly.')],
                    ['📅', 'bg:#831843', __('Appointments Manager'), __('Schedule control: complete module to organize visits with notifications and capacity limits.')],
                    ['🌐', 'bg:#0f172a', __('Web Microsites'), __('Digital presence: generate and deploy public mini-sites instantly without writing any code.')],
                    ['🤖', 'bg:#022c22', __('Integrated AI (Ax.ia)'), __('AI Assistant: automatic summaries, survey generation, stress monitoring, and productivity insights.')],
                    ['👥', 'bg:#581c87', __('Teams & Forums'), __('Full collaboration: manage members, roles, and maintain team discussions in real time.')],
                    ['🌍', 'bg:#1e3a5f', __('Multi-language & Time'), __('Global adaptivity: full support for multiple languages and automatic timezone adjustments.')],
                    ['📜', 'bg:#7f1d1d', __('Audit & Control'), __('Perfect traceability: a detailed change history ensures total team transparency.')],
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
            <h2>{{ __('Ready to transform your productivity?') }}
            </h2>
            <p>{{ __('Start managing your projects with MTX, Gantt, and Kanban today.') }}
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative">
                @auth
                    <a href="{{ route('teams.index') }}" class="btn btn-primary btn-hero">→
                        {{ __('Go to dashboard') }}</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary btn-hero">→
                        {{ __('Register for free') }}</a>
                    <a href="{{ route('login') }}"
                        class="btn btn-outline btn-hero">{{ __('Log in') }}</a>
                @endauth
            </div>
            <p class="cta-demo">
                {{ __('Demo account:') }} 
                <code>demo@sientia.com</code> / <code>12345678</code>
            </p>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <p>
            © {{ date('Y') }} <a href="https://www.sientia.com" style="color:white; font-weight: 700;">Sientia Open Source Lab</a> · 
            sientia<strong>MTX</strong> v{{ config('app.version', '0.0.1') }} ·
            {{ __('MTX, Gantt and Kanban for focused teams') }}
        </p>
        
        <div style="margin-top: 16px; display: flex; justify-content: center; gap: 24px; font-size: 13px; font-weight: 500;">
            <a href="https://www.patreon.com/cw/sientia" target="_blank"
                class="hover:text-orange-400 transition-colors flex items-center gap-2" style="color:#f97316">
                <i class="fab fa-patreon"></i>
                Patreon
            </a>
            <span style="color:var(--border)">|</span>
            <a href="https://buymeacoffee.com/sientia" target="_blank"
                class="hover:text-yellow-400 transition-colors flex items-center gap-2" style="color:#eab308">
                <i class="fas fa-coffee"></i>
                Buy me a coffee
            </a>
        </div>
        
        <div style="margin-top: 12px; display: flex; justify-content: center; gap: 16px; font-size: 11px; color: var(--muted);">
            <a href="{{ route('privacy') }}" class="hover:text-white transition-colors">
                {{ __('Privacy') }}
            </a>
            <a href="{{ route('terms') }}" class="hover:text-white transition-colors">
                {{ __('Terms') }}
            </a>
            <a href="{{ route('cookies') }}" class="hover:text-white transition-colors">
                {{ __('Cookies') }}
            </a>
        </div>
    </footer>

</body>

</html>
