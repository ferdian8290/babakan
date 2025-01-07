<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/vendor/autoload.php'; // Autoload Composer

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$targetIP = getenv('IP_TARGET');
// echo $targetIP; 
// die();

// URL dan kredensial
$urls = [
    'https://10.147.17.212/rest/ppp/secret',
    'https://10.147.17.212/rest/ppp/active'
];
$username = 'root';
$password = 'pi';

// Fungsi untuk melakukan request curl
function executeCurl($url, $username, $password)
{
    // Inisialisasi curl
    $ch = curl_init();

    // Set opsi curl
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password"); // Autentikasi
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);          // Abaikan verifikasi SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);          // Abaikan validasi hostname
    curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1'); // Set cipher

    // Eksekusi curl
    $response = curl_exec($ch);

    // Periksa error
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        $response = json_encode(['error' => $error]);
    }

    // Tutup curl
    curl_close($ch);

    // Return hasil (response atau error dalam JSON)
    return $response;
}

function pingAsync($ip)
{
    // URL untuk ping, ganti dengan API atau skrip yang memproses ping
    $pingUrl = "http://example.com/ping?ip=$ip";

    // Inisialisasi cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pingUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout setelah 5 detik

    return $ch;
}

// Array untuk menampung hasil
$results = [];

// Jalankan untuk setiap URL dan ambil response
$secretData = json_decode(executeCurl($urls[0], $username, $password), true);
$activeData = json_decode(executeCurl($urls[1], $username, $password), true);

// Gabungkan data berdasarkan username (name)
foreach ($secretData as $secret) {
    $username = $secret['name'];
    
    // Cari data aktif yang sesuai dengan username
    $activeStatus = 'inactive'; // Default status
    foreach ($activeData as $active) {
        if ($active['name'] == $username) {
            $activeStatus = 'active'; // Status aktif jika ditemukan
            break;
        }
    }
    
    
    // Gabungkan data dengan keterangan "active" atau "inactive"
    $results[] = array_merge($secret, ['status' => $activeStatus]);
}

// Encode hasil menjadi JSON dan tampilkan
echo json_encode($results, JSON_PRETTY_PRINT);
?>
