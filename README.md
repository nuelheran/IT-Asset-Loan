# IT Asset Loan — Sistem Peminjaman Aset IT

Aplikasi web untuk mengelola peminjaman aset IT perusahaan (laptop, monitor, proyektor, printer, dll) antara Admin IT dan Karyawan. Dibangun dengan PHP native (tanpa framework) + MySQL.

## Tampilan & Interaktivitas

- **Desain glassmorphism** dengan latar biru gelap modern dan kartu kaca transparan (blur), kontras tinggi agar tetap mudah dibaca
- **Ikon SVG** di setiap menu sidebar (bukan teks/karakter biasa)
- **Hover-lift + tooltip** pada kartu statistik dashboard — arahkan kursor ke kartu untuk melihat penjelasan singkat
- **Klik baris tabel** pada halaman Kelola Peminjaman (admin) langsung membuka halaman detail
- **Modal konfirmasi kustom** menggantikan dialog `confirm()` bawaan browser untuk aksi penting (hapus, setujui, dll)
- **Notifikasi badge** pada menu "Perlu Persetujuan" menampilkan jumlah pengajuan yang masih pending
- **Loading state** otomatis pada tombol saat form sedang dikirim
- **Sepenuhnya responsif**: di perangkat tablet/HP (lebar ≤900px), sidebar berubah menjadi menu geser (off-canvas) yang dibuka lewat tombol hamburger; tabel data bisa digeser secara horizontal di layar sempit
- Mendukung navigasi keyboard (fokus terlihat jelas) dan menghormati preferensi *reduced motion* pengguna

## Fitur

**Admin**
- Dashboard ringkasan (total aset, tersedia, dipinjam, terlambat)
- Master data aset IT (CRUD lengkap: kode, kategori, merk, no. seri, kondisi, status)
- Kelola kategori aset
- Kelola pengguna (admin & karyawan), aktif/nonaktif akun
- Tinjau & setujui/tolak pengajuan peminjaman
- Konfirmasi penyerahan aset & konfirmasi pengembalian
- Riwayat/log setiap perubahan status peminjaman
- Cetak bukti peminjaman (print-friendly, bisa disimpan sebagai PDF lewat dialog cetak browser)

**Karyawan**
- Dashboard ringkasan peminjaman pribadi
- Ajukan peminjaman aset dari katalog aset yang tersedia
- Lihat riwayat & status peminjaman sendiri
- Ajukan pengembalian aset (mengisi kondisi aset saat dikembalikan)
- Cetak bukti peminjaman
- Edit profil & ganti password

**QR Code & Scan Aset (Admin & Karyawan)**
- Generate QR code untuk setiap aset, berisi kode aset unik untuk identifikasi
- Cetak label QR per aset (siap ditempel langsung ke barang) dari halaman Master Aset
- Cetak label QR massal (grid, bisa difilter per kategori) — admin saja
- Scan QR lewat kamera HP/tablet langsung dari browser (tidak perlu install aplikasi tambahan)
- Hasil scan langsung menampilkan info lengkap aset (kategori, merk, no. seri, kondisi) beserta status peminjaman terkini — termasuk siapa peminjamnya jika sedang dipinjam, dan riwayat 3 peminjaman terakhir
- Tersedia opsi input manual sebagai alternatif jika kamera tidak tersedia/diizinkan
- Hasil scan menyertakan shortcut langsung ke halaman Detail Peminjaman, serta tombol aksi cepat sesuai status (Setujui Peminjaman, Konfirmasi Serah Aset, atau Konfirmasi Pengembalian) khusus untuk admin — sehingga admin/petugas keamanan bisa langsung memproses status peminjaman begitu memindai barang, tanpa perlu mencari transaksinya secara manual

## Struktur Status Peminjaman

```
pending  -> menunggu persetujuan admin
approved -> disetujui, menunggu penyerahan fisik aset
active   -> aset sudah di tangan peminjam
overdue  -> melewati tanggal jatuh tempo (otomatis terdeteksi sistem)
returned -> aset sudah dikembalikan & dikonfirmasi
rejected -> pengajuan ditolak admin
```

## Instalasi

### 1. Kebutuhan Server
- PHP 7.4+ (disarankan 8.x) dengan ekstensi `mysqli`
- MySQL / MariaDB 5.7+
- Web server: Apache (XAMPP/Laragon) atau Nginx

