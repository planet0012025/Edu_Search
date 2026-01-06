<?php
session_start();
// Matikan error reporting visual
error_reporting(0);
include '../config/koneksi.php';

// Cek Login
if(!isset($_SESSION['status_login']) || $_SESSION['status_login'] != true){
    echo '<script>window.location="login.php"</script>';
    exit;
}

// --- 1. AUTO REPAIR DATABASE (Pastikan Kolom Ada) ---
$cols_needed = [
    'foto' => "VARCHAR(255) NULL",
    'deskripsi' => "TEXT NULL",
    'tanggal' => "DATE NULL",
    'tempat' => "VARCHAR(255) NULL"
];
foreach($cols_needed as $col => $def){
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM tb_fasilitas LIKE '$col'");
    if(mysqli_num_rows($check) == 0) mysqli_query($koneksi, "ALTER TABLE tb_fasilitas ADD COLUMN $col $def");
}

// --- 2. FILTER SEKOLAH ---
$id_sekolah_pilih = "";
$nama_sekolah_pilih = "Pilih Sekolah";

if(isset($_GET['edit'])){
    $id_edit = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $q_cek = mysqli_query($koneksi, "SELECT id_sekolah FROM tb_fasilitas WHERE id_fasilitas='$id_edit'");
    if($d = mysqli_fetch_array($q_cek)) $id_sekolah_pilih = $d['id_sekolah'];
} else if(isset($_GET['id_sekolah'])){
    $id_sekolah_pilih = mysqli_real_escape_string($koneksi, $_GET['id_sekolah']);
}

if(!empty($id_sekolah_pilih)){
    $q_ns = mysqli_query($koneksi, "SELECT nama_sekolah FROM tb_sekolah WHERE id_sekolah='$id_sekolah_pilih'");
    if($d_ns = mysqli_fetch_array($q_ns)) $nama_sekolah_pilih = $d_ns['nama_sekolah'];
}

// --- 3. HAPUS DATA ---
if(isset($_GET['hapus'])){
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    $q_info = mysqli_query($koneksi, "SELECT foto, id_sekolah FROM tb_fasilitas WHERE id_fasilitas='$id_hapus'");
    if(mysqli_num_rows($q_info) > 0){
        $info = mysqli_fetch_object($q_info);
        if(!empty($info->foto) && file_exists('../uploads/fasilitas/'.$info->foto)) @unlink('../uploads/fasilitas/'.$info->foto);
        mysqli_query($koneksi, "DELETE FROM tb_fasilitas WHERE id_fasilitas='$id_hapus'");
        $_SESSION['msg_sukses'] = "Data berhasil dihapus!";
        header("Location: fasilitas.php?id_sekolah=".$info->id_sekolah);
        exit;
    }
}

// --- 4. SIMPAN DATA ---
if(isset($_POST['simpan'])){
    $id_sekolah_post = mysqli_real_escape_string($koneksi, $_POST['id_sekolah']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $nama_fasilitas = mysqli_real_escape_string($koneksi, $_POST['nama_fasilitas']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
    
    // Handle Date & Place
    $tgl_in = $_POST['tanggal'] ?? '';
    $tanggal = !empty($tgl_in) ? "'$tgl_in'" : "NULL";
    $tempat = mysqli_real_escape_string($koneksi, $_POST['tempat'] ?? '');
    
    $filename = $_POST['foto_lama'] ?? '';

    // Validasi & Defaulting
    if(empty($kategori)) $kategori = "Fasilitas Umum"; // Default jika kosong
    if(empty($nama_fasilitas)) $nama_fasilitas = "Fasilitas Tanpa Nama";

    // Upload
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] === 0){
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $new_name = 'fas_' . time() . rand(100,999) . '.' . $ext;
        $path = '../uploads/fasilitas/';
        if(!file_exists($path)) mkdir($path, 0777, true);
        
        if(move_uploaded_file($_FILES['foto']['tmp_name'], $path . $new_name)){
            if(!empty($filename) && file_exists($path . $filename)) @unlink($path . $filename);
            $filename = $new_name;
        }
    }

    if(!empty($_POST['id_fasilitas'])){
        $id_f = mysqli_real_escape_string($koneksi, $_POST['id_fasilitas']);
        $sql = "UPDATE tb_fasilitas SET kategori='$kategori', nama_fasilitas='$nama_fasilitas', deskripsi='$deskripsi', tanggal=$tanggal, tempat='$tempat', foto='$filename' WHERE id_fasilitas='$id_f'";
    } else {
        $sql = "INSERT INTO tb_fasilitas (id_sekolah, kategori, nama_fasilitas, deskripsi, tanggal, tempat, foto) VALUES ('$id_sekolah_post', '$kategori', '$nama_fasilitas', '$deskripsi', $tanggal, '$tempat', '$filename')";
    }

    if(mysqli_query($koneksi, $sql)) $_SESSION['msg_sukses'] = "Data berhasil disimpan!";
    else $_SESSION['msg_error'] = "Error DB: " . mysqli_error($koneksi);
    
    header("Location: fasilitas.php?id_sekolah=$id_sekolah_post");
    exit;
}

