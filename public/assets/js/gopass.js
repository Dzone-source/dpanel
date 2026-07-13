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
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay?.classList.remove('show');
        }

        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        overlay?.addEventListener('click', closeSidebar);

        sidebar.querySelectorAll('.gopass-nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
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

    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        markActiveNav();
    });
})();
