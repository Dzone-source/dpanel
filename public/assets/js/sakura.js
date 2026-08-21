/*! Mid-Autumn Festival — falling osmanthus (桂花) petals */
(function () {
    'use strict';

    if (window.__gopassSakuraStarted) return;
    window.__gopassSakuraStarted = true;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function start() {
        if (prefersReducedMotion()) return;

        var canvas = document.createElement('canvas');
        canvas.id = 'gopass-sakura';
        canvas.className = 'gopass-sakura-canvas';
        canvas.setAttribute('aria-hidden', 'true');
        canvas.style.cssText = [
            'position:fixed',
            'top:0',
            'left:0',
            'right:0',
            'bottom:0',
            'width:100%',
            'height:100%',
            'max-width:100vw',
            'max-height:100dvh',
            'pointer-events:none',
            'z-index:1040',
            'display:block',
            'margin:0',
            'padding:0',
            'border:0',
            'overflow:hidden'
        ].join(';');
        document.body.appendChild(canvas);

        var ctx = canvas.getContext('2d');
        if (!ctx) return;

        var petals = [];
        var running = true;
        var last = 0;
        var dpr = Math.min(window.devicePixelRatio || 1, 2);

        function countForViewport() {
            var w = window.innerWidth || 360;
            if (w < 480) return 12;
            if (w < 768) return 18;
            if (w < 1200) return 24;
            return 30;
        }

        function resize() {
            dpr = Math.min(window.devicePixelRatio || 1, 2);
            var w = window.innerWidth || document.documentElement.clientWidth || 360;
            var h = window.innerHeight || document.documentElement.clientHeight || 640;
            canvas.width = Math.floor(w * dpr);
            canvas.height = Math.floor(h * dpr);
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';
            canvas.style.position = 'fixed';
            canvas.style.pointerEvents = 'none';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            syncPetalCount();
        }

        function makePetal(randomY) {
            var w = window.innerWidth;
            var h = window.innerHeight;
            return {
                x: Math.random() * (w + 80) - 40,
                y: randomY ? Math.random() * h : -20 - Math.random() * h * 0.3,
                size: 5 + Math.random() * 7,
                speedY: 0.35 + Math.random() * 0.7,
                speedX: 0.15 + Math.random() * 0.4,
                swing: 0.5 + Math.random() * 1.2,
                swingSpeed: 0.01 + Math.random() * 0.018,
                angle: Math.random() * Math.PI * 2,
                spin: (Math.random() - 0.5) * 0.03,
                opacity: 0.4 + Math.random() * 0.45,
                hue: Math.floor(Math.random() * 3)
            };
        }

        function syncPetalCount() {
            var target = countForViewport();
            while (petals.length < target) petals.push(makePetal(true));
            if (petals.length > target) petals.length = target;
        }

        function drawPetal(p) {
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.angle);
            ctx.scale(p.size / 10, p.size / 10);
            ctx.globalAlpha = p.opacity;

            var grad = ctx.createRadialGradient(0, 0, 0.5, 0, 0, 7);
            if (p.hue === 0) {
                grad.addColorStop(0, '#fff6d6');
                grad.addColorStop(0.45, '#e8c547');
                grad.addColorStop(1, '#c9a227');
            } else if (p.hue === 1) {
                grad.addColorStop(0, '#fff9e8');
                grad.addColorStop(0.5, '#f0d78c');
                grad.addColorStop(1, '#b8860b');
            } else {
                grad.addColorStop(0, '#ffe8d6');
                grad.addColorStop(0.5, '#e8b84a');
                grad.addColorStop(1, '#c73e2e');
            }

            // Osmanthus-like 4-petal bloom
            for (var i = 0; i < 4; i++) {
                ctx.save();
                ctx.rotate((Math.PI / 2) * i);
                ctx.beginPath();
                ctx.ellipse(0, -3.2, 2.1, 3.4, 0, 0, Math.PI * 2);
                ctx.fillStyle = grad;
                ctx.fill();
                ctx.restore();
            }

            ctx.beginPath();
            ctx.arc(0, 0, 1.4, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(154, 122, 26, 0.85)';
            ctx.fill();

            ctx.restore();
        }

        function tick(ts) {
            if (!running) return;
            if (!last) last = ts;
            var dt = Math.min(32, ts - last) / 16.67;
            last = ts;

            var w = window.innerWidth;
            var h = window.innerHeight;
            ctx.clearRect(0, 0, w, h);

            for (var i = 0; i < petals.length; i++) {
                var p = petals[i];
                p.angle += p.spin * dt;
                p.y += p.speedY * dt;
                p.x += (Math.sin(p.y * p.swingSpeed) * p.swing + p.speedX * 0.35) * dt;

                if (p.y > h + 30 || p.x < -60 || p.x > w + 60) {
                    petals[i] = makePetal(false);
                    continue;
                }
                drawPetal(p);
            }

            requestAnimationFrame(tick);
        }

        function onVisibility() {
            if (document.hidden) {
                running = false;
                last = 0;
            } else if (!running) {
                running = true;
                requestAnimationFrame(tick);
            }
        }

        resize();
        window.addEventListener('resize', resize, { passive: true });
        document.addEventListener('visibilitychange', onVisibility);
        requestAnimationFrame(tick);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
