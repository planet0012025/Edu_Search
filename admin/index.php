<?php
session_start();
include '../config/koneksi.php';

// Cek Login Admin
if(!isset($_SESSION['status_login']) || $_SESSION['status_login'] != true){ 
    echo '<script>window.location="login.php"</script>'; 
    exit; 
}

// --- 1. HITUNG DATA UTAMA ---
// Total Sekolah
$q_sekolah = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_sekolah");
$jml_sekolah = mysqli_fetch_assoc($q_sekolah)['total'];

// Total Pendaftar
$q_pendaftar = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_pendaftaran");
$jml_pendaftar = mysqli_fetch_assoc($q_pendaftar)['total'];

// Total User Orang Tua (Cek tabel dulu untuk keamanan jika belum buat)
$jml_users = 0;
$check_tbl = mysqli_query($koneksi, "SHOW TABLES LIKE 'tb_orang_tua'");
if(mysqli_num_rows($check_tbl) > 0){
    $q_users = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_orang_tua");
    $jml_users = mysqli_fetch_assoc($q_users)['total'];
}

// --- 2. HITUNG KATEGORI SEKOLAH ---
$q_negeri = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_sekolah WHERE status_sekolah='Negeri'");
$negeri = mysqli_fetch_assoc($q_negeri)['total'];

$q_swasta = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_sekolah WHERE status_sekolah='Swasta'");
$swasta = mysqli_fetch_assoc($q_swasta)['total'];

$q_boarding = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_sekolah WHERE status_sekolah='Boarding School'");
$boarding = mysqli_fetch_assoc($q_boarding)['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin - EduSearch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        /* Sidebar Styling */
        .sidebar { width: var(--sidebar); background: var(--dark); color: #fff; height: 100vh; position: fixed; padding-top: 20px; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; letter-spacing: 1px; }
        .menu a { display: block; padding: 15px 25px; color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: rgba(255,255,255,0.05); color: white; border-left-color: var(--primary); }
        .menu i { width: 25px; margin-right: 10px; text-align: center;}

        /* Main Content Styling */
        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        
        /* Welcome Banner */
        .welcome-banner { 
            background: linear-gradient(135deg, #0d6efd, #0043a8); 
            color: white; padding: 35px; border-radius: 15px; margin-bottom: 40px; 
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.2);
            display: flex; justify-content: space-between; align-items: center;
        }
        .welcome-text h2 { margin: 0 0 5px 0; font-size: 24px; }
        .welcome-text p { margin: 0; opacity: 0.9; font-size: 14px; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .card { 
            background: white; padding: 25px; border-radius: 12px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            display: flex; align-items: center; justify-content: space-between; 
            border-bottom: 4px solid #ddd; transition: 0.3s;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        
        .card-info h3 { font-size: 36px; margin-bottom: 0; color: #333; line-height: 1; }
        .card-info p { color: #888; font-size: 13px; font-weight: 600; text-transform: uppercase; margin-top: 5px; letter-spacing: 0.5px; }
        .icon-box { font-size: 45px; opacity: 0.2; }

        /* Warna Kustom Kartu */
        .c-blue { border-bottom-color: #0d6efd; } .c-blue .icon-box { color: #0d6efd; }
        .c-green { border-bottom-color: #198754; } .c-green .icon-box { color: #198754; }
        .c-purple { border-bottom-color: #6f42c1; } .c-purple .icon-box { color: #6f42c1; }
        .c-orange { border-bottom-color: #fd7e14; } .c-orange .icon-box { color: #fd7e14; }
        .c-cyan { border-bottom-color: #0dcaf0; } .c-cyan .icon-box { color: #0dcaf0; }
        
        .section-label { font-size: 18px; font-weight: 700; color: #444; margin-bottom: 20px; padding-left: 15px; border-left: 5px solid #0d6efd; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> ADMIN PANEL</div>
        <div class="menu">
            <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="data_sekolah.php"><i class="fas fa-school"></i> Data Sekolah</a>
            <a href="fasilitas.php"><i class="fas fa-cubes"></i> Fasilitas & Sarana</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri Foto</a>
            <a href="pendaftar.php"><i class="fas fa-user-graduate"></i> Data Pendaftar</a>
            <a href="data_users.php"><i class="fas fa-users"></i> Data Akun Ortu</a>
            
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 25px;">
            
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Halo, Admin! 👋</h2>
                <p>Selamat datang kembali di panel kontrol EduSearch.</p>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 50px; font-size: 14px;">
                <i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?>
            </div>
        </div>

        <!-- STATISTIK UTAMA -->
        <div class="stats-grid">
            <!-- Total Sekolah -->
            <div class="card c-blue">
                <div class="card-info">
                    <h3><?php echo $jml_sekolah ?></h3>
                    <p>Total Sekolah</p>
                </div>
                <div class="icon-box"><i class="fas fa-school"></i></div>
            </div>

            <!-- Total Pendaftar -->
            <div class="card c-green">
                <div class="card-info">
                    <h3><?php echo $jml_pendaftar ?></h3>
                    <p>Pendaftar Masuk</p>
                </div>
                <div class="icon-box"><i class="fas fa-file-contract"></i></div>
            </div>

            <!-- Total User -->
            <div class="card c-purple">
                <div class="card-info">
                    <h3><?php echo $jml_users ?></h3>
                    <p>Akun Orang Tua</p>
                </div>
                <div class="icon-box"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <!-- STATISTIK KATEGORI -->
        <div class="section-label">Kategori Sekolah Mitra</div>
        
        <div class="stats-grid">
            <div class="card c-green">
                <div class="card-info">
                    <h3><?php echo $negeri ?></h3>
                    <p>Sekolah Negeri</p>
                </div>
                <div class="icon-box"><i class="fas fa-building"></i></div>
            </div>

            <div class="card c-orange">
                <div class="card-info">
                    <h3><?php echo $swasta ?></h3>
                    <p>Sekolah Swasta</p>
                </div>
                <div class="icon-box"><i class="fas fa-landmark"></i></div>
            </div>

            <div class="card c-cyan">
                <div class="card-info">
                    <h3><?php echo $boarding ?></h3>
                    <p>Boarding School</p>
                </div>
                <div class="icon-box"><i class="fas fa-mosque"></i></div>
            </div>
        </div>

    </div>

</body>
</html>