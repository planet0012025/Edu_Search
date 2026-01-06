<?php
session_start();
unset($_SESSION['uid_ortu']);
unset($_SESSION['nama_ortu']);
echo '<script>window.location="index.php"</script>';
?>
```

---

### Tahap 5: Proteksi Halaman Quiz (`assessment.php`)

Kita harus mengunci halaman ini. Jika belum login, tendang ke halaman login.

Buka file **`assessment.php`**, dan ubah bagian paling atas (baris PHP-nya saja) menjadi seperti ini:

```php
<?php 
session_start(); // Wajib start session
include 'config/koneksi.php'; 

// CEK APAKAH SUDAH LOGIN
if(!isset($_SESSION['uid_ortu'])){
    // Jika belum, arahkan ke login dengan parameter redirect
    echo "<script>
            alert('Maaf, Anda harus Login terlebih dahulu untuk mengakses Quiz ini.');
            window.location='login_user.php?redirect=quiz';
          </script>";
    exit;
}
?>
<!-- HTML SISANYA TETAP SAMA, JANGAN DIUBAH -->
<!DOCTYPE html> ...
```

---

### Tahap 6: Update Landing Page (`index.php`)

Terakhir, kita update Header di Landing Page agar tombolnya berubah dinamis:
* Jika **Belum Login**: Muncul tombol "Masuk/Daftar".
* Jika **Sudah Login**: Muncul "Halo, [Nama]" dan tombol Logout.

Buka **`index.php`** dan ganti bagian `<header>...</header>` saja dengan kode ini:

```php
    <!-- Session Start Wajib ada di paling atas index.php -->
    <?php session_start(); ?> 

    <!-- Header -->
    <header>
        <div class="container header-content">
            <div style="display:flex; align-items:center; gap:20px;">
                <a href="index.php" class="logo">
                    <i class="fas fa-graduation-cap"></i> EduSearch
                </a>
                <a href="cek_status.php" style="color:#555; font-weight:600; font-size:14px;">
                    <i class="fas fa-clipboard-check"></i> Cek Status Daftar
                </a>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                
                <!-- LOGIC TOMBOL LOGIN/USER -->
                <?php if(isset($_SESSION['uid_ortu'])): ?>
                    
                    <!-- Jika Sudah Login -->
                    <span style="font-weight:600; color:#0d6efd;">
                        <i class="fas fa-user"></i> Halo, <?php echo substr($_SESSION['nama_ortu'], 0, 10) ?>..
                    </span>
                    <a href="logout_user.php" class="btn-login" style="background:#dc3545; border-color:#dc3545; color:white; font-size:12px;">
                        Logout
                    </a>

                <?php else: ?>
                    
                    <!-- Jika Belum Login -->
                    <a href="login_user.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Masuk / Daftar
                    </a>

                <?php endif; ?>

            </div>
        </div>
    </header>
```

**Catatan Penting:**
Pastikan baris `session_start();` diletakkan di baris **paling atas** (baris 1) dari file `index.php` sebelum kode `include 'config/koneksi.php';`.

Contoh `index.php` paling atas:
```php
<?php
session_start(); // Tambahkan ini
include 'config/koneksi.php';
// ... codingan statistik ...