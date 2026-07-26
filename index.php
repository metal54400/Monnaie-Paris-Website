<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$errors = [];

// --- Ajout d'une pièce ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $date = trim((string) ($_POST['date'] ?? ''));

    if ($name === '' || $date === '') {
        $errors[] = "Le nom et la date sont obligatoires.";
    }
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Veuillez sélectionner une image.";
    }

    if (!$errors) {
        try {
            $filename = handleUpload($_FILES['image'], $uploadDir);

            $maxNoStmt = $pdo->query("SELECT COALESCE(MAX(accession_no), 0) FROM pieces");
            $nextNo = (int) $maxNoStmt->fetchColumn() + 1;

            $stmt = $pdo->prepare(
                "INSERT INTO pieces (accession_no, name, piece_date, image_path, added_at)
                 VALUES (:no, :name, :date, :image, :added_at)"
            );
            $stmt->execute([
                ':no'       => $nextNo,
                ':name'     => $name,
                ':date'     => $date,
                ':image'    => $filename,
                ':added_at' => time(),
            ]);

            header('Location: index.php');
            exit;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// --- Suppression d'une pièce ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT image_path FROM pieces WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $imgPath = $uploadDir . '/' . $row['image_path'];
            if (is_file($imgPath)) {
                unlink($imgPath);
            }
            $del = $pdo->prepare("DELETE FROM pieces WHERE id = :id");
            $del->execute([':id' => $id]);
        }
    }
    header('Location: index.php');
    exit;
}

// --- Recherche + listing ---
$search = trim((string) ($_GET['q'] ?? ''));
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM pieces WHERE name LIKE :q ORDER BY accession_no ASC");
    $stmt->execute([':q' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM pieces ORDER BY accession_no ASC");
}
$pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $pdo->query("SELECT COUNT(*) FROM pieces");
$total = (int) $totalStmt->fetchColumn();

$currentPage = 'index';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Musée des Monnaies — Catalogue</title>

<!-- Tailwind CSS (CDN) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          bgroot:  '#1b1712',
          panel:   '#241f18',
          panell:  '#2f2820',
          seam:    '#3a3226',
          ivory:   '#e9e0cc',
          muted:   '#8c8270',
          brass:   '#b98b4e',
          brassl:  '#d9b173',
          patina:  '#6f9483',
          rust:    '#a65c4b',
        },
        fontFamily: {
          display: ['"Cormorant Garamond"', 'serif'],
          body:    ['"EB Garamond"', 'serif'],
          mono:    ['"JetBrains Mono"', 'monospace'],
        },
      }
    }
  }
</script>

<!-- Font Awesome (icônes) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- Feuille de style perso (polices, médaillon, animations) -->
<link rel="stylesheet" href="style.css">
</head>

<body class="bg-bgroot text-ivory font-body min-h-screen">
<?php include __DIR__ . '/header.php'; ?>

