<?php
session_start();
include '../config/koneksi.php';

// 1. Cek Login Mitra
if(!isset($_SESSION['mitra_login'])){ echo '<script>window.location="login.php"</script>'; exit; }

$id_sekolah = $_SESSION['id_sekolah'];
$edit_mode = false;
$data_edit = null;

// --- CEK MODE EDIT ---
if(isset($_GET['edit'])){
    $id_edit = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $q_edit = mysqli_query($koneksi, "SELECT * FROM tb_galeri WHERE id_galeri='$id_edit' AND id_sekolah='$id_sekolah'");
    if(mysqli_num_rows($q_edit) > 0){
        $edit_mode = true;
        $data_edit = mysqli_fetch_object($q_edit);
    }
}

// --- LOGIKA TAMBAH FOTO (CREATE) ---
if(isset($_POST['tambah_foto'])){
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']); // Baru
    
    $filename = $_FILES['foto']['name'];
    $tmp_name = $_FILES['foto']['tmp_name'];
    $newname = 'galeri_' . time() . '_' . $filename;
    
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if(!in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])){
        echo '<script>alert("Format file harus JPG atau PNG!");</script>';
    } else {
        if(move_uploaded_file($tmp_name, '../uploads/'.$newname)){
            // Kolom tgl_upload otomatis (CURRENT_TIMESTAMP)
            $insert = mysqli_query($koneksi, "INSERT INTO tb_galeri 
                (id_sekolah, judul_foto, deskripsi, file_foto) 
                VALUES 
                ('$id_sekolah', '$judul', '$deskripsi', '$newname')");
                
            if($insert){
                echo '<script>alert("Foto berhasil diunggah!"); window.location="galeri.php"</script>';
            } else {
                echo '<script>alert("Gagal menyimpan ke database.");</script>';
            }
        }
    }
}

// --- LOGIKA UPDATE FOTO (EDIT) ---
if(isset($_POST['update_foto'])){
    $id_galeri = $_POST['id_galeri'];
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']); // Baru
    $foto_lama = $_POST['foto_lama'];
    
    $foto_db = $foto_lama;
    
    // Cek jika ada foto baru
    if($_FILES['foto']['name'] != ""){
        $filename = $_FILES['foto']['name'];
        $tmp_name = $_FILES['foto']['tmp_name'];
        $newname = 'galeri_' . time() . '_' . $filename;
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])){
            if(move_uploaded_file($tmp_name, '../uploads/'.$newname)){
                if(file_exists('../uploads/'.$foto_lama)) unlink('../uploads/'.$foto_lama);
                $foto_db = $newname;
            }
        } else {
            echo '<script>alert("Format file baru salah!"); window.location="galeri.php"</script>';
            exit;
        }
    }
    
    $update = mysqli_query($koneksi, "UPDATE tb_galeri SET 
        judul_foto='$judul', 
        deskripsi='$deskripsi', 
        file_foto='$foto_db' 
        WHERE id_galeri='$id_galeri' AND id_sekolah='$id_sekolah'");
    
    if($update){
        echo '<script>alert("Galeri berhasil diperbarui!"); window.location="galeri.php"</script>';
    } else {
        echo '<script>alert("Gagal update data.");</script>';
    }
}

