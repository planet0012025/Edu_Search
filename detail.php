<?php
session_start(); 
include 'config/koneksi.php';

// 1. Ambil ID Sekolah & Data Utama
$id = mysqli_real_escape_string($koneksi, $_GET['id']);
$query = mysqli_query($koneksi, "SELECT * FROM tb_sekolah WHERE id_sekolah = '$id'");
$d = mysqli_fetch_object($query);

if(!$d){ echo "<script>window.location='index.php';</script>"; exit; }

// Helper Visual
$sanitasi_color = ($d->sanitasi_baik == 'Ya') ? '#198754' : '#dc3545';
$sanitasi_icon = ($d->sanitasi_baik == 'Ya') ? 'fa-check-circle' : 'fa-times-circle';

// --- FUNGSI HITUNG JARAK (Haversine) ---
if (!function_exists('hitungJarak')) {
    function hitungJarak($lat1, $lon1, $lat2, $lon2) {
        if(empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) return null;
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos(min(max($dist, -1.0), 1.0));
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $km = $miles * 1.609344;
        return $km;
    }
}

// --- LOGIKA JARAK ---
$jarak_text = "";
$user_has_location = false;
$school_has_location = (!empty($d->latitude) && !empty($d->longitude));
$user_lat = "";
$user_lng = "";

if(isset($_SESSION['uid_ortu'])) {
    $uid = $_SESSION['uid_ortu'];
    $u_query = mysqli_query($koneksi, "SELECT latitude, longitude FROM tb_orang_tua WHERE id_ortu='$uid'");
    $u = mysqli_fetch_object($u_query);
    
    if($u && !empty($u->latitude)){
        $user_has_location = true;
        $user_lat = $u->latitude;
        $user_lng = $u->longitude;
        if($school_has_location){
            $km = hitungJarak($u->latitude, $u->longitude, $d->latitude, $d->longitude);
            if($km !== null){
                $jarak_text = ($km < 1) ? round($km*1000)." Meter" : number_format($km, 2)." KM";
            }
        }
    }
}

