<?php
session_start();
include '../config/koneksi.php';

// 1. Cek Login Mitra
if(!isset($_SESSION['mitra_login'])){ echo '<script>window.location="login.php"</script>'; exit; }

// 2. AMBIL ID DARI SESSION (Bukan dari URL)
$id = $_SESSION['id_sekolah'];

// 3. Ambil Data Sekolah
$query = mysqli_query($koneksi, "SELECT * FROM tb_sekolah WHERE id_sekolah='$id'");
$d = mysqli_fetch_object($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profil Sekolah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- LEAFLET JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        /* Style Dashboard Mitra (Putih Bersih) */
        :root { --primary: #0d6efd; --dark: #333; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        .sidebar { width: var(--sidebar); background: white; color: #333; height: 100vh; position: fixed; padding-top: 20px; border-right: 1px solid #eee; }
        .brand { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px; color: var(--primary); padding: 0 20px; }
        .menu a { display: block; padding: 12px 25px; color: #555; text-decoration: none; transition: 0.3s; font-size: 14px; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: #f8f9fa; color: var(--primary); border-left-color: var(--primary); }
        .menu i { width: 25px; margin-right: 5px; }

        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 1000px; }
        
        /* Form Styling (Sama seperti Admin) */
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; font-family: inherit; font-size: 14px; }
        label { margin-bottom: 5px; display: block; font-weight: 600; font-size: 14px; color: #555;}
        .form-section-title { font-size: 18px; color: #0d6efd; font-weight: 700; border-bottom: 1px dashed #ccc; padding-bottom: 10px; margin: 30px 0 20px; display: flex; align-items: center; gap: 10px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .form-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; }
        .score-box { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #eee; }
        .score-header { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; font-weight: bold; }
        .score-val { color: #0d6efd; }
        input[type=range] { width: 100%; cursor: pointer; }
        .map-preview { width: 100%; height: 250px; border: 0; border-radius: 10px; margin-bottom: 15px; border: 1px solid #ddd; background: #eee;}
        .btn-save { background: #0d6efd; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-weight: bold; transition:0.3s;}
        .btn-save:hover { background: #0b5ed7; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="fas fa-school"></i> MITRA AREA</div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profil_sekolah.php" class="active"><i class="fas fa-edit"></i> Edit Profil Sekolah</a>
            <a href="fasilitas.php"><i class="fas fa-cubes"></i> Fasilitas & Sarana</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Kelola Galeri</a>
            <a href="pendaftar.php"><i class="fas fa-user-graduate"></i> Data Pendaftar</a>
            
            <!-- MENU AKUN PENGELOLA (BARU) -->
            <a href="akun.php"><i class="fas fa-user-cog"></i> Akun Pengelola</a>
            
            <hr style="margin: 20px; border: 0; border-top: 1px solid #eee;">
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 style="margin-bottom: 20px;">Edit Profil Sekolah</h2>
        <div class="card">
            <form action="" method="POST" enctype="multipart/form-data">
                
                <!-- A. INFO DASAR & LOKASI -->
                <div class="form-section-title" style="margin-top:0;"><i class="fas fa-map-marked-alt"></i> A. Informasi Dasar & Peta Lokasi</div>
                
                <div class="form-grid-2">
                    <div>
                        <label>Nama Sekolah</label>
                        <input type="text" name="nama" id="nama_sekolah" value="<?php echo $d->nama_sekolah ?>" required oninput="updateMap()">
                        
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" id="alamat_sekolah" rows="3" required oninput="updateMap()"><?php echo $d->alamat ?></textarea>

                        <label>Kurikulum</label>
                        <input type="text" name="kurikulum" value="<?php echo $d->kurikulum ?>" required>
                    </div>

                    <div>
                        <label>Preview Lokasi (Google Maps)</label>
                        <iframe id="map-frame" class="map-preview" 
                            src="https://maps.google.com/maps?q=<?php echo urlencode($d->nama_sekolah . ' ' . $d->alamat) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                            allowfullscreen>
                        </iframe>
                        
                        <div style="background: #f1f8ff; padding: 10px; border-radius: 8px; border: 1px dashed #0d6efd;">
                            <p style="font-size:11px; color:#666; margin:0 0 5px 0;">*Isi koordinat dari Google Maps agar fitur hitung jarak berfungsi.</p>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label style="font-size:11px;">Latitude</label>
                                    <input type="text" name="latitude" value="<?php echo isset($d->latitude)?$d->latitude:'' ?>" style="margin-bottom:0; font-size:12px;">
                                </div>
                                <div>
                                    <label style="font-size:11px;">Longitude</label>
                                    <input type="text" name="longitude" value="<?php echo isset($d->longitude)?$d->longitude:'' ?>" style="margin-bottom:0; font-size:12px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- B. KLASIFIKASI -->
                <div class="form-section-title"><i class="fas fa-tags"></i> B. Klasifikasi & Biaya</div>
                <div class="form-grid-4">
                    <div>
                        <label>Jenjang</label>
                        <select name="jenjang">
                            <option <?php echo ($d->jenjang == 'SD')? 'selected':'' ?>>SD</option>
                            <option <?php echo ($d->jenjang == 'SMP')? 'selected':'' ?>>SMP</option>
                            <option <?php echo ($d->jenjang == 'SMA')? 'selected':'' ?>>SMA</option>
                            <option <?php echo ($d->jenjang == 'SMK')? 'selected':'' ?>>SMK</option>
                        </select>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status_sekolah">
                            <option value="Negeri" <?php echo ($d->status_sekolah == 'Negeri')? 'selected':'' ?>>Negeri</option>
                            <option value="Swasta" <?php echo ($d->status_sekolah == 'Swasta')? 'selected':'' ?>>Swasta</option>
                            <option value="Boarding School" <?php echo ($d->status_sekolah == 'Boarding School')? 'selected':'' ?>>Boarding School</option>
                        </select>
                    </div>
                    <div>
                        <label>Akreditasi</label>
                        <select name="akreditasi">
                            <option value="A" <?php echo ($d->akreditasi == 'A')? 'selected':'' ?>>A (Unggul)</option>
                            <option value="B" <?php echo ($d->akreditasi == 'B')? 'selected':'' ?>>B (Baik)</option>
                            <option value="C" <?php echo ($d->akreditasi == 'C')? 'selected':'' ?>>C (Cukup)</option>
                            <option value="-" <?php echo ($d->akreditasi == '-' || $d->akreditasi == '')? 'selected':'' ?>>Belum Ada</option>
                        </select>
                    </div>
                    <div>
                        <label>Biaya Masuk (Rp)</label><input type="number" name="biaya_masuk" value="<?php echo $d->biaya_masuk ?>">
                        <label>Biaya Bulanan (Rp)</label><input type="number" name="biaya_bulanan" value="<?php echo $d->biaya_bulanan ?>">
                    </div>
                </div>

                <!-- C. DATA RINCI -->
                <div class="form-section-title"><i class="fas fa-chart-bar"></i> C. Data Statistik & Fasilitas</div>
                <div class="form-grid-3">
                    <div><label>Total Siswa</label><input type="number" name="jml_siswa_total" value="<?php echo $d->jml_siswa_total ?>"></div>
                    <div><label>Guru PNS</label><input type="number" name="guru_pns" value="<?php echo $d->guru_pns ?>"></div>
                    <div><label>Guru Non-PNS</label><input type="number" name="guru_non_pns" value="<?php echo $d->guru_non_pns ?>"></div>
                    <div><label>Ruang Kelas</label><input type="number" name="ruang_kelas" value="<?php echo $d->ruang_kelas ?>"></div>
                    <div><label>Lab Komputer</label><input type="number" name="lab_komputer" value="<?php echo $d->lab_komputer ?>"></div>
                    <div>
                        <label>Sanitasi Baik?</label>
                        <select name="sanitasi_baik">
                            <option value="Ya" <?php echo ($d->sanitasi_baik == 'Ya')? 'selected':'' ?>>Ya</option>
                            <option value="Tidak" <?php echo ($d->sanitasi_baik == 'Tidak')? 'selected':'' ?>>Tidak</option>
                        </select>
                    </div>
                    <div>
                        <label>Fasilitas (List per baris)</label>
                        <textarea name="fasilitas_text" rows="5"><?php echo isset($d->fasilitas_text)?$d->fasilitas_text:'' ?></textarea>
                    </div>
                </div>

                <!-- D. INFORMASI MENDALAM -->
                <div class="form-section-title"><i class="fas fa-align-left"></i> D. Deskripsi Lengkap</div>
                <div class="form-grid-2">
                    <div>
                        <label>Profil & Keunggulan</label><textarea name="deskripsi" rows="4"><?php echo $d->deskripsi ?></textarea>
                        <label>Program Pembangunan</label><textarea name="program_pembangunan" rows="2"><?php echo $d->program_pembangunan ?></textarea>
                    </div>
                    <div>
                        <label>Ekstrakurikuler (List)</label><textarea name="ekstrakurikuler" rows="3"><?php echo isset($d->ekstrakurikuler)?$d->ekstrakurikuler:'' ?></textarea>
                        <label>Prestasi</label><textarea name="prestasi" rows="3"><?php echo isset($d->prestasi)?$d->prestasi:'' ?></textarea>
                        <label>Track Record Alumni</label><textarea name="track_record" rows="2"><?php echo isset($d->track_record)?$d->track_record:'' ?></textarea>
                    </div>
                </div>

                <!-- E. SKOR PENILAIAN -->
                <div class="form-section-title"><i class="fas fa-star-half-alt"></i> E. Skor Penilaian (1-5)</div>
                <div class="form-grid-4">
                    <?php
                    $skor_items = [
                        'kurikulum_score' => 'Kurikulum', 'guru_score' => 'Kualitas Guru', 'fasilitas_score' => 'Fasilitas', 
                        'kedisiplinan_score' => 'Disiplin', 'biaya_score' => 'Biaya', 'lokasi_score' => 'Lokasi', 
                        'keamanan_score' => 'Keamanan', 'reputasi_score' => 'Reputasi'
                    ];
                    foreach($skor_items as $key => $label){
                        $val = isset($d->$key) ? $d->$key : 3;
                    ?>
                    <div class="score-box">
                        <div class="score-header"><span><?php echo $label ?></span><span class="score-val" id="v_<?php echo $key ?>"><?php echo $val ?></span></div>
                        <input type="range" name="<?php echo $key ?>" min="1" max="5" value="<?php echo $val ?>" oninput="document.getElementById('v_<?php echo $key ?>').innerText=this.value">
                    </div>
                    <?php } ?>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                     <label>Ganti Logo Sekolah</label>
                     <input type="file" name="logo">
                     <input type="hidden" name="foto_lama" value="<?php echo $d->foto_logo ?>">
                     <small>Biarkan kosong jika tidak ingin mengganti logo.</small>
                </div>

                <br>
                <button type="submit" name="update" class="btn-save"><i class="fas fa-save"></i> Simpan Profil</button>
            </form>
        </div>
    </div>

    <script>
        function updateMap() {
            var nama = document.getElementById('nama_sekolah').value;
            var alamat = document.getElementById('alamat_sekolah').value;
            var query = encodeURIComponent(nama + ' ' + alamat);
            var mapUrl = "https://maps.google.com/maps?q=" + query + "&t=&z=15&ie=UTF8&iwloc=&output=embed";
            document.getElementById('map-frame').src = mapUrl;
        }
    </script>

    <?php
    if(isset($_POST['update'])){
        // Sanitasi
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        $lat = mysqli_real_escape_string($koneksi, $_POST['latitude']);
        $lng = mysqli_real_escape_string($koneksi, $_POST['longitude']);
        
        $jenjang = $_POST['jenjang']; $status = $_POST['status_sekolah']; $akred = $_POST['akreditasi'];
        $masuk = $_POST['biaya_masuk']; $bulan = $_POST['biaya_bulanan']; $kur = $_POST['kurikulum'];
        $desk = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
        
        // Rinci
        $fasilitas = mysqli_real_escape_string($koneksi, $_POST['fasilitas_text']);
        $ekskul = mysqli_real_escape_string($koneksi, $_POST['ekstrakurikuler']);
        $prestasi = mysqli_real_escape_string($koneksi, $_POST['prestasi']);
        $track = mysqli_real_escape_string($koneksi, $_POST['track_record']);
        $pembangun = mysqli_real_escape_string($koneksi, $_POST['program_pembangunan']);
        
        $jml = $_POST['jml_siswa_total']; $pns = $_POST['guru_pns']; $non = $_POST['guru_non_pns'];
        $ruang = $_POST['ruang_kelas']; $lab = $_POST['lab_komputer']; $sani = $_POST['sanitasi_baik'];
        
        // Skor
        $skor_query = "";
        foreach($skor_items as $key => $label){
            $val = $_POST[$key];
            $skor_query .= "$key = '$val', ";
        }

        // Foto
        $foto_db = $_POST['foto_lama'];
        if($_FILES['logo']['name'] != ""){
            $new = time().$_FILES['logo']['name'];
            move_uploaded_file($_FILES['logo']['tmp_name'], '../uploads/'.$new);
            $foto_db = $new;
        }

        $sql = "UPDATE tb_sekolah SET 
            nama_sekolah='$nama', alamat='$alamat', latitude='$lat', longitude='$lng',
            jenjang='$jenjang', status_sekolah='$status', akreditasi='$akred',
            biaya_masuk='$masuk', biaya_bulanan='$bulan', kurikulum='$kur', deskripsi='$desk', 
            fasilitas_text='$fasilitas', ekstrakurikuler='$ekskul', prestasi='$prestasi', track_record='$track', program_pembangunan='$pembangun',
            jml_siswa_total='$jml', guru_pns='$pns', guru_non_pns='$non', ruang_kelas='$ruang', lab_komputer='$lab', sanitasi_baik='$sani',
            $skor_query
            foto_logo='$foto_db'
            WHERE id_sekolah='$id'";

        if(mysqli_query($koneksi, $sql)){
            echo '<script>alert("Profil Sekolah Berhasil Diupdate!"); window.location="profil_sekolah.php"</script>';
        } else {
            echo '<script>alert("Gagal Update: '.mysqli_error($koneksi).'");</script>';
        }
    }
    ?>
</body>
</html>