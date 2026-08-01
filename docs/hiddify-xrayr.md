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

Share link Hiddify sẽ có dạng:
`trojan://UUID@server:443?hiddify=1&sni=…&type=tcp&fp=chrome&headerType=none&security=tls&host=…#Name`

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

| Client | URL |
|--------|-----|
| **Hiddify-app** | `{subUrl}/sub/{token}/hiddify` + deep link `hiddify://import/.../hiddify#name` |
| Sing-box SFA/SFM | `{subUrl}/sub/{token}/singbox` |
| Clash Meta | `{subUrl}/sub/{token}/clash` |

`/hiddify` trả về **base64 allshare** (`trojan://` / `vless://` / `ss://`) — định dạng subscription Hiddify import ổn định nhất (wiki URL Scheme). Headers: `profile-title: base64:…`, `profile-update-interval: 6`, `Subscription-Userinfo`.

Deep link dùng query form (Hiddify LinkParser decode đúng):
`hiddify://import/?url=<urlencoded sub>/hiddify&name=<name>`

**Không** percent-encode path-style `hiddify://import/https://...` — app không decode path → lỗi "Unexpected connection error".

User-Agent chứa `Hiddify` trên `/json` hoặc `/sub/{token}` (không subtype) cũng được remap sang profile này.

### Hiddify vs Clash Meta (cùng node Trojan)

| | `/clash` (ClashMi) | `/hiddify` (Hiddify-app) |
|--|--|--|
| Body | Clash Meta YAML | base64 `trojan://` share links |
| `alpn` | omitted (TCP) | omitted unless custom_config / grpc |
| `udp` | `udp: true` | (sing-box default) |
| `client-fingerprint` | yes | `fp=chrome` |
| `tcp-concurrent` | yes (Clash_Config) | N/A (sing-box) |
| mux / fragment | no | **not** enabled by `hiddify=1` (app defaults off) |

Nếu Hiddify vẫn drop upload: A/B import `{sub}/clash` trong Hiddify-app (app hỗ trợ Clash YAML).

## Kiểm tra nhanh

```bash
# Panel subscription
curl -sL "https://PANEL/sub/TOKEN/clash" | grep -A20 "type: trojan"

# Node log — không còn "not a valid user"
journalctl -u XrayR -n 100 --no-pager
```
