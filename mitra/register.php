<?php
session_start();
include '../config/koneksi.php';

// Generate Kode Sekolah Otomatis (Format: SCH-YYYY-XXXX)
$tahun = date('Y');
$q_kode = mysqli_query($koneksi, "SELECT MAX(id_sekolah) as max_id FROM tb_sekolah");
$d_kode = mysqli_fetch_assoc($q_kode);
$next_id = $d_kode['max_id'] + 1;
$kode_sistem = "SCH-" . $tahun . "-" . sprintf("%04d", $next_id);

// Ambil Daftar Sekolah untuk Dropdown
$list_sekolah = mysqli_query($koneksi, "SELECT id_sekolah, nama_sekolah, alamat FROM tb_sekolah ORDER BY nama_sekolah ASC");

if(isset($_POST['daftar'])){
    // Data Akun Mitra
    $nama_operator = mysqli_real_escape_string($koneksi, $_POST['nama_operator']);
    $wa_admin      = mysqli_real_escape_string($koneksi, $_POST['no_wa_admin']);
    $username      = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass          = md5($_POST['password']);
    
    // Cek Username Ganda
    $cek_user = mysqli_query($koneksi, "SELECT * FROM tb_mitra WHERE username='$username'");
    if(mysqli_num_rows($cek_user) > 0){
        echo '<script>alert("Username sudah digunakan! Silakan pilih yang lain.");</script>';
    } else {
        
        $id_sekolah_final = 0;
        $pilihan_sekolah = $_POST['pilihan_sekolah']; // 'existing' atau 'new'

        if($pilihan_sekolah == 'new'){
            // === SKENARIO 1: DAFTAR SEKOLAH BARU ===
            
            // Identitas Sekolah Baru
            $npsn         = mysqli_real_escape_string($koneksi, $_POST['npsn']);
            $nama_sekolah = mysqli_real_escape_string($koneksi, $_POST['nama_sekolah_new']);
            $jenjang      = mysqli_real_escape_string($koneksi, $_POST['jenjang']);
            $status_sek   = mysqli_real_escape_string($koneksi, $_POST['status_sekolah']);
            $alamat       = mysqli_real_escape_string($koneksi, $_POST['alamat']);
            $provinsi     = mysqli_real_escape_string($koneksi, $_POST['provinsi']);
            $kabupaten    = mysqli_real_escape_string($koneksi, $_POST['kabupaten']);
            $kecamatan    = mysqli_real_escape_string($koneksi, $_POST['kecamatan']);
            $kodepos      = mysqli_real_escape_string($koneksi, $_POST['kode_pos']);
            $nama_kepsek  = mysqli_real_escape_string($koneksi, $_POST['nama_kepsek']);
            $email_resmi  = mysqli_real_escape_string($koneksi, $_POST['email_sekolah']);
            $akreditasi   = mysqli_real_escape_string($koneksi, $_POST['akreditasi']); // Tambahan

            // Cek NPSN Ganda
            $cek_npsn = mysqli_query($koneksi, "SELECT * FROM tb_sekolah WHERE npsn='$npsn'");
            if(mysqli_num_rows($cek_npsn) > 0){
                echo '<script>alert("NPSN sudah terdaftar! Mohon cari sekolah Anda di daftar pencarian.");</script>';
                exit; // Stop proses
            }

            // Upload SK
            $filename = $_FILES['file_sk']['name'];
            $tmp_name = $_FILES['file_sk']['tmp_name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $sk_name_db = '';

            if(in_array($file_ext, ['pdf', 'jpg', 'jpeg', 'png'])){
                $sk_name_db = 'sk_' . time() . '_' . rand(100,999) . '.' . $file_ext;
                if(move_uploaded_file($tmp_name, '../uploads/' . $sk_name_db)){
                    
                    // Insert Sekolah Baru
                    // Perhatikan penambahan kolom-kolom baru di query INSERT ini
                    $q_insert_sek = "INSERT INTO tb_sekolah (
                        npsn, nama_sekolah, jenjang, status_sekolah, akreditasi, alamat, provinsi, kabupaten, kecamatan, kode_pos,
                        nama_kepsek, email_sekolah, no_wa_admin, file_sk, foto_logo
                    ) VALUES (
                        '$npsn', '$nama_sekolah', '$jenjang', '$status_sek', '$akreditasi', '$alamat', '$provinsi', '$kabupaten', '$kecamatan', '$kodepos',
                        '$nama_kepsek', '$email_resmi', '$wa_admin', '$sk_name_db', 'default.jpg'
                    )";
                    
                    if(mysqli_query($koneksi, $q_insert_sek)){
                        $id_sekolah_final = mysqli_insert_id($koneksi);
                    } else {
                        echo '<script>alert("Gagal menyimpan data sekolah: '.mysqli_error($koneksi).'");</script>';
                        exit;
                    }
                } else {
                    echo '<script>alert("Gagal upload file SK.");</script>';
                    exit;
                }
            } else {
                echo '<script>alert("Format file SK harus PDF/JPG/PNG.");</script>';
                exit;
            }

        } else {
            // === SKENARIO 2: PILIH SEKOLAH LAMA ===
            $id_sekolah_final = mysqli_real_escape_string($koneksi, $_POST['id_sekolah_existing']);
        }

        // --- PROSES BUAT AKUN MITRA ---
        if($id_sekolah_final > 0){
            // Simpan kode sistem di tabel mitra (opsional, atau tampilkan saja ke user)
            $q_mitra = "INSERT INTO tb_mitra (id_sekolah, username, password, nama_lengkap, status_akun) 
                        VALUES ('$id_sekolah_final', '$username', '$pass', '$nama_operator', 'Pending')";
            
            if(mysqli_query($koneksi, $q_mitra)){
                echo '<script>alert("Pendaftaran Berhasil! Kode Sekolah: '.$kode_sistem.'. Silakan tunggu verifikasi admin."); window.location="login.php";</script>';
            } else {
                echo '<script>alert("Gagal membuat akun mitra: '.mysqli_error($koneksi).'");</script>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrasi Mitra Lengkap - EduSearch</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Select2 (Library Dropdown Search) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; padding: 40px 0; margin: 0; }
        .container { width: 100%; max-width: 850px; margin: 0 auto; }
        
        .reg-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        h2 { color: #0d6efd; margin-bottom: 5px; text-align: center; font-size: 24px; }
        p.subtitle { text-align: center; color: #666; font-size: 14px; margin-bottom: 30px; }
        
        /* Form Styling */
        .form-section { margin-bottom: 30px; border: 1px solid #eee; padding: 25px; border-radius: 12px; background: #fff; position: relative; }
        .section-header { font-size: 16px; font-weight: 700; color: #0d6efd; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        
        /* Grid System */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #444; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; outline: none; font-family: inherit; font-size: 14px; background: #fcfcfc; transition: 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: #0d6efd; background: #fff; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); }
        
        /* Custom Style untuk Select2 agar match dengan tema */
        .select2-container .select2-selection--single { height: 45px; border: 1px solid #ddd; border-radius: 8px; background: #fcfcfc; display: flex; align-items: center; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 43px; right: 10px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { padding-left: 12px; color: #444; font-size: 14px; }
        
        .btn-reg { width: 100%; padding: 16px; background: #0d6efd; color: white; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 16px; margin-top: 10px; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); }
        .btn-reg:hover { background: #0b5ed7; transform: translateY(-2px); }
        
        .back-link { display: block; margin-top: 20px; font-size: 13px; color: #888; text-align: center; text-decoration: none; }
        .back-link:hover { color: #333; }
        
        /* Pilihan Sekolah Baru/Lama */
        .school-choice { display: flex; gap: 15px; margin-bottom: 20px; }
        .choice-item { flex: 1; border: 2px solid #eee; padding: 15px; border-radius: 10px; cursor: pointer; text-align: center; transition: 0.3s; }
        .choice-item:hover { border-color: #0d6efd; background: #f0f8ff; }
        .choice-item.active { border-color: #0d6efd; background: #e7f1ff; color: #0d6efd; font-weight: bold; }
        .choice-item input { display: none; }
        
        #section-new-school { display: none; } /* Hidden by default */

        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="reg-box">
            <h2>Formulir Registrasi Mitra</h2>
            <p class="subtitle">Lengkapi data sekolah untuk bergabung dengan jaringan EduSearch.</p>
            
            <form action="" method="POST" enctype="multipart/form-data">
                
                <!-- BAGIAN 1: PILIH SEKOLAH -->
                <div class="form-section">
                    <div class="section-header"><i class="fas fa-school"></i> 1. Data Sekolah</div>
                    
                    <label>Apakah sekolah Anda sudah terdaftar di EduSearch?</label>
                    <div class="school-choice">
                        <label class="choice-item active" id="lbl-existing">
                            <input type="radio" name="pilihan_sekolah" value="existing" checked onchange="toggleSchoolForm()">
                            <i class="fas fa-search"></i> Sudah Terdaftar
                        </label>
                        <label class="choice-item" id="lbl-new">
                            <input type="radio" name="pilihan_sekolah" value="new" onchange="toggleSchoolForm()">
                            <i class="fas fa-plus-circle"></i> Belum (Daftar Baru)
                        </label>
                    </div>

                    <!-- A. FORM CARI SEKOLAH (EXISTING) -->
                    <div id="section-existing-school">
                        <label>Cari Nama Sekolah Anda</label>
                        <select name="id_sekolah_existing" id="select_sekolah" style="width: 100%;">
                            <option value="">-- Ketik Nama Sekolah --</option>
                            <?php while($s = mysqli_fetch_array($list_sekolah)){ ?>
                                <option value="<?php echo $s['id_sekolah'] ?>">
                                    <?php echo $s['nama_sekolah'] ?> (<?php echo substr($s['alamat'], 0, 40) ?>...)
                                </option>
                            <?php } ?>
                        </select>
                        <small style="color:#888; display:block; margin-top:5px;">*Pilih sekolah Anda dari daftar jika sudah ada.</small>
                    </div>

                    <!-- B. FORM INPUT SEKOLAH BARU (NEW) -->
                    <div id="section-new-school">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>NPSN (Nomor Pokok Sekolah Nasional)</label>
                                <input type="number" name="npsn" placeholder="8 Digit Angka">
                            </div>
                            <div class="form-group">
                                <label>Nama Sekolah</label>
                                <input type="text" name="nama_sekolah_new" id="input_nama_sekolah" placeholder="Contoh: SMA Harapan Bangsa">
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Jenjang</label>
                                <select name="jenjang">
                                    <option value="">-- Pilih --</option>
                                    <option>SD</option><option>SMP</option><option>SMA</option><option>SMK</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status Sekolah</label>
                                <select name="status_sekolah">
                                    <option value="">-- Pilih --</option>
                                    <option value="Negeri">Negeri</option>
                                    <option value="Swasta">Swasta</option>
                                    <option value="Boarding School">Boarding School</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alamat Jalan</label>
                            <textarea name="alamat" rows="2" placeholder="Nama Jalan, No, RT/RW..."></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-group"><label>Provinsi</label><input type="text" name="provinsi"></div>
                            <div class="form-group"><label>Kabupaten / Kota</label><input type="text" name="kabupaten"></div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group"><label>Kecamatan</label><input type="text" name="kecamatan"></div>
                            <div class="form-group"><label>Kode Pos</label><input type="number" name="kode_pos"></div>
                        </div>

                        <!-- 2. LEGALITAS & DOKUMEN (Untuk Sekolah Baru) -->
                         <div class="section-header" style="margin-top: 20px; border-top: 1px dashed #eee; padding-top: 15px;"><i class="fas fa-file-contract"></i> 2. Legalitas & Dokumen</div>
                         
                         <div class="form-group">
                            <label>Status Akreditasi</label>
                            <select name="akreditasi">
                                <option value="A">A (Unggul)</option>
                                <option value="B">B (Baik)</option>
                                <option value="C">C (Cukup)</option>
                                <option value="-">Belum Terakreditasi</option>
                            </select>
                        </div>

                         <div class="form-group">
                            <label style="color:#d63384;">Upload SK Izin Operasional (PDF/JPG - Max 2MB)</label>
                            <input type="file" name="file_sk" accept=".pdf,.jpg,.jpeg,.png">
                            <small style="color:#888; font-size:11px;">*Wajib untuk sekolah baru sebagai verifikasi.</small>
                        </div>
                        
                         <!-- 3. DATA KONTAK & PENANGGUNG JAWAB (Sekolah Baru) -->
                         <div class="section-header" style="margin-top: 20px; border-top: 1px dashed #eee; padding-top: 15px;"><i class="fas fa-address-book"></i> 3. Kontak & Penanggung Jawab</div>
                         
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nama Kepala Sekolah</label>
                                <input type="text" name="nama_kepsek">
                            </div>
                            <div class="form-group">
                                <label>Email Resmi Sekolah</label>
                                <input type="email" name="email_sekolah" placeholder="admin@sekolah.sch.id">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. DATA OPERATOR / AKUN (TETAP SAMA UNTUK KEDUA OPSI) -->
                <div class="form-section">
                    <div class="section-header"><i class="fas fa-user-shield"></i> 4. Akun Operator Sekolah</div>
                    
                    <div class="form-group">
                        <label>Kode Sekolah (Auto Generated)</label>
                        <input type="text" value="<?php echo $kode_sistem ?>" disabled style="font-weight:bold; color:#555; background-color: #e9ecef;">
                        <small style="color:#666;">*Kode ini akan menjadi ID unik sekolah Anda di sistem.</small>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Operator Sekolah (PIC)</label>
                            <input type="text" name="nama_operator" required placeholder="Nama Anda">
                        </div>
                        <div class="form-group">
                            <label>No. WhatsApp Admin</label>
                            <input type="number" name="no_wa_admin" required placeholder="0812xxxx">
                        </div>
                    </div>

                    <div class="form-section" style="background: #e7f1ff; border: 1px solid #b6d4fe; padding:15px;">
                        <label style="color:#0043a8;">Username Login</label>
                        <input type="text" name="username" required placeholder="Buat username unik">
                        
                        <label style="color:#0043a8; margin-top:10px;">Password</label>
                        <input type="password" name="password" required placeholder="******">
                    </div>
                </div>

                <button type="submit" name="daftar" class="btn-reg"><i class="fas fa-paper-plane"></i> Daftarkan Akun Mitra</button>
            </form>
            
            <div style="text-align:center; margin-top:20px; font-size:13px;">
                Sudah terdaftar? <a href="login.php" style="color:#0d6efd; font-weight:bold; text-decoration:none;">Login Disini</a>
            </div>
            
            <a href="../index.php" class="back-link">&larr; Kembali ke Halaman Utama</a>
        </div>
    </div>

    <!-- JQUERY & SELECT2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Aktifkan Select2 untuk pencarian
            $('#select_sekolah').select2({
                placeholder: "Ketik nama sekolah untuk mencari...",
                allowClear: true
            });

            // Logika Show/Hide Form Sekolah Baru
            $('input[name="pilihan_sekolah"]').on('change', toggleSchoolForm);
            toggleSchoolForm(); // Run on load
        });

        // Logika Ganti Tab Sekolah Baru vs Lama
        function toggleSchoolForm() {
            const isNew = $('input[name="pilihan_sekolah"]:checked').val() === 'new';
            
            const sectionExisting = $('#section-existing-school');
            const sectionNew = $('#section-new-school');
            const lblExisting = $('#lbl-existing');
            const lblNew = $('#lbl-new');

            // Input Fields untuk Toggle Required
            const inputNewNames = ['npsn', 'nama_sekolah_new', 'jenjang', 'status_sekolah', 'alamat', 'provinsi', 'kabupaten', 'kecamatan', 'kode_pos', 'file_sk', 'nama_kepsek', 'email_sekolah'];

            if (isNew) {
                sectionExisting.hide();
                sectionNew.slideDown();
                
                lblNew.addClass('active');
                lblExisting.removeClass('active');

                // Set Required untuk Form Baru
                inputNewNames.forEach(name => {
                    $(`[name="${name}"]`).prop('required', true);
                });
                // Unset Required untuk Select2
                $('#select_sekolah').prop('required', false);

            } else {
                sectionExisting.slideDown();
                sectionNew.hide();
                
                lblExisting.addClass('active');
                lblNew.removeClass('active');

                // Unset Required untuk Form Baru
                inputNewNames.forEach(name => {
                    $(`[name="${name}"]`).prop('required', false);
                });
                // Set Required untuk Select2
                $('#select_sekolah').prop('required', true);
            }
        }
    </script>
</body>
</html>