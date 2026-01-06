<?php

$host = "localhost";

$user = "root";

$pass = "";

$db   = "db_portal_sekolah";



$koneksi = mysqli_connect($host, $user, $pass, $db);



if (!$koneksi) {

    die("Gagal terhubung ke database: " . mysqli_connect_error());

}



// --- FUNGSI HITUNG JARAK (GLOBAL) ---

// Menghitung jarak antara dua titik koordinat (Latitude, Longitude)

// Rumus: Haversine Formula

function hitungJarak($lat1, $lon1, $lat2, $lon2) {

    // Jika koordinat kosong/0, kembalikan strip

    if(empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) {

        return "-";

    }



    $theta = $lon1 - $lon2;

    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));

    $dist = acos($dist);

    $dist = rad2deg($dist);

    $miles = $dist * 60 * 1.1515;

    $km = $miles * 1.609344;

   

    return number_format($km, 2); // Mengembalikan string angka 2 desimal (contoh: 5.20)

}



// --- SETTING MYSQLI LEBIH AMAN ---

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

mysqli_set_charset($koneksi, 'utf8mb4');



// --- SESSION SAFE (biar include ini aman dipanggil darimana aja) ---

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}



// --- HELPER ESCAPE OUTPUT ---

function e($str) {

    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');

}



// --- CSRF TOKEN (untuk form POST admin/user) ---

function csrf_token() {

    if (empty($_SESSION['_csrf'])) {

        $_SESSION['_csrf'] = bin2hex(random_bytes(16));

    }

    return $_SESSION['_csrf'];

}



function csrf_verify($token) {

    if (empty($token) || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {

        http_response_code(400);

        die("CSRF token tidak valid.");

    }

}





?>

