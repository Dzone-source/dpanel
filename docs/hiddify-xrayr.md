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

## Hiddify

Import lại:

- Desktop: `{sub}/singbox`
- Android: `{sub}/clash`

Trong Clash phải thấy `type: trojan`, `password` = UUID, `sni` đúng domain cert.

## Kiểm tra nhanh

```bash
# Panel subscription
curl -sL "https://PANEL/sub/TOKEN/clash" | grep -A20 "type: trojan"

# Node log — không còn "not a valid user"
journalctl -u XrayR -n 100 --no-pager
```
