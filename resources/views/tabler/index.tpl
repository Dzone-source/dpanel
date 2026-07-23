<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="description" content="{$config['appName']} — nâng cấp tốc độ không giới hạn cho SIM SoftBank / LINEMO / Y!mobile và mạng di động Nhật Bản; ổn định, đa nền tảng."/>
    <link rel="icon" type="image/png" href="/favicon.ico"/>
    <title>{$config['appName']}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500&family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>

    <link rel="stylesheet" href="https://fastly.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://fastly.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.8.2/css/all.min.css"/>
    <link rel="stylesheet" href="https://fastly.jsdelivr.net/npm/swiper@4.5.0/dist/css/swiper.min.css"/>

    <link rel="stylesheet" href="/assets/landing/cool/css/style.css"/>
    <link rel="stylesheet" href="/assets/landing/cool/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="/assets/css/landing.css?v=20260723zalo"/>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top" id="navbar">
    <div class="container">
        <a class="navbar-brand logo" href="/">
            <h2 class="logo-dark"><i class="fa fa-globe"></i>&nbsp;{$config['appName']}</h2>
            <h2 class="logo-light" style="color:#dee2e6;font-weight:400;"><i class="fa fa-globe"></i>&nbsp;{$config['appName']}</h2>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse"
                aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="feather feather-menu">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav ml-auto ms-auto navbar-center" id="navbar-navlist">
                <li class="nav-item"><a href="/#home" class="nav-link active">Trang chủ</a></li>
                <li class="nav-item"><a href="/#services" class="nav-link">Dịch vụ</a></li>
                <li class="nav-item"><a href="/#features" class="nav-link">Tính năng</a></li>
                <li class="nav-item"><a href="/#pricing" class="nav-link">Giá</a></li>
            </ul>
            <a href="/auth/login" class="btn btn-sm rounded-pill nav-btn ml-lg-3 ms-lg-3">
                <i class="fas fa-sign-in-alt"></i> Đăng nhập
            </a>
        </div>
    </div>
</nav>

<section class="hero-1 bg-center bg-primary position-relative"
         style="background-image: url(/assets/landing/cool/img/hero-g-bg.png);" id="home">
    <div class="container">
        <div class="row align-items-center hero-content">
            <div class="col-lg-5">
                <h1 class="text-white display-4 font-weight-bold mb-4 hero-1-title">
                    Nâng cấp tốc độ không giới hạn — SoftBank / LINEMO / Y!mobile
                </h1>
                <p class="text-white-70 mb-4">
                    Khi SIM Nhật bị giới hạn tốc độ sau khi hết data, {$config['appName']} giúp bạn
                    lấy lại trải nghiệm cao tốc ổn định trên SoftBank, LINEMO, Y!mobile và các mạng tương tự.
                    Node tối ưu cho mạng di động Nhật, tương thích Hiddify / Clash / Sing-box;
                    dùng được trên điện thoại, máy tính và tablet — mọi lúc, mọi nơi.
                </p>
                <a class="btn btn-lg btn-light rounded-pill mb-2" href="/auth/login">
                    <strong>Bắt đầu sử dụng</strong> <i class="fa fa-plane" aria-hidden="true"></i>
                </a>
            </div>

            <div id="carouselExampleIndicators"
                 class="col-lg-6 col-sm-12 mx-auto ml-lg-auto mr-lg-0 ms-lg-auto me-lg-0 carousel slide"
                 data-ride="carousel">
                <div class="swiper-container">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <img src="/assets/landing/cool/img/banner-hbo-1.png" class="d-block w-100" alt="HBO"/>
                        </div>
                        <div class="swiper-slide">
                            <img src="/assets/landing/cool/img/banner-netflix-1.png" class="d-block w-100" alt="Netflix"/>
                        </div>
                        <div class="swiper-slide">
                            <img src="/assets/landing/cool/img/banner-hulu-3.png" class="d-block w-100" alt="Hulu"/>
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-bottom-shape">
        <img src="/assets/landing/cool/img/hero-1-bottom-shape.png" alt="" class="img-fluid d-block mx-auto"/>
    </div>
</section>

