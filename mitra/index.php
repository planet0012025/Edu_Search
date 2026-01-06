<?php
session_start();
include '../config/koneksi.php';

// Cek Login Mitra
if(!isset($_SESSION['mitra_login'])){ echo '<script>window.location="login.php"</script>'; exit; }

$id_sekolah = $_SESSION['id_sekolah'];

// Ambil Data Sekolah
$q_sekolah = mysqli_query($koneksi, "SELECT * FROM tb_sekolah WHERE id_sekolah='$id_sekolah'");
$d_sekolah = mysqli_fetch_object($q_sekolah);

// Hitung Statistik
$q_daftar = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_pendaftaran WHERE id_sekolah='$id_sekolah'");
$jml_daftar = mysqli_fetch_assoc($q_daftar)['total'];

$q_terima = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_pendaftaran WHERE id_sekolah='$id_sekolah' AND status='Diterima'");
$jml_terima = mysqli_fetch_assoc($q_terima)['total'];

$q_pending = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_pendaftaran WHERE id_sekolah='$id_sekolah' AND status='Pending'");
$jml_pending = mysqli_fetch_assoc($q_pending)['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Mitra - <?php echo $d_sekolah->nama_sekolah ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        .sidebar { width: var(--sidebar); background: white; color: #333; height: 100vh; position: fixed; padding-top: 20px; border-right: 1px solid #eee; }
        .brand { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px; color: var(--primary); padding: 0 20px; }
        .menu a { display: block; padding: 12px 25px; color: #555; text-decoration: none; transition: 0.3s; font-size: 14px; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: #f8f9fa; color: var(--primary); border-left-color: var(--primary); }
        .menu i { width: 25px; margin-right: 5px; }

        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        
        .banner { 
            background: linear-gradient(135deg, #0d6efd, #0043a8); color: white; 
            padding: 30px; border-radius: 15px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; 
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);
        }
        .logo-sekolah { width: 80px; height: 80px; background: white; border-radius: 50%; padding: 5px; object-fit: cover; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .card h3 { font-size: 30px; margin: 0; color: #333; }
        .card p { font-size: 13px; color: #888; margin-top: 5px; font-weight: 600; text-transform: uppercase;}
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-school"></i> MITRA AREA</div>
        <div class="menu">
            <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profil_sekolah.php"><i class="fas fa-edit"></i> Edit Profil Sekolah</a>
            
            <!-- MENU BARU -->
            <a href="fasilitas.php"><i class="fas fa-cubes"></i> Fasilitas & Sarana</a>
            
            <a href="galeri.php"><i class="fas fa-images"></i> Kelola Galeri</a>
            <a href="pendaftar.php"><i class="fas fa-user-graduate"></i> Data Pendaftar</a>
            
            <hr style="margin: 20px; border: 0; border-top: 1px solid #eee;">
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        
        <div class="banner">
            <img src="../uploads/<?php echo $d_sekolah->foto_logo ?>" class="logo-sekolah" onerror="this.src='../assets/img/no-image.jpg'">
            <div>
                <h2 style="margin:0; font-size:24px;"><?php echo $d_sekolah->nama_sekolah ?></h2>
                <p style="margin:5px 0 0; opacity:0.9;"><i class="fas fa-map-marker-alt"></i> <?php echo $d_sekolah->alamat ?></p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card">
                <h3><?php echo $jml_daftar ?></h3>
                <p>Total Pendaftar</p>
            </div>
            <div class="card">
                <h3 style="color: #ffc107;"><?php echo $jml_pending ?></h3>
                <p>Menunggu Verifikasi</p>
            </div>
            <div class="card">
                <h3 style="color: #198754;"><?php echo $jml_terima ?></h3>
                <p>Siswa Diterima</p>
            </div>
        </div>

    </div>

</body>
</html>