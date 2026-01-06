<?php
session_start();
include '../config/koneksi.php';

// Cek Login
if(!isset($_SESSION['status_login']) || $_SESSION['status_login'] != true){ 
    echo '<script>window.location="login.php"</script>'; 
    exit; 
}

// --- LOGIKA APPROVAL (VERIFIKASI MITRA) ---
if(isset($_GET['aksi']) && isset($_GET['id_mitra'])){
    $id_m = mysqli_real_escape_string($koneksi, $_GET['id_mitra']);
    $aksi = $_GET['aksi'];
    
    $status_baru = '';
    if($aksi == 'approve') $status_baru = 'Aktif';
    if($aksi == 'reject') $status_baru = 'Nonaktif';
    
    if($status_baru != ''){
        $update = mysqli_query($koneksi, "UPDATE tb_mitra SET status_akun='$status_baru' WHERE id_mitra='$id_m'");
        if($update){
            echo '<script>alert("Status mitra berhasil diperbarui!"); window.location="data_sekolah.php"</script>';
        }
    }
}

// --- LOGIKA FILTER & PENCARIAN ---
// Kita gunakan LEFT JOIN agar sekolah yang belum punya akun mitra tetap muncul
$where = "WHERE 1=1"; 
$url_param = "";

// 1. Filter Keyword
if(isset($_GET['q']) && $_GET['q'] != ''){
    $q = mysqli_real_escape_string($koneksi, $_GET['q']);
    $where .= " AND s.nama_sekolah LIKE '%$q%'";
    $url_param .= "&q=$q";
}

// 2. Filter Jenjang
if(isset($_GET['jenjang']) && $_GET['jenjang'] != ''){
    $jenjang = mysqli_real_escape_string($koneksi, $_GET['jenjang']);
    $where .= " AND s.jenjang = '$jenjang'";
    $url_param .= "&jenjang=$jenjang";
}

// 3. Filter Wilayah
if(isset($_GET['wilayah']) && $_GET['wilayah'] != ''){
    $wilayah = mysqli_real_escape_string($koneksi, $_GET['wilayah']);
    $where .= " AND s.wilayah = '$wilayah'";
    $url_param .= "&wilayah=$wilayah";
}

// 4. Filter Status Mitra (BARU)
// Mapping: Verifikasi = Pending, Bergabung = Aktif, Belum = NULL/Nonaktif
if(isset($_GET['status_mitra']) && $_GET['status_mitra'] != ''){
    $sm = $_GET['status_mitra'];
    if($sm == 'Verifikasi') $where .= " AND m.status_akun = 'Pending'";
    if($sm == 'Bergabung') $where .= " AND m.status_akun = 'Aktif'";
    if($sm == 'Belum') $where .= " AND (m.status_akun IS NULL OR m.status_akun = 'Nonaktif')";
    $url_param .= "&status_mitra=$sm";
}

// --- PAGINATION ---
$batas = 10;
$halaman = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$previous = $halaman - 1;
$next = $halaman + 1;

// Query Dasar (Join Sekolah & Mitra)
// Mengambil data sekolah (s) dan status akun mitra (m)
$base_query_count = "SELECT COUNT(*) as total FROM tb_sekolah s LEFT JOIN tb_mitra m ON s.id_sekolah = m.id_sekolah $where";
$data_count = mysqli_query($koneksi, $base_query_count);
$d_count = mysqli_fetch_assoc($data_count);
$jumlah_data = $d_count['total'];
$total_halaman = ceil($jumlah_data / $batas);

