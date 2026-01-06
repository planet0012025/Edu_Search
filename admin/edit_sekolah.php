<?php
session_start();
include '../config/koneksi.php';

// 1. Cek Login
if(!isset($_SESSION['status_login']) || $_SESSION['status_login'] != true){ 
    echo '<script>window.location="login.php"</script>'; 
    exit; 
}

// 2. PENGAMANAN: Cek ID
if(!isset($_GET['id']) || empty($_GET['id'])){
    echo '<script>alert("Pilih data sekolah yang akan diedit terlebih dahulu!"); window.location="data_sekolah.php"</script>';
    exit; 
}

$id = mysqli_real_escape_string($koneksi, $_GET['id']);

// --- LOGIKA APPROVAL (VERIFIKASI) VIA HALAMAN INI ---
if(isset($_GET['aksi']) && isset($_GET['id_mitra'])){
    $id_m = mysqli_real_escape_string($koneksi, $_GET['id_mitra']);
    $aksi = $_GET['aksi'];
    
    $status_baru = '';
    if($aksi == 'approve') $status_baru = 'Aktif';
    if($aksi == 'reject') $status_baru = 'Nonaktif';
    
    if($status_baru != ''){
        $update_mitra = mysqli_query($koneksi, "UPDATE tb_mitra SET status_akun='$status_baru' WHERE id_mitra='$id_m'");
        if($update_mitra){
            echo "<script>alert('Status mitra berhasil diubah menjadi: $status_baru'); window.location='edit_sekolah.php?id=$id'</script>";
        }
    }
}

