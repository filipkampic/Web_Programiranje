<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$loggedIn = isLoggedIn();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!$loggedIn) {
        echo json_encode(['ok' => false, 'msg' => 'You are not logged in.']);
        exit;
    }

    $songId = (int)($_POST['song_id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'add') {
        $playlistId = (int)($_POST['playlist_id'] ?? '');

        $chk = $conn->prepare('SELECT id FROM playlists WHERE id = ? AND user_id = ?');
        $chk->bind_param('ii', $playlistId, $user['id']);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid playlist.']);
            exit;
        }

        $s = $conn->prepare('SELECT popularnost FROM songs WHERE id = ?');
        $s->bind_param('i', $songId);
        $s->execute();
        $s->bind_result($pop);
        $s->fetch();
        $s->close();

        $warning = ($pop !== null && $pop < 4.5)
            ? 'This song has a low popularity rating (' . $pop . ')!'
            : null;

        $ins = $conn->prepare('INSERT IGNORE INTO kolekcija (user_id, playlist_id, song_id) VALUES (?, ?, ?)');
        $ins->bind_param('iii', $user['id'], $playlistId, $songId);
        $ins->execute();

        echo json_encode(['ok' => true, 'added' => $ins->affected_rows > 0, 'warning' => $warning]);
        exit;
    }

    if ($action === 'remove') {
        $del = $conn->prepare('DELETE FROM kolekcija WHERE user_id = ? AND song_id = ?');
        $del->bind_param('ii', $user['id'], $songId);
        $del->execute();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'new_playlist') {
        $naziv = trim($_POST['naziv'] ?? '');
        if ($naziv === '') {
            echo json_encode(['ok' => false, 'msg' => 'Name required.']);
            exit;
        }
        $ins = $conn->prepare('INSERT INTO playlists (user_id, naziv) VALUES (?, ?)');
        $ins->bind_param('is', $user['id'], $naziv);
        $ins->execute();
        echo json_encode(['ok' => true, 'playlist_id' => $ins->insert_id]);
        exit;
    }

    if ($action === 'delete_playlist') {
        $playlistId = (int)($_POST['playlist_id'] ?? 0);
        $chk = $conn->prepare('SELECT id, is_default FROM playlists WHERE id = ? AND user_id = ?');
        $chk->bind_param('ii', $playlistId, $user['id']);
        $chk->execute();
        $chk->bind_result($pid, $isDefault);
        $chk->fetch();
        $chk->close();

        if (!$pid) { echo json_encode(['ok' => false, 'msg' => 'Invalid playlist.']); exit; }
        if ($isDefault) { echo json_encode(['ok' => false, 'msg' => 'Cannot delete default playlist.']); exit; }

        $del1 = $conn->prepare('DELETE FROM kolekcija WHERE playlist_id = ? AND user_id = ?');
        $del1->bind_param('ii', $playlistId, $user['id']);
        $del1->execute();

        $del2 = $conn->prepare('DELETE FROM playlists WHERE id = ? AND user_id = ?');
        $del2->bind_param('ii', $playlistId, $user['id']);
        $del2->execute();

        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action.']);
    exit;
}

$genres = [];
$res = $conn->query('SELECT DISTINCT zanr FROM songs ORDER BY zanr');
while ($r = $res->fetch_assoc()) $genres[] = $r['zanr'];

$moods = [];
$res = $conn->query('SELECT DISTINCT raspolozenje FROM songs ORDER BY raspolozenje');
while ($r = $res->fetch_assoc()) $moods[] = $r['raspolozenje'];

$filterGenre = trim($_GET['zanr'] ?? '');
$filterArtist = trim($_GET['izvodac'] ?? '');
$filterBpm = max(60, (int)($_GET['bpm'] ?? 60));
$filterMood = trim($_GET['raspolozenje'] ?? '');

$where = ['bpm >= ?'];
$params = [$filterBpm];
$types = 'i';

