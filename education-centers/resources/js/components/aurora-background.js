/**
 * AURORA 3D — Education Centers
 * 
 * Architecture en 2 couches indépendantes :
 * 
 * 1. COUCHE STATIC (Arrière-plan 3D fixe) :
 *    - Anneaux 3D lumineux en perspective.
 *    - Ruban d'aurore S-curve et halos diffus.
 *    - Sphères 3D de verre translucides et poussières d'étoiles.
 *    - Décor entièrement immobile : aucune rotation, aucune dérive, aucune parallaxe.
 *    - Dessiné une seule fois au chargement (et lors d'un resize / changement de thème).
 * 
 * 2. COUCHE INTERACTIVE (Mouse Follow Aurora) :
 *    - Grande lumière diffuse violette, bleue et rose qui suit la souris avec un léger retard fluide.
 *    - Ondulations et vagues lumineuses douces en sillage lors du déplacement du curseur.
 *    - Dissipation progressive sans cercles nets ni contours visibles.
 *    - Placé derrière le contenu (pointer-events: none, z-index: 1 sous le contenu z-10).
 *    - Adapté au Mode Clair (pastel doux) et Mode Sombre (luminescence néon).
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

    // Facteur d'échelle interne pour le canvas interactif (flou soyeux + haute performance)
    const INTER_SCALE = 2;
    let interW = 0;
    let interH = 0;

    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = window.innerWidth;
        height = window.innerHeight;

        // Canvas statique à haute résolution
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

    const LIGHT = {
        ring1Core: [56, 189, 248],    // cyan ciel #38bdf8
        ring1Mid:  [129, 140, 248],   // lavande violet #818cf8
        ring1Edge: [244, 114, 182],   // rose poudré #f472b6
        ring2Core: [168, 85, 247],    // violet doux #a855f7
        ring2Mid:  [236, 72, 153],    // rose pastel #ec4899
        ring2Edge: [99, 102, 241],    // indigo clair #6366f1
        streamCyan:   [56, 189, 248],
        streamViolet: [139, 92, 246],
        streamPink:   [244, 114, 182],
        cursorViolet: [147, 51, 234],
        cursorBlue:   [59, 130, 246],
        cursorPink:   [236, 72, 153],
        cursorCyan:   [56, 189, 248]
    };

    const DARK = {
        ring1Core: [0, 229, 255],     // cyan néon #00e5ff
        ring1Mid:  [139, 92, 246],    // violet électrique #8b5cf6
        ring1Edge: [236, 72, 153],    // magenta néon #ec4899
        ring2Core: [217, 70, 239],    // fuchsia éclatant #d946ef
        ring2Mid:  [99, 102, 241],    // indigo électrique #6366f1
        ring2Edge: [0, 240, 255],     // cyan néon #00f0ff
        streamCyan:   [0, 229, 255],
        streamViolet: [147, 51, 234],
        streamPink:   [236, 72, 153],
        cursorViolet: [139, 92, 246],
        cursorBlue:   [59, 130, 246],
        cursorPink:   [244, 63, 94],
        cursorCyan:   [0, 229, 255]
    };

    function rgba(c, a) {
        return `rgba(${c[0]}, ${c[1]}, ${c[2]}, ${Math.max(0, Math.min(1, a))})`;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. COUCHE STATIC : DÉCOR 3D TOTALEMENT FIXE ET IMMOBILE
    // ──────────────────────────────────────────────────────────────────────────
    const staticRings = [
        {
            posXRatio: 0.22, posYRatio: 0.48,
            baseRadiusX: 285, baseRadiusY: 125,
            rotZ: -0.38, rotX: 1.15, rotY: 0.25,
            thickness: 26, waveFreq: 3, waveAmp: 9,
            pulseSpot: 0.35, type: 1
        },
        {
            posXRatio: 0.78, posYRatio: 0.44,
            baseRadiusX: 335, baseRadiusY: 145,
            rotZ: 0.42, rotX: 1.22, rotY: -0.30,
            thickness: 28, waveFreq: 4, waveAmp: 11,
            pulseSpot: 0.75, type: 2
        },
        {
            posXRatio: 0.50, posYRatio: 0.38,
            baseRadiusX: 420, baseRadiusY: 170,
            rotZ: -0.12, rotX: 1.35, rotY: 0.10,
            thickness: 18, waveFreq: 3, waveAmp: 7,
            pulseSpot: 0.15, type: 3
        }
    ];

    const staticOrbs = [
        { baseX: 0.12, baseY: 0.68, radius: 26, colorLight: [217, 70, 239], colorDark: [236, 72, 153] },
        { baseX: 0.09, baseY: 0.32, radius: 15, colorLight: [56, 189, 248], colorDark: [0, 229, 255] },
        { baseX: 0.41, baseY: 0.18, radius: 13, colorLight: [129, 140, 248], colorDark: [99, 102, 241] },
        { baseX: 0.53, baseY: 0.68, radius: 14, colorLight: [56, 189, 248], colorDark: [59, 130, 246] },
        { baseX: 0.94, baseY: 0.16, radius: 22, colorLight: [236, 72, 153], colorDark: [217, 70, 239] },
        { baseX: 0.88, baseY: 0.78, radius: 12, colorLight: [139, 92, 246], colorDark: [147, 51, 234] }
    ];

    const staticParticles = [
        { xRatio: 0.05, yRatio: 0.22, radius: 2.0, alpha: 0.5 },
        { xRatio: 0.16, yRatio: 0.14, radius: 1.5, alpha: 0.4 },
        { xRatio: 0.28, yRatio: 0.35, radius: 2.5, alpha: 0.6 },
        { xRatio: 0.35, yRatio: 0.82, radius: 1.8, alpha: 0.45 },
        { xRatio: 0.44, yRatio: 0.48, radius: 3.0, alpha: 0.7 },
        { xRatio: 0.58, yRatio: 0.15, radius: 1.6, alpha: 0.5 },
        { xRatio: 0.65, yRatio: 0.85, radius: 2.2, alpha: 0.55 },
        { xRatio: 0.72, yRatio: 0.28, radius: 2.8, alpha: 0.65 },
        { xRatio: 0.82, yRatio: 0.62, radius: 1.8, alpha: 0.45 },
        { xRatio: 0.92, yRatio: 0.45, radius: 2.4, alpha: 0.6 },
        { xRatio: 0.96, yRatio: 0.88, radius: 1.5, alpha: 0.4 },
        { xRatio: 0.24, yRatio: 0.74, radius: 2.0, alpha: 0.5 },
        { xRatio: 0.79, yRatio: 0.12, radius: 1.7, alpha: 0.45 },
        { xRatio: 0.62, yRatio: 0.52, radius: 2.2, alpha: 0.55 },
        { xRatio: 0.38, yRatio: 0.60, radius: 1.4, alpha: 0.35 },
        { xRatio: 0.86, yRatio: 0.34, radius: 2.6, alpha: 0.6 }
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

        // 1. Halos d'ambiance
        const h1X = width * 0.22, h1Y = height * 0.48;
        const g1 = staticCtx.createRadialGradient(h1X, h1Y, 0, h1X, h1Y, 380);
        const cH1 = palette.ring1Mid;
        g1.addColorStop(0, rgba(cH1, isDark ? 0.28 : 0.16));
        g1.addColorStop(0.5, rgba(cH1, isDark ? 0.12 : 0.06));
        g1.addColorStop(1, 'rgba(0,0,0,0)');
        staticCtx.fillStyle = g1;
        staticCtx.beginPath();
        staticCtx.arc(h1X, h1Y, 380, 0, Math.PI * 2);
        staticCtx.fill();

        const h2X = width * 0.78, h2Y = height * 0.44;
        const g2 = staticCtx.createRadialGradient(h2X, h2Y, 0, h2X, h2Y, 420);
        const cH2 = palette.ring2Mid;
        g2.addColorStop(0, rgba(cH2, isDark ? 0.26 : 0.14));
        g2.addColorStop(0.5, rgba(cH2, isDark ? 0.10 : 0.05));
        g2.addColorStop(1, 'rgba(0,0,0,0)');
        staticCtx.fillStyle = g2;
        staticCtx.beginPath();
        staticCtx.arc(h2X, h2Y, 420, 0, Math.PI * 2);
        staticCtx.fill();

        // 2. Courbe S-Curve fixe
        const startX = width * 0.02, startY = height * 0.62;
        const cp1X = width * 0.32, cp1Y = height * 0.72;
        const cp2X = width * 0.48, cp2Y = height * 0.32;
        const cp3X = width * 0.72, cp3Y = height * 0.18;
        const endX = width * 0.98, endY = height * 0.42;

        const streamGrad = staticCtx.createLinearGradient(startX, startY, endX, endY);
        streamGrad.addColorStop(0, rgba(palette.streamCyan, 0));
        streamGrad.addColorStop(0.18, rgba(palette.streamCyan, 0.85));
        streamGrad.addColorStop(0.50, rgba(palette.streamViolet, 0.90));
        streamGrad.addColorStop(0.82, rgba(palette.streamPink, 0.85));
        streamGrad.addColorStop(1, rgba(palette.streamPink, 0));

        const streamLayers = [
            { width: 56, alpha: 0.12 },
            { width: 28, alpha: 0.24 },
            { width: 10, alpha: 0.48 },
            { width: 2.5, alpha: 0.85 }
        ];

        streamLayers.forEach(l => {
            staticCtx.save();
            staticCtx.beginPath();
            staticCtx.moveTo(startX, startY);
            staticCtx.bezierCurveTo(cp1X, cp1Y, cp2X, cp2Y, (cp1X + cp3X) / 2, (cp1Y + cp3Y) / 2);
            staticCtx.bezierCurveTo(cp2X, cp2Y, cp3X, cp3Y, endX, endY);
            staticCtx.lineWidth = l.width;
            staticCtx.lineCap = 'round';
            staticCtx.globalAlpha = l.alpha * (isDark ? 1.0 : 0.65);
            staticCtx.strokeStyle = streamGrad;
            staticCtx.stroke();
            staticCtx.restore();
        });

        // 3. Anneaux 3D fixes
        staticRings.forEach(ring => {
            const cx = ring.posXRatio * width;
            const cy = ring.posYRatio * height;
            const rx = ring.baseRadiusX;
            const ry = ring.baseRadiusY;
            const focal = 600;

            const segments = 64;
            const outer = [];
            const inner = [];
            const core = [];

            const isType1 = ring.type === 1;
            const cCore = isType1 ? palette.ring1Core : palette.ring2Core;
            const cMid = isType1 ? palette.ring1Mid : palette.ring2Mid;
            const cEdge = isType1 ? palette.ring1Edge : palette.ring2Edge;

            const alphaBase = isType1 ? 0.35 : 0.28;
            const alphaMult = isDark ? 1.55 : 1.0;

            for (let i = 0; i <= segments; i++) {
                const theta = (i / segments) * Math.PI * 2;
                const deform = Math.sin(theta * ring.waveFreq + 0.8) * ring.waveAmp
                             + Math.cos(theta * (ring.waveFreq + 2)) * (ring.waveAmp * 0.4);

                const rCurrX = rx + deform;
                const rCurrY = ry + deform * 0.6;
                const px = Math.cos(theta) * rCurrX;
                const py = Math.sin(theta) * rCurrY;
                const halfThick = ring.thickness * 0.5;

                core.push(project3D(px, py, 0, cx, cy, ring.rotX, ring.rotY, ring.rotZ, focal));
                outer.push(project3D(px * (1 + halfThick / rx), py * (1 + halfThick / ry), halfThick * 0.3, cx, cy, ring.rotX, ring.rotY, ring.rotZ, focal));
                inner.push(project3D(px * (1 - halfThick / rx), py * (1 - halfThick / ry), -halfThick * 0.3, cx, cy, ring.rotX, ring.rotY, ring.rotZ, focal));
            }

            // Corps diffus de l'anneau
            staticCtx.save();
            staticCtx.beginPath();
            staticCtx.moveTo(outer[0].x, outer[0].y);
            for (let i = 1; i < outer.length; i++) staticCtx.lineTo(outer[i].x, outer[i].y);
            for (let i = inner.length - 1; i >= 0; i--) staticCtx.lineTo(inner[i].x, inner[i].y);
            staticCtx.closePath();

            const gradRing = staticCtx.createLinearGradient(
                outer[0].x, outer[0].y,
                outer[Math.floor(segments / 2)].x, outer[Math.floor(segments / 2)].y
            );
            gradRing.addColorStop(0, rgba(cEdge, alphaBase * 0.45 * alphaMult));
            gradRing.addColorStop(0.35, rgba(cMid, alphaBase * 0.70 * alphaMult));
            gradRing.addColorStop(0.70, rgba(cCore, alphaBase * 0.70 * alphaMult));
            gradRing.addColorStop(1, rgba(cEdge, alphaBase * 0.35 * alphaMult));

            staticCtx.fillStyle = gradRing;
            staticCtx.fill();
            staticCtx.restore();

            // Ligne néon centrale
            staticCtx.save();
            staticCtx.beginPath();
            staticCtx.moveTo(core[0].x, core[0].y);
            for (let i = 1; i < core.length; i++) {
                const prev = core[i - 1], curr = core[i];
                staticCtx.quadraticCurveTo(prev.x, prev.y, (prev.x + curr.x) / 2, (prev.y + curr.y) / 2);
            }
            staticCtx.closePath();
            staticCtx.lineWidth = isDark ? 2.5 : 1.8;
            staticCtx.strokeStyle = rgba(cCore, isDark ? 0.90 : 0.55);
            staticCtx.stroke();

            // Accent néon fixe
            const spotIdx = Math.floor(ring.pulseSpot * segments);
            const pSpot = core[spotIdx];
            if (pSpot) {
                const spotGrad = staticCtx.createRadialGradient(pSpot.x, pSpot.y, 0, pSpot.x, pSpot.y, 38);
                spotGrad.addColorStop(0, rgba([255, 255, 255], 0.85 * alphaMult));
                spotGrad.addColorStop(0.3, rgba(cCore, 0.65 * alphaMult));
                spotGrad.addColorStop(1, 'rgba(0,0,0,0)');
                staticCtx.fillStyle = spotGrad;
                staticCtx.beginPath();
                staticCtx.arc(pSpot.x, pSpot.y, 38, 0, Math.PI * 2);
                staticCtx.fill();
            }
            staticCtx.restore();
        });

        // 4. Sphères 3D de verre fixes
        staticOrbs.forEach(orb => {
            const px = orb.baseX * width;
            const py = orb.baseY * height;
            const r = orb.radius;
            const color = isDark ? orb.colorDark : orb.colorLight;

            staticCtx.save();

            // Ombre portée
            const shadowGrad = staticCtx.createRadialGradient(px, py + r * 0.5, r * 0.6, px, py + r * 0.5, r * 1.8);
            shadowGrad.addColorStop(0, isDark ? 'rgba(0, 0, 0, 0.45)' : 'rgba(120, 110, 180, 0.12)');
            shadowGrad.addColorStop(1, 'rgba(0, 0, 0, 0)');
            staticCtx.fillStyle = shadowGrad;
            staticCtx.beginPath();
            staticCtx.arc(px, py + r * 0.5, r * 1.8, 0, Math.PI * 2);
            staticCtx.fill();

            // Halo diffus
            const haloGrad = staticCtx.createRadialGradient(px, py, r * 0.5, px, py, r * 2.2);
            haloGrad.addColorStop(0, rgba(color, isDark ? 0.35 : 0.18));
            haloGrad.addColorStop(1, 'rgba(0,0,0,0)');
            staticCtx.fillStyle = haloGrad;
            staticCtx.beginPath();
            staticCtx.arc(px, py, r * 2.2, 0, Math.PI * 2);
            staticCtx.fill();

            // Corps translucide
            const bodyGrad = staticCtx.createRadialGradient(px - r * 0.25, py - r * 0.25, r * 0.1, px, py, r);
            const baseA = isDark ? 0.85 : 0.65;
            bodyGrad.addColorStop(0, rgba([255, 255, 255], 0.45 * baseA));
            bodyGrad.addColorStop(0.35, rgba(color, 0.40 * baseA));
            bodyGrad.addColorStop(0.80, rgba(color, 0.75 * baseA));
            bodyGrad.addColorStop(1.0, rgba(color, 0.95 * baseA));
            staticCtx.fillStyle = bodyGrad;
            staticCtx.beginPath();
            staticCtx.arc(px, py, r, 0, Math.PI * 2);
            staticCtx.fill();

            // Reflet spéculaire
            const specX = px - r * 0.32, specY = py - r * 0.32;
            const specGrad = staticCtx.createRadialGradient(specX, specY, 0, specX, specY, r * 0.45);
            specGrad.addColorStop(0, 'rgba(255, 255, 255, 0.95)');
            specGrad.addColorStop(0.5, 'rgba(255, 255, 255, 0.45)');
            specGrad.addColorStop(1, 'rgba(255, 255, 255, 0)');
            staticCtx.fillStyle = specGrad;
            staticCtx.beginPath();
            staticCtx.arc(specX, specY, r * 0.45, 0, Math.PI * 2);
            staticCtx.fill();

            // Reflet secondaire (caustique)
            const rimX = px + r * 0.28, rimY = py + r * 0.28;
            const rimGrad = staticCtx.createRadialGradient(rimX, rimY, 0, rimX, rimY, r * 0.35);
            rimGrad.addColorStop(0, rgba([255, 255, 255], 0.35));
            rimGrad.addColorStop(1, 'rgba(255, 255, 255, 0)');
            staticCtx.fillStyle = rimGrad;
            staticCtx.beginPath();
            staticCtx.arc(rimX, rimY, r * 0.35, 0, Math.PI * 2);
            staticCtx.fill();

            staticCtx.restore();
        });

        // 5. Particules fixes
        staticParticles.forEach(p => {
            const x = p.xRatio * width;
            const y = p.yRatio * height;
            const pColor = isDark ? [200, 235, 255] : [140, 130, 210];
            const alpha = p.alpha * (isDark ? 0.8 : 0.45);

            staticCtx.save();
            staticCtx.beginPath();
            staticCtx.arc(x, y, p.radius, 0, Math.PI * 2);
            staticCtx.fillStyle = rgba(pColor, alpha);
            staticCtx.fill();
            staticCtx.restore();
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. COUCHE INTERACTIVE : MOUSE FOLLOW AURORA (LUMIÈRE & VAGUES FLUIDES)
    // ──────────────────────────────────────────────────────────────────────────
    const cursorColors = [
        [139, 92, 246], // Violet
        [59, 130, 246],  // Bleu
        [236, 72, 153], // Rose
        [56, 189, 248]  // Cyan
    ];

    // Position de tête avec retard naturel (lerp fluide)
    const head = {
        x: null,
        y: null,
        targetX: 0,
        targetY: 0,
        energy: 0,
        opacity: 0
    };

    const waves = [];
    const MAX_WAVES = 16;
    let lastSpawn = 0;
    let lastMouseX = null;
    let lastMouseY = null;
    let rafId = null;
    let lastFrameTime = performance.now();
    let animTime = 0;

    function spawnWave(x, y, vx, vy, dist) {
        if (waves.length >= MAX_WAVES) waves.shift();

        const moveAngle = Math.atan2(vy, vx);
        const waveAngle = moveAngle + Math.PI / 2 + (Math.random() - 0.5) * 0.5;
        const power = Math.min(1.0, Math.max(0.2, dist / 25));

        const c1 = cursorColors[Math.floor(Math.random() * cursorColors.length)];
        const c2 = cursorColors[Math.floor(Math.random() * cursorColors.length)];
        const c3 = cursorColors[Math.floor(Math.random() * cursorColors.length)];

        waves.push({
            x, y,
            driftX: Math.cos(moveAngle) * 0.75 + Math.cos(waveAngle) * (Math.random() - 0.5) * 1.1,
            driftY: Math.sin(moveAngle) * 0.75 + Math.sin(waveAngle) * (Math.random() - 0.5) * 1.1,
            angle: waveAngle,
            span: 90 + power * 90,        // correspond à 180-360px écran
            thickness: 20 + power * 18,   // 40-76px écran
            amplitude: 14 + power * 18,
            freq: 2.0 + Math.random() * 1.4,
            phase: Math.random() * Math.PI * 2,
            age: 0,
            ttl: 2.4 + power * 1.2,
            colors: [c1, c2, c3],
            power
        });
    }

    function drawWave(wave, dt) {
        wave.age += dt / wave.ttl;
        if (wave.age >= 1) return false;

        wave.x += wave.driftX * dt * 42;
        wave.y += wave.driftY * dt * 42;

        const growth = 1 + wave.age * 0.95;
        const curSpan = wave.span * growth;
        const curThick = wave.thickness * (1 + wave.age * 0.65);
        const curAmp = wave.amplitude * (1 - wave.age * 0.35);

        // Enveloppe d'opacité fluide (montée rapide, dissipation progressive)
        const envelope = Math.sin(Math.pow(wave.age, 0.7) * Math.PI);
        const maxAlpha = isDark ? (0.35 + wave.power * 0.25) : (0.24 + wave.power * 0.18);
        const alpha = envelope * maxAlpha;

        if (alpha <= 0.005) return true;

        const cosA = Math.cos(wave.angle), sinA = Math.sin(wave.angle);
        const normX = -sinA, normY = cosA;

        const segments = 6;
        const crest = [], trough = [];

        for (let i = 0; i <= segments; i++) {
            const u = (i / segments) - 0.5;
            const harm = Math.sin(u * Math.PI * wave.freq + wave.age * 3.8 + wave.phase) * curAmp;
            const taper = Math.sin((i / segments) * Math.PI);
            const halfThick = (curThick * 0.5 * taper) + 2;

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
        grad.addColorStop(0.55, rgba(c2, alpha * 0.95));
        grad.addColorStop(0.85, rgba(c3, alpha * 0.85));
        grad.addColorStop(1, rgba(c3, 0));

        interCtx.fillStyle = grad;
        interCtx.fill();
        interCtx.restore();

        return true;
    }

    function drawHeadGlow(x, y, energy, alpha, time) {
        if (alpha <= 0.005) return;
        // Rayon en coordonnées du canvas interactif (scaled x2 en affichage = 400-600px)
        const baseRadius = 85 + energy * 35;

        const lobes = [
            {
                color: isDark ? DARK.cursorViolet : LIGHT.cursorViolet,
                ox: Math.cos(time * 0.8) * 20,
                oy: Math.sin(time * 0.7) * 14,
                r: baseRadius * (1 + Math.sin(time * 1.1) * 0.12),
                weight: 0.90
            },
            {
                color: isDark ? DARK.cursorCyan : LIGHT.cursorBlue,
                ox: Math.cos(time * 0.9 + 2.0) * 22,
                oy: Math.sin(time * 0.8 + 2.0) * 16,
                r: baseRadius * 0.92 * (1 + Math.cos(time * 0.9 + 1.4) * 0.1),
                weight: 0.80
            },
            {
                color: isDark ? DARK.cursorPink : LIGHT.cursorPink,
                ox: Math.cos(time * 0.7 + 4.2) * 18,
                oy: Math.sin(time * 1.0 + 4.2) * 18,
                r: baseRadius * 0.82 * (1 + Math.sin(time * 0.8 + 3.0) * 0.14),
                weight: 0.70
            }
        ];

        lobes.forEach(lobe => {
            const lx = x + lobe.ox;
            const ly = y + lobe.oy;
            const lAlpha = alpha * lobe.weight * (isDark ? (0.42 + energy * 0.25) : (0.28 + energy * 0.20));

            interCtx.save();
            interCtx.translate(lx, ly);
            interCtx.rotate(time * 0.15 + lobe.weight);
            interCtx.scale(1.15, 0.85);

            const g = interCtx.createRadialGradient(0, 0, 0, 0, 0, lobe.r);
            g.addColorStop(0, rgba(lobe.color, lAlpha));
            g.addColorStop(0.45, rgba(lobe.color, lAlpha * 0.55));
            g.addColorStop(0.80, rgba(lobe.color, lAlpha * 0.15));
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

        // Poursuite naturelle avec retard amorti
        if (head.x === null) {
            head.x = head.targetX;
            head.y = head.targetY;
        }

        const dx = head.targetX - head.x;
        const dy = head.targetY - head.y;
        head.x += dx * Math.min(1, dt * 5.5);
        head.y += dy * Math.min(1, dt * 5.5);

        const distLag = Math.hypot(dx, dy);
        head.energy += (Math.min(1, distLag / 25) - head.energy) * Math.min(1, dt * 4.5);

        interCtx.clearRect(0, 0, interW, interH);
        interCtx.globalCompositeOperation = isDark ? 'screen' : 'source-over';

        // 1. Dessin des vagues interactives
        for (let i = waves.length - 1; i >= 0; i--) {
            const alive = drawWave(waves[i], dt);
            if (!alive) waves.splice(i, 1);
        }

        // 2. Dessin de la lumière de tête diffuse à la souris
        if (head.opacity > 0.01 && head.x !== null) {
            drawHeadGlow(head.x, head.y, head.energy, head.opacity, animTime);
        }

        // Si des vagues voyagent encore ou si la lumière n'est pas encore stabilisée, continuer
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
        // Ignorer les événements de touch purs (téléphones/tablettes tactiles)
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

        // Coordonnées pour le canvas interactif
        const targetX = clientX / INTER_SCALE;
        const targetY = clientY / INTER_SCALE;

        head.targetX = targetX;
        head.targetY = targetY;

        if (head.x === null) {
            head.x = targetX;
            head.y = targetY;
        }

        head.opacity = Math.min(1, head.opacity + 0.15);

        // Émission de vagues lors du mouvement de souris
        if (dist > 5 && now - lastSpawn > 80) {
            lastSpawn = now;
            spawnWave(head.x, head.y, deltaX, deltaY, dist);
        }

        wakeAnimation();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // INITIALISATION
    // ──────────────────────────────────────────────────────────────────────────
    resize();
    renderStaticBackground();

    // Écoute des mouvements de souris (sur window et document pour une réactivité absolue)
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

    // Détection du changement de thème
    const themeObserver = new MutationObserver(() => {
        const newDark = checkIsDark();
        if (newDark !== isDark) {
            isDark = newDark;
            renderStaticBackground();
            wakeAnimation();
        }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    // Redimensionnement
    window.addEventListener('resize', () => {
        resize();
        renderStaticBackground();
        wakeAnimation();
    }, { passive: true });

    // Économie d'énergie
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
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
