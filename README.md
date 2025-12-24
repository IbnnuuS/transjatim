# Sistem Monitoring Kinerja & Pelaporan (E-Laporan)

Aplikasi manajemen tugas (Jobdesk) dan pelaporan harian/bulanan berbasis web untuk instansi, dikembangkan dengan **Laravel** dan **TailwindCSS**. Aplikasi ini mempermudah monitoring kinerja tim melalui sistem jobdesk harian, penugasan insidental, serta rekapitulasi laporan otomatis ke format PDF dengan kop surat resmi.

## 🚀 Fitur Utama

### 🛠️ Manajemen Tugas (Jobdesk)

-   **Jobdesk Harian Berulang**: Template tugas harian yang otomatis muncul setiap hari.
-   **Status Tracking**: Status lengkap (`To Do`, `In Progress`, `Verification`, `Done`, dll).
-   **Bukti Kinerja**: Upload bukti foto dengan fitur **Crop 1:1** untuk kerapian.
-   **Validasi**: Sistem validasi pekerjaan oleh admin.

### 📋 Penugasan (Assignment)

-   **Tugas Insidental**: Admin memberikan tugas khusus di luar jobdesk harian.
-   **Integrasi**: Tugas yang diselesaikan otomatis masuk ke laporan harian.

### 📄 Pelaporan & Ekspor

-   **Laporan Harian (PDF)**: Generate laporan harian otomatis dengan statistik dan detail pekerjaan.
-   **Rekap Bulanan (PDF)**: Ringkasan kinerja bulanan dengan total kehadiran dan penyelesaian tugas.
-   **Format Resmi**: Layout laporan rapi menggunakan kop surat instansi (Customizable).

### 👥 Manajemen Pengguna

-   **Multi-Role**: Admin & User.
-   **Manajemen Tim**: Kelola akun, divisi, dan data karyawan.
-   **Profile Management**: Update foto profil dengan crop tool, ganti password.

## 💻 Tech Stack

-   **Backend**: [Laravel Framework](https://laravel.com)
-   **Frontend**: [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev)
-   **Asset Bundling**: [Vite](https://vitejs.dev)
-   **PDF Generation**: [laravel-dompdf](https://github.com/barryvdh/laravel-dompdf)
-   **Database**: MySQL

## ⚙️ Instalasi

Ikuti langkah berikut untuk menjalankan project di komputer lokal Anda:

1.  **Clone Repository**

    ```bash
    git clone https://github.com/username/repo-name.git
    cd repo-name
    ```

2.  **Install Dependencies**

    ```bash
    composer install
    npm install
    ```

3.  **Setup Environment**
    Salin file `.env.example` ke `.env` dan sesuaikan konfigurasi database.

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Database Migration & Seeding**

    ```bash
    php artisan migrate --seed
    ```

5.  **Setup Storage**
    Agar foto profil dan bukti pekerjaan bisa diakses publik.

    ```bash
    php artisan storage:link
    ```

6.  **Jalankan Aplikasi**
    Buka dua terminal berbeda untuk menjalankan server PHP dan Vite (Hot Reload).

    ```bash
    # Terminal 1
    php artisan serve

    # Terminal 2
    npm run dev
    ```

## 📸 Screenshots

_(Tambahkan screenshot aplikasi di sini nanti)_

## 🔐 Keamanan

-   **Role-based Access Control (RBAC)**: Middleware untuk membatasi akses Admin vs User.
-   **Authorization Checks**: Proteksi ganda pada controller untuk mencegah _Privilege Escalation_ dan _IDOR_.

## 📝 Lisensi

Project ini dilisensikan di bawah [MIT license](https://opensource.org/licenses/MIT).
