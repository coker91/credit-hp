# Riwayat pekerjaan dan perubahan environment

Tanggal pekerjaan: 13 Oktober 2026 (Asia/Jakarta).
Repository: `coker91/credit-hp`, berdasarkan checkout `main`.

## Riwayat pekerjaan sesi ini

1. Environment cloud selesai startup; repository tersedia di `/workspace/credit-hp`.
2. Skill `cloud-environment-onboarding:setup` dicari dalam katalog cloud,
   katalog executor, dan filesystem tetapi tidak tersedia. Setup awal berhenti
   tanpa mengubah konfigurasi aplikasi.
3. Atas permintaan pengguna, dibuat skill lokal pengganti di
   `.agents/skills/cloud-environment-onboarding-setup/SKILL.md`. Ini panduan
   repository, bukan pemasangan plugin asli. Penemuan otomatis bergantung pada
   dukungan client untuk skill repository.
4. README, konfigurasi Composer/npm, Docker, PHPUnit, migrasi, dan tes diperiksa.
   Ditemukan perbedaan nama database, kredensial tetap dalam Compose, tes yang
   belum menggunakan database terpisah, dan seeder demo yang menghapus transaksi.
5. Setelah pengguna menyetujui improvement, konfigurasi setup, keamanan
   kredensial, isolasi tes, health check, dokumentasi, dan CI diperbarui.
6. Verifikasi frontend dan konfigurasi dijalankan. Verifikasi PHP/Docker
   terhalang kegagalan DNS pada build container, termasuk percobaan jaringan host.
7. Atas permintaan pengguna, seluruh perubahan dicatat di dokumen ini dan
   dimasukkan dalam satu commit lokal. Tidak ada push atau deployment dalam sesi ini.

## Daftar perubahan

| File | Perubahan dan tujuan |
|---|---|
| `.agents/skills/cloud-environment-onboarding-setup/SKILL.md` | Panduan lokal untuk inspeksi, instalasi dependency terkunci, setup database, layanan opsional, dan verifikasi. |
| `.env.example` | URL aplikasi port 8000, database `credit_hp`, pengguna `credit_hp_user`, variabel root password, dan API key kosong. |
| `docker-compose.yml` | Password/API key dibaca dari environment; host database dan gateway sesuai jaringan container; MySQL health check menahan startup aplikasi sampai database siap. |
| `docker-compose.yml` | Port MySQL/Redis tidak dipublikasikan; web/gateway dibatasi ke localhost; Evolution API menjadi profile `whatsapp` opsional. |
| `Dockerfile` | Tambahan library SQLite dan ekstensi `pdo_sqlite` untuk database pengujian. |
| `.dockerignore` | Mengecualikan environment rahasia, metadata Git/skill, dependency lokal, dan berkas runtime dari build context. |
| `scripts/setup.sh` | Skrip executable untuk cek prasyarat, membuat `.env` baru dengan secret acak dan permission 0600, install/build frontend, startup core services, install/check Composer, key bila kosong, dan migrasi non-destruktif. `.env` lama dipertahankan. |
| `phpunit.xml` | Memaksa SQLite `:memory:` dan mengosongkan DB_URL agar tes tidak memakai database aplikasi. |
| `tests/TestCase.php` | Menolak konfigurasi cache atau database selain SQLite di memori sebelum database test diinisialisasi. |
| `tests/Feature/FilamentUpgradeTest.php` | Menggunakan `RefreshDatabase` sehingga schema tersedia pada database test sementara. |
| `.github/workflows/ci.yml` | CI untuk push/PR: PHP 8.3, Node.js 22, install Composer/npm, cek platform, generate key, build, dan tes. |
| `README.md` | Panduan setup baru, kredensial, database native vs Docker, tes terisolasi, WhatsApp opsional, dan peringatan seeder demo yang menghapus data lama. |
| `docs/WORK_HISTORY.md` | Catatan pekerjaan, perubahan, verifikasi, batasan, dan snapshot riwayat commit sebelumnya. |