// 3. AMBIL DATA (JOIN TABEL MITRA)
// Kita perlu join ke tb_mitra untuk tahu status akunnya
$query = mysqli_query($koneksi, "
    SELECT s.*, m.id_mitra, m.status_akun, m.username, m.nama_lengkap as nama_pic 
    FROM tb_sekolah s 
    LEFT JOIN tb_mitra m ON s.id_sekolah = m.id_sekolah 
    WHERE s.id_sekolah='$id'
");

if(mysqli_num_rows($query) == 0){
    echo '<script>alert("Data sekolah tidak ditemukan!"); window.location="data_sekolah.php"</script>';
    exit;
}

$d = mysqli_fetch_object($query);

// Menyiapkan Label Status
$status_badge = "Tidak Ada Akun Mitra";
$badge_color = "#6c757d"; // Abu-abu

if(isset($d->status_akun)){
    if($d->status_akun == 'Pending'){
        $status_badge = "MENUNGGU VERIFIKASI";
        $badge_color = "#ffc107"; // Kuning
    } elseif($d->status_akun == 'Aktif'){
        $status_badge = "MITRA AKTIF";
        $badge_color = "#198754"; // Hijau
    } elseif($d->status_akun == 'Nonaktif'){
        $status_badge = "NONAKTIF / DITOLAK";
        $badge_color = "#dc3545"; // Merah
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit & Verifikasi Sekolah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- LEAFLET JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        .sidebar { width: var(--sidebar); background: var(--dark); color: #fff; height: 100vh; position: fixed; padding-top: 20px; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .menu a { display: block; padding: 15px 25px; color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; }
        .menu a:hover, .menu a.active { background: var(--primary); color: white; }
        .menu i { width: 25px; }
        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 1100px; margin-bottom: 50px; }
        
        /* Form Styling */
        input[type="text"], input[type="number"], input[type="email"], select, textarea { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px; font-family: inherit; font-size: 14px;
        }
        textarea { resize: vertical; }
        label { margin-bottom: 8px; display: block; font-weight: 600; font-size: 14px; color: #444;}
        
        .form-section-title { 
            font-size: 18px; color: #0d6efd; font-weight: 700; 
            border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin: 40px 0 25px; 
            display: flex; align-items: center; gap: 10px;
        }
        
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .form-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; }

        .score-box { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #eee; }
        .score-header { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; font-weight: bold; }
        .score-val { color: #0d6efd; }
        input[type=range] { width: 100%; cursor: pointer; }

        /* Map Preview Style */
        .map-preview { width: 100%; height: 250px; border: 0; border-radius: 10px; margin-bottom: 15px; border: 1px solid #ddd; background: #eee;}

        .btn-save { background: #0d6efd; color: white; border: none; padding: 15px 40px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px; transition:0.3s; display: block; width: 100%; }
        .btn-save:hover { background: #0b5ed7; }

        /* PANEL VERIFIKASI */
        .verification-panel {
            background: #fff3cd; border: 1px solid #ffeeba; border-left: 5px solid #ffc107;
            padding: 20px; border-radius: 8px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .v-info h3 { margin: 0 0 5px 0; color: #856404; }
        .v-info p { margin: 0; font-size: 14px; color: #856404; }
        .v-actions { display: flex; gap: 10px; }
        .btn-v { padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 14px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-approve { background: #198754; color: white; } .btn-approve:hover { background: #157347; }
        .btn-reject { background: #dc3545; color: white; } .btn-reject:hover { background: #bb2d3b; }
        .btn-doc { background: #0dcaf0; color: white; } .btn-doc:hover { background: #0aa2c0; }

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
            <a href="data_users.php"><i class="fas fa-user-circle"></i> Data Akun Ortu</a>
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #ff6b6b; margin-top: 30px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 style="margin-bottom: 20px;">Edit & Verifikasi Sekolah</h2>

        <!-- PANEL VERIFIKASI MITRA (BARU) -->
        <div class="verification-panel" style="border-left-color: <?php echo $badge_color ?>; background: <?php echo $badge_color ?>15;">
            <div class="v-info">
                <h3 style="color:<?php echo $badge_color ?>"><?php echo $status_badge ?></h3>
                <p>
                    <?php if(isset($d->nama_pic)): ?>
                        Pengelola: <b><?php echo $d->nama_pic ?></b> (<?php echo $d->username ?>)
                    <?php else: ?>
                        Sekolah ini belum memiliki akun mitra pengelola.
                    <?php endif; ?>
                </p>
            </div>
            <div class="v-actions">
                <!-- Tombol Lihat SK -->
                <?php if(!empty($d->file_sk) && file_exists('../uploads/'.$d->file_sk)): ?>
                    <a href="../uploads/<?php echo $d->file_sk ?>" target="_blank" class="btn-v btn-doc"><i class="fas fa-file-pdf"></i> Cek SK</a>
                <?php endif; ?>

                <!-- Tombol Approve / Reject -->
                <?php if(isset($d->id_mitra)): ?>
                    <?php if($d->status_akun == 'Pending' || $d->status_akun == 'Nonaktif'): ?>
                        <a href="?id=<?php echo $id ?>&aksi=approve&id_mitra=<?php echo $d->id_mitra ?>" class="btn-v btn-approve" onclick="return confirm('Aktifkan akun mitra ini?')"><i class="fas fa-check"></i> Terima</a>
                    <?php endif; ?>
                    
                    <?php if($d->status_akun == 'Pending' || $d->status_akun == 'Aktif'): ?>
                        <a href="?id=<?php echo $id ?>&aksi=reject&id_mitra=<?php echo $d->id_mitra ?>" class="btn-v btn-reject" onclick="return confirm('Nonaktifkan akun mitra ini?')"><i class="fas fa-times"></i> Tolak</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <form action="" method="POST" enctype="multipart/form-data">
                
                <!-- A. INFORMASI DASAR & WILAYAH -->
                <div class="form-section-title" style="margin-top:0;"><i class="fas fa-info-circle"></i> A. Informasi Dasar</div>
                <div class="form-grid-2">
                    <div>
                        <label>Nama Sekolah</label>
                        <input type="text" name="nama" id="nama_sekolah" value="<?php echo $d->nama_sekolah ?>" required oninput="updateMap()">
                        
                        <label style="color:#0d6efd;">Wilayah (Jabodetabek)</label>
                        <select name="wilayah" required>
                            <option value="Jakarta" <?php echo (isset($d->wilayah) && $d->wilayah == 'Jakarta') ? 'selected' : '' ?>>Jakarta</option>
                            <option value="Bogor" <?php echo (isset($d->wilayah) && $d->wilayah == 'Bogor') ? 'selected' : '' ?>>Bogor</option>
                            <option value="Depok" <?php echo (isset($d->wilayah) && $d->wilayah == 'Depok') ? 'selected' : '' ?>>Depok</option>
                            <option value="Tangerang" <?php echo (isset($d->wilayah) && $d->wilayah == 'Tangerang') ? 'selected' : '' ?>>Tangerang</option>
                            <option value="Bekasi" <?php echo (isset($d->wilayah) && $d->wilayah == 'Bekasi') ? 'selected' : '' ?>>Bekasi</option>
                            <option value="Lainnya" <?php echo (isset($d->wilayah) && $d->wilayah == 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                        </select>

                        <label>Logo Sekolah</label>
                        <div style="display:flex; gap:10px; align-items:center; margin-bottom:15px; background:#f9f9f9; padding:10px; border-radius:5px;">
                            <img src="../uploads/<?php echo $d->foto_logo ?>" width="50" style="border-radius:5px;">
                            <input type="file" name="logo" style="margin-bottom:0;">
                            <input type="hidden" name="foto_lama" value="<?php echo $d->foto_logo ?>">
                        </div>
                    </div>
                    <div>
                         <label>Visi & Misi</label>
                         <textarea name="visi_misi" rows="9" placeholder="Tuliskan Visi dan Misi sekolah..."><?php echo isset($d->visi_misi)?$d->visi_misi:'' ?></textarea>
                    </div>
                </div>

                <!-- B. KONTAK & LOKASI -->
                <div class="form-section-title"><i class="fas fa-map-marked-alt"></i> B. Kontak & Lokasi</div>
                <div class="form-grid-2">
                    <div>
                        <div class="form-grid-3">
                            <div><label>No Telpon</label><input type="text" name="no_telpon" value="<?php echo isset($d->no_telpon)?$d->no_telpon:'' ?>"></div>
                            <div><label>WhatsApp</label><input type="text" name="whatsapp" value="<?php echo isset($d->whatsapp)?$d->whatsapp:'' ?>"></div>
                            <div><label>Email</label><input type="email" name="email" value="<?php echo isset($d->email)?$d->email:'' ?>"></div>
                        </div>

                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" id="alamat_sekolah" rows="3" required oninput="updateMap()"><?php echo $d->alamat ?></textarea>

                        <div style="background: #e7f1ff; padding: 15px; border-radius: 8px; border: 1px dashed #0d6efd;">
                            <label style="font-size:12px; color:#0d6efd; margin-bottom:5px;">Koordinat Peta (Wajib untuk Hitung Jarak)</label>
                            <div class="form-grid-2" style="gap:15px; margin-bottom:0;">
                                <input type="text" name="latitude" placeholder="Latitude" value="<?php echo isset($d->latitude)?$d->latitude:'' ?>" style="margin-bottom:0; font-size:12px;">
                                <input type="text" name="longitude" placeholder="Longitude" value="<?php echo isset($d->longitude)?$d->longitude:'' ?>" style="margin-bottom:0; font-size:12px;">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label>Preview Lokasi</label>
                        <iframe id="map-frame" class="map-preview" src="https://maps.google.com/maps?q=<?php echo urlencode($d->nama_sekolah . ' ' . $d->alamat) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed"></iframe>
                    </div>
                </div>

                <!-- C. KURIKULUM & PROGRAM -->
                <div class="form-section-title"><i class="fas fa-book"></i> C. Kurikulum & Program</div>
                <div class="form-grid-2">
                    <div>
                        <label>Kurikulum Utama</label>
                        <input type="text" name="kurikulum" value="<?php echo $d->kurikulum ?>" required placeholder="Contoh: Kurikulum Merdeka, Cambridge">

                        <label>Kejuruan/Peminatan (Khusus SMK/SMA)</label>
                        <textarea name="kejuruan" rows="3" placeholder="Tuliskan daftar jurusan, pisahkan dengan Enter"><?php echo isset($d->kejuruan)?$d->kejuruan:'' ?></textarea>

                        <!-- VIDEO PROFIL -->
                        <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px dashed #ffc107; border-radius: 8px;">
                            <label style="color:#856404; font-size:12px;"><i class="fas fa-video"></i> Video Profil (MP4)</label>
                            <?php if(!empty($d->video_profil) && file_exists('../uploads/'.$d->video_profil)): ?>
                                <div style="font-size:11px; margin-bottom:5px;">File: <b><?php echo $d->video_profil ?></b></div>
                            <?php endif; ?>
                            <input type="file" name="video" accept="video/mp4,video/*" style="margin-bottom:0;">
                            <input type="hidden" name="video_lama" value="<?php echo isset($d->video_profil)?$d->video_profil:'' ?>">
                        </div>
                    </div>
                    <div>
                        <label>Program Unggulan (List per baris)</label>
                        <textarea name="program_unggulan" rows="3" placeholder="Tuliskan program unggulan..."><?php echo isset($d->program_unggulan)?$d->program_unggulan:'' ?></textarea>

                        <label>Ekstrakurikuler (List per baris)</label>
                        <textarea name="ekstrakurikuler" rows="3" placeholder="Tuliskan daftar ekstrakurikuler..."><?php echo isset($d->ekstrakurikuler)?$d->ekstrakurikuler:'' ?></textarea>
                        <small style="color:#888; font-size:11px;">*Gunakan tombol Enter untuk membuat baris baru (list).</small>
                    </div>
                </div>

                <!-- D. STATUS & BIAYA -->
                <div class="form-section-title"><i class="fas fa-tags"></i> D. Status, Biaya & Prestasi</div>
                <div class="form-grid-4">
                    <div>
                        <label>Jenjang</label>
                        <select name="jenjang">
                            <option value="SD" <?php echo ($d->jenjang == 'SD')?'selected':'' ?>>SD</option>
                            <option value="SMP" <?php echo ($d->jenjang == 'SMP')?'selected':'' ?>>SMP</option>
                            <option value="SMA" <?php echo ($d->jenjang == 'SMA')?'selected':'' ?>>SMA</option>
                            <option value="SMK" <?php echo ($d->jenjang == 'SMK')?'selected':'' ?>>SMK</option>
                        </select>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status_sekolah">
                            <option value="Negeri" <?php echo ($d->status_sekolah=='Negeri')?'selected':'' ?>>Negeri</option>
                            <option value="Swasta" <?php echo ($d->status_sekolah=='Swasta')?'selected':'' ?>>Swasta</option>
                            <option value="Boarding School" <?php echo ($d->status_sekolah=='Boarding School')?'selected':'' ?>>Boarding School</option>
                        </select>
                    </div>
                    <div>
                        <label>Akreditasi</label>
                        <select name="akreditasi">
                            <option value="A" <?php echo ($d->akreditasi=='A')?'selected':'' ?>>A (Unggul)</option>
                            <option value="B" <?php echo ($d->akreditasi=='B')?'selected':'' ?>>B (Baik)</option>
                            <option value="C" <?php echo ($d->akreditasi=='C')?'selected':'' ?>>C (Cukup)</option>
                            <option value="-" <?php echo ($d->akreditasi=='-')?'selected':'' ?>>Belum Ada</option>
                        </select>
                    </div>
                    <div>
                        <label>Prestasi Sekolah (List per baris)</label>
                        <textarea name="prestasi" rows="3" placeholder="Contoh:&#10;Juara 1 O2SN&#10;Juara LKS Provinsi"><?php echo isset($d->prestasi)?$d->prestasi:'' ?></textarea>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-top:10px;">
                    <div><label>Uang Pangkal (Rp)</label><input type="number" name="biaya_masuk" value="<?php echo $d->biaya_masuk ?>"></div>
                    <div><label>SPP Bulanan (Rp)</label><input type="number" name="biaya_bulanan" value="<?php echo $d->biaya_bulanan ?>"></div>
                </div>

                <!-- E. DATA RINCI -->
                <div class="form-section-title"><i class="fas fa-chart-bar"></i> E. Data Statistik & Fasilitas</div>
                <div class="form-grid-3">
                    <div><label>Total Siswa</label><input type="number" name="jml_siswa_total" value="<?php echo $d->jml_siswa_total ?>"></div>
                    <div><label>Guru PNS</label><input type="number" name="guru_pns" value="<?php echo $d->guru_pns ?>"></div>
                    <div><label>Guru Non-PNS</label><input type="number" name="guru_non_pns" value="<?php echo $d->guru_non_pns ?>"></div>
                    <div><label>Ruang Kelas</label><input type="number" name="ruang_kelas" value="<?php echo $d->ruang_kelas ?>"></div>
                    <div><label>Lab Komputer</label><input type="number" name="lab_komputer" value="<?php echo $d->lab_komputer ?>"></div>
                    <div><label>Sanitasi Baik?</label><select name="sanitasi_baik"><option value="Ya" <?php echo ($d->sanitasi_baik=='Ya')?'selected':'' ?>>Ya</option><option value="Tidak" <?php echo ($d->sanitasi_baik=='Tidak')?'selected':'' ?>>Tidak</option></select></div>
                </div>
                
                <!-- F. DESKRIPSI LENGKAP -->
                <div class="form-section-title"><i class="fas fa-align-left"></i> F. Deskripsi Lengkap</div>
                <div class="form-grid-2">
                    <div>
                        <label>Deskripsi Umum</label><textarea name="deskripsi" rows="4"><?php echo $d->deskripsi ?></textarea>
                        <label>Program Pembangunan</label><textarea name="program_pembangunan" rows="2"><?php echo $d->program_pembangunan ?></textarea>
                    </div>
                    <div>
                        <label>Fasilitas (List per baris)</label>
                        <textarea name="fasilitas_text" rows="3" placeholder="Contoh:&#10;Lapangan Futsal&#10;Masjid Besar&#10;Perpustakaan Digital"><?php echo isset($d->fasilitas_text)?$d->fasilitas_text:'' ?></textarea>
                        
                        <label>Track Record Alumni</label>
                        <textarea name="track_record" rows="2"><?php echo isset($d->track_record)?$d->track_record:'' ?></textarea>
                    </div>
                </div>

                <!-- G. SKOR PENILAIAN (8 KATEGORI) -->
                <div class="form-section-title"><i class="fas fa-star"></i> G. Skor Penilaian (1-5)</div>
                <div class="form-grid-4" style="background:#f9f9f9; padding:15px; border-radius:8px;">
                    <?php 
                    $skor = ['kurikulum_score'=>'Kurikulum', 'guru_score'=>'Guru', 'fasilitas_score'=>'Fasilitas', 'kedisiplinan_score'=>'Disiplin', 'biaya_score'=>'Biaya', 'lokasi_score'=>'Lokasi', 'keamanan_score'=>'Keamanan', 'reputasi_score'=>'Reputasi'];
                    foreach($skor as $key=>$label){
                        $val = isset($d->$key) ? $d->$key : 3;
                        echo "<div><small>$label: <b id='v_$key'>$val</b></small><input type='range' name='$key' min='1' max='5' value='$val' oninput='document.getElementById(\"v_$key\").innerText=this.value' style='width:100%; margin:0;'></div>";
                    }
                    ?>
                </div>

                <br><br>
                <button type="submit" name="update" class="btn-save"><i class="fas fa-save"></i> Simpan Semua Data</button>
            </form>
        </div>
    </div>

    <script>
        function updateMap() {
            var nama = document.getElementById('nama_sekolah').value;
            var alamat = document.getElementById('alamat_sekolah').value;
            var query = encodeURIComponent(nama + ' ' + alamat);
            document.getElementById('map-frame').src = "https://maps.google.com/maps?q=" + query + "&t=&z=15&ie=UTF8&iwloc=&output=embed";
        }
    </script>

    <?php
    if(isset($_POST['update'])){
        // Sanitasi Input
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        $wilayah = mysqli_real_escape_string($koneksi, $_POST['wilayah']);
        $lat = mysqli_real_escape_string($koneksi, $_POST['latitude']);
        $lng = mysqli_real_escape_string($koneksi, $_POST['longitude']);
        
        // Kontak & Info Baru
        $telp = mysqli_real_escape_string($koneksi, $_POST['no_telpon']);
        $wa = mysqli_real_escape_string($koneksi, $_POST['whatsapp']);
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);
        $visi = mysqli_real_escape_string($koneksi, $_POST['visi_misi']);
        
        // Dropdown Lama Diganti Textarea/Input
        $prog_ung = mysqli_real_escape_string($koneksi, $_POST['program_unggulan']);
        $kejuruan = mysqli_real_escape_string($koneksi, $_POST['kejuruan']);
        $ekskul = mysqli_real_escape_string($koneksi, $_POST['ekstrakurikuler']);
        $kur = mysqli_real_escape_string($koneksi, $_POST['kurikulum']);
        
        // Data Lama
        $jenjang = $_POST['jenjang']; $status = $_POST['status_sekolah']; $akred = $_POST['akreditasi'];
        $masuk = $_POST['biaya_masuk']; $bulan = $_POST['biaya_bulanan']; 
        
        $desk = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
        $fasilitas = mysqli_real_escape_string($koneksi, $_POST['fasilitas_text']);
        $prestasi = mysqli_real_escape_string($koneksi, $_POST['prestasi']); 
        $track = isset($_POST['track_record']) ? mysqli_real_escape_string($koneksi, $_POST['track_record']) : '';
        $pembangun = mysqli_real_escape_string($koneksi, $_POST['program_pembangunan']);

        $jml = $_POST['jml_siswa_total']; $pns = $_POST['guru_pns']; $non = $_POST['guru_non_pns'];
        $ruang = $_POST['ruang_kelas']; $lab = $_POST['lab_komputer']; $sani = $_POST['sanitasi_baik'];

        $s1 = $_POST['kurikulum_score']; $s2 = $_POST['guru_score']; $s3 = $_POST['fasilitas_score']; $s4 = $_POST['kedisiplinan_score'];
        $s5 = $_POST['biaya_score']; $s6 = $_POST['lokasi_score']; $s7 = $_POST['keamanan_score']; $s8 = $_POST['reputasi_score'];

        $foto_db = $_POST['foto_lama'];
        if($_FILES['logo']['name'] != ""){
            $new = time().$_FILES['logo']['name'];
            move_uploaded_file($_FILES['logo']['tmp_name'], '../uploads/'.$new);
            if(file_exists('../uploads/'.$foto_db)) unlink('../uploads/'.$foto_db);
            $foto_db = $new;
        }

        $video_db = $_POST['video_lama'];
        if($_FILES['video']['name'] != ""){
            $vid_name = $_FILES['video']['name'];
            $vid_tmp = $_FILES['video']['tmp_name'];
            $vid_ext = pathinfo($vid_name, PATHINFO_EXTENSION);
            if(in_array(strtolower($vid_ext), ['mp4', 'mkv', 'avi'])){
                $new_vid = 'vid_'.time().'.'.$vid_ext;
                move_uploaded_file($vid_tmp, '../uploads/'.$new_vid);
                $video_db = $new_vid;
            }
        }

        $sql = "UPDATE tb_sekolah SET 
            nama_sekolah='$nama', alamat='$alamat', wilayah='$wilayah', latitude='$lat', longitude='$lng',
            no_telpon='$telp', whatsapp='$wa', email='$email', visi_misi='$visi', program_unggulan='$prog_ung', kejuruan='$kejuruan',
            jenjang='$jenjang', status_sekolah='$status', akreditasi='$akred', biaya_masuk='$masuk', biaya_bulanan='$bulan', kurikulum='$kur', 
            deskripsi='$desk', fasilitas_text='$fasilitas', ekstrakurikuler='$ekskul', prestasi='$prestasi', track_record='$track', program_pembangunan='$pembangun',
            jml_siswa_total='$jml', guru_pns='$pns', guru_non_pns='$non', ruang_kelas='$ruang', lab_komputer='$lab', sanitasi_baik='$sani',
            kurikulum_score='$s1', guru_score='$s2', fasilitas_score='$s3', kedisiplinan_score='$s4',
            biaya_score='$s5', lokasi_score='$s6', keamanan_score='$s7', reputasi_score='$s8',
            foto_logo='$foto_db', video_profil='$video_db'
            WHERE id_sekolah='$id'";

        if(mysqli_query($koneksi, $sql)){
            echo '<script>alert("Data berhasil diperbarui!"); window.location="data_sekolah.php"</script>';
        } else {
            echo '<script>alert("Gagal Update: '.mysqli_error($koneksi).'");</script>';
        }
    }
    ?>
</body>
</html>