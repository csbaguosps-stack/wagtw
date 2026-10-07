# 🚀 WAGTW - WhatsApp Multi-Device Gateway & AI Marketing Platform

![Version](https://img.shields.io/badge/Version-3.0.0-emerald?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%20|%208.0%20|%208.1%20|%208.2-blue?style=flat-square)
![Node.js](https://img.shields.io/badge/Node.js-18.x%20|%2020.x%20LTS-green?style=flat-square)
![Baileys](https://img.shields.io/badge/Engine-Baileys%20v7%20(Pure%20WebSocket)-purple?style=flat-square)
![Database](https://img.shields.io/badge/Database-MySQL%20|%20MariaDB-orange?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-teal?style=flat-square)
![Author](https://img.shields.io/badge/Coding%20by-cs.baguosps%40gmail.com-blueviolet?style=flat-square)

**WAGTW** adalah platform WhatsApp Gateway modern berbasis **PHP MVC (Native)** dan **Node.js Baileys Engine v7 (WebSocket murni tanpa Chromium/Puppeteer)**. Sistem ini sangat hemat RAM dan CPU, stabil, serta siap digunakan untuk multi-device WhatsApp, broadcast marketing, auto-responder otomatis, dan integrasi AI cerdas (Groq Llama 3) dengan fitur pencarian web realtime.

---

## 🌟 Fitur Unggulan

- ⚡ **Engine Cepat & Hemat Resource (Baileys v7)**: Berjalan murni via protokol WebSocket WhatsApp, mendukung LID (Linked ID), tanpa browser Chromium/Puppeteer sehingga hemat memori (hanya butuh RAM rendah).
- 📱 **Multi-Device & Multi-Session**: Mendukung koneksi banyak nomor WhatsApp sekaligus secara independen.
- 🤖 **Smart AI Assistant (Groq & Web Search)**: Integrasi model AI (Llama 3, Mixtral) dengan auto-switch key, prompt kustom, dan live web browsing (DuckDuckGo, Bing, Google Search).
- 💬 **Auto-Reply Fleksibel**: Pengaturan respon otomatis berdasarkan pencocokan kata (*exact match*, *contains*, *regex*, dan *spintax* dengan simulasi mengetik).
- 📢 **Broadcast & Campaign Scheduler**: Kirim pesan massal dengan personalisasi nama, import data kontak via Excel/CSV, dan jeda pengiriman dinamis anti-banned.
- 🌐 **Modern SPA-like UI**: Antarmuka responsif dan elegan dengan Tailwind CSS, pembaruan otomatis via jQuery AJAX (tanpa refresh browser), DataTables, dan SweetAlert2.
- 🔗 **REST API & Webhook**: Dokumentasi API lengkap untuk pengiriman pesan teks/media dan penerusan pesan masuk ke sistem pihak ketiga.
- 🛡️ **Proteksi Keamanan Berlapis**: Dilengkapi aturan `.htaccess` anti-akses core, pencegah eksekusi script pada folder upload, dan pemisahan file rahasia `.env`.

---

## 📁 Struktur Direktori Proyek

Proyek WAGTW memiliki struktur yang bersih dan modular:

```text
wagtw/
├── app/                  # Core MVC (Controllers, Models, Views, Helpers)
│   ├── config/config.php # Konfigurasi database & auto-detect environment
│   └── .htaccess         # Memblokir seluruh akses HTTP langsung ke core
├── public/               # Web Document Root (Aset Publik)
│   ├── index.php         # Entry point aplikasi web
│   ├── js/               # JavaScript SPA & Tailwind CSS offline
│   ├── templates/        # Template contoh broadcast CSV & Excel
│   └── uploads/          # Folder upload logo & profil (anti-script execution)
├── server/               # Engine Node.js Baileys v7
│   ├── server.js         # REST API & Socket Gateway Engine
│   ├── groq.js           # Helper AI Groq Llama 3 & fallback system
│   ├── search.js         # Helper live web search provider
│   ├── migration.sql     # Skema database MySQL lengkap
│   ├── .env.example      # Template konfigurasi environment engine
│   └── start_engine.bat  # Script auto-restart loop untuk Windows
├── database.sql          # File SQL lengkap + Super Admin bawaan siap import
├── build_cpanel_zip.bat  # Script otomatis pembuat bundle zip bersih
├── LICENSE               # Lisensi MIT (Coding by cs.baguosps@gmail.com)
├── README.md             # Dokumentasi instalasi dan penggunaan
└── wagtw_cpanel_ready.zip# Arsip siap upload ke cPanel hosting (~380 KB)
```

---

## 📋 Persyaratan Sistem

Pastikan lingkungan server Anda memenuhi spesifikasi berikut:

| Komponen | Spesifikasi Minimum | Rekomendasi |
| :--- | :--- | :--- |
| **Sistem Operasi** | Windows 10/11, Ubuntu 20.04+, Debian 11+ | Ubuntu 22.04 LTS / 24.04 LTS |
| **Web Server** | Apache 2.4+ (`mod_rewrite` aktif) atau Nginx | Nginx / Apache |
| **PHP** | PHP 7.4 | PHP 8.1 atau PHP 8.2 |
| **Ekstensi PHP** | `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `json` | Aktif bawaan PHP |
| **Database** | MySQL 5.7+ atau MariaDB 10.3+ | MySQL 8.0 / MariaDB 10.6+ |
| **Node.js** | Node.js 18.x LTS | Node.js 20.x LTS |

---

## 🛠️ Panduan Instalasi Node.js

Node.js bertugas menjalankan WhatsApp Engine (`server.js`). Pilih cara instalasi sesuai lingkungan Anda:

### A. Instalasi Node.js di Windows (Localhost / XAMPP)

1. Kunjungi situs resmi Node.js: [https://nodejs.org](https://nodejs.org)
2. Unduh versi **LTS (Long Term Support)** installer `.msi` (contoh: `v20.x.x LTS`).
3. Jalankan file `.msi`:
   - Klik **Next** dan setujui License Agreement.
   - Pada pilihan opsi, pastikan **Add to PATH** dicentang (aktif default).
   - Klik **Install** hingga selesai (**Finish**).
4. Buka **Command Prompt (CMD)** atau **PowerShell**, lalu cek versi:
   ```cmd
   node -v
   npm -v
   ```
   *Jika versi muncul (misal: `v20.18.0`), Node.js berhasil terpasang.*

---

### B. Instalasi Node.js di Linux Ubuntu / Debian (VPS Dedicated)

Jalankan perintah berikut via SSH terminal:

```bash
# 1. Update package list & install curl
sudo apt update && sudo apt install -y curl build-essential

# 2. Tambahkan repo NodeSource Node.js 20.x LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -

# 3. Install Node.js
sudo apt install -y nodejs

# 4. Verifikasi instalasi
node -v
npm -v

# 5. Install PM2 (Process Manager agar engine berjalan 24/7 di background)
sudo npm install -g pm2
```

---

### C. Instalasi Node.js di cPanel Hosting

1. Masuk ke dashboard **cPanel** hosting Anda.
2. Cari dan klik menu **Setup Node.js App** (CloudLinux).
3. Klik tombol **Create Application**:
   - **Node.js version**: Pilih `18.x` atau `20.x`
   - **Application mode**: `Production`
   - **Application root**: Isi `server`
   - **Application startup file**: Isi `server.js`
4. Klik **Create**.
5. Pada bagian konfigurasi aplikasi, klik tombol **Run NPM Install** untuk memasang seluruh dependensi.

---

## 🚀 Panduan Setup & Instalasi Proyek

### 1. Letakkan File Proyek
- **Localhost (XAMPP)**: Letakkan folder proyek di `C:\xampp\htdocs\wagtw`
- **VPS Linux**: Letakkan di direktori web, misalnya `/var/www/wagtw`
- **cPanel**: Upload file `wagtw_cpanel_ready.zip` ke `public_html`, lalu klik kanan **Extract**.

---

### 2. Setup Database MySQL
1. Buka **phpMyAdmin** (misal: `http://localhost/phpmyadmin` atau phpMyAdmin di cPanel).
2. Buat database baru bernama `wagtw` (collation: `utf8mb4_unicode_ci`).
3. Buka tab **Import**, pilih file:
   👉 **`database.sql`** *(berada langsung di folder utama proyek)*
   *(Atau file `server/migration.sql`)*
4. Klik tombol **Go / Kirim**.

> 🔑 **Akun Super Admin Bawaan (Otomatis Terbuat)**:
> - **Username**: `admin`
> - **Password**: `password`
> *(Segera ganti password Anda setelah login di menu Setting demi keamanan!)*

---

### 3. Konfigurasi File Environment (`.env`)
1. Buka folder `server/`.
2. Salin file `.env.example` menjadi `.env`:
   - **Windows CMD**:
     ```cmd
     cd server
     copy .env.example .env
     ```
   - **Linux / Bash**:
     ```bash
     cd server
     cp .env.example .env
     ```
3. Buka file `server/.env` dan sesuaikan koneksi database Anda:
   ```env
   # Engine Port & Secret Token
   WA_ENGINE_PORT=3001
   API_SECRET_TOKEN=wagtw_secret_token_ganti_dengan_token_rahasia_anda

   # Database Server
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=""
   DB_NAME=wagtw
   ```

---

### 4. Install Dependensi Node.js
Buka terminal/CMD, arahkan ke folder `server`:

```bash
cd server
npm install
```
*Tunggu hingga pengunduhan modul `@whiskeysockets/baileys`, `express`, `mysql2`, dll selesai.*

---

### 5. Menjalankan WhatsApp Engine

#### Cara 1: Localhost Windows
- Cukup klik dua kali file:
  👉 **`server/start_engine.bat`**
- *File batch ini memiliki fitur auto-restart otomatis jika terjadi kendala jaringan.*

#### Cara 2: VPS Linux dengan PM2 (Production 24/7)
```bash
cd /var/www/wagtw/server
pm2 start server.js --name "wagtw-engine"
pm2 save
pm2 startup
```

#### Cara 3: cPanel Shared Hosting
- Di menu cPanel **Setup Node.js App**, cukup klik tombol **Restart / Start Application**.

---

### 6. Mengakses Dashboard Web

1. Pastikan Web Server Apache/XAMPP dan MySQL sudah berjalan.
2. Buka browser Anda dan akses:
   ```
   http://localhost/wagtw/public
   ```
   *(Atau `https://domain-anda.com` jika di hosting)*
3. Login menggunakan akun Super Admin:
   - **Username**: `admin`
   - **Password**: `password`
4. Selamat, dashboard WAGTW siap digunakan! 🎉

---

## 📱 Panduan Penggunaan Fitur

### 1. Menautkan Nomor WhatsApp (Scan QR)
1. Buka menu **Devices** di sidebar.
2. Klik tombol **+ Tambah Device**, beri nama (misal: *CS Layanan Pelanggan*).
3. Klik tombol **Scan QR**.
4. Buka aplikasi WhatsApp di Smartphone Anda:
   - Android: Ketuk titik tiga kanan atas > **Perangkat Tertaut** > **Tautkan Perangkat**.
   - iPhone: Masuk ke Pengaturan > **Perangkat Tertaut** > **Tautkan Perangkat**.
5. Arahkan kamera HP ke QR Code pada layar.
6. Status device akan otomatis berganti menjadi **Connected**.

### 2. Balasan Otomatis (Auto-Reply)
1. Buka menu **Autoreply**.
2. Klik **Tambah Autoreply**:
   - Pilih device yang merespons.
   - Masukkan **Trigger Word** (kata kunci).
   - Tentukan tipe pencocokan: `Exact` (sama persis) atau `Contains` (mengandung kata).
   - Tulis teks balasan. Anda dapat mengaktifkan opsi simulasi mengetik (*typing indicator*).
3. Klik **Simpan**.

### 3. Mengaktifkan AI Smart Assistant
1. Buka menu **Server AI**:
   - Masukkan API Key Groq Anda (dapatkan gratis di [console.groq.com](https://console.groq.com)).
   - Pilih model (contoh: `llama-3.3-70b-versatile` atau `llama3-8b-8192`).
   - Tulis instruksi prompt bisnis Anda.
2. Aktifkan fitur **Web Search** jika ingin AI mencari info harga atau berita terbaru secara otomatis dari Google, Bing, atau DuckDuckGo.

### 4. Kirim Pesan Massal (Broadcast Campaign)
1. Buka menu **Broadcast** > **Buat Broadcast**.
2. Download template CSV/Excel yang tersedia, isi daftar nomor tujuan, lalu upload kembali.
3. Tulis pesan dengan variabel personalisasi (misal: `Halo {name}, promo hari ini...`).
4. Atur jeda aman (minimal 5–15 detik) untuk melindungi nomor dari pembatasan WhatsApp.
5. Klik **Mulai Pengiriman**.

---

## 🔒 Tips Keamanan & Anti-Banned WhatsApp

1. **Jeda Pengiriman (Delay Dinamis)**: Selalu beri jeda minimal 5–15 detik per pesan pada pengiriman broadcast.
2. **Warming Up Nomor Baru**: Nomor WhatsApp baru jangan langsung digunakan untuk blast ribuan pesan. Naikkan volume secara bertahap dalam 1–2 minggu pertama.
3. **Penyimpanan Kredensial**: File `.env` dan folder sesi `server/.sessions/` memuat token login. Jangan pernah mempublikasikannya ke repositori GitHub publik.
4. **Izin File di Hosting**: Set izin hak akses file `server/.env` ke `600` atau `640` di File Manager hosting.

---

## 📄 Lisensi & Hak Cipta

Proyek ini dilindungi di bawah lisensi resmi **MIT License**.

```text
MIT License
Copyright (c) 2024-2026 cs.baguosps@gmail.com

Coding by: cs.baguosps@gmail.com
Project: WAGTW - WhatsApp Multi-Device Gateway & AI Marketing Platform
```

Untuk konsultasi teknis, kolaborasi, atau kustomisasi fitur, silakan hubungi:
- **Email**: [cs.baguosps@gmail.com](mailto:cs.baguosps@gmail.com)
