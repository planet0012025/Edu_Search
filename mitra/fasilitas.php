<?php
session_start();
// Matikan notifikasi error di layar
error_reporting(0);
include '../config/koneksi.php';

// 1. Cek Login Mitra
if(!isset($_SESSION['status_login']) || $_SESSION['status_login'] != true){ 
    echo '<script>window.location="../login.php"</script>'; 
    exit; 
}

if(!isset($_SESSION['id_sekolah'])){
    echo '<script>alert("Akses ditolak! Anda belum terhubung dengan data sekolah."); window.location="../index.php"</script>';
    exit;
}

$id_sekolah = $_SESSION['id_sekolah'];

// --- AUTO-REPAIR DATABASE (PERBAIKAN KOLOM) ---
// Menambahkan 'kategori' dan 'nama_fasilitas' ke pengecekan agar database sinkron
$cols_needed = [
    'kategori' => 'VARCHAR(100) NULL',
    'nama_fasilitas' => 'VARCHAR(255) NULL',
    'foto' => 'VARCHAR(255) NULL', 
    'deskripsi' => 'TEXT NULL', 
    'tanggal' => 'DATE NULL', 
    'tempat' => 'VARCHAR(255) NULL'
];

foreach($cols_needed as $col => $type){
    $cek = mysqli_query($koneksi, "SHOW COLUMNS FROM tb_fasilitas LIKE '$col'");
    if(mysqli_num_rows($cek) == 0){
        mysqli_query($koneksi, "ALTER TABLE tb_fasilitas ADD COLUMN $col $type");
    } else {
        // Opsional: Pastikan tipe data cukup panjang (jika kolom sudah ada tapi kependekan)
        if($col == 'kategori') mysqli_query($koneksi, "ALTER TABLE tb_fasilitas MODIFY COLUMN kategori VARCHAR(100) NULL");
    }
}

// --- FLASH MESSAGE ---
$pesan = '';
if(isset($_SESSION['msg_sukses'])){
    $pesan = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> ".$_SESSION['msg_sukses']."</div>";
    unset($_SESSION['msg_sukses']);
}
if(isset($_SESSION['msg_error'])){
    $pesan = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> ".$_SESSION['msg_error']."</div>";
    unset($_SESSION['msg_error']);
}

// --- HAPUS DATA ---
if(isset($_GET['hapus'])){
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    $q_img = mysqli_query($koneksi, "SELECT foto FROM tb_fasilitas WHERE id_fasilitas='$id_hapus' AND id_sekolah='$id_sekolah'");
    
    if(mysqli_num_rows($q_img) > 0){
        $img = mysqli_fetch_object($q_img);
        if(!empty($img->foto) && file_exists('../uploads/fasilitas/'.$img->foto)){
            @unlink('../uploads/fasilitas/'.$img->foto);
        }
        
        $del = mysqli_query($koneksi, "DELETE FROM tb_fasilitas WHERE id_fasilitas='$id_hapus' AND id_sekolah='$id_sekolah'");
        if($del){
            $_SESSION['msg_sukses'] = "Data berhasil dihapus!";
            header("Location: fasilitas.php");
            exit;
        }
    }
}

