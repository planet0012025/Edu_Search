<?php
session_start();
include '../config/koneksi.php';

// 1. Cek Login Admin
if(!isset($_SESSION['status_login']) || $_SESSION['status_login'] != true){
    echo '<script>window.location="login.php"</script>';
    exit;
}

// 2. Proses Hapus
if(isset($_GET['id'])){
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);

    // --- TAHAP A: HAPUS FILE UTAMA SEKOLAH (Logo, SK, Video) ---
    $cek_sekolah = mysqli_query($koneksi, "SELECT foto_logo, file_sk, video_profil FROM tb_sekolah WHERE id_sekolah = '$id'");
    if(mysqli_num_rows($cek_sekolah) > 0){
        $d = mysqli_fetch_object($cek_sekolah);
        
        // Hapus Logo
        if(!empty($d->foto_logo) && file_exists('../uploads/'.$d->foto_logo)){
            unlink('../uploads/'.$d->foto_logo);
        }
        // Hapus File SK
        if(!empty($d->file_sk) && file_exists('../uploads/'.$d->file_sk)){
            unlink('../uploads/'.$d->file_sk);
        }
        // Hapus Video Profil
        if(!empty($d->video_profil) && file_exists('../uploads/'.$d->video_profil)){
            unlink('../uploads/'.$d->video_profil);
        }
    }

    // --- TAHAP B: HAPUS FASILITAS TERKAIT (Data & Gambar) ---
    $cek_fasilitas = mysqli_query($koneksi, "SELECT foto FROM tb_fasilitas WHERE id_sekolah = '$id'");
    while($f = mysqli_fetch_object($cek_fasilitas)){
        if(!empty($f->foto) && file_exists('../uploads/fasilitas/'.$f->foto)){
            unlink('../uploads/fasilitas/'.$f->foto);
        }
    }
    // Hapus data fasilitas dari DB
    mysqli_query($koneksi, "DELETE FROM tb_fasilitas WHERE id_sekolah = '$id'");

    // --- TAHAP C: HAPUS GALERI TERKAIT (Data & Gambar) ---
    $cek_galeri = mysqli_query($koneksi, "SELECT file_foto FROM tb_galeri WHERE id_sekolah = '$id'");
    while($g = mysqli_fetch_object($cek_galeri)){
        if(!empty($g->file_foto) && file_exists('../uploads/galeri/'.$g->file_foto)){
            unlink('../uploads/galeri/'.$g->file_foto);
        }
    }
    // Hapus data galeri dari DB
    mysqli_query($koneksi, "DELETE FROM tb_galeri WHERE id_sekolah = '$id'");

    // --- TAHAP D: HAPUS AKUN MITRA (Jika Ada) ---
    mysqli_query($koneksi, "DELETE FROM tb_mitra WHERE id_sekolah = '$id'");

    // --- TAHAP E: HAPUS DATA UTAMA SEKOLAH ---
    $delete = mysqli_query($koneksi, "DELETE FROM tb_sekolah WHERE id_sekolah = '$id'");

    if($delete){
        echo "<script>alert('Data sekolah beserta seluruh fasilitas, galeri, dan akun mitra berhasil dihapus secara permanen.'); window.location='data_sekolah.php'</script>";
    } else {
        echo "<script>alert('Gagal menghapus data sekolah: ".mysqli_error($koneksi)."'); window.location='data_sekolah.php'</script>";
    }

} else {
    echo "<script>window.location='data_sekolah.php'</script>";
}
?>