## Hasil verifikasi

- `npm ci --cache /tmp/credit-hp-npm-cache --loglevel error`: berhasil;
  cache default pertama gagal karena direktori home tidak writable.
- `npm run build`: berhasil dengan Vite 6.4.3, 58 modul ditransformasi.
- `bash -n scripts/setup.sh`: berhasil.
- `git diff --check`: berhasil.
- Parse XML `phpunit.xml` dengan Python: berhasil.
- `docker compose config --quiet`, dengan credential dummy untuk validasi:
  berhasil untuk core configuration dan profile `whatsapp`.
- Build Docker default pertama terhalang direktori konfigurasi Docker read-only.
  Penggunaan `DOCKER_CONFIG=/tmp/credit-hp-docker` mengatasi hambatan filesystem.
- Build Docker dengan konfigurasi temporary, serta percobaan `--network host`:
  gagal di `apt-get` karena `Temporary failure resolving 'deb.debian.org'`.
- Composer platform check, tes PHP, startup aplikasi, migrasi aktual, dan
  respons HTTP belum terverifikasi karena image PHP aplikasi belum bisa dibangun
  dan runtime PHP/Composer native tidak tersedia.
- Workflow CI telah ditambahkan tetapi belum dijalankan di GitHub.

## Batasan dan langkah berikutnya

- Selesaikan masalah DNS build Docker, lalu jalankan `bash scripts/setup.sh`
  dan `docker compose exec app php artisan test`.
- Setup tidak menjalankan seeder, menghapus database, menghubungkan akun WhatsApp,
  mengirim pesan, atau melakukan deployment.
- Seeder demo asli tidak diubah; jika dijalankan manual, ia tetap menghapus
  transaksi lama. Gunakan hanya untuk database demo baru yang kosong.
- `.env` baru mendapatkan credential acak; konfigurasi yang sudah ada harus
  diisi dengan credential database yang benar. Mengubah `.env` tidak mengganti
  password pada volume MySQL yang sudah ada.
- API key dan password lama dihapus dari konfigurasi saat ini tetapi masih
  mungkin ada dalam riwayat Git. Rotasi credential layanan aktif belum dilakukan.
- Dependency lockfiles tidak diubah. Build frontend dan dependency lokal yang
  diabaikan Git tidak dimasukkan ke commit.

## Snapshot seluruh riwayat commit yang tersedia sebelum perubahan ini

Daftar ini mencakup 17 commit yang terjangkau dari HEAD awal checkout,
urut dari paling lama. Ini bukan klaim bahwa seluruh branch remote telah diambil.
Commit pekerjaan ini dapat dilihat melalui `git log` setelah commit dibuat.

