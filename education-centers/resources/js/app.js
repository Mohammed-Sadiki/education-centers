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
