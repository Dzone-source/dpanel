{include file='user/header.tpl'}
<div class="page-wrapper"><div class="page-body"><div class="container-xl">
<h2>Thiết bị và profile kết nối</h2>
<p>Mỗi thiết bị dùng một profile riêng. Hai thiết bị dùng chung Wi-Fi vẫn được tính là hai profile. Không chia sẻ liên kết dưới đây.</p>
{if $notice}<div class="alert alert-info">{$notice|escape}</div>{/if}
{if !$device_mode}
<div class="card"><div class="card-body">
<p>Bật chế độ này sẽ ngừng cấp quyền cho UUID/profile chung của tài khoản sau khi node đồng bộ. Các máy đang dùng profile cũ cần nhập profile riêng mới. Nâng cấp tất cả node trước khi bật.</p>
<form method="post" action="/user/devices">
<input type="hidden" name="csrf" value="{$csrf|escape}"><input type="hidden" name="action" value="enable">
<button class="btn btn-primary" type="submit">Bật profile riêng cho từng thiết bị</button>
</form></div></div>
{else}
<p>Giới hạn profile được cấp: {if $device_limit > 0}{$device_limit}{else}Không giới hạn{/if}. Trạng thái online dựa trên phiên kết nối mà node báo cáo trong 180 giây gần đây.</p>
<form method="post" action="/user/devices" class="card card-body mb-3">
<input type="hidden" name="csrf" value="{$csrf|escape}"><input type="hidden" name="action" value="create">
<label for="device-name">Tên thiết bị</label><input id="device-name" class="form-control mb-2" name="name" maxlength="100" required placeholder="Ví dụ: Điện thoại của tôi">
<button class="btn btn-primary" type="submit">Tạo profile</button>
</form>
{/if}
<div class="table-responsive"><table class="table"><thead><tr><th>Thiết bị</th><th>Trạng thái</th><th>Ghi nhận gần nhất</th><th>Profile Clash</th><th></th></tr></thead><tbody>
{foreach $devices as $device}
<tr><td>{$device.name|escape}</td><td>{if $device.revoked}Đã thu hồi{elseif $device.online}Online{else}Chưa ghi nhận online{/if}</td><td>{$device.last_seen|escape}{if $device.online}<br><small>IP: {$device.ips|escape} · {$device.connections} luồng</small>{/if}</td>
<td>{if !$device.revoked}<input class="form-control" aria-label="Liên kết profile" value="{$device.url|escape}" readonly><small>Đổi đuôi /clash thành /singbox hoặc /general nếu app yêu cầu.</small>{/if}</td>
<td>{if !$device.revoked}<form method="post" action="/user/devices"><input type="hidden" name="csrf" value="{$csrf|escape}"><input type="hidden" name="action" value="revoke"><input type="hidden" name="device_id" value="{$device.id}"><button class="btn btn-outline-danger" type="submit">Thu hồi</button></form>{/if}</td></tr>
{foreachelse}<tr><td colspan="5">Chưa có profile thiết bị.</td></tr>{/foreach}
</tbody></table></div>
<p class="text-muted">Một profile bị sao chép vẫn là cùng một định danh. Thu hồi profile để khóa khóa kết nối đó trên các node; việc này cần node nhận bản cập nhật quyền.</p>
</div></div></div>
{include file='user/footer.tpl'}