### 2. Setup Database
1. Buat database baru, atau biarkan script SQL membuatkannya otomatis.
2. Import `database.sql` melalui phpMyAdmin atau CLI:
   ```bash
   mysql -u root -p < database.sql
   ```
   Script ini otomatis membuat database `it_asset_loan`, seluruh tabel, dan data contoh (3 user, 6 kategori, beberapa aset).

### 3. Konfigurasi Koneksi
Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_asset_loan');

define('BASE_URL', '/it-asset-loan/');
```

Jika aplikasi diakses langsung dari root domain (bukan subfolder), set `BASE_URL` menjadi `'/'`.

### 4. Upload ke Server
Salin seluruh folder `it-asset-loan/` ke direktori web server, misalnya:
- XAMPP: `C:/xampp/htdocs/it-asset-loan/`
- Linux/cPanel: `/var/www/html/it-asset-loan/` atau `public_html/it-asset-loan/`

Pastikan folder `uploads/` dan `exports/` dapat ditulis oleh web server (permission 755/775).

### 5. Akses Aplikasi
Buka browser ke `http://localhost/it-asset-loan/`.

## Akun Demo

| Role     | Email                       | Password     |
|----------|------------------------------|--------------|
| Admin    | admin@company.com            | password123  |
| Karyawan | budi.santoso@company.com     | password123  |
| Karyawan | siti.aminah@company.com      | password123  |

**Penting:** Segera ganti password akun-akun ini setelah deployment ke server produksi, melalui menu Profil Saya (atau Kelola Pengguna untuk admin).

## Struktur Folder

```
it-asset-loan/
├── config/
│   └── database.php       # konfigurasi koneksi DB & BASE_URL
├── includes/
│   ├── functions.php      # helper functions, auth, dll
│   ├── header.php         # template header + sidebar navigasi
│   └── footer.php         # template footer
├── assets/css/style.css   # stylesheet utama
├── assets/js/vendor/       # library QR (qrcode.min.js, html5-qrcode.min.js) — lokal, tidak butuh CDN
├── uploads/                # (opsional) untuk foto aset
├── exports/                 # (opsional) untuk file export
├── database.sql            # schema + seed data
├── index.php                # entry point (redirect ke login/dashboard)
├── login.php / logout.php
├── dashboard.php
├── assets_list.php / asset_form.php / asset_delete.php
├── categories.php
├── users_list.php / user_form.php / user_toggle.php
├── loan_request.php         # form pengajuan peminjaman (karyawan)
├── my_loans.php              # riwayat peminjaman karyawan
├── loan_return_request.php   # form pengembalian (karyawan)
├── loans_list.php / loan_detail.php / loan_approve.php  # kelola peminjaman (admin)
├── loan_print.php            # cetak bukti peminjaman (semua role, miliknya sendiri)
├── scan.php                   # scan QR via kamera + input manual
├── asset_qr_print.php         # cetak label QR satu aset
├── assets_qr_print_bulk.php   # cetak label QR massal (admin)
├── asset_lookup.php            # endpoint AJAX: lookup info aset dari kode/hasil scan
└── profile.php                # edit profil & ganti password
```

## Catatan Keamanan untuk Produksi
- Ganti seluruh password default sebelum digunakan secara nyata.
- Pertimbangkan menambahkan HTTPS (SSL) pada server produksi.
- **Penting untuk fitur Scan QR**: browser modern (Chrome, Safari, Firefox, dll) **mewajibkan koneksi HTTPS** agar bisa mengakses kamera perangkat. Akses kamera hanya akan berfungsi otomatis di `localhost` (tanpa HTTPS) untuk keperluan development; begitu di-deploy ke domain/IP sungguhan, pastikan sudah memakai HTTPS, atau fitur "Input Manual" yang menjadi alternatifnya akan tetap berfungsi sebagai pengganti.
- Set `display_errors = Off` pada `php.ini` produksi agar pesan error tidak tampil ke pengguna akhir.
- Lakukan backup rutin pada database.

## Pengembangan Lanjutan (Opsional)
- Tambah upload foto aset (kolom `photo` sudah tersedia di tabel `assets`).
- Tambah notifikasi email saat pengajuan disetujui/ditolak.
- Tambah export laporan ke Excel/PDF dengan library seperti PhpSpreadsheet atau DomPDF untuk hasil cetak yang lebih presisi.
- Tambah halaman approval bertingkat (atasan langsung) jika diperlukan alur persetujuan berjenjang.
