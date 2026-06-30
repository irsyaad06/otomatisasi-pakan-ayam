# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# SISTEM OTOMATISASI PEMBERIAN PAKAN AYAM PEDAGING BERBASIS IoT DENGAN MONITORING KETERSEDIAAN PAKAN

---

# 1. OVERVIEW PRODUK

## 1.1 Nama Sistem

Sistem Otomatisasi Pemberian Pakan Ayam Pedaging Berbasis *Internet of Things* (IoT) dengan Monitoring Ketersediaan Pakan.

## 1.2 Latar Belakang

Proses pemberian pakan ayam pedaging pada kandang SMKN 5 Pangalengan masih dilakukan secara manual oleh petugas kandang. Kondisi tersebut menyebabkan proses pemberian pakan sangat bergantung pada keberadaan petugas, sehingga berpotensi menimbulkan keterlambatan pemberian pakan, pemberian pakan berlebih, penumpukan pakan di talang, serta kesulitan dalam memantau stok pakan secara cepat.

Untuk membantu mengatasi permasalahan tersebut, diperlukan sistem otomatisasi pemberian pakan berbasis IoT yang mampu membantu proses distribusi pakan secara terjadwal, memonitor stok pakan secara *real-time*, serta menyediakan pencatatan riwayat pemberian pakan melalui aplikasi monitoring berbasis web.

---

# 2. TUJUAN SISTEM

## 2.1 Tujuan Utama

Membangun sistem otomatisasi pemberian pakan ayam pedaging berbasis IoT yang dapat membantu proses pemberian pakan dan monitoring stok pakan secara lebih terukur, terjadwal, dan mudah dipantau.

## 2.2 Tujuan Sistem

1. Membantu pemberian pakan ayam secara otomatis berdasarkan jadwal.
2. Mengurangi pemberian pakan berlebih.
3. Membantu monitoring stok pakan secara *real-time*.
4. Menyediakan riwayat pemberian pakan secara digital.
5. Membantu petugas kandang dalam pengelolaan pakan ayam pedaging.

---

# 3. TARGET PENGGUNA

## 3.1 Pengguna Utama

### Petugas Kandang

Pengguna utama yang bertugas memantau stok pakan, jadwal pemberian pakan, serta kondisi alat.

### Guru Produktif / Pembimbing

Pengguna yang memantau laporan dan aktivitas pemberian pakan.

---

# 4. KONSEP PRODUK

## 4.1 Konsep Umum

Sistem bekerja menggunakan mikrokontroler ESP32 yang terhubung dengan sensor ketersediaan pakan dan aktuator motor *auger*. Sistem akan menjalankan pemberian pakan berdasarkan jadwal yang telah ditentukan dan mengirim data ke aplikasi monitoring berbasis web menggunakan REST API.

Aplikasi monitoring digunakan untuk:

* melihat stok pakan;
* melihat jadwal pemberian pakan;
* melihat riwayat pemberian pakan;
* melihat status alat;
* menerima notifikasi stok hampir habis.

---

# 5. FITUR UTAMA SISTEM

## 5.1 Dashboard Monitoring

### Fungsi

Menampilkan ringkasan kondisi sistem secara *real-time*.

### Informasi yang Ditampilkan

* Status alat;
* Stok pakan saat ini;
* Jadwal pemberian pakan berikutnya;
* Total pakan keluar hari ini;
* Riwayat aktivitas terbaru;
* Status koneksi perangkat IoT.

---

## 5.2 Monitoring Stok Pakan

### Fungsi

Menampilkan kondisi stok pakan pada *hopper* atau *mini-silo*.

### Fitur

* Persentase stok pakan;
* Status stok:

  * Aman;
  * Hampir habis;
  * Habis;
* Grafik perubahan stok pakan;
* Notifikasi stok hampir habis.

---

## 5.3 Penjadwalan Pakan

### Fungsi

Mengatur jadwal pemberian pakan otomatis.

### Fitur

* Tambah jadwal;
* Edit jadwal;
* Hapus jadwal;
* Menentukan durasi motor aktif;
* Menentukan jumlah pakan keluar;
* Mengaktifkan/nonaktifkan jadwal.

---

## 5.4 Riwayat Pemberian Pakan

### Fungsi

Menyimpan aktivitas pemberian pakan.

### Informasi

* Waktu pemberian pakan;
* Status berhasil/gagal;
* Jumlah pakan keluar;
* Sumber aktivitas:

  * Otomatis;
  * Manual.

---

## 5.5 Monitoring Status Alat

### Fungsi

Menampilkan kondisi perangkat IoT.

### Informasi

* Status ESP32;
* Status koneksi internet;
* Status sensor;
* Status motor *auger*;
* Status *auto-cut*.

---

## 5.6 Simulasi Manual

### Fungsi

Membantu demo dan pengujian sistem.

### Fitur

* Simulasi pemberian pakan;
* Simulasi stok hampir habis;
* Simulasi motor aktif;
* Simulasi pengiriman data ESP32.

---

# 6. KONSEP USER EXPERIENCE (UX)

## 6.1 Prinsip Desain

Sistem harus:

