# Credit HP - System Monitoring Cicilan, Modal & Laba (with Evolution API WhatsApp Gateway)

Aplikasi Web Management & Monitoring Angsuran Cicilan HP berbasis **PHP 8.3**, **Laravel 12** & **Filament 5** dengan integrasi **Evolution API (WhatsApp Gateway)**.

---

## 🐳 Panduan Memulai dengan Docker (Turnkey Setup)

Seluruh stack (PHP 8.3, Laravel 12, Nginx, MySQL, Redis, dan Evolution API WhatsApp Gateway) telah dibungkus dalam `docker-compose.yml`.

### 1. Setup development

Prasyarat: Docker dengan Compose v2 (mendukung `--wait`), Node.js 22, npm,
Python 3, serta akses internet untuk mengunduh image dan dependensi.

```bash
bash scripts/setup.sh
```

Skrip membuat `.env` dengan kredensial acak hanya jika file belum ada,
membangun aset, menyalakan aplikasi dan MySQL, memasang dependensi Composer,
serta menjalankan migrasi tanpa menghapus data. `.env` yang sudah ada
harus memiliki `DB_PASSWORD` dan `DB_ROOT_PASSWORD` yang sesuai dengan database.
Mengganti password di `.env` tidak mengubah password volume MySQL yang sudah ada.

Konfigurasi native memakai `DB_HOST=127.0.0.1`; container aplikasi otomatis
memakai `DB_HOST=db`. Nama database default adalah `credit_hp` dan pengguna
`credit_hp_user`. Database tidak dipublikasikan ke host; untuk setup native,
sediakan MySQL sendiri dan sesuaikan `.env`.

Buka `http://localhost:8000/admin`. Setup tidak membuat akun admin otomatis.
Untuk database demo baru dan kosong saja, seeder berikut membuat akun
`admin@admin.com` / `password` **dan menghapus data transaksi lama**:

```bash
docker compose exec app php artisan db:seed
```

Jangan jalankan seeder ini pada database yang berisi data pengguna.

### 2. Pengujian

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan test
```

PHPUnit menggunakan SQLite di memori, terpisah dari database aplikasi.
Ekstensi `pdo_sqlite` tersedia dalam Dockerfile. CI menjalankan pemeriksaan
platform Composer, build frontend, dan tes pada setiap push/pull request.

### 3. WhatsApp opsional

```bash
docker compose --profile whatsapp up -d evolution-redis evolution-api
```

Isi `EVOLUTION_API_KEY` di `.env` jika memakai konfigurasi sendiri. Aplikasi
container menggunakan `http://evolution-api:8080`; akses dari host memakai
`http://localhost:8080`. Gunakan kunci dari `.env` dalam header `apikey`.
Kredensial lama yang pernah tersimpan di repository perlu diganti pada layanan
aktif. Konfigurasi image Evolution API perlu diverifikasi sebelum menghubungkan
akun WhatsApp; skrip setup hanya memvalidasi aplikasi inti.

Buat instance `credit-hp` melalui API gateway dan scan QR dari WhatsApp bila
integrasi diperlukan. Setup tidak menghubungkan akun atau mengirim pesan.

---

## 🛠️ Fitur Utama Aplikasi

- 📊 **Matrix Monitoring Cicilan, Modal & Laba**: Tampilan spreadsheet interaktif (Cicilan per Bulan, Modal Produk, dan Clean Profit Laba).
- 📲 **Integrasi Auto WA Reminder**: Kirim pesan penagihan otomatis ke nomor WhatsApp pelanggan via Evolution API.
- 🧾 **Manajemen Kontrak & Transaksi Pelunasan**: Pencatatan cepat status lunas, overdue, dan skema tenor.
