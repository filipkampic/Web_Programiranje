<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$loggedIn = isLoggedIn();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!$loggedIn) {
        echo json_encode(['ok' => false, 'msg' => 'Not logged in.']);
        exit;
    }

    if ($_POST['action'] === 'rate') {
        $imageId = (int)($_POST['slika_id'] ?? 0);   
        $rating = (int)($_POST['ocjena'] ?? 0);   

        if ($imageId <= 0 || $rating < 1 || $rating > 5) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid data.']);
            exit;
        }

        $stmt = $conn->prepare('
            INSERT INTO ocjene (id_korisnik, id_slika, ocjena)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE ocjena = VALUES(ocjena), vrijeme_ocjene = NOW()
        ');
        $stmt->bind_param('iii', $user['id'], $imageId, $rating);
        $stmt->execute();

        $avg = $conn->prepare('SELECT ROUND(AVG(ocjena), 1) AS avg, COUNT(*) AS cnt FROM ocjene WHERE id_slika = ?');
        $avg->bind_param('i', $imageId);
        $avg->execute();
        $avg->bind_result($newAvg, $cnt);
        $avg->fetch();
        $avg->close();

        echo json_encode(['ok' => true, 'avg' => $newAvg, 'cnt' => $cnt]);
        exit;
    }

    if ($_POST['action'] === 'upload') {
        if (empty($_FILES['slika']) || $_FILES['slika']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'msg' => 'Upload error.']);
            exit;
        }

        $allowed = ['image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['slika']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed)) {
            echo json_encode(['ok' => False, 'msg' => 'Only JPEG and PNG allowed.']);
            exit;
        }
        if ($_FILES['slika']['size'] > $maxSize) {
            echo json_encode(['ok' => False, 'msg' => 'File too large (max 5MB).']);
            exit;
        }

        $ext = $mimeType === 'image/jpeg' ? 'jpg' : 'png';
        $filename = uniqid('img_', true) . '.' . $ext;
        $destDir = __DIR__ . '/img/gallery/';
        $path = 'img/gallery/' . $filename;

        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        if (!move_uploaded_file($_FILES['slika']['tmp_name'], $destDir . $filename)) {
            echo json_encode(['ok' => false, 'msg' => 'Failed to save file.']);
            exit;
        }

        $desc = trim($_POST['opis'] ?? '');
        $stmt = $conn->prepare('INSERT INTO slike (naziv_datoteke, opis, putanja, izvor) VALUES (?, ?, ?, \'lokalno\')');
        $stmt->bind_param('sss', $filename, $desc, $path);
        $stmt->execute();

        echo json_encode(['ok' => true, 'id' => $stmt->insert_id, 'putanja' => $path, 'opis' => htmlspecialchars($desc)]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action.']);
    exit;
}

