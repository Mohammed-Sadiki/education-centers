/**
 * AURORA SAAS DESIGN SYSTEM — Education Centers
 * 
 * Version épurée, moderne et professionnelle (High-End SaaS / Académique) :
 * 
 * 1. COUCHE STATIC (Arrière-plan fixe & minimaliste) :
 *    - Halos lumineux d'ambiance ultra-doux (dégradés lavande, bleu ciel et rose poudré).
 *    - Anneaux fins, discrets et élégants en perspective 3D (traits fins, sans surcharge).
 *    - Suppression intégrale des sphères 3D, boules de verre et particules futuristes.
 *    - Décor 100% immobile : aucun mouvement, aucune dérive, rendu unique haute définition.
 * 
 * 2. COUCHE INTERACTIVE (Mouse Follow Aurora Waves) :
 *    - Lueur diffuse élégante violette, bleue et rose qui suit la souris avec un retard naturel fluide.
 *    - Ondulations et vagues lumineuses soyeuses en sillage lors des déplacements du curseur.
 *    - Dégradés radiaux organiques et flou vaporeux sans aucun cercle dur ni contour visible.
 *    - Placé derrière le contenu (pointer-events: none, z-index: 1).
 *    - Rendu harmonisé pour Light Mode (fond blanc lavande pastel) et Dark Mode (fond bleu nuit profond).
 *    - Désactivé sur tactile pur et si prefers-reduced-motion est actif.
 */

