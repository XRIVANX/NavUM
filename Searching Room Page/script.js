document.getElementById('roomsBtn').addEventListener('click', function() {
    const arrow = document.getElementById('arrowUp');
    const list = document.getElementById('roomsList');
   if (list.classList.contains('show')) {
        list.classList.remove('show');
        arrow.textContent = '▲';
    } else {
          list.classList.add('show');
        arrow.textContent = '▼';
  }
});


window.addEventListener('click', function(e) {
    const list = document.getElementById('roomsList');
    const btn = document.getElementById('roomsBtn');
    const arrow = document.getElementById('arrowUp');
    if (!btn.contains(e.target) && !list.contains(e.target)) {
        list.classList.remove('show');
        arrow.textContent = '▲';
    }
});