* sederhana;
* mudah dipahami pengguna awam;
* cepat dipelajari;
* minim teks teknis;
* fokus pada visual informasi.

## 6.2 Pendekatan UX

### Clean Dashboard

Tampilan bersih dengan fokus pada informasi utama.

### Card-Based Interface

Menggunakan *card layout* agar informasi mudah dipindai.

### Minimal Interaction

Pengguna tidak perlu banyak klik untuk melihat informasi penting.

### Status Visualization

Menggunakan:

* badge warna;
* ikon;
* progress bar;
* grafik sederhana.

---

# 7. KONSEP USER INTERFACE (UI)

# 7.1 Gaya Visual

## Konsep Visual

Modern Industrial Dashboard.

## Karakter UI

* Elegan;
* Minimalis;
* Profesional;
* Mudah dibaca;
* Tidak ramai.

## Warna Utama

### Primary

Deep Blue:
#0F172A

### Secondary

Slate:
#334155

### Accent

Emerald:
#10B981

### Warning

Amber:
#F59E0B

### Danger

Red:
#EF4444

### Background

Soft Gray:
#F8FAFC

---

# 7.2 Typography

## Font

Inter

## Karakter Font

* modern;
* bersih;
* nyaman dibaca;
* cocok untuk dashboard.

---

# 7.3 Layout

## Sidebar

Posisi kiri.

### Menu

* Dashboard;
* Stok Pakan;
* Jadwal Pakan;
* Riwayat Pakan;
* Status Alat;
* Pengaturan.

---

## Topbar

Berisi:

* nama sistem;
* status perangkat;
* notifikasi;
* profil pengguna.

---

## Main Content

Menggunakan sistem grid responsif.

---

# 8. RANCANGAN HALAMAN

# 8.1 Halaman Dashboard

## Komponen

### Card Ringkasan

* Stok pakan;
* Status alat;
* Jadwal berikutnya;
* Total pakan hari ini.

### Grafik

* Grafik stok pakan;
* Grafik aktivitas pemberian pakan.

### Activity Feed

Riwayat aktivitas terbaru.

---

# 8.2 Halaman Stok Pakan

## Komponen

* Progress stok;
* Persentase stok;
* Status stok;
* Grafik histori stok;
* Riwayat perubahan stok.

---

# 8.3 Halaman Jadwal Pakan

## Komponen

* Tabel jadwal;
* Form tambah jadwal;
* Toggle aktif/nonaktif;
* Tombol simulasi.

---

# 8.4 Halaman Riwayat Pakan

## Komponen

* Tabel riwayat;
* Filter tanggal;
* Filter status;
* Pencarian aktivitas.

---

# 8.5 Halaman Status Alat

## Komponen

* Status ESP32;
* Status sensor;
* Status koneksi;
* Status motor;
* Status API.

---

# 9. KONSEP RESPONSIVE DESIGN

## Desktop

Fokus utama penggunaan dashboard.

## Tablet

Grid menyesuaikan ukuran layar.

## Mobile

Sidebar berubah menjadi drawer.

---

# 10. KONSEP TEKNOLOGI

## Backend

Laravel REST API.

## Frontend

Vue.js.

## State Management

Pinia.

## HTTP Client

Axios.

## Database

MySQL.

## IoT Device

ESP32.

---

# 11. STRUKTUR BASIS DATA

## Tabel

* pengguna;
* periode_pemeliharaan;
* jadwal_pakan;
* stok_pakan;
* log_pemberian_pakan;
* status_alat.

---

# 12. ALUR SISTEM

1. Jadwal pakan dibaca oleh ESP32.
2. ESP32 mengaktifkan motor *auger*.
3. Pakan keluar dari *hopper*.
4. Sensor membaca kondisi stok.
5. Data dikirim ke REST API Laravel.
6. Data disimpan ke MySQL.
7. Dashboard Vue.js menampilkan data secara *real-time*.

---

# 13. NON-FUNCTIONAL REQUIREMENTS

## Performa

Dashboard harus ringan dan cepat diakses.

## Kemudahan Penggunaan

Pengguna awam harus dapat memahami dashboard tanpa pelatihan khusus.

## Reliabilitas

Sistem tetap dapat digunakan walaupun koneksi internet tidak stabil sementara.

## Maintainability

Kode harus modular dan mudah dikembangkan.

---

# 14. PRIORITAS MVP (VERSI DOSEN)

## Wajib Ada

* Dashboard monitoring;
* Stok pakan;
* Jadwal pakan;
* Riwayat pakan;
* Simulasi IoT;
* REST API;
* Database;
* UI modern.

## Tidak Wajib Dulu

* Push notification;
* Multi-user kompleks;
* MQTT broker;
* Analitik AI;
* Mobile app native.

---

# 15. TARGET HASIL DEMO

## Saat Demo

Dosen dapat:

* melihat dashboard;
* melihat stok pakan berubah;
* melihat simulasi pemberian pakan;
* melihat riwayat aktivitas;
* melihat status alat;
* memahami alur IoT secara visual.

Sistem harus terlihat:

* modern;
* jelas;
* profesional;
* mudah digunakan.
