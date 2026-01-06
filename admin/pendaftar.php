<?php
session_start();
include '../config/koneksi.php';
if($_SESSION['status_login'] != true){ echo '<script>window.location="login.php"</script>'; }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Pendaftar - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS DASHBOARD (KONSISTEN) --- */
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        /* Sidebar Styling */
        .sidebar { width: var(--sidebar); background: var(--dark); color: #fff; height: 100vh; position: fixed; padding-top: 20px; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .menu a { display: block; padding: 15px 25px; color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; }
        .menu a:hover, .menu a.active { background: var(--primary); color: white; }
        .menu i { width: 25px; }

        /* Main Content */
        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        h2 { margin-bottom: 20px; color: #333; }

        /* Table Styling Modern */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #ddd; color: #555; font-weight: 600; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; color: #444; }
        tr:hover { background-color: #fdfdfd; }
        
        /* Badges Status */
        .badge { padding: 6px 12px; border-radius: 50px; color: white; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .bg-pending { background: #ffc107; color: #856404; }
        .bg-terima { background: #198754; }
        .bg-tolak { background: #dc3545; }

        /* Action Buttons */
        .btn-action { 
            text-decoration: none; padding: 8px 12px; color: white; border-radius: 5px; 
            font-size: 12px; margin-right: 5px; transition: 0.3s; display: inline-block; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-acc { background: #198754; }
        .btn-acc:hover { background: #157347; transform: translateY(-2px); }
        .btn-rej { background: #dc3545; }
        .btn-rej:hover { background: #b02a37; transform: translateY(-2px); }
        
        /* Nama Siswa Bold */
        .student-name { font-weight: bold; font-size: 15px; color: #0d6efd; }
        .parent-info { font-size: 12px; color: #888; display: block; margin-top: 2px; }

        /* Sidebar Styling */
        .sidebar { width: var(--sidebar); background: var(--dark); color: #fff; height: 100vh; position: fixed; padding-top: 20px; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; letter-spacing: 1px; }
        .menu a { display: block; padding: 15px 25px; color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: rgba(255,255,255,0.05); color: white; border-left-color: var(--primary); }
        .menu i { width: 25px; margin-right: 10px; text-align: center;}
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> ADMIN PANEL</div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="data_sekolah.php"><i class="fas fa-school"></i> Data Sekolah</a>
            <a href="fasilitas.php"><i class="fas fa-cubes"></i> Fasilitas & Sarana</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri Foto</a>
            <a href="pendaftar.php" class="active"><i class="fas fa-users"></i> Pendaftar</a>
            <a href="data_users.php" class="active"><i class="fas fa-user-circle"></i> Data Akun Ortu</a>
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #ff6b6b; margin-top: 30px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <h2><i class="fas fa-user-graduate" style="color: #0d6efd;"></i> Data Pendaftaran Masuk</h2>

        <div class="card">
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Sekolah Tujuan</th>
                            <th>Kontak Ortu</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        // Join Tabel Pendaftaran dengan Sekolah untuk dapat nama sekolahnya
                        $q = mysqli_query($koneksi, "SELECT * FROM tb_pendaftaran JOIN tb_sekolah ON tb_pendaftaran.id_sekolah = tb_sekolah.id_sekolah ORDER BY id_daftar DESC");
                        
                        if(mysqli_num_rows($q) > 0){
                            while($d = mysqli_fetch_array($q)){
                                $status = $d['status'];
                                $badge = ($status == 'Pending') ? 'bg-pending' : (($status == 'Diterima') ? 'bg-terima' : 'bg-tolak');
                        ?>
                        <tr>
                            <td><?php echo $no++ ?></td>
                            <td>
                                <div class="student-name"><?php echo $d['nama_siswa'] ?></div>
                                <span class="parent-info"><i class="fas fa-user"></i> <?php echo $d['nama_ortu'] ?></span>
                            </td>
                            <td><?php echo $d['nama_sekolah'] ?></td>
                            <td>
                                <i class="fas fa-phone-alt" style="font-size:12px; color:#555;"></i> <?php echo $d['no_hp'] ?><br>
                                <small style="color:#888;"><?php echo $d['email'] ?></small>
                            </td>
                            <td><?php echo date('d M Y', strtotime($d['tanggal_daftar'])) ?></td>
                            <td><span class="badge <?php echo $badge ?>"><?php echo $status ?></span></td>
                            <td>
                                <?php if($status == 'Pending'){ ?>
                                    <a href="?act=terima&id=<?php echo $d['id_daftar'] ?>" class="btn-action btn-acc" title="Terima Siswa" onclick="return confirm('Terima siswa ini?')">
                                        <i class="fas fa-check"></i> Terima
                                    </a>
                                    <a href="?act=tolak&id=<?php echo $d['id_daftar'] ?>" class="btn-action btn-rej" title="Tolak Siswa" onclick="return confirm('Tolak siswa ini?')">
                                        <i class="fas fa-times"></i> Tolak
                                    </a>
                                <?php } else { 
                                    echo "<span style='color:#aaa; font-size:12px;'><i class='fas fa-check-circle'></i> Selesai</span>"; 
                                } ?>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#999; font-style:italic;'>Belum ada data pendaftaran yang masuk.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <?php
    // Logic Update Status
    if(isset($_GET['act'])){
        $id = $_GET['id'];
        $status = ($_GET['act'] == 'terima') ? 'Diterima' : 'Ditolak';
        
        $update = mysqli_query($koneksi, "UPDATE tb_pendaftaran SET status='$status' WHERE id_daftar='$id'");
        if($update){
            echo '<script>window.location="pendaftar.php"</script>';
        }
    }
    ?>

</body>
</html>