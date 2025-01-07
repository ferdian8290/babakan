<?php

// Mengambil parameter IP dari URL (misal: ?ip=192.168.1.1)
$ip = $_GET['ip'];

function ping($ip)
{
    $pingResult = shell_exec("ping -c 1 $ip");

    // Memeriksa apakah paket berhasil diterima
    if (strpos($pingResult, '1 packets transmitted, 1 received') !== false) {
        return 'reachable'; // IP dapat dijangkau
    }
    return 'unreachable'; // IP tidak dapat dijangkau
}

// Menyiapkan data dalam format JSON
$result = ping($ip);

// Mengatur header agar output berupa JSON
header('Content-Type: application/json');

// Mengirimkan hasilnya dalam format JSON
echo json_encode(['status' => $result]);
?>
