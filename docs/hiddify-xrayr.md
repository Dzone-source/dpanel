# Hiddify + Trojan (DPanel / XrayR)

Node đang chạy **Trojan** (sort=`14`), không phải VLESS. Hiddify lỗi thường do:

1. Panel cứng kick user khi `alive_ip >= node_iplimit` → XrayR log `not a valid user` và drop Trojan session  
2. Subscription thiếu SNI / ws-opts / fingerprint  
3. XrayR `NodeType` hoặc TLS cert sai

## Panel

- Node **sort = 14** (Trojan)
- `muKey` = XrayR `ApiKey`
- `webAPIUrl` / `subUrl` = HTTPS public URL
- `checkNodeIp = false` nếu node NAT/CDN (đã default trong bản fix)

### `custom_config` mẫu (Trojan TCP + TLS) — khớp HiddifyPanel

```json
{
  "offset_port_node": 443,
  "host": "node.example.com",
  "network": "tcp",
  "security": "tls",
  "allow_insecure": false,
  "udp": true,
  "fingerprint": "chrome"
}
```

> **Note:** omit `alpn` for plain Trojan TCP — DPanel no longer defaults `alpn=http/1.1` (Clash Meta also omits it). Only set `alpn` when the node/CDN actually requires it (or for grpc/h2).

Share link (trojan://) chỉ còn dùng cho `/trojan` / `/v2ray`. **`/hiddify` = Clash Meta YAML** giống `/clash` (ClashMi đã ổn định trên cùng node).

### Trojan + WebSocket

```json
{
  "offset_port_node": 443,
  "host": "node.example.com",
  "network": "ws",
  "path": "/trojan",
  "security": "tls",
  "allow_insecure": false,
  "fingerprint": "chrome"
}
```

Password client = **user UUID** (giống XrayR).

## XrayR `config.yml`

```yaml
ConnectionConfig:
  Handshake: 8
  ConnIdle: 600
  UplinkOnly: 3600   # upload speed-test: tránh cắt nửa chừng
  DownlinkOnly: 3600
  BufferSize: 1024
Nodes:
  - PanelType: "SSpanel"
    ApiConfig:
      ApiHost: "https://YOUR_PANEL"
      ApiKey: "SAME_AS_muKey"
      NodeID: 1
      NodeType: Trojan
      Timeout: 30
      SpeedLimit: 0
      DeviceLimit: 0
      DisableCustomConfig: false
    ControllerConfig:
      ListenIP: 0.0.0.0
      UpdatePeriodic: 60
      DisableLocalREALITYConfig: true
      EnableREALITY: false
      CertConfig:
        CertMode: file   # Trojan CẦN TLS — không dùng none
        CertDomain: "node.example.com"
        CertFile: /etc/XrayR/cert/node.example.com.crt
        KeyFile: /etc/XrayR/cert/node.example.com.key
```

Hoặc `CertMode: http` / `dns` nếu muốn ACME tự cấp.

### Upload speed-test bị rớt

Hay gặp **giới hạn tốc độ**, không phải hết data:

1. XrayR rate-limit dùng chung 1 bucket up+down → download speedtest hút token → upload bị treo (giống disconnect). Fix: v0.9.15 tách bucket + `DisableSpeedLimit: true`.
2. Panel `node_speedlimit` / `keep_connect_speedlimit: 5` quá thấp. Mặc định mới: `disable_xrayr_speed_limit=true` (không gửi Mbps cho XrayR).
3. Sync `user deleted` / `UplinkOnly` thấp — cần binary branch ổn định + `UplinkOnly/DownlinkOnly: 3600`.

Kiểm tra API user list: mọi `node_speedlimit` phải là `0` khi `disable_xrayr_speed_limit=true`.

## Hiddify import (profile riêng)

**Khuyến nghị: ClashMi** → `{subUrl}/sub/{token}/clash`

| Client | URL |
|--------|-----|
| **ClashMi (khuyến nghị)** | `{subUrl}/sub/{token}/clash` |
| Hiddify (dự phòng) | `{subUrl}/sub/{token}/hiddify` — base64 share links |
| Sing-box SFA/SFM | `{subUrl}/sub/{token}/singbox` |

GoPass UI ưu tiên ClashMi ở “Cấu hình nhanh”. Hiddify nằm trong app khác — upload trên Hiddify có thể kém hơn ClashMi cùng node Trojan/XrayR.

## Kiểm tra nhanh

```bash
# /hiddify phải là base64 (không bắt đầu bằng ---)
curl -sL "https://PANEL/sub/TOKEN/hiddify" | head -c 80; echo
# decode thử:
curl -sL "https://PANEL/sub/TOKEN/hiddify" | base64 -d | head -n 5

curl -sL "https://PANEL/sub/TOKEN/clash" | grep -A15 "type: trojan"
```
