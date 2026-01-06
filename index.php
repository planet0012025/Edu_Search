<?php
/**
 * HALAMAN UTAMA (LANDING PAGE) - EduSearch
 * Update: Re-order Layout (CTA dipindah ke atas Testimoni)
 */

session_start(); 
include 'config/koneksi.php';

// ============================================================
// 1. LOGIKA STATISTIK
// ============================================================
if (isset($koneksi)) {
    $q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_sekolah");
    $d_total = mysqli_fetch_assoc($q_total);
    $total_sekolah = $d_total['total'];

    $q_negeri = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_sekolah WHERE status_sekolah='Negeri'");
    $negeri = mysqli_fetch_assoc($q_negeri)['total'];

    $q_swasta = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_sekolah WHERE status_sekolah='Swasta'");
    $swasta = mysqli_fetch_assoc($q_swasta)['total'];

    $q_boarding = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_sekolah WHERE status_sekolah='Boarding School'");
    $boarding = mysqli_fetch_assoc($q_boarding)['total'];
} else {
    $total_sekolah = 0; $negeri = 0; $swasta = 0; $boarding = 0;
}

// ============================================================
// 2. LOGIKA PENCARIAN & QUIZ
// ============================================================
$is_search_active = (isset($_GET['q']) && $_GET['q'] != '') || 
                    (isset($_GET['jenjang']) && $_GET['jenjang'] != '') || 
                    (isset($_GET['status']) && $_GET['status'] != '');

$is_quiz_active = isset($_GET['quiz']);

// Ambil nilai quiz
$q_biaya_val = isset($_GET['q_biaya']) ? $_GET['q_biaya'] : '';
$q_kurikulum_val = isset($_GET['q_kurikulum']) ? $_GET['q_kurikulum'] : '';
$q_disiplin_val = isset($_GET['q_disiplin']) ? $_GET['q_disiplin'] : '';

// Validasi Wilayah
$selected_wilayah = '';
if(isset($_GET['wilayah']) && $_GET['wilayah'] != ''){
    $selected_wilayah = $_GET['wilayah']; 
} elseif(isset($_GET['q_wilayah']) && $_GET['q_wilayah'] != '' && $_GET['q_wilayah'] != 'Semua'){
    $selected_wilayah = $_GET['q_wilayah']; 
}

// ============================================================
// 3. LOGIKA TESTIMONI
// ============================================================
$batas_testi = 3;
$halaman_testi = isset($_GET['hal_testi']) ? (int)$_GET['hal_testi'] : 1;
$halaman_awal_testi = ($halaman_testi > 1) ? ($halaman_testi * $batas_testi) - $batas_testi : 0;

$q_count_testi = mysqli_query($koneksi, "SELECT id_testimoni FROM tb_testimoni");
$total_testi = mysqli_num_rows($q_count_testi);
$total_halaman_testi = ceil($total_testi / $batas_testi);