// --- SIMPAN DATA ---
if(isset($_POST['simpan'])){
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $nama_fasilitas = mysqli_real_escape_string($koneksi, $_POST['nama_fasilitas']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
    
    $tgl_in = $_POST['tanggal'] ?? '';
    $tanggal = !empty($tgl_in) ? "'".mysqli_real_escape_string($koneksi, $tgl_in)."'" : "NULL";
    
    $tempat = mysqli_real_escape_string($koneksi, $_POST['tempat'] ?? '');
    $filename = $_POST['foto_lama'] ?? '';

    // Validasi Input
    if(empty($kategori)){
        $_SESSION['msg_error'] = "Gagal! Anda belum memilih Kategori.";
        header("Location: fasilitas.php");
        exit;
    }
    if(empty($nama_fasilitas)){
        $_SESSION['msg_error'] = "Gagal! Anda belum memilih Nama Fasilitas.";
        header("Location: fasilitas.php");
        exit;
    }

    // Proses Upload
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK){
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
        // UPDATE
        $id_f = mysqli_real_escape_string($koneksi, $_POST['id_fasilitas']);
        $query = "UPDATE tb_fasilitas SET kategori='$kategori', nama_fasilitas='$nama_fasilitas', deskripsi='$deskripsi', tanggal=$tanggal, tempat='$tempat', foto='$filename' WHERE id_fasilitas='$id_f' AND id_sekolah='$id_sekolah'";
    } else {
        // INSERT
        $query = "INSERT INTO tb_fasilitas (id_sekolah, kategori, nama_fasilitas, deskripsi, tanggal, tempat, foto) VALUES ('$id_sekolah', '$kategori', '$nama_fasilitas', '$deskripsi', $tanggal, '$tempat', '$filename')";
    }

    if(mysqli_query($koneksi, $query)) $_SESSION['msg_sukses'] = "Data tersimpan!";
    else $_SESSION['msg_error'] = "Error DB: " . mysqli_error($koneksi);
    
    header("Location: fasilitas.php");
    exit;
}

