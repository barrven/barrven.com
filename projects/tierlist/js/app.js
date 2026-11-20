// track state
let currentImageIndex = 0;
let activeListKey = 'L';

// object to hold arrays of paths to media files
const tierArrays = {
    S: [],
    A: [],
    B: [],
    C: [],
    D: [],
    L: []
};

// for caching .tier-content html containers 
// which hold .thumbnail media elements (image or video) returned by createMediaElement()
const tierContents = {};

// todo: add better error reporting
async function saveState() {
    try {
        const response = await fetch(`${ENTRY_URL}?action=saveTierData`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ list_id: list_id, tierData: tierArrays })
        });

        if (!response.ok) throw new Error('Failed to save tier data');
        
        // console.log('Tier data saved to server');
        
    } catch (error) {
        console.error('Error saving tier data to server:', error);
    }
}

// need list_id to be defined by php earlier
async function loadState() {
    // load data from server
    try {
        const response = await fetch(`${ENTRY_URL}?action=loadTierData&list_id=${list_id}`, {
            method: 'GET', headers: { 'Content-Type': 'application/json' }
        });
        
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        // console.log('Loaded tier data from server');
        if (!data){
            console.warn('No tier data found on server.');
            return;
        }

        Object.keys(tierArrays).forEach(k => {
            if (Array.isArray(data[k])) {
                //prepend the home folder to the filenames
                data[k] = data[k].map(filename => `${home_directory}/${filename}`);
                tierArrays[k].length = 0;
                tierArrays[k].push(...data[k]);
            }
        });

    } catch (error) {
        console.error('Error loading tier data from server:', error);
    }

    populateImageGrid();
    populateTierRows();
}

//data mutation
function moveImageToTier(url, targetTierKey) {
    Object.keys(tierArrays).forEach(key => {
        const list = tierArrays[key];
        const idx = list.indexOf(url);
        if (idx !== -1) list.splice(idx, 1);
    });

    tierArrays[targetTierKey].push(url);
    
    saveState();
    populateImageGrid();
    populateTierRows();
}



//////////////////////////
// initialization ////////
//////////////////////////

document.addEventListener('DOMContentLoaded', () => {
    ['S','A','B','C','D'].forEach(letter => {
        const row = document.querySelector(`.tier-${letter.toLowerCase()}`);
        tierContents[letter] = row.querySelector('.tier-content');
    });

    loadState();
    
    // document.getElementById('downloadBtn').addEventListener('click', downloadTierData);
    // document.getElementById('uploadBtn').addEventListener('click', uploadTierData);
    document.getElementById('resetBtn').addEventListener('click', resetTierData);
});



//////////////////////////
// data input / output ///
//////////////////////////

// function downloadTierData() {

//     const json = JSON.stringify(tierArrays, null, 2);
//     const blob = new Blob([json], { type: 'application/json' });
//     const url = URL.createObjectURL(blob);

//     const a = document.createElement('a');
//     a.href = url;
//     a.download = 'tier-data.json';
//     a.click();

//     URL.revokeObjectURL(url);
// }

// function uploadTierData() {
    
//     const fileInput = document.createElement('input');
//     fileInput.type = 'file';
//     fileInput.accept = 'application/json';
//     fileInput.style.display = 'none';
//     document.body.appendChild(fileInput);

//     fileInput.addEventListener('change', async (event) => {
//         const file = event.target.files[0];

//         if (!file) {
//             console.error('No file selected.');
//             return;
//         }

//         try {
//             const content = await file.text();
//             const data = JSON.parse(content);
            
//             // copy the uploaded data into memory
//             Object.keys(tierArrays).forEach(k => {
//                 if (Array.isArray(data[k])) {
//                     tierArrays[k].length = 0;
//                     tierArrays[k].push(...data[k]);
//                 }
//             });

//             populateTierRows();
//             saveState();

//             } catch (error) {
//                 console.error("Error reading file:", error);
//             }
//     });

//     fileInput.click();
// }

async function resetTierData() {
    if (!confirm('Are you sure you want to reset all tier data? This action cannot be undone.')) {
        return;
    }

    // reset tier arrays (ignore L)
    let numFiles = 0;
    Object.keys(tierArrays).forEach(k => {
        if (k === 'L') return;
        numFiles += tierArrays[k].length;
        // tierArrays[k].length = 0; // not needed since we are removing from db
    });


    try {
        const response = await fetch(`${ENTRY_URL}?action=removeFromTiers`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ list_id, numFiles })
        });

        if (!response.ok) throw new Error('Failed to update list data');
        
        // console.log('List updated');
        
    } catch (error) {
        console.error('Error updating tier data on server:', error);
    }


    //re render rows
    loadState();
    populateTierRows();
    populateImageGrid();
}

