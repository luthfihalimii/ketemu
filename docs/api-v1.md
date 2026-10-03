# API v1 — KETEMU PENS

Base URL: `https://domain/api/v1`. Semua response JSON:

```json
{ "data": {} }
```

Error Laravel standar (`message`, `errors`) untuk 422/401/403/429.

## Auth

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/api/v1/login` | tidak | `{email, password, device_name?}` → `{token, user}` |
| GET | `/api/v1/me` | Sanctum | profil |
| POST | `/api/v1/logout` | Sanctum | hapus token aktif |

```bash
curl -X POST https://domain/api/v1/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"a@student.pens.ac.id","password":"secret","device_name":"android"}'
```

Kirim token: `Authorization: Bearer <token>`. Akun `banned` ditolak saat login
dan setiap request (`not-banned`).

## Katalog (publik, throttle 60/menit/IP)

- `GET /api/v1/items?q=&category=&location=` — hanya status discoverable
  (`STORED, CLAIMED, VERIFIED, READY_FOR_PICKUP`). MySQL pakai FULLTEXT.
- `GET /api/v1/items/{code}` — detail by **kode laporan** (`KP-...`), bukan ID.
  Tidak pernah expose `verification_answer` / `private_note` / `code_hash`.

Contoh item:

```json
{
  "data": {
    "code": "KP-...",
    "title": "Dompet hitam",
    "status": "STORED",
    "photo_url": "https://foto.ketemupens.id/items/xxx.webp"
  }
}
```

## Batas

- Klaim + pickup tetap via web (verifikasi + `password.confirm` + CSRF) —
  API v1 sengaja read-only katalog + auth untuk fondasi mobile.
- Tulis (lapor/klaim) masuk fase berikutnya setelah rate-limit per-akun +
  upload multipart ke R2 diuji.
