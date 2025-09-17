document.getElementById('roomsBtn').addEventListener('click', function() {
    const arrow = document.getElementById('arrowUp');
    if (arrow.textContent == '▲') {
        arrow.textContent = '▼'; 
    } else {
        arrow.textContent = '▲'; 
    }
});
