# TAMARA - Install (Windows & Linux)

Aplikasi **PHP Native** dengan **Composer**. Panduan ini hanya untuk **instalasi & menjalankan**.

## Prasyarat
- PHP **8.1+**
- Composer **2.x**
- MySQL/MariaDB
- Apache (aktif **mod_rewrite**)
- Web root diarahkan ke folder **`public/`** *(atau akses langsung via `…/public/`)*

> **Penting:** Letakkan project di **htdocs** saat memakai XAMPP/LAMPP.  
> - Windows (XAMPP): `C:\xampp\htdocs\tamara`  
> - Linux (LAMPP): `/opt/lampp/htdocs/tamara`  
> - Linux (Apache native): `/var/www/tamara` (DocumentRoot → `public/`)

---

## Windows (XAMPP)

### 1) Clone ke `htdocs`
```bat
cd C:\xampp\htdocs
git clone https://github.com/<org-atau-username>/tamara.git
cd tamara
```

### 2) Composer
```bat
composer install
composer dump-autoload -o
```

### 3) Database (phpMyAdmin)
- Buka: `http://localhost/phpmyadmin`
- **Database** → **New** → buat: `tamara`
- **Import** → pilih file **.sql** (mis. `database/tamara.sql`) → **Go**

### 4) Konfigurasi & Jalankan
Edit `config/database.php`:
```php
<?php
return [
  'host'     => '127.0.0.1',
  'database' => 'tamara',
  'username' => 'root',
  'password' => '',
];
```
Start **Apache** & **MySQL** di XAMPP.  
Akses: `http://localhost/tamara/public/`

---

## Linux (LAMPP/XAMPP for Linux)
```bash
# Start LAMPP
sudo /opt/lampp/lampp start

# Clone ke htdocs LAMPP
cd /opt/lampp/htdocs
sudo git clone https://github.com/<org-atau-username>/tamara.git
cd tamara

# Composer
sudo /opt/lampp/bin/php /usr/local/bin/composer install || composer install
sudo composer dump-autoload -o
```
- Buat DB `tamara` via `http://localhost/phpmyadmin` → **Import** file **.sql**.  
- Edit `config/database.php`.  
- Akses: `http://localhost/tamara/public/`

---

## Akun Default (Dev/Testing)
> Akun berikut hanya untuk pengujian/development.
- **superadmin** - `admin123`
- **admin_gudang** - `sg123`
- **kepala_gudang** - `kg123`
- **admin_wilayah** - `sw123`
- **perwakilan_pi** - `pi123`
- **admin_pcs** - `ap123`
- **keuangan** - `kw123`

---

## Selesai
- Akses halaman via `…/public/`
- Jika error autoload: jalankan `composer dump-autoload -o`.
