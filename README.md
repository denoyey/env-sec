# 🛡️ Env-Sec: Secure .env Encryption Tool

**Env-Sec** adalah sebuah *standalone CLI tool* berbasis PHP (OOP) yang dirancang secara khusus untuk mengenkripsi dan mengamankan file konfigurasi sensitif Anda (seperti `.env`) sebelum di-*push* ke GitHub, CI/CD, atau didistribusikan ke *environment* lain.

Dibangun dengan standar kriptografi modern (**AES-256-GCM** dan **PBKDF2**), *tool* ini memastikan kredensial Anda tidak hanya terenkripsi, tetapi juga **anti-tampering** (kebal terhadap modifikasi peretas).

---

## 🌟 Fitur Utama

- **Keamanan Tingkat Tinggi (AES-256-GCM)**: Menggunakan *Authenticated Encryption*. Jika ada satu karakter/bit saja yang dimanipulasi oleh pihak ketiga, proses dekripsi akan diblokir seketika secara otomatis.
- **Kombinasi Kunci Ganda**: Menggunakan gabungan dari `APP_KEY` dan `PIN` rahasia tambahan dari Anda.
- **Anti Rainbow-Table**: Meng-*generate Salt* unik (16 bytes) secara acak dan otomatis setiap kali enkripsi dijalankan.
- **Hidden Input**: Saat Anda mengetikkan Kunci dan PIN di terminal, teks Anda disembunyikan (*stealth mode*) untuk mencegah pencurian dari rekaman layar atau riwayat terminal (*history*).
- **Non-Destructive**: Aman digunakan. File `.env` asli Anda tidak pernah dihapus, di-rename, ataupun tertimpa selama proses enkripsi/dekripsi. 

---

## 📋 Persyaratan Sistem

- **PHP CLI** >= 7.4 (Direkomendasikan PHP 8.x ke atas)
- Ekstensi **OpenSSL** (Secara *default* sudah otomatis aktif pada kebanyakan instalasi PHP)

---

## 🚀 Cara Penggunaan

Cukup letakkan file `env-sec.php` di *root directory* dari project Anda.

### 1. Mengenkripsi File

Berfungsi untuk mengubah file konfigurasi rahasia Anda menjadi file biner yang aman.

```bash
php env-sec.php encrypt
```

**Alur Berjalan:**
1. Masukkan **APP_KEY** Anda di terminal (Input disembunyikan).
2. Masukkan **Secret PIN** Anda (Input disembunyikan).
3. Script akan membaca `.env` Anda, membuat *salt* & *nonce* acak, lalu menghasilkan file **`.env.encrypted`**.
   *(Catatan: File `.env` lama Anda akan tetap ada dan tidak diganggu).*

### 2. Mendekripsi File

Berfungsi untuk mengembalikan file yang telah dienkripsi saat Anda berada di server tujuan (misalnya saat proses *deploy*).

```bash
php env-sec.php decrypt
```

**Alur Berjalan:**
1. Masukkan **APP_KEY** dan **Secret PIN** yang sama persis seperti saat mengenkripsi.
2. Script akan melakukan *cross-check integrity tag*.
3. Jika lolos keamanan dan kunci yang Anda masukkan benar, sistem akan membuat file **`.env.decrypted`**.
4. Anda bisa mengganti nama file tersebut menjadi `.env` secara manual jika ingin digunakan.

---

## 🔒 Arsitektur Keamanan (Under the Hood)

Ketika file dienkripsi, struktur *output file* dari `env-sec.php` berbentuk seperti ini (tanpa pemisah):
`[ Salt (16-bytes) ] + [ IV/Nonce (12-bytes) ] + [ Ciphertext ] + [ Auth Tag (16-bytes) ]`

- **Tidak Menyimpan State**: Sistem sama sekali tidak merekam *password* yang Anda ketikkan.
- **PBKDF2 Key Derivation**: Input rahasia Anda diputar sebanyak **100.000 iterasi** (`hash_pbkdf2` sha256) untuk menghasilkan *cryptographic key* murni 256-bit, membuat *Brute Force Attack* menjadi tidak masuk akal (memakan waktu ribuan tahun).
- **GCM Auth Tag**: Berbeda dengan *AES-CBC* konvensional, arsitektur *GCM (Galois/Counter Mode)* memiliki mekanisme perlindungan ganda untuk *Confidentiality* sekaligus *Authenticity*.

---

## ⚠️ Disclaimer
Gunakan dan simpan kunci rahasia (*APP_KEY* + *PIN*) Anda di *Password Manager* atau tempat aman lain. Jika Anda kehilangan kunci tersebut, file `.env.encrypted` Anda **mustahil untuk dipulihkan kembali**.