$q_testimoni = mysqli_query($koneksi, "
    SELECT t.*, o.nama_lengkap, o.tempat_lahir 
    FROM tb_testimoni t 
    JOIN tb_orang_tua o ON t.id_ortu = o.id_ortu 
    ORDER BY t.tgl_posting DESC 
    LIMIT $halaman_awal_testi, $batas_testi
");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduSearch - Temukan Sekolah Impian</title>
    
    <link rel="stylesheet" href="assets/css/style.css?v=28">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .search-box { max-width: 1100px !important; flex-wrap: wrap; gap: 10px; padding: 15px; } 
        .search-box input, .search-box select { flex: 1; min-width: 140px; }
        .search-box button { flex: 0 0 auto; padding: 12px 30px; }
        .user-link:hover { text-decoration: underline !important; }

        /* --- CSS TESTIMONI MODERN --- */
        .testi-section { padding: 80px 0 60px; background: #f9fbff; border-top: 1px solid #eee; }
        .testi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px; }
        .testi-card { 
            background: white; padding: 30px; border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;
            transition: 0.3s; position: relative; display: flex; flex-direction: column;
        }
        .testi-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(13, 110, 253, 0.1); border-color: #0d6efd; }
        .quote-icon { font-size: 40px; color: #e7f1ff; position: absolute; top: 20px; right: 20px; }
        
        .testi-text { font-size: 15px; color: #555; line-height: 1.7; font-style: italic; margin-bottom: 20px; flex-grow: 1; }
        .testi-user { display: flex; align-items: center; gap: 15px; border-top: 1px solid #f9f9f9; padding-top: 15px; }
        .user-avatar { width: 50px; height: 50px; background: #0d6efd; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; }
        .user-info h4 { margin: 0; font-size: 16px; color: #333; }
        .user-info p { margin: 0; font-size: 12px; color: #888; }
        .stars { color: #ffc107; font-size: 14px; margin-bottom: 10px; }

        .pg-testi { display: flex; justify-content: center; gap: 8px; margin-top: 40px; }
        .pg-testi a { padding: 8px 14px; border-radius: 50px; border: 1px solid #ddd; color: #555; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .pg-testi a:hover, .pg-testi a.active { background: #0d6efd; color: white; border-color: #0d6efd; }

        /* --- CSS CONTACT SECTION --- */
        .contact-section { padding: 80px 0; background: #fff; position: relative; overflow: hidden; }
        .contact-wrapper {
            display: grid; grid-template-columns: 1fr 1.5fr; gap: 0; 
            background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            overflow: hidden; margin-top: 50px; border: 1px solid #f0f0f0;
        }
        .contact-info-box { background: linear-gradient(135deg, #0d6efd, #0056b3); padding: 60px 40px; color: white; position: relative; overflow: hidden; }
        .contact-info-box::before { content: ''; position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .contact-info-box::after { content: ''; position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .contact-info-box h3 { font-size: 28px; margin-bottom: 20px; font-weight: 700; }
        .contact-info-box p { opacity: 0.9; line-height: 1.6; margin-bottom: 40px; font-size: 15px; }
        .contact-detail-item { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 35px; }
        .c-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; transition: 0.3s; backdrop-filter: blur(5px); }
        .contact-detail-item:hover .c-icon { background: white; color: #0d6efd; transform: scale(1.1); }
        .c-text h5 { margin: 0 0 5px; font-size: 18px; font-weight: 600; }
        .c-text span { font-size: 14px; opacity: 0.85; }
        .contact-form-box { padding: 60px 50px; background: #fff; }
        .form-title { font-size: 26px; font-weight: 700; color: #333; margin-bottom: 10px; }
        .form-subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        .input-group-modern { position: relative; margin-bottom: 25px; }
        .input-modern { width: 100%; padding: 15px 20px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 15px; transition: 0.3s; background: #fcfcfc; outline: none; font-family: inherit; }
        .input-modern:focus { border-color: #0d6efd; background: #fff; box-shadow: 0 5px 20px rgba(13, 110, 253, 0.1); }
        .label-modern { position: absolute; left: 20px; top: 17px; color: #999; font-size: 15px; transition: 0.3s; pointer-events: none; background: transparent; }
        .input-modern:focus ~ .label-modern, .input-modern:not(:placeholder-shown) ~ .label-modern { top: -10px; left: 15px; font-size: 12px; color: #0d6efd; background: white; padding: 0 5px; font-weight: 600; }
        .btn-send { width: 100%; padding: 18px; background: #0d6efd; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s; box-shadow: 0 10px 25px rgba(13, 110, 253, 0.25); }
        .btn-send:hover { background: #0b5ed7; transform: translateY(-3px); box-shadow: 0 15px 30px rgba(13, 110, 253, 0.35); }

        /* --- CSS CTA MITRA MODERN (UPDATE - ALIGN LEFT) --- */
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white;
            position: relative;
            overflow: hidden;
            margin-top: 60px; /* Jarak atas */
        }
        /* Dekorasi Background */
        .cta-section::before {
            content: ''; position: absolute; top: -50%; left: -10%; width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%; z-index: 1;
        }
        .cta-content-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 50px;
            position: relative;
            z-index: 2;
            text-align: left; 
        }
        .cta-text {
            flex: 1;
            text-align: left; 
        }
        .cta-text h2 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        .cta-text p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.6;
            max-width: 500px;
        }
        .btn-cta-white {
            display: inline-block;
            padding: 15px 35px;
            background: #fff;
            color: #0d6efd;
            font-weight: 700;
            border-radius: 50px;
            text-decoration: none;
            font-size: 16px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .btn-cta-white:hover {
            background: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        /* Animasi Gambar */
        .cta-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .cta-image img {
            max-width: 100%;
            height: auto;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        @media (max-width: 900px) {
            .contact-wrapper { grid-template-columns: 1fr; }
            .cta-content-wrapper { flex-direction: column-reverse; text-align: center; }
            .cta-text { text-align: center; } /* Tengah di mobile */
            .cta-text p { margin: 0 auto 30px; }
            .cta-image img { max-width: 80%; margin-bottom: 30px; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header>
        <div class="container header-content">
            <div style="display:flex; align-items:center; gap:20px;">
                <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduSearch</a>
                <a href="cek_status.php" style="color:#555; font-weight:600; font-size:14px; text-decoration:none;">
                    <i class="fas fa-clipboard-check"></i> Cek Status Daftar
                </a>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <?php if(isset($_SESSION['uid_ortu'])): ?>
                    <div style="display:flex; align-items:center; gap:10px; background: #e7f1ff; padding: 8px 15px; border-radius: 50px;">
                        <a href="dashboard_user.php" class="user-link" style="font-weight:600; color:#0d6efd; font-size:14px; text-decoration:none; display:flex; align-items:center; gap:5px;">
                            <i class="fas fa-user-circle"></i> Halo, <?php echo htmlspecialchars(substr($_SESSION['nama_ortu'], 0, 10)); ?>
                        </a>
                        <a href="logout_user.php" title="Keluar" style="color:#dc3545; margin-left:5px;"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                <?php else: ?>
                    <a href="login_user.php" class="btn-login" style="background:#0d6efd; color:white; border:none;"><i class="fas fa-sign-in-alt"></i> Masuk</a>
                    <a href="mitra/login.php" class="btn-login" style="font-size:14px;"><i class="fas fa-user-lock"></i> Sekolah</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <div class="hero">
        <div class="container">
            <h1>Masa Depan Cerah Dimulai Di Sini</h1>
            <p>Ekosistem Digital Pencarian & Kolaborasi Sekolah terlengkap dengan informasi transparan dan pendaftaran mudah.</p>
            
            <form action="index.php" method="GET" class="search-box" style="margin-bottom: 20px;">
                <?php if($is_quiz_active): ?>
                    <input type="hidden" name="quiz" value="1">
                    <input type="hidden" name="q_biaya" value="<?php echo $q_biaya_val ?>">
                    <input type="hidden" name="q_kurikulum" value="<?php echo $q_kurikulum_val ?>">
                    <input type="hidden" name="q_disiplin" value="<?php echo $q_disiplin_val ?>">
                <?php endif; ?>

                <input type="text" name="q" placeholder="Cari nama sekolah..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                
                <select name="wilayah">
                    <option value="">Semua Wilayah</option>
                    <option value="Jakarta" <?php if($selected_wilayah == 'Jakarta') echo 'selected'; ?>>Jakarta</option>
                    <option value="Bogor" <?php if($selected_wilayah == 'Bogor') echo 'selected'; ?>>Bogor</option>
                    <option value="Depok" <?php if($selected_wilayah == 'Depok') echo 'selected'; ?>>Depok</option>
                    <option value="Tangerang" <?php if($selected_wilayah == 'Tangerang') echo 'selected'; ?>>Tangerang</option>
                    <option value="Bekasi" <?php if($selected_wilayah == 'Bekasi') echo 'selected'; ?>>Bekasi</option>
                    <option value="Lainnya" <?php if($selected_wilayah == 'Lainnya') echo 'selected'; ?>>Lainnya</option>
                </select>

                <select name="jenjang">
                    <option value="">Semua Jenjang</option>
                    <option value="SD" <?php if(isset($_GET['jenjang']) && $_GET['jenjang'] == 'SD') echo 'selected'; ?>>SD</option>
                    <option value="SMP" <?php if(isset($_GET['jenjang']) && $_GET['jenjang'] == 'SMP') echo 'selected'; ?>>SMP</option>
                    <option value="SMA" <?php if(isset($_GET['jenjang']) && $_GET['jenjang'] == 'SMA') echo 'selected'; ?>>SMA</option>
                    <option value="SMK" <?php if(isset($_GET['jenjang']) && $_GET['jenjang'] == 'SMK') echo 'selected'; ?>>SMK</option>
                </select>

                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="Negeri" <?php if(isset($_GET['status']) && $_GET['status'] == 'Negeri') echo 'selected'; ?>>Negeri</option>
                    <option value="Swasta" <?php if(isset($_GET['status']) && $_GET['status'] == 'Swasta') echo 'selected'; ?>>Swasta</option>
                    <option value="Boarding School" <?php if(isset($_GET['status']) && $_GET['status'] == 'Boarding School') echo 'selected'; ?>>Boarding</option>
                </select>

                <button type="submit"><i class="fas fa-search"></i> Cari</button>
            </form>
            
            <p style="font-size: 20px; margin-top: 10px;">
                Ayo Gunakan Fitur Rekomendasi Cerdas Kami: 
                <a href="assessment.php" style="color: #ffc107; font-weight: bold; text-decoration: underline;"><i class="fas fa-question-circle"></i> Kuesioner</a>
            </p>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="container">
        <div class="stats-container">
            <div class="stat-box s-total"><div class="stat-icon"><i class="fas fa-school"></i></div><div class="stat-number"><?php echo $total_sekolah ?></div><div class="stat-label">Total Sekolah</div></div>
            <div class="stat-box s-negeri"><div class="stat-icon"><i class="fas fa-building"></i></div><div class="stat-number"><?php echo $negeri ?></div><div class="stat-label">Sekolah Negeri</div></div>
            <div class="stat-box s-swasta"><div class="stat-icon"><i class="fas fa-landmark"></i></div><div class="stat-number"><?php echo $swasta ?></div><div class="stat-label">Sekolah Swasta</div></div>
            <div class="stat-box s-boarding"><div class="stat-icon"><i class="fas fa-mosque"></i></div><div class="stat-number"><?php echo $boarding ?></div><div class="stat-label">Boarding School</div></div>
        </div>
    </div>

    <!-- FEATURES -->
    <div class="container">
        <div class="features-section">
            <div class="feature-box"><div class="icon-circle"><i class="fas fa-search-location"></i></div><h3>Pencarian Mudah</h3><p>Temukan sekolah terdekat dengan filter jenjang dan lokasi.</p></div>
            <div class="feature-box"><div class="icon-circle"><i class="fas fa-clipboard-list"></i></div><h3>Info Transparan</h3><p>Cek biaya, fasilitas, dan kurikulum secara detail.</p></div>
            <div class="feature-box"><div class="icon-circle"><i class="fas fa-bolt"></i></div><h3>Daftar Cepat</h3><p>Proses pendaftaran online langsung ke sekolah.</p></div>
        </div>

        <!-- HASIL PENCARIAN -->
        <div class="section-header" id="hasil-pencarian">
            <?php if($is_quiz_active): ?>
                <h2 class="section-title" style="color: #198754;"><i class="fas fa-lightbulb"></i> Rekomendasi Paling Cocok!</h2>
                <p style="color: #666; margin-top: 10px; font-weight: bold;">
                    Diurutkan berdasarkan kecocokan skor preferensi Anda.
                    <?php if($selected_wilayah != '') echo " (Area: $selected_wilayah)"; ?>
                </p>
            <?php else: ?>
                <h2 class="section-title">Rekomendasi Sekolah</h2>
                <p style="color: #666; margin-top: 10px;">Pilihan terbaik untuk masa depan buah hati Anda</p>
            <?php endif; ?>
        </div>
        
        <div class="grid-sekolah">
            <?php
            // LOGIKA QUERY SAMA SEPERTI SEBELUMNYA (DIPERSINGKAT UNTUK FOKUS KE FITUR BARU)
            $where = "WHERE 1=1"; 
            $url_param = "";
            if(isset($_GET['q']) && $_GET['q'] != ''){ $key = mysqli_real_escape_string($koneksi, $_GET['q']); $where .= " AND (nama_sekolah LIKE '%$key%' OR alamat LIKE '%$key%')"; $url_param .= "&q=" . urlencode($_GET['q']); }
            if($selected_wilayah != '' && $selected_wilayah != 'Semua'){ $w = mysqli_real_escape_string($koneksi, $selected_wilayah); $where .= " AND wilayah = '$w'"; $url_param .= "&wilayah=$w"; }
            if(isset($_GET['jenjang']) && $_GET['jenjang'] != ''){ $jenjang = mysqli_real_escape_string($koneksi, $_GET['jenjang']); $where .= " AND jenjang = '$jenjang'"; $url_param .= "&jenjang=$jenjang"; }
            if(isset($_GET['status']) && $_GET['status'] != ''){ $status = mysqli_real_escape_string($koneksi, $_GET['status']); $where .= " AND status_sekolah = '$status'"; $url_param .= "&status=$status"; }

            $order_by = "id_sekolah DESC"; 
            if($is_quiz_active){
                $biaya_q = (int)$q_biaya_val; $kurikulum_q = (int)$q_kurikulum_val; $disiplin_q = (int)$q_disiplin_val;
                $order_by = "ABS(biaya_score - $biaya_q) + ABS(kurikulum_score - $kurikulum_q) + ABS(kedisiplinan_score - $disiplin_q) ASC, id_sekolah DESC";
                $url_param .= "&quiz=1&q_biaya=$biaya_q&q_kurikulum=$kurikulum_q&q_disiplin=$disiplin_q";
            }

            $batas = 6; $halaman = isset($_GET['hal']) ? (int)$_GET['hal'] : 1; $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
            $data_count = mysqli_query($koneksi, "SELECT id_sekolah FROM tb_sekolah $where");
            $jumlah_data = mysqli_num_rows($data_count);
            $total_halaman = ceil($jumlah_data / $batas);
            $query = mysqli_query($koneksi, "SELECT * FROM tb_sekolah $where ORDER BY $order_by LIMIT $halaman_awal, $batas");

            if(mysqli_num_rows($query) > 0){
                while($data = mysqli_fetch_array($query)){
                    $persen_cocok = 0; $persen_color = '';
                    if($is_quiz_active){
                        $diff = abs($data['biaya_score'] - $q_biaya_val) + abs($data['kurikulum_score'] - $q_kurikulum_val) + abs($data['kedisiplinan_score'] - $q_disiplin_val);
                        $persen_cocok = round(100 - ($diff / 12 * 100));
                        $persen_color = ($persen_cocok > 80) ? '#198754' : (($persen_cocok > 60) ? '#fd7e14' : '#dc3545');
                    }
            ?>
            <div class="card">
                <div class="card-img-wrap">
                    <span class="badge-jenjang"><?php echo $data['jenjang'] ?></span>
                    <img src="uploads/<?php echo $data['foto_logo'] ?>" class="card-img" onerror="this.src='assets/img/no-image.jpg';">
                </div>
                <div class="card-body">
                    <div class="card-title"><?php echo $data['nama_sekolah'] ?></div>
                    <?php if($is_quiz_active): ?><div style="font-size: 16px; font-weight: bold; color: <?php echo $persen_color ?>; margin-bottom: 10px;"><i class="fas fa-heart"></i> Kecocokan: <?php echo $persen_cocok ?>%</div><?php endif; ?>
                    <div class="card-price">Rp <?php echo number_format($data['biaya_bulanan'], 0, ',', '.') ?> <span style="font-size:12px;font-weight:normal;color:#888;">/bln</span></div>
                    <div class="card-info"><i class="fas fa-star" style="color:#ffc107;"></i> Akreditasi <?php echo (!empty($data['akreditasi'])) ? $data['akreditasi'] : '-'; ?> | <span style="color:#0d6efd; font-weight:600;"><?php echo $data['status_sekolah'] ?></span></div>
                    <div class="card-info"><i class="fas fa-map-marker-alt" style="color:#dc3545;"></i> <?php echo substr($data['alamat'], 0, 35) ?>...</div>
                    <a href="detail.php?id=<?php echo $data['id_sekolah'] ?>" class="btn-detail">Detail & Daftar <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <?php } } else { echo "<div style='grid-column: 1/-1; text-align: center; padding: 50px; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);'><h3>Data tidak ditemukan</h3><p style='color:#666;'>Tidak ada sekolah yang cocok dengan filter/kriteria Anda.</p><br><a href='index.php' class='btn-detail' style='width:auto; display:inline-block; background:#6c757d;'>Reset Filter</a></div>"; } ?>
        </div>

        <?php if($jumlah_data > $batas) { ?>
        <div class="pagination">
            <?php if($halaman > 1){ ?><a href="?hal=<?php echo ($halaman-1) . $url_param ?>#hasil-pencarian" class="page-link page-nav">Prev</a><?php } ?>
            <?php for($x=1;$x<=$total_halaman;$x++){ ?><a href="?hal=<?php echo $x . $url_param ?>#hasil-pencarian" class="page-link <?php echo ($x == $halaman) ? 'active' : '' ?>"><?php echo $x ?></a><?php } ?>
            <?php if($halaman < $total_halaman){ ?><a href="?hal=<?php echo ($halaman+1) . $url_param ?>#hasil-pencarian" class="page-link page-nav">Next</a><?php } ?>
        </div>
        <?php } ?>
    </div>

    <!-- CTA SECTION (MITRA - NEW DESIGN) - DIPINDAHKAN KE SINI -->
    <div class="cta-section">
        <div class="container">
            <div class="cta-content-wrapper">
                <div class="cta-text">
                    <h2>Apakah Anda Pengelola Sekolah?</h2>
                    <p>Bergabunglah dengan ribuan sekolah lainnya dan dapatkan lebih banyak siswa baru melalui platform digital kami. Tingkatkan visibilitas sekolah Anda sekarang juga.</p>
                    <a href="mitra/register.php" class="btn-cta-white">Bergabung Sebagai Mitra</a>
                </div>
                <div class="cta-image">
                    <!-- Ganti URL gambar dengan ilustrasi sekolah (SVG/PNG) -->
                    <img src="uploads/2D SEKOLAH.png" alt="Ilustrasi Sekolah">
                </div>
            </div>
        </div>
    </div>

    <!-- TESTIMONI SECTION -->
    <?php if($total_testi > 0): ?>
    <div class="testi-section" id="testimoni">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" style="text-align:center; display:block;">Kesan dan Pesan</h2>
                <p style="text-align:center; color:#666; margin-top:10px;">Pengalaman nyata dari orang tua siswa yang telah menggunakan EduSearch.</p>
            </div>

            <div class="testi-grid">
                <?php while($t = mysqli_fetch_array($q_testimoni)){ ?>
                <div class="testi-card">
                    <div class="quote-icon"><i class="fas fa-quote-right"></i></div>
                    <div class="stars">
                        <?php for($i=0; $i<$t['rating']; $i++){ echo '<i class="fas fa-star"></i>'; } ?>
                        <?php for($i=$t['rating']; $i<5; $i++){ echo '<i class="far fa-star" style="color:#ddd;"></i>'; } ?>
                    </div>
                    <p class="testi-text">"<?php echo nl2br($t['isi_testimoni']) ?>"</p>
                    <div class="testi-user">
                        <div class="user-avatar"><?php echo strtoupper(substr($t['nama_lengkap'], 0, 1)) ?></div>
                        <div class="user-info">
                            <h4><?php echo $t['nama_lengkap'] ?></h4>
                            <p><?php echo !empty($t['tempat_lahir']) ? $t['tempat_lahir'] : 'Orang Tua Murid' ?></p>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>

            <!-- Pagination Testimoni -->
            <?php if($total_halaman_testi > 1): ?>
            <div class="pg-testi">
                <?php for($i=1; $i<=$total_halaman_testi; $i++){ ?>
                    <a href="?hal_testi=<?php echo $i ?>#testimoni" class="<?php echo ($i == $halaman_testi) ? 'active' : '' ?>"><?php echo $i ?></a>
                <?php } ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======= CONTACT SECTION (BARU) ======= -->
    <div class="contact-section" id="contact">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" style="text-align:center; display:block;">Hubungi Kami</h2>
                <p style="text-align:center; color:#666; margin-top:10px;">Punya pertanyaan atau butuh bantuan? Tim kami siap membantu Anda.</p>
            </div>

            <div class="contact-wrapper">
                <!-- Kiri: Info Kontak -->
                <div class="contact-info-box">
                    <h3>Get in Touch</h3>
                    <p>Silakan hubungi kami melalui kontak di bawah ini atau kirim pesan melalui formulir.</p>
                    
                    <div class="contact-detail-item">
                        <div class="c-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="c-text">
                            <h5>Lokasi Kantor</h5>
                            <span>Jl. Lapangan Merah No.83 11, RT.11/RW.7, Srengseng Sawah, Kec. Jagakarsa, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12630</span>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="c-icon"><i class="fas fa-envelope"></i></div>
                        <div class="c-text">
                            <h5>Email Kami</h5>
                            <span>ikramacademy.id@gmail.com</span>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="c-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="c-text">
                            <h5>Telepon / WhatsApp</h5>
                            <span>0851-8319-8360</span>
                        </div>
                    </div>
                </div>

                <!-- Kanan: Form Pesan -->
                <div class="contact-form-box">
                    <div class="form-title">Kirim Pesan</div>
                    <div class="form-subtitle">Kami akan membalas pesan Anda sesegera mungkin.</div>
                    
                    <form action="#" method="POST"> 
                        <div class="input-group-modern">
                            <input type="text" name="nama" class="input-modern" placeholder=" " required>
                            <label class="label-modern">Nama Lengkap</label>
                        </div>

                        <div class="input-group-modern">
                            <input type="email" name="email" class="input-modern" placeholder=" " required>
                            <label class="label-modern">Alamat Email</label>
                        </div>

                        <div class="input-group-modern">
                            <input type="text" name="subject" class="input-modern" placeholder=" " required>
                            <label class="label-modern">Subjek Pesan</label>
                        </div>

                        <div class="input-group-modern">
                            <textarea name="pesan" class="input-modern" style="height:150px;" placeholder=" " required></textarea>
                            <label class="label-modern">Isi Pesan Anda</label>
                        </div>

                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i> Kirim Pesan Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ======= END CONTACT SECTION ======= -->

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col"><h3>EduSearch</h3><p>Platform pencarian sekolah terbaik.</p></div>
                <div class="footer-col"><h3>Navigasi</h3><ul><li><a href="index.php">Beranda</a></li><li><a href="cek_status.php">Cek Status</a></li></ul></div>
                <div class="footer-col"><h3>Hubungi Kami</h3><ul><li><i class="fas fa-envelope"></i> ikramacademy.id@gmail.com</li><li><i class="fas fa-phone"></i> 0851-8319-8360</li></ul></div>
            </div>
            <div class="copyright">© <?php echo date('Y'); ?> EduSearch. All Rights Reserved.</div>
        </div>
    </footer>

    <script>
        const params = new URLSearchParams(window.location.search);
        if ((params.has('q') || params.has('jenjang') || params.has('status') || params.has('wilayah') || params.has('quiz')) && !params.has('hal')) {
            const el = document.getElementById('hasil-pencarian');
            if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        }
    </script>
</body>
</html>