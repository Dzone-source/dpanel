/*! Lightweight cherry-blossom (sakura) fall effect */
(function () {
    'use strict';

    if (window.__gopassSakuraStarted) return;
    window.__gopassSakuraStarted = true;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
    }

    function start() {
        if (prefersReducedMotion()) return;

        var canvas = document.createElement('canvas');
        canvas.id = 'gopass-sakura';
        canvas.className = 'gopass-sakura-canvas';
        canvas.setAttribute('aria-hidden', 'true');
        // Inline overlay styles so the canvas never expands document height
        // even when gopass.css is not loaded (e.g. admin pages).
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
            if (w < 480) return 14;
            if (w < 768) return 20;
            if (w < 1200) return 28;
            return 36;
        }

        function resize() {
            dpr = Math.min(window.devicePixelRatio || 1, 2);
            var w = window.innerWidth || document.documentElement.clientWidth || 360;
            var h = window.innerHeight || document.documentElement.clientHeight || 640;
            canvas.width = Math.floor(w * dpr);
            canvas.height = Math.floor(h * dpr);
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';
            // Keep overlay out of document flow after style width/height updates.
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
                size: 7 + Math.random() * 9,
                speedY: 0.45 + Math.random() * 0.9,
                speedX: 0.25 + Math.random() * 0.55,
                swing: 0.6 + Math.random() * 1.4,
                swingSpeed: 0.01 + Math.random() * 0.02,
                angle: Math.random() * Math.PI * 2,
                spin: (Math.random() - 0.5) * 0.04,
                opacity: 0.45 + Math.random() * 0.4,
                hue: Math.random() > 0.55 ? 0 : 1
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
            ctx.scale(p.size / 12, p.size / 12);
            ctx.globalAlpha = p.opacity;

            var grad = ctx.createLinearGradient(-6, -4, 6, 6);
            if (p.hue === 0) {
                grad.addColorStop(0, '#ffe4ec');
                grad.addColorStop(0.45, '#ff9aab');
                grad.addColorStop(1, '#ef4056');
            } else {
                grad.addColorStop(0, '#fff5f7');
                grad.addColorStop(0.5, '#f48291');
                grad.addColorStop(1, '#ff6b81');
            }

            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.bezierCurveTo(4, -8, 10, -2, 0, 10);
            ctx.bezierCurveTo(-10, -2, -4, -8, 0, 0);
            ctx.fillStyle = grad;
            ctx.fill();

            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.quadraticCurveTo(0, 5, 0, 9);
            ctx.strokeStyle = 'rgba(201, 31, 58, 0.28)';
            ctx.lineWidth = 0.7;
            ctx.stroke();

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
