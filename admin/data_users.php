<?php
session_start();
include '../config/koneksi.php';
if(!isset($_SESSION['status_login']) || $_SESSION['status_login'] != true){ 
    echo '<script>window.location="login.php"</script>'; 
    exit; 
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Akun Orang Tua - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS DASHBOARD STANDARD --- */
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        /* Sidebar */
        .sidebar { width: var(--sidebar); background: var(--dark); color: #fff; height: 100vh; position: fixed; padding-top: 20px; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .menu a { display: block; padding: 15px 25px; color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: rgba(255,255,255,0.05); color: white; border-left-color: var(--primary); }
        .menu i { width: 25px; margin-right: 10px; text-align: center; }

        /* Main Content */
        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        h2 { margin-bottom: 20px; color: #333; font-size: 24px; display: flex; align-items: center; gap: 10px; }

        /* Table Styling */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #ddd; color: #555; font-weight: 600; white-space: nowrap; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; color: #444; }
        tr:hover { background-color: #fdfdfd; }
        
        /* Badges */
        .badge-loc { padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; }
        .bg-success { background: #d1e7dd; color: #0f5132; }
        .bg-danger { background: #f8d7da; color: #842029; }
        
        /* Button */
        .btn-del { 
            background: #dc3545; color: white; padding: 6px 12px; border-radius: 5px; 
            text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; 
            transition: 0.3s; border: none; cursor: pointer;
        }
        .btn-del:hover { background: #b02a37; }

        .info-user { display: flex; flex-direction: column; }
        .info-user strong { color: var(--primary); font-size: 15px; }
        .info-user small { color: #888; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> ADMIN PANEL</div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="data_sekolah.php"><i class="fas fa-school"></i> Data Sekolah</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri Foto</a>
            <a href="pendaftar.php"><i class="fas fa-user-graduate"></i> Data Pendaftar</a>
            <a href="data_users.php" class="active"><i class="fas fa-users"></i> Data Akun Ortu</a>
            
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 25px;">
            
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <h2><i class="fas fa-users" style="color: var(--primary);"></i> Data Akun Orang Tua</h2>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Profil Orang Tua</th>
                            <th>Kontak & Pekerjaan</th>
                            <th>Alamat Domisili</th>
                            <th>Status Peta</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $q = mysqli_query($koneksi, "SELECT * FROM tb_orang_tua ORDER BY id_ortu DESC");
                        
                        if(mysqli_num_rows($q) > 0){
                            while($d = mysqli_fetch_array($q)){
                                // Cek Status Maps
                                $has_map = (!empty($d['latitude']) && !empty($d['longitude']) && $d['latitude'] != 0);
                                $status_loc = $has_map 
                                    ? '<span class="badge-loc bg-success"><i class="fas fa-check-circle"></i> Sudah Set</span>' 
                                    : '<span class="badge-loc bg-danger"><i class="fas fa-times-circle"></i> Belum Set</span>';
                        ?>
                        <tr>
                            <td><?php echo $no++ ?></td>
                            <td>
                                <div class="info-user">
                                    <strong><?php echo $d['nama_lengkap'] ?></strong>
                                    <small>Lahir: <?php echo $d['tempat_lahir'] ?>, <?php echo $d['tanggal_lahir'] ?></small>
                                    <small>Pendidikan: <?php echo $d['pendidikan_terakhir'] ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="info-user">
                                    <span><i class="fas fa-envelope" style="color:#888; width:15px;"></i> <?php echo $d['email'] ?></span>
                                    <span><i class="fas fa-phone" style="color:#888; width:15px;"></i> <?php echo $d['no_hp'] ?></span>
                                    <span><i class="fas fa-briefcase" style="color:#888; width:15px;"></i> <?php echo $d['pekerjaan'] ?></span>
                                </div>
                            </td>
                            <td style="max-width: 250px;">
                                <small style="line-height:1.4; display:block; color:#555;">
                                    <?php echo substr($d['alamat_lengkap'], 0, 80) . (strlen($d['alamat_lengkap']) > 80 ? '...' : '') ?>
                                </small>
                            </td>
                            <td><?php echo $status_loc ?></td>
                            <td>
                                <a href="?hapus=<?php echo $d['id_ortu'] ?>" class="btn-del" onclick="return confirm('Hapus akun ini permanen? Data pendaftaran terkait mungkin akan error.')">
                                    <i class="fas fa-trash-alt"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:30px; color:#999; font-style:italic;'>Belum ada user yang mendaftar.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <?php
    // Logic Hapus User
    if(isset($_GET['hapus'])){
        $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
        $del = mysqli_query($koneksi, "DELETE FROM tb_orang_tua WHERE id_ortu='$id'");
        
        if($del){ 
            echo '<script>alert("Data berhasil dihapus"); window.location="data_users.php"</script>'; 
        } else {
            echo '<script>alert("Gagal menghapus data.");</script>';
        }
    }
    ?>

</body>
</html>