$userId = $user['id'] ?? 0;
$stmt = $conn->prepare('
    SELECT s.*,
        ROUND(AVG(o.ocjena), 1) AS avg_ocjena,
        COUNT(o.id) AS broj_ocjena,
        MAX(CASE WHEN o.id_korisnik = ? THEN o.ocjena END) AS moja_ocjena
    FROM slike s
    LEFT JOIN ocjene o ON o.id_slika = s.id
    GROUP BY s.id
    ORDER BY s.dodano DESC
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$slike = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Rock Music</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/gallery.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main>
        <?php if($loggedIn): ?>
            <section class="upload-card">
                <h2>Upload Image</h2>
                <div class="upload-form">
                    <input type="file" id="upload-file" accept="image/jpeg,image/png">
                    <input type="text" id="upload-description" placeholder="Description (optional)" maxlength="500">
                    <button id="btn-upload">Upload</button>
                </div>
                <p id="upload-msg"></p>
            </section>
        <?php endif; ?>

        <?php if (empty($slike)): ?>
            <section><p>No images yet. Upload one!</p></section>
        <?php else: ?>
            <div class="gallery" id="gallery-grid">
                <?php foreach ($slike as $s):
                    $avg = $s['avg_ocjena'] ?? 0;
                    $cnt = $s['broj_ocjena'] ?? 0;
                    $myGrade = (int)($s['moja_ocjena'] ?? 0);
                ?>
                    <figure class="gallery-image" data-id="<?= $s['id'] ?>">
                        <img src="<?= htmlspecialchars($s['putanja'])?>" 
                             alt="<?= htmlspecialchars($s['opis'] ?: $s['naziv_datoteke']) ?>"
                             loading="lazy"
                             class="gallery-thumb">
                        <?php if ($s['opis']): ?>
                            <figcaption><?= htmlspecialchars($s['opis']) ?></figcaption>
                        <?php endif; ?>

                        <div class="rating-wrap">
                            <div class="stars" data-id="<?= $s['id'] ?>" data-my-grade="<?= $myGrade ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $myGrade ? 'active' : '' ?>"
                                          data-val="<?= $i ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="avg-wrap">
                                <span class="avg-val"><?= $avg ?: '—' ?></span>
                                <span class="avg-cnt">(<?= $cnt ?>)</span>
                            </span>

                            <?php if (!$loggedIn): ?>
                                <p class="rating-login"><a href="login.php">Log in</a> to rate</p>
                            <?php endif; ?>
                        </div>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="lightbox" role="dialog" aria-modal="true">
        <button id="lightbox-close" aria-label="Close">✕</button>
        <img id="lightbox-img" src="" alt="">
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        <?php if ($loggedIn): ?>
            document.querySelectorAll('.stars').forEach(starsEl => {
                const spans = starsEl.querySelectorAll('.star');
                const slikaId = starsEl.dataset.id;

                spans.forEach(star => {
                    star.addEventListener('mouseover', () => {
                        const val = +star.dataset.val;
                        spans.forEach(s => s.classList.toggle('hover', +s.dataset.val <= val));
                    });
                    star.addEventListener('mouseleave', () => {
                        spans.forEach(s => s.classList.remove('hover'));
                    });
                    star.addEventListener('click', async () => {
                        const ocjena = +star.dataset.val;
                        const fd = new FormData();
                        fd.append('action', 'rate');
                        fd.append('slika_id', slikaId);
                        fd.append('ocjena', ocjena);

                        const res = await fetch('gallery.php', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (!data.ok) { alert(data.msg); return; }

                        spans.forEach(s => s.classList.toggle('active', +s.dataset.val <= ocjena));
                        starsEl.dataset.myGrade = ocjena;

                        const wrap = starsEl.closest('.rating-wrap');
                        wrap.querySelector('.avg-val').textContent = data.avg;
                        wrap.querySelector('.avg-cnt').textContent = '(' + data.cnt + ')';
                    });
                });
            });

            document.getElementById('btn-upload').addEventListener('click', async () => {
                const file = document.getElementById('upload-file').files[0];
                const desc = document.getElementById('upload-description').value;
                const msg = document.getElementById('upload-msg');

                if (!file) { msg.textContent = 'Select a file first.'; return; }

                const fd = new FormData();
                fd.append('action', 'upload');
                fd.append('slika', file);
                fd.append('opis', desc);

                msg.textContent = 'Uploading...';
                const res = await fetch('gallery.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (!data.ok) { msg.textContent = data.msg; return; }

                msg.textContent = 'Uploaded!';
                document.getElementById('upload-file').value = '';
                document.getElementById('upload-description').value = '';

                const grid = document.getElementById('gallery-grid');
                const fig = document.createElement('figure');
                fig.className = 'gallery-image';
                fig.dataset.id = data.id;
                fig.innerHTML = `
                    <a href="${data.putanja}" target="_blank">
                        <img src="${data.putanja}" alt="${data.opis}" loading="lazy">
                    </a>
                    ${data.opis ? `<figcaption>${data.opis}</figcaption>` : ''}
                    <div class="rating-wrap">
                        <div class="stars" data-id="${data.id}" data-my-grade="0">
                            <span class="star" data-val="1">★</span>
                            <span class="star" data-val="2">★</span>
                            <span class="star" data-val="3">★</span>
                            <span class="star" data-val="4">★</span>
                            <span class="star" data-val="5">★</span>
                        </div>
                        <span class="avg-wrap">
                            <span class="avg-val">—</span>
                            <span class="avg-cnt">(0)</span>
                        </span>
                    </div>
                `;
                grid.prepend(fig);
                attachStars(fig.querySelector('.stars'));
            });

            function attachStars(starsEl) {
                const spans = starsEl.querySelectorAll('.star');
                const imageId = starsEl.dataset.id;
                spans.forEach(star => {
                    star.addEventListener('mouseover', () => {
                        const val = +star.dataset.val;
                        spans.forEach(s => s.classList.toggle('hover', +s.dataset.val <= val));
                    });
                    star.addEventListener('mouseleave', () => spans.forEach(s => s.classList.remove('hover')));
                    star.addEventListener('click', async () => {
                        const ocjena = +star.dataset.val;
                        const fd = new FormData();
                        fd.append('action', 'rate');
                        fd.append('slika_id', imageId);
                        fd.append('ocjena', ocjena);

                        const res = await fetch('gallery.php', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (!data.ok) return;

                        spans.forEach(s => s.classList.toggle('active', +s.dataset.val <= ocjena));

                        const wrap = starsEl.closest('.rating-wrap');
                        wrap.querySelector('.avg-val').textContent = data.avg;
                        wrap.querySelector('.avg-cnt').textContent = '(' + data.cnt + ')';
                    });
                });
            }
        <?php endif; ?>

        const lightbox    = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');

        document.getElementById('gallery-grid')?.addEventListener('click', e => {
            const thumb = e.target.closest('.gallery-thumb');
            if (!thumb) return;
            lightboxImg.src = thumb.src;
            lightboxImg.alt = thumb.alt;
            lightbox.classList.add('active');
        });

        document.getElementById('lightbox-close').addEventListener('click', () => {
            lightbox.classList.remove('active');
            lightboxImg.src = '';
        });

        lightbox.addEventListener('click', e => {
            if (e.target === lightbox) {
                lightbox.classList.remove('active');
                lightboxImg.src = '';
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                lightbox.classList.remove('active');
                lightboxImg.src = '';
            }
        });
    </script>
</body>
</html>