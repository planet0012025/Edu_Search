<?php
session_start();
include '../config/koneksi.php';

// Cek Login
if(!isset($_SESSION['status_login']) || $_SESSION['status_login'] != true){ 
    echo '<script>window.location="login.php"</script>'; 
    exit; 
}

// --- LOGIKA FILTER ---
$where = "WHERE 1=1";
$url_param = "";

// 1. Filter Sekolah
if(isset($_GET['id_sekolah']) && $_GET['id_sekolah'] != ''){
    $id_sek = mysqli_real_escape_string($koneksi, $_GET['id_sekolah']);
    $where .= " AND g.id_sekolah = '$id_sek'";
    $url_param .= "&id_sekolah=$id_sek";
}

// 2. Filter Kategori (BARU)
if(isset($_GET['kategori']) && $_GET['kategori'] != ''){
    $kat = mysqli_real_escape_string($koneksi, $_GET['kategori']);
    $where .= " AND g.kategori = '$kat'";
    $url_param .= "&kategori=$kat";
}

// --- PAGINATION ---
$batas = 8;
$halaman = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$q_count = mysqli_query($koneksi, "SELECT g.id_galeri FROM tb_galeri g $where");
$jumlah_data = mysqli_num_rows($q_count);
$total_halaman = ceil($jumlah_data / $batas);

