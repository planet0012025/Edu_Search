<?php
session_start();
include '../config/koneksi.php';

if(isset($_POST['login'])){
    $user = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass = md5($_POST['password']);

    $cek = mysqli_query($koneksi, "SELECT * FROM tb_mitra WHERE username='$user' AND password='$pass'");
    
    if(mysqli_num_rows($cek) > 0){
        $d = mysqli_fetch_object($cek);
        if($d->status_akun == 'Nonaktif'){
            echo '<script>alert("Akun Anda dinonaktifkan oleh Admin Pusat.");</script>';
        } else {
            $_SESSION['mitra_login'] = true;
            $_SESSION['id_mitra'] = $d->id_mitra;
            $_SESSION['id_sekolah'] = $d->id_sekolah;
            $_SESSION['nama_mitra'] = $d->nama_lengkap;
            echo '<script>window.location="index.php"</script>';
        }
    } else {
        echo '<script>alert("Username atau Password salah!");</script>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Mitra Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
        h2 { color: #0d6efd; margin-bottom: 10px; }
        p { color: #666; font-size: 14px; margin-bottom: 30px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; outline: none; }
        input:focus { border-color: #0d6efd; }
        button { width: 100%; padding: 12px; background: #0d6efd; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: #0b5ed7; }
        .back-link { display: block; margin-top: 20px; font-size: 13px; color: #888; text-decoration: none; }
        .back-link:hover { color: #333; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Portal Mitra</h2>
        <p>Login khusus pengelola sekolah</p>
        <form action="" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Masuk Dashboard</button>
        </form>
        <a href="../index.php" class="back-link">&larr; Kembali ke Website Utama</a>
    </div>
</body>
</html>