export function initAuroraBackground() {
    let staticCanvas = document.getElementById('ec-aurora-static');
    let interactiveCanvas = document.getElementById('ec-aurora-interactive');

    // Fallback de compatibilité si seul `#ec-aurora` existe
    if (!staticCanvas && !interactiveCanvas) {
        const legacyCanvas = document.getElementById('ec-aurora');
        if (!legacyCanvas) return;

        staticCanvas = document.createElement('canvas');
        staticCanvas.id = 'ec-aurora-static';
        staticCanvas.className = 'ec-aurora-layer ec-aurora-static';
        staticCanvas.setAttribute('aria-hidden', 'true');
        legacyCanvas.parentNode.insertBefore(staticCanvas, legacyCanvas);

        legacyCanvas.id = 'ec-aurora-interactive';
        legacyCanvas.className = 'ec-aurora-layer ec-aurora-interactive';
        interactiveCanvas = legacyCanvas;
    }

    if (!staticCanvas || !interactiveCanvas) return;

    const staticCtx = staticCanvas.getContext('2d', { alpha: true });
    const interCtx = interactiveCanvas.getContext('2d', { alpha: true });
    if (!staticCtx || !interCtx) return;

    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    let width = 0;
    let height = 0;
    let dpr = 1;

    // Facteur d'échelle pour le canvas interactif (optimisation 60+ FPS + diffusion veloutée)
    const INTER_SCALE = 2;
    let interW = 0;
    let interH = 0;

    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = window.innerWidth;
        height = window.innerHeight;

        // Canvas statique à résolution nette
        staticCanvas.width = Math.round(width * dpr);
        staticCanvas.height = Math.round(height * dpr);
        staticCanvas.style.width = width + 'px';
        staticCanvas.style.height = height + 'px';
        staticCtx.setTransform(dpr, 0, 0, dpr, 0, 0);

        // Canvas interactif pour la lumière et les vagues du curseur
        interW = Math.max(160, Math.ceil(width / INTER_SCALE));
        interH = Math.max(120, Math.ceil(height / INTER_SCALE));
        interactiveCanvas.width = interW;
        interactiveCanvas.height = interH;
        interactiveCanvas.style.width = width + 'px';
        interactiveCanvas.style.height = height + 'px';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GESTION DU THÈME (Light Mode / Dark Mode)
    // ──────────────────────────────────────────────────────────────────────────
    function checkIsDark() {
        return document.documentElement.classList.contains('dark') ||
            localStorage.getItem('ec-theme') === 'dark' ||
            (!localStorage.getItem('ec-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
    }

    let isDark = checkIsDark();

    // Palettes équilibrées, douces et académiques (sans saturation agressive)
    const LIGHT = {
        // Anneaux fins discrets
        ring1Core: [99, 102, 241],    // indigo élégant #6366f1
        ring1Mid:  [139, 92, 246],   // lavande violet #8b5cf6
        ring1Edge: [236, 72, 153],   // rose poudré doux #ec4899

        ring2Core: [56, 189, 248],   // bleu ciel doux #38bdf8
        ring2Mid:  [129, 140, 248],  // pervenche #818cf8
        ring2Edge: [244, 114, 182],  // blush rose #f472b6

        // Halos ambiants doux
        haloViolet: [139, 92, 246],
        haloBlue:   [59, 130, 246],
        haloPink:   [244, 114, 182],

        // Couleurs de la souris
        cursorViolet: [129, 140, 248],
        cursorBlue:   [56, 189, 248],
        cursorPink:   [244, 114, 182]
    };

    const DARK = {
        // Anneaux fins discrets
        ring1Core: [129, 140, 248],  // violet doux #818cf8
        ring1Mid:  [99, 102, 241],   // indigo raffiné #6366f1
        ring1Edge: [217, 70, 239],   // fuchsia feutré #d946ef

        ring2Core: [56, 189, 248],   // bleu saphir doux #38bdf8
        ring2Mid:  [79, 70, 229],    // indigo profond #4f46e5
        ring2Edge: [236, 72, 153],   // rose délicat #ec4899

        // Halos ambiants doux
        haloViolet: [99, 102, 241],
        haloBlue:   [56, 189, 248],
        haloPink:   [217, 70, 239],

        // Couleurs de la souris
        cursorViolet: [139, 92, 246],
        cursorBlue:   [56, 189, 248],
        cursorPink:   [236, 72, 153]
    };

    function rgba(c, a) {
        return `rgba(${c[0]}, ${c[1]}, ${c[2]}, ${Math.max(0, Math.min(1, a))})`;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. COUCHE STATIC : ANNEAUX ULTRA-FINS & HALOS D'AMBIANCE ÉPURÉS (FIXES)
    // ──────────────────────────────────────────────────────────────────────────
    // Deux anneaux épurés, fins et élégants positionnés de façon asymétrique
    const staticRings = [
        {
            posXRatio: 0.18, posYRatio: 0.42,
            baseRadiusX: 310, baseRadiusY: 130,
            rotZ: -0.32, rotX: 1.18, rotY: 0.22,
            thickness: 6, waveFreq: 2, waveAmp: 3.5,
            accentRatio: 0.38, type: 1
        },
        {
            posXRatio: 0.82, posYRatio: 0.48,
            baseRadiusX: 350, baseRadiusY: 150,
            rotZ: 0.36, rotX: 1.25, rotY: -0.25,
            thickness: 7, waveFreq: 2, waveAmp: 4.0,
            accentRatio: 0.72, type: 2
        }
    ];

    function project3D(x, y, z, cx, cy, rotX, rotY, rotZ, focal) {
        const cosZ = Math.cos(rotZ), sinZ = Math.sin(rotZ);
        const x1 = x * cosZ - y * sinZ;
        const y1 = x * sinZ + y * cosZ;
        const z1 = z;

        const cosX = Math.cos(rotX), sinX = Math.sin(rotX);
        const y2 = y1 * cosX - z1 * sinX;
        const z2 = y1 * sinX + z1 * cosX;
        const x2 = x1;

        const cosY = Math.cos(rotY), sinY = Math.sin(rotY);
        const x3 = x2 * cosY + z2 * sinY;
        const z3 = -x2 * sinY + z2 * cosY;
        const y3 = y2;

        const scale = focal / (focal + z3);
        return { x: cx + x3 * scale, y: cy + y3 * scale };
    }

    function renderStaticBackground() {
        if (!staticCtx) return;
        const palette = isDark ? DARK : LIGHT;

        staticCtx.clearRect(0, 0, width, height);
        staticCtx.globalCompositeOperation = isDark ? 'screen' : 'source-over';

        // 1. Halos d'ambiance ultra-doux (vignettage lumineux discret de type SaaS moderne)
        // Halo 1 : Haut Gauche (Violet / Lavande doux)
        const h1X = width * 0.15, h1Y = height * 0.35;
        const g1 = staticCtx.createRadialGradient(h1X, h1Y, 0, h1X, h1Y, Math.max(width * 0.35, 420));
        g1.addColorStop(0, rgba(palette.haloViolet, isDark ? 0.16 : 0.08));
        g1.addColorStop(0.5, rgba(palette.haloViolet, isDark ? 0.06 : 0.03));
        g1.addColorStop(1, 'rgba(0,0,0,0)');
        staticCtx.fillStyle = g1;
        staticCtx.beginPath();
        staticCtx.arc(h1X, h1Y, Math.max(width * 0.35, 420), 0, Math.PI * 2);
        staticCtx.fill();

        // Halo 2 : Bas Droite (Bleu / Cyan subtil)
        const h2X = width * 0.85, h2Y = height * 0.55;
        const g2 = staticCtx.createRadialGradient(h2X, h2Y, 0, h2X, h2Y, Math.max(width * 0.38, 460));
        g2.addColorStop(0, rgba(palette.haloBlue, isDark ? 0.14 : 0.07));
        g2.addColorStop(0.55, rgba(palette.haloBlue, isDark ? 0.05 : 0.025));
        g2.addColorStop(1, 'rgba(0,0,0,0)');
        staticCtx.fillStyle = g2;
        staticCtx.beginPath();
        staticCtx.arc(h2X, h2Y, Math.max(width * 0.38, 460), 0, Math.PI * 2);
        staticCtx.fill();

        // Halo 3 : Centre / Haut (Touche rose poudré délicate)
        const h3X = width * 0.52, h3Y = height * 0.15;
        const g3 = staticCtx.createRadialGradient(h3X, h3Y, 0, h3X, h3Y, Math.max(width * 0.30, 360));
        g3.addColorStop(0, rgba(palette.haloPink, isDark ? 0.10 : 0.05));
        g3.addColorStop(0.6, rgba(palette.haloPink, isDark ? 0.03 : 0.015));
        g3.addColorStop(1, 'rgba(0,0,0,0)');
        staticCtx.fillStyle = g3;
        staticCtx.beginPath();
        staticCtx.arc(h3X, h3Y, Math.max(width * 0.30, 360), 0, Math.PI * 2);
        staticCtx.fill();

        // 2. Anneaux 3D fins, discrets et précis
        staticRings.forEach(ring => {
            const cx = ring.posXRatio * width;
            const cy = ring.posYRatio * height;
            const rx = ring.baseRadiusX;
            const ry = ring.baseRadiusY;
            const focal = 650;

            const segments = 96; // Tracé haute définition
            const points = [];

            const isType1 = ring.type === 1;
            const cCore = isType1 ? palette.ring1Core : palette.ring2Core;
            const cMid  = isType1 ? palette.ring1Mid  : palette.ring2Mid;
            const cEdge = isType1 ? palette.ring1Edge : palette.ring2Edge;

            for (let i = 0; i <= segments; i++) {
                const theta = (i / segments) * Math.PI * 2;
                // Déformation sinusoïdale très douce et élégante
                const deform = Math.sin(theta * ring.waveFreq + 0.5) * ring.waveAmp;
                const rCurrX = rx + deform;
                const rCurrY = ry + deform * 0.6;
                const px = Math.cos(theta) * rCurrX;
                const py = Math.sin(theta) * rCurrY;

                points.push(project3D(px, py, 0, cx, cy, ring.rotX, ring.rotY, ring.rotZ, focal));
            }

            // Dégradé linéaire élégant qui traverse l'anneau
            const pStart = points[0];
            const pMid = points[Math.floor(segments * 0.5)];
            const ringGrad = staticCtx.createLinearGradient(pStart.x, pStart.y, pMid.x, pMid.y);
            ringGrad.addColorStop(0, rgba(cEdge, isDark ? 0.25 : 0.20));
            ringGrad.addColorStop(0.35, rgba(cMid, isDark ? 0.45 : 0.35));
            ringGrad.addColorStop(0.70, rgba(cCore, isDark ? 0.50 : 0.40));
            ringGrad.addColorStop(1, rgba(cEdge, isDark ? 0.20 : 0.15));

            // A. Halo fin autour de l'anneau (lueur subtile, non agressive)
            staticCtx.save();
            staticCtx.beginPath();
            staticCtx.moveTo(points[0].x, points[0].y);
            for (let i = 1; i < points.length; i++) {
                const prev = points[i - 1], curr = points[i];
                staticCtx.quadraticCurveTo(prev.x, prev.y, (prev.x + curr.x) / 2, (prev.y + curr.y) / 2);
            }
            staticCtx.closePath();
            staticCtx.lineWidth = ring.thickness;
            staticCtx.strokeStyle = ringGrad;
            staticCtx.globalAlpha = isDark ? 0.35 : 0.22;
            staticCtx.stroke();
            staticCtx.restore();

            // B. Trait de contour vectoriel ultra-fin (hairline ~1.2px)
            staticCtx.save();
            staticCtx.beginPath();
            staticCtx.moveTo(points[0].x, points[0].y);
            for (let i = 1; i < points.length; i++) {
                const prev = points[i - 1], curr = points[i];
                staticCtx.quadraticCurveTo(prev.x, prev.y, (prev.x + curr.x) / 2, (prev.y + curr.y) / 2);
            }
            staticCtx.closePath();
            staticCtx.lineWidth = isDark ? 1.4 : 1.1;
            staticCtx.strokeStyle = ringGrad;
            staticCtx.globalAlpha = isDark ? 0.70 : 0.50;
            staticCtx.stroke();
            staticCtx.restore();

            // C. Point lumineux feutré (accent délicat sans éblouissement)
            const accentIdx = Math.floor(ring.accentRatio * segments);
            const pAccent = points[accentIdx];
            if (pAccent) {
                staticCtx.save();
                const accGrad = staticCtx.createRadialGradient(pAccent.x, pAccent.y, 0, pAccent.x, pAccent.y, 16);
                accGrad.addColorStop(0, rgba([255, 255, 255], isDark ? 0.75 : 0.55));
                accGrad.addColorStop(0.4, rgba(cCore, isDark ? 0.40 : 0.25));
                accGrad.addColorStop(1, 'rgba(0,0,0,0)');
                staticCtx.fillStyle = accGrad;
                staticCtx.beginPath();
                staticCtx.arc(pAccent.x, pAccent.y, 16, 0, Math.PI * 2);
                staticCtx.fill();
                staticCtx.restore();
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. COUCHE INTERACTIVE : MOUSE FOLLOW AURORA (VAGUES SOYEUSES ÉPURÉES)
    // ──────────────────────────────────────────────────────────────────────────
    // Couleurs douces pour les vagues lumineuses du curseur
    const cursorColors = [
        [139, 92, 246], // Violet lavande
        [56, 189, 248],  // Bleu ciel doux
        [244, 114, 182], // Rose poudré
        [99, 102, 241]   // Indigo
    ];

    const head = {
        x: null,
        y: null,
        targetX: 0,
        targetY: 0,
        energy: 0,
        opacity: 0
    };

    const waves = [];
    const MAX_WAVES = 12; // Nombre modéré pour une sensation d'épure et de fluidité
    let lastSpawn = 0;
    let lastMouseX = null;
    let lastMouseY = null;
    let rafId = null;
    let lastFrameTime = performance.now();
    let animTime = 0;

    function spawnWave(x, y, vx, vy, dist) {
        if (waves.length >= MAX_WAVES) waves.shift();

        const moveAngle = Math.atan2(vy, vx);
        const waveAngle = moveAngle + Math.PI / 2 + (Math.random() - 0.5) * 0.4;
        const power = Math.min(1.0, Math.max(0.18, dist / 28));

        const c1 = cursorColors[Math.floor(Math.random() * cursorColors.length)];
        const c2 = cursorColors[Math.floor(Math.random() * cursorColors.length)];
        const c3 = cursorColors[Math.floor(Math.random() * cursorColors.length)];

        waves.push({
            x, y,
            driftX: Math.cos(moveAngle) * 0.65 + Math.cos(waveAngle) * (Math.random() - 0.5) * 0.9,
            driftY: Math.sin(moveAngle) * 0.65 + Math.sin(waveAngle) * (Math.random() - 0.5) * 0.9,
            angle: waveAngle,
            span: 80 + power * 75,
            thickness: 16 + power * 14,
            amplitude: 10 + power * 12,
            freq: 1.8 + Math.random() * 1.2,
            phase: Math.random() * Math.PI * 2,
            age: 0,
            ttl: 2.2 + power * 1.0,
            colors: [c1, c2, c3],
            power
        });
    }

    function drawWave(wave, dt) {
        wave.age += dt / wave.ttl;
        if (wave.age >= 1) return false;

        wave.x += wave.driftX * dt * 36;
        wave.y += wave.driftY * dt * 36;

        const growth = 1 + wave.age * 0.85;
        const curSpan = wave.span * growth;
        const curThick = wave.thickness * (1 + wave.age * 0.55);
        const curAmp = wave.amplitude * (1 - wave.age * 0.30);

        // Enveloppe d'opacité douce et progressive
        const envelope = Math.sin(Math.pow(wave.age, 0.72) * Math.PI);
        const maxAlpha = isDark ? (0.24 + wave.power * 0.16) : (0.16 + wave.power * 0.12);
        const alpha = envelope * maxAlpha;

        if (alpha <= 0.004) return true;

        const cosA = Math.cos(wave.angle), sinA = Math.sin(wave.angle);
        const normX = -sinA, normY = cosA;

        const segments = 6;
        const crest = [], trough = [];

        for (let i = 0; i <= segments; i++) {
            const u = (i / segments) - 0.5;
            const harm = Math.sin(u * Math.PI * wave.freq + wave.age * 3.5 + wave.phase) * curAmp;
            const taper = Math.sin((i / segments) * Math.PI);
            const halfThick = (curThick * 0.5 * taper) + 1.5;

            const bx = wave.x + cosA * (u * curSpan);
            const by = wave.y + sinA * (u * curSpan);

            crest.push({ x: bx + normX * (harm + halfThick), y: by + normY * (harm + halfThick) });
            trough.push({ x: bx + normX * (harm - halfThick), y: by + normY * (harm - halfThick) });
        }

        interCtx.save();
        interCtx.beginPath();
        interCtx.moveTo(crest[0].x, crest[0].y);
        for (let i = 1; i < crest.length; i++) {
            const prev = crest[i - 1], curr = crest[i];
            interCtx.quadraticCurveTo(prev.x, prev.y, (prev.x + curr.x) / 2, (prev.y + curr.y) / 2);
        }
        interCtx.lineTo(crest[crest.length - 1].x, crest[crest.length - 1].y);
        interCtx.lineTo(trough[trough.length - 1].x, trough[trough.length - 1].y);
        for (let i = trough.length - 2; i >= 0; i--) {
            const next = trough[i + 1], curr = trough[i];
            interCtx.quadraticCurveTo(next.x, next.y, (next.x + curr.x) / 2, (next.y + curr.y) / 2);
        }
        interCtx.closePath();

        const grad = interCtx.createLinearGradient(crest[0].x, crest[0].y, crest[crest.length - 1].x, crest[crest.length - 1].y);
        const [c1, c2, c3] = wave.colors;
        grad.addColorStop(0, rgba(c1, 0));
        grad.addColorStop(0.25, rgba(c1, alpha));
        grad.addColorStop(0.55, rgba(c2, alpha * 0.90));
        grad.addColorStop(0.85, rgba(c3, alpha * 0.80));
        grad.addColorStop(1, rgba(c3, 0));

        interCtx.fillStyle = grad;
        interCtx.fill();
        interCtx.restore();

        return true;
    }

    function drawHeadGlow(x, y, energy, alpha, time) {
        if (alpha <= 0.005) return;
        const baseRadius = 75 + energy * 30;

        const lobes = [
            {
                color: isDark ? DARK.cursorViolet : LIGHT.cursorViolet,
                ox: Math.cos(time * 0.7) * 16,
                oy: Math.sin(time * 0.6) * 12,
                r: baseRadius * (1 + Math.sin(time * 0.9) * 0.10),
                weight: 0.85
            },
            {
                color: isDark ? DARK.cursorBlue : LIGHT.cursorBlue,
                ox: Math.cos(time * 0.8 + 2.0) * 18,
                oy: Math.sin(time * 0.7 + 2.0) * 14,
                r: baseRadius * 0.90 * (1 + Math.cos(time * 0.8 + 1.2) * 0.08),
                weight: 0.75
            },
            {
                color: isDark ? DARK.cursorPink : LIGHT.cursorPink,
                ox: Math.cos(time * 0.6 + 4.0) * 15,
                oy: Math.sin(time * 0.9 + 4.0) * 15,
                r: baseRadius * 0.80 * (1 + Math.sin(time * 0.7 + 2.8) * 0.10),
                weight: 0.65
            }
        ];

        lobes.forEach(lobe => {
            const lx = x + lobe.ox;
            const ly = y + lobe.oy;
            const lAlpha = alpha * lobe.weight * (isDark ? (0.28 + energy * 0.18) : (0.18 + energy * 0.14));

            interCtx.save();
            interCtx.translate(lx, ly);
            interCtx.rotate(time * 0.12 + lobe.weight);
            interCtx.scale(1.10, 0.90);

            const g = interCtx.createRadialGradient(0, 0, 0, 0, 0, lobe.r);
            g.addColorStop(0, rgba(lobe.color, lAlpha));
            g.addColorStop(0.45, rgba(lobe.color, lAlpha * 0.50));
            g.addColorStop(0.80, rgba(lobe.color, lAlpha * 0.12));
            g.addColorStop(1, 'rgba(0,0,0,0)');

            interCtx.fillStyle = g;
            interCtx.beginPath();
            interCtx.arc(0, 0, lobe.r, 0, Math.PI * 2);
            interCtx.fill();
            interCtx.restore();
        });
    }

    function renderInteractive(now) {
        rafId = null;
        if (!interCtx) return;

        const dt = Math.min(0.05, (now - lastFrameTime) / 1000 || 0.016);
        lastFrameTime = now;
        animTime += dt;

        // Poursuite fluide avec amortissement naturel
        if (head.x === null) {
            head.x = head.targetX;
            head.y = head.targetY;
        }

        const dx = head.targetX - head.x;
        const dy = head.targetY - head.y;
        head.x += dx * Math.min(1, dt * 5.0);
        head.y += dy * Math.min(1, dt * 5.0);

        const distLag = Math.hypot(dx, dy);
        head.energy += (Math.min(1, distLag / 25) - head.energy) * Math.min(1, dt * 4.0);

        interCtx.clearRect(0, 0, interW, interH);
        interCtx.globalCompositeOperation = isDark ? 'screen' : 'source-over';

        // 1. Vagues lumineuses interactives
        for (let i = waves.length - 1; i >= 0; i--) {
            const alive = drawWave(waves[i], dt);
            if (!alive) waves.splice(i, 1);
        }

        // 2. Halo de suivi à la souris
        if (head.opacity > 0.01 && head.x !== null) {
            drawHeadGlow(head.x, head.y, head.energy, head.opacity, animTime);
        }

        const needsMoreFrames = waves.length > 0 || head.energy > 0.02 || head.opacity > 0.02;
        if (needsMoreFrames && !motionQuery.matches) {
            rafId = requestAnimationFrame(renderInteractive);
        }
    }

    function wakeAnimation() {
        if (!rafId && !motionQuery.matches) {
            lastFrameTime = performance.now();
            rafId = requestAnimationFrame(renderInteractive);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GESTION DES ÉVÉNEMENTS DU CURSEUR
    // ──────────────────────────────────────────────────────────────────────────
    function onPointerMove(e) {
        if (e.pointerType === 'touch') return;

        const clientX = e.clientX;
        const clientY = e.clientY;
        const now = performance.now();

        if (lastMouseX === null) {
            lastMouseX = clientX;
            lastMouseY = clientY;
        }

        const deltaX = clientX - lastMouseX;
        const deltaY = clientY - lastMouseY;
        const dist = Math.hypot(deltaX, deltaY);

        lastMouseX = clientX;
        lastMouseY = clientY;

        const targetX = clientX / INTER_SCALE;
        const targetY = clientY / INTER_SCALE;

        head.targetX = targetX;
        head.targetY = targetY;

        if (head.x === null) {
            head.x = targetX;
            head.y = targetY;
        }

        head.opacity = Math.min(1, head.opacity + 0.15);

        // Déclenchement des vagues lors du mouvement de souris
        if (dist > 5 && now - lastSpawn > 85) {
            lastSpawn = now;
            spawnWave(head.x, head.y, deltaX, deltaY, dist);
        }

        wakeAnimation();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // INITIALISATION & LISTENERS
    // ──────────────────────────────────────────────────────────────────────────
    resize();
    renderStaticBackground();

    window.addEventListener('pointermove', onPointerMove, { passive: true });
    window.addEventListener('mousemove', onPointerMove, { passive: true });

    document.addEventListener('mouseenter', (e) => {
        head.targetX = e.clientX / INTER_SCALE;
        head.targetY = e.clientY / INTER_SCALE;
        lastMouseX = e.clientX;
        lastMouseY = e.clientY;
        head.opacity = 1;
        wakeAnimation();
    });

    document.addEventListener('mouseleave', () => {
        head.opacity = 0;
        wakeAnimation();
    });

    // Surveillance du changement de thème Light / Dark
    const themeObserver = new MutationObserver(() => {
        const newDark = checkIsDark();
        if (newDark !== isDark) {
            isDark = newDark;
            renderStaticBackground();
            wakeAnimation();
        }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    window.addEventListener('resize', () => {
        resize();
        renderStaticBackground();
        wakeAnimation();
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden && rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    });

    window.addEventListener('pagehide', () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    });

    window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
            resize();
            renderStaticBackground();
        }
    });
}
