# Sistem Monitoring Kinerja & Pelaporan (E-Laporan) - Trans Jatim

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)

Aplikasi berbasis web untuk manajemen tugas (Jobdesk), absensi, dan pelaporan harian/bulanan bagi tim **Trans Jatim**. Dikembangkan dengan **Laravel** dan **TailwindCSS**, sistem ini mempermudah monitoring kinerja karyawan, penugasan insidental, serta rekapitulasi laporan otomatis.

## 🚀 Fitur Utama

### 👨‍💻 User / Teams

-   **Jobdesk & Todo List**: Manajemen tugas harian dengan status tracking (`To Do`, `In Progress`, `Done`).
-   **Recurring Tasks**: Template tugas yang otomatis muncul setiap hari (Reset harian).
-   **Absensi (Attendance)**: Fitur _Punch In_ dan _Punch Out_ untuk mencatat kehadiran harian.
-   **Laporan Harian (Daily Report)**: Form input kegiatan harian yang terintegrasi dengan jobdesk.
-   **Riwayat & Rekap**: Melihat riwayat pekerjaan dan rekapitulasi bulanan.
-   **Bukti Kinerja**: Upload foto bukti pekerjaan dengan fitur **Crop 1:1**.

### 👮 Admin Dashboard

-   **Monitoring Terpusat**: Dashboard statistik kinerja tim, kehadiran hari ini, dan grafik aktivitas.
-   **Manajemen User (Teams)**: Kelola data karyawan/tim, roles, dan akses login.
-   **Approval & Feedback**: Validasi bukti pekerjaan dan status tugas.
-   **Jobdesk Management**: Membuat template tugas berulang dan memberikan tugas insidental (Assignments).
-   **Laporan Otomatis**:
    -   Export Laporan Harian (PDF/Excel)
    -   Export Laporan Bulanan (PDF/Excel)
-   **Pengaturan Jadwal**: Kelola hari libur dan jadwal kerja.

## 💻 Teknologi yang Digunakan

-   **Backend**: [Laravel Framework](https://laravel.com)
-   **Frontend**: [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev) + Blade Templates
-   **Build Tool**: [Vite](https://vitejs.dev)
-   **PDF Generator**: [laravel-dompdf](https://github.com/barryvdh/laravel-dompdf)
-   **Excel Export**: [Laravel Excel](https://laravel-excel.com/)
-   **Database**: MySQL

## ⚙️ Panduan Instalasi

Ikuti langkah-langkah berikut untuk menjalankan proyek ini di komputer lokal (Localhost):

### 1. Prasyarat

Pastikan Anda telah menginstal:

-   PHP >= 8.2
-   Composer
-   Node.js & NPM
-   MySQL

### 2. Clone Repository

```bash
git clone https://github.com/IbnnuuS/transjatim.git
cd transjatim
```

### 3. Install Dependencies

Install paket backend (PHP) dan frontend (JS):

```bash
composer install
npm install
```

### 4. Konfigurasi Environment (.env)

Salin file `.env.example` menjadi `.env` dan atur koneksi database:

```bash
cp .env.example .env
```

Buka file `.env` dan sesuaikan:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_anda
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Generate Key & Migrasi Database

```bash
php artisan key:generate
php artisan migrate --seed
```

> **Note:** Gunakan `--seed` jika ingin mengisi database dengan data dummy awal (Admin/User default).

### 6. Setup Storage Link

Agar file upload (foto profil, bukti kerja) dapat diakses publik:

```bash
php artisan storage:link
```

### 7. Jalankan Aplikasi

Jalankan server Laravel dan Vite secara bersamaan (buka 2 terminal):

**Terminal 1 (Laravel Server):**

```bash
php artisan serve
```

**Terminal 2 (Vite Hot Reload):**

```bash
npm run dev
```

Akses aplikasi di: `http://localhost:8000`