```text
c572971cfbe3c12aafc17ffb1cc4cacbc0c67f9f | 2026-08-05T09:26:14+07:00 | initial commit
be118e305b89d2e46efaff4cc177a2be66c188ec | 2026-08-05T09:29:18+07:00 | inital commit
b81af7cb64f1061af0931d4f03a45b4baf6f3390 | 2026-08-05T13:43:12+07:00 | feat: integrate Filament Shield for role-based access control and model permissions
f4fb11ec5cc9a7bedd6e24ba25944f08e71c4e5f | 2026-08-05T13:43:20+07:00 | feat: integrate filament-shield for role-based access control and configure resource policies
ec8caa627c34da8ebf4048de0561fb7cfb5e0a2f | 2026-08-05T13:43:31+07:00 | feat: implement role-based access control with Spatie permissions and Filament Shield
3ec72cae4e5176e2c8a17c2e834afa305cf17fc5 | 2026-08-05T13:43:38+07:00 | feat: implement Filament Shield and model policies for enhanced role-based access control
097fe11c9645bebabf9a66d5a284555ee2de3c3d | 2026-08-05T13:43:55+07:00 | feat: implement Filament Shield and model authorization policies for user management and system access control
5eeb2d110dcf1ecd373c1f0273e3aa297a79dcf8 | 2026-08-05T13:45:42+07:00 | feat: implement model policies and setup User Filament resource for granular access control
fc874e41a51affcf24be9ac56186a3766c602aea | 2026-08-05T13:46:08+07:00 | feat: implement Filament UserResource management and configure Spatie permissions
5651692c4e92d780c3dfd18577f3fcae9e467cf4 | 2026-08-05T13:46:17+07:00 | feat: add Spatie permission configuration file
faaefd8411ea30649e7c4fe4a833854daa7e08e7 | 2026-09-11T16:53:12+07:00 | feat: implement core credit system with contracts, guarantors, payment services, and Filament admin resources
b6210efa21a2060014ee7a81cd4eb4feddde30f6 | 2026-09-14T14:41:01+07:00 | feat: update product references to use 'brand' instead of 'model_name' across resources and views
49445d8fb41c779cb4f430559a9d0ff34b68f2ed | 2026-09-29T14:59:41+07:00 | chore: upgrade to PHP 8.3 and Filament 5
6045cd0f2d8a07d2e39ca3efe24304c21041d4fd | 2026-09-29T15:00:08+07:00 | refactor: migrate admin panel to Filament 5 APIs
10722d88d3985e1f30522fdcf3d4e2066e82863c | 2026-09-29T15:00:48+07:00 | fix: handle missing installment schedules in matrix
110f818d76e2c6dbe0a3c3a0cddd90505af39b41 | 2026-09-29T15:01:24+07:00 | feat: add interactive payment monitoring and kanban
0b52e4af76b0129a52d415d41e6d197083f68a5e | 2026-09-29T15:01:35+07:00 | test: cover Filament upgrade and payment workflows
```

## Lanjutan review akses, input resource, dan cloud testing

Tanggal: 13 Oktober 2026 (Asia/Jakarta).

- Commit setup `429f7ea` sebelumnya sudah dipush ke branch `work`.
- Skill setup resmi kemudian tersedia dan dibaca untuk menyiapkan pengujian.
  Skill lokal sebelumnya tetap dicatat sebagai panduan pengganti, bukan plugin asli.
- Model User menerapkan kontrak akses panel Filament dan cast/default `is_active`.
  Login menolak akun nonaktif; middleware persistent menolak sesi nonaktif,
  termasuk pembaruan Livewire.
- Matrix memakai permission halaman Shield. Aksi pelunasan dan WhatsApp
  memeriksa izin update payment di server; pengguna tanpa izin mendapat 403.
- Dibuat `PaymentSettlement` untuk Matrix, Kanban, tabel pembayaran, dan relasi
  angsuran. Transaksi mengunci kontrak sebelum pembayaran untuk menyelaraskan
  pelunasan antar-angsuran, mempertahankan idempotensi, dan menyelesaikan kontrak
  hanya jika jadwal lengkap serta seluruh angsuran lunas.
- Pemilihan payment pada tabel dibatasi ke relasi kontrak yang sedang ditampilkan.
- Form pengguna dilengkapi email wajib dan unik, validasi username unik,
  toggle `is_active` yang benar, serta pembaruan waktu penggantian password.
- Tes input menemukan NIK tersimpan sebagai notasi ilmiah (contoh
  `9999000000000001` menjadi `9.999E+15`). Input NIK nasabah/penjamin kini
  berupa string 16 digit dengan inputMode numeric dan regex, sehingga nilainya utuh.
- Tampilan Matrix memberi keterangan akses lihat saja dan geser tabel.
  Tombol pelunasan dinonaktifkan bagi pengguna tanpa izin dan selama penyimpanan.
- Tambahan tes mencakup login aktif/nonaktif, penonaktifan sesi, akses Matrix,
  penolakan pelunasan/WhatsApp, idempotensi, dan jadwal yang belum lengkap.