// --- LOGIKA HAPUS ---
if(isset($_GET['delete'])){
    $id_galeri = $_GET['delete'];
    $q_del = mysqli_query($koneksi, "SELECT * FROM tb_galeri WHERE id_galeri='$id_galeri' AND id_sekolah='$id_sekolah'");
    $d_del = mysqli_fetch_object($q_del);
    
    if($d_del){
        if(file_exists('../uploads/'.$d_del->file_foto) && $d_del->file_foto != '') unlink('../uploads/'.$d_del->file_foto);
        mysqli_query($koneksi, "DELETE FROM tb_galeri WHERE id_galeri='$id_galeri'");
        echo '<script>alert("Foto berhasil dihapus."); window.location="galeri.php"</script>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Galeri Sekolah</title>
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
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        /* Form & Input */
        input[type="text"], input[type="file"], textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; font-family: inherit; font-size: 14px; }
        textarea { resize: vertical; height: 80px; }
        label { margin-bottom: 5px; display: block; font-weight: 600; font-size: 14px; color: #555;}
        
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-warning { background: #ffc107; color: #333; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-cancel { background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; margin-left: 10px; }
        
        /* Grid Galeri */
        .galeri-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .foto-item { border: 1px solid #eee; border-radius: 10px; overflow: hidden; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .foto-item:hover { transform: translateY(-5px); }
        .foto-item img { width: 100%; height: 180px; object-fit: cover; display: block; border-bottom: 1px solid #eee; }
        
        .foto-caption { padding: 15px; }
        .foto-caption h4 { margin: 0 0 5px; font-size: 15px; font-weight: 700; color: #333; }
        .foto-caption p { font-size: 13px; color: #666; margin: 0 0 10px; line-height: 1.4; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .meta-date { font-size: 11px; color: #999; display: block; margin-bottom: 10px; }
        
        .action-area { border-top: 1px dashed #eee; padding-top: 10px; display: flex; justify-content: space-between; }
        .btn-sm { font-size: 12px; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .btn-edit-sm { background: #fff3cd; color: #856404; }
        .btn-del-sm { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fas fa-school"></i> MITRA AREA</div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profil_sekolah.php"><i class="fas fa-edit"></i> Edit Profil Sekolah</a>
            <a href="galeri.php" class="active"><i class="fas fa-images"></i> Kelola Galeri</a>
            <a href="pendaftar.php"><i class="fas fa-user-graduate"></i> Data Pendaftar</a>
            <hr style="margin: 20px; border: 0; border-top: 1px solid #eee;">
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 style="margin-bottom: 20px;">Kelola Galeri Sekolah</h2>
        
        <!-- CARD FORM (DINAMIS) -->
        <div class="card" id="form-area">
            <h3 style="margin-top:0; font-size:16px; color:var(--primary);">
                <?php if($edit_mode): ?> <i class="fas fa-edit"></i> Edit Foto Galeri <?php else: ?> <i class="fas fa-plus-circle"></i> Tambah Foto Baru <?php endif; ?>
            </h3>

            <form action="" method="POST" enctype="multipart/form-data">
                <label>Judul Foto</label>
                <input type="text" name="judul" required placeholder="Contoh: Kegiatan Pameran Seni" 
                       value="<?php echo ($edit_mode) ? $data_edit->judul_foto : '' ?>">

                <!-- Input Deskripsi Baru -->
                <label>Deskripsi Singkat</label>
                <textarea name="deskripsi" placeholder="Jelaskan sedikit tentang foto ini..."><?php echo ($edit_mode && isset($data_edit->deskripsi)) ? $data_edit->deskripsi : '' ?></textarea>
                
                <label>
                    <?php if($edit_mode): ?> Ganti File Foto (Opsional) <?php else: ?> Pilih File Foto (Max 2MB) <?php endif; ?>
                </label>
                
                <?php if($edit_mode): ?>
                    <div style="margin-bottom:10px;">
                        <img src="../uploads/<?php echo $data_edit->file_foto ?>" width="80" style="border-radius:5px; border:1px solid #ddd;">
                        <input type="hidden" name="foto_lama" value="<?php echo $data_edit->file_foto ?>">
                        <input type="hidden" name="id_galeri" value="<?php echo $data_edit->id_galeri ?>">
                    </div>
                <?php endif; ?>
                
                <input type="file" name="foto" <?php echo ($edit_mode) ? '' : 'required' ?>>
                
                <?php if($edit_mode): ?>
                    <button type="submit" name="update_foto" class="btn-warning"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    <a href="galeri.php" class="btn-cancel">Batal</a>
                <?php else: ?>
                    <button type="submit" name="tambah_foto" class="btn-primary"><i class="fas fa-upload"></i> Unggah Foto</button>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- CARD DAFTAR FOTO -->
        <div class="card">
            <h3 style="margin-top:0; font-size:16px; color:#333; border-bottom:1px solid #eee; padding-bottom:10px;">
                <i class="fas fa-images"></i> Daftar Foto Galeri Anda
            </h3>
            
            <?php 
            // Cek apakah kolom tgl_upload ada, jika tidak pakai ID untuk sorting
            $order_by = "id_galeri DESC"; 
            $check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM tb_galeri LIKE 'tgl_upload'");
            if(mysqli_num_rows($check_col) > 0) { $order_by = "tgl_upload DESC"; }

            $q_galeri = mysqli_query($koneksi, "SELECT * FROM tb_galeri WHERE id_sekolah='$id_sekolah' ORDER BY $order_by");
            
            if(mysqli_num_rows($q_galeri) > 0){
            ?>
            <div class="galeri-grid">
                <?php while($g = mysqli_fetch_object($q_galeri)){ ?>
                <div class="foto-item">
                    <img src="../uploads/<?php echo $g->file_foto ?>" onerror="this.src='../assets/img/no-image.jpg'">
                    <div class="foto-caption">
                        <h4><?php echo $g->judul_foto ?></h4>
                        
                        <!-- Tampilkan Deskripsi -->
                        <p><?php echo (isset($g->deskripsi) && !empty($g->deskripsi)) ? substr($g->deskripsi, 0, 60).'...' : 'Tidak ada deskripsi.' ?></p>
                        
                        <span class="meta-date">
                            <i class="far fa-calendar"></i> 
                            <?php echo (isset($g->tgl_upload)) ? date('d M Y', strtotime($g->tgl_upload)) : '-' ?>
                        </span>
                        
                        <div class="action-area">
                            <a href="?edit=<?php echo $g->id_galeri ?>#form-area" class="btn-sm btn-edit-sm"><i class="fas fa-pencil-alt"></i> Edit</a>
                            <a href="?delete=<?php echo $g->id_galeri ?>" class="btn-sm btn-del-sm" onclick="return confirm('Hapus foto ini?')"><i class="fas fa-trash"></i> Hapus</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } else { ?>
                <div style="text-align:center; padding:40px; color:#999;">Belum ada foto. Silakan unggah foto kegiatan sekolah Anda.</div>
            <?php } ?>
        </div>
    </div>
</body>
</html>