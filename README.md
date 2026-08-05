# Credit HP - System Monitoring Cicilan, Modal & Laba (with Evolution API WhatsApp Gateway)

Aplikasi Web Management & Monitoring Angsuran Cicilan HP berbasis **Laravel 11** & **Filament 3** dengan integrasi **Evolution API (WhatsApp Gateway)**.

---

## 🐳 Panduan Memulai dengan Docker (Turnkey Setup)

Seluruh stack (Laravel 11, Nginx, MySQL, Redis, dan Evolution API WhatsApp Gateway) telah dibungkus dalam `docker-compose.yml`.

### 1. Menjalankan Container
Jalankan perintah berikut di terminal root proyek:

```bash
# 1. Start seluruh container (App, Nginx, MySQL, Redis, Evolution API)
docker compose up -d

# 2. Install dependency Composer (jika belum)
docker compose exec app composer install

# 3. Generate App Key
docker compose exec app php artisan key:generate

# 4. Jalankan Migrasi & Seeder Data Matrix awal
docker compose exec app php artisan migrate:fresh --seed
```

---

## 📱 Akses Layanan & Integrasi WhatsApp Evolution API

| Layanan | URL / Port | Keterangan |
|---|---|---|
| **Aplikasi Credit HP** | `http://localhost:8000/admin` | Login Filament Admin (`admin@admin.com` / `password`) |
| **Evolution API** | `http://localhost:8080` | Endpoint REST API Gateway |
| **API Key Evolution API** | `429683C4C977415CAAFCCE10F7D57E11` | Set pada header `apikey` |

---

### 🟢 Langkah 2: Hubungkan Nomor WhatsApp ke Evolution API

1. **Buat Instance WhatsApp**:
   Kirim request HTTP POST ke Evolution API (misal via cURL / Postman / PowerShell):

   ```bash
   curl -X POST "http://localhost:8080/instance/create" \
     -H "Content-Type: application/json" \
     -H "apikey: 429683C4C977415CAAFCCE10F7D57E11" \
     -d '{
       "instanceName": "credit-hp",
       "token": "credit-hp-secret-token",
       "qrcode": true,
       "integration": "WHATSAPP-BAILEYS"
     }'
   ```

2. **Scan QR Code**:
   Buka browser ke `http://localhost:8080/instance/connect/credit-hp` lalu scan QR Code menggunakan aplikasi WhatsApp di HP Anda.

3. **Siap Digunakan**:
   Setiap kali Anda menekan tombol **Kirim WA Warning** di halaman [Matrix Cicilan](file:///c:/laragon/www/credit-hp/app/Filament/Pages/MatrixCicilanPage.php) atau [Monitoring Tagihan](file:///c:/laragon/www/credit-hp/app/Filament/Resources/PaymentResource.php), pesan otomatis terkirim melalui Evolution API.

---

## 🛠️ Fitur Utama Aplikasi

- 📊 **Matrix Monitoring Cicilan, Modal & Laba**: Tampilan spreadsheet interaktif (Cicilan per Bulan, Modal Produk, dan Clean Profit Laba).
- 📲 **Integrasi Auto WA Reminder**: Kirim pesan penagihan otomatis ke nomor WhatsApp pelanggan via Evolution API.
- 🧾 **Manajemen Kontrak & Transaksi Pelunasan**: Pencatatan cepat status lunas, overdue, dan skema tenor.