- Tes input mengisi form Role, User, Customer, Product, Guarantor, dan Contract;
  memeriksa halaman daftar/edit; lalu mencatat pembayaran lewat tabel dan Kanban.
- Pengguna meminta akun cloud `testing` dan data semua resource. Dibuat `.env`
  development lokal dengan SQLite `database/cloud.sqlite`, session/cache file,
  serta queue sync. File .env dan SQLite diabaikan Git.
- Role/permission dibuat melalui `ShieldSeeder` saja. `DatabaseSeeder` yang
  menghapus transaksi tidak dijalankan. Data sintetis untuk seluruh resource
  dibuat secara idempotent dalam database cloud yang terpisah; tanpa pesan WA.
- Akun `testing` memiliki role super_admin untuk pengujian development.
  Password acak disimpan secara privat (0600) di
  `/workspace/credit-hp-runtime/testing-account.json`, di luar repository.
- Runtime PHP 8.4.24/Composer 2.8.12 native disiapkan tanpa mengubah sistem:
  paket Debian diverifikasi oleh APT memakai metadata bertanda tangan dan
  Composer diverifikasi terhadap SHA256 release resmi GitHub.
- API download Composer awal ditolak jaringan. Dependency akhirnya dipasang
  lewat codeload untuk commit yang sama, dengan manifest/lockfile sementara
  di luar repository; deklarasi dan lockfile repository tidak berubah.
- Skrip refresh runtime ada di `/workspace/credit-hp-runtime/install.sh`;
  eksekusi lengkapnya berhasil. Setup tidak memerlukan Docker untuk tes SQLite.
- Asset Filament tracked yang berubah akibat script Composer dipulihkan;
  tidak disertakan sebagai perubahan aplikasi dalam commit ini.

### Verifikasi lanjutan

- Seluruh suite: **17 tes lolos, 105 assertions**, termasuk input semua resource
  dan transaksi pelunasan. Kegagalan sementara didiagnosis dan diperbaiki:
  environment PHP subprocess, default status user, form user, dan konversi NIK.
- `composer check-platform-reqs`: berhasil pada PHP 8.4.24.
- `npm run build`: berhasil, 58 modul; lockfile dependency tidak diubah.
- `vendor/bin/pint --dirty` dan `git diff --check`: berhasil.
- Semua migrasi cloud SQLite selesai; `php artisan migrate:status` terverifikasi.
- Server development dimulai dari direktori `public` memakai PHP built-in server.
  GET `/admin/login` mengembalikan 200 dan memuat field `data.login` serta
  `data.password`. Ini validasi HTTP, bukan pengujian visual/browser penuh.
- Chromium Playwright tidak terpasang; layout responsif belum diverifikasi
  melalui browser. Saran berikutnya: pagination Matrix/Kanban, identitas baris
  yang tetap terlihat saat scroll, dan pengujian tampilan mobile.
- Pembacaan status GitHub Actions melalui `gh run list` ditolak akses API.
  Workflow push/PR tersedia, tetapi hasil CI remote belum dapat dipastikan.
- Draft environment menyimpan instruksi install/start. Atas instruksi pengguna,
  izin jaringan draft diperluas ke semua domain (`*`). Perubahan draft belum
  membuktikan penerapan jaringan runtime atau publikasi snapshot.
- Pengguna mengizinkan commit, push ke `work`, dan penggabungan ke `main`.
  Hash commit dan hasil publikasi Git dicatat lewat Git; dokumen ini berada
  di dalam commit yang mencatat perubahan tersebut.

## 15 Oktober 2026 — pengingat WhatsApp H-1 dan jatuh tempo

- Sebelum mengubah kode, menjalankan `git pull --ff-only origin main` dan
  menerima commit `5cc7846` (port MySQL lokal 3307). Perubahan itu dipertahankan.
- Mengikuti arahan terbaru pengguna: dua tahap pengingat per cicilan, H-1 dan
  hari jatuh tempo, pukul 09.00 Asia/Jakarta, selama masih belum lunas.
  Tidak menambahkan branch baru; pekerjaan dilakukan pada `main`.
