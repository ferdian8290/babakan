#!/usr/bin/env python3
from librouteros import connect
from prettytable import PrettyTable
import re
import subprocess
import threading
import requests
import csv
from dotenv import load_dotenv
import os

load_dotenv()

# INI CONTOH

# Ganti dengan informasi MikroTik Anda
HOST = os.getenv('IP_TARGET')  # Alamat IP MikroTik
USERNAME = os.getenv('USERNAME_SERVER')     # Username MikroTik
PASSWORD = os.getenv('PASSWORD_SERVER')   # Password MikroTik

def ip_key(ip):
    """Fungsi untuk mengonversi alamat IP menjadi tuple dari integer untuk pengurutan."""
    return tuple(int(part) for part in ip.split('.'))

def parse_duration(duration):
    """Fungsi untuk mengonversi durasi aktif ke format yang lebih baik."""
    days = hours = minutes = 0

    # Regex untuk mencocokkan durasi
    day_match = re.search(r'(\d+)d', duration)
    hour_match = re.search(r'(\d+)h', duration)
    minute_match = re.search(r'(\d+)m', duration)

    if day_match:
        days = int(day_match.group(1))
    if hour_match:
        hours = int(hour_match.group(1))
    if minute_match:
        minutes = int(minute_match.group(1))

    # Membuat string durasi yang lebih baik
    parts = []
    if days > 0:
        parts.append(f"{days} day{'s' if days > 1 else ''}")
    if hours > 0:
        parts.append(f"{hours} hour{'s' if hours > 1 else ''}")
    if minutes > 0:
        parts.append(f"{minutes} minute{'s' if minutes > 1 else ''}")

    return ', '.join(parts) if parts else '0 minutes'

# def ping_ip(ip_address, result_dict):
#     """Fungsi untuk melakukan ping ke alamat IP dan menyimpan hasilnya."""
#     try:
#         # Jalankan perintah ping (4 paket untuk contoh)
#         output = subprocess.run(['ping', '-c', '4', ip_address], capture_output=True, text=True, timeout=10)
#         if output.returncode == 0:
#             result_dict[ip_address] = "Success"
#         else:
#             result_dict[ip_address] = "Failed"
#     except subprocess.TimeoutExpired:
#         result_dict[ip_address] = "Ping Timeout"
#     except Exception as e:
#         result_dict[ip_address] = f"Ping Error: {e}"

# def check_http_response(ip_address, http_result_dict):
#     """Fungsi untuk memeriksa respons HTTP dari alamat IP dan mengembalikan Yes atau No."""
#     try:
#         # Kirim permintaan HTTP GET ke alamat IP
#         response = requests.get(f"http://{ip_address}", timeout=10)
#         if response.status_code == 200:
#             http_result_dict[ip_address] = "Yes"  # Ganti "OK" menjadi "Yes"
#         else:
#             http_result_dict[ip_address] = "No"   # Ganti "Not OK" menjadi "No"
#     except requests.exceptions.RequestException:
#         http_result_dict[ip_address] = "No"       # Ganti "Not OK" menjadi "No"

try:
    # Koneksi ke MikroTik
    api = connect(username=USERNAME, password=PASSWORD, host=HOST)

    # Mengambil daftar pengguna PPPoE dan mengonversi generator ke list
    pppoe_users = list(api('/ppp/secret/print'))

    # Mengambil koneksi aktif PPPoE dan mengonversi generator ke list
    active_connections = list(api('/ppp/active/print'))

    # Membuat tabel
    table = PrettyTable()
    table.field_names = ["Username", "IP_Address", "User_Status", "Active_Status", "Ping_Result", "Remoteable", "Profile", "Caller_ID", "Active_Duration", "Last_Logged_Out", "Last_Disconnect_Reason"]

    # Membuat dictionary untuk koneksi aktif
    active_dict = {conn.get('name', ''): conn for conn in active_connections}

    # Dictionary untuk menyimpan hasil ping dan HTTP response
    ping_results = {}
    http_results = {}

    # List untuk menyimpan thread
    threads = []

    # Membuat thread untuk setiap remote_address
    for user in pppoe_users:
        remote_address = user.get('remote-address', '')
        if remote_address:  # Hanya lakukan ping dan HTTP check jika remote address tidak kosong
            # Thread untuk ping
            # ping_thread = threading.Thread(target=ping_ip, args=(remote_address, ping_results))
            # threads.append(ping_thread)
            # ping_thread.start()

            # Thread untuk HTTP response check
            # http_thread = threading.Thread(target=check_http_response, args=(remote_address, http_results))
            # threads.append(http_thread)
            # http_thread.start()

    # Menunggu semua thread selesai
    for thread in threads:
        thread.join()

    # Mengisi tabel dengan data pengguna PPPoE
    for user in pppoe_users:
        status = "Enabled" if not user.get('disabled', False) else "Disabled"
        if user.get('name') in active_dict:
            active_status = "Active"
            duration = active_dict[user.get('name')].get('uptime', 'N/A')  # Mendapatkan uptime
            formatted_duration = parse_duration(duration)  # Memformat durasi
            last_disconnect_reason = user.get('last-disconnect-reason', 'N/A')
            last_logged_out = user.get('last-logged-out', 'N/A')
            caller_id = active_dict[user.get('name')].get('caller-id', 'N/A')  # Mengambil Caller-ID dari active_connections
        else:
            active_status = "Inactive"
            formatted_duration = 'N/A'
            last_disconnect_reason = 'N/A'
            last_logged_out = 'N/A'
            caller_id = 'N/A'  # Jika tidak aktif, Caller-ID diisi N/A
        
        remote_address = user.get('remote-address', '')
        # Mengambil hasil ping dan HTTP response dari dictionary
        # ping_result = ping_results.get(remote_address, 'N/A')
        # http_result = http_results.get(remote_address, 'N/A')
        
        table.add_row([
            user.get('name', ''),
            remote_address,
            status,
            active_status,
            ping_result,  # Ping Result
            http_result,  # Remoteable
            user.get('profile', ''),  # Profile dipindahkan ke sini
            caller_id,
            formatted_duration,
            last_logged_out,
            last_disconnect_reason
        ])

    # Mengurutkan tabel berdasarkan IP Address dengan benar
    sorted_rows = sorted(table.rows, key=lambda row: ip_key(row[1]))  # Mengurutkan berdasarkan kolom IP Address

    # Menyimpan tabel ke file CSV dengan delimiter ;
    with open('pppoe_users.csv', mode='w', newline='') as csv_file:
        csv_writer = csv.writer(csv_file, delimiter=';')
        csv_writer.writerow(table.field_names)  # Menulis header
        for row in sorted_rows:
            csv_writer.writerow(row)  # Menulis setiap baris data

except Exception as e:
    print(f'Error: {e}')