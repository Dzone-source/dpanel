/*! Trung thu Việt Nam — rơi lồng đèn ông sao */
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

        var items = [];
        var running = true;
        var last = 0;
        var dpr = Math.min(window.devicePixelRatio || 1, 2);

        var palettes = [
            ['#f0d78c', '#c73e2e', '#e8b84a'],
            ['#e8c547', '#c9a227', '#fff6d6'],
            ['#3d8b4f', '#2f6b3c', '#c8f0d0']
        ];

        function countForViewport() {
            var w = window.innerWidth || 360;
            if (w < 480) return 8;
            if (w < 768) return 12;
            if (w < 1200) return 16;
            return 20;
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
            syncCount();
        }

        function makeItem(randomY) {
            var w = window.innerWidth;
            var h = window.innerHeight;
            return {
                x: Math.random() * (w + 80) - 40,
                y: randomY ? Math.random() * h : -40 - Math.random() * h * 0.25,
                size: 14 + Math.random() * 18,
                speedY: 0.28 + Math.random() * 0.55,
                speedX: 0.08 + Math.random() * 0.28,
                swing: 0.4 + Math.random() * 1.0,
                swingSpeed: 0.008 + Math.random() * 0.014,
                angle: (Math.random() - 0.5) * 0.35,
                spin: (Math.random() - 0.5) * 0.012,
                opacity: 0.35 + Math.random() * 0.45,
                palette: Math.floor(Math.random() * palettes.length)
            };
        }

        function syncCount() {
            var target = countForViewport();
            while (items.length < target) items.push(makeItem(true));
            if (items.length > target) items.length = target;
        }

        function drawStarLantern(p) {
            var colors = palettes[p.palette];
            var s = p.size;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.angle);
            ctx.globalAlpha = p.opacity;

            // stick
            ctx.beginPath();
            ctx.moveTo(0, s * 0.15);
            ctx.lineTo(0, s * 0.95);
            ctx.strokeStyle = '#8b5a2b';
            ctx.lineWidth = Math.max(1.2, s * 0.06);
            ctx.lineCap = 'round';
            ctx.stroke();

            // tassels
            ctx.beginPath();
            ctx.moveTo(-s * 0.22, s * 0.95);
            ctx.quadraticCurveTo(0, s * 0.78, s * 0.22, s * 0.95);
            ctx.strokeStyle = colors[1];
            ctx.lineWidth = Math.max(1, s * 0.045);
            ctx.stroke();

            // 5-point star
            ctx.beginPath();
            for (var i = 0; i < 5; i++) {
                var a = -Math.PI / 2 + (i * 2 * Math.PI) / 5;
                var r = s * 0.42;
                var x = Math.cos(a) * r;
                var y = Math.sin(a) * r - s * 0.05;
                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
                var a2 = a + Math.PI / 5;
                var r2 = s * 0.17;
                ctx.lineTo(Math.cos(a2) * r2, Math.sin(a2) * r2 - s * 0.05);
            }
            ctx.closePath();
            var grad = ctx.createLinearGradient(-s * 0.4, -s * 0.4, s * 0.4, s * 0.3);
            grad.addColorStop(0, colors[0]);
            grad.addColorStop(0.55, colors[1]);
            grad.addColorStop(1, colors[2]);
            ctx.fillStyle = grad;
            ctx.fill();
            ctx.strokeStyle = 'rgba(255, 246, 214, 0.65)';
            ctx.lineWidth = 1;
            ctx.stroke();

            // center glow
            ctx.beginPath();
            ctx.arc(0, -s * 0.05, s * 0.08, 0, Math.PI * 2);
            ctx.fillStyle = '#fff6d6';
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

            for (var i = 0; i < items.length; i++) {
                var p = items[i];
                p.angle += p.spin * dt;
                p.y += p.speedY * dt;
                p.x += (Math.sin(p.y * p.swingSpeed) * p.swing + p.speedX * 0.35) * dt;

                if (p.y > h + 40 || p.x < -70 || p.x > w + 70) {
                    items[i] = makeItem(false);
                    continue;
                }
                drawStarLantern(p);
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
