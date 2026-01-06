<?php
session_start();
include '../config/koneksi.php';

// Logika saat tombol Login diklik
if(isset($_POST['login'])) {
    $user = $_POST['user'];
    $pass = md5($_POST['pass']); // Enkripsi password inputan dengan MD5

    $cek = mysqli_query($koneksi, "SELECT * FROM tb_admin WHERE username = '$user' AND password = '$pass'");
    
    if(mysqli_num_rows($cek) > 0){
        $d = mysqli_fetch_object($cek);
        $_SESSION['status_login'] = true;
        $_SESSION['a_global'] = $d;
        $_SESSION['id'] = $d->id_admin;
        echo '<script>window.location="index.php"</script>';
    } else {
        echo '<script>alert("Username atau password salah!")</script>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Admin - Portal Sekolah</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .box-login { background: #fff; padding: 20px; width: 300px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 5px 0; box-sizing: border-box; }
        .btn { background: #28a745; color: #fff; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <div class="box-login">
        <h2 style="text-align:center;">Login Admin</h2>
        <form action="" method="POST">
            <input type="text" name="user" placeholder="Username" class="input-control" required>
            <input type="password" name="pass" placeholder="Password" class="input-control" required>
            <input type="submit" name="login" value="Login" class="btn">
        </form>
    </div>

</body>
</html>