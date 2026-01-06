<?php
session_start();
include 'config/koneksi.php';

if(isset($_POST['daftar'])){
    // Data Diri Utama
    $nama    = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $tempat  = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
    $tgl     = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $jk      = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    
    // Kontak & Profil
    $email   = mysqli_real_escape_string($koneksi, $_POST['email']);
    $hp      = mysqli_real_escape_string($koneksi, $_POST['hp']);
    $pend    = mysqli_real_escape_string($koneksi, $_POST['pendidikan']);
    $kerja   = mysqli_real_escape_string($koneksi, $_POST['pekerjaan']);
    $pass    = md5($_POST['pass']); 
    
    // Data Lokasi
    $lat     = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $lng     = mysqli_real_escape_string($koneksi, $_POST['longitude']);
    $alamat  = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    // Cek email duplikat
    $cek = mysqli_query($koneksi, "SELECT * FROM tb_orang_tua WHERE email = '$email'");
    if(mysqli_num_rows($cek) > 0){
        echo '<script>alert("Email sudah terdaftar! Silakan login.");</script>';
    } else {
        $query = "INSERT INTO tb_orang_tua 
                  (nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, email, no_hp, pendidikan_terakhir, pekerjaan, password, latitude, longitude, alamat_lengkap) 
                  VALUES 
                  ('$nama', '$tempat', '$tgl', '$jk', '$email', '$hp', '$pend', '$kerja', '$pass', '$lat', '$lng', '$alamat')";
                  
        $insert = mysqli_query($koneksi, $query);
        
        if($insert){
            echo '<script>alert("Pendaftaran Berhasil! Silakan Login."); window.location="login_user.php";</script>';
        } else {
            echo '<script>alert("Gagal mendaftar: '.mysqli_error($koneksi).'");</script>';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrasi Lengkap - EduSearch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=18">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- LEAFLET JS (PETA) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { background: #f0f2f5; padding: 40px 0; font-family: 'Segoe UI', sans-serif; }
        .auth-container { display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .auth-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 800px; } /* Lebar diperbesar */
        
        .auth-header { text-align: center; margin-bottom: 30px; border-bottom: 2px dashed #f0f0f0; padding-bottom: 20px; }
        .auth-header h2 { color: #0d6efd; margin: 10px 0 5px; }
        
        .form-label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none; box-sizing: border-box; transition: 0.3s; }
        .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 5px rgba(13, 110, 253, 0.2); }
        
        /* Grid System untuk Form */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        
        .section-title { font-size: 16px; font-weight: bold; color: #0d6efd; margin: 25px 0 15px; display: flex; align-items: center; gap: 10px; }
        .section-title::after { content: ''; flex: 1; height: 1px; background: #eee; }

        .btn-auth { width: 100%; padding: 15px; background: #0d6efd; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 16px; margin-top: 20px; }
        .btn-auth:hover { background: #0b5ed7; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3); }
        
        /* Map Styling */
        #map { height: 300px; border-radius: 10px; border: 1px solid #ddd; margin-bottom: 10px; }
        .btn-geo { background: #198754; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 12px; margin-bottom: 10px; display: inline-flex; align-items: center; gap: 5px; }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; gap: 15px; }
            .auth-box { padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <i class="fas fa-graduation-cap" style="font-size: 40px; color: #0d6efd;"></i>
                <h2>Registrasi Akun Orang Tua</h2>
                <p style="color: #888; font-size: 14px;">Lengkapi data diri untuk mendapatkan rekomendasi sekolah terbaik.</p>
            </div>

            <form action="" method="POST">
                
                <!-- DATA PRIBADI -->
                <div class="section-title"><i class="fas fa-user"></i> Data Pribadi</div>
                
                <div class="form-group">
                    <label class="form-label">Nama Lengkap (Sesuai KTP)</label>
                    <input type="text" name="nama" class="form-control" required placeholder="Nama Orang Tua">
                </div>

                <div class="form-grid">
                    <div>
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control" required placeholder="Kota Kelahiran">
                    </div>
                    <div>
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-control" required>
                        <option value="">-- Pilih --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <!-- KONTAK & PEKERJAAN -->
                <div class="section-title"><i class="fas fa-briefcase"></i> Kontak & Pekerjaan</div>

                <div class="form-grid">
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="email@contoh.com">
                    </div>
                    <div>
                        <label class="form-label">Nomor Handphone (WA)</label>
                        <input type="number" name="hp" class="form-control" required placeholder="0812xxx">
                    </div>
                </div>

                <div class="form-grid">
                    <div>
                        <label class="form-label">Pendidikan Terakhir</label>
                        <select name="pendidikan" class="form-control">
                            <option value="SMA/Sederajat">SMA/Sederajat</option>
                            <option value="D3">Diploma (D3)</option>
                            <option value="S1">Sarjana (S1)</option>
                            <option value="S2">Magister (S2)</option>
                            <option value="S3">Doktor (S3)</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Pekerjaan Saat Ini</label>
                        <input type="text" name="pekerjaan" class="form-control" placeholder="Contoh: Wiraswasta / PNS">
                    </div>
                </div>

                <!-- LOKASI & KEAMANAN -->
                <div class="section-title"><i class="fas fa-map-marked-alt"></i> Lokasi Rumah</div>
                
                <div class="form-group">
                    <button type="button" class="btn-geo" onclick="getLocation()">
                        <i class="fas fa-crosshairs"></i> Ambil Lokasi Saya Saat Ini
                    </button>
                    <div id="map"></div>
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Nama Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan..." required></textarea>
                    
                    <!-- Hidden Input Koordinat -->
                    <input type="hidden" name="latitude" id="lat" required>
                    <input type="hidden" name="longitude" id="lng" required>
                </div>

                <div class="section-title"><i class="fas fa-lock"></i> Keamanan Akun</div>
                <div class="form-group">
                    <label class="form-label">Buat Password</label>
                    <input type="password" name="pass" class="form-control" required placeholder="******">
                </div>

                <button type="submit" name="daftar" class="btn-auth">Daftar & Simpan Data</button>
            </form>

            <div style="text-align:center; margin-top:25px; font-size:14px;">
                Sudah punya akun? <a href="login_user.php" style="color:#0d6efd; font-weight:bold; text-decoration:none;">Login disini</a>
            </div>
        </div>
    </div>

    <script>
        // Inisialisasi Peta (Default Jakarta)
        var map = L.map('map').setView([-6.200000, 106.816666], 13);

        // Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Marker Draggable
        var marker = L.marker([-6.200000, 106.816666], {draggable: true}).addTo(map);

        // Update input saat marker digeser
        marker.on('dragend', function(event){
            var position = marker.getLatLng();
            document.getElementById('lat').value = position.lat.toFixed(8);
            document.getElementById('lng').value = position.lng.toFixed(8);
        });

        // Geolocation
        function getLocation() {
            if (navigator.geolocation) {
                alert("Sedang mendeteksi lokasi... Mohon tunggu.");
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                alert("Geolocation tidak didukung oleh browser ini.");
            }
        }

        function showPosition(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;

            map.setView([lat, lng], 16);
            marker.setLatLng([lat, lng]);

            document.getElementById('lat').value = lat.toFixed(8);
            document.getElementById('lng').value = lng.toFixed(8);
        }
        
        function showError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED: alert("User menolak permintaan lokasi."); break;
                case error.POSITION_UNAVAILABLE: alert("Informasi lokasi tidak tersedia."); break;
                case error.TIMEOUT: alert("Waktu permintaan habis."); break;
                case error.UNKNOWN_ERROR: alert("Terjadi kesalahan yang tidak diketahui."); break;
            }
        }
        
        // Set default
        document.getElementById('lat').value = -6.200000;
        document.getElementById('lng').value = 106.816666;
        
        // Fix Map Resize
        setTimeout(function(){ map.invalidateSize(); }, 500);
    </script>

</body>
</html>