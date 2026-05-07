// Sidebar Toggle
const toggleSidebar = document.querySelector('.toggle-sidebar');
const sidebar = document.querySelector('.sidebar');
const mainContent = document.querySelector('.main-content');

toggleSidebar.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('sidebar-collapsed');
});

// Close alerts
document.querySelectorAll('.close-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.target.closest('.alert').style.animation = 'slideUp 0.3s ease forwards';
        setTimeout(() => {
            e.target.closest('.alert').remove();
        }, 300);
    });
});

// Tolak pesanan confirmation
function tolakPesanan(bookingId) {
    if (confirm('Yakin ingin menolak pesanan ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="booking_id" value="${bookingId}">
            <input type="hidden" name="action" value="tolak">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Search functionality
document.getElementById('search')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.admin-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Refresh button
document.querySelector('.refresh-btn')?.addEventListener('click', () => {
    location.reload();
});

// Animate stats on load
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
});

document.querySelectorAll('.stat-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
});