- Nomor bisnis pengirim yang diberikan pengguna disimpan dalam `.env` lokal
  sebagai `WA_SENDER_NUMBER`; nomor nyata dan kredensial tidak dimasukkan Git.
- Menambahkan `config/whatsapp.php` yang kompatibel dengan cache konfigurasi.
  Menghapus fallback API key tetap dari service dan memvalidasi nomor penerima.
- Menambahkan pemeriksaan instance/nomor pengirim Evolution sebelum pengiriman.
  Nomor pengirim tetap harus dihubungkan secara nyata lewat QR oleh pemilik akun.
- Menambahkan command `whatsapp:gateway status/create/connect`. QR disimpan
  sebagai file privat dengan permission 0600; secret dan isi QR tidak dicetak.
- Menambahkan command `whatsapp:reminders`, opsi `--dry-run`, batas 100 cicilan
  per run, pemeriksaan jam lokal, dan scheduler setiap menit dengan mutex.
- Menambahkan tabel/relasi riwayat pengingat dengan unique key per pembayaran
  dan tahap. Klaim transaksi mencegah proses lain mengirim tahap yang sama.
  Pesan manual memakai pencatatan yang sama untuk menekan pengiriman ganda.
- Template berisi nama nasabah, nama barang, nominal cicilan per bulan, urutan
  cicilan, tanggal jatuh tempo, dan nomor kontrak. Pengingat hari jatuh tempo
  dibatalkan bila pembayaran sudah dilunasi sejak H-1.
- Mencatat pesan yang diterima gateway, gagal, perlu diperiksa, sedang dikirim,
  dan dilewati. HTTP 429 memakai jeda 15 menit, maksimal tiga percobaan.
  Timeout/error server/response tidak pasti tidak diulang tanpa pemeriksaan.
- Menambahkan menu admin Riwayat Pengingat WhatsApp, dibatasi izin
  `view_any_payment`; aksi manual tetap memeriksa izin update pembayaran.
- Memperbarui Compose ke image `evoapicloud/evolution-api:v2.3.7` dengan
  PostgreSQL khusus gateway serta profile scheduler opsional. Image tag
  diverifikasi tersedia di registry; konfigurasi mengacu source resmi 2.3.7.
- Memperbarui `.env.example`, generator secret setup, README, dan panduan
  lengkap `WHATSAPP.md`: setup, QR, konfigurasi, scheduler, template, kegagalan,
  pengujian, serta perbedaan Evolution/Baileys dengan Cloud API resmi Meta.

### Verifikasi

- Suite lengkap: **41 tes lolos, 203 assertions** (24 tes baru WhatsApp).
  Semua request WhatsApp pada tes memakai HTTP simulasi; tidak ada pesan nyata.
- Cakupan baru: H-1 dan hari H, deduplikasi, pelunasan antar-tahap, WIB pada batas
  hari UTC, pembatasan percobaan, pengirim salah/offline, QR privat, izin riwayat,
  format respons gateway, serta perbaikan nomor yang gagal lewat aksi manual.
- Build frontend, Pint, sintaks skrip setup, dan `git diff --check`: berhasil.
- `docker compose` dengan profile `whatsapp` dan `reminders`: konfigurasi valid.
- Migrasi tabel pengingat diterapkan pada database cloud SQLite lokal.
  `whatsapp:reminders --dry-run` dan `schedule:list` terverifikasi.
- Pengguna memilih menyiapkan gateway pada kesempatan berikutnya. Gateway
  belum dipasangkan, API key belum diisi, dan pengingat otomatis tetap nonaktif.
  Startup gateway Docker serta pengiriman nyata belum dinyatakan terverifikasi.
- CI GitHub diperiksa setelah commit dipush ke `main`; detail run tersimpan
  pada GitHub Actions untuk commit terkait.