<section class="section" id="services">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-7 text-center">
                <h2 class="fw-bold">Dành cho SIM Nhật bị slow — lấy lại tốc độ cao</h2>
                <p class="text-muted">
                    Tập trung tối ưu SoftBank, LINEMO, Y!mobile và các gói data Nhật phổ biến.
                    Kết nối nhanh, ổn định trên 4G/5G; phù hợp xem video, làm việc và dùng app hàng ngày.
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="service-box text-center px-4 py-5 position-relative mb-4">
                    <div class="service-box-content p-4">
                        <div class="icon-mono service-icon avatar-md mx-auto mb-4">
                            <i data-feather="box" class="icon-dual-primary"></i>
                        </div>
                        <h4 class="mb-3 font-size-22">Bỏ giới hạn tốc độ</h4>
                        <p class="text-muted mb-0">
                            Hết data tốc độ cao? Tiếp tục lướt web, xem YouTube / TikTok mượt trên SoftBank, LINEMO, Y!mobile.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="service-box text-center px-4 py-5 position-relative mb-4 active">
                    <div class="service-box-content p-4">
                        <div class="icon-mono service-icon avatar-md mx-auto mb-4">
                            <i data-feather="layers" class="icon-dual-primary"></i>
                        </div>
                        <h4 class="mb-3 font-size-22 text-white-90">Đa nền tảng</h4>
                        <p class="text-white mb-0 text-white-90">
                            Hiddify, Clash, Sing-box trên iOS, Android, Windows, macOS — một tài khoản dùng nhiều thiết bị.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="service-box text-center px-4 py-5 position-relative mb-4">
                    <div class="service-box-content p-4">
                        <div class="icon-mono service-icon avatar-md mx-auto mb-4">
                            <i data-feather="server" class="icon-dual-primary"></i>
                        </div>
                        <h4 class="mb-3 font-size-22">Node Nhật &amp; VN</h4>
                        <p class="text-muted mb-0">
                            Tuyến tối ưu cho mạng di động Nhật; có thêm node Việt Nam khi cần truy cập nội địa ổn định.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section bg-light" id="features">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-md-5 order-2 order-md-1 mt-md-0 mt-5">
                <span class="badge badge-pill badge-primary mb-4">SOFTBANK · LINEMO · Y!MOBILE</span>
                <h2 class="mb-4">Nâng cấp tốc độ cao không giới hạn trên SIM Nhật</h2>
                <p class="text-muted mb-5">
                    Nhiều gói SoftBank / LINEMO / Y!mobile (và các MVNO tương tự) sẽ giảm tốc độ sau khi hết dung lượng.
                    {$config['appName']} giúp bạn kết nối lại với tốc độ cao ổn định trên 4G/5G — phù hợp điện thoại,
                    hotspot chia sẻ Wi‑Fi, máy tính bảng và laptop. Cấu hình đơn giản qua subscription Hiddify / Clash.
                </p>
                <a href="/auth/register" class="btn btn-primary">
                    Tìm hiểu thêm
                    <i data-feather="arrow-right" class="icon-xs ml-1 ms-2"></i>
                </a>
            </div>
            <div class="col-md-6 ml-md-auto ms-md-auto order-1 order-md-2">
                <div class="position-relative">
                    <div class="ml-5 ms-5 features-img">
                        <img src="/assets/landing/cool/img/top-hbo-movies.jpg" alt="" class="img-fluid d-block mx-auto"/>
                    </div>
                    <img src="/assets/landing/cool/img/dot-img.png" alt="" class="dot-img-left"/>
                </div>
            </div>
        </div>

        <div class="row align-items-center section pb-0">
            <div class="col-md-6">
                <div class="position-relative mb-md-0 mb-5">
                    <div class="mr-5 me-5 features-img">
                        <img src="/assets/landing/cool/img/halloween-movies.jpg" alt=""
                             class="img-fluid d-block mx-auto rounded shadow"/>
                    </div>
                    <img src="/assets/landing/cool/img/dot-img.png" alt="" class="dot-img-right"/>
                </div>
            </div>
            <div class="col-md-5 ml-md-auto ms-md-auto">
                <span class="badge badge-pill badge-primary mb-4">XEM PHIM · LÀM VIỆC · MẠNG XÃ HỘI</span>
                <h2 class="mb-4">Xem phim, gọi video và làm việc không còn “giật từng KB”</h2>
                <p class="text-muted mb-5">
                    Duy trì tốc độ đủ để YouTube / Netflix / TikTok mượt, Zoom / Meet ổn định, và dùng app hàng ngày
                    ngay cả khi SIM đã vào chế độ tốc độ thấp. Mã hóa truyền tải, tương thích đa client —
                    tập trung vào trải nghiệm thực tế trên mạng SoftBank, LINEMO, Y!mobile.
                </p>
                <a href="/auth/register" class="btn btn-primary">
                    Tìm hiểu thêm
                    <i data-feather="arrow-right" class="icon-xs ml-1 ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="section bg-gradient-primary">
    <div class="bg-overlay-img" style="background-image: url(/assets/landing/cool/img/demos.png);"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center">
                    <h1 class="text-white mb-4">Hỗ trợ cấu hình SoftBank / LINEMO / Y!mobile</h1>
                    <p class="text-white mb-5 font-size-16">
                        Chưa biết chọn node hay app nào? Đăng ký và liên hệ hỗ trợ — chúng tôi hướng dẫn cấu hình
                        Hiddify / Clash trên iOS &amp; Android, tối ưu cho SIM Nhật trong suốt chu kỳ gói của bạn.
                    </p>
                    <a href="/auth/register" class="btn btn-lg btn-light fw-bold">Liên hệ chúng tôi</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="pricing">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-7 text-center">
                <h2 class="fw-bold">Gói nâng cấp tốc độ — rõ ràng, dễ dùng</h2>
                <p class="text-muted">
                    Chọn gói phù hợp để duy trì tốc độ cao trên SoftBank / LINEMO / Y!mobile.
                    Đăng ký nhanh, import subscription vào app — dùng ngay không cần chờ.
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center mb-4 pricing-tab">
                    <ul class="nav nav-pills rounded-pill justify-content-center d-inline-block shadow-sm" role="tablist">
                        <li class="nav-item d-inline-block">
                            <a class="nav-link rounded-pill active" href="#pricing">Gói đang bán</a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        {if $has_pricing}
                            <div class="row mt-5">
                                {foreach from=$pricing_products item=product}
                                    <div class="col-12 col-md-4 col-lg-4">
                                        <div class="pricing shadow rounded-lg">
                                            <div class="pricing-title">{$product.name|escape:'html'}</div>
                                            <div class="pricing-padding">
                                                <div class="pricing-price">
                                                    <div>{$product.price|format_vnd:0} VNĐ</div>
                                                    <div>theo gói</div>
                                                </div>
                                                <div class="pricing-details">
                                                    {foreach from=$product.features item=feature}
                                                        <div class="pricing-item">
                                                            <div style="width:25px;height:25px;line-height:20px;color:#3c2299;">
                                                                <i class="fas fa-check-circle"></i>
                                                            </div>
                                                            <div class="pricing-item-label">{$feature|escape:'html'}</div>
                                                        </div>
                                                    {/foreach}
                                                </div>
                                            </div>
                                            <div class="pricing-cta go-to-buy-page">
                                                <a style="border-radius:0 0 14px 14px;background:#3c2299;color:#fff;"
                                                   href="/auth/register">Đăng ký</a>
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        {else}
                            <div class="text-center text-muted mt-4">
                                Hiện chưa có gói đang bán. Vui lòng quay lại sau.
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="bg-overlay-img" style="background-image: url(/assets/landing/cool/img/footer-bg.png);"></div>
            <div class="col-lg-5">
                <div class="mb-4">
                    <a href="/"><h2 class="logo-dark"><i class="fa fa-globe"></i>&nbsp;{$config['appName']}</h2></a>
                    <p class="text-white-50 mt-4 mb-1" style="font-size:1rem;line-height:1.6rem;">
                        Chuyên tối ưu tốc độ cao không giới hạn cho SIM SoftBank, LINEMO, Y!mobile và mạng di động Nhật —
                        ổn định, dễ cấu hình, dùng được trên mọi thiết bị.
                    </p>
                    <p style="font-size:0.8rem;line-height:1.2rem;">
                        Built for SoftBank / LINEMO / Y!mobile users who need stable high-speed after data throttle.
                        Works with Hiddify, Clash and major platforms.
                    </p>
                    <p class="text-white-50 mb-0" style="font-size:0.8rem!important;">
                        © {$landing_year} {$config['appName']}
                        · <a href="/tos" class="footer-link">Điều khoản dịch vụ</a>
                    </p>
                </div>
            </div>

            <div class="col-lg-7 d-none d-sm-none d-md-none d-lg-block">
                <div class="row">
                    <div class="col-lg-3 col-6"></div>
                    <div class="col-lg-3 col-6"></div>
                    <div class="col-lg-3 col-6">
                        <div class="mt-4 mt-lg-0">
                            <h4 class="text-white font-size-18 mb-3 text-end text-right">Trang chủ</h4>
                            <ul class="list-unstyled footer-sub-menu">
                                <li class="text-end text-right"><a href="/#pricing" class="footer-link">Cửa hàng / Giá</a></li>
                                <li class="text-end text-right"><a href="/auth/register" class="footer-link">Đăng ký</a></li>
                                <li class="text-end text-right"><a href="/auth/login" class="footer-link">Đăng nhập</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="mt-4 mt-lg-0">
                            <h4 class="text-white font-size-18 mb-3 text-end text-right">Hỗ trợ</h4>
                            <ul class="list-unstyled footer-sub-menu">
                                <li class="text-end text-right"><a href="/auth/register" class="footer-link">Liên hệ</a></li>
                                <li class="text-end text-right"><a href="/tos" class="footer-link">Điều khoản</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<a href="https://zalo.me/0796969444" target="_blank" rel="noopener noreferrer"
   class="landing-zalo-fab" aria-label="Chat Zalo 0796969444">
    <span class="landing-zalo-fab-icon" aria-hidden="true">
        <svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="22" height="22">
            <path fill="currentColor" d="M12.49 10.2722v-.4496h1.3467v6.3218h-.7704a.576.576 0 01-.5763-.5729l-.0006.0005a3.273 3.273 0 01-1.9372.6321c-1.8138 0-3.2844-1.4697-3.2844-3.2823 0-1.8125 1.4706-3.2822 3.2844-3.2822a3.273 3.273 0 011.9372.6321l.0006.0005zM6.9188 7.7896v.205c0 .3823-.051.6944-.2995 1.0605l-.03.0343c-.0542.0615-.1815.206-.2421.2843L2.024 14.8h4.8948v.7682a.5764.5764 0 01-.5767.5761H0v-.3622c0-.4436.1102-.6414.2495-.8476L4.8582 9.23H.1922V7.7896h6.7266zm8.5513 8.3548a.4805.4805 0 01-.4803-.4798v-7.875h1.4416v8.3548H15.47zM20.6934 9.6C22.52 9.6 24 11.0807 24 12.9044c0 1.8252-1.4801 3.306-3.3066 3.306-1.8264 0-3.3066-1.4808-3.3066-3.306 0-1.8237 1.4802-3.3044 3.3066-3.3044zm-10.1412 5.253c1.0675 0 1.9324-.8645 1.9324-1.9312 0-1.065-.865-1.9295-1.9324-1.9295s-1.9324.8644-1.9324 1.9295c0 1.0667.865 1.9312 1.9324 1.9312zm10.1412-.0033c1.0737 0 1.945-.8707 1.945-1.9453 0-1.073-.8713-1.9436-1.945-1.9436-1.0753 0-1.945.8706-1.945 1.9436 0 1.0746.8697 1.9453 1.945 1.9453z"/>
        </svg>
    </span>
    <span class="landing-zalo-fab-label">Zalo</span>
</a>

<script src="https://fastly.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://fastly.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://fastly.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
<script src="/assets/landing/cool/js/smooth-scroll.polyfills.min.js"></script>
<script src="/assets/landing/cool/js/feather-icons"></script>
<script src="/assets/landing/cool/js/app.js"></script>
<script src="https://fastly.jsdelivr.net/npm/swiper@4.5.0/dist/js/swiper.min.js"></script>
<script>
{literal}
var mySwiper = new Swiper('.swiper-container', {
    direction: 'horizontal',
    loop: true,
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
    autoplay: {
        delay: 5000,
        disableOnInteraction: true,
    },
});
{/literal}
</script>
</body>
</html>
