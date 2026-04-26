# Panduan Kontribusi (Contributing Guide)

Terima kasih atas minat Anda untuk berkontribusi pada **Yualan Community Edition (Dedicated Enterprise)**! Kami sangat menghargai bantuan Anda dalam membuat solusi POS mandiri yang lebih baik untuk semua orang.

---

## Kode Etik (Code of Conduct)

Kami berkomitmen untuk menciptakan lingkungan yang inklusif dan bebas dari pelecehan. Kami mengharapkan semua kontributor untuk:
- Menggunakan bahasa yang ramah dan profesional.
- Menghormati perbedaan pendapat dan pengalaman.
- Fokus pada apa yang terbaik bagi komunitas pengguna Yualan.

---

## Cara Berkontribusi

### 1. Melaporkan Bug
Jika Anda menemukan bug, silakan buat **Issue** di GitHub dengan menyertakan:
- Deskripsi bug yang jelas.
- Langkah-langkah untuk mereproduksi masalah.
- Screenshot atau log error (jika ada).
- Informasi lingkungan server Anda (Versi PHP, OS, Versi Database).

### 2. Mengusulkan Fitur
Kami menerima usulan fitur baru yang bermanfaat bagi banyak pengguna. Gunakan label `enhancement` pada Issue Anda.

### 3. Kontribusi Kode (Pull Request)
1. **Fork** repositori ini ke akun Anda.
2. Buat branch baru untuk fitur atau perbaikan Anda: `git checkout -b feature/nama-fitur`.
3. Lakukan perubahan kode dan pastikan mengikuti standar Laravel/Vue kami.
4. Jalankan pengujian: `php artisan test`.
5. Kirim **Pull Request** ke branch `main` repositori asli.

---

## Standar Penulisan Kode

### Backend (Laravel)
- Ikuti standar **PSR-12**.
- Gunakan **Type Hinting** dan dokumentasikan logika yang kompleks.
- Pastikan perubahan skema database menyertakan file migrasi yang benar.

### Frontend (Vue 3)
- Gunakan **Composition API** dengan `<script setup lang="ts">`.
- Pastikan komponen bersifat responsif.
- Gunakan standar warna dan tipografi yang sudah ada di proyek.

---

## Kebijakan Lisensi

Dengan berkontribusi pada proyek ini, Anda setuju bahwa kode Anda akan dilisensikan di bawah **GNU GPL v2**. Anda tetap memegang hak cipta atas karya Anda, namun memberikan izin kepada kami dan komunitas untuk menggunakan, memodifikasi, dan mendistribusikannya sesuai aturan lisensi tersebut.

---

## Dukungan & Komunikasi

- **GitHub Issues**: Untuk laporan bug teknis.
- **GitHub Discussions**: Untuk diskusi umum dan tanya jawab.

**Created Under PT. Nusavasoft Digital Solutions**
