let allSongs = [];
let currentSongs = [];
let playlist = JSON.parse(localStorage.getItem('playlist_confirmed') || '[]');
let addedSinceLastConfirm = 0;

document.querySelector('input[name="mood"][value=""]').checked = true;

fetch('glazba.csv')
    .then(res => res.text())
    .then(csv => {
        const result = Papa.parse(csv, {
            header: true,
            skipEmptyLines: true
        });

        allSongs = result.data.map(song => ({
            id:         Number(song['ID']),
            title:      song['Naslov'],
            artist:     song['Izvođač'],
            genre:      song['Žanr'],
            bpm:        Number(song['BPM']),
            year:       Number(song['Godina']),
            popularity: parseFloat(song['Popularnost']),
            mood:       song['Raspoloženje']
        }));

        renderTable(allSongs);
    })
    .catch(err => {
        console.error('Error fetching CSV:', err);
    });

function renderTable(songs) {
    currentSongs = songs;
    const tbody = document.querySelector('#music-table tbody');
    tbody.innerHTML = '';

    if (songs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8">No results found.</td></tr>';
        return;
    }

    for (const song of songs) {
        const inPlaylist = playlist.some(s => s.id === song.id);

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${song.title}</td>
            <td>${song.artist}</td>
            <td>${song.genre}</td>
            <td>${song.bpm}</td>
            <td>${song.year}</td>
            <td>${song.popularity.toFixed(1)}</td>
            <td>${song.mood}</td>
            <td>
                <button class="btn-add" ${inPlaylist ? 'disabled' : ''}>＋</button>
            </td>
        `;

        if (!inPlaylist) {
            row.querySelector('.btn-add').addEventListener('click', () => {
                addToPlaylist(song);
            });
        }

        tbody.appendChild(row);
    }
}

const bpmSlider  = document.getElementById('filter-bpm');
const bpmDisplay = document.getElementById('bpm-value');

bpmSlider.addEventListener('input', () => {
    bpmDisplay.textContent = bpmSlider.value;
});

function applyFilters() {
    const genre  = document.getElementById('filter-genre').value;
    const artist = document.getElementById('filter-artist').value.trim().toLowerCase();
    const mood   = document.querySelector('input[name="mood"]:checked')?.value ?? '';
    const minBpm = parseInt(bpmSlider.value);

    const filtered = allSongs.filter(song => {
        const genreMatch  = !genre  || song.genre === genre;
        const artistMatch = !artist || song.artist.toLowerCase().includes(artist);
        const moodMatch   = !mood   || song.mood === mood;
        const bpmMatch    = song.bpm >= minBpm;
        return genreMatch && artistMatch && moodMatch && bpmMatch;
    });

    renderTable(filtered);
}

document.getElementById('btn-filter').addEventListener('click', applyFilters);

document.getElementById('btn-reset').addEventListener('click', () => {
    document.getElementById('filter-genre').value = '';
    document.getElementById('filter-artist').value = '';
    bpmSlider.value = 60;
    bpmDisplay.textContent = '60';
    document.querySelector('input[name="mood"][value=""]').checked = true;
    renderTable(allSongs);
});

function savePlaylist() {
    localStorage.setItem('playlist_confirmed', JSON.stringify(playlist));
}

function addToPlaylist(song) {
    if (playlist.find(s => s.id === song.id)) {
        notify(`"${song.title}" is already in the playlist!`);
        return;
    }
    playlist.push(song);
    addedSinceLastConfirm++;
    refreshPlaylist();
    renderTable(currentSongs);
}

function refreshPlaylist() {
    const list = document.getElementById('playlist-list');
    const empty = document.getElementById('playlist-empty');
    const counter = document.getElementById('playlist-count');

    list.innerHTML = '';
    counter.textContent = `(${playlist.length})`;

    if (playlist.length === 0) {
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';

    playlist.forEach((song, index) => {
        const li = document.createElement('li');
        li.innerHTML = `
            <span>${song.title} — <em>${song.artist}</em></span>
            <button class="btn-remove" data-index="${index}">✕</button>
        `;
        list.appendChild(li);
    });

    list.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', () => {
            playlist.splice(Number(btn.dataset.index), 1);
            refreshPlaylist();
            renderTable(currentSongs);
        });
    });
}

document.getElementById('btn-confirm-playlist').addEventListener('click', () => {
    if (playlist.length === 0) {
        localStorage.removeItem('playlist_confirmed');
        notify('Your playlist is empty!');
        return;
    }
    savePlaylist();
    const n = addedSinceLastConfirm;
    addedSinceLastConfirm = 0;
    if (n === 0) {
        notify(`Playlist confirmed with ${playlist.length} song${playlist.length > 1 ? 's' : ''}.`);
    } else {
        notify(`Playlist saved! You added ${n} song${n > 1 ? 's' : ''} to your playlist.`);
    }
    refreshPlaylist();
});

document.getElementById('btn-toggle-playlist').addEventListener('click', () => {
    const body = document.getElementById('playlist-body');
    const btn = document.getElementById('btn-toggle-playlist');
    const hidden = body.style.display === 'none';
    body.style.display = hidden ? 'block' : 'none';
    btn.textContent = hidden ? '▼' : '▲';
});

refreshPlaylist();

function notify(msg) {
    const box = document.createElement('div');
    box.textContent = msg;
    box.style.position = 'fixed';
    box.style.bottom = '20px';
    box.style.left = '50%';
    box.style.transform = 'translateX(-50%)';
    box.style.background = '#111';
    box.style.color = 'white';
    box.style.padding = '12px 20px';
    box.style.borderRadius = '8px';
    box.style.boxShadow = '0 4px 12px rgba(0,0,0,0.4)';
    box.style.fontSize = '14px';
    box.style.zIndex = '2000';
    box.style.opacity = '0';
    box.style.transition = 'opacity 0.3s ease';

    document.body.appendChild(box);

    requestAnimationFrame(() => box.style.opacity = '1');

    setTimeout(() => {
        box.style.opacity = '0';
        setTimeout(() => box.remove(), 300);
    }, 2000);
}
