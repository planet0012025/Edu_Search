<?php 
include 'config/koneksi.php';
$id = $_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM tb_pendaftaran 
         JOIN tb_sekolah ON tb_pendaftaran.id_sekolah = tb_sekolah.id_sekolah 
         WHERE id_daftar = '$id'");
$d = mysqli_fetch_object($query);

if(!$d){ die("Data tidak ditemukan"); }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bukti Pendaftaran - <?php echo $d->nama_siswa ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 40px; -webkit-print-color-adjust: exact; }
        .container { width: 100%; max-width: 700px; margin: 0 auto; border: 1px solid #000; padding: 30px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 14px; }
        
        .content table { width: 100%; }
        .content td { padding: 8px 0; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        
        .status-box { 
            border: 2px solid #000; padding: 10px; text-align: center; 
            font-weight: bold; font-size: 20px; margin: 30px 0; display: inline-block; width: 100%;
        }
        .footer { margin-top: 50px; text-align: right; }
        
        @media print {
            @page { margin: 0; }
            body { margin: 2cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container">
        <div class="header">
            <h1>BUKTI PENDAFTARAN SEKOLAH</h1>
            <p>EduSearch Portal Indonesia</p>
        </div>

        <div class="content">
            <p>Telah diterima data pendaftaran calon siswa baru dengan rincian sebagai berikut:</p>
            <br>
            <table>
                <tr><td class="label">ID Pendaftaran</td><td>: #REG-<?php echo sprintf('%04d', $d->id_daftar) ?></td></tr>
                <tr><td class="label">Tanggal Daftar</td><td>: <?php echo date('d F Y H:i', strtotime($d->tanggal_daftar)) ?></td></tr>
                <tr><td colspan="2"><hr></td></tr>
                <tr><td class="label">Nama Sekolah Tujuan</td><td>: <b><?php echo $d->nama_sekolah ?></b></td></tr>
                <tr><td class="label">Alamat Sekolah</td><td>: <?php echo $d->alamat ?></td></tr>
                <tr><td colspan="2"><hr></td></tr>
                <tr><td class="label">Nama Calon Siswa</td><td>: <?php echo $d->nama_siswa ?></td></tr>
                <tr><td class="label">Nama Orang Tua/Wali</td><td>: <?php echo $d->nama_ortu ?></td></tr>
                <tr><td class="label">Nomor HP / WA</td><td>: <?php echo $d->no_hp ?></td></tr>
                <tr><td class="label">Email</td><td>: <?php echo $d->email ?></td></tr>
            </table>

            <div class="status-box">
                STATUS SAAT INI: <?php echo strtoupper($d->status) ?>
            </div>
            
            <p style="font-size: 12px; font-style: italic;">
                *Simpan bukti pendaftaran ini untuk keperluan verifikasi ulang di sekolah.<br>
                *Silakan pantau status pendaftaran Anda secara berkala di website EduSearch.
            </p>
        </div>

        <div class="footer">
            <p>Jakarta, <?php echo date('d F Y') ?></p>
            <br><br><br>
            <p>( Panitia PPDB )</p>
        </div>
    </div>

</body>
</html>