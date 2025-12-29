🏫 Sistem Absensi Sekolah Berbasis QR Code & WhatsApp Gateway

Aplikasi berbasis web untuk mempermudah pencatatan kehadiran siswa secara digital menggunakan QR Code. Sistem ini dilengkapi dengan fitur notifikasi WhatsApp otomatis ke orang tua dan manajemen data yang lengkap.

Dibangun menggunakan PHP Native (tanpa framework) agar mudah dipelajari dan dimodifikasi.

🌟 Fitur Utama

1. 🔐 Multi-Level User (Role)

Admin: Memiliki akses penuh (Kelola User, Siswa, Jam Kerja, Hari Libur, Edit Absensi, Laporan).

Petugas: Fokus pada operasional harian (Scan QR, Input Libur, Melihat Laporan).

Guru: Memantau kehadiran siswa, Melihat Laporan, dan Menyetujui/Menolak Izin Siswa.

Siswa: Melihat riwayat absen sendiri, Mengajukan Izin (Upload Bukti), Download Kartu Pelajar.

2. 📸 Absensi Canggih

Scan QR Code: Menggunakan kamera laptop/HP/Webcam.

Validasi Ketat:

Cek Jam Masuk & Pulang.

Cek Hari Libur (Tidak bisa absen saat libur).

Cek Status Izin (Tidak bisa absen jika status sedang Izin/Sakit).

Cek Duplikat (Tidak bisa absen masuk 2x sehari).

Mode Kiosk (Scan Public): Halaman khusus untuk ditaruh di gerbang sekolah tanpa perlu login.

3. 📩 Notifikasi WhatsApp (Fonnte)

Sistem mengirim pesan otomatis ke nomor WhatsApp Orang Tua saat:

Siswa Absen Masuk (Hadir/Terlambat).

Siswa Absen Pulang.

Anti-Spam: Pesan divariasikan secara otomatis agar nomor bot tidak mudah terblokir WhatsApp.

4. 📊 Laporan & Data

Dashboard Statistik: Grafik kehadiran 7 hari terakhir (Chart.js).

Export Excel: Download rekap absensi per Bulan, Tahun, Kelas, dan Jurusan.

Cetak Kartu: Admin/Siswa bisa mencetak Kartu Pelajar yang berisi QR Code.

Import Data: Input data siswa massal menggunakan file CSV/Excel.

🛠️ Teknologi yang Digunakan

Bahasa: PHP (Native)

Database: MySQL

Frontend: HTML5, CSS3, Bootstrap 5

JavaScript:

html5-qrcode (Scanner)

SweetAlert2 (Notifikasi Cantik)

Chart.js (Grafik Statistik)

API: Fonnte (WhatsApp Gateway), QRServer (Generate QR)

🚀 Cara Instalasi

1. Persiapan Database

Buka phpMyAdmin.

Buat database baru dengan nama db_absensi.

Import file db_absensi.sql (jika ada) atau jalankan query SQL tabel manual.

2. Konfigurasi Project

Copy folder project ke dalam htdocs (jika pakai XAMPP) atau www (jika pakai Laragon).

Buka file config/db.php, sesuaikan username dan password database Anda:

$conn = mysqli_connect("localhost", "root", "", "db_absensi");


3. Konfigurasi WhatsApp (Opsional)

Daftar di Fonnte.com.

Hubungkan WhatsApp Anda (Scan QR di dashboard Fonnte).

Copy Token API Anda.

Buka file lib/wa_api.php, tempel token Anda:

$token = "TOKEN_FONNTE_ANDA";


4. Jalankan

Buka browser dan akses: http://localhost/nama_folder_project

👤 Akun Default (Login)

Berikut adalah akun bawaan untuk pengujian sistem:

Role

Username

Password

Keterangan

Admin

admin

admin123

Akses Penuh

Petugas

petugas

123

Akses Scan & Laporan

Guru

guru

123

Akses Data & Approval Izin

Siswa

12345

12345

(Sesuai NISN yang didaftarkan)

Catatan: Password siswa secara default adalah NISN mereka. Admin bisa mereset password siswa jika lupa.

📂 Struktur Folder

/absensi-qr
├── /assets              # File CSS, Images, Template CSV
├── /api                 # Backend Logic (Process Scan)
├── /config              # Koneksi Database
├── /lib                 # Helper Functions (WA API)
├── /uploads             # Bukti Izin (Foto/Surat)
│
├── index.php            # Halaman Login
├── dashboard.php        # Halaman Utama & Statistik
├── siswa.php            # Manajemen Data Siswa (CRUD, Import, Cetak)
├── scan.php             # Halaman Scan QR (Login Required)
├── scan_public.php      # Halaman Scan QR (Tanpa Login - Mode Kiosk)
├── rekap.php            # Laporan Absensi & Export Excel
├── izin.php             # Form & Approval Izin
├── profil.php           # Ganti Password & Info Akun
├── pengaturan.php       # Setting Jam Kerja (Admin)
├── libur.php            # Setting Hari Libur (Admin)
├── data_absensi.php     # Edit Data Absensi Manual
└── manajemen_user.php   # Kelola Akun Staf (Admin)


⚠️ Troubleshooting (Masalah Umum)

Kamera tidak muncul?

Pastikan browser mengizinkan akses kamera.

Jika akses lewat HP via jaringan lokal (WiFi), pastikan menggunakan https:// atau gunakan Ngrok karena browser modern memblokir kamera di http:// biasa selain localhost.

WhatsApp tidak terkirim?

Cek token Fonnte di lib/wa_api.php.

Pastikan device di dashboard Fonnte berstatus Connected.

Pastikan ada koneksi internet.

Gagal Import CSV?

Pastikan menggunakan template assets/template_siswa.csv.

Jangan mengubah urutan kolom di file CSV.

📜 Lisensi

Project ini dibuat untuk tujuan pendidikan dan pembelajaran (Open Source). Bebas dikembangkan lebih lanjut.