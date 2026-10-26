<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ current_dir() }}" x-data="{ mobile: false }" x-init="$store.ui.init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', __('Centres de formation — Education Centers'))">
    @include('layouts.partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script>
        if (localStorage.getItem('ec-theme') === 'dark' || (!localStorage.getItem('ec-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
<body class="relative min-h-screen bg-white text-slate-800 dark:bg-slate-950 dark:text-slate-100 overflow-x-hidden">
    {{-- Aurores interactives — site vitrine uniquement (absentes des dashboards,
         du tactile et de prefers-reduced-motion ; voir .ec-aurora dans app.css). --}}
    <canvas id="ec-aurora" class="ec-aurora" aria-hidden="true"></canvas>

    @php
        $navItems = [
            ['route' => 'home', 'pattern' => 'home', 'label' => __('Accueil')],
            ['route' => 'public.centers', 'pattern' => 'public.centers*', 'label' => __('Centres')],
            ['route' => 'public.formations', 'pattern' => 'public.formations*', 'label' => __('Formations')],
            ['route' => 'public.announcements', 'pattern' => 'public.announcements*', 'label' => __('Actualités')],
            ['route' => 'public.contact', 'pattern' => 'public.contact', 'label' => __('Contact')],
        ];
    @endphp

    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 shadow-[0_1px_10px_rgba(15,23,42,0.05)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex h-[4.25rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/30">
                    <x-icon name="academic" class="h-5 w-5" />
                </span>
                <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Education Centers') }}</span>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex dark:text-slate-300" aria-label="{{ __('Navigation') }}">
                @foreach($navItems as $item)
                    @php($active = request()->routeIs($item['pattern']))
                    <a href="{{ route($item['route']) }}"
                       @if($active) aria-current="page" @endif
                       class="relative py-1 transition-colors hover:text-brand-600 dark:hover:text-brand-400 {{ $active ? 'text-brand-600 dark:text-brand-400' : '' }}">
                        {{ $item['label'] }}
                        <span class="absolute -bottom-1 start-0 h-0.5 rounded-full bg-brand-600 transition-all {{ $active ? 'w-full' : 'w-0' }}"></span>
                    </a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                @include('layouts.partials.language-switcher')
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                        x-on:click="$store.ui.toggleTheme()" aria-label="{{ __('Basculer le thème') }}">
                    <x-icon name="moon" class="h-5 w-5" x-show="!$store.ui.dark" />
                    <x-icon name="sun" class="h-5 w-5" x-show="$store.ui.dark" x-cloak />
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="ec-btn-primary">
                        <x-icon name="home" class="h-4 w-4 rtl:-scale-x-100" />
                        {{ __('Tableau de bord') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="ec-btn-secondary">{{ __('Connexion') }}</a>
                    <a href="{{ route('public.apply') }}" class="ec-btn-primary shadow-md shadow-brand-600/25">
                        <x-icon name="user" class="h-4 w-4" />
                        {{ __('S’inscrire') }}
                    </a>
                @endauth
            </div>

            <button type="button"
                    class="rounded-xl p-2 text-slate-600 transition hover:bg-slate-100 lg:hidden dark:text-slate-300 dark:hover:bg-slate-800"
                    x-on:click="mobile = !mobile"
                    :aria-expanded="mobile.toString()"
                    aria-label="{{ __('Menu') }}">
                <x-icon name="menu" class="h-6 w-6" x-show="!mobile" />
                <x-icon name="x" class="h-6 w-6" x-show="mobile" x-cloak />
            </button>
        </div>

        <div x-show="mobile" x-cloak x-collapse class="border-t border-slate-200 bg-white px-4 py-4 lg:hidden dark:border-slate-800 dark:bg-slate-950">
            <nav class="flex flex-col gap-1 text-sm font-semibold text-slate-700 dark:text-slate-200" aria-label="{{ __('Navigation') }}">
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="rounded-xl px-3 py-2.5 transition hover:bg-brand-50 hover:text-brand-700 dark:hover:bg-slate-800 {{ request()->routeIs($item['pattern']) ? 'bg-brand-50 text-brand-700 dark:bg-slate-800 dark:text-brand-300' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    @include('layouts.partials.language-switcher')
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors"
                            x-on:click="$store.ui.toggleTheme()" aria-label="{{ __('Basculer le thème') }}">
                        <x-icon name="moon" class="h-5 w-5" x-show="!$store.ui.dark" />
                        <x-icon name="sun" class="h-5 w-5" x-show="$store.ui.dark" x-cloak />
                    </button>
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="ec-btn-primary w-full">{{ __('Tableau de bord') }}</a>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('login') }}" class="ec-btn-secondary w-full">{{ __('Connexion') }}</a>
                        <a href="{{ route('public.apply') }}" class="ec-btn-primary w-full">{{ __('S’inscrire') }}</a>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    @include('layouts.partials.toasts')
    <main class="relative z-10">@yield('content')</main>

    <footer class="relative z-10 bg-slate-950 text-slate-300">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                        <x-icon name="academic" class="h-5 w-5" />
                    </span>
                    <span class="text-lg font-bold tracking-tight text-white">{{ __('Education Centers') }}</span>
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-slate-400">{{ __('Plateforme de gestion des centres de formation') }}</p>
            </div>

            <nav aria-label="{{ __('Navigation') }}">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('Navigation') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="transition hover:text-white">{{ __('Accueil') }}</a></li>
                    <li><a href="{{ route('public.centers') }}" class="transition hover:text-white">{{ __('Centres') }}</a></li>
                    <li><a href="{{ route('public.formations') }}" class="transition hover:text-white">{{ __('Formations') }}</a></li>
                    <li><a href="{{ route('public.announcements') }}" class="transition hover:text-white">{{ __('Actualités') }}</a></li>
                </ul>
            </nav>

            <nav aria-label="{{ __('Liens utiles') }}">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('Liens utiles') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('public.apply') }}" class="transition hover:text-white">{{ __('Candidature') }}</a></li>
                    <li><a href="{{ route('public.contact') }}" class="transition hover:text-white">{{ __('Contact') }}</a></li>
                    <li><a href="{{ route('public.verify-certificate') }}" class="transition hover:text-white">{{ __('Vérifier un document') }}</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-white">{{ __('Connexion') }}</a></li>
                </ul>
            </nav>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('Contact') }}</h3>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">{{ __('Une question ? Notre équipe vous répond.') }}</p>
                <a href="{{ route('public.contact') }}" class="ec-btn-primary mt-4 !bg-white !text-slate-900 hover:!bg-slate-100">
                    {{ __('Nous contacter') }}
                    <x-icon name="arrow-right" class="h-4 w-4 rtl:-scale-x-100" />
                </a>
            </div>
        </div>

        <div class="border-t border-slate-800/80">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} {{ __('Education Centers') }}. {{ __('Tous droits réservés.') }}</p>
                <div class="flex items-center gap-5">
                    <a href="{{ route('public.contact') }}" class="transition hover:text-white">{{ __('Contact') }}</a>
                    <a href="{{ route('public.verify-certificate') }}" class="transition hover:text-white">{{ __('Vérifier un document') }}</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        /**
         * ANIMATION — INTERACTIVE AURORA WAVES
         * Site vitrine public d'Education Centers uniquement.
         *
         * - Grande lumière diffuse asymétrique et vivante qui suit le curseur (400-600px).
         * - Vagues lumineuses fluides (rubans ondulatoires multi-harmoniques) générées par les mouvements.
         * - Déformation organique (water ripples / curtains d'aurore boréale) sans cercles ni anneaux géométriques.
         * - Palette dégradée violet, bleu et rose se mélangeant progressivement.
         * - Expansion progressive, dissipation et disparition en douceur.
         * - Stabilisation lors de l'arrêt du curseur.
         * - Arrière-plan transparent z-0 sous le contenu du site (aucun élément masqué).
         * - Désactivé sur tactile et si prefers-reduced-motion est actif.
         */
        (() => {
            const canvas = document.getElementById('ec-aurora');
            if (!canvas) return;

            // Désactivation sur écrans tactiles ou si l'utilisateur demande moins de mouvement
            const motionMedia = window.matchMedia('(prefers-reduced-motion: reduce)');
            const pointerMedia = window.matchMedia('(hover: hover) and (pointer: fine)');
            if (motionMedia.matches || !pointerMedia.matches) {
                canvas.style.display = 'none';
                return;
            }

            const ctx = canvas.getContext('2d', { alpha: true });
            if (!ctx) {
                canvas.remove();
                return;
            }

            // Downsampling interne (facteur 2) pour un flou d'aurore naturel et d'excellentes performances GPU
            const DOWNSAMPLE = 2;
            let width = 0;
            let height = 0;

            function resize() {
                width = canvas.width = Math.ceil(window.innerWidth / DOWNSAMPLE);
                height = canvas.height = Math.ceil(window.innerHeight / DOWNSAMPLE);
            }
            resize();

            // Palette d'aurore boréale : violets, bleus et roses
            const PALETTE = [
                { r: 139, g: 92,  b: 246 }, // Violet #8b5cf6
                { r: 99,  g: 102, b: 241 }, // Indigo #6366f1
                { r: 59,  g: 130, b: 246 }, // Bleu #3b82f6
                { r: 56,  g: 189, b: 248 }, // Cyan #38bdf8
                { r: 236, g: 72,  b: 153 }, // Rose #ec4899
                { r: 217, g: 70,  b: 239 }  // Magenta #d946ef
            ];

            // Curseur et tête lumineuse
            const mouse = { x: -2000, y: -2000, active: false, speed: 0, lastX: 0, lastY: 0, lastTime: 0 };
            const head = { x: -2000, y: -2000, opacity: 0, energy: 0 };

            // Vagues actives
            const MAX_WAVES = 14;
            const waves = [];
            let lastSpawnTime = 0;
            let animId = null;
            let lastFrameTime = performance.now();
            let time = 0;

            function isDarkMode() {
                return document.documentElement.classList.contains('dark');
            }

            // Émission d'une vague lumineuse fluide (ruban d'aurore)
            function spawnWave(x, y, vx, vy, speed) {
                if (waves.length >= MAX_WAVES) waves.shift();

                const moveAngle = Math.atan2(vy, vx);
                // Angle légèrement transversal pour faire s'épanouir la vague le long et autour du sillage
                const spread = (Math.random() - 0.5) * 0.7;
                const waveAngle = moveAngle + Math.PI / 2 + spread;

                // Énergie normalisée selon la vitesse (mouvements rapides = plus dynamiques)
                const energy = Math.min(1.0, Math.max(0.18, speed / 32));

                // Sélection harmonieuse de 3 couleurs de la palette
                const c1 = PALETTE[Math.floor(Math.random() * PALETTE.length)];
                const c2 = PALETTE[Math.floor(Math.random() * PALETTE.length)];
                const c3 = PALETTE[Math.floor(Math.random() * PALETTE.length)];

                waves.push({
                    x: x,
                    y: y,
                    driftX: Math.cos(moveAngle) * (0.9 + Math.random() * 0.8) + Math.cos(waveAngle) * (Math.random() - 0.5) * 1.4,
                    driftY: Math.sin(moveAngle) * (0.9 + Math.random() * 0.8) + Math.sin(waveAngle) * (Math.random() - 0.5) * 1.4,
                    angle: waveAngle,
                    span: (190 + energy * 150 + Math.random() * 40), // 380px à 680px à l'écran
                    thickness: (36 + energy * 34 + Math.random() * 16),
                    amplitude: (24 + energy * 36 + Math.random() * 12),
                    freq1: 1.8 + Math.random() * 1.3,
                    freq2: 3.1 + Math.random() * 1.8,
                    phase: Math.random() * Math.PI * 2,
                    waveSpeed: 1.2 + energy * 1.4,
                    age: 0,
                    ttl: 2.8 + energy * 1.3, // 2.8s à 4.1s
                    colors: [c1, c2, c3],
                    energy: energy
                });
            }

            // Rendu d'une vague en ruban ondulatoire organique
            function drawWave(wave, dt, dark) {
                wave.age += dt / wave.ttl;
                if (wave.age >= 1) return false;

                // Propagation douce dans le background
                wave.x += wave.driftX * dt * 45;
                wave.y += wave.driftY * dt * 45;

                // Expansion progressive en envergure et épaisseur
                const growth = 1 + wave.age * 0.95;
                const curSpan = wave.span * growth;
                const curThickness = wave.thickness * (1 + wave.age * 0.75);
                const curAmp = wave.amplitude * (1 - wave.age * 0.3);

                // Enveloppe d'opacité fluide (montée rapide, dissipation douce vers 0)
                const envelope = Math.sin(Math.pow(wave.age, 0.7) * Math.PI);
                const maxAlpha = dark ? (0.26 + wave.energy * 0.16) : (0.16 + wave.energy * 0.12);
                const alpha = envelope * maxAlpha;

                if (alpha <= 0.005) return true;

                const cosA = Math.cos(wave.angle);
                const sinA = Math.sin(wave.angle);
                const normX = -sinA;
                const normY = cosA;

                const segments = 6;
                const crest = [];
                const trough = [];

                // Calcul des crêtes et creux avec déformations multi-fréquences
                for (let i = 0; i <= segments; i++) {
                    const u = (i / segments) - 0.5; // de -0.5 à +0.5
                    const harm1 = Math.sin(u * Math.PI * wave.freq1 + time * wave.waveSpeed + wave.phase);
                    const harm2 = Math.cos(u * Math.PI * wave.freq2 - time * (wave.waveSpeed * 0.65) + wave.phase * 1.6);
                    const waveDisplace = (harm1 * 0.7 + harm2 * 0.3) * curAmp;

                    // Effilage fluide aux deux extrémités de la vague
                    const taper = Math.sin((i / segments) * Math.PI);
                    const halfThick = (curThickness * 0.5 * taper) + 3;

                    const bx = wave.x + cosA * (u * curSpan);
                    const by = wave.y + sinA * (u * curSpan);

                    crest.push({
                        x: bx + normX * (waveDisplace + halfThick),
                        y: by + normY * (waveDisplace + halfThick)
                    });
                    trough.push({
                        x: bx + normX * (waveDisplace - halfThick),
                        y: by + normY * (waveDisplace - halfThick)
                    });
                }

                ctx.save();
                ctx.beginPath();
                ctx.moveTo(crest[0].x, crest[0].y);
                for (let i = 1; i < crest.length; i++) {
                    const prev = crest[i - 1];
                    const curr = crest[i];
                    ctx.quadraticCurveTo(prev.x, prev.y, (prev.x + curr.x) / 2, (prev.y + curr.y) / 2);
                }
                ctx.lineTo(crest[crest.length - 1].x, crest[crest.length - 1].y);

                ctx.lineTo(trough[trough.length - 1].x, trough[trough.length - 1].y);
                for (let i = trough.length - 2; i >= 0; i--) {
                    const next = trough[i + 1];
                    const curr = trough[i];
                    ctx.quadraticCurveTo(next.x, next.y, (next.x + curr.x) / 2, (next.y + curr.y) / 2);
                }
                ctx.closePath();

                // Dégradé linéaire transversal intégrant violet, bleu et rose
                const pStart = crest[0];
                const pEnd = crest[crest.length - 1];
                const grad = ctx.createLinearGradient(pStart.x, pStart.y, pEnd.x, pEnd.y);
                const [c1, c2, c3] = wave.colors;

                grad.addColorStop(0, `rgba(${c1.r}, ${c1.g}, ${c1.b}, 0)`);
                grad.addColorStop(0.25, `rgba(${c1.r}, ${c1.g}, ${c1.b}, ${alpha})`);
                grad.addColorStop(0.55, `rgba(${c2.r}, ${c2.g}, ${c2.b}, ${alpha * 0.95})`);
                grad.addColorStop(0.85, `rgba(${c3.r}, ${c3.g}, ${c3.b}, ${alpha * 0.85})`);
                grad.addColorStop(1, `rgba(${c3.r}, ${c3.g}, ${c3.b}, 0)`);

                ctx.fillStyle = grad;
                ctx.fill();
                ctx.restore();

                return true;
            }

            // Dessin de la grande lumière diffuse qui suit le curseur (multi-lobes organiques sans cercle géométrique)
            function drawHeadGlow(x, y, energy, alpha, dark) {
                if (alpha <= 0.005) return;

                // Zone diffuse ~400px à 600px
                const baseRadius = 145 + energy * 65;

                // 3 lobes orbitaux vivants (violet, indigo/bleu, rose)
                const lobes = [
                    {
                        color: PALETTE[0], // Violet
                        ox: Math.cos(time * 0.8) * 34,
                        oy: Math.sin(time * 0.7) * 24,
                        r: baseRadius * (1 + Math.sin(time * 1.1) * 0.12),
                        weight: 0.85
                    },
                    {
                        color: PALETTE[1], // Indigo / Bleu
                        ox: Math.cos(time * 0.9 + 2.1) * 38,
                        oy: Math.sin(time * 0.8 + 2.1) * 28,
                        r: baseRadius * 0.92 * (1 + Math.cos(time * 0.9 + 1.4) * 0.1),
                        weight: 0.75
                    },
                    {
                        color: PALETTE[4], // Rose
                        ox: Math.cos(time * 0.7 + 4.2) * 30,
                        oy: Math.sin(time * 1.0 + 4.2) * 30,
                        r: baseRadius * 0.82 * (1 + Math.sin(time * 0.8 + 3.0) * 0.14),
                        weight: 0.65
                    }
                ];

                lobes.forEach((lobe) => {
                    const lx = x + lobe.ox;
                    const ly = y + lobe.oy;
                    const lr = lobe.r;
                    const lAlpha = alpha * lobe.weight * (dark ? 0.32 : 0.20);

                    ctx.save();
                    ctx.translate(lx, ly);
                    ctx.rotate(time * 0.15 + lobe.weight);
                    ctx.scale(1.15, 0.85); // Déformation asymétrique vivante

                    const g = ctx.createRadialGradient(0, 0, 0, 0, 0, lr);
                    const c = lobe.color;
                    g.addColorStop(0, `rgba(${c.r}, ${c.g}, ${c.b}, ${lAlpha})`);
                    g.addColorStop(0.4, `rgba(${c.r}, ${c.g}, ${c.b}, ${lAlpha * 0.55})`);
                    g.addColorStop(0.75, `rgba(${c.r}, ${c.g}, ${c.b}, ${lAlpha * 0.18})`);
                    g.addColorStop(1, 'rgba(0,0,0,0)');

                    ctx.fillStyle = g;
                    ctx.beginPath();
                    ctx.arc(0, 0, lr, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();
                });
            }

            // Boucle de rendu cadencée avec requestAnimationFrame
            function render(now) {
                animId = null;
                const dt = Math.min(0.06, (now - lastFrameTime) / 1000 || 0.016);
                lastFrameTime = now;
                time += dt;

                const dark = isDarkMode();

                // Poursuite du curseur avec un léger retard naturel (lerp fluide)
                if (mouse.active) {
                    const targetX = mouse.x / DOWNSAMPLE;
                    const targetY = mouse.y / DOWNSAMPLE;

                    if (head.x < -1000) {
                        head.x = targetX;
                        head.y = targetY;
                    }

                    const dx = targetX - head.x;
                    const dy = targetY - head.y;
                    head.x += dx * 0.085;
                    head.y += dy * 0.085;

                    head.opacity += (1 - head.opacity) * Math.min(1, dt * 3.5);
                    const dist = Math.hypot(dx, dy);
                    head.energy += (Math.min(1, dist / 40) - head.energy) * Math.min(1, dt * 4.0);
                } else {
                    // Si le curseur quitte la fenêtre, disparition douce
                    head.opacity += (0 - head.opacity) * Math.min(1, dt * 2.2);
                    head.energy += (0 - head.energy) * Math.min(1, dt * 2.2);
                }

                // Émission de vagues lors du mouvement (rapide = dynamique, lent = léger)
                if (mouse.active && mouse.speed > 2.5) {
                    const interval = mouse.speed > 20 ? 65 : 120;
                    if (now - lastSpawnTime > interval) {
                        lastSpawnTime = now;
                        const vx = (mouse.x - mouse.lastX) / DOWNSAMPLE;
                        const vy = (mouse.y - mouse.lastY) / DOWNSAMPLE;
                        spawnWave(head.x, head.y, vx, vy, mouse.speed);
                    }
                }

                ctx.clearRect(0, 0, width, height);

                // 'screen' en dark mode pour magnifier la luminescence violette/bleue/rose
                // 'source-over' en light mode pour conserver des teintes douces et fraîches
                ctx.globalCompositeOperation = dark ? 'screen' : 'source-over';

                // 1. Dessin des vagues
                for (let i = waves.length - 1; i >= 0; i--) {
                    const alive = drawWave(waves[i], dt, dark);
                    if (!alive) {
                        waves.splice(i, 1);
                    }
                }

                // 2. Dessin de la lumière de tête diffuse
                if (head.opacity > 0.01 && head.x > -200) {
                    drawHeadGlow(head.x, head.y, head.energy, head.opacity, dark);
                }

                // Stabilisation : si les vagues se sont dissipées et la lumière est calme, suspendre ou continuer
                const needsUpdate = waves.length > 0 || (mouse.active && head.opacity > 0.02) || head.opacity > 0.02;
                if (needsUpdate) {
                    animId = requestAnimationFrame(render);
                }
            }

            function ensureRunning() {
                if (!animId) {
                    lastFrameTime = performance.now();
                    animId = requestAnimationFrame(render);
                }
            }

            // Écouteurs de mouvement souris
            window.addEventListener('mousemove', (e) => {
                const now = performance.now();
                const dt = (now - mouse.lastTime) / 1000 || 0.016;

                if (mouse.active) {
                    const dx = e.clientX - mouse.x;
                    const dy = e.clientY - mouse.y;
                    const dist = Math.hypot(dx, dy);
                    const curSpeed = dist / (dt * 60);
                    mouse.speed += (curSpeed - mouse.speed) * 0.35;
                } else {
                    mouse.active = true;
                    mouse.speed = 0;
                    mouse.lastX = e.clientX;
                    mouse.lastY = e.clientY;
                }

                mouse.lastX = mouse.x;
                mouse.lastY = mouse.y;
                mouse.x = e.clientX;
                mouse.y = e.clientY;
                mouse.lastTime = now;

                ensureRunning();
            }, { passive: true });

            document.addEventListener('mouseenter', (e) => {
                mouse.active = true;
                mouse.x = e.clientX;
                mouse.y = e.clientY;
                mouse.lastX = e.clientX;
                mouse.lastY = e.clientY;
                mouse.lastTime = performance.now();
                ensureRunning();
            });

            document.addEventListener('mouseleave', () => {
                mouse.active = false;
                mouse.speed = 0;
                ensureRunning();
            });

            window.addEventListener('resize', () => {
                resize();
                ensureRunning();
            }, { passive: true });

            // Mise en veille si onglet masqué
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    if (animId) {
                        cancelAnimationFrame(animId);
                        animId = null;
                    }
                } else {
                    ensureRunning();
                }
            });

            // Nettoyage lors du changement de page
            window.addEventListener('pagehide', () => {
                if (animId) {
                    cancelAnimationFrame(animId);
                    animId = null;
                }
            });

            window.addEventListener('pageshow', (e) => {
                if (e.persisted) {
                    resize();
                    ensureRunning();
                }
            });
        })();
    </script>
</body>
</html>
