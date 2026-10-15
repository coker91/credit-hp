# Panduan pengingat cicilan WhatsApp

Pengingat dikirim ke nomor nasabah pada kontrak, dalam dua tahap:

- **H-1, pukul 09.00 WIB:** sehari sebelum tanggal jatuh tempo.
- **Hari jatuh tempo, pukul 09.00 WIB:** hanya jika cicilan masih belum lunas.

Setiap tahap hanya dikirim satu kali untuk tiap cicilan. Tidak ada penagihan
harian berulang setelah tanggal jatuh tempo. Jam dapat diubah di `.env`.
Scheduler memeriksa tiap menit; bila server baru aktif setelah pukul 09.00,
pengingat yang masih relevan pada hari itu dapat menyusul. Pengingat untuk
hari yang sudah lewat tidak dikirim sebagai backlog.

## 1. Persiapan

Implementasi ini memakai **Evolution API 2.3.7 dengan koneksi WhatsApp Baileys**,
PostgreSQL khusus gateway, dan Redis. Jadwal diatur Laravel; tidak perlu menulis
skrip JavaScript terpisah. Image yang dipakai:
`evoapicloud/evolution-api:v2.3.7`.

Ambil kode terbaru dari root repository:

```bash
git pull --ff-only origin main
```

Pilih jalur sesuai cara menjalankan aplikasi:

- **Aplikasi dalam Docker:** gunakan perintah `docker compose exec app ...`.
  Database aplikasi tetap MySQL sesuai konfigurasi yang sudah ada.
- **PHP native / cloud SQLite:** gunakan `php artisan ...` langsung. Database
  SQLite tetap digunakan; PostgreSQL tambahan hanya untuk gateway WhatsApp.
  Pada cloud development yang sudah disiapkan, jalankan terlebih dahulu:
  `export PATH=/workspace/credit-hp-runtime/bin:$PATH`.

Jangan mengganti database aplikasi atau menjalankan `migrate:fresh` untuk
mengaktifkan fitur ini. Akun/data `testing` hanya untuk pengujian development.

## 2. Isi konfigurasi privat

Gunakan `.env` lokal; file ini tidak boleh di-commit. Buat dari `.env.example`
jika belum ada. Isi nomor pengirim dengan format internasional, diawali `62`,
tanpa `+` atau spasi:

```dotenv
WA_PROVIDER=evolution
WA_SENDER_NUMBER=ISI_NOMOR_PENGIRIM_FORMAT_62
EVOLUTION_API_URL=http://localhost:8080
EVOLUTION_INSTANCE=credit-hp
EVOLUTION_API_KEY=
EVOLUTION_DB_PASSWORD=
WA_REMINDERS_ENABLED=false
WA_REMINDER_TIME=09:00
WA_REMINDER_TIMEZONE=Asia/Jakarta
```

Nomor pengirim yang diminta sudah disimpan pada `.env` cloud secara privat.
Saat menyiapkan komputer/server lain, isi nomor yang sama sendiri. Nomor
pengirim tidak ditanam dalam source code. Nomor penerima berasal dari
`Customer.phone_number`; format `08...`, `8...`, `62...`, dan `+62...` didukung.

Untuk gateway lokal baru, buat secret acak tanpa mencetak nilainya. Perintah
berikut mempertahankan nilai lama yang sudah terisi. Password database aplikasi
juga diisi bila kosong untuk memenuhi validasi Compose, tetapi pilihan database
aplikasi tidak diubah:

```bash
python3 - <<'PY'
from pathlib import Path
import secrets
p = Path('.env')
if not p.exists():
    p.write_text(Path('.env.example').read_text())
lines = p.read_text().splitlines()
for key in ['EVOLUTION_API_KEY', 'EVOLUTION_DB_PASSWORD', 'DB_PASSWORD', 'DB_ROOT_PASSWORD']:
    found = False
    for i, line in enumerate(lines):
        if line.startswith(key + '='):
            found = True
            if line.split('=', 1)[1].strip() in ['', '""', "''"]:
                lines[i] = key + '=' + secrets.token_hex(32)
            break
    if not found:
        lines.append(key + '=' + secrets.token_hex(32))
p.write_text('\n'.join(lines) + '\n')
p.chmod(0o600)
print('Konfigurasi privat disiapkan; secret tidak ditampilkan.')
PY
```

