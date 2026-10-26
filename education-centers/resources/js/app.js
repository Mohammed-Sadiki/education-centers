import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.data('attendanceForm', (url, initialScheduleId) => ({
    url,
    scheduleId: initialScheduleId ? String(initialScheduleId) : '',
    students: [],
    status: {},
    loading: false,
    async loadStudents() {
        if (!this.scheduleId) {
            this.students = [];
            return;
        }
        this.loading = true;
        const res = await fetch(`${this.url}?schedule_id=${encodeURIComponent(this.scheduleId)}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        this.students = data.students || [];
        this.status = {};
        this.students.forEach((s) => {
            this.status[s.id] = data.existing && data.existing[s.id] ? data.existing[s.id] : 'present';
        });
        this.loading = false;
    },
    init() {
        if (this.scheduleId) {
            this.loadStudents();
        }
    },
}));

Alpine.store('ui', {
    sidebarOpen: false,
    sidebarCollapsed: localStorage.getItem('ec-sidebar-collapsed') === '1',
    dark: localStorage.getItem('ec-theme') === 'dark' ||
        (localStorage.getItem('ec-theme') == null && window.matchMedia('(prefers-color-scheme: dark)').matches),
    toast: null,
    toastTimer: null,

    init() {
        this.applyTheme();
    },

    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
    },

    closeSidebar() {
        this.sidebarOpen = false;
    },

    toggleCollapsed() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        localStorage.setItem('ec-sidebar-collapsed', this.sidebarCollapsed ? '1' : '0');
    },

    toggleTheme() {
        this.dark = !this.dark;
        localStorage.setItem('ec-theme', this.dark ? 'dark' : 'light');
        this.applyTheme();
    },

    applyTheme() {
        document.documentElement.classList.toggle('dark', this.dark);
    },

    showToast(type, message) {
        this.toast = { type, message };
        clearTimeout(this.toastTimer);
        this.toastTimer = setTimeout(() => {
            this.toast = null;
        }, 4500);
    },

    hideToast() {
        this.toast = null;
        clearTimeout(this.toastTimer);
    },
});

window.Alpine = Alpine;
Alpine.start();

/* ── Micro-interactions (purement visuelles, jamais de modification de données) ──

   1. Count-up des indicateurs KPI : le texte final est toujours restauré à
      l'identique ; désactivé si l'utilisateur réduit les animations.
   2. Feedback de chargement sur les boutons de soumission (formulaires POST).
   3. Nettoyage de l'état de chargement au retour via le cache d'historique.

   Les trois blocs se réinitialisent d'eux-mêmes à chaque navigation complète :   
   aucune animation permanente sur les éléments essentiels. */

const ecReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

function ecCountUp(el) {
    const original = el.textContent;
    const match = original.trimStart().match(/^-?\d[\d\s\u00A0\u202F]*(?:[.,]\d+)?/);
    if (!match) return;

    const raw = match[0];
    const groupSep = raw.includes('\u00A0') ? '\u00A0' : raw.includes('\u202F') ? '\u202F' : raw.includes(' ') ? ' ' : '';
    const decimalSep = raw.includes(',') ? ',' : '.';
    const clean = raw.replace(/[\s\u00A0\u202F]/g, '').replace(',', '.');
    const target = parseFloat(clean);
    if (!Number.isFinite(target)) return;

    const decimals = (clean.split('.')[1] || '').length;
    // Préfixe/suffixe conservés pendant l'animation (« 450 DH », « 78 % »…).    
    const leadLen = original.length - original.trimStart().length;
    const prefix = original.substring(0, leadLen);
    const suffix = original.substring(leadLen + raw.length);
    const format = (value) => {
        const fixed = Math.abs(value).toFixed(decimals);
        const [intPart, decPart] = fixed.split('.');
        const grouped = groupSep ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, groupSep) : intPart;
        return (value < 0 ? '-' : '') + grouped + (decPart ? decimalSep + decPart : '');
    };

    const duration = 650;
    const start = performance.now();
    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = prefix + format(target * eased) + suffix;
        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            el.textContent = original; // restauration exacte de la valeur réelle
        }
    };
    requestAnimationFrame(step);
}

if (!ecReducedMotion.matches) {
    // Laisser le premier paint afficher la valeur réelle avant d'animer.
    requestAnimationFrame(() => {
        document.querySelectorAll('.ec-kpi__value').forEach((el) => {
            if (!el.closest('[data-no-countup]')) ecCountUp(el);
        });
    });
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;
    const btn = form.querySelector('button[type="submit"]:not([data-no-loading])');
    if (!btn || btn.disabled) return;
    btn.classList.add('is-loading');
    btn.setAttribute('aria-busy', 'true');
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('button.is-loading').forEach((btn) => {
        btn.classList.remove('is-loading');
        btn.removeAttribute('aria-busy');
    });
});

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-bs-toggle="modal"]');
    if (opener) {
        const target = document.querySelector(opener.getAttribute('data-bs-target'));
        if (target) {
            target.classList.add('show');
            target.setAttribute('aria-hidden', 'false');
        }
        return;
    }

    const closer = event.target.closest('[data-bs-dismiss="modal"], [data-bs-dismiss="alert"]');
    if (closer) {
        closer.closest('.modal')?.classList.remove('show');
        closer.closest('.alert')?.remove();
    }
});
