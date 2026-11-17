# Profile thiết bị giữa DPanel và XrayR

Web quyết định quyền; node thực thi và truyền dữ liệu. App Clash, sing-box, v2rayNG… dùng profile bình thường, không cần sửa app.

- Mỗi profile có token subscription và UUID/mật khẩu riêng, gắn về một account.
- Người dùng vào **Thiết bị** (`/user/devices`), bật chế độ riêng cho account, đặt tên và tạo profile cho từng máy.
- Thu hồi một profile làm mất quyền khóa đó trên mọi node sau khi đồng bộ, kể cả các luồng đang mở. Khóa account hoặc hết hạn gói loại toàn bộ credential của account khỏi danh sách được cấp.
- Admin có thể thu hồi từng profile trong trang sửa user và chỉnh `device_limit`. Mặc định 3 profile đang được cấp; 0 là không giới hạn. Đây là giới hạn profile đăng ký, không phải số IP hoặc phát hiện phần cứng. Hạ giới hạn không tự thu hồi profile đang có.
- Account chưa bật tiếp tục dùng UUID/subscription chung như trước. Khi bật, khóa chung không còn được cấp và các máy cần nhập profile mới. Không tự bật cho toàn bộ user.

## Giới hạn định danh

Profile bị sao chép sang hai máy vẫn cùng một định danh. IPv4/IPv6, chuyển Wi-Fi/4G hoặc nhiều thiết bị chung NAT không được dùng để xác định thiết bị. Muốn chống sao chép/hardware binding cần app có đăng ký khóa thiết bị riêng. Bản này không tuyên bố có tính năng đó.

Online là có luồng còn mở tại node và nhận báo cáo trong 180 giây gần đây. Một profile có thể có nhiều IP, nhiều luồng hoặc dùng nhiều node nhưng web chỉ đếm một profile. Tải subscription không làm profile được tính online.

## Triển khai phối hợp

Hai repository dùng cùng nhánh `feature/device-profiles`:

- Web: https://github.com/Dzone-source/dpanel/tree/feature/device-profiles (tách từ `cursor/gopass-reskin-e6df`).
- Node: https://github.com/Dzone-source/xrayr/tree/feature/device-profiles (tách từ bản Debian/DPanel đã chạy).

1. Backup database và cấu hình web/node. Kiểm tra `git status` để giữ các thay đổi cục bộ.
2. Triển khai mã web cùng image PHP/cron mới trong cửa sổ bảo trì. Chạy migration `2026111700-add_device_profiles.php` trước khi mở lưu lượng web/API. Với Docker, build lại image và chạy `docker compose run --rm --no-deps php php xcat Migration latest` trên image mới, rồi khởi động lại các dịch vụ. Giữ `.env`, config, database và Redis hiện tại; không chạy cài mới database.
3. Nâng cấp các node lên `0.9.5-debian-devices.1`. Bootstrap riêng cho nhánh thử nghiệm:

   ```sh
   curl -fsSL https://raw.githubusercontent.com/Dzone-source/xrayr/feature/device-profiles/deploy/debian/oneclick.sh -o xrayr-oneclick.sh && sudo bash xrayr-oneclick.sh --upgrade
   ```

   Node vẫn dùng cấu hình hiện tại, vẫn có backup/rollback binary. Bộ cài vẫn cập nhật kernel Debian như bản trước; không tự reboot. Xác minh phiên bản và service trước khi tiếp tục.
4. Nên đặt `UpdatePeriodic: 30` trên node (và restart trong lúc ít người dùng). Bật chế độ thiết bị cho **một account thử**. Tạo hai profile, nhập trên hai app, kiểm tra online và thu hồi một profile; profile còn lại phải tiếp tục hoạt động.
5. Sau khi kiểm thử trên hạ tầng thật, mới chuyển thêm account. Không xóa profile chung/UUID cũ trong DB; chúng không còn được cấp khi device mode bật.

Các node cũ không nhận credential thiết bị; chúng chỉ tiếp tục nhận account legacy. Backend hỗ trợ trong bản này là XrayR cho VMess/VLESS/Trojan (node sort 11/12/14); subscription thiết bị cũng chỉ hiển thị các node này. Không bật cho account cần giao thức/backend chưa được nâng cấp.

Rollback về node cũ khi account đã bật device mode sẽ làm account đó không có quyền trên node cũ. Giữ node bản mới hoặc xử lý chuyển đổi account có chủ đích; không tự trả lại UUID chung khi chỉ thu hồi một profile. Rollback migration xóa profile/token/presence; phải backup database trước, và không chạy rollback trong khi node còn phụ thuộc credential đó.

## Đồng bộ và khi mất liên lạc

Node gửi `device_profiles=1` khi lấy user; DPanel trả `id` account thật + `device_id` và UUID riêng. Không đổi account ID thành device ID, nên lưu lượng các profile vẫn cộng về account. Báo cáo device-tagged có owner không khớp bị từ chối. Quota và chính sách tốc độ hiện hữu của panel vẫn áp dụng; khi bật speed cap ở backend, bucket là theo credential/profile, chưa phải bucket tốc độ gộp toàn account.

Báo cáo `/mod_mu/users/aliveip` của node mới là snapshot theo node, chứa `device_id`, `user_id`, IP và số luồng; snapshot rỗng xóa hiện diện cũ của node. Các node/account legacy vẫn dùng luồng API cũ. IP/node của cùng profile được lưu riêng, không ghi đè node khác.

Thu hồi phụ thuộc lần pull danh sách user kế tiếp, không tức thời qua push. Node giữ quyền có thời hạn cho profile thiết bị: `max(120, 3 * UpdatePeriodic)` giây tính từ lần đồng bộ thành công, kể cả HTTP 304. Khi hết hạn mà chưa đồng bộ lại, node đóng các luồng trong khoảng 5 giây tiếp theo và từ chối luồng mới. Sau khi web trở lại và node nhận lại quyền, app có thể kết nối lại. Account legacy không áp dụng lease timeout này. Không gọi API cho từng gói dữ liệu.

## Kiểm tra đã thực hiện

- Test PHP với MariaDB 11.8: tạo/thu hồi, phân quyền và CSRF, UUID subscription riêng, tổng traffic về account, snapshot IP, template escaping/compile, migration lên/xuống và hai request tạo profile cạnh tranh cùng quota.
- Test Go có race detector: mapping device/owner, registry và quyền hết hạn, thu hồi độc lập các profile chung IP, chuyển legacy sang device mode.
- Integration qua binary XrayR + client VLESS thật trên loopback: hai credential cùng account/IP, cắt phiên đang chạy của profile bị thu hồi, profile khác vẫn truyền dữ liệu, sau đó khóa toàn account cắt phiên còn lại.
- Chưa triển khai thay đổi lên web hoặc node production; cần bước thử một account trên hạ tầng thật như trên.
