<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();

$playlists = [];
$activePlaylistId = (int)($_GET['playlist_id'] ?? 0);

$pr = $conn->prepare('SELECT id, naziv FROM playlists WHERE user_id = ? ORDER BY kreirana ASC');
$pr->bind_param('i', $user['id']);
$pr->execute();
$playlists = $pr->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($playlists)) {
    if (!$activePlaylistId || !in_array($activePlaylistId, array_column($playlists, 'id'))) {
        $activePlaylistId = $playlists[0]['id'];
    }
}

$activePlaylist = null;
foreach ($playlists as $pl) {
    if ($pl['id'] === $activePlaylistId) {
        $activePlaylist = $pl;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $removeId = (int)$_POST['remove_id'];
    $del = $conn->prepare('DELETE FROM kolekcija WHERE user_id = ? AND song_id = ? AND playlist_id = ?');
    $del->bind_param('ii', $user['id'], $removeId, $activePlaylistId);
    $del->execute();
    header('Location: /playlist.php?playlist_id=' . $activePlaylistId);
    exit;
}

$songs = [];
if ($activePlaylistId > 0) {
    $stmt = $conn->prepare(
        'SELECT s.*, k.dodano FROM kolekcija k
        JOIN songs s ON s.id = k.song_id
        WHERE k.user_id = ? AND k.playlist_id = ?
        ORDER BY k.dodano DESC'
    );
    $stmt->bind_param('ii', $user['id'], $activePlaylistId);
    $stmt->execute();
    $songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


$avgPop = 0;
$genreCount = [];
if (!empty($songs)) {
    $avgPop = array_sum(array_column($songs, 'popularnost')) / count($songs);
    foreach ($songs as $s) {
        $genreCount[$s['zanr']] = ($genreCount[$s['zanr']] ?? 0) + 1;
    }
    arsort($genreCount);
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moja Playlist – Rock Music</title>
    <link rel="stylesheet" href="styles/playlist.css">
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main>

        <section class="chart-card">
            <div class="playlist-tabs">
                <?php foreach ($playlists as $pl): ?>
                    <a href="playlist.php?playlist_id=<?= $pl['id'] ?>"
                    class="playlist-tab <?= $pl['id'] === $activePlaylistId ? 'active' : '' ?>">
                        <?= htmlspecialchars($pl['naziv']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <h2><?= htmlspecialchars($activePlaylist ? $activePlaylist['naziv'] : 'No playlist')?></h2>
            
            <p class="chart-desc">
                Hello, <strong><?= htmlspecialchars($user['username']) ?></strong>!
                <?php if (empty($songs)): ?>
                    You haven't added any songs yet.
                    <a href="/">Browse the list →</a>
                <?php else: ?>
                    You have <strong><?= count($songs) ?></strong> 
                    <?= count($songs) === 1 ? 'song' : 'songs' ?> in your playlist.
                    Average popularity: <strong><?= number_format($avgPop, 2) ?></strong>
                <?php endif; ?>
            </p>

            <?php if (!empty($genreCount)): ?>
            <div class="genre-stats">
                <?php foreach (array_slice($genreCount, 0, 5, true) as $g => $cnt): ?>
                    <span class="genre-badge"><?= htmlspecialchars($g) ?> (<?= $cnt ?>)</span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($songs)): ?>
        <section class="chart-card">
            <div class="table-wrapper">
                <table id="playlist-table" aria-label="My Playlist">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>Genre</th>
                            <th>BPM</th>
                            <th>Year</th>
                            <th>Popularity</th>
                            <th>Mood</th>
                            <th>Added</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($songs as $i => $s): ?>
                        <tr <?= $s['popularnost'] < 4.5 ? 'class="row-low-pop"' : '' ?>>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($s['naslov']) ?></td>
                            <td><?= htmlspecialchars($s['izvodac']) ?></td>
                            <td><?= htmlspecialchars($s['zanr']) ?></td>
                            <td><?= $s['bpm'] ?></td>
                            <td><?= $s['godina'] ?></td>
                            <td>
                                <?= number_format($s['popularnost'], 1) ?>
                                <?php if ($s['popularnost'] < 4.5): ?>
                                    <span class="low-pop-badge" title="Low popularity">⚠</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s['raspolozenje']) ?></td>
                            <td class="col-date"><?= date('d.m.Y.', strtotime($s['dodano'])) ?></td>
                            <td>
                                <form method="post" action="playlist.php">
                                    <input type="hidden" name="remove_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn-remove-col">✕</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>