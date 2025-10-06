if (document.getElementById('getStartedBtn')) {
    document.getElementById('getStartedBtn').addEventListener('click', function() {
        window.location.href = 'main.html';
    });
}

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

document.getElementById('backBtn').addEventListener('click', function() {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    showImage(currentIndex);
    
});


document.getElementById('nextBtn').addEventListener('click', function() {
   if (currentIndex === images.length - 1) {
    
        document.getElementById('imageViewer').style.display = 'none';
        document.getElementById('mapPlaceholder').style.display = 'flex';
        document.getElementById('map').style.display = 'block';
        document.getElementById('rightArrow').style.display = 'block';
        currentIndex = 0; 
    } else {
        currentIndex++;
        showImage(currentIndex);
    }
});

document.getElementById('logoBtn').addEventListener('click', function() {

    document.getElementById('imageViewer').style.display = 'none';
    document.getElementById('mapPlaceholder').style.display = 'flex';
    document.getElementById('map').style.display = 'block';
    document.getElementById('rightArrow').style.display = 'block';
});

// CL-v3 functionality
const images = [
    'CL-v3 Location/CLV3-1.png',
    'CL-v3 Location/CLV3-2.png',
    'CL-v3 Location/CLV3-3.png',
    'CL-v3 Location/CLV3-4.png',
    'CL-v3 Location/CLV3-5.png',
    'CL-v3 Location/CLV3-6.png',
    'CL-v3 Location/CLV3-7.png'
];
let currentIndex = 0;

document.querySelectorAll('.rooms-list div').forEach(item => {
    item.addEventListener('click', function() {
        if (this.textContent === 'CL-v3') {
            document.getElementById('map').style.display = 'block';
            document.getElementById('rightArrow').style.display = 'block';
            document.getElementById('imageViewer').style.display = 'none';
            document.getElementById('mapPlaceholder').style.display = 'flex';
        }
    });
});

document.getElementById('rightArrow').addEventListener('click', function() {
    document.getElementById('imageViewer').style.display = 'block';
    document.getElementById('mapPlaceholder').style.display = 'none';
    showImage(0);
});

function showImage(index) {
    document.getElementById('currentImage').src = images[index];
    if (index === 0) {
        backBtn.style.display = 'none';
    } else {
        backBtn.style.display = 'inline-block';
    }
}




