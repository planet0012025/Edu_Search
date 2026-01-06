<?php
include 'config/koneksi.php';
$id_sekolah = $_GET['id'];

// Ambil nama sekolah buat judul
$q = mysqli_query($koneksi, "SELECT nama_sekolah FROM tb_sekolah WHERE id_sekolah = '$id_sekolah'");
$d = mysqli_fetch_object($q);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar - <?php echo $d->nama_sekolah ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    <header>
        <div class="container header-content">
            <a href="index.php" class="logo">EduSearch</a>
        </div>
    </header>

    <div class="container" style="max-width: 600px; margin-top: 40px;">
        <div class="content-box">
            <h2 style="text-align:center; margin-bottom: 20px;">Formulir Pendaftaran Online</h2>
            <h4 style="text-align:center; color:#28a745; margin-bottom: 30px;"><?php echo $d->nama_sekolah ?></h4>

            <form action="" method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Nama Calon Siswa</label>
                    <input type="text" name="nama_siswa" style="width:100%; padding:10px; border:1px solid #ccc;" required>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Nama Orang Tua / Wali</label>
                    <input type="text" name="nama_ortu" style="width:100%; padding:10px; border:1px solid #ccc;" required>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Nomor HP (WhatsApp)</label>
                    <input type="number" name="hp" style="width:100%; padding:10px; border:1px solid #ccc;" required>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Alamat Email</label>
                    <input type="email" name="email" style="width:100%; padding:10px; border:1px solid #ccc;">
                </div>

                <button type="submit" name="daftar" class="btn-daftar-big" style="width:100%; border:none; cursor:pointer;">Kirim Pendaftaran</button>
            </form>

            <?php
            if(isset($_POST['daftar'])){
                $siswa = $_POST['nama_siswa'];
                $ortu  = $_POST['nama_ortu'];
                $hp    = $_POST['hp'];
                $email = $_POST['email'];

                $insert = mysqli_query($koneksi, "INSERT INTO tb_pendaftaran VALUES (
                    null, '$id_sekolah', '$siswa', '$ortu', '$hp', '$email', CURRENT_TIMESTAMP, 'Pending'
                )");

                if($insert){
                    echo '<script>alert("Pendaftaran Berhasil! Pihak sekolah akan menghubungi Anda."); window.location="index.php";</script>';
                } else {
                    echo 'Gagal: '.mysqli_error($koneksi);
                }
            }
            ?>
        </div>
    </div>

</body>
</html>