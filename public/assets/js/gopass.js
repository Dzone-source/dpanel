(function () {
    function initSidebar() {
        const sidebar = document.getElementById('gopass-sidebar');
        const toggle = document.getElementById('gopass-sidebar-toggle');
        const overlay = document.getElementById('gopass-sidebar-overlay');

        if (!sidebar || !toggle) {
            return;
        }

        function openSidebar() {
            sidebar.classList.add('open');
            overlay?.classList.add('show');
            document.body.classList.add('gopass-sidebar-open');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay?.classList.remove('show');
            document.body.classList.remove('gopass-sidebar-open');
        }

        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        overlay?.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });

        sidebar.querySelectorAll('.gopass-nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
    }

    function markActiveNav() {
        const path = window.location.pathname;
        document.querySelectorAll('.gopass-nav-link').forEach(function (link) {
            const href = link.getAttribute('href');
            if (!href || href === '#') {
                return;
            }
            if (path === href || (href !== '/user' && path.startsWith(href))) {
                link.classList.add('active');
            }
        });
    }

    function syncThemeUI() {
        const body = document.body;
        if (!body || !body.classList.contains('gopass-theme')) {
            return;
        }

        const mode = body.getAttribute('data-gopass-theme-mode');
        let theme = body.getAttribute('data-bs-theme');

        if (mode === '2' || theme === 'auto') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            body.setAttribute('data-bs-theme', theme);
        }

        if (theme === 'dark' || theme === 'light') {
            document.documentElement.setAttribute('data-bs-theme', theme);
        }

        const toggle = document.getElementById('gopass-theme-toggle');
        if (!toggle) {
            return;
        }

        const icon = toggle.querySelector('i');
        if (!icon) {
            return;
        }

        const isDark = theme === 'dark';
        icon.className = isDark ? 'ti ti-sun' : 'ti ti-moon';
        toggle.setAttribute('title', isDark ? 'Chế độ sáng' : 'Chế độ tối');
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        markActiveNav();
        syncThemeUI();

        if (document.body.getAttribute('data-gopass-theme-mode') === '2') {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', syncThemeUI);
        }
    });
})();