Untuk gateway yang sudah ada, pakai API key milik gateway tersebut, bukan key
acak baru. Mengubah `.env` tidak mengganti password volume database yang sudah
terinisialisasi. Simpan kredensial lama jika volume PostgreSQL sudah berisi data.

Dalam container aplikasi, Compose mengganti URL gateway menjadi
`http://evolution-api:8080`. PHP native memakai URL `.env`; jika gateway berada
di server lain, gunakan alamat HTTPS gateway yang bisa dijangkau aplikasi.

## 3. Jalankan gateway dan migrasi aplikasi

Gateway lokal, untuk kedua jalur:

```bash
docker compose --profile whatsapp up -d evolution-db evolution-redis evolution-api
```

Layanan database gateway dan Redis tidak membuka port ke host. API lokal hanya
tersedia melalui port 8080 di localhost. Tunggu proses startup/migrasi gateway
selesai. Untuk memeriksa masalah startup:

```bash
docker compose --profile whatsapp ps
docker compose --profile whatsapp logs --tail=50 evolution-api
```

**Aplikasi Docker:** bangun image aplikasi baru dan jalankan migrasi tambahan:

```bash
docker compose up -d --build app db web
docker compose exec app composer install --no-interaction --prefer-dist
docker compose exec app php artisan migrate --no-interaction
docker compose exec app php artisan config:clear
```

**PHP native / cloud SQLite:**

```bash
composer install --no-interaction --prefer-dist
php artisan migrate --no-interaction
php artisan config:clear
```

## 4. Hubungkan nomor pengirim lewat QR

Buat instance baru, dari aplikasi Docker:

```bash
docker compose exec app php artisan whatsapp:gateway create
```

PHP native menggunakan `php artisan whatsapp:gateway create`.
Jika instance `credit-hp` sudah ada atau QR kedaluwarsa, minta QR baru:

```bash
docker compose exec app php artisan whatsapp:gateway connect
```

QR disimpan sebagai **`storage/app/private/whatsapp-pairing.png`** pada
repository host (di container: `/var/www/storage/app/private/...`). Buka gambar
tersebut secara lokal. Di WhatsApp Business pada nomor pengirim yang sesuai,
pilih **Perangkat tertaut → Tautkan perangkat**, lalu scan QR.

QR adalah data autentikasi sementara; jangan taruh dalam folder `public`,
commit ke Git, atau bagikan kepada orang lain. QR tidak dicetak ke terminal.

Periksa koneksi:

```bash
docker compose exec app php artisan whatsapp:gateway status
```

Hasil yang diperlukan: **`WhatsApp sender: ready`**. Aplikasi memeriksa
`ownerJid` serta status koneksi instance. Jika nomor yang dipasangkan berbeda
dari `WA_SENDER_NUMBER`, pengiriman ditolak dengan `sender_number_mismatch`.
Pengisian nomor di `.env` saja tidak menghubungkan akun WhatsApp.

## 5. Uji kandidat, lalu aktifkan otomasi

Simulasi tanpa pengiriman dan tanpa mengubah riwayat:

```bash
docker compose exec app php artisan whatsapp:reminders --dry-run
```

Perintah hanya menampilkan jumlah cicilan yang memenuhi syarat H-1/hari jatuh
tempo. Pastikan tanggal, status pembayaran, nomor nasabah, dan barang benar.

Setelah nomor terhubung dan data diperiksa, ubah `.env`:

```dotenv
WA_REMINDERS_ENABLED=true
```

Bersihkan config dan jalankan scheduler Docker:

```bash
docker compose exec app php artisan config:clear
docker compose --profile reminders up -d scheduler
```

Untuk PHP native, jalankan scheduler di bawah process manager:

```bash
php artisan schedule:work
```

Alternatif pada server dengan cron: jalankan `php artisan schedule:run` setiap
menit dari root repository, menggunakan path PHP dan repository yang benar.
Jangan menjalankan kedua metode sekaligus. Server harus tetap aktif pada
jadwal pengiriman. Jika konfigurasi diganti, bersihkan config dan restart
scheduler yang digunakan.

Menjalankan `php artisan whatsapp:reminders` **tanpa** `--dry-run` dapat
mengirim pesan sungguhan jika fitur sudah diaktifkan dan gateway siap.

