# Hiddify + XrayR (DPanel)

Stable setup for Hiddify clients against DPanel (SSPanel-UIM WebAPI) + XrayR, aligned with Xboard / Xboard-Node protocol expectations (VLESS + REALITY / TLS).

## Why Hiddify failed before

Hiddify imports Clash Meta (`/clash`) or Sing-box (`/singbox`). DPanel used to emit **VMess** for all sort=`11` nodes even when the node ran **VLESS + REALITY** on XrayR. Clients then dialed the wrong protocol and the handshake failed.

Subscriptions now emit `vless` + `reality-opts` / Sing-box `tls.reality` when `custom_config` enables them.

## Panel (`muKey` / Host)

1. `config/.config.php`:
   - `webAPI = true`
   - `webAPIUrl` = public HTTPS URL (must match Host header XrayR uses)
   - `muKey` = long random secret (same as XrayR `ApiKey`)
   - `checkNodeIp = false` if the node is behind CDN/NAT (otherwise keep `true` with correct node IP)
   - `subUrl` = same public HTTPS base as users open for subscriptions
2. Node sort: `11` for VMess/VLESS, `14` for Trojan.

## Node `custom_config` (VLESS + REALITY)

Paste into Admin → Node → custom_config (JSON). Generate keys with `XrayR x25519`:

```json
{
  "offset_port_node": 443,
  "host": "www.microsoft.com",
  "network": "tcp",
  "security": "reality",
  "enable_vless": "1",
  "flow": "xtls-rprx-vision",
  "enable_reality": true,
  "fingerprint": "chrome",
  "reality-opts": {
    "dest": "www.microsoft.com:443",
    "server_names": ["www.microsoft.com"],
    "private_key": "SERVER_PRIVATE_KEY",
    "public_key": "SERVER_PUBLIC_KEY",
    "short_ids": ["0123456789abcdef"]
  }
}
```

Notes:

- `public_key` is required for **subscriptions** (Hiddify / Clash Meta). XrayR only needs `private_key` on the server.
- Keep `flow` as `xtls-rprx-vision` for TCP REALITY (Hiddify / Clash Meta / Sing-box).
- Prefer a real CDN/site as `dest` / SNI that supports TLS1.3 + H2.

## XrayR `config.yml` (pair with DPanel)

```yaml
Nodes:
  - PanelType: "SSpanel"
    ApiConfig:
      ApiHost: "https://YOUR_PANEL"
      ApiKey: "SAME_AS_muKey"
      NodeID: 1
      NodeType: V2ray
      Timeout: 30
      EnableVless: true
      VlessFlow: "xtls-rprx-vision"
      DisableCustomConfig: false
    ControllerConfig:
      ListenIP: 0.0.0.0
      UpdatePeriodic: 60
      DisableLocalREALITYConfig: true
      EnableREALITY: false
      CertConfig:
        CertMode: none
```

- `PanelType` must be `SSpanel` (not `NewV2board` — that is for Xboard UniProxy).
- `DisableLocalREALITYConfig: true` so Reality keys come from panel `custom_config` (Xboard-Node style: panel owns protocol settings).
- Open the node port on the firewall; for REALITY no ACME cert is needed.

## Hiddify import

| Client | URL |
|--------|-----|
| Desktop / recommended | `{subUrl}/sub/{token}/singbox` or one-click `hiddify://import/.../singbox` |
| Android Clash path | `{subUrl}/sub/{token}/clash` (Clash Meta with VLESS+REALITY) |

User-Agent containing `Hiddify` on `/json` is remapped to Sing-box automatically.

## Quick checks

1. Panel: open `/sub/{token}/clash` — proxy `type` must be `vless` and include `reality-opts.public-key`.
2. XrayR log: sync users without `custom_config format error` / panic.
3. Hiddify: import → select node → connect; if fail, verify public/private key pair and `short_id`.