//////////////////////////
// rendering /////////////
//////////////////////////

// returns img or video html element
function createMediaElement(url, isThumbnail) {

    const ext = url.split('.').pop().toLowerCase();
    const videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi'];

    if (videoExts.includes(ext)) {
        const video = document.createElement('video');
        video.src = url;
        video.muted = true;
        video.loop = true;
        video.playsInline = true;

        if (isThumbnail) {
            video.classList.add('thumbnail');
        } else {
            video.classList.add('grid-image');
        }

        return video;
    } else {
        const img = document.createElement('img');
        img.src = url;
        img.alt = 'this is a meme image or video';
        img.classList.add(isThumbnail ? 'thumbnail' : 'grid-image');
        return img;
    }
}

// modal handling
function showModalWithMedia(url) {
    const container = document.getElementById('modalMediaContainer');
    container.innerHTML = '';

    const media = createMediaElement(url, false);

    if (media.tagName === 'VIDEO') {
        media.controls = true;
        media.autoplay = true;
        media.muted = false;
        media.loop = true;
    }

    container.appendChild(media);
    document.getElementById('imageModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
    const video = document.querySelector('#modalMediaContainer video');
    if (video) video.pause();
}

function updateModalImage() {
    const list = tierArrays[activeListKey];
    let url = list[currentImageIndex]
    
    if (!list.length) {
        closeModal();
        return;
    }

    // url would be undefined if currentImageIndex is out of bounds 
    // after moving images between tiers
    if(url === undefined){
        url = list[--currentImageIndex];
    }
    showModalWithMedia(url);
}

function getCurrentImageUrl() {
    return tierArrays[activeListKey][currentImageIndex];
}



// render the tier rows
function populateTierRows() {
    ['S','A','B','C','D'].forEach(letter => {
        const container = tierContents[letter];
        container.innerHTML = '';

        tierArrays[letter].forEach((url, index) => {
            const media = createMediaElement(url, true);
            media.style.cursor = 'pointer';
            media.addEventListener('click', () => {
                activeListKey = letter;
                currentImageIndex = index;
                showModalWithMedia(url);
            });
            container.appendChild(media);
        });
    });
}

// render the image grid (for L tier)
function populateImageGrid() {
    const grid = document.querySelector('.image-grid');
    grid.innerHTML = '';
    tierArrays.L.forEach((url, index) => {
        const media = createMediaElement(url, false);
        media.style.cursor = 'pointer';
        media.addEventListener('click', () => {
            activeListKey = 'L';
            currentImageIndex = index;
            showModalWithMedia(url);
        });
        grid.appendChild(media);
    });
}

//////////////////////////
// wire up controls //////
//////////////////////////

document.getElementById('prevBtn').addEventListener('click', () => {
    const list = tierArrays[activeListKey];
    currentImageIndex = (currentImageIndex - 1 + list.length) % list.length;
    updateModalImage();
});

document.getElementById('nextBtn').addEventListener('click', () => {
    const list = tierArrays[activeListKey];
    currentImageIndex = (currentImageIndex + 1) % list.length;
    updateModalImage();
});

// modal tier letter buttons
document.querySelectorAll('.tier-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        let tierLetter = btn.textContent.trim();
        const url = getCurrentImageUrl();

        if (tierLetter === 'None') tierLetter = 'L';
        if (tierArrays[tierLetter].includes(url)) return;

        moveImageToTier(url, tierLetter);
        updateModalImage();
    });
});

document.getElementById('imageModal').addEventListener('click', e => {
    if (e.target.id === 'imageModal') closeModal();
});

// key bindings
document.addEventListener('keydown', e => {
    const modalOpen = document.getElementById('imageModal').style.display === 'flex';
    if (!modalOpen) return;

    if (e.key === 'ArrowLeft') {
        document.getElementById('prevBtn').click();
    }

    if (e.key === 'ArrowRight') {
        document.getElementById('nextBtn').click();
    }

    if (e.key === 'Escape') {
        closeModal();
    }
});
