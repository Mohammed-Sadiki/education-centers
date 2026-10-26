/**
 * AURORA 3D INTERACTIF — Education Centers
 * 
 * Arrière-plan immersif inspiré de la maquette de référence :
 * - Grands anneaux lumineux 3D en perspective (déformation organique, rotation douce, reflets néon).
 * - Courbe lumineuse centrale (ruban d'aurore S-curve fluide).
 * - Sphères 3D translucides flottantes avec reflets spéculaires et caustiques de verre.
 * - Particules lumineuses flottantes avec parallaxe en profondeur.
 * - Interaction souris : sillage de vagues d'aurore fluide, réaction des anneaux et parallaxe.
 * - Gestion automatique et fluide du Mode Clair (pastel nacré) et Mode Sombre (bleu nuit néon).
 * - Zéro impact sur les performances, sans librairie 3D lourde, Canvas 2D ultra-optimisé.
 */

export function initAuroraBackground() {
    const canvas = document.getElementById('ec-aurora');
    if (!canvas) return;

    const ctx = canvas.getContext('2d', { alpha: true });
    if (!ctx) return;

    // Détection accessibilité & pointeur
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const pointerQuery = window.matchMedia('(hover: hover) and (pointer: fine)');
    const isTouch = !pointerQuery.matches;

    // Dimensions canvas & support HiDPI / Retina
    let width = 0;
    let height = 0;
    let dpr = 1;

    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    resize();

    // ──────────────────────────────────────────────────────────────────────────
    // GESTION DU THÈME (Light Mode / Dark Mode)
    // ──────────────────────────────────────────────────────────────────────────
    function checkIsDark() {
        return document.documentElement.classList.contains('dark') ||
            localStorage.getItem('ec-theme') === 'dark' ||
            (!localStorage.getItem('ec-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
    }

    let isDarkTarget = checkIsDark();
    let darkProgress = isDarkTarget ? 1.0 : 0.0; // Interpolation fluide entre 0 (light) et 1 (dark)

    // Observateur pour détecter le changement de classe sur <html>
    const themeObserver = new MutationObserver(() => {
        isDarkTarget = checkIsDark();
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    // ──────────────────────────────────────────────────────────────────────────
    // PALETTES DE COULEURS INTERPOLÉES
    // ──────────────────────────────────────────────────────────────────────────
    // Couleurs Light Mode (pastel doux, nacré, élégant)
    const LIGHT = {
        bgTop: 'rgba(251, 249, 255, 1)',
        bgMid: 'rgba(244, 241, 253, 0.95)',
        bgBottom: 'rgba(238, 242, 255, 0.95)',
        ring1Core: [56, 189, 248],    // cyan ciel #38bdf8
        ring1Mid:  [129, 140, 248],   // lavande violet #818cf8
        ring1Edge: [244, 114, 182],   // rose poudré #f472b6
        ring2Core: [168, 85, 247],    // violet doux #a855f7
        ring2Mid:  [236, 72, 153],    // rose pastel #ec4899
        ring2Edge: [99, 102, 241],    // indigo clair #6366f1
        streamCyan:   [56, 189, 248],
        streamViolet: [139, 92, 246],
        streamPink:   [244, 114, 182],
        orbShadow: 'rgba(120, 110, 180, 0.15)',
        orbAlpha: 0.75,
        ringAlphaMult: 0.65,
        streamAlphaMult: 0.55,
        composite: 'source-over'
    };

    // Couleurs Dark Mode (bleu nuit profond, violet électrique, néons vifs)
    const DARK = {
        bgTop: 'rgba(6, 8, 24, 1)',
        bgMid: 'rgba(11, 14, 43, 0.98)',
        bgBottom: 'rgba(17, 13, 46, 0.98)',
        ring1Core: [0, 229, 255],     // cyan néon intense #00e5ff
        ring1Mid:  [139, 92, 246],    // violet électrique #8b5cf6
        ring1Edge: [236, 72, 153],    // magenta néon #ec4899
        ring2Core: [217, 70, 239],    // fuchsia éclatant #d946ef
        ring2Mid:  [99, 102, 241],    // bleu indigo électrique #6366f1
        ring2Edge: [0, 240, 255],     // cyan néon #00f0ff
        streamCyan:   [0, 229, 255],
        streamViolet: [147, 51, 234],
        streamPink:   [236, 72, 153],
        orbShadow: 'rgba(0, 0, 0, 0.6)',
        orbAlpha: 0.92,
        ringAlphaMult: 1.0,
        streamAlphaMult: 0.9,
        composite: 'screen'
    };

    function lerpColor(c1, c2, t) {
        return [
            Math.round(c1[0] + (c2[0] - c1[0]) * t),
            Math.round(c1[1] + (c2[1] - c1[1]) * t),
            Math.round(c1[2] + (c2[2] - c1[2]) * t)
        ];
    }

    function rgba(color, a) {
        return `rgba(${color[0]}, ${color[1]}, ${color[2]}, ${Math.max(0, Math.min(1, a))})`;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MODÈLE DES ANNEAUX 3D EN PERSPECTIVE
    // ──────────────────────────────────────────────────────────────────────────
    // Deux anneaux principaux inspirés exactement de la maquette :
    // - Anneau 1 : à gauche, enveloppant le titre ("Construisez votre avenir...")
    // - Anneau 2 : à droite, entourant la carte des étudiants / image hero
    // - Anneau 3 : central plus discret qui crée la profondeur et le liant
    const rings = [
        {
            // Anneau Gauche (Hero Titre)
            posXRatio: 0.22,
            posYRatio: 0.48,
            baseRadiusX: 280,
            baseRadiusY: 125,
            rotZ: -0.38, // inclinaison -22°
            rotX: 1.15,  // angle de perspective 3D (pitch)
            rotY: 0.25,
            thickness: 24,
            pulsePhase: 0,
            pulseSpeed: 0.35,
            waveFreq: 3,
            waveAmp: 9,
            spinSpeed: 0.04,
            type: 1
        },
        {
            // Anneau Droite (Hero Image / Cartes)
            posXRatio: 0.78,
            posYRatio: 0.44,
            baseRadiusX: 330,
            baseRadiusY: 145,
            rotZ: 0.42,  // inclinaison +24°
            rotX: 1.22,
            rotY: -0.30,
            thickness: 28,
            pulsePhase: 2.5,
            pulseSpeed: 0.28,
            waveFreq: 4,
            waveAmp: 11,
            spinSpeed: -0.035,
            type: 2
        },
        {
            // Anneau Central de liaison en profondeur
            posXRatio: 0.50,
            posYRatio: 0.38,
            baseRadiusX: 420,
            baseRadiusY: 170,
            rotZ: -0.12,
            rotX: 1.35,
            rotY: 0.10,
            thickness: 18,
            pulsePhase: 1.2,
            pulseSpeed: 0.22,
            waveFreq: 3,
            waveAmp: 7,
            spinSpeed: 0.02,
            type: 3
        }
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // SPHÈRES 3D TRANSLUCIDES (GLASS ORBS)
    // ──────────────────────────────────────────────────────────────────────────
    // Placées stratégiquement autour des anneaux et du contenu comme sur la maquette
    const orbs = [
        {
            // Sphère 1 (Gauche bas, rose/violette, assez grande)
            baseX: 0.12, baseY: 0.68, radius: 26,
            colorLight: [217, 70, 239], colorDark: [236, 72, 153],
            floatSpeed: 0.5, floatAmpX: 12, floatAmpY: 16, phase: 0.2, parallax: 0.035
        },
        {
            // Sphère 2 (Gauche milieu-haut, cyan délicat)
            baseX: 0.09, baseY: 0.32, radius: 15,
            colorLight: [56, 189, 248], colorDark: [0, 229, 255],
            floatSpeed: 0.65, floatAmpX: 10, floatAmpY: 14, phase: 1.8, parallax: 0.025
        },
        {
            // Sphère 3 (Centre haut, indigo)
            baseX: 0.41, baseY: 0.18, radius: 13,
            colorLight: [129, 140, 248], colorDark: [99, 102, 241],
            floatSpeed: 0.45, floatAmpX: 8, floatAmpY: 11, phase: 3.2, parallax: 0.02
        },
        {
            // Sphère 4 (Centre bas, cyan doux)
            baseX: 0.53, baseY: 0.68, radius: 14,
            colorLight: [56, 189, 248], colorDark: [59, 130, 246],
            floatSpeed: 0.55, floatAmpX: 11, floatAmpY: 15, phase: 4.6, parallax: 0.03
        },
        {
            // Sphère 5 (Droite haut, rose fuchsia brillante)
            baseX: 0.94, baseY: 0.16, radius: 22,
            colorLight: [236, 72, 153], colorDark: [217, 70, 239],
            floatSpeed: 0.4, floatAmpX: 13, floatAmpY: 17, phase: 5.4, parallax: 0.04
        },
        {
            // Sphère 6 (Droite bas, violette)
            baseX: 0.88, baseY: 0.78, radius: 12,
            colorLight: [139, 92, 246], colorDark: [147, 51, 234],
            floatSpeed: 0.6, floatAmpX: 9, floatAmpY: 13, phase: 2.1, parallax: 0.022
        }
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // PARTICULES LUMINEUSES (STAR DUST)
    // ──────────────────────────────────────────────────────────────────────────
    const PARTICLE_COUNT = 32;
    const particles = [];
    for (let i = 0; i < PARTICLE_COUNT; i++) {
        particles.push({
            xRatio: Math.random(),
            yRatio: Math.random(),
            radius: 1.2 + Math.random() * 2.2,
            depth: 0.2 + Math.random() * 0.8,
            speedX: (Math.random() - 0.5) * 8,
            speedY: (Math.random() - 0.5) * 8,
            twinklePhase: Math.random() * Math.PI * 2,
            twinkleSpeed: 1.0 + Math.random() * 2.0,
            baseAlpha: 0.2 + Math.random() * 0.5
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GESTION DU CURSEUR & VAGUES INTERACTIVES
    // ──────────────────────────────────────────────────────────────────────────
    const mouse = {
        x: -2000,
        y: -2000,
        normX: 0,
        normY: 0,
        active: false,
        speed: 0,
        lastX: 0,
        lastY: 0,
        lastTime: 0
    };

    const head = { x: -2000, y: -2000, opacity: 0, energy: 0 };
    const interactiveWaves = [];
    const MAX_INTERACTIVE_WAVES = 10;
    let lastWaveSpawn = 0;

    function spawnInteractiveWave(x, y, vx, vy, speed) {
        if (interactiveWaves.length >= MAX_INTERACTIVE_WAVES) interactiveWaves.shift();

        const moveAngle = Math.atan2(vy, vx);
        const waveAngle = moveAngle + Math.PI / 2 + (Math.random() - 0.5) * 0.5;
        const energy = Math.min(1.0, Math.max(0.2, speed / 30));

        interactiveWaves.push({
            x, y,
            driftX: Math.cos(moveAngle) * 0.8 + Math.cos(waveAngle) * (Math.random() - 0.5) * 1.2,
            driftY: Math.sin(moveAngle) * 0.8 + Math.sin(waveAngle) * (Math.random() - 0.5) * 1.2,
            angle: waveAngle,
            span: 180 + energy * 160,
            thickness: 34 + energy * 30,
            amplitude: 22 + energy * 30,
            freq: 2.2 + Math.random() * 1.5,
            phase: Math.random() * Math.PI * 2,
            age: 0,
            ttl: 2.6 + energy * 1.2,
            energy
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PROJECTION 3D D'UN ANNEAU DÉFORMÉ
    // ──────────────────────────────────────────────────────────────────────────
    function project3DPoint(x, y, z, cx, cy, rotX, rotY, rotZ, focal) {
        // Rotation Z (inclinaison dans le plan)
        const cosZ = Math.cos(rotZ), sinZ = Math.sin(rotZ);
        const x1 = x * cosZ - y * sinZ;
        const y1 = x * sinZ + y * cosZ;
        const z1 = z;

        // Rotation X (perspective pitch)
        const cosX = Math.cos(rotX), sinX = Math.sin(rotX);
        const y2 = y1 * cosX - z1 * sinX;
        const z2 = y1 * sinX + z1 * cosX;
        const x2 = x1;

        // Rotation Y (légère inclinaison latérale)
        const cosY = Math.cos(rotY), sinY = Math.sin(rotY);
        const x3 = x2 * cosY + z2 * sinY;
        const z3 = -x2 * sinY + z2 * cosY;
        const y3 = y2;

        // Projection perspective
        const scale = focal / (focal + z3);
        return {
            x: cx + x3 * scale,
            y: cy + y3 * scale,
            z: z3,
            scale: scale
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RENDU D'UN ANNEAU LUMINEUX 3D
    // ──────────────────────────────────────────────────────────────────────────
    function drawRing3D(ring, time, darkT) {
        const cx = ring.posXRatio * width + mouse.normX * 18;
        const cy = ring.posYRatio * height + mouse.normY * 18;

        // Animation lente : rotation, respiration et déformation
        const currentRotZ = ring.rotZ + Math.sin(time * 0.15 + ring.pulsePhase) * 0.05 + mouse.normX * 0.05;
        const currentRotX = ring.rotX + Math.cos(time * 0.12) * 0.04 + mouse.normY * 0.05;
        const currentRotY = ring.rotY + Math.sin(time * 0.18) * 0.04;

        const breathe = 1 + Math.sin(time * 0.35 + ring.pulsePhase) * 0.04;
        const rx = ring.baseRadiusX * breathe;
        const ry = ring.baseRadiusY * breathe;
        const focal = 600;

        const segments = 64;
        const outerPoints = [];
        const innerPoints = [];
        const corePoints = [];

        // Couleurs courantes interpolées
        const isType1 = ring.type === 1;
        const cCore = isType1
            ? lerpColor(LIGHT.ring1Core, DARK.ring1Core, darkT)
            : lerpColor(LIGHT.ring2Core, DARK.ring2Core, darkT);
        const cMid = isType1
            ? lerpColor(LIGHT.ring1Mid, DARK.ring1Mid, darkT)
            : lerpColor(LIGHT.ring2Mid, DARK.ring2Mid, darkT);
        const cEdge = isType1
            ? lerpColor(LIGHT.ring1Edge, DARK.ring1Edge, darkT)
            : lerpColor(LIGHT.ring2Edge, DARK.ring2Edge, darkT);

        const alphaBase = isType1 ? 0.35 : 0.28;
        const alphaMultiplier = 1 + darkT * 0.55;

        // Calcul des points en 3D
        for (let i = 0; i <= segments; i++) {
            const theta = (i / segments) * Math.PI * 2;

            // Déformation organique (harmoniques ondulatoires)
            const deform = Math.sin(theta * ring.waveFreq + time * ring.spinSpeed * 10 + ring.pulsePhase) * ring.waveAmp
                         + Math.cos(theta * (ring.waveFreq + 2) - time * 0.2) * (ring.waveAmp * 0.4);

            const rCurrX = rx + deform;
            const rCurrY = ry + deform * 0.6;

            const px = Math.cos(theta) * rCurrX;
            const py = Math.sin(theta) * rCurrY;

            // Demi-épaisseur
            const halfThick = ring.thickness * 0.5;

            // Projection du centre, du bord externe et interne
            const pCore = project3DPoint(px, py, 0, cx, cy, currentRotX, currentRotY, currentRotZ, focal);
            const pOuter = project3DPoint(px * (1 + halfThick / rx), py * (1 + halfThick / ry), halfThick * 0.3, cx, cy, currentRotX, currentRotY, currentRotZ, focal);
            const pInner = project3DPoint(px * (1 - halfThick / rx), py * (1 - halfThick / ry), -halfThick * 0.3, cx, cy, currentRotX, currentRotY, currentRotZ, focal);

            corePoints.push(pCore);
            outerPoints.push(pOuter);
            innerPoints.push(pInner);
        }

        // 1. Halo néon large et diffus autour de l'anneau
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(outerPoints[0].x, outerPoints[0].y);
        for (let i = 1; i < outerPoints.length; i++) {
            ctx.lineTo(outerPoints[i].x, outerPoints[i].y);
        }
        for (let i = innerPoints.length - 1; i >= 0; i--) {
            ctx.lineTo(innerPoints[i].x, innerPoints[i].y);
        }
        ctx.closePath();

        const gradRing = ctx.createLinearGradient(
            outerPoints[0].x, outerPoints[0].y,
            outerPoints[Math.floor(segments / 2)].x, outerPoints[Math.floor(segments / 2)].y
        );

        const a1 = (alphaBase * 0.45) * alphaMultiplier;
        const a2 = (alphaBase * 0.70) * alphaMultiplier;
        const a3 = (alphaBase * 0.35) * alphaMultiplier;

        gradRing.addColorStop(0, rgba(cEdge, a1));
        gradRing.addColorStop(0.35, rgba(cMid, a2));
        gradRing.addColorStop(0.70, rgba(cCore, a2));
        gradRing.addColorStop(1, rgba(cEdge, a3));

        ctx.fillStyle = gradRing;
        ctx.fill();
        ctx.restore();

        // 2. Cœur lumineux brillant le long du filament central (ligne néon vive)
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(corePoints[0].x, corePoints[0].y);
        for (let i = 1; i < corePoints.length; i++) {
            const prev = corePoints[i - 1];
            const curr = corePoints[i];
            ctx.quadraticCurveTo(prev.x, prev.y, (prev.x + curr.x) / 2, (prev.y + curr.y) / 2);
        }
        ctx.closePath();

        ctx.lineWidth = darkT > 0.5 ? 2.5 : 1.8;
        const coreAlpha = (0.55 + darkT * 0.35);
        ctx.strokeStyle = rgba(cCore, coreAlpha);
        ctx.stroke();

        // 3. Impulsion lumineuse en déplacement le long de l'anneau
        const pulseProgress = (time * ring.pulseSpeed) % 1;
        const pulseIdx = Math.floor(pulseProgress * segments);
        const pSpot = corePoints[pulseIdx];
        if (pSpot) {
            const spotGrad = ctx.createRadialGradient(pSpot.x, pSpot.y, 0, pSpot.x, pSpot.y, 35);
            spotGrad.addColorStop(0, rgba([255, 255, 255], 0.85 * alphaMultiplier));
            spotGrad.addColorStop(0.3, rgba(cCore, 0.65 * alphaMultiplier));
            spotGrad.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.fillStyle = spotGrad;
            ctx.beginPath();
            ctx.arc(pSpot.x, pSpot.y, 35, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.restore();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RENDU DE LA COURBE LUMINEUSE CENTRALE (S-CURVE AURORA STREAM)
    // ──────────────────────────────────────────────────────────────────────────
    function drawAuroraStream(time, darkT) {
        // Points de contrôle de la grande courbe lumineuse en S
        const startX = width * 0.02;
        const startY = height * 0.62 + Math.sin(time * 0.25) * 14;

        const cp1X = width * 0.32 + Math.cos(time * 0.2) * 20;
        const cp1Y = height * 0.72 + Math.sin(time * 0.3) * 18;

        const cp2X = width * 0.48 + Math.sin(time * 0.22) * 18;
        const cp2Y = height * 0.32 + Math.cos(time * 0.28) * 16;

        const cp3X = width * 0.72 + Math.cos(time * 0.18) * 22;
        const cp3Y = height * 0.18 + Math.sin(time * 0.24) * 20;

        const endX = width * 0.98;
        const endY = height * 0.42 + Math.cos(time * 0.2) * 15;

        // Tracé avec 4 épaisseurs dégradées successives pour créer l'effet volumique
        const layers = [
            { width: 55, alpha: 0.12, blur: 24 },
            { width: 28, alpha: 0.22, blur: 14 },
            { width: 10, alpha: 0.45, blur: 6 },
            { width: 2.5, alpha: 0.80, blur: 2 }
        ];

        const cCyan = lerpColor(LIGHT.streamCyan, DARK.streamCyan, darkT);
        const cViolet = lerpColor(LIGHT.streamViolet, DARK.streamViolet, darkT);
        const cPink = lerpColor(LIGHT.streamPink, DARK.streamPink, darkT);

        const grad = ctx.createLinearGradient(startX, startY, endX, endY);
        grad.addColorStop(0, rgba(cCyan, 0));
        grad.addColorStop(0.18, rgba(cCyan, 0.85));
        grad.addColorStop(0.50, rgba(cViolet, 0.90));
        grad.addColorStop(0.82, rgba(cPink, 0.85));
        grad.addColorStop(1, rgba(cPink, 0));

        layers.forEach(layer => {
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(startX, startY);
            ctx.bezierCurveTo(cp1X, cp1Y, cp2X, cp2Y, (cp1X + cp3X) / 2, (cp1Y + cp3Y) / 2);
            ctx.bezierCurveTo(cp2X, cp2Y, cp3X, cp3Y, endX, endY);

            ctx.lineWidth = layer.width;
            ctx.lineCap = 'round';
            ctx.globalAlpha = layer.alpha * (darkT > 0.5 ? 1.0 : 0.65);
            ctx.strokeStyle = grad;
            ctx.stroke();
            ctx.restore();
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RENDU D'UNE SPHÈRE 3D TRANSLUCIDE (GLASS ORB)
    // ──────────────────────────────────────────────────────────────────────────
    function drawGlassOrb(orb, time, darkT) {
        // Mouvement flottant oscillatoire doux
        const floatX = Math.sin(time * orb.floatSpeed + orb.phase) * orb.floatAmpX;
        const floatY = Math.cos(time * orb.floatSpeed * 0.85 + orb.phase) * orb.floatAmpY;

        // Parallaxe souris
        const px = orb.baseX * width + floatX + mouse.normX * (orb.parallax * width);
        const py = orb.baseY * height + floatY + mouse.normY * (orb.parallax * height);
        const r = orb.radius;

        const color = lerpColor(orb.colorLight, orb.colorDark, darkT);

        ctx.save();

        // 1. Ombre portée douce
        const shadowGrad = ctx.createRadialGradient(px, py + r * 0.5, r * 0.6, px, py + r * 0.5, r * 1.8);
        const shadowColor = darkT > 0.5 ? 'rgba(0, 0, 0, 0.45)' : 'rgba(120, 110, 180, 0.12)';
        shadowGrad.addColorStop(0, shadowColor);
        shadowGrad.addColorStop(1, 'rgba(0, 0, 0, 0)');
        ctx.fillStyle = shadowGrad;
        ctx.beginPath();
        ctx.arc(px, py + r * 0.5, r * 1.8, 0, Math.PI * 2);
        ctx.fill();

        // 2. Halo diffus / lueur interne
        const haloGrad = ctx.createRadialGradient(px, py, r * 0.5, px, py, r * 2.2);
        const haloAlpha = darkT > 0.5 ? 0.35 : 0.18;
        haloGrad.addColorStop(0, rgba(color, haloAlpha));
        haloGrad.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = haloGrad;
        ctx.beginPath();
        ctx.arc(px, py, r * 2.2, 0, Math.PI * 2);
        ctx.fill();

        // 3. Corps sphérique translucide avec bord lumineux
        const bodyGrad = ctx.createRadialGradient(
            px - r * 0.25, py - r * 0.25, r * 0.1,
            px, py, r
        );
        const baseA = darkT > 0.5 ? 0.85 : 0.65;
        bodyGrad.addColorStop(0, rgba([255, 255, 255], 0.45 * baseA));
        bodyGrad.addColorStop(0.35, rgba(color, 0.40 * baseA));
        bodyGrad.addColorStop(0.80, rgba(color, 0.75 * baseA));
        bodyGrad.addColorStop(1.0, rgba(color, 0.95 * baseA));

        ctx.fillStyle = bodyGrad;
        ctx.beginPath();
        ctx.arc(px, py, r, 0, Math.PI * 2);
        ctx.fill();

        // 4. Reflet spéculaire de verre (accent lumineux haut-gauche)
        const specX = px - r * 0.32;
        const specY = py - r * 0.32;
        const specGrad = ctx.createRadialGradient(specX, specY, 0, specX, specY, r * 0.45);
        specGrad.addColorStop(0, 'rgba(255, 255, 255, 0.95)');
        specGrad.addColorStop(0.5, 'rgba(255, 255, 255, 0.45)');
        specGrad.addColorStop(1, 'rgba(255, 255, 255, 0)');

        ctx.fillStyle = specGrad;
        ctx.beginPath();
        ctx.arc(specX, specY, r * 0.45, 0, Math.PI * 2);
        ctx.fill();

        // 5. Reflet secondaire au bord opposé (caustique)
        const rimX = px + r * 0.28;
        const rimY = py + r * 0.28;
        const rimGrad = ctx.createRadialGradient(rimX, rimY, 0, rimX, rimY, r * 0.35);
        rimGrad.addColorStop(0, rgba([255, 255, 255], 0.35));
        rimGrad.addColorStop(1, 'rgba(255, 255, 255, 0)');
        ctx.fillStyle = rimGrad;
        ctx.beginPath();
        ctx.arc(rimX, rimY, r * 0.35, 0, Math.PI * 2);
        ctx.fill();

        ctx.restore();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RENDU DES PARTICULES SCINTILLANTES
    // ──────────────────────────────────────────────────────────────────────────
    function drawParticles(time, darkT) {
        particles.forEach(p => {
            const x = (p.xRatio * width + Math.sin(time * 0.2 + p.speedX) * 15 + mouse.normX * (p.depth * 30) + width) % width;
            const y = (p.yRatio * height + Math.cos(time * 0.2 + p.speedY) * 15 + mouse.normY * (p.depth * 30) + height) % height;

            const twinkle = 0.5 + 0.5 * Math.sin(time * p.twinkleSpeed + p.twinklePhase);
            const alpha = p.baseAlpha * twinkle * (darkT > 0.5 ? 0.8 : 0.45);

            const pColor = darkT > 0.5 ? [200, 235, 255] : [140, 130, 210];

            ctx.save();
            ctx.beginPath();
            ctx.arc(x, y, p.radius * (0.8 + twinkle * 0.4), 0, Math.PI * 2);
            ctx.fillStyle = rgba(pColor, alpha);
            ctx.fill();
            ctx.restore();
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RENDU DES VAGUES INTERACTIVES ÉMISES PAR LA SOURIS
    // ──────────────────────────────────────────────────────────────────────────
    function drawInteractiveWave(wave, dt, darkT) {
        wave.age += dt / wave.ttl;
        if (wave.age >= 1) return false;

        wave.x += wave.driftX * dt * 42;
        wave.y += wave.driftY * dt * 42;

        const growth = 1 + wave.age * 0.95;
        const curSpan = wave.span * growth;
        const curThickness = wave.thickness * (1 + wave.age * 0.7);
        const curAmp = wave.amplitude * (1 - wave.age * 0.3);

        const envelope = Math.sin(Math.pow(wave.age, 0.7) * Math.PI);
        const maxAlpha = darkT > 0.5 ? (0.28 + wave.energy * 0.16) : (0.16 + wave.energy * 0.12);
        const alpha = envelope * maxAlpha;

        if (alpha <= 0.005) return true;

        const cosA = Math.cos(wave.angle);
        const sinA = Math.sin(wave.angle);
        const normX = -sinA;
        const normY = cosA;

        const segments = 6;
        const crest = [];
        const trough = [];

        for (let i = 0; i <= segments; i++) {
            const u = (i / segments) - 0.5;
            const harm = Math.sin(u * Math.PI * wave.freq + wave.age * 4 + wave.phase) * curAmp;
            const taper = Math.sin((i / segments) * Math.PI);
            const halfThick = (curThickness * 0.5 * taper) + 3;

            const bx = wave.x + cosA * (u * curSpan);
            const by = wave.y + sinA * (u * curSpan);

            crest.push({ x: bx + normX * (harm + halfThick), y: by + normY * (harm + halfThick) });
            trough.push({ x: bx + normX * (harm - halfThick), y: by + normY * (harm - halfThick) });
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

        const grad = ctx.createLinearGradient(crest[0].x, crest[0].y, crest[crest.length - 1].x, crest[crest.length - 1].y);
        const c1 = lerpColor(LIGHT.ring1Core, DARK.ring1Core, darkT);
        const c2 = lerpColor(LIGHT.ring1Mid, DARK.ring1Mid, darkT);
        const c3 = lerpColor(LIGHT.ring2Edge, DARK.ring2Edge, darkT);

        grad.addColorStop(0, rgba(c1, 0));
        grad.addColorStop(0.25, rgba(c1, alpha));
        grad.addColorStop(0.55, rgba(c2, alpha * 0.95));
        grad.addColorStop(0.85, rgba(c3, alpha * 0.85));
        grad.addColorStop(1, rgba(c3, 0));

        ctx.fillStyle = grad;
        ctx.fill();
        ctx.restore();

        return true;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HALO DIFFUS À LA SOURIS (FOLLOWING CURSOR GLOW)
    // ──────────────────────────────────────────────────────────────────────────
    function drawHeadGlow(x, y, energy, alpha, time, darkT) {
        if (alpha <= 0.005) return;
        const baseRadius = 140 + energy * 60;

        const lobes = [
            {
                color: lerpColor(LIGHT.ring1Mid, DARK.ring1Mid, darkT),
                ox: Math.cos(time * 0.8) * 32,
                oy: Math.sin(time * 0.7) * 22,
                r: baseRadius * (1 + Math.sin(time * 1.1) * 0.12),
                weight: 0.85
            },
            {
                color: lerpColor(LIGHT.ring1Core, DARK.ring1Core, darkT),
                ox: Math.cos(time * 0.9 + 2.0) * 36,
                oy: Math.sin(time * 0.8 + 2.0) * 26,
                r: baseRadius * 0.92 * (1 + Math.cos(time * 0.9 + 1.4) * 0.1),
                weight: 0.75
            },
            {
                color: lerpColor(LIGHT.ring2Mid, DARK.ring2Mid, darkT),
                ox: Math.cos(time * 0.7 + 4.2) * 28,
                oy: Math.sin(time * 1.0 + 4.2) * 28,
                r: baseRadius * 0.82 * (1 + Math.sin(time * 0.8 + 3.0) * 0.14),
                weight: 0.65
            }
        ];

        lobes.forEach(lobe => {
            const lx = x + lobe.ox;
            const ly = y + lobe.oy;
            const lAlpha = alpha * lobe.weight * (darkT > 0.5 ? 0.32 : 0.18);

            ctx.save();
            ctx.translate(lx, ly);
            ctx.rotate(time * 0.15 + lobe.weight);
            ctx.scale(1.15, 0.85);

            const g = ctx.createRadialGradient(0, 0, 0, 0, 0, lobe.r);
            g.addColorStop(0, rgba(lobe.color, lAlpha));
            g.addColorStop(0.4, rgba(lobe.color, lAlpha * 0.55));
            g.addColorStop(0.75, rgba(lobe.color, lAlpha * 0.18));
            g.addColorStop(1, 'rgba(0,0,0,0)');

            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(0, 0, lobe.r, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BOUCLE DE RENDU PRINCIPALE
    // ──────────────────────────────────────────────────────────────────────────
    let animId = null;
    let lastFrameTime = performance.now();
    let time = 0;

    function render(now) {
        animId = null;
        const dt = Math.min(0.05, (now - lastFrameTime) / 1000 || 0.016);
        lastFrameTime = now;
        time += dt;

        // Transition douce de thème
        darkProgress += ((isDarkTarget ? 1.0 : 0.0) - darkProgress) * Math.min(1, dt * 5.5);

        // Suivi fluide du curseur (ressort naturel)
        if (mouse.active) {
            if (head.x < -1000) { head.x = mouse.x; head.y = mouse.y; }
            const dx = mouse.x - head.x;
            const dy = mouse.y - head.y;
            head.x += dx * 0.085;
            head.y += dy * 0.085;
            head.opacity += (1 - head.opacity) * Math.min(1, dt * 3.5);
            head.energy += (Math.min(1, Math.hypot(dx, dy) / 40) - head.energy) * Math.min(1, dt * 4.0);

            mouse.normX += ((mouse.x / width - 0.5) - mouse.normX) * Math.min(1, dt * 3.0);
            mouse.normY += ((mouse.y / height - 0.5) - mouse.normY) * Math.min(1, dt * 3.0);
        } else {
            head.opacity += (0 - head.opacity) * Math.min(1, dt * 2.2);
            head.energy += (0 - head.energy) * Math.min(1, dt * 2.2);
            mouse.normX += (0 - mouse.normX) * Math.min(1, dt * 2.0);
            mouse.normY += (0 - mouse.normY) * Math.min(1, dt * 2.0);
        }

        // Émission de vagues lors de mouvements rapides
        if (mouse.active && mouse.speed > 2.5) {
            const interval = mouse.speed > 20 ? 70 : 130;
            if (now - lastWaveSpawn > interval) {
                lastWaveSpawn = now;
                const vx = mouse.x - mouse.lastX;
                const vy = mouse.y - mouse.lastY;
                spawnInteractiveWave(head.x, head.y, vx, vy, mouse.speed);
            }
        }

        ctx.clearRect(0, 0, width, height);

        // Composite : 'screen' en Dark Mode pour une luminescence éclatante sans brûlure,
        // 'source-over' en Light Mode pour des pastels clairs et purs.
        ctx.globalCompositeOperation = darkProgress > 0.5 ? 'screen' : 'source-over';

        // 1. Courbe lumineuse centrale (S-Curve Aurora Stream)
        drawAuroraStream(time, darkProgress);

        // 2. Anneaux lumineux 3D en perspective
        rings.forEach(ring => drawRing3D(ring, time, darkProgress));

        // 3. Vagues interactives de sillage de souris
        for (let i = interactiveWaves.length - 1; i >= 0; i--) {
            const alive = drawInteractiveWave(interactiveWaves[i], dt, darkProgress);
            if (!alive) interactiveWaves.splice(i, 1);
        }

        // 4. Sphères 3D translucides flottantes (Glass Orbs)
        orbs.forEach(orb => drawGlassOrb(orb, time, darkProgress));

        // 5. Particules flottantes scintillantes
        drawParticles(time, darkProgress);

        // 6. Lumière diffuse suivant le curseur
        if (head.opacity > 0.01 && head.x > -200) {
            drawHeadGlow(head.x, head.y, head.energy, head.opacity, time, darkProgress);
        }

        // Boucle continue (lente et continue pour les anneaux et orbs)
        if (!motionQuery.matches) {
            animId = requestAnimationFrame(render);
        }
    }

    function startAnimation() {
        if (!animId && !motionQuery.matches) {
            lastFrameTime = performance.now();
            animId = requestAnimationFrame(render);
        }
    }

    // Si prefers-reduced-motion est activé : un seul paint statique
    if (motionQuery.matches) {
        render(performance.now());
        return;
    }

    startAnimation();

    // ──────────────────────────────────────────────────────────────────────────
    // ÉCOUTEURS D'ÉVÉNEMENTS
    // ──────────────────────────────────────────────────────────────────────────
    if (!isTouch) {
        window.addEventListener('mousemove', (e) => {
            const now = performance.now();
            const dt = (now - mouse.lastTime) / 1000 || 0.016;

            if (mouse.active) {
                const dist = Math.hypot(e.clientX - mouse.x, e.clientY - mouse.y);
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

            startAnimation();
        }, { passive: true });

        document.addEventListener('mouseenter', (e) => {
            mouse.active = true;
            mouse.x = e.clientX;
            mouse.y = e.clientY;
            mouse.lastX = e.clientX;
            mouse.lastY = e.clientY;
            mouse.lastTime = performance.now();
            startAnimation();
        });

        document.addEventListener('mouseleave', () => {
            mouse.active = false;
            mouse.speed = 0;
        });
    }

    window.addEventListener('resize', () => {
        resize();
        if (motionQuery.matches) render(performance.now());
        else startAnimation();
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (animId) {
                cancelAnimationFrame(animId);
                animId = null;
            }
        } else {
            startAnimation();
        }
    });

    window.addEventListener('pagehide', () => {
        if (animId) {
            cancelAnimationFrame(animId);
            animId = null;
        }
    });

    window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
            resize();
            startAnimation();
        }
    });
}
