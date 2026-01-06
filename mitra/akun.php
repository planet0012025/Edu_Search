<?php
session_start();
include '../config/koneksi.php';

// 1. Cek Login Mitra
if(!isset($_SESSION['mitra_login'])){ echo '<script>window.location="login.php"</script>'; exit; }

$id_mitra = $_SESSION['id_mitra'];
$id_sekolah = $_SESSION['id_sekolah'];

// --- LOGIKA UPDATE AKUN ---
if(isset($_POST['update_akun'])){
    $nama_pic = mysqli_real_escape_string($koneksi, $_POST['nama_pic']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    
    // Cek Password Baru
    $pass_sql = "";
    if(!empty($_POST['password'])){
        $pass = md5($_POST['password']);
        $pass_sql = ", password='$pass'";
    }

    // VALIDASI: Cek Username Kembar
    $cek_user = mysqli_query($koneksi, "SELECT * FROM tb_mitra WHERE username='$username' AND id_mitra != '$id_mitra'");
    
    if(mysqli_num_rows($cek_user) > 0){
        echo '<script>alert("Username sudah digunakan orang lain! Silakan pilih username berbeda.");</script>';
    } else {
        $update = mysqli_query($koneksi, "UPDATE tb_mitra SET 
            nama_lengkap='$nama_pic', 
            username='$username' 
            $pass_sql 
            WHERE id_mitra='$id_mitra'");

        if($update){
            $_SESSION['nama_mitra'] = $nama_pic; 
            echo '<script>alert("Akun berhasil diperbarui!"); window.location="akun.php"</script>';
        } else {
            echo '<script>alert("Gagal update akun: '.mysqli_error($koneksi).'");</script>';
        }
    }
}

// --- AMBIL DATA AKUN & SEKOLAH ---
// Menggunakan @ untuk suppress error jika kolom created_at belum ada (Fallback ke tahun sekarang)
$q_akun = mysqli_query($koneksi, "
    SELECT m.*, s.npsn, s.no_wa_admin 
    FROM tb_mitra m 
    JOIN tb_sekolah s ON m.id_sekolah = s.id_sekolah 
    WHERE m.id_mitra='$id_mitra'
");
$d = mysqli_fetch_object($q_akun);

// Coba ambil tanggal daftar (jika kolom ada)
$tgl_daftar = isset($d->created_at) ? $d->created_at : date('Y-m-d');

// --- GENERATE KODE SEKOLAH ---
// Format: SCH-TAHUN-ID (Contoh: SCH-2025-0015)
$tahun_reg = date('Y', strtotime($tgl_daftar));
$kode_otomatis = "SCH-" . $tahun_reg . "-" . sprintf("%04d", $id_sekolah);

// Prioritas tampilan: NPSN (jika ada) -> Kode Otomatis
$kode_tampil = (!empty($d->npsn)) ? $d->npsn : $kode_otomatis;
$val_wa = !empty($d->no_wa_admin) ? $d->no_wa_admin : '-';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Akun Pengelola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Style Dashboard Mitra */
        :root { --primary: #0d6efd; --dark: #333; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        .sidebar { width: var(--sidebar); background: white; color: #333; height: 100vh; position: fixed; padding-top: 20px; border-right: 1px solid #eee; }
        .brand { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px; color: var(--primary); padding: 0 20px; }
        .menu a { display: block; padding: 12px 25px; color: #555; text-decoration: none; transition: 0.3s; font-size: 14px; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: #f8f9fa; color: var(--primary); border-left-color: var(--primary); }
        .menu i { width: 25px; margin-right: 5px; }

        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 600px; }
        
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; font-family: inherit; font-size: 14px; }
        input[readonly] { background-color: #e9ecef; color: #6c757d; cursor: not-allowed; border-color: #ced4da; }
        
        label { margin-bottom: 5px; display: block; font-weight: 600; font-size: 14px; color: #555;}
        .btn-save { background: #0d6efd; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-weight: bold; transition:0.3s;}
        .btn-save:hover { background: #0b5ed7; }
        
        .alert-info { background: #e7f1ff; color: #0d6efd; padding: 15px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; border: 1px solid #b6d4fe; display: flex; align-items: center; gap: 10px; }
        .alert-info i { font-size: 18px; }
        
        .row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fas fa-school"></i> MITRA AREA</div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profil_sekolah.php"><i class="fas fa-edit"></i> Edit Profil Sekolah</a>
            <a href="fasilitas.php"><i class="fas fa-cubes"></i> Fasilitas & Sarana</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Kelola Galeri</a>
            <a href="pendaftar.php"><i class="fas fa-user-graduate"></i> Data Pendaftar</a>
            <a href="akun.php" class="active"><i class="fas fa-user-cog"></i> Akun Pengelola</a>
            <hr style="margin: 20px; border: 0; border-top: 1px solid #eee;">
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 style="margin-bottom: 20px;">Pengaturan Akun</h2>
        
        <div class="card">
            <div class="alert-info">
                <i class="fas fa-info-circle"></i>
                <div>Data ini digunakan untuk login ke Portal Mitra. Jaga kerahasiaan password Anda.</div>
            </div>

            <form action="" method="POST">
                
                <!-- KODE SEKOLAH (GENERATED / NPSN) -->
                <div class="row-grid">
                    <div>
                        <label>Kode Sekolah (Sistem)</label>
                        <input type="text" value="<?php echo htmlspecialchars($kode_otomatis); ?>" readonly title="Kode Unik Sekolah Anda">
                    </div>
                    <div>
                        <label>NPSN (Nasional)</label>
                        <input type="text" value="<?php echo htmlspecialchars($kode_tampil); ?>" readonly style="font-weight:bold; color:#0d6efd;">
                    </div>
                </div>

                <!-- NO WA ADMIN (READONLY) -->
                <label>No. WhatsApp Admin Sekolah</label>
                <input type="text" value="<?php echo htmlspecialchars($val_wa); ?>" readonly>
                <small style="display:block; margin-top:-10px; margin-bottom:15px; color:#999; font-size:11px;">*Untuk mengubah No. WA, silakan ke menu "Edit Profil Sekolah".</small>

                <!-- DATA AKUN (BISA DIEDIT) -->
                <label>Nama Penanggung Jawab (PIC)</label>
                <input type="text" name="nama_pic" value="<?php echo htmlspecialchars($d->nama_lengkap); ?>" required>

                <label>Username Login</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($d->username); ?>" required>

                <label>Ganti Password (Opsional)</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengganti password">
                <small style="color:#888; display:block; margin-top:-10px; margin-bottom:15px;">*Minimal 6 karakter disarankan.</small>

                <button type="submit" name="update_akun" class="btn-save"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </form>
        </div>
    </div>
</body>
</html>