Untuk menonaktifkan: set `WA_REMINDERS_ENABLED=false`, bersihkan config, lalu
hentikan scheduler profile bila tidak dipakai untuk pekerjaan lain.

## 6. Template pesan

Contoh **H-1**, dengan data fiktif:

```text
Halo Bapak/Ibu *Budi*,

Kami mengingatkan bahwa *besok* angsuran Anda jatuh tempo.

Barang: *Samsung Galaxy A55*
Cicilan per bulan: *Rp 400.000*
Cicilan ke-: 2
Tanggal jatuh tempo: *16 Oktober 2026*
No. kontrak: CONTOH-001

Mohon melakukan pembayaran sesuai jadwal. Jika sudah membayar, abaikan pesan ini dan konfirmasikan kepada admin.

Terima kasih.
```

Pada **hari jatuh tempo**, kalimat pertama berubah menjadi:
`Kami mengingatkan bahwa *hari ini* angsuran Anda jatuh tempo.`
Nama, barang, nominal per bulan, nomor kontrak, urutan cicilan, dan tanggal
semuanya diambil dari database. Template dibentuk di
`app/Services/PaymentReminderService.php`.

## 7. Riwayat dan kegagalan

Buka menu **Transaksi → Riwayat Pengingat WhatsApp**. Pengguna membutuhkan izin
`view_any_payment`; pengiriman manual tetap membutuhkan `update_payment`.

| Status | Arti / tindakan |
|---|---|
| Diterima gateway | Gateway menerima permintaan. Ini belum membuktikan pesan sampai atau dibaca di HP nasabah. |
| Gagal | Lihat kode keterangan; periksa nomor penerima dan konfigurasi gateway. |
| Perlu diperiksa | Respons tidak pasti, timeout, error server, atau proses terhenti. Periksa riwayat gateway sebelum mempertimbangkan pengiriman ulang. |
| Sedang dikirim | Proses pengiriman sudah mengklaim tahap tersebut; proses lain tidak mengirimnya lagi. |
| Dilewati | Pembayaran/tanggal/status kontrak berubah sebelum pengiriman. |

Pembatasan laju HTTP 429 dicoba kembali setelah 15 menit, maksimal tiga
percobaan pada tahap/tanggal yang sama. Timeout dan error server tidak diulang
secara buta karena gateway mungkin sudah mengirim pesannya. Proses `sending`
yang terhenti lebih dari 30 menit ditandai perlu diperiksa pada run berikutnya.
Jangan menghapus riwayat untuk memaksa pengiriman ulang tanpa pemeriksaan.

Pesan manual ikut memakai pencatatan ini; pengingat otomatis pada hari yang
sama dilewati jika pembayaran tersebut sudah mendapat pesan. Tahap H-1 dan
jatuh tempo dicatat terpisah. Cicilan lunas dan kontrak selesai tidak ditagih.
Status kontrak macet tidak termasuk pengiriman otomatis.

## 8. Pengujian dan pilihan API

```bash
php artisan test --filter=WhatsappReminderTest
php artisan test
```

Tes memakai database SQLite di memori dan HTTP simulasi; tidak menghubungi
WhatsApp atau nomor nasabah. Cakupannya termasuk dua tahap, duplikasi, perubahan
status pembayaran, zona waktu WIB, pemeriksaan pengirim, QR, dan kegagalan gateway.

Evolution/Baileys memakai sesi perangkat tertaut, bukan WhatsApp Cloud API
resmi. Memakai nomor WhatsApp Business dan volume rendah tidak menjamin akun
bebas pembatasan. Hubungi hanya nasabah yang menyetujui pengingat.

Alternatif resmi: **WhatsApp Business Platform / Cloud API**, dengan persetujuan
penerima dan template pesan proaktif yang disetujui serta mematuhi kebijakan
Meta. Template teks di atas bukan otomatis template Meta yang disetujui.
Adaptor Cloud API belum termasuk implementasi ini; jangan mengisinya sebagai
URL Evolution karena format endpoint dan autentikasinya berbeda.

Referensi gateway yang dipakai:
[Compose resmi Evolution 2.3.7](https://github.com/evolution-foundation/evolution-api/blob/2.3.7/docker-compose.yaml)
dan [contoh konfigurasi](https://github.com/evolution-foundation/evolution-api/blob/2.3.7/.env.example).
