<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta name="theme-color" content="#07131c"/>
    <meta name="description" content="{$config['appName']} — kết nối mạng ổn định cho SoftBank Nhật Bản và nhu cầu xuyên biên giới. Truy cập nhanh, bảo mật, đa nền tảng."/>
    <title>{$config['appName']} — Kết nối mạng toàn cầu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link href="/assets/css/tabler-icons.min.css?v=3.31.0" rel="stylesheet"/>
    <link href="/assets/css/landing.css?v=20260723b" rel="stylesheet"/>
</head>
<body class="landing-page">
<div class="ld-atmosphere" aria-hidden="true"></div>

<header class="ld-nav" id="ld-nav">
    <div class="ld-wrap ld-nav-inner">
        <a class="ld-brand" href="/">
            <img src="/images/uim-logo-round_96x96.png" width="30" height="30" alt="{$config['appName']}"/>
            <span>{$config['appName']}</span>
        </a>
        <div class="ld-nav-actions">
            <a class="ld-btn ld-btn-ghost" href="/auth/login">Đăng nhập</a>
            <a class="ld-btn ld-btn-primary" href="/auth/register">Đăng ký</a>
        </div>
    </div>
</header>

<main>
    <section class="ld-hero">
        <div class="ld-hero-visual" aria-hidden="true">
            <span class="ld-hero-orb ld-hero-orb-a"></span>
            <span class="ld-hero-orb ld-hero-orb-b"></span>
            <span class="ld-hero-orb ld-hero-orb-c"></span>
        </div>
        <div class="ld-wrap ld-hero-content">
            <h1 class="ld-hero-brand">{$config['appName']}</h1>
            <p class="ld-hero-title">Kết nối mạng toàn cầu — dùng mọi lúc, mọi nơi</p>
            <p class="ld-hero-lead">
                Kết nối ổn định cho SoftBank Nhật Bản và nhu cầu xuyên biên giới.
                Trải nghiệm nhanh, bảo mật, tương thích Hiddify, Clash và nhiều ứng dụng phổ biến.
            </p>
            <div class="ld-hero-cta">
                <a class="ld-btn ld-btn-primary ld-btn-lg" href="/auth/register">Bắt đầu sử dụng</a>
                <a class="ld-btn ld-btn-outline ld-btn-lg" href="#goi">Xem các gói</a>
            </div>
        </div>
    </section>

    <section class="ld-section" id="tinh-nang">
        <div class="ld-wrap">
            <div class="ld-section-head ld-reveal">
                <span class="ld-kicker">Tại sao chọn chúng tôi</span>
                <h2>Công cụ cho giải trí quốc tế &amp; công việc xuyên biên giới</h2>
                <p>Thiết kế cho người cần đường truyền ổn định trên mạng cố định lẫn 4G/5G, mọi lúc mọi nơi.</p>
            </div>
            <div class="ld-features">
                <article class="ld-feature ld-reveal">
                    <div class="ld-feature-icon" aria-hidden="true"><i class="ti ti-bolt"></i></div>
                    <h3>Nhanh &amp; ổn định</h3>
                    <p>Tốc độ gần như đang ở nước ngoài, phù hợp Wi‑Fi gia đình và mạng di động SoftBank / nhà mạng Việt Nam.</p>
                </article>
                <article class="ld-feature ld-reveal">
                    <div class="ld-feature-icon" aria-hidden="true"><i class="ti ti-devices"></i></div>
                    <h3>Đa nền tảng</h3>
                    <p>Dùng trên macOS, iOS, Android, Windows và Linux với các ứng dụng bên thứ ba quen thuộc.</p>
                </article>
                <article class="ld-feature ld-reveal">
                    <div class="ld-feature-icon" aria-hidden="true"><i class="ti ti-world"></i></div>
                    <h3>Kết nối toàn cầu</h3>
                    <p>Hạ tầng node đa khu vực, ưu tiên độ trễ thấp và trải nghiệm xem phim / làm việc mượt mà.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="ld-section" id="thiet-bi">
        <div class="ld-wrap">
            <div class="ld-section-head ld-reveal">
                <span class="ld-kicker">Mọi thiết bị yêu thích</span>
                <h2>Điện thoại hay máy tính — sẵn sàng mọi lúc</h2>
                <p>
                    {$config['appName']} hoạt động trên macOS, iOS, Android, Windows và Linux.
                    Dùng được trên điện thoại, máy tính, router, máy chơi game và TV box.
                </p>
            </div>
            <div class="ld-platforms ld-reveal">
                <span class="ld-chip">iOS</span>
                <span class="ld-chip">Android</span>
                <span class="ld-chip">Windows</span>
                <span class="ld-chip">macOS</span>
                <span class="ld-chip">Linux</span>
                <span class="ld-chip">Hiddify</span>
                <span class="ld-chip">Clash</span>
                <span class="ld-chip">Router / TV Box</span>
            </div>
        </div>
    </section>

    <section class="ld-section" id="streaming">
        <div class="ld-wrap">
            <div class="ld-section-head ld-reveal">
                <span class="ld-kicker">Giải trí không giới hạn</span>
                <h2>Mở khóa nội dung streaming chất lượng cao</h2>
                <p>
                    Xem phim và nghe nhạc trên các nền tảng phổ biến nhờ đường truyền ổn định
                    và IP phù hợp theo khu vực node bạn chọn.
                </p>
            </div>
        </div>
    </section>

    <section class="ld-section" id="goi">
        <div class="ld-wrap">
            <div class="ld-section-head ld-reveal">
                <span class="ld-kicker">Bảng giá</span>
                <h2>Trải nghiệm tốt — mức giá rõ ràng</h2>
                <p>Đừng lãng phí thời gian chờ đợi. Chọn gói phù hợp và kích hoạt ngay trong tài khoản.</p>
            </div>

            {if $pricing_products|@count > 0}
                <div class="ld-pricing">
                    {foreach $pricing_products as $product}
                        <article class="ld-price-card ld-reveal{if $product.featured} is-featured{/if}">
                            {if $product.featured}
                                <span class="ld-price-badge">Phổ biến</span>
                            {/if}
                            <h3 class="ld-price-name">{$product.name|escape}</h3>
                            <div class="ld-price-amount">
                                <strong>{$product.price|format_vnd:0}</strong>
                                <span>VNĐ</span>
                            </div>
                            <ul class="ld-price-list">
                                {foreach $product.features as $feature}
                                    <li>{$feature|escape}</li>
                                {/foreach}
                            </ul>
                            <a class="ld-btn ld-btn-primary" href="/auth/register">Đăng ký &amp; mua gói</a>
                        </article>
                    {/foreach}
                </div>
            {else}
                <div class="ld-price-empty ld-reveal">
                    Hiện chưa có gói đang bán. Vui lòng đăng nhập sau hoặc liên hệ hỗ trợ.
                </div>
            {/if}
        </div>
    </section>

    <section class="ld-section">
        <div class="ld-wrap">
            <div class="ld-support ld-reveal">
                <div>
                    <h2>Hỗ trợ khách hàng</h2>
                    <p>
                        Cần tư vấn trước khi đăng ký? Đội ngũ hỗ trợ sẵn sàng giải đáp trong suốt chu kỳ đăng ký của bạn.
                    </p>
                </div>
                <a class="ld-btn ld-btn-outline ld-btn-lg" href="/auth/register">Liên hệ qua tài khoản</a>
            </div>
        </div>
    </section>
</main>

<footer class="ld-footer">
    <div class="ld-wrap ld-footer-inner">
        <div>© {$landing_year} {$config['appName']}. Tất cả các quyền được bảo lưu.</div>
        <div>
            <a href="/tos">Điều khoản dịch vụ</a>
        </div>
    </div>
</footer>

<script>
(() => {
    const nav = document.getElementById('ld-nav');
    const onScroll = () => {
        if (!nav) return;
        nav.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const nodes = Array.from(document.querySelectorAll('.ld-reveal'));

    // Stagger siblings in the same parent for smoother cascade.
    const groups = new Map();
    nodes.forEach((el) => {
        const parent = el.parentElement;
        if (!parent) return;
        if (!groups.has(parent)) groups.set(parent, []);
        groups.get(parent).push(el);
    });
    groups.forEach((items) => {
        items.forEach((el, i) => {
            el.style.transitionDelay = `${Math.min(i * 0.07, 0.28)}s`;
        });
    });

    if (reduce || !('IntersectionObserver' in window)) {
        nodes.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
        });
    }, {
        threshold: 0.08,
        rootMargin: '0px 0px -6% 0px',
    });

    nodes.forEach((el) => io.observe(el));
})();
</script>
</body>
</html>
