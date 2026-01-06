<?php 
session_start(); 
include 'config/koneksi.php'; 

// --- LOGIKA PENGAMAN: CEK LOGIN ---
if(!isset($_SESSION['uid_ortu'])){
    echo "<script>
            alert('Maaf, Anda harus Login atau Daftar akun terlebih dahulu untuk mengakses Quiz ini.');
            window.location='login_user.php?redirect=quiz';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asesmen Sekolah Ideal - EduSearch</title>
    
    <!-- Style Utama -->
    <link rel="stylesheet" href="assets/css/style.css?v=28">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; }
        
        .main-wrapper {
            padding-top: 40px;
            padding-bottom: 60px;
            min-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .quiz-container { 
            width: 100%; max-width: 900px; background: white; padding: 50px; 
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            position: relative; z-index: 1;
        }

        .quiz-header { text-align: center; margin-bottom: 40px; border-bottom: 2px dashed #eee; padding-bottom: 20px; }
        .quiz-header h2 { color: #0d6efd; margin-bottom: 10px; font-size: 28px; }

        /* Styling Group Soal */
        .group-title {
            font-size: 20px; color: #333; font-weight: 800; margin-bottom: 20px;
            padding-left: 15px; border-left: 5px solid #0d6efd;
            display: flex; align-items: center; gap: 10px;
        }
        
        .question-box { 
            margin-bottom: 30px; padding: 25px; border: 1px solid #e9ecef; 
            border-radius: 12px; background: #fcfcfc; transition: 0.3s;
        }
        .question-box:hover { border-color: #0d6efd; background: #fff; box-shadow: 0 5px 15px rgba(13, 110, 253, 0.05); }

        .question-box label.q-title { font-weight: 700; margin-bottom: 15px; display: block; color: #333; font-size: 1.1rem; }

        .options-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .options-grid input[type="radio"] { display: none; }
        .options-grid label { 
            display: inline-block; padding: 10px 18px; border: 2px solid #dee2e6; 
            border-radius: 50px; cursor: pointer; transition: all 0.2s ease; 
            font-size: 13px; font-weight: 500; color: #555; background: #fff; flex: 1 1 auto; text-align: center;
        }

        .options-grid input[type="radio"]:checked + label {
            background: #0d6efd; color: white; border-color: #0d6efd;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); transform: translateY(-2px);
        }

        .btn-submit-quiz { 
            width: 100%; padding: 18px; background: #198754; color: white; 
            border: none; border-radius: 12px; font-weight: 700; font-size: 18px; 
            margin-top: 30px; cursor: pointer; transition: 0.3s;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }
        .btn-submit-quiz:hover { background: #157347; transform: translateY(-2px); }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="container header-content">
            <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduSearch</a>
            <div style="display:flex; gap:15px; align-items:center;">
                <span style="font-weight:600; color:#555;">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['nama_ortu']; ?>
                </span>
                <a href="index.php" style="color:#dc3545; font-weight:600; text-decoration:none; font-size:14px;">Batal</a>
            </div>
        </div>
    </header>

    <!-- Wrapper Utama -->
    <div class="main-wrapper">
        <div class="quiz-container">
            <div class="quiz-header">
                <h2><i class="fas fa-clipboard-check"></i> Asesmen Kebutuhan Sekolah</h2>
                <p style="color: #666;">Jawab pertanyaan berikut untuk menemukan sekolah yang paling sesuai dengan kriteria dan lokasi Anda.</p>
            </div>

            <form action="index.php" method="GET">
                <input type="hidden" name="quiz" value="1">
                
                <!-- BAGIAN 0: PREFERENSI WILAYAH (BARU) -->
                <div class="group-title"><i class="fas fa-map-marked-alt"></i> 0. Preferensi Wilayah (Jabodetabek)</div>
                <div class="question-box" style="background: #e7f1ff; border-color: #b6d4fe;">
                    <label class="q-title" style="color: #0d6efd;">Di area mana Anda mencari sekolah?</label>
                    <div class="options-grid">
                        <input type="radio" id="w_jkt" name="q_wilayah" value="Jakarta" required><label for="w_jkt">DKI Jakarta</label>
                        <input type="radio" id="w_bgr" name="q_wilayah" value="Bogor"><label for="w_bgr">Bogor</label>
                        <input type="radio" id="w_dpk" name="q_wilayah" value="Depok"><label for="w_dpk">Depok</label>
                        <input type="radio" id="w_tng" name="q_wilayah" value="Tangerang"><label for="w_tng">Tangerang</label>
                        <input type="radio" id="w_bks" name="q_wilayah" value="Bekasi"><label for="w_bks">Bekasi</label>
                        <input type="radio" id="w_all" name="q_wilayah" value="Semua"><label for="w_all">Semua Area</label>
                    </div>
                </div>

                <!-- BAGIAN 1: KUALITAS PENDIDIKAN (Kurikulum) -->
                <div class="group-title"><i class="fas fa-book-reader"></i> 1. Kualitas Pendidikan & Kurikulum</div>
                <div class="question-box">
                    <label class="q-title">Seberapa ketat kurikulum dan beban akademik yang ideal untuk anak Anda?</label>
                    <div class="options-grid">
                        <input type="radio" id="q_kur_1" name="q_kurikulum" value="1" required><label for="q_kur_1">Sangat Santai (Fokus Minat Bakat)</label>
                        <input type="radio" id="q_kur_2" name="q_kurikulum" value="2"><label for="q_kur_2">Fleksibel & Kreatif</label>
                        <input type="radio" id="q_kur_3" name="q_kurikulum" value="3"><label for="q_kur_3">Seimbang (Akademik & Non-Akademik)</label>
                        <input type="radio" id="q_kur_4" name="q_kurikulum" value="4"><label for="q_kur_4">Intensif (Persiapan PTN)</label>
                        <input type="radio" id="q_kur_5" name="q_kurikulum" value="5"><label for="q_kur_5">Sangat Ketat / Akselerasi</label>
                    </div>
                </div>

                <!-- BAGIAN 2: KUALITAS GURU -->
                <div class="group-title"><i class="fas fa-chalkboard-teacher"></i> 2. Kualitas Guru & Tenaga Pendidik</div>
                <div class="question-box">
                    <label class="q-title">Apa prioritas utama Anda terkait kualifikasi pengajar?</label>
                    <div class="options-grid">
                        <input type="radio" id="q_guru_1" name="q_guru" value="1"><label for="q_guru_1">Yang penting ramah & sabar</label>
                        <input type="radio" id="q_guru_2" name="q_guru" value="2"><label for="q_guru_2">Berpengalaman mengajar</label>
                        <input type="radio" id="q_guru_3" name="q_guru" value="3"><label for="q_guru_3">Lulusan PTN Ternama</label>
                        <input type="radio" id="q_guru_4" name="q_guru" value="4"><label for="q_guru_4">Tersertifikasi Internasional</label>
                        <input type="radio" id="q_guru_5" name="q_guru" value="5"><label for="q_guru_5">Pakar / Doktor di bidangnya</label>
                    </div>
                </div>

                <!-- BAGIAN 3: FASILITAS SEKOLAH -->
                <div class="group-title"><i class="fas fa-building"></i> 3. Fasilitas Sekolah</div>
                <div class="question-box">
                    <label class="q-title">Fasilitas apa yang WAJIB ada dan menjadi penentu utama?</label>
                    <div class="options-grid">
                        <input type="radio" id="q_fas_1" name="q_fasilitas" value="1"><label for="q_fas_1">Standar (Kelas & Halaman)</label>
                        <input type="radio" id="q_fas_2" name="q_fasilitas" value="2"><label for="q_fas_2">Perpustakaan & Lab Komputer</label>
                        <input type="radio" id="q_fas_3" name="q_fasilitas" value="3"><label for="q_fas_3">Lapangan Olahraga Lengkap</label>
                        <input type="radio" id="q_fas_4" name="q_fasilitas" value="4"><label for="q_fas_4">AC & Multimedia Lengkap</label>
                        <input type="radio" id="q_fas_5" name="q_fasilitas" value="5"><label for="q_fas_5">Kolam Renang & Smart Class</label>
                    </div>
                </div>

                <!-- BAGIAN 4: LINGKUNGAN & BUDAYA (Disiplin) -->
                <div class="group-title"><i class="fas fa-users"></i> 4. Lingkungan & Budaya Sekolah</div>
                <div class="question-box">
                    <label class="q-title">Seberapa ketat kedisiplinan dan pembentukan karakter yang diterapkan?</label>
                    <div class="options-grid">
                        <input type="radio" id="q_dis_1" name="q_disiplin" value="1" required><label for="q_dis_1">Biasa / Bebas Bertanggung Jawab</label>
                        <input type="radio" id="q_dis_2" name="q_disiplin" value="2"><label for="q_dis_2">Cukup Disiplin</label>
                        <input type="radio" id="q_dis_3" name="q_disiplin" value="3"><label for="q_dis_3">Ketat & Tertib (Agamis/Nasionalis)</label>
                        <input type="radio" id="q_dis_4" name="q_disiplin" value="4"><label for="q_dis_4">Sangat Ketat (Semi-Militer)</label>
                        <input type="radio" id="q_dis_5" name="q_disiplin" value="5"><label for="q_dis_5">Full Boarding (Asrama 24 Jam)</label>
                    </div>
                </div>

                <!-- BAGIAN 5: BIAYA PENDIDIKAN -->
                <div class="group-title"><i class="fas fa-wallet"></i> 5. Biaya Pendidikan</div>
                <div class="question-box">
                    <label class="q-title">Berapakah rentang biaya sekolah (SPP Bulanan) yang ideal bagi Anda?</label>
                    <div class="options-grid">
                        <input type="radio" id="q_biaya_1" name="q_biaya" value="1" required><label for="q_biaya_1">< Rp 500rb</label>
                        <input type="radio" id="q_biaya_2" name="q_biaya" value="2"><label for="q_biaya_2">500rb - 1 Juta</label>
                        <input type="radio" id="q_biaya_3" name="q_biaya" value="3"><label for="q_biaya_3">1 - 2 Juta</label>
                        <input type="radio" id="q_biaya_4" name="q_biaya" value="4"><label for="q_biaya_4">2 - 4 Juta</label>
                        <input type="radio" id="q_biaya_5" name="q_biaya" value="5"><label for="q_biaya_5">> 4 Juta</label>
                    </div>
                </div>

                <!-- BAGIAN 6: LOKASI & AKSES -->
                <div class="group-title"><i class="fas fa-map-marked-alt"></i> 6. Lokasi & Akses</div>
                <div class="question-box">
                    <label class="q-title">Seberapa penting kedekatan jarak rumah ke sekolah?</label>
                    <div class="options-grid">
                        <input type="radio" id="q_lok_1" name="q_lokasi" value="1"><label for="q_lok_1">Tidak Penting (Asrama/Jauh OK)</label>
                        <input type="radio" id="q_lok_2" name="q_lokasi" value="2"><label for="q_lok_2">Agak Jauh Tidak Masalah</label>
                        <input type="radio" id="q_lok_3" name="q_lokasi" value="3"><label for="q_lok_3">Jarak Menengah (30-45 menit)</label>
                        <input type="radio" id="q_lok_4" name="q_lokasi" value="4"><label for="q_lok_4">Dekat (15-30 menit)</label>
                        <input type="radio" id="q_lok_5" name="q_lokasi" value="5"><label for="q_lok_5">Sangat Dekat (Jalan Kaki/5 menit)</label>
                    </div>
                </div>

                <!-- BAGIAN 7: KEAMANAN & KESELAMATAN -->
                <div class="group-title"><i class="fas fa-shield-alt"></i> 7. Keamanan & Keselamatan</div>
                <div class="question-box">
                    <label class="q-title">Apa standar keamanan sekolah yang Anda harapkan?</label>
                    <div class="options-grid">
                        <input type="radio" id="q_aman_1" name="q_keamanan" value="1"><label for="q_aman_1">Standar (Pagar & Satpam)</label>
                        <input type="radio" id="q_aman_2" name="q_keamanan" value="2"><label for="q_aman_2">Ada CCTV di area utama</label>
                        <input type="radio" id="q_aman_3" name="q_keamanan" value="3"><label for="q_aman_3">CCTV Lengkap & Penjagaan Ketat</label>
                        <input type="radio" id="q_aman_4" name="q_keamanan" value="4"><label for="q_aman_4">Sangat Ketat (ID Card Akses)</label>
                        <input type="radio" id="q_aman_5" name="q_keamanan" value="5"><label for="q_aman_5">High Security (Keamanan Berlapis)</label>
                    </div>
                </div>

                <!-- BAGIAN 8: KEPERCAYAAN & REPUTASI -->
                <div class="group-title"><i class="fas fa-star"></i> 8. Kepercayaan & Reputasi</div>
                <div class="question-box">
                    <label class="q-title">Apa faktor utama yang membuat Anda percaya pada sebuah sekolah?</label>
                    <div class="options-grid">
                        <input type="radio" id="q_rep_1" name="q_reputasi" value="1"><label for="q_rep_1">Biaya Terjangkau</label>
                        <input type="radio" id="q_rep_2" name="q_reputasi" value="2"><label for="q_rep_2">Rekomendasi Teman/Keluarga</label>
                        <input type="radio" id="q_rep_3" name="q_reputasi" value="3"><label for="q_rep_3">Akreditasi Pemerintah (A/B)</label>
                        <input type="radio" id="q_rep_4" name="q_reputasi" value="4"><label for="q_rep_4">Prestasi Alumni & Sekolah</label>
                        <input type="radio" id="q_rep_5" name="q_reputasi" value="5"><label for="q_rep_5">Reputasi Internasional/Nasional</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit-quiz">
                    <i class="fas fa-search-location"></i> Analisis & Tampilkan Hasil
                </button>
            </form>
        </div>
    </div>

</body>
</html>