// --- 5. EDIT MODE ---
$edit_mode = false;
$d_edit = [];
if(isset($_GET['edit'])){
    $id_edit = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $q_edit = mysqli_query($koneksi, "SELECT * FROM tb_fasilitas WHERE id_fasilitas='$id_edit'");
    if(mysqli_num_rows($q_edit) > 0){
        $edit_mode = true;
        $d_edit = mysqli_fetch_assoc($q_edit);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Fasilitas - Admin Panel</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4e73df; --secondary: #858796; --success: #1cc88a; --info: #36b9cc; --warning: #f6c23e; --danger: #e74a3b;
            --light: #f8f9fc; --dark: #5a5c69; --white: #ffffff;
            --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--light); margin: 0; display: flex; color: var(--dark); }
        
        /* Sidebar */
        .sidebar { width: 250px; background: #2c3e50; color: white; min-height: 100vh; position: fixed; transition: all 0.3s; z-index: 100; }
        .sidebar-brand { padding: 20px; text-align: center; font-weight: 700; font-size: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { list-style: none; padding: 0; margin: 20px 0; }
        .sidebar-menu li a { display: flex; align-items: center; padding: 15px 25px; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid var(--primary); }
        .sidebar-menu li a i { margin-right: 15px; width: 20px; text-align: center; }

        /* Main Content */
        .main-content { margin-left: 250px; width: calc(100% - 250px); padding: 30px; transition: all 0.3s; }
        
        /* Cards */
        .card { background: var(--white); border-radius: 10px; box-shadow: var(--shadow); border: none; margin-bottom: 30px; overflow: hidden; }
        .card-header { background: var(--white); padding: 20px; border-bottom: 1px solid #e3e6f0; display: flex; justify-content: space-between; align-items: center; }
        .card-title { margin: 0; font-weight: 700; color: var(--primary); font-size: 1.1rem; }
        .card-body { padding: 25px; }

        /* Form Elements */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: #4e73df; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #d1d3e2; border-radius: 8px; font-size: 0.95rem; color: #6e707e; transition: 0.3s; outline: none; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1); }
        
        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; } .btn-primary:hover { background: #2e59d9; }
        .btn-secondary { background: var(--secondary); color: white; } .btn-secondary:hover { background: #6c757d; }
        .btn-danger { background: var(--danger); color: white; } .btn-danger:hover { background: #be2617; }
        .btn-warning { background: var(--warning); color: white; } .btn-warning:hover { background: #dddfeb; color: #858796; }
        .btn-sm { padding: 5px 10px; font-size: 0.8rem; }

        /* Table */
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { padding: 15px; text-align: left; border-bottom: 1px solid #e3e6f0; vertical-align: middle; }
        .table th { background: #f8f9fc; color: var(--primary); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
        .table tr:hover { background-color: #f8f9fc; }
        
        /* Badges & Images */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-olahraga { background: #e0f2fe; color: #0284c7; }
        .badge-belajar { background: #dcfce7; color: #16a34a; }
        .badge-ibadah { background: #fef9c3; color: #ca8a04; }
        .badge-penunjang { background: #fee2e2; color: #dc2626; }
        .badge-umum { background: #f3f4f6; color: #4b5563; }
        .img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #e3e6f0; }

        /* Alert */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        /* Empty State */
        .empty-state { text-align: center; padding: 50px; color: #858796; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; color: #d1d3e2; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-graduation-cap"></i> ADMIN PANEL</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="data_sekolah.php"><i class="fas fa-school"></i> Data Sekolah</a></li>
            <li><a href="fasilitas.php" class="active"><i class="fas fa-basketball-ball"></i> Kelola Fasilitas</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 style="margin-bottom: 25px; color: #2c3e50; font-weight: 700;">Kelola Fasilitas Sekolah</h2>

        <!-- Flash Messages -->
        <?php if(isset($_SESSION['msg_sukses'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['msg_sukses']; unset($_SESSION['msg_sukses']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['msg_error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
        <?php endif; ?>

        <!-- 1. Card Filter -->
        <div class="card">
            <div class="card-body" style="background: #f8f9fc; border-left: 5px solid var(--primary);">
                <form method="GET" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <label style="font-weight: 600; color: #4e73df; white-space: nowrap;"><i class="fas fa-filter"></i> Pilih Sekolah:</label>
                    <select name="id_sekolah" class="form-control" style="max-width: 300px; margin: 0;" onchange="this.form.submit()">
                        <option value="">-- Pilih Sekolah --</option>
                        <?php 
                        $q_sek = mysqli_query($koneksi, "SELECT id_sekolah, nama_sekolah FROM tb_sekolah ORDER BY nama_sekolah ASC");
                        while($r = mysqli_fetch_array($q_sek)){
                            $sel = ($id_sekolah_pilih == $r['id_sekolah']) ? 'selected' : '';
                            echo "<option value='".$r['id_sekolah']."' $sel>".$r['nama_sekolah']."</option>";
                        }
                        ?>
                    </select>
                    <?php if($id_sekolah_pilih): ?>
                        <a href="fasilitas.php" class="btn btn-secondary btn-sm"><i class="fas fa-sync"></i> Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if($id_sekolah_pilih): ?>
        
        <!-- 2. Card Form -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> <?php echo $edit_mode ? 'Edit Data Fasilitas' : 'Tambah Fasilitas Baru'; ?></div>
                <div class="badge badge-umum"><?php echo $nama_sekolah_pilih; ?></div>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_sekolah" value="<?php echo $id_sekolah_pilih; ?>">
                    <?php if($edit_mode): ?>
                        <input type="hidden" name="id_fasilitas" value="<?php echo $d_edit['id_fasilitas']; ?>">
                        <input type="hidden" name="foto_lama" value="<?php echo $d_edit['foto']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Kategori <span style="color:red">*</span></label>
                            <select name="kategori" id="kategori" class="form-control" required onchange="updateFasilitas()">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Sarana Olahraga">Sarana Olahraga</option>
                                <option value="Sarana Belajar">Sarana Belajar</option>
                                <option value="Sarana Ibadah">Sarana Ibadah</option>
                                <option value="Sarana Penunjang">Sarana Penunjang</option>
                                <option value="Fasilitas Umum">Fasilitas Umum</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Fasilitas <span style="color:red">*</span></label>
                            <select name="nama_fasilitas" id="nama_fasilitas" class="form-control" required>
                                <option value="">-- Pilih Kategori Dahulu --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Tanggal (Opsional)</label>
                            <input type="date" name="tanggal" class="form-control" value="<?php echo $edit_mode ? $d_edit['tanggal'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tempat / Lokasi (Opsional)</label>
                            <input type="text" name="tempat" class="form-control" placeholder="Contoh: Gedung A Lt 1" value="<?php echo $edit_mode ? $d_edit['tempat'] : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi Singkat</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan fasilitas ini..."><?php echo $edit_mode ? $d_edit['deskripsi'] : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Upload Foto (Maks 2MB)</label>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <?php if($edit_mode && !empty($d_edit['foto'])): ?>
                                <img src="../uploads/fasilitas/<?php echo $d_edit['foto']; ?>" class="img-thumb">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Data</button>
                        <?php if($edit_mode): ?>
                            <a href="fasilitas.php?id_sekolah=<?php echo $id_sekolah_pilih; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Card Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-list"></i> Daftar Fasilitas</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Foto</th>
                                <th width="25%">Nama & Kategori</th>
                                <th width="40%">Detail Info</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_tabel = mysqli_query($koneksi, "SELECT * FROM tb_fasilitas WHERE id_sekolah='$id_sekolah_pilih' ORDER BY kategori ASC, nama_fasilitas ASC");
                            $no = 1;
                            
                            if(mysqli_num_rows($q_tabel) > 0){
                                while($row = mysqli_fetch_array($q_tabel)){
                                    // Badge Logic
                                    $kat = $row['kategori'];
                                    $badge = "badge-umum";
                                    if(strpos($kat, 'Olahraga') !== false) $badge = "badge-olahraga";
                                    elseif(strpos($kat, 'Belajar') !== false) $badge = "badge-belajar";
                                    elseif(strpos($kat, 'Ibadah') !== false) $badge = "badge-ibadah";
                                    elseif(strpos($kat, 'Penunjang') !== false) $badge = "badge-penunjang";
                                    
                                    $img = !empty($row['foto']) ? "../uploads/fasilitas/".$row['foto'] : "../assets/img/no-image.jpg";
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><img src="<?php echo $img; ?>" class="img-thumb"></td>
                                <td>
                                    <div style="font-weight: 600; margin-bottom: 5px;"><?php echo $row['nama_fasilitas']; ?></div>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $kat ? $kat : 'Umum'; ?></span>
                                </td>
                                <td>
                                    <?php if($row['tanggal'] != '0000-00-00'): ?>
                                        <small><i class="far fa-calendar"></i> <?php echo $row['tanggal']; ?></small><br>
                                    <?php endif; ?>
                                    <?php if($row['tempat']): ?>
                                        <small><i class="fas fa-map-marker-alt"></i> <?php echo $row['tempat']; ?></small><br>
                                    <?php endif; ?>
                                    <small style="color: #858796;"><?php echo substr($row['deskripsi'], 0, 60) . '...'; ?></small>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?edit=<?php echo $row['id_fasilitas']; ?>" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus=<?php echo $row['id_fasilitas']; ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='5' class='empty-state'><i class='fas fa-folder-open'></i><br>Belum ada data fasilitas.</td></tr>";
                            } 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php else: ?>
            <div class="card empty-state">
                <i class="fas fa-school" style="color: var(--secondary);"></i>
                <h3>Silakan Pilih Sekolah</h3>
                <p>Pilih nama sekolah pada dropdown filter di atas untuk mulai mengelola data.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Script Dropdown Cerdas -->
    <script>
        const dataFas = {
            "Sarana Olahraga": ["Lapangan Sepakbola", "Lapangan Futsal", "Lapangan Basket", "Lapangan Voli", "Kolam Renang", "Jogging Track", "Gym"],
            "Sarana Belajar": ["Ruang Kelas", "Lab Komputer", "Lab IPA", "Lab Bahasa", "Perpustakaan", "Multimedia", "Smart Classroom"],
            "Sarana Ibadah": ["Masjid", "Musholla", "Kapel", "Pura", "Vihara"],
            "Sarana Penunjang": ["Kantin Sehat", "UKS", "Toilet", "Parkir", "CCTV", "Aula", "Taman"],
            "Fasilitas Umum": ["Lobby", "Pos Satpam", "Ruang Guru", "Ruang Kepala Sekolah"]
        };

        function updateFasilitas(selName = null){
            const k = document.getElementById('kategori').value;
            const n = document.getElementById('nama_fasilitas');
            const cur = selName || n.value;
            
            n.innerHTML = '<option value="">-- Pilih Fasilitas --</option>';
            if(dataFas[k]){
                dataFas[k].forEach(val => {
                    let o = document.createElement('option'); o.value = val; o.text = val;
                    if(cur == val) o.selected = true;
                    n.add(o);
                });
            }
            
            // Jaga data lama agar tidak hilang
            let exist = Array.from(n.options).some(o => o.value === cur);
            if(cur && !exist){
                let o = document.createElement('option'); o.value = cur; o.text = cur + " (Data Lama)"; o.selected = true;
                n.add(o);
            }
        }

        <?php if($edit_mode): ?>
        window.addEventListener('DOMContentLoaded', () => {
            let k = "<?php echo $d_edit['kategori']; ?>";
            let n = "<?php echo $d_edit['nama_fasilitas']; ?>";
            let elK = document.getElementById('kategori');
            
            if(k === "") k = "Fasilitas Umum"; // Default fallback
            
            let kExist = Array.from(elK.options).some(o => o.value === k);
            if(!kExist){
                let o = document.createElement('option'); o.value = k; o.text = k + " (Data Lama)";
                elK.add(o);
            }
            elK.value = k;
            updateFasilitas(n);
        });
        <?php endif; ?>
    </script>
</body>
</html>