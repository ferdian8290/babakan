<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php'; // Autoload Composer

use Dotenv\Dotenv;

// Memuat file .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();  // Memuat variabel dari file .env

$IP = '10.147.17.212';

// Mengambil nilai IP_TARGET dari environment
$targetIP = $IP;
$hostMessage = "";

// Fungsi untuk memeriksa status host
function checkHostStatus($host) {
    exec("ping -c 4 $host", $output, $returnVar);
    return $returnVar === 0;
}

// IP yang akan di-ping sebelum menjalankan skrip Python

// Cek status host
if (checkHostStatus($targetIP)) {
    shell_exec('python3 babakan.py');
    $hostMessage = "Host $targetIP dapat dijangkau. Menjalankan skrip Python...";
} else {
    $hostMessage = "Host $targetIP tidak dapat dijangkau. Skrip Python tidak dijalankan.";
}

// Hitung waktu eksekusi
$startTime = microtime(true);
shell_exec('python3 babakan.py');
$endTime = microtime(true);
$executionTime = $endTime - $startTime;

// Cek waktu pembuatan file pppoe_users.csv
$csvFile = "pppoe_users.csv";
$fileCreationTime = file_exists($csvFile) ? filemtime($csvFile) : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPPoE Users</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f7f9fc;
            color: #333333;
            font-family: 'Roboto', sans-serif;
        }
        .dark-mode {
            background-color: #2a2a2a;
            color: #f8f9fa;
        }
        .sticky-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background-color: #ffffff;
            padding: 10px 15px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-container {
            margin-top: 190px; /* Adjusted for sticky headers */
        }
        .table-responsive {
            max-height: 70vh; /* Scrollable area for the table */
            overflow-y: auto;
        }
        .table th {
            position: sticky;
            top: 0;
            background-color: #f1f1f1;
            z-index: 1020;
        }
        .table td, .table th {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #eef4fa;
        }
        .table-striped tbody tr:hover {
            background-color: #d8e2f0;
        }
        .search-container {
            margin: 20px 0;
        }
        .search-container input {
            width: 50%;
            margin: auto;
            border: 2px solid #9fbcdf;
            border-radius: 25px;
            padding: 10px 15px;
            outline: none;
            transition: all 0.3s ease;
        }
        .search-container input:focus {
            border-color: #6c93d0;
            box-shadow: 0 0 10px #6c93d0;
        }

        .alert-apadeh{
            padding: 5px !important;
        }
        
    </style>
</head>
<body>
    <!-- Sticky Header -->
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="text-center" id="name-app">PPPoE Users</h1>
            <button id="darkModeToggle" class="btn btn-outline-primary">
                🌙 <span>Dark Mode</span>
            </button>
        </div>
        <div class="search-container text-center">
            <input type="text" id="searchBox" class="form-control" placeholder="Search by Username, IP Address, or Profile...">
        </div>
    </div>

    <div class="container table-container">
        <!-- Status Host dan Waktu Eksekusi -->
        <?php if (!empty($hostMessage)): ?>
    <div class="alert alert-info text-center mt-4 alert-apadeh">
        <?php echo $hostMessage; ?>
    </div>
<?php endif; ?>
        <?php if ($fileCreationTime): ?>
            <div class="alert alert-success text-center alert-apadeh">
                Execution Time: <strong><?php echo number_format($executionTime, 4); ?> seconds</strong>
                | Last Modified: <strong><?php echo date("Y-m-d H:i:s", $fileCreationTime); ?></strong>
            </div>
        <?php endif; ?>

        <!-- Tabel PPPoE Users -->
        <?php if (file_exists($csvFile)): ?>
            <div class="table-responsive">
            <table id="pppoeTable" class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <?php
                        if (($handle = fopen($csvFile, "r")) !== FALSE) {
                            $header = fgetcsv($handle, 1000, ";");
                            if ($header !== FALSE) {
                                foreach ($header as $column) {
                                    echo "<th>" . htmlspecialchars(str_replace('_', ' ', $column)) . "</th>";
                                }
                                echo "<th>Edit</th>";
                            }
                            fclose($handle);
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (($handle = fopen($csvFile, "r")) !== FALSE) {
                        fgetcsv($handle, 1000, ";");
                        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                            echo "<tr>";
                            foreach ($data as $cell) {
                                echo "<td>" . htmlspecialchars($cell) . "</td>";
                            }
                            echo "<td><button class='btn btn-warning btn-sm'>Edit</button></td>";
                            echo "</tr>";
                        }
                        fclose($handle);
                    }
                    ?>
                </tbody>
            </table>
            </div>
        <?php else: ?>
            <div class="alert alert-danger text-center">
                File PPPoE Users tidak ditemukan.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle Dark Mode
            $('#darkModeToggle').click(function() {
                $('body').toggleClass('dark-mode');
                $('.sticky-header').toggleClass('dark-mode');
                
                // Change button text based on dark mode status
                if ($('body').hasClass('dark-mode')) {
                    $('#darkModeToggle').html('☀️ <span>Light Mode</span>');
                    $('#name-app').css('color', 'black'); 
                    $('#pppoeTable').removeClass('table-striped').addClass('table-dark');
                    $('#darkModeToggle').html('🌙 <span>Dark Mode</span>');
                } else {
                    $('#pppoeTable').removeClass('table-dark').addClass('table-striped');
                    $('#darkModeToggle').html('☀️ <span>Light Mode</span>');
                }
            });

            // Search functionality
            $('#searchBox').on('keyup', function() {
                const filter = $(this).val().toLowerCase();
                $('#pppoeTable tbody tr').each(function() {
                    let found = false;
                    $(this).find('td').each(function() {
                        if ($(this).text().toLowerCase().includes(filter)) {
                            found = true;
                            return false; // Break the loop when a match is found
                        }
                    });
                    $(this).toggle(found);
                });
            });
        });
    </script>
</body>
</html>