// --- QUERY DATA FASILITAS (OPTIMIZED) ---
$q_fasilitas = mysqli_query($koneksi, "
    SELECT * FROM tb_fasilitas 
    WHERE id_sekolah = '$id' 
    ORDER BY 
    CASE 
        WHEN kategori = 'Fasilitas Umum' THEN 1
        WHEN kategori = 'Sarana Belajar' THEN 2
        WHEN kategori = 'Sarana Olahraga' THEN 3
        WHEN kategori = 'Sarana Ibadah' THEN 4
        WHEN kategori = 'Sarana Penunjang' THEN 5
        ELSE 6 
    END ASC, 
    nama_fasilitas ASC
");
$has_fasilitas_db = (mysqli_num_rows($q_fasilitas) > 0);

// --- LOGIKA PAGINATION GALERI ---
$batas_galeri = 6; 
$halaman_galeri = isset($_GET['hal_galeri']) ? (int)$_GET['hal_galeri'] : 1;
$halaman_awal_galeri = ($halaman_galeri > 1) ? ($halaman_galeri * $batas_galeri) - $batas_galeri : 0;

$q_count_galeri = mysqli_query($koneksi, "SELECT id_galeri FROM tb_galeri WHERE id_sekolah = '$id'");
$total_foto = mysqli_num_rows($q_count_galeri);
$total_halaman_galeri = ceil($total_foto / $batas_galeri);

$galeri = mysqli_query($koneksi, "SELECT * FROM tb_galeri WHERE id_sekolah = '$id' ORDER BY id_galeri DESC LIMIT $halaman_awal_galeri, $batas_galeri");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $d->nama_sekolah ?> - EduSearch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=33"> 
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 (UX Upgrade) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* --- CSS Custom Detail --- */
        html { scroll-behavior: smooth; }
        .data-rinci-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;}
        .rinci-item { background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center; border-bottom: 3px solid #0d6efd; transition: 0.3s; }
        .rinci-item:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .rinci-item p { font-size: 12px; color: #666; margin: 0; }
        .rinci-item .val { font-size: 20px; font-weight: bold; color: #333; }
        
        .list-box { margin-bottom: 20px; }
        .list-box ul { list-style: none; padding: 0; }
        .list-box li { padding: 10px 0; border-bottom: 1px dashed #eee; display: flex; gap: 12px; color: #555; align-items: flex-start; font-size: 14px; }
        .list-box li i { color: #0d6efd; margin-top: 4px; flex-shrink: 0; font-size: 16px; }

        .badge-status { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 50px; font-size: 12px; font-weight: bold; display: inline-block; color: white; border: 1px solid rgba(255,255,255,0.3);}
        .badge-jenjang { background: #ffc107 !important; color: #333 !important; padding: 5px 15px; border-radius: 50px; font-size: 12px; font-weight: bold; display: inline-block; position: static !important; box-shadow: none !important; }
        
        .distance-badge { background: #ffc107; color: #000; padding: 8px 15px; border-radius: 50px; font-weight: 800; font-size: 13px; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); margin-right: 10px; transform: translateY(-2px); }
        .distance-warning { background: #dc3545; color: #fff; padding: 6px 15px; border-radius: 50px; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; margin-right: 10px; text-decoration: none; transition: 0.3s; cursor: pointer; }
        .distance-warning:hover { background: #b02a37; }
        
        .btn-route { background: #0d6efd; color: white; padding: 10px 15px; border-radius: 8px; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; text-decoration: none; transition: 0.3s; }
        .btn-route:hover { background: #0b5ed7; }

        .fasilitas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .fasilitas-item { text-align: center; }
        .fasilitas-item img { width: 100%; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; cursor: pointer; transition: 0.3s; background: #f0f0f0; }
        .fasilitas-item img:hover { transform: scale(1.05); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .fasilitas-item span { display: block; font-size: 11px; margin-top: 5px; font-weight: 500; line-height: 1.2; color: #555; }
        .kategori-title { margin: 25px 0 12px; color: #0d6efd; border-bottom: 1px solid #eee; padding-bottom: 5px; font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px;}
        
        .gallery-modern { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 15px; }
        .gallery-card { border-radius: 12px; overflow: hidden; position: relative; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: all 0.3s ease; cursor: pointer; background: #fff; border: 1px solid #eee; }
        .gallery-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15); border-color: #0d6efd; }
        .gallery-card img { width: 100%; height: 160px; object-fit: cover; display: block; transition: transform 0.5s ease; }
        .gallery-card:hover img { transform: scale(1.1); }
        .gallery-caption { padding: 12px; font-size: 13px; color: #444; text-align: center; background: white; position: relative; z-index: 2; font-weight: 600; border-top: 1px solid #f0f0f0; }

        .pagination-galeri { display: flex; justify-content: center; gap: 8px; margin-top: 25px; padding-top: 20px; border-top: 1px dashed #eee; }
        .pg-link { padding: 8px 14px; background: white; border: 1px solid #ddd; border-radius: 50px; color: #555; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.3s; }
        .pg-link:hover { background: #f1f1f1; color: #333; }
        .pg-link.active { background: #0d6efd; color: white; border-color: #0d6efd; pointer-events: none; }

        .map-frame { width: 100%; height: 350px; border: 1px solid #ddd; border-radius: 10px; }
        .blur-price { filter: blur(4px); user-select: none; }
        .login-to-view { color: #dc3545; font-size: 12px; font-style: italic; margin-top: 5px; }

        .about-box { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 30px; margin-bottom: 30px; border: 1px solid #f0f0f0; position: relative; overflow: hidden; }
        .about-box::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(to bottom, #0d6efd, #0099ff); }
        .about-title { font-size: 20px; font-weight: 800; color: #222; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .about-title i { color: #0d6efd; background: #e7f1ff; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 16px; }
        .about-text { color: #555; line-height: 1.8; font-size: 15px; text-align: justify; margin-bottom: 20px; }
        .highlight-box { background: #f8faff; border: 1px dashed #cce5ff; padding: 15px 20px; border-radius: 10px; display: inline-block; color: #0d6efd; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .highlight-box i { font-size: 18px; }
        .video-wrapper { margin-top: 25px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #eee; position: relative; }
        .highlight-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .contact-list { list-style: none; padding: 0; margin: 0; }
        .contact-list li { margin-bottom: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; color: #555; }
        .contact-list li i { width: 25px; text-align: center; color: #0d6efd; }
        .contact-list a { text-decoration: none; color: #555; transition: 0.3s; }
        .contact-list a:hover { color: #0d6efd; }

        .accordion { background-color: #f8f9fa; color: #444; cursor: pointer; padding: 15px 20px; width: 100%; border: none; text-align: left; outline: none; font-size: 15px; transition: 0.4s; border-radius: 8px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border: 1px solid #eee; }
        .accordion:hover { background-color: #e7f1ff; color: #0d6efd; }
        .accordion.active { background-color: #0d6efd; color: white; }
        .panel { padding: 0 18px; background-color: white; max-height: 0; overflow: hidden; transition: max-height 0.2s ease-out; margin-bottom: 0; border-radius: 0 0 8px 8px; }
        .panel p { margin: 15px 0; color: #555; line-height: 1.7; font-size: 14px; }
        .accordion:after { content: '\002B'; font-size: 18px; color: #777; float: right; margin-left: 5px; transition: 0.3s;}
        .accordion.active:after { content: "\2212"; color: white; }
        .accordion:hover:after { color: #0d6efd; }
        .accordion.active:hover:after { color: white; }

        /* Lightbox Update */
        .lightbox { display: none; position: fixed; z-index: 1000; padding-top: 50px; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.9); backdrop-filter: blur(5px); }
        .lightbox-wrapper { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; padding: 20px; box-sizing: border-box; }
        .lightbox-container { display: flex; background: white; width: 100%; max-width: 1000px; height: 85vh; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative; border: 1px solid #f0f0f0; }
        .lightbox-image-area { flex: 2; background: #fdfdfd; display: flex; align-items: center; justify-content: center; position: relative; padding: 20px; }
        .lightbox-content { max-width: 100%; max-height: 100%; object-fit: contain; display: block; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .lightbox-sidebar { flex: 1; background: #fff; padding: 40px 30px; display: flex; flex-direction: column; overflow-y: auto; min-width: 320px; max-width: 400px; border-left: 1px solid #f0f0f0; }
        #caption { font-size: 22px; font-weight: 700; color: #222; margin-bottom: 10px; line-height: 1.3; margin-top: 0; }
        .lightbox-meta-label { display: inline-block; background: #e7f1ff; color: #0d6efd; padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px; }
        #description { font-size: 15px; color: #555; line-height: 1.7; margin-bottom: 20px; white-space: pre-line; }
        
        /* New Metadata Styles */
        .meta-info-box { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; display: none; }
        .meta-item { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 13px; color: #666; }
        .meta-item i { width: 20px; text-align: center; color: #0d6efd; }

        .lightbox-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid #f0f0f0; font-size: 13px; color: #999; display: flex; align-items: center; gap: 5px; }
        .close { position: absolute; top: 20px; right: 20px; color: #555; font-size: 24px; cursor: pointer; z-index: 1010; background: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 1px solid #eee; }
        .close:hover { background: #f1f1f1; color: #000; transform: rotate(90deg); }

        /* --- MOBILE FAB (NEW UX) --- */
        .fab-container { position: fixed; bottom: 20px; right: 20px; z-index: 999; display: none; }
        .fab-btn { background: #0d6efd; color: white; padding: 12px 20px; border-radius: 50px; font-weight: bold; box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4); text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .fab-btn:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(13, 110, 253, 0.5); }
        
        @media (max-width: 900px) {
            .lightbox-wrapper { padding: 0; }
            .lightbox-container { flex-direction: column; width: 100%; height: 100%; max-height: 100%; border-radius: 0; }
            .lightbox-image-area { flex: none; width: 100%; height: 50vh; background: #f8f9fa; padding: 0; }
            .lightbox-content { width: 100%; height: 100%; object-fit: contain; border-radius: 0; box-shadow: none; }
            .lightbox-sidebar { flex: 1; width: 100%; min-width: auto; max-width: none; padding: 25px; border-left: none; border-top: 1px solid #eee; }
            .close { top: 15px; right: 15px; background: rgba(255,255,255,0.9); }
            .fab-container { display: block; } /* Show FAB only on mobile */
        }
    </style>
</head>
<body>

    <header>
        <div class="container header-content">
            <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduSearch</a>
            <div style="display:flex; gap:10px;">
                <?php if(isset($_SESSION['uid_ortu'])): ?>
                    <a href="dashboard_user.php" class="btn-login" style="font-size:14px;"><i class="fas fa-user"></i> Profil Saya</a>
                <?php endif; ?>
                <a href="index.php" style="color:#555; font-weight:600; font-size:14px; display:flex; align-items:center; text-decoration:none;"><i class="fas fa-arrow-left" style="margin-right:5px;"></i> Kembali</a>
            </div>
        </div>
    </header>

    <!-- Banner Detail -->
    <div class="detail-banner">
        <div class="container">
            <div class="detail-banner-content">
                <img src="uploads/<?php echo $d->foto_logo ?>" 
                     alt="Logo Sekolah" 
                     onerror="this.src='assets/img/no-image.jpg'"
                     style="width: 120px; height: 120px; object-fit: cover; border-radius: 15px; border: 4px solid rgba(255,255,255,0.3); background: #fff;">
                
                <div class="text-content">
                    <div style="margin-bottom: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <span class="badge-jenjang"><?php echo $d->jenjang ?></span>
                        <span class="badge-status"><?php echo $d->status_sekolah ?></span>
                    </div>

                    <h1 style="margin:0;"><?php echo $d->nama_sekolah ?></h1>
                    
                    <div class="school-meta" style="margin-top:15px; display:flex; flex-wrap:wrap; gap:15px; align-items:center;">
                        <?php if($jarak_text != ""): ?>
                            <span class="distance-badge" title="Jarak Garis Lurus">
                                <i class="fas fa-location-arrow"></i> <?php echo $jarak_text ?> dari Anda
                            </span>
                        <?php elseif(isset($_SESSION['uid_ortu'])): ?>
                            <?php if(!$user_has_location): ?>
                                <a href="dashboard_user.php" class="distance-warning"><i class="fas fa-map-marker-alt"></i> Set Lokasi Anda</a>
                            <?php elseif(!$school_has_location): ?>
                                <span class="distance-warning" style="background:rgba(0,0,0,0.4); cursor:default; border:1px solid rgba(255,255,255,0.2);"><i class="fas fa-map-marker-slash"></i> Lokasi Sekolah Belum Tersedia</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo $d->alamat ?></span>
                        <span><i class="fas fa-star" style="color:#ffc107;"></i> Akreditasi <?php echo (!empty($d->akreditasi)) ? $d->akreditasi : '-'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="detail-grid">
            
            <!-- KIRI: KONTEN UTAMA -->
            <div class="detail-main">
                
                <!-- Data Statistik Angka -->
                <div class="box-white">
                    <div class="detail-label"><i class="fas fa-chart-pie"></i> Data Statistik</div>
                    <div class="data-rinci-grid">
                        <div class="rinci-item"><div class="val"><?php echo number_format($d->jml_siswa_total) ?></div><p>Siswa</p></div>
                        <div class="rinci-item"><div class="val"><?php echo $d->guru_pns + $d->guru_non_pns ?></div><p>Total Guru</p></div>
                        <div class="rinci-item"><div class="val"><?php echo $d->ruang_kelas ?></div><p>R. Kelas</p></div>
                        <div class="rinci-item"><div class="val"><?php echo $d->lab_komputer ?></div><p>Lab Komp</p></div>
                        <div class="rinci-item" style="border-color:<?php echo $sanitasi_color ?>">
                            <div class="val" style="color:<?php echo $sanitasi_color ?>"><i class="fas <?php echo $sanitasi_icon ?>"></i></div>
                            <p>Sanitasi</p>
                        </div>
                    </div>
                </div>

                <!-- DESKRIPSI SEKOLAH -->
                <div class="about-box">
                    <div class="about-title">
                        <i class="fas fa-graduation-cap"></i> Tentang Sekolah
                    </div>
                    
                    <div class="about-text">
                        <?php echo nl2br($d->deskripsi) ?>
                    </div>
                    
                    <div class="highlight-grid">
                        <div class="highlight-box">
                            <i class="fas fa-book-open"></i>
                            <div><span>Kurikulum</span><b><?php echo $d->kurikulum ?></b></div>
                        </div>
                    </div>

                    <?php if(!empty($d->visi_misi)): ?>
                    <div style="margin-top: 25px; padding-top:20px; border-top:1px solid #eee;">
                        <button class="accordion"><i class="fas fa-bullseye" style="margin-right:10px; color:#0d6efd;"></i> Visi & Misi Sekolah</button>
                        <div class="panel">
                            <p><?php echo nl2br($d->visi_misi) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($d->video_profil) && file_exists('uploads/'.$d->video_profil)): ?>
                        <div class="video-wrapper">
                            <video width="100%" controls poster="uploads/<?php echo $d->foto_logo ?>" style="display:block; max-height: 450px; background:#000;">
                                <source src="uploads/<?php echo $d->video_profil ?>" type="video/mp4">
                                Browser Anda tidak mendukung tag video.
                            </video>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PRESTASI & TRACK RECORD -->
                <?php if(!empty($d->prestasi) || !empty($d->track_record)): ?>
                <div class="box-white">
                    <div class="detail-label"><i class="fas fa-trophy"></i> Prestasi Sekolah</div>
                    <?php if(!empty($d->prestasi)): ?>
                        <p style="font-weight:bold; margin-bottom:5px; color:#0d6efd;">Prestasi Murid :</p>
                        <ul style="list-style:none; padding:0; margin-bottom:20px;">
                            <?php $lines = explode("\n", $d->prestasi); foreach($lines as $line){ if(trim($line)!="") echo "<li style='padding:5px 0; border-bottom:1px dashed #eee;'><i class='fas fa-medal' style='color:#ffc107; margin-right:8px;'></i> $line</li>"; } ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($d->track_record)): ?>
                        <p style="font-weight:bold; margin-bottom:5px; color:#0d6efd;">Jejak Alumni:</p>
                        <div style="background:#f0f8ff; padding:15px; border-radius:10px; border:1px solid #d0e3ff;">
                            <ul style="list-style:none; padding:0; margin:0;">
                                <?php
                                $records = explode("\n", $d->track_record);
                                foreach ($records as $item) {
                                    $item = trim($item);
                                    if ($item != "") {
                                        echo "<li style='padding:5px 0;'><i class=\"fas fa-user-graduate\" style=\"color:#0d6efd; margin-right:8px;\"></i> " . htmlspecialchars($item) . "</li>";
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- KEJURUAN / PEMINATAN -->
                <?php if(!empty($d->kejuruan)): ?>
                <div class="box-white list-box">
                    <div class="detail-label"><i class="fas fa-cogs"></i> Kejuruan / Peminatan</div>
                    <ul>
                        <?php 
                        $lines = explode("\n", $d->kejuruan); 
                        foreach($lines as $line){ 
                            if(trim($line)!="") echo "<li><i class='fas fa-check-circle'></i> $line</li>"; 
                        } 
                        ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- PROGRAM UNGGULAN -->
                <?php if(!empty($d->program_unggulan)): ?>
                <div class="box-white list-box">
                    <div class="detail-label"><i class="fas fa-star"></i> Program Unggulan</div>
                    <ul>
                        <?php 
                        $lines = explode("\n", $d->program_unggulan); 
                        foreach($lines as $line){ 
                            if(trim($line)!="") echo "<li><i class='fas fa-trophy' style='color:#ffc107;'></i> $line</li>"; 
                        } 
                        ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- FASILITAS & SARANA (UPDATED UI/UX) -->
                <div class="box-white">
                    <div class="detail-label"><i class="fas fa-building"></i> Fasilitas & Sarana</div>
                    
                    <?php if($has_fasilitas_db): ?>
                        <?php 
                        $current_cat = "DUMMY_INIT_VALUE"; 
                        
                        echo "<div class='fasilitas-wrapper'>";
                        
                        while($f = mysqli_fetch_array($q_fasilitas)){
                            // 1. Handle Kategori Kosong -> Masuk ke "Fasilitas Umum"
                            $kat_display = empty($f['kategori']) ? "Fasilitas Umum" : $f['kategori'];

                            // 2. Cek Perubahan Group (Untuk membuat Judul Baru)
                            if($current_cat !== $kat_display){
                                if($current_cat !== "DUMMY_INIT_VALUE") echo "</div>"; // Tutup grid sebelumnya
                                
                                $current_cat = $kat_display;
                                
                                // 3. Tentukan Ikon Berdasarkan Kategori
                                $icon_cat = 'fa-check-circle';
                                if(strpos($current_cat, 'Olahraga') !== false) $icon_cat = 'fa-basketball-ball';
                                elseif(strpos($current_cat, 'Belajar') !== false) $icon_cat = 'fa-book-reader';
                                elseif(strpos($current_cat, 'Ibadah') !== false) $icon_cat = 'fa-mosque';
                                elseif(strpos($current_cat, 'Penunjang') !== false) $icon_cat = 'fa-first-aid';
                                elseif(strpos($current_cat, 'Umum') !== false) $icon_cat = 'fa-building';

                                echo "<div class='kategori-title'><i class='fas $icon_cat'></i> $current_cat</div>";
                                echo "<div class='fasilitas-grid'>"; // Buka grid baru
                            }
                            
                            // 4. Data Modal Lightbox (Cleaned)
                            $img_src = 'assets/img/no-image.jpg';
                            $onclick = '';
                            $cursor = 'default';
                            
                            if(!empty($f['foto']) && file_exists('uploads/fasilitas/'.$f['foto'])){
                                $img_src = 'uploads/fasilitas/'.$f['foto'];
                                $onclick = 'onclick="openModal(this)"';
                                $cursor = 'pointer';
                            }
                            
                            $data_nama = htmlspecialchars($f['nama_fasilitas']);
                            $data_desc = htmlspecialchars($f['deskripsi'] ?? '');
                            $data_tgl  = htmlspecialchars($f['tanggal'] ?? '');
                            $data_loc  = htmlspecialchars($f['tempat'] ?? '');
                            ?>
                            
                            <!-- ITEM FASILITAS (LAZY LOAD) -->
                            <div class="fasilitas-item">
                                <img src="<?php echo $img_src ?>" 
                                     <?php echo $onclick ?> 
                                     style="cursor: <?php echo $cursor ?>;"
                                     alt="<?php echo $data_nama ?>" 
                                     loading="lazy"
                                     data-description="<?php echo $data_desc ?>"
                                     data-tanggal="<?php echo $data_tgl ?>"
                                     data-tempat="<?php echo $data_loc ?>">
                                <span><?php echo $f['nama_fasilitas'] ?></span>
                            </div>

                            <?php
                        }
                        echo "</div></div>"; // Tutup grid terakhir & wrapper
                        ?>
                    
                    <?php else: ?>
                        <!-- Empty State UI -->
                        <div style="text-align: center; padding: 40px; color: #999; background: #f9f9f9; border-radius: 8px;">
                            <i class="fas fa-box-open" style="font-size: 40px; margin-bottom: 15px; color: #ddd;"></i>
                            <p style="margin:0;">Belum ada data fasilitas yang ditambahkan.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- EKSTRAKURIKULER -->
                <?php if(!empty($d->ekstrakurikuler)): ?>
                <div class="box-white list-box">
                    <div class="detail-label"><i class="fas fa-basketball-ball"></i> Ekstrakurikuler</div>
                    <ul><?php $lines = explode("\n", $d->ekstrakurikuler); foreach($lines as $line){ if(trim($line)!="") echo "<li><i class='fas fa-star' style='color:#ffc107;'></i> $line</li>"; } ?></ul>
                </div>
                <?php endif; ?>

                <!-- GALERI FOTO -->
                <div class="box-white" id="galeri-foto">
                    <div class="detail-label"><i class="fas fa-images"></i> Galeri Foto Lainnya</div>
                    <div class="gallery-modern">
                        <?php
                        if(mysqli_num_rows($galeri) > 0){
                            while($foto = mysqli_fetch_array($galeri)){
                        ?>
                            <div class="gallery-card" onclick="openModal(this)" data-description="<?php echo htmlspecialchars($foto['deskripsi']); ?>">
                                <img src="uploads/<?php echo $foto['file_foto'] ?>" alt="<?php echo $foto['judul_foto'] ?>" loading="lazy">
                                <div class="gallery-caption">
                                    <?php echo $foto['judul_foto'] ?>
                                </div>
                            </div>
                        <?php } } else { ?>
                            <!-- Empty State Gallery -->
                             <div style="grid-column: 1 / -1; text-align: center; color: #999; padding: 20px;">
                                <i class="far fa-images" style="font-size: 30px; margin-bottom: 10px; color: #ddd;"></i>
                                <p style="margin:0;">Belum ada foto galeri tambahan.</p>
                             </div>
                        <?php } ?>
                    </div>
                    <?php if($total_halaman_galeri > 1): ?>
                    <div class="pagination-galeri">
                        <?php if($halaman_galeri > 1){ echo '<a href="?id='.$id.'&hal_galeri='.($halaman_galeri-1).'#galeri-foto" class="pg-link">&laquo; Prev</a>'; } ?>
                        <?php for($i=1; $i<=$total_halaman_galeri; $i++): ?>
                            <a href="?id=<?php echo $id ?>&hal_galeri=<?php echo $i ?>#galeri-foto" class="pg-link <?php echo ($i == $halaman_galeri) ? 'active' : '' ?>"><?php echo $i ?></a>
                        <?php endfor; ?>
                        <?php if($halaman_galeri < $total_halaman_galeri){ echo '<a href="?id='.$id.'&hal_galeri='.($halaman_galeri+1).'#galeri-foto" class="pg-link">Next &raquo;</a>'; } ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Maps -->
                <div class="box-white">
                    <div class="detail-label"><i class="fas fa-map-marked-alt"></i> Lokasi Sekolah</div>
                    <?php if($jarak_text != ""): ?>
                    <div style="margin-bottom:15px;">
                        <p style="color:#555; margin-bottom:10px;">Ingin melihat jarak tempuh sebenarnya?</p>
                        <a href="https://www.google.com/maps/dir/?api=1&origin=<?php echo $user_lat ?>,<?php echo $user_lng ?>&destination=<?php echo $d->latitude ?>,<?php echo $d->longitude ?>" target="_blank" class="btn-route">
                            <i class="fas fa-directions"></i> Lihat Rute di Google Maps
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php 
                        $map_src = ($school_has_location) 
                            ? "https://maps.google.com/maps?q=$d->latitude,$d->longitude&z=15&ie=UTF8&iwloc=&output=embed"
                            : "https://maps.google.com/maps?q=".urlencode($d->nama_sekolah.' '.$d->alamat)."&t=&z=15&ie=UTF8&iwloc=&output=embed";
                    ?>
                    <iframe class="map-frame" src="<?php echo $map_src ?>" allowfullscreen loading="lazy"></iframe>
                </div>

            </div>

            <!-- KANAN: SIDEBAR -->
            <div class="detail-sidebar">
                <div class="price-card" style="margin-bottom: 20px; border-top-color: #6610f2;">
                    <h3 style="margin-bottom:15px;">Hubungi Sekolah</h3>
                    <ul class="contact-list">
                        <?php if(!empty($d->no_telpon)): ?>
                            <li><i class="fas fa-phone-alt"></i> <a href="tel:<?php echo $d->no_telpon ?>"><?php echo $d->no_telpon ?></a></li>
                        <?php endif; ?>
                        <?php if(!empty($d->whatsapp)): ?>
                            <li><i class="fab fa-whatsapp" style="font-weight:bold;"></i> <a href="https://wa.me/<?php echo $d->whatsapp ?>" target="_blank">Chat WhatsApp</a></li>
                        <?php endif; ?>
                        <?php if(!empty($d->email)): ?>
                            <li><i class="fas fa-envelope"></i> <a href="mailto:<?php echo $d->email ?>"><?php echo $d->email ?></a></li>
                        <?php endif; ?>
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo $d->alamat ?></li>
                    </ul>
                </div>

                <div class="price-card">
                    <h3>Estimasi Biaya</h3>
                    <div class="price-item">
                        <span class="price-label">Uang Pangkal</span>
                        <span class="price-val" style="color: #dc3545;">
                            <?php if(isset($_SESSION['uid_ortu'])): ?> Rp <?php echo number_format($d->biaya_masuk, 0, ',', '.') ?>
                            <?php else: ?> <span class="blur-price">Rp 10.000.000</span> <?php endif; ?>
                        </span>
                    </div>
                    <div class="price-item" style="border:none;">
                        <span class="price-label">SPP Bulanan</span>
                        <span class="price-val" style="color: #198754;">
                            <?php if(isset($_SESSION['uid_ortu'])): ?> Rp <?php echo number_format($d->biaya_bulanan, 0, ',', '.') ?>
                            <?php else: ?> <span class="blur-price">Rp 500.000</span> <div class="login-to-view"><i class="fas fa-lock"></i> Login untuk melihat</div> <?php endif; ?>
                        </span>
                    </div>
                    <?php if(isset($_SESSION['uid_ortu'])): ?>
                        <a href="daftar.php?id=<?php echo $d->id_sekolah ?>" class="btn-daftar-big"><i class="fas fa-edit"></i> Daftar Sekarang</a>
                    <?php else: ?>
                        <!-- SweetAlert Trigger -->
                        <button onclick="loginAlert()" class="btn-daftar-big" style="background:#6c757d; border:none; width:100%; cursor:pointer;">Login untuk Daftar</button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    
    <!-- MOBILE FLOATING ACTION BUTTON (New UX) -->
    <div class="fab-container">
        <?php if(isset($_SESSION['uid_ortu'])): ?>
            <a href="daftar.php?id=<?php echo $d->id_sekolah ?>" class="fab-btn"><i class="fas fa-edit"></i> Daftar</a>
        <?php else: ?>
            <button onclick="loginAlert()" class="fab-btn" style="border:none; cursor:pointer;"><i class="fas fa-lock"></i> Login</button>
        <?php endif; ?>
    </div>

    <!-- LIGHTBOX MODAL -->
    <div id="myModal" class="lightbox">
        <div class="lightbox-wrapper">
            <div class="lightbox-container">
                <span class="close" onclick="document.getElementById('myModal').style.display='none'">&times;</span>
                <div class="lightbox-image-area">
                    <img class="lightbox-content" id="img01">
                </div>
                <div class="lightbox-sidebar">
                    <div class="lightbox-meta-label">DETAIL INFO</div>
                    <h3 id="caption">Judul Foto</h3>
                    
                    <div class="meta-info-box" id="meta-details">
                        <div class="meta-item" id="view-tanggal"></div>
                        <div class="meta-item" id="view-tempat"></div>
                    </div>

                    <div id="description">Deskripsi foto...</div>
                    <div class="lightbox-footer">
                        <i class="fas fa-camera"></i> EduSearch Galeri
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // SweetAlert for Login
        function loginAlert(){
            Swal.fire({
                title: 'Akses Terbatas',
                text: 'Silakan login terlebih dahulu untuk mendaftar atau melihat biaya sekolah secara lengkap.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Login Sekarang',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login_user.php';
                }
            })
        }

        // Script Accordion (Visi Misi)
        var acc = document.getElementsByClassName("accordion");
        var i;
        for (i = 0; i < acc.length; i++) {
            acc[i].addEventListener("click", function() {
                this.classList.toggle("active");
                var panel = this.nextElementSibling;
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = null;
                } else {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                } 
            });
        }

        // FUNGSI UTAMA MODAL (Smart Handling)
        function openModal(element) {
            var modal = document.getElementById("myModal");
            modal.style.display = "block";
            modal.style.opacity = "0";
            setTimeout(function(){ modal.style.opacity = "1"; }, 50);

            var src = "";
            var cap = "";
            var desc = "";
            var tgl = "";
            var loc = "";

            if (element.tagName === 'IMG') {
                src = element.src;
                cap = element.alt;
                desc = element.getAttribute('data-description') || "";
                tgl = element.getAttribute('data-tanggal') || "";
                loc = element.getAttribute('data-tempat') || "";
            } else {
                // Untuk Galeri Card
                var img = element.querySelector('img');
                src = img.src;
                cap = element.querySelector('.gallery-caption').innerText;
                desc = element.getAttribute('data-description') || "";
            }
            
            // Update DOM
            document.getElementById("img01").src = src;
            document.getElementById("caption").innerHTML = cap;
            
            // Handle Description
            var descEl = document.getElementById("description");
            descEl.innerHTML = desc ? desc : "<i>Tidak ada deskripsi.</i>";

            // Handle Tanggal & Tempat
            var metaDiv = document.getElementById("meta-details");
            var hasMeta = false;

            if(tgl && tgl !== "0000-00-00"){
                document.getElementById("view-tanggal").innerHTML = '<i class="far fa-calendar-alt"></i> ' + tgl;
                document.getElementById("view-tanggal").style.display = "flex";
                hasMeta = true;
            } else {
                document.getElementById("view-tanggal").style.display = "none";
            }

            if(loc){
                document.getElementById("view-tempat").innerHTML = '<i class="fas fa-map-marker-alt"></i> ' + loc;
                document.getElementById("view-tempat").style.display = "flex";
                hasMeta = true;
            } else {
                document.getElementById("view-tempat").style.display = "none";
            }

            metaDiv.style.display = hasMeta ? "block" : "none";
        }
        
        window.onclick = function(event) {
            var modal = document.getElementById("myModal");
            var wrapper = document.querySelector(".lightbox-wrapper");
            if (event.target == modal || event.target == wrapper) {
                modal.style.opacity = "0";
                setTimeout(function(){ modal.style.display = "none"; }, 300);
            }
        }
    </script>

</body>
</html>