// --- EDIT MODE ---
$edit_mode = false;
$data_edit = [];
if(isset($_GET['edit'])){
    $id_edit = mysqli_real_escape_string($koneksi, $_GET['edit']);
    $q_edit = mysqli_query($koneksi, "SELECT * FROM tb_fasilitas WHERE id_fasilitas='$id_edit' AND id_sekolah='$id_sekolah'");
    if(mysqli_num_rows($q_edit) > 0){
        $edit_mode = true;
        $data_edit = mysqli_fetch_assoc($q_edit);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Fasilitas - Mitra Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0d6efd; --dark: #212529; --light: #f4f6f9; --sidebar: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--light); display: flex; }
        
        /* SIDEBAR DESIGN */
        .sidebar { width: var(--sidebar); background: white; height: 100vh; position: fixed; border-right: 1px solid #ddd; }
        .brand { padding: 25px; font-size: 18px; font-weight: bold; color: var(--primary); display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f0f0f0; }
        .menu { padding: 20px 0; }
        .menu a { display: flex; align-items: center; padding: 12px 25px; color: #666; text-decoration: none; transition: 0.3s; font-size: 14px; border-left: 3px solid transparent; }
        .menu a:hover, .menu a.active { background: #f0f7ff; color: var(--primary); border-left-color: var(--primary); font-weight: 500; }
        .menu i { width: 25px; font-size: 16px; text-align: center; margin-right: 10px; }
        
        /* Main Content */
        .main { margin-left: var(--sidebar); width: calc(100% - var(--sidebar)); padding: 30px; }
        
        /* Cards & Forms */
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 25px; }
        .card-header { font-size: 16px; font-weight: 600; color: var(--primary); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #555; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: 0.2s; background: #fff; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); }
        textarea.form-control { resize: vertical; min-height: 80px; }
        
        .btn { padding: 10px 25px; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; color: white; text-decoration: none; }
        .btn-primary { background: var(--primary); }
        .btn-secondary { background: #6c757d; }
        .btn-warning { background: #ffc107; color: #000; padding: 5px 10px; font-size: 12px; }
        .btn-danger { background: #dc3545; padding: 5px 10px; font-size: 12px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 12px 15px; background: #f8f9fa; font-size: 13px; font-weight: 600; color: #555; border-bottom: 2px solid #eee; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #333; vertical-align: middle; }
        .img-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
        
        /* Badges */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; border: 1px solid transparent; }
        .bg-olahraga { background: #e2f0ff; color: #007bff; border-color: #b6d4fe; }
        .bg-belajar { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        .bg-ibadah { background: #fff3cd; color: #856404; border-color: #ffeeba; }
        .bg-penunjang { background: #f8d7da; color: #842029; border-color: #f5c2c7; }
        .bg-null { background: #6c757d; color: white; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <i class="fas fa-search-location"></i> EDU SEARCH
        </div>
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profil_sekolah.php"><i class="fas fa-school"></i> Profil Sekolah</a>
            <a href="fasilitas.php" class="active"><i class="fas fa-basketball-ball"></i> Fasilitas</a>
            <a href="galeri.php"><i class="fas fa-images"></i> Galeri Foto</a>
            <a href="pendaftar.php"><i class="fas fa-user-graduate"></i> Data Pendaftar</a>
            <a href="logout.php" style="margin-top: 20px; color: #dc3545;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <h2 style="margin-bottom: 20px;">Kelola Fasilitas Sekolah</h2>
        <?php echo $pesan; ?>

        <!-- CARD FORM -->
        <div class="card">
            <div class="card-header">
                <i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> 
                <?php echo $edit_mode ? 'Edit Data Fasilitas' : 'Tambah Fasilitas Baru'; ?>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <?php if($edit_mode): ?>
                    <input type="hidden" name="id_fasilitas" value="<?php echo $data_edit['id_fasilitas']; ?>">
                    <input type="hidden" name="foto_lama" value="<?php echo $data_edit['foto']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori (Wajib)</label>
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
                        <label>Nama Fasilitas</label>
                        <select name="nama_fasilitas" id="nama_fasilitas" class="form-control" required>
                            <option value="">-- Pilih Kategori Terlebih Dahulu --</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal (Opsional)</label>
                        <input type="date" name="tanggal" class="form-control" value="<?php echo $edit_mode ? $data_edit['tanggal'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Tempat / Lokasi (Opsional)</label>
                        <input type="text" name="tempat" class="form-control" placeholder="Contoh: Gedung A" value="<?php echo $edit_mode ? $data_edit['tempat'] : ''; ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Deskripsi Singkat</label>
                    <textarea name="deskripsi" class="form-control" placeholder="Jelaskan fasilitas ini..."><?php echo $edit_mode ? $data_edit['deskripsi'] : ''; ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Foto Fasilitas (Max 2MB)</label>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <?php if($edit_mode && !empty($data_edit['foto'])): ?>
                            <div style="text-align: center;">
                                <img src="../uploads/fasilitas/<?php echo $data_edit['foto']; ?>" style="height: 40px; border-radius: 4px; border: 1px solid #ccc;">
                                <div style="font-size: 10px; color: #666;">Foto Saat Ini</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="text-align: right;">
                    <?php if($edit_mode): ?>
                        <a href="fasilitas.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                    <button type="submit" name="simpan" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_mode ? 'Simpan Perubahan' : 'Simpan Data'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- CARD TABEL -->
        <div class="card">
            <div class="card-header"><i class="fas fa-list"></i> Daftar Fasilitas Sekolah</div>
            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">Foto</th>
                        <th width="25%">Info Fasilitas</th>
                        <th width="40%">Detail / Deskripsi</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $query = mysqli_query($koneksi, "SELECT * FROM tb_fasilitas WHERE id_sekolah='$id_sekolah' ORDER BY kategori ASC, nama_fasilitas ASC");
                    
                    if(mysqli_num_rows($query) > 0){
                        while($d = mysqli_fetch_array($query)){
                            // BADGE WARNA
                            $kat = $d['kategori'];
                            $badge_class = 'bg-null';
                            if(strpos($kat, 'Olahraga') !== false) $badge_class = 'bg-olahraga';
                            elseif(strpos($kat, 'Belajar') !== false) $badge_class = 'bg-belajar';
                            elseif(strpos($kat, 'Ibadah') !== false) $badge_class = 'bg-ibadah';
                            elseif(strpos($kat, 'Penunjang') !== false) $badge_class = 'bg-penunjang';
                            
                            $tampilan_kat = empty($kat) ? "Belum Ada Kategori" : $kat;
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <?php if(!empty($d['foto'])): ?>
                                <img src="../uploads/fasilitas/<?php echo $d['foto']; ?>" class="img-thumb">
                            <?php else: ?>
                                <span style="font-size: 11px; color: #999;">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #333; margin-bottom: 5px;"><?php echo $d['nama_fasilitas']; ?></div>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $tampilan_kat; ?></span>
                        </td>
                        <td>
                            <small style="display:block; color:#666; margin-bottom:3px;">
                                <?php if(!empty($d['tanggal']) && $d['tanggal'] != '0000-00-00') echo "<b>Tgl:</b> " . $d['tanggal'] . " | "; ?>
                                <?php if(!empty($d['tempat'])) echo "<b>Loc:</b> " . $d['tempat']; ?>
                            </small>
                            <div style="font-size: 12px; color: #777; line-height: 1.4;">
                                <?php echo !empty($d['deskripsi']) ? substr($d['deskripsi'], 0, 80) . (strlen($d['deskripsi']) > 80 ? '...' : '') : '-'; ?>
                            </div>
                        </td>
                        <td>
                            <a href="?edit=<?php echo $d['id_fasilitas']; ?>" class="btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="?hapus=<?php echo $d['id_fasilitas']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus?')" title="Hapus"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="5" style="text-align:center; padding: 30px; color:#999;">Belum ada data fasilitas. Silakan tambah data baru.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JAVASCRIPT DROPDOWN -->
    <script>
        const dataFasilitas = {
            "Sarana Olahraga": [ "Lapangan Sepakbola", "Lapangan Futsal", "Lapangan Basket", "Lapangan Voli", "Lapangan Badminton", "Kolam Renang", "Jogging Track", "Tenis Meja", "Gym / Fitness" ],
            "Sarana Belajar": [ "Ruang Kelas AC", "Laboratorium Komputer", "Laboratorium IPA", "Laboratorium Bahasa", "Perpustakaan", "Ruang Multimedia", "Ruang Praktik Siswa", "Smart Classroom" ],
            "Sarana Ibadah": [ "Masjid", "Musholla", "Kapel", "Pura", "Vihara" ],
            "Sarana Penunjang": [ "Kantin Sehat", "UKS", "Toilet Bersih", "Area Parkir Luas", "CCTV 24 Jam", "Wi-Fi Area", "Aula / Auditorium", "Taman Sekolah" ],
            "Fasilitas Umum": ["Lobby", "Pos Satpam", "Ruang Guru", "Ruang Kepala Sekolah"]
        };

        const selectKategori = document.getElementById('kategori');
        const selectNama = document.getElementById('nama_fasilitas');

        function updateFasilitas(selectedValue = null) {
            const kategori = selectKategori.value;
            const currentName = selectedValue || selectNama.value;

            selectNama.innerHTML = '<option value="">-- Pilih Fasilitas --</option>';
            
            if (dataFasilitas[kategori]) {
                dataFasilitas[kategori].forEach(fasilitas => {
                    const option = document.createElement('option');
                    option.value = fasilitas;
                    option.textContent = fasilitas;
                    if (currentName === fasilitas) option.selected = true;
                    selectNama.appendChild(option);
                });
            } else {
                selectNama.innerHTML = '<option value="">-- Pilih Kategori Terlebih Dahulu --</option>';
            }

            // AUTO-ADD: Data Lama/Custom
            let exists = Array.from(selectNama.options).some(o => o.value === currentName);
            if (currentName && !exists) {
                const option = document.createElement('option');
                option.value = currentName;
                option.textContent = currentName + " (Data Lama)";
                option.selected = true;
                selectNama.appendChild(option);
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            <?php if($edit_mode): ?>
                const dbKategori = "<?php echo $data_edit['kategori']; ?>";
                const dbNama = "<?php echo $data_edit['nama_fasilitas']; ?>";
                
                let katExists = Array.from(selectKategori.options).some(o => o.value === dbKategori);
                if(!katExists && dbKategori !== ""){
                    const option = document.createElement('option');
                    option.value = dbKategori;
                    option.textContent = dbKategori + " (Data Lama)";
                    selectKategori.appendChild(option);
                }
                
                selectKategori.value = dbKategori;
                updateFasilitas(dbNama);
            <?php endif; ?>
        });
    </script>

</body>
</html>