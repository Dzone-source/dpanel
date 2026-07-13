# DPanel

Hệ thống quản lý dịch vụ proxy đa giao thức (Shadowsocks 2022, V2Ray, Trojan, TUIC), được đóng gói sẵn với Docker để triển khai nhanh trên VPS Ubuntu.

Fork từ [SSPanel-UIM](https://github.com/Anankke/SSPanel-UIM), tùy biến và bảo trì bởi [dzone-source](https://github.com/dzone-source).

## Yêu cầu

- Ubuntu 20.04+ (hoặc bất kỳ Linux nào hỗ trợ Docker)
- Docker Engine 24+
- Docker Compose v2
- Tối thiểu 1 CPU, 1GB RAM, 10GB disk

## Cài đặt nhanh (Docker)

```bash
# 1. Clone repo
git clone https://github.com/dzone-source/dpanel.git
cd dpanel

# 2. Chạy script cài đặt (tự tạo .env, config, database, admin)
chmod +x install.sh
./install.sh
```

Script sẽ hỏi:

- URL panel (bắt buộc `https://...`)
- Mật khẩu MariaDB
- Email và mật khẩu admin

Sau khi xong, truy cập URL đã cấu hình để đăng nhập.

## Cài đặt thủ công

```bash
cp .env.example .env
# Chỉnh sửa .env theo nhu cầu

cp config/.config.example.php config/.config.php
cp config/appprofile.example.php config/appprofile.php
php docker/setup-config.php

docker compose up -d --build

docker compose exec php php xcat Migration new
docker compose exec php php xcat Migration latest
docker compose exec php php xcat Tool importSetting
docker compose exec php php xcat Tool createAdmin admin@example.com your_password
```

## Cấu trúc Docker

| Service | Mô tả |
|---------|--------|
| `nginx` | Web server, cổng `HTTP_PORT` (mặc định 80) |
| `php` | PHP 8.3-FPM chạy ứng dụng |
| `cron` | Chạy `php xcat Cron` mỗi 5 phút |
| `mariadb` | MariaDB 11.8 |
| `redis` | Redis 7 |

## Lệnh hữu ích

```bash
# Xem trạng thái
docker compose ps

# Xem log
docker compose logs -f

# Vào container PHP
docker compose exec php sh

# Cập nhật sau khi git pull
docker compose exec php composer install --no-dev
docker compose exec php php xcat Update
docker compose exec php php xcat Tool importSetting
docker compose exec php php xcat Migration latest
docker compose up -d --build
```

## HTTPS

Container Nginx chỉ lắng nghe HTTP (cổng 80). Đặt reverse proxy phía trước để bật HTTPS:

- **Caddy** — tự động Let's Encrypt
- **Traefik** — phù hợp khi chạy nhiều service
- **Nginx trên host** + Certbot

`APP_URL` trong `.env` phải dùng `https://` để panel hoạt động đúng.

## Cấu hình

| File | Mục đích |
|------|----------|
| `.env` | Biến môi trường Docker (DB, Redis, admin, URL) |
| `config/.config.php` | Cấu hình ứng dụng (tự sinh từ `.env`) |
| `config/appprofile.php` | Profile client (Clash, Sing-box, ...) |

## Giấy phép

MIT — dựa trên SSPanel-UIM. Xem [LICENSE](LICENSE).
