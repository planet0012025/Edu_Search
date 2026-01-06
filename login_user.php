<?php
session_start();
include 'config/koneksi.php';

// Jika sudah login, langsung lempar ke index
if(isset($_SESSION['uid_ortu'])){
    echo '<script>window.location="index.php"</script>';
}

if(isset($_POST['login'])){
    $email = trim($_POST['email'] ?? '');
    $passPlain = $_POST['pass'] ?? '';

    // ambil user berdasarkan email (prepared statement)
    $stmt = $koneksi->prepare("SELECT id_ortu, nama_lengkap, password FROM tb_orang_tua WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if($row){
        $stored = $row['password'];
        $ok = false;

        // kompatibilitas: jika masih md5 (32 hex), cek md5; kalau bukan, cek password_hash
        if (strlen($stored) === 32 && ctype_xdigit($stored)) {
            $ok = hash_equals($stored, md5($passPlain));
        } else {
            $ok = password_verify($passPlain, $stored);
        }

        if($ok){
            session_regenerate_id(true);

            $_SESSION['uid_ortu']  = (int)$row['id_ortu'];
            $_SESSION['nama_ortu'] = $row['nama_lengkap'];

            // (opsional, biar lebih konsisten ke depan)
            $_SESSION['role'] = 'ortu';

            if(isset($_GET['redirect']) && $_GET['redirect'] == 'quiz'){
                echo '<script>window.location="assessment.php"</script>';
            } else {
                echo '<script>window.location="index.php"</script>';
            }
            exit;
        }
    }

    echo '<script>alert("Email atau Password salah!");</script>';
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Orang Tua - EduSearch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=12">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .auth-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header h2 { color: #0d6efd; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        .btn-auth { width: 100%; padding: 12px; background: #0d6efd; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-auth:hover { background: #0b5ed7; }
        .auth-footer { text-align: center; margin-top: 20px; font-size: 14px; }
        .auth-footer a { color: #0d6efd; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body>

    <div class="auth-box">
        <div class="auth-header">
            <i class="fas fa-user-circle" style="font-size: 50px; color: #0d6efd; margin-bottom: 10px;"></i>
            <h2>Login Orang Tua</h2>
            <p>Masuk untuk mengakses fitur rekomendasi.</p>
        </div>

        <form action="" method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="Email Anda">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="pass" required placeholder="******">
            </div>
            <button type="submit" name="login" class="btn-auth">Masuk</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="register_user.php">Daftar sekarang</a>
            <br><br>
            <a href="index.php" style="color:#888; font-weight:normal;"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
        </div>
    </div>

</body>
</html>