<div class="max-w-5xl mx-auto px-6 pt-12 pb-20">

  <!-- ===== En-tête de page ===== -->
  <header class="text-center mb-14">
    <div class="font-mono text-[11px] tracking-[0.35em] text-patina uppercase mb-3">
      <i class="fa-solid fa-landmark mr-2"></i>Cabinet numismatique
    </div>
    <h1 class="font-display font-bold text-5xl md:text-6xl text-brassl title-shadow tracking-wide mb-1">
      Musée des Monnaies
    </h1>
    <p class="italic text-muted text-base md:text-lg">Catalogue personnel de la collection — Paris</p>
    <div class="w-36 h-px mx-auto mt-7 bg-gradient-to-r from-transparent via-brass to-transparent"></div>
  </header>

  <!-- ===== Panneau d'ajout ===== -->
  <section class="tray-frame bg-panel border border-seam p-8 mb-16">
    <?php if ($errors): ?>
      <div class="border border-rust bg-rust/10 text-red-200 px-4 py-3 mb-5 font-mono text-xs space-y-1">
        <?php foreach ($errors as $err): ?>
          <p><i class="fa-solid fa-triangle-exclamation mr-2"></i><?= e($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-[200px_1fr] gap-7">
      <input type="hidden" name="action" value="add">

      <!-- Zone de dépôt -->
      <label id="dropzone" class="dropzone relative flex flex-col items-center justify-center gap-3 min-h-[180px] p-4 text-center border-[1.5px] border-dashed border-seam cursor-pointer">
        <input type="file" name="image" id="fileInput" accept="image/*" class="hidden" required>
        <img id="previewImg" class="preview absolute inset-0 w-full h-full object-cover hidden" alt="">
        <i id="dzIcon" class="fa-regular fa-image text-3xl text-brassl/70"></i>
        <span id="dzText" class="text-xs text-muted leading-relaxed">
          Glissez une image ici<br>ou cliquez pour sélectionner
        </span>
      </label>

      <!-- Champs -->
      <div class="flex flex-col gap-4">
        <div>
          <label for="nameInput" class="block font-mono text-[10px] tracking-[0.18em] uppercase text-patina mb-1.5">
            <i class="fa-solid fa-tag mr-1"></i>Nom de la pièce
          </label>
          <input type="text" name="name" id="nameInput" required
                 placeholder="ex. Denier de Philippe Auguste"
                 class="w-full bg-panell border border-seam text-ivory px-3 py-2.5 font-body text-lg outline-none focus:border-brass transition-colors">
        </div>
        <div>
          <label for="dateInput" class="block font-mono text-[10px] tracking-[0.18em] uppercase text-patina mb-1.5">
            <i class="fa-regular fa-calendar mr-1"></i>Date
          </label>
          <input type="text" name="date" id="dateInput" required
                 placeholder="ex. 1180–1223 ou 1793"
                 class="w-full bg-panell border border-seam text-ivory px-3 py-2.5 font-body text-lg outline-none focus:border-brass transition-colors">
        </div>
        <button type="submit"
                class="add-btn group self-start mt-1 border border-brass text-brassl font-mono text-xs tracking-[0.14em] uppercase px-6 py-3 hover:bg-brass hover:text-bgroot transition-colors">
          <i class="fa-solid fa-plus mr-2 inline-block transition-transform duration-300 group-hover:rotate-90"></i>Cataloguer la pièce
        </button>
      </div>
    </form>
  </section>

  <!-- ===== En-tête de la galerie ===== -->
  <div class="flex items-baseline justify-between flex-wrap gap-3 mb-7">
    <div>
      <div class="font-display font-semibold text-2xl tracking-wide"><i class="fa-solid fa-coins mr-2 text-brassl"></i>Collection</div>
      <div class="font-mono text-xs text-muted mt-1"><?= $total ?> pièce<?= $total !== 1 ? 's' : '' ?></div>
    </div>
    <form method="get" class="relative">
      <i class="fa-solid fa-magnifying-glass absolute left-0 top-1/2 -translate-y-1/2 text-muted text-xs"></i>
      <input class="search-input border-0 border-b border-seam text-ivory font-body italic text-sm pl-5 pr-2 py-1 outline-none w-56"
             type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par nom…" onchange="this.form.submit()">
    </form>
  </div>

  <!-- ===== Galerie ===== -->
  <?php if (!$pieces): ?>
    <div class="text-center py-16 px-5 text-muted italic border border-dashed border-seam">
      <i class="fa-solid fa-box-open text-2xl mb-3 block"></i>
      Le cabinet est vide. Ajoutez une première pièce ci-dessus.
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-x-7 gap-y-11">
      <?php foreach ($pieces as $i => $p): ?>
        <div class="piece flex flex-col items-center text-center" style="animation-delay: <?= min($i * 0.06, 0.9) ?>s;">
          <div class="medallion relative w-[170px] h-[170px] rounded-full">
            <div class="rim absolute inset-0 rounded-full p-1.5">
              <div class="face w-full h-full rounded-full overflow-hidden">
                <img class="w-full h-full object-cover" src="uploads/<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>">
              </div>
            </div>
            <form method="post" onsubmit="return confirm('Retirer cette pièce du catalogue ?');" class="absolute -top-1.5 -right-1.5 m-0">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button type="submit" title="Retirer"
                      class="del-btn w-7 h-7 rounded-full bg-panel border border-rust text-rust text-xs flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </form>
          </div>
          <div class="plaque mt-4 pt-2 border-t border-seam w-full">
            <div class="font-mono text-[10px] tracking-[0.16em] text-patina mb-1">
              N° <?= str_pad((string)$p['accession_no'], 4, '0', STR_PAD_LEFT) ?>
            </div>
            <div class="font-display font-semibold text-lg leading-tight"><?= e($p['name']) ?></div>
            <div class="font-mono text-[11px] text-muted mt-1"><?= e($p['piece_date']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php include __DIR__ . '/footer.php'; ?>
</div>

<script>
const fileInput  = document.getElementById('fileInput');
const dropzone   = document.getElementById('dropzone');
const previewImg = document.getElementById('previewImg');
const dzIcon     = document.getElementById('dzIcon');
const dzText     = document.getElementById('dzText');

function showPreview(file) {
  if (!file || !file.type.startsWith('image/')) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    previewImg.src = e.target.result;
    previewImg.classList.remove('hidden');
    dzIcon.classList.add('hidden');
    dzText.classList.add('hidden');
  };
  reader.readAsDataURL(file);
}

fileInput.addEventListener('change', (e) => showPreview(e.target.files[0]));
dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('drag'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag'));
dropzone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropzone.classList.remove('drag');
  const file = e.dataTransfer.files[0];
  if (file) {
    fileInput.files = e.dataTransfer.files;
    showPreview(file);
  }
});
</script>
</body>
</html>
