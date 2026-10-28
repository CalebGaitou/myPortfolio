document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchBar');
    if (!input) {
        return;
    }

    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        document.querySelectorAll('[data-search]').forEach((item) => {
            const text = item.getAttribute('data-search') || item.textContent;
            item.style.display = text.toLowerCase().includes(query) ? '' : 'none';
        });
    });
});
