<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="description" content="{$config['appName']} — dịch vụ mạng trung kế toàn cầu, ổn định, đa nền tảng."/>
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
    <link rel="stylesheet" href="/assets/css/landing.css?v=20260723font"/>
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
                    Dịch vụ mạng trung kế toàn cầu, dùng mọi lúc mọi nơi
                </h1>
                <p class="text-white-70 mb-4">
                    Hệ thống phân luồng thông minh, trang nội địa đi thẳng để trải nghiệm mượt hơn;
                    tăng tốc dịch vụ Apple; tăng tốc các trang quốc tế phổ biến
                    (Google / YouTube / Twitter / Instagram / GitHub…);
                    mã hóa mạnh trong quá trình truyền tải để bảo vệ dữ liệu và quyền riêng tư;
                    tương thích nhiều ứng dụng trên mọi nền tảng.
                </p>
                <a class="btn btn-lg btn-light rounded-pill mb-2" href="/auth/register">
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
                <h2 class="fw-bold">Công cụ cho xem phim quốc tế &amp; công việc xuyên biên giới</h2>
                <p class="text-muted">
                    Thiết kế cho nhu cầu ra nước ngoài số — mọi lúc, mọi nơi, tốc độ cao trên mọi nền tảng.
                    Hạ tầng ổn định, nhiều tính năng tiện lợi.
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
                        <h4 class="mb-3 font-size-22">Tốc độ ổn định</h4>
                        <p class="text-muted mb-0">Trải nghiệm như đang ở nước ngoài, phù hợp Wi‑Fi và mạng di động.</p>
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
                        <p class="text-white mb-0 text-white-90">Hỗ trợ macOS, iOS, Android, Windows.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="service-box text-center px-4 py-5 position-relative mb-4">
                    <div class="service-box-content p-4">
                        <div class="icon-mono service-icon avatar-md mx-auto mb-4">
                            <i data-feather="server" class="icon-dual-primary"></i>
                        </div>
                        <h4 class="mb-3 font-size-22">Kết nối toàn cầu</h4>
                        <p class="text-muted mb-0">Kết nối đến nhà cung cấp nội dung toàn cầu qua IXP — nhanh hơn.</p>
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
                <span class="badge badge-pill badge-primary mb-4">CROSS DEVICES &amp; PLATFORMS</span>
                <h2 class="mb-4">Dùng trên thiết bị yêu thích — điện thoại hay máy tính, mọi lúc mọi nơi.</h2>
                <p class="text-muted mb-5">
                    {$config['appName']} hỗ trợ macOS, iOS, Android, Windows và Linux.
                    Qua ứng dụng bên thứ ba, dùng được trên điện thoại, máy tính, router, máy chơi game và TV box.
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
                <span class="badge badge-pill badge-primary mb-4">UNBLOCK STREAMING MEDIA</span>
                <h2 class="mb-4">Mở khóa streaming — xem và nghe nội dung chất lượng cao</h2>
                <p class="text-muted mb-5">
                    Qua dịch vụ của {$config['appName']}, bạn có thể xem Netflix, Hulu, HBO, TVB và nhiều nền tảng khác,
                    cũng như nghe nhạc trên Spotify, Pandora và các dịch vụ phổ biến.
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
                    <h1 class="text-white mb-4">Hỗ trợ khách hàng</h1>
                    <p class="text-white mb-5 font-size-16">
                        Có thắc mắc về gói thành viên? Đội ngũ tư vấn sẵn sàng giải đáp.
                        Chúng tôi hỗ trợ kỹ thuật trong suốt chu kỳ đăng ký của bạn.
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
                <h2 class="fw-bold">Trải nghiệm tốt — mức giá bất ngờ</h2>
                <p class="text-muted">
                    Đừng lãng phí thời gian chờ đợi. Bật dịch vụ mạng trung kế toàn cầu ngay,
                    truy cập internet toàn cầu mọi lúc mọi nơi.
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
                        Cam kết mang đến dịch vụ mạng trung kế tốc độ cao, ổn định, giá hợp lý —
                        trải nghiệm như đang ở nước ngoài trên mọi thiết bị và mọi mạng.
                    </p>
                    <p style="font-size:0.8rem;line-height:1.2rem;">
                        We dedicate to providing the finest network proxy service.
                        Easy to use on any device and any network.
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