// Ambil Data Limit
$query_sekolah = mysqli_query($koneksi, "
    SELECT s.*, m.id_mitra, m.status_akun, m.username, m.nama_lengkap as nama_pic 
    FROM tb_sekolah s 
    LEFT JOIN tb_mitra m ON s.id_sekolah = m.id_sekolah 
    $where 
    ORDER BY m.status_akun = 'Pending' DESC, s.id_sekolah DESC 
    LIMIT $halaman_awal, $batas
");
// Note: ORDER BY m.status_akun = 'Pending' DESC akan menampilkan yang Pending di paling atas
$nomor = $halaman_awal + 1;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Sekolah & Verifikasi - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS DASHBOARD --- */
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        .sidebar { width: var(--sidebar); background: var(--dark); color: #fff; height: 100vh; position: fixed; padding-top: 20px; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .menu a { display: block; padding: 15px 25px; color: #aaa; text-decoration: none; transition: 0.3s; font-size: 15px; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: rgba(255,255,255,0.05); color: white; border-left-color: var(--primary); }
        .menu i { width: 25px; margin-right: 10px; text-align: center; }

        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        h2 { margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px; }

        /* Filter Box Style */
        .filter-box { display: flex; gap: 10px; background: #f1f8ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #d0e3ff; flex-wrap: wrap; align-items: center; }
        .filter-box select, .filter-box input { padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-size: 13px; outline: none; flex: 1; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 13px; font-weight: bold; }
        .btn-reset { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 13px; text-decoration: none; }

        /* Table Style */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; color: #555; font-weight: 600; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; color: #444; }
        tr:hover { background-color: #fdfdfd; }
        
        .badge { padding: 5px 10px; border-radius: 50px; font-size: 11px; font-weight: bold; display: inline-block; }
        .bg-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; } /* Verifikasi */
        .bg-active { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; } /* Bergabung */
        .bg-inactive { background: #f8d7da; color: #842029; border: 1px solid #f5c6cb; } /* Belum/Nonaktif */

        .btn-action { padding: 5px 10px; border-radius: 5px; font-size: 12px; text-decoration: none; font-weight: bold; display: inline-block; margin-right: 3px; transition: 0.3s; }
        .btn-view { background: #17a2b8; color: white; }
        .btn-approve { background: #198754; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-action:hover { opacity: 0.8; transform: translateY(-2px); }
        
        .btn-edit { color: #ffc107; font-size: 16px; margin-right: 5px; }
        .btn-del { color: #dc3545; font-size: 16px; }

        .btn-add { background: #198754; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-link { padding: 5px 10px; border: 1px solid #ddd; color: #333; text-decoration: none; border-radius: 3px; font-size: 12px; transition: 0.3s; }
        .page-link.active, .page-link:hover { background: var(--primary); color: white; border-color: var(--primary); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> ADMIN PANEL</div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="data_sekolah.php" class="active"><i class="fas fa-school"></i> Kelola Sekolah</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri Foto</a>
            <a href="pendaftar.php"><i class="fas fa-users"></i> Pendaftar</a>
            <a href="data_users.php"><i class="fas fa-user-circle"></i> Data Akun Ortu</a>
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 25px;">
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Lihat Website</a>
            <a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2><i class="fas fa-school" style="color: var(--primary);"></i> Kelola & Verifikasi Sekolah</h2>
            <a href="tambah_sekolah.php" class="btn-add"><i class="fas fa-plus"></i> Input Sekolah Manual</a>
        </div>

        <div class="card">
            
            <!-- FORM FILTER -->
            <form action="" method="GET" class="filter-box">
                <i class="fas fa-filter" style="color:#666; font-size: 18px;"></i>
                
                <input type="text" name="q" placeholder="Cari Nama Sekolah..." value="<?php echo isset($_GET['q']) ? $_GET['q'] : '' ?>">
                
                <select name="wilayah">
                    <option value="">- Semua Wilayah -</option>
                    <option value="Jakarta" <?php if(isset($_GET['wilayah']) && $_GET['wilayah'] == 'Jakarta') echo 'selected'; ?>>Jakarta</option>
                    <option value="Bogor" <?php if(isset($_GET['wilayah']) && $_GET['wilayah'] == 'Bogor') echo 'selected'; ?>>Bogor</option>
                    <option value="Depok" <?php if(isset($_GET['wilayah']) && $_GET['wilayah'] == 'Depok') echo 'selected'; ?>>Depok</option>
                    <option value="Tangerang" <?php if(isset($_GET['wilayah']) && $_GET['wilayah'] == 'Tangerang') echo 'selected'; ?>>Tangerang</option>
                    <option value="Bekasi" <?php if(isset($_GET['wilayah']) && $_GET['wilayah'] == 'Bekasi') echo 'selected'; ?>>Bekasi</option>
                </select>

                <!-- FILTER STATUS MITRA (NEW) -->
                <select name="status_mitra">
                    <option value="">- Status Kemitraan -</option>
                    <option value="Verifikasi" <?php if(isset($_GET['status_mitra']) && $_GET['status_mitra'] == 'Verifikasi') echo 'selected'; ?>>Perlu Verifikasi (Pending)</option>
                    <option value="Bergabung" <?php if(isset($_GET['status_mitra']) && $_GET['status_mitra'] == 'Bergabung') echo 'selected'; ?>>Telah Bergabung (Aktif)</option>
                    <option value="Belum" <?php if(isset($_GET['status_mitra']) && $_GET['status_mitra'] == 'Belum') echo 'selected'; ?>>Belum Bergabung</option>
                </select>

                <button type="submit" class="btn-filter">Filter</button>
                <a href="data_sekolah.php" class="btn-reset">Reset</a>
            </form>

            <!-- TABEL DATA -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="40">No</th>
                            <th>Nama Sekolah & Wilayah</th>
                            <th>Status Kemitraan</th>
                            <th>Dokumen Legalitas</th>
                            <th>Aksi Verifikasi</th>
                            <th width="80">Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if($jumlah_data > 0){
                            while($d = mysqli_fetch_array($query_sekolah)){
                                
                                // Tentukan Badge Status
                                $status_label = 'Belum Bergabung';
                                $status_class = 'bg-inactive';
                                
                                if($d['status_akun'] == 'Pending'){
                                    $status_label = 'Perlu Verifikasi';
                                    $status_class = 'bg-pending';
                                } elseif($d['status_akun'] == 'Aktif'){
                                    $status_label = 'Telah Bergabung';
                                    $status_class = 'bg-active';
                                } elseif($d['status_akun'] == 'Nonaktif'){
                                    $status_label = 'Ditolak / Nonaktif';
                                    $status_class = 'bg-inactive';
                                }
                        ?>
                        <tr>
                            <td><?php echo $nomor++; ?></td>
                            <td>
                                <b><?php echo $d['nama_sekolah']; ?></b>
                                <span style="font-size:11px; color:#888; display:block;">
                                    <?php echo $d['jenjang'] . ' - ' . $d['status_sekolah']; ?><br>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo !empty($d['wilayah']) ? $d['wilayah'] : 'Belum set wilayah'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                <?php if(!empty($d['username'])): ?>
                                    <div style="font-size:11px; margin-top:5px; color:#666;">
                                        PIC: <?php echo $d['nama_pic'] ?><br>
                                        User: <?php echo $d['username'] ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($d['file_sk']) && file_exists('../uploads/'.$d['file_sk'])): ?>
                                    <a href="../uploads/<?php echo $d['file_sk']; ?>" target="_blank" class="btn-action btn-view">
                                        <i class="fas fa-file-pdf"></i> Lihat SK
                                    </a>
                                <?php else: ?>
                                    <span style="color:#999; font-size:12px;">Tidak ada SK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- LOGIKA UPDATE: Tombol muncul jika ada Mitra, tidak hanya saat status Pending -->
                                
                                <!-- Tombol Approve: Muncul jika ada Mitra DAN statusnya BUKAN Aktif -->
                                <?php if(!empty($d['id_mitra']) && $d['status_akun'] != 'Aktif'): ?>
                                    <a href="?aksi=approve&id_mitra=<?php echo $d['id_mitra']; ?>" class="btn-action btn-approve" onclick="return confirm('Setujui sekolah ini menjadi Mitra Aktif?')">
                                        <i class="fas fa-check"></i> Terima
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Tombol Reject: Muncul jika ada Mitra DAN statusnya BUKAN Nonaktif -->
                                <?php if(!empty($d['id_mitra']) && $d['status_akun'] != 'Nonaktif'): ?>
                                    <a href="?aksi=reject&id_mitra=<?php echo $d['id_mitra']; ?>" class="btn-action btn-reject" onclick="return confirm('Nonaktifkan akun mitra ini?')">
                                        <i class="fas fa-times"></i> Tolak
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_sekolah.php?id=<?php echo $d['id_sekolah']; ?>" class="btn-edit" title="Edit Data"><i class="fas fa-edit"></i></a>
                                <a href="hapus_sekolah.php?id=<?php echo $d['id_sekolah']; ?>" class="btn-del" onclick="return confirm('Yakin hapus data ini?')" title="Hapus Data"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:30px; color:#999; font-style:italic;'>Data tidak ditemukan. Coba filter lain.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if($total_halaman > 1){ ?>
            <div class="pagination">
                <?php if($halaman > 1){ ?>
                    <a href="?hal=<?php echo $previous.$url_param; ?>" class="page-link">&laquo; Prev</a>
                <?php } ?>
                
                <?php for($x=1; $x<=$total_halaman; $x++){ ?>
                    <a href="?hal=<?php echo $x.$url_param; ?>" class="page-link <?php if($x == $halaman) echo 'active'; ?>"><?php echo $x; ?></a>
                <?php } ?>
                
                <?php if($halaman < $total_halaman){ ?>
                    <a href="?hal=<?php echo $next.$url_param; ?>" class="page-link">Next &raquo;</a>
                <?php } ?>
            </div>
            <?php } ?>

        </div>
    </div>

</body>
</html>