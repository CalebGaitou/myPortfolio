document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchBar');
    if (!input) {
        return;
    }

    const items = document.querySelectorAll('[data-search]');
    const noResults = document.querySelector('.noResults');

    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        let visible = 0;

        items.forEach((item) => {
            const text = (item.getAttribute('data-search') + ' ' + item.textContent).toLowerCase();
            const match = text.includes(query);
            item.style.display = match ? '' : 'none';
            if (match) {
                visible++;
            }
        });

        if (noResults) {
            noResults.hidden = visible > 0;
        }
    });
});