if ($filterGenre !== '') { $where[] = 'zanr = ?';         $params[] = $filterGenre;              $types .= 's'; }
if ($filterArtist !== '') { $where[] = 'izvodac LIKE ?';   $params[] = '%' . $filterArtist . '%'; $types .= 's'; }
if ($filterMood !== '') { $where[] = 'raspolozenje = ?'; $params[] = $filterMood;               $types .= 's'; }

$stmt = $conn->prepare('SELECT * FROM songs WHERE ' . implode(' AND ', $where) . ' ORDER BY naslov');
$stmt->bind_param($types, ...$params);
$stmt->execute();
$songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$playlists = [];
$collectionIds = [];
$collectionSongs = [];
$activePlaylistId = (int)($_GET['playlist_id'] ?? 0);

if ($loggedIn) {
    $pr = $conn->prepare('SELECT id, naziv FROM playlists WHERE user_id = ? ORDER BY kreirana ASC');
    $pr->bind_param('i', $user['id']);
    $pr->execute();
    $playlists = $pr->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($playlists)) {
        $ins = $conn->prepare('INSERT INTO playlists (user_id, naziv, is_default) VALUES (?, ?, 1)');
        $naziv = 'My Playlist';
        $ins->bind_param('is', $user['id'], $naziv);
        $ins->execute();
        $activePlaylistId = $ins->insert_id;
        $playlists = [['id' => $activePlaylistId, 'naziv' => $naziv, 'is_default' => 1]];
    }

    if (!$activePlaylistId || !in_array($activePlaylistId, array_column($playlists, 'id'))) {
        $activePlaylistId = $playlists[0]['id'];
    }

    $activeIsDefault = false;
    foreach ($playlists as $pl) {
        if ($pl['id'] === $activePlaylistId && ((int)($pl['is_default'] ?? 0) === 1)) {
            $activeIsDefault = true;
            break;
        }
    }

    $cs = $conn->prepare(
        'SELECT s.id, s.naslov, s.izvodac FROM kolekcija k
         JOIN songs s ON s.id = k.song_id
         WHERE k.user_id = ? AND k.playlist_id = ? ORDER BY k.dodano DESC'
    );
    $cs->bind_param('ii', $user['id'], $activePlaylistId);
    $cs->execute();
    $cr = $cs->get_result();
    while ($r = $cr->fetch_assoc()) {
        $collectionIds[] = $r['id'];
        $collectionSongs[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Responsive web page about music.">
    <link rel="stylesheet" href="styles/style.css">
    <title>Rock Music</title>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main>
        <div class="table-image">
            <div class="left-col">

                <form class="filters-bar" method="get" action="index.php" id="filter-form">
                    <div class="filter-controls">
                        <label for="filter-genre">Genre:</label>
                        <select id="filter-genre" name="zanr">
                            <option value="">-- All Genres --</option>
                            <?php foreach ($genres as $g): ?>
                                <option value="<?= htmlspecialchars($g) ?>" <?= $filterGenre === $g ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="filter-artist">Artist:</label>
                        <input type="text" id="filter-artist" name="izvodac"
                               placeholder="e.g. Nirvana" value="<?= htmlspecialchars($filterArtist) ?>">

                        <div class="bpm-group">
                            <label for="filter-bpm">Min BPM: <span id="bpm-value"><?= $filterBpm ?></span></label>
                            <input type="range" id="filter-bpm" name="bpm" min="60" max="220" step="1" value="<?= $filterBpm ?>">
                        </div>

                        <div class="button-group">
                            <button type="submit" id="btn-filter">Apply</button>
                            <button type="button" id="btn-reset">Reset</button>
                        </div>

                        <fieldset class="filter-mood">
                            <legend>Mood:</legend>
                            <label><input type="radio" name="raspolozenje" value="" <?= $filterMood === '' ? 'checked' : '' ?>> All</label>
                            <?php foreach ($moods as $m): ?>
                                <label>
                                    <input type="radio" name="raspolozenje"
                                           value="<?= htmlspecialchars($m) ?>"
                                           <?= $filterMood === $m ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($m) ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    </div>
                </form>

                <div class="table-wrapper">
                    <table id="music-table" aria-label="Songs table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>Genre</th>
                                <th>BPM</th>
                                <th>Year</th>
                                <th>Popularity</th>
                                <th>Mood</th>
                                <th>Add</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($songs)): ?>
                            <tr><td colspan="8">No results.</td></tr>
                        <?php else: ?>
                            <?php foreach ($songs as $s):
                                $inCol = in_array($s['id'], $collectionIds); ?>
                            <tr>
                                <td><?= htmlspecialchars($s['naslov']) ?></td>
                                <td><?= htmlspecialchars($s['izvodac']) ?></td>
                                <td><?= htmlspecialchars($s['zanr']) ?></td>
                                <td><?= $s['bpm'] ?></td>
                                <td><?= $s['godina'] ?></td>
                                <td><?= number_format($s['popularnost'], 1) ?></td>
                                <td><?= htmlspecialchars($s['raspolozenje']) ?></td>
                                <td>
									<?php if (!$loggedIn): ?>
										<a href="/login.php" class="btn-add btn-login-hint" title="Sign in to add">＋</a>
									<?php elseif ($inCol): ?>
										<button class="btn-add btn-in-collection"
												data-id="<?= $s['id'] ?>"
												data-naslov="<?= htmlspecialchars($s['naslov'], ENT_QUOTES) ?>"
												data-izvodac="<?= htmlspecialchars($s['izvodac'], ENT_QUOTES) ?>"
												disabled
												title="In playlist">✓</button>
									<?php else: ?>
										<button class="btn-add"
												data-id="<?= $s['id'] ?>"
												data-naslov="<?= htmlspecialchars($s['naslov'], ENT_QUOTES) ?>"
												data-izvodac="<?= htmlspecialchars($s['izvodac'], ENT_QUOTES) ?>"
												data-pop="<?= $s['popularnost'] ?>">＋</button>
									<?php endif; ?>
								</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="right-col">
                <p class="gallery-title">Image Gallery</p>
                <div class="concert-image-aside"><img src="img/rock-concert-1.jpg" alt="Guitarist performing on stage" loading="lazy"></div>
                <div class="concert-image-aside"><img src="img/rock-concert-2.jpg" alt="Silhouette of a guitarist" loading="lazy"></div>
                <div class="concert-image-aside"><img src="img/rock-concert-3.jpg" alt="Rock concert crowd" loading="lazy"></div>
                <div class="concert-image-aside"><img src="img/rock-concert-4.jpg" alt="Crowd at a rock concert" loading="lazy"></div>
            </div>
        </div>

        <?php if ($loggedIn): ?>
        <aside id="playlist-panel" aria-label="My playlist">
            <div class="playlist-header">
                <h3>My Playlist <span id="playlist-count">(<?= count($collectionIds) ?>)</span></h3>
                <button id="btn-toggle-playlist" aria-label="Toggle playlist">▼</button>
            </div>
            <div id="playlist-body">
                <div class="playlist-selector">
                    <select id="playlist-select">
                        <?php foreach ($playlists as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= $pl['id'] === $activePlaylistId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pl['naziv']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button id="btn-new-playlist">+</button>
                    <button id="btn-delete-playlist" title="Delete playlist" <?= $activeIsDefault ? 'style="display:none;"' : '' ?> >🗑</button>
                </div>
                <ul id="playlist-list">
                    <?php foreach ($collectionSongs as $cs): ?>
                        <li data-id="<?= $cs['id'] ?>">
                            <span><?= htmlspecialchars($cs['naslov']) ?> <em><?= htmlspecialchars($cs['izvodac']) ?></em></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p id="playlist-empty" style="<?= empty($collectionSongs) ? '' : 'display:none' ?>">No songs added yet.</p>
                <a href="/playlist.php?playlist_id=<?= $activePlaylistId ?>" id="btn-view-collection">View the playlist</a>
            </div>
        </aside>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.getElementById('filter-bpm').addEventListener('input', function() {
            document.getElementById('bpm-value').textContent = this.value;
        });

        document.getElementById('btn-reset').addEventListener('click', () => {
            const url = new URL(window.location.href);
            const pid = url.searchParams.get('playlist_id');
            let newUrl = 'index.php';
            if (pid) newUrl += '?playlist_id=' + pid;
            window.location.href = newUrl;
        });

        <?php if ($loggedIn): ?>
            const collectionIds = new Set(<?= json_encode($collectionIds) ?>);

            let activePlaylistId = <?= $activePlaylistId ?? 0 ?>;

            document.getElementById('playlist-select').addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('playlist_id', this.value);
                window.location.href = url.toString();
            });

            document.getElementById('btn-new-playlist').addEventListener('click', async () => {
                const name = prompt('Playlist name:');
                if (!name || !name.trim()) return;

                const fd = new FormData();
                fd.append('action', 'new_playlist');
                fd.append('naziv', name.trim());
                const res = await fetch('index.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.ok) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('playlist_id', data.playlist_id);
                    window.location.href = url.toString();
                } else {
                    notify(data.msg || 'Error.');
                }
            });

            document.getElementById('btn-delete-playlist').addEventListener('click', async () => {
                if (<?= $activeIsDefault ? 'true' : 'false' ?>) {
                    notify('Cannot delete default playlist.');
                    return;
                }
                        
                if (!confirm('Delete this playlist and all its songs?')) return;

                const fd = new FormData();
                fd.append('action', 'delete_playlist');
                fd.append('playlist_id', activePlaylistId);
                const res  = await fetch('index.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.ok) {
                    window.location.href = 'index.php';
                } else {
                    notify(data.msg || 'Error.');
                }
            });

            document.querySelectorAll('.btn-add[data-id]').forEach(btn => {
                btn.addEventListener('click', () => handleCollection(btn));
            });

            async function handleCollection(btn) {
                const songId  = btn.dataset.id;
                const pop = parseFloat(btn.dataset.pop || '10');
                const naslov = btn.dataset.naslov;
                const izvodac = btn.dataset.izvodac;
                const action = 'add';

                if (pop < 4.5) {
                    const ok = confirm(`This song has a low popularity rating (${pop}) – add anyway?`);
                    if (!ok) return;
                }

                const fd = new FormData();
                fd.append('action', action);
                fd.append('song_id', songId);
                fd.append('playlist_id', activePlaylistId)

                const res  = await fetch('index.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (!data.ok) { notify(data.msg || 'Error.'); return; }

                btn.textContent = '✓';
                btn.disabled = true;
                btn.classList.add('btn-in-collection');
                btn.title = 'In playlist';
                collectionIds.add(Number(songId));
                updateCount(1);

                const li = document.createElement('li');
                li.dataset.id = songId;
                li.innerHTML = `<span>${naslov} <em>${izvodac}</em></span>`;
                document.getElementById('playlist-list').prepend(li);
                document.getElementById('playlist-empty').style.display = 'none';

                if (data.warning) notify('⚠ ' + data.warning);
                else notify('Added to playlist!');
            }

            function updateCount(delta) {
                const el = document.getElementById('playlist-count');
                if (!el) return;
                const cur = parseInt(el.textContent.replace(/\D/g, '')) || 0;
                el.textContent = '(' + Math.max(0, cur + delta) + ')';
            }

            const playlistBody = document.getElementById('playlist-body');
            const toggleBtn = document.getElementById('btn-toggle-playlist');

            toggleBtn.addEventListener('click', () => {
                const isOpen = playlistBody.style.display !== 'none';
                playlistBody.style.display = isOpen ? 'none' : 'block';
                toggleBtn.textContent = isOpen ? '▲' : '▼';
            });
        <?php endif; ?>

        function notify(msg) {
            const box = document.createElement('div');
            box.textContent = msg;
            Object.assign(box.style, {
                position: 'fixed', bottom: '20px', left: '50%',
                transform: 'translateX(-50%)', background: '#111',
                color: 'white', padding: '12px 20px', borderRadius: '8px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.4)', fontSize: '14px',
                zIndex: '2000', opacity: '0', transition: 'opacity 0.3s ease'
            });
            document.body.appendChild(box);
            requestAnimationFrame(() => box.style.opacity = '1');
            setTimeout(() => { box.style.opacity = '0'; setTimeout(() => box.remove(), 300); }, 2000);
        }
    </script>
</body>
</html>