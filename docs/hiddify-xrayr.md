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

### `custom_config` mẫu (Trojan TCP + TLS)

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
Nodes:
  - PanelType: "SSpanel"
    ApiConfig:
      ApiHost: "https://YOUR_PANEL"
      ApiKey: "SAME_AS_muKey"
      NodeID: 1
      NodeType: Trojan
      Timeout: 30
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

## Hiddify import (profile riêng)

| Client | URL |
|--------|-----|
| **Hiddify-app** | `{subUrl}/sub/{token}/hiddify` + deep link `hiddify://import/.../hiddify#name` |
| Sing-box SFA/SFM | `{subUrl}/sub/{token}/singbox` |
| Clash Meta | `{subUrl}/sub/{token}/clash` |

`/hiddify` trả về **Sing-box JSON tối giản** (giống HiddifyPanel `full_singbox` cho UA `HiddifyNext|Dart|SFI|SFA`): `Content-Type: application/json`, header `profile-title: base64:…`, `profile-update-interval: 1`, `Subscription-Userinfo`. Outbound Trojan dùng `password = UUID`.

User-Agent chứa `Hiddify` trên `/json` hoặc `/sub/{token}` (không subtype) cũng được remap sang profile này.

## Kiểm tra nhanh

```bash
# Panel subscription
curl -sL "https://PANEL/sub/TOKEN/clash" | grep -A20 "type: trojan"

# Node log — không còn "not a valid user"
journalctl -u XrayR -n 100 --no-pager
```
