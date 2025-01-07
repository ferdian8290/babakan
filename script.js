document.getElementById('toggleDarkMode').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
    const icon = document.getElementById('darkModeIcon');
    icon.textContent = icon.textContent === '🌙' ? '☀️' : '🌙';
    icon.classList.toggle('rotate'); // Tambahkan class untuk animasi
});

// Fungsi pencarian
document.getElementById('searchBox').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#pppoeTable tbody tr');

    rows.forEach(row => {
        const cells = row.getElementsByTagName('td');
        let found = false;

        for (let i = 0; i < cells.length; i++) {
            if (cells[i].textContent.toLowerCase().includes(filter)) {
                found = true;
                break;
            }
        }

        row.style.display = found ? '' : 'none';
    });
});