// Query Data Utama
$query = mysqli_query($koneksi, "SELECT g.*, s.nama_sekolah 
                                 FROM tb_galeri g 
                                 JOIN tb_sekolah s ON g.id_sekolah = s.id_sekolah 
                                 $where 
                                 ORDER BY g.id_galeri DESC 
                                 LIMIT $halaman_awal, $batas");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Galeri - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        .sidebar { width: var(--sidebar); background: var(--dark); color: #fff; height: 100vh; position: fixed; padding-top: 20px; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .menu a { display: block; padding: 15px 25px; color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; }
        .menu a:hover, .menu a.active { background: rgba(255,255,255,0.05); color: white; border-left: 4px solid var(--primary); }
        .menu i { width: 25px; margin-right: 10px; text-align: center; }

        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        h2 { margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px; }

        /* Form Upload Style */
        .upload-box { background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px dashed #ddd; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; outline: none; }
        .btn-upload { background: #198754; color: white; border: none; padding: 10px 25px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-upload:hover { background: #157347; }

        /* Filter Box */
        .filter-box { display: flex; gap: 10px; background: #e7f1ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;}
        .filter-box select { width: auto; flex-grow: 1; }

        /* Grid Galeri */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .gallery-item { border: 1px solid #eee; border-radius: 10px; overflow: hidden; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: relative; transition: 0.3s; }
        .gallery-item:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .gallery-img { width: 100%; height: 150px; object-fit: cover; display: block; }
        .gallery-info { padding: 15px; }
        .school-name { font-size: 12px; color: #888; display: block; margin-bottom: 5px; }
        .img-title { font-weight: bold; font-size: 14px; color: #333; display: block; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .badge-cat { 
            display: inline-block; padding: 3px 8px; border-radius: 4px; 
            font-size: 10px; font-weight: bold; text-transform: uppercase;
            background: #e7f1ff; color: #0d6efd; margin-bottom: 10px;
        }

        .btn-del-sm { 
            display: block; text-align: center; background: #fff5f5; color: #dc3545; 
            padding: 5px; border-radius: 5px; font-size: 12px; text-decoration: none; font-weight: 600; 
        }
        .btn-del-sm:hover { background: #dc3545; color: white; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 30px; }
        .page-link { padding: 8px 14px; border: 1px solid #ddd; color: #333; text-decoration: none; border-radius: 5px; font-size: 13px; }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> ADMIN PANEL</div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="data_sekolah.php"><i class="fas fa-school"></i> Data Sekolah</a>
            <a href="galeri.php" class="active"><i class="fas fa-images"></i> Galeri Foto</a>
            <a href="pendaftar.php"><i class="fas fa-users"></i> Pendaftar</a>
            <a href="data_users.php"><i class="fas fa-user-circle"></i> Data Akun Ortu</a>
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 25px;">
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <h2><i class="fas fa-images" style="color: var(--primary);"></i> Kelola Galeri Foto</h2>

        <!-- FORM UPLOAD BARU -->
        <div class="upload-box">
            <h4 style="margin-top:0; margin-bottom:15px; color:#333;"><i class="fas fa-cloud-upload-alt"></i> Upload Foto Baru</h4>
            <form action="" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                
                <div>
                    <label>Pilih Sekolah</label>
                    <select name="id_sekolah" required>
                        <option value="">-- Pilih Sekolah --</option>
                        <?php
                        $s_query = mysqli_query($koneksi, "SELECT id_sekolah, nama_sekolah FROM tb_sekolah ORDER BY nama_sekolah ASC");
                        while($s = mysqli_fetch_array($s_query)){
                            // Auto select jika filter sedang aktif
                            $sel = (isset($_GET['id_sekolah']) && $_GET['id_sekolah'] == $s['id_sekolah']) ? 'selected' : '';
                            echo "<option value='$s[id_sekolah]' $sel>$s[nama_sekolah]</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- INPUT KATEGORI BARU -->
                <div>
                    <label>Kategori Foto</label>
                    <select name="kategori" required>
                        <option value="Lainnya">-- Pilih Kategori --</option>
                        <option value="Aktifitas Siswa">Aktifitas Siswa</option>
                        <option value="Guru">Guru & Staff</option>
                        <option value="Alumni">Alumni</option>
                        <option value="Fasilitas">Fasilitas</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label>Judul/Keterangan</label>
                    <input type="text" name="judul" placeholder="Contoh: Lomba Basket 2024" required>
                </div>

                <div>
                    <label>File Foto</label>
                    <input type="file" name="foto" required style="padding: 7px;">
                </div>

                <button type="submit" name="upload" class="btn-upload">Upload</button>
            </form>
        </div>

        <!-- FILTER DATA -->
        <form method="GET" class="filter-box">
            <b style="color: #0d6efd;"><i class="fas fa-filter"></i> Filter:</b>
            
            <select name="id_sekolah" onchange="this.form.submit()">
                <option value="">-- Semua Sekolah --</option>
                <?php
                // Reset pointer query sekolah
                mysqli_data_seek($s_query, 0);
                while($s = mysqli_fetch_array($s_query)){
                    $sel = (isset($_GET['id_sekolah']) && $_GET['id_sekolah'] == $s['id_sekolah']) ? 'selected' : '';
                    echo "<option value='$s[id_sekolah]' $sel>$s[nama_sekolah]</option>";
                }
                ?>
            </select>

            <!-- FILTER KATEGORI -->
            <select name="kategori" onchange="this.form.submit()">
                <option value="">-- Semua Kategori --</option>
                <?php 
                $cats = ['Aktifitas Siswa', 'Guru', 'Alumni', 'Fasilitas', 'Lainnya'];
                foreach($cats as $c){
                    $sel = (isset($_GET['kategori']) && $_GET['kategori'] == $c) ? 'selected' : '';
                    echo "<option value='$c' $sel>$c</option>";
                }
                ?>
            </select>

            <a href="galeri.php" style="color:#666; text-decoration:none; font-size:13px; font-weight:bold;">Reset</a>
        </form>

        <!-- GRID GALERI -->
        <div class="gallery-grid">
            <?php
            if(mysqli_num_rows($query) > 0){
                while($d = mysqli_fetch_array($query)){
            ?>
            <div class="gallery-item">
                <img src="../uploads/<?php echo $d['file_foto'] ?>" class="gallery-img" onerror="this.src='../assets/img/no-image.jpg'">
                <div class="gallery-info">
                    <span class="badge-cat"><?php echo isset($d['kategori']) ? $d['kategori'] : 'Lainnya' ?></span>
                    <span class="img-title" title="<?php echo $d['judul_foto'] ?>"><?php echo $d['judul_foto'] ?></span>
                    <span class="school-name"><i class="fas fa-school"></i> <?php echo substr($d['nama_sekolah'], 0, 20) ?>...</span>
                    
                    <a href="?hapus=<?php echo $d['id_galeri'] ?>" class="btn-del-sm" onclick="return confirm('Yakin hapus foto ini?')">
                        <i class="fas fa-trash"></i> Hapus Foto
                    </a>
                </div>
            </div>
            <?php 
                }
            } else {
                echo "<p style='grid-column:1/-1; text-align:center; color:#999; padding:40px;'>Tidak ada foto yang ditemukan sesuai filter.</p>";
            }
            ?>
        </div>

        <!-- PAGINATION -->
        <?php if($total_halaman > 1){ ?>
        <div class="pagination">
            <?php for($i=1; $i<=$total_halaman; $i++){ ?>
                <a href="?hal=<?php echo $i.$url_param ?>" class="page-link <?php if($i==$halaman) echo 'active'; ?>"><?php echo $i ?></a>
            <?php } ?>
        </div>
        <?php } ?>

    </div>

    <?php
    // PROSES UPLOAD
    if(isset($_POST['upload'])){
        $id_sek = $_POST['id_sekolah'];
        $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
        $kat = $_POST['kategori'];

        $file = $_FILES['foto']['name'];
        $tmp = $_FILES['foto']['tmp_name'];
        $new = 'galeri_'.time().$file; // Rename unik

        // Validasi Ekstensi
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])){
            if(move_uploaded_file($tmp, '../uploads/'.$new)){
                // Simpan ke DB
                // Pastikan kolom 'kategori' sudah dibuat di langkah SQL sebelumnya!
                $sql = "INSERT INTO tb_galeri (id_sekolah, judul_foto, kategori, file_foto) VALUES ('$id_sek', '$judul', '$kat', '$new')";
                mysqli_query($koneksi, $sql);
                echo "<script>alert('Berhasil upload!'); window.location='galeri.php';</script>";
            } else {
                echo "<script>alert('Gagal upload file!');</script>";
            }
        } else {
            echo "<script>alert('Format file harus JPG/PNG!');</script>";
        }
    }

    // PROSES HAPUS
    if(isset($_GET['hapus'])){
        $id = $_GET['hapus'];
        $cek = mysqli_query($koneksi, "SELECT file_foto FROM tb_galeri WHERE id_galeri='$id'");
        if(mysqli_num_rows($cek) > 0){
            $f = mysqli_fetch_object($cek);
            if(file_exists('../uploads/'.$f->file_foto)) unlink('../uploads/'.$f->file_foto);
            
            mysqli_query($koneksi, "DELETE FROM tb_galeri WHERE id_galeri='$id'");
            echo "<script>alert('Foto dihapus!'); window.location='galeri.php';</script>";
        }
    }
    ?>

</body>
</html>