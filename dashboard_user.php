<?php
session_start();
include 'config/koneksi.php';

// Cek Login User
if(!isset($_SESSION['uid_ortu'])){
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login_user.php';</script>";
    exit;
}

$id_user = $_SESSION['uid_ortu'];

// --- PROSES UPDATE PROFIL ---
if(isset($_POST['update_profil'])){
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $hp     = mysqli_real_escape_string($koneksi, $_POST['hp']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $lat    = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $lng    = mysqli_real_escape_string($koneksi, $_POST['longitude']);
    
    $tempat = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
    $tgl    = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $jk     = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $pend   = mysqli_real_escape_string($koneksi, $_POST['pendidikan']);
    $kerja  = mysqli_real_escape_string($koneksi, $_POST['pekerjaan']);
    
    $pass_query = "";
    if(!empty($_POST['password'])){
        $pass = md5($_POST['password']);
        $pass_query = ", password='$pass'";
    }

    $sql = "UPDATE tb_orang_tua SET 
            nama_lengkap='$nama', no_hp='$hp', alamat_lengkap='$alamat',
            latitude='$lat', longitude='$lng',
            tempat_lahir='$tempat', tanggal_lahir='$tgl', jenis_kelamin='$jk',
            pendidikan_terakhir='$pend', pekerjaan='$kerja'
            $pass_query
            WHERE id_ortu='$id_user'";
            
    if(mysqli_query($koneksi, $sql)){
        $_SESSION['nama_ortu'] = $nama;
        echo "<script>alert('Data profil berhasil diperbarui!'); window.location='dashboard_user.php';</script>";
    } else {
        echo "<script>alert('Gagal update: ".mysqli_error($koneksi)."');</script>";
    }
}

// --- PROSES KIRIM TESTIMONI (BARU) ---
if(isset($_POST['kirim_testimoni'])){
    $isi = mysqli_real_escape_string($koneksi, $_POST['isi_testimoni']);
    $rating = (int)$_POST['rating'];
    
    // Cek apakah user sudah pernah memberi testimoni
    $cek_testi = mysqli_query($koneksi, "SELECT id_testimoni FROM tb_testimoni WHERE id_ortu='$id_user'");
    
    if(mysqli_num_rows($cek_testi) > 0){
        // Update jika sudah ada
        $sql_testi = "UPDATE tb_testimoni SET isi_testimoni='$isi', rating='$rating', tgl_posting=NOW() WHERE id_ortu='$id_user'";
    } else {
        // Insert jika belum ada
        $sql_testi = "INSERT INTO tb_testimoni (id_ortu, isi_testimoni, rating) VALUES ('$id_user', '$isi', '$rating')";
    }
    
    if(mysqli_query($koneksi, $sql_testi)){
        echo "<script>alert('Terima kasih! Ulasan Anda berhasil dikirim.'); window.location='dashboard_user.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim ulasan.');</script>";
    }
}

// --- AMBIL DATA ---
// Data User
$query = mysqli_query($koneksi, "SELECT * FROM tb_orang_tua WHERE id_ortu='$id_user'");
$u = mysqli_fetch_object($query);

// Data Testimoni User (Jika ada)
$q_testi_saya = mysqli_query($koneksi, "SELECT * FROM tb_testimoni WHERE id_ortu='$id_user'");
$testi = mysqli_fetch_object($q_testi_saya);
$my_rating = isset($testi->rating) ? $testi->rating : 5; // Default 5 bintang
$my_review = isset($testi->isi_testimoni) ? $testi->isi_testimoni : '';

// Helper Peta
$lat_val = (isset($u->latitude) && $u->latitude != 0) ? $u->latitude : '-6.200000'; 
$lng_val = (isset($u->longitude) && $u->longitude != 0) ? $u->longitude : '106.816666'; 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - EduSearch</title>
    
    <link rel="stylesheet" href="assets/css/style.css?v=22">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { background: #f8f9fa; }
        .dashboard-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .welcome-box { background: linear-gradient(135deg, #0d6efd, #0043a8); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2); }
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card-profile { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); height: fit-content; margin-bottom: 30px; }
        .section-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        label { display: block; font-weight: 600; margin-bottom: 5px; color: #555; font-size: 14px; }
        input, textarea, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; outline: none; font-family: inherit; }
        input:focus, textarea:focus, select:focus { border-color: #0d6efd; }
        #map { height: 300px; border-radius: 10px; margin-bottom: 15px; border: 1px solid #ddd; z-index: 0; }
        .btn-update { width: 100%; padding: 15px; background: #198754; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-update:hover { background: #157347; }
        
        /* Rating Star Style */
        .rating-box { display: flex; gap: 10px; flex-direction: row-reverse; justify-content: flex-end; margin-bottom: 15px; }
        .rating-box input { display: none; }
        .rating-box label { cursor: pointer; font-size: 25px; color: #ddd; transition: 0.2s; margin: 0; }
        .rating-box input:checked ~ label { color: #ffc107; }
        .rating-box label:hover, .rating-box label:hover ~ label { color: #ffdb70; }
        
        .btn-testi { background: #0d6efd; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; }
        .btn-testi:hover { background: #0b5ed7; }

        @media (max-width: 768px) { .profile-grid { grid-template-columns: 1fr; } .welcome-box { flex-direction: column; text-align: center; gap: 15px; } }
    </style>
</head>
<body>

    <header>
        <div class="container header-content">
            <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduSearch</a>
            <div style="display:flex; gap:10px; align-items:center;">
                <a href="index.php" style="color:#555; font-weight:600; font-size:14px; text-decoration:none;"><i class="fas fa-home"></i> Beranda</a>
                <a href="logout_user.php" style="color:#dc3545; font-weight:600; font-size:14px; text-decoration:none; margin-left:10px;"><i class="fas fa-sign-out-alt"></i> Keluar</a>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        
        <div class="welcome-box">
            <div>
                <h2 style="margin:0;">Halo, <?php echo htmlspecialchars($u->nama_lengkap) ?>!</h2>
                <p style="margin:5px 0 0; opacity:0.9;">Lengkapi profil Anda untuk pengalaman terbaik.</p>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 50px;">
                <i class="fas fa-user-check"></i> Akun Orang Tua
            </div>
        </div>

        <form action="" method="POST">
            <div class="profile-grid">
                
                <!-- KOLOM KIRI -->
                <div class="card-profile">
                    <div class="section-title"><i class="fas fa-user-edit"></i> Data Diri</div>
                    
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($u->nama_lengkap) ?>" required>
                    
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <div><label>Tempat Lahir</label><input type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($u->tempat_lahir) ?>"></div>
                        <div><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?php echo $u->tanggal_lahir ?>"></div>
                    </div>

                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="L" <?php echo ($u->jenis_kelamin == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?php echo ($u->jenis_kelamin == 'P') ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                    
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($u->email) ?>" disabled style="background:#f9f9f9; color:#888;">
                    
                    <label>Nomor Handphone</label>
                    <input type="number" name="hp" value="<?php echo htmlspecialchars($u->no_hp) ?>" required>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <div>
                            <label>Pendidikan</label>
                            <select name="pendidikan">
                                <option <?php echo ($u->pendidikan_terakhir == 'SMA/Sederajat') ? 'selected' : '' ?>>SMA/Sederajat</option>
                                <option <?php echo ($u->pendidikan_terakhir == 'D3') ? 'selected' : '' ?>>D3</option>
                                <option <?php echo ($u->pendidikan_terakhir == 'S1') ? 'selected' : '' ?>>S1</option>
                                <option <?php echo ($u->pendidikan_terakhir == 'S2') ? 'selected' : '' ?>>S2</option>
                                <option <?php echo ($u->pendidikan_terakhir == 'S3') ? 'selected' : '' ?>>S3</option>
                                <option <?php echo ($u->pendidikan_terakhir == 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>
                        <div><label>Pekerjaan</label><input type="text" name="pekerjaan" value="<?php echo htmlspecialchars($u->pekerjaan) ?>"></div>
                    </div>
                    
                    <label>Ganti Password (Opsional)</label>
                    <input type="password" name="password" placeholder="Isi jika ingin ubah password">
                </div>

                <!-- KOLOM KANAN -->
                <div>
                    <!-- CARD LOKASI -->
                    <div class="card-profile">
                        <div class="section-title"><i class="fas fa-map-marked-alt"></i> Lokasi Rumah</div>
                        <div id="map"></div>
                        <button type="button" onclick="getLocation()" style="background:#0d6efd; color:white; border:none; padding:10px; width:100%; border-radius:5px; cursor:pointer; margin-bottom:15px; font-weight:bold;"><i class="fas fa-crosshairs"></i> Ambil Lokasi Saya</button>
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" rows="2"><?php echo htmlspecialchars($u->alamat_lengkap) ?></textarea>
                        <div style="display:flex; gap:10px;">
                            <input type="text" name="latitude" id="lat" value="<?php echo htmlspecialchars($lat_val) ?>" readonly style="background:#f0f0f0; font-size:11px;">
                            <input type="text" name="longitude" id="lng" value="<?php echo htmlspecialchars($lng_val) ?>" readonly style="background:#f0f0f0; font-size:11px;">
                        </div>
                        <button type="submit" name="update_profil" class="btn-update"><i class="fas fa-save"></i> Simpan Profil</button>
                    </div>

                    <!-- CARD TESTIMONI (BARU) -->
                    <div class="card-profile">
                        <div class="section-title"><i class="fas fa-comment-dots"></i> Bagikan Pengalaman Anda</div>
                        <p style="font-size:13px; color:#666; margin-bottom:15px;">Bagaimana pengalaman Anda menggunakan EduSearch? Ulasan Anda membantu kami berkembang.</p>
                        
                        <label>Rating Bintang</label>
                        <div class="rating-box">
                            <input type="radio" name="rating" value="5" id="r5" <?php echo ($my_rating==5)?'checked':'' ?>><label for="r5">★</label>
                            <input type="radio" name="rating" value="4" id="r4" <?php echo ($my_rating==4)?'checked':'' ?>><label for="r4">★</label>
                            <input type="radio" name="rating" value="3" id="r3" <?php echo ($my_rating==3)?'checked':'' ?>><label for="r3">★</label>
                            <input type="radio" name="rating" value="2" id="r2" <?php echo ($my_rating==2)?'checked':'' ?>><label for="r2">★</label>
                            <input type="radio" name="rating" value="1" id="r1" <?php echo ($my_rating==1)?'checked':'' ?>><label for="r1">★</label>
                        </div>

                        <label>Ulasan Anda</label>
                        <textarea name="isi_testimoni" rows="3" placeholder="Tulis ulasan di sini..." required><?php echo htmlspecialchars($my_review) ?></textarea>
                        
                        <button type="submit" name="kirim_testimoni" class="btn-testi"><i class="fas fa-paper-plane"></i> Kirim Ulasan</button>
                    </div>
                    <!-- END CARD TESTIMONI -->
                </div>

            </div>
        </form>
    </div>

    <script>
        var dbLat = "<?php echo $lat_val; ?>";
        var dbLng = "<?php echo $lng_val; ?>";
        var startLat = (dbLat && parseFloat(dbLat) != 0) ? parseFloat(dbLat) : -6.200000;
        var startLng = (dbLng && parseFloat(dbLng) != 0) ? parseFloat(dbLng) : 106.816666;

        var map = L.map('map').setView([startLat, startLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        var marker = L.marker([startLat, startLng], {draggable: true}).addTo(map);

        marker.on('dragend', function(event){
            var position = marker.getLatLng();
            document.getElementById('lat').value = position.lat.toFixed(8);
            document.getElementById('lng').value = position.lng.toFixed(8);
        });

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    map.setView([lat, lng], 16);
                    marker.setLatLng([lat, lng]);
                    document.getElementById('lat').value = lat.toFixed(8);
                    document.getElementById('lng').value = lng.toFixed(8);
                });
            } else { alert("Browser tidak support geolocation."); }
        }
        setTimeout(function(){ map.invalidateSize(); }, 500);
    </script>

</body>
</html>