// table-sort.js
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('sortableTable');
    const headers = table.querySelectorAll('th');
    const tbody = table.querySelector('tbody');
    let rows = Array.from(tbody.querySelectorAll('tr'));

    // Загружаем сохраненную сортировку из localStorage
    const savedSort = localStorage.getItem('tableSort');
    if (savedSort) {
        const { column, direction } = JSON.parse(savedSort);
        setTimeout(() => sortTable(column, true), 100);
    }

    // Добавляем обработчики кликов на заголовки
    headers.forEach((header, index) => {
        header.addEventListener('click', () => {
            sortTable(index);
        });
    });

    function sortTable(columnIndex, skipSave = false) {
        // Получаем текущее состояние сортировки
        const currentSort = headers[columnIndex].getAttribute('data-sort');

        // Сбрасываем сортировку для всех заголовков
        headers.forEach(header => {
            header.setAttribute('data-sort', 'none');
            header.classList.remove('sort-asc', 'sort-desc');
        });

        // Определяем направление сортировки
        let sortDirection;
        if (currentSort === 'none' || currentSort === 'desc') {
            sortDirection = 'asc';
            headers[columnIndex].setAttribute('data-sort', 'asc');
            headers[columnIndex].classList.add('sort-asc');
        } else {
            sortDirection = 'desc';
            headers[columnIndex].setAttribute('data-sort', 'desc');
            headers[columnIndex].classList.add('sort-desc');
        }

        // Сортируем строки
        rows.sort((rowA, rowB) => {
            const cellA = rowA.cells[columnIndex].textContent.trim();
            const cellB = rowB.cells[columnIndex].textContent.trim();

            // Проверяем, являются ли значения числами
            const numA = parseFloat(cellA.replace(/[^\d.-]/g, ''));
            const numB = parseFloat(cellB.replace(/[^\d.-]/g, ''));

            if (!isNaN(numA) && !isNaN(numB)) {
                return sortDirection === 'asc' ? numA - numB : numB - numA;
            } else {
                if (cellA < cellB) return sortDirection === 'asc' ? -1 : 1;
                if (cellA > cellB) return sortDirection === 'asc' ? 1 : -1;
                return 0;
            }
        });

        // Очищаем таблицу и добавляем отсортированные строки
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));

        // Сохраняем состояние сортировки
        if (!skipSave) {
            localStorage.setItem('tableSort', JSON.stringify({
                column: columnIndex,
                direction: sortDirection
            }));
        }
    }
});