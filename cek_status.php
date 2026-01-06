<?php
session_start();
include 'config/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pendaftaran - EduSearch</title>
    
    <!-- Menggunakan CSS Utama -->
    <link rel="stylesheet" href="assets/css/style.css?v=23">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; }
        
        .status-card { 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            padding: 40px; 
            max-width: 600px; 
            margin: 60px auto; 
        }
        
        .form-input { 
            width: 100%; 
            padding: 15px 25px; 
            border: 1px solid #ddd; 
            border-radius: 50px; 
            margin-bottom: 15px; 
            outline: none; 
            font-size: 16px;
            background: #fcfcfc;
            transition: 0.3s;
        }
        .form-input:focus { border-color: #0d6efd; background: #fff; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); }
        
        .btn-check { 
            width: 100%; 
            padding: 15px; 
            background: #0d6efd; 
            color: white; 
            border: none; 
            border-radius: 50px; 
            font-weight: bold; 
            font-size: 16px;
            cursor: pointer; 
            transition: 0.3s; 
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }
        .btn-check:hover { background: #0b5ed7; transform: translateY(-2px); }
        
        /* Hasil Pencarian */
        .result-item { 
            border: 1px solid #eee; 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 15px; 
            background: #fff;
            transition: 0.3s;
        }
        .result-item:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-color: #0d6efd; }
        
        .badge { padding: 6px 15px; border-radius: 50px; font-size: 12px; font-weight: bold; display: inline-block;}
        .bg-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .bg-terima { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .bg-tolak { background: #f8d7da; color: #842029; border: 1px solid #f5c6cb; }

        .btn-print {
            display: inline-flex; align-items: center; gap: 5px;
            background: #0d6efd; color: white; padding: 8px 15px; 
            border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;
            margin-top: 10px; transition: 0.3s;
        }
        .btn-print:hover { background: #0b5ed7; }

        /* Alert Not Found */
        .alert-not-found {
            text-align: center;
            color: #dc3545;
            background: #fff5f5;
            padding: 30px;
            border-radius: 12px;
            border: 1px dashed #dc3545;
            margin-top: 20px;
        }
        .alert-not-found i {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>

    <!-- Header Sederhana -->
    <header>
        <div class="container header-content">
            <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduSearch</a>
            <a href="index.php" style="color:#555; font-weight:600; font-size:14px; text-decoration:none; display:flex; align-items:center; gap:5px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
    </header>

    <div class="container">
        <div class="status-card">
            <div style="text-align:center; margin-bottom:30px;">
                <h2 style="color:#0d6efd; margin-bottom:10px;">Cek Status Pendaftaran</h2>
                <p style="color:#666;">Masukkan Nomor HP yang Anda gunakan saat mendaftar untuk melihat status penerimaan.</p>
            </div>
            
            <form action="" method="GET">
                <!-- Ubah type menjadi text agar bisa menghandle inputan yang mungkin mengandung spasi atau tanda baca, lalu dibersihkan di PHP -->
                <input type="text" name="hp" class="form-input" placeholder="Contoh: 08123456789" value="<?php echo isset($_GET['hp']) ? htmlspecialchars($_GET['hp']) : '' ?>" required>
                <button type="submit" class="btn-check"><i class="fas fa-search"></i> Cari Data Saya</button>
            </form>

            <?php
            if(isset($_GET['hp'])){
                // 1. Sanitasi Input: Hapus semua karakter selain angka
                $hp_raw = $_GET['hp'];
                $hp_clean = preg_replace('/[^0-9]/', '', $hp_raw);

                // Validasi panjang nomor HP (opsional, misalnya minimal 10 digit)
                if(strlen($hp_clean) < 10) {
                     echo '<div class="alert-not-found">
                            <i class="fas fa-exclamation-circle"></i>
                            <b>Nomor HP tidak valid.</b><br>
                            <span style="font-size:14px; display:block; margin-top:5px;">Nomor HP harus terdiri dari minimal 10 digit angka.</span>
                          </div>';
                } else {
                    
                    $hp = mysqli_real_escape_string($koneksi, $hp_clean);
                    
                    echo '<div style="margin-top:40px; border-top:1px dashed #ddd; padding-top:20px;">';
                    
                    // Query menggunakan nomor HP yang sudah dibersihkan
                    // Asumsi di database nomor HP disimpan hanya angka
                    $q = mysqli_query($koneksi, "SELECT p.*, s.nama_sekolah 
                                                 FROM tb_pendaftaran p 
                                                 JOIN tb_sekolah s ON p.id_sekolah = s.id_sekolah 
                                                 WHERE p.no_hp = '$hp' 
                                                 ORDER BY p.id_daftar DESC");
                    
                    if(mysqli_num_rows($q) > 0){
                        echo '<h4 style="margin-bottom:20px; color:#333;">Ditemukan ' . mysqli_num_rows($q) . ' Data Pendaftaran:</h4>';
                        
                        while($d = mysqli_fetch_array($q)){
                            // Tentukan Warna Badge Status
                            $status_class = 'bg-pending';
                            $icon_status = 'fa-clock';
                            
                            if($d['status'] == 'Diterima') { 
                                $status_class = 'bg-terima'; 
                                $icon_status = 'fa-check-circle';
                            }
                            if($d['status'] == 'Ditolak') { 
                                $status_class = 'bg-tolak'; 
                                $icon_status = 'fa-times-circle';
                            }
                            ?>
                            
                            <div class="result-item">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                                    <div>
                                        <h3 style="margin:0 0 5px 0; font-size:18px; color:#0d6efd;"><?php echo $d['nama_siswa'] ?></h3>
                                        <p style="margin:0; color:#888; font-size:13px;">ID Reg: #<?php echo $d['id_daftar'] ?></p>
                                    </div>
                                    <span class="badge <?php echo $status_class ?>">
                                        <i class="fas <?php echo $icon_status ?>"></i> <?php echo $d['status'] ?>
                                    </span>
                                </div>
                                
                                <div style="background:#f9f9f9; padding:10px; border-radius:8px; font-size:14px; color:#555;">
                                    <div style="margin-bottom:5px;">
                                        <i class="fas fa-school" style="color:#0d6efd; width:20px;"></i> 
                                        <b><?php echo $d['nama_sekolah'] ?></b>
                                    </div>
                                    <div>
                                        <i class="far fa-calendar-alt" style="color:#0d6efd; width:20px;"></i> 
                                        Tgl Daftar: <?php echo date('d F Y', strtotime($d['tanggal_daftar'])) ?>
                                    </div>
                                </div>

                                <?php if($d['status'] == 'Diterima'){ ?>
                                    <div style="margin-top:15px; text-align:right;">
                                        <a href="cetak_bukti.php?id=<?php echo $d['id_daftar'] ?>" target="_blank" class="btn-print">
                                            <i class="fas fa-print"></i> Cetak Bukti Penerimaan
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>

                            <?php
                        }
                    } else {
                        // Tampilan Data Tidak Ditemukan yang Lebih Rapi
                        echo '<div class="alert-not-found">
                                <i class="fas fa-exclamation-triangle"></i>
                                <b>Data tidak ditemukan.</b><br>
                                <span style="font-size:14px; display:block; margin-top:5px;">Pastikan nomor HP yang Anda masukkan (<b>' . htmlspecialchars($hp_raw) . '</b>) benar dan sesuai saat mendaftar.</span>
                                <a href="index.php" style="display:inline-block; margin-top:15px; color:#dc3545; font-weight:bold; text-decoration:underline;">Cari Sekolah & Daftar Sekarang</a>
                              </div>';
                    }
                    
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>

</body>
</html>