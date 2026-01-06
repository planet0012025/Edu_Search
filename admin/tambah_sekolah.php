<?php
session_start();
include '../config/koneksi.php';
if($_SESSION['status_login'] != true){ echo '<script>window.location="login.php"</script>'; }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Sekolah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        /* Sidebar CSS (Sama) */
        .sidebar { width: var(--sidebar); background: var(--dark); color: #fff; height: 100vh; position: fixed; padding-top: 20px; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .menu a { display: block; padding: 15px 25px; color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; }
        .menu a:hover, .menu a.active { background: var(--primary); color: white; }
        .menu i { width: 25px; }
        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        
        /* Form Style */
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 900px; margin-bottom: 30px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; }
        label { margin-bottom: 5px; display: block; font-weight: 600; }
        .btn-save { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; }
        .btn-back { background: #6c757d; color: white; padding: 12px 20px; border-radius: 5px; text-decoration: none; margin-right: 10px; }
        .form-section-title { font-size: 18px; color: #0d6efd; border-bottom: 1px dashed #eee; padding-bottom: 10px; margin-bottom: 20px;}
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> ADMIN PANEL</div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="data_sekolah.php" class="active"><i class="fas fa-school"></i> Data Sekolah</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri Foto</a>
            <a href="pendaftar.php"><i class="fas fa-users"></i> Pendaftar</a>
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #ff6b6b; margin-top: 30px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 style="margin-bottom: 20px;">Input Sekolah Baru</h2>
        
        <div class="card">
            <form action="" method="POST" enctype="multipart/form-data">

                <!-- INFORMASI DASAR & LOGO -->
                <div class="form-section-title"><i class="fas fa-book"></i> A. Informasi Dasar</div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div>
                        <label>Nama Sekolah</label><input type="text" name="nama" required>
                        <label>Alamat Lengkap</label><textarea name="alamat" rows="2" required></textarea>
                    </div>
                    <div>
                        <label>Foto Logo (Thumbnail)</label>
                        <input type="file" name="logo" required>
                        <label>Kurikulum</label><input type="text" name="kurikulum" placeholder="Contoh: Merdeka Belajar" required>
                    </div>
                </div>

                <!-- KLASIFIKASI -->
                <div class="form-section-title"><i class="fas fa-list-alt"></i> B. Klasifikasi & Biaya</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Jenjang</label>
                        <select name="jenjang"><option>SD</option><option>SMP</option><option>SMA</option><option>SMK</option></select>
                    </div>
                    <div>
                        <label>Status Sekolah</label>
                        <select name="status_sekolah"><option value="Negeri">Negeri</option><option value="Swasta">Swasta</option><option value="Boarding School">Boarding School</option></select>
                    </div>
                    <div>
                        <label>Akreditasi</label>
                        <select name="akreditasi"><option>A</option><option>B</option><option>C</option></select>
                    </div>
                    <div><label>Biaya Masuk (Rp)</label><input type="number" name="biaya_masuk"></div>
                    <div><label>Biaya Bulanan (Rp)</label><input type="number" name="biaya_bulanan"></div>
                </div>
                
                <!-- DATA RINCI (BARU) -->
                <div class="form-section-title"><i class="fas fa-chart-bar"></i> C. Data Rinci (Untuk Profil Lengkap)</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Total Siswa (Saat Ini)</label><input type="number" name="jml_siswa_total" placeholder="Contoh: 450">
                        <label>Jumlah Guru PNS</label><input type="number" name="guru_pns" placeholder="Contoh: 12">
                        <label>Jumlah Guru Non-PNS</label><input type="number" name="guru_non_pns" placeholder="Contoh: 25">
                    </div>
                    <div>
                        <label>Jumlah Ruang Kelas</label><input type="number" name="ruang_kelas" placeholder="Contoh: 18">
                        <label>Jumlah Lab Komputer</label><input type="number" name="lab_komputer" placeholder="Contoh: 2">
                        <label>Sanitasi Sekolah Baik?</label>
                        <select name="sanitasi_baik">
                            <option value="Ya">Ya (Baik)</option>
                            <option value="Tidak">Tidak (Perlu Perbaikan)</option>
                        </select>
                    </div>
                    <div style="grid-column: 3 / span 1;">
                        <label>Deskripsi & Keunggulan</label><textarea name="deskripsi" rows="5"></textarea>
                        <label>Program Pembangunan (Rehab/RKB)</label><textarea name="program_pembangunan" rows="3" placeholder="Sebutkan rencana pembangunan atau rehabilitasi..."></textarea>
                    </div>
                </div>

                <br>
                <a href="data_sekolah.php" class="btn-back">Batal</a>
                <button type="submit" name="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Data</button>
            </form>
        </div>
    </div>

    <?php
    if(isset($_POST['submit'])){
        // Ambil data dari form
        $fields = ['nama', 'alamat', 'jenjang', 'status_sekolah', 'akreditasi', 'biaya_masuk', 'biaya_bulanan', 'kurikulum', 'deskripsi', 'jml_siswa_total', 'guru_pns', 'guru_non_pns', 'ruang_kelas', 'lab_komputer', 'sanitasi_baik', 'program_pembangunan'];
        $values = [];
        
        foreach($fields as $field) {
            // Bersihkan input sebelum dimasukkan ke query
            $values[] = "'" . mysqli_real_escape_string($koneksi, $_POST[$field]) . "'";
        }
        
        // Proses Upload Foto
        $file = $_FILES['logo']['name'];
        $tmp = $_FILES['logo']['tmp_name'];
        $new = time().$file;
        move_uploaded_file($tmp, '../uploads/'.$new);
        $fields[] = 'foto_logo';
        $values[] = "'$new'";

        $field_list = implode(', ', $fields);
        $value_list = implode(', ', $values);
        
        $query = "INSERT INTO tb_sekolah ($field_list) VALUES ($value_list)";
        $simpan = mysqli_query($koneksi, $query);
        
        if($simpan){
            echo '<script>window.location="data_sekolah.php"</script>';
        } else {
            echo '<script>alert("Gagal menyimpan data! Error: '.mysqli_error($koneksi).'");</script>';
        }
    }
    ?>
</body>
</html>