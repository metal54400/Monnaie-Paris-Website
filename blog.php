<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$errors = [];

// --- Ajout d'un article ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_post') {
    $title   = trim((string) ($_POST['title'] ?? ''));
    $date    = trim((string) ($_POST['date'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));

    if ($title === '' || $date === '' || $content === '') {
        $errors[] = "Le titre, la date et le contenu sont obligatoires.";
    }

    if (!$errors) {
        try {
            $filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $filename = handleUpload($_FILES['image'], $uploadDir);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO posts (title, post_date, content, image_path, added_at)
                 VALUES (:title, :date, :content, :image, :added_at)"
            );
            $stmt->execute([
                ':title'    => $title,
                ':date'     => $date,
                ':content'  => $content,
                ':image'    => $filename,
                ':added_at' => time(),
            ]);

            header('Location: blog.php');
            exit;
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// --- Suppression d'un article ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_post') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!empty($row['image_path'])) {
                $imgPath = $uploadDir . '/' . $row['image_path'];
                if (is_file($imgPath)) {
                    unlink($imgPath);
                }
            }
            $del = $pdo->prepare("DELETE FROM posts WHERE id = :id");
            $del->execute([':id' => $id]);
        }
    }
    header('Location: blog.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM posts ORDER BY added_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'blog';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog — Musée des Monnaies</title>

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

<!-- Feuille de style perso -->
<link rel="stylesheet" href="style.css">
</head>

<body class="bg-bgroot text-ivory font-body min-h-screen">
<?php include __DIR__ . '/header.php'; ?>

<div class="max-w-3xl mx-auto px-6 pt-12 pb-20">

  <!-- ===== En-tête de page ===== -->
  <header class="text-center mb-14">
    <div class="font-mono text-[11px] tracking-[0.35em] text-patina uppercase mb-3">
      <i class="fa-solid fa-feather-pointed mr-2"></i>Journal du conservateur
    </div>
    <h1 class="font-display font-bold text-5xl md:text-6xl text-brassl title-shadow tracking-wide mb-1">
      Blog
    </h1>
    <p class="italic text-muted text-base md:text-lg">Notes, trouvailles et carnets de recherche</p>
    <div class="w-36 h-px mx-auto mt-7 bg-gradient-to-r from-transparent via-brass to-transparent"></div>
  </header>

  <!-- ===== Panneau d'ajout d'article ===== -->
  <section class="tray-frame bg-panel border border-seam p-8 mb-16">
    <?php if ($errors): ?>
      <div class="border border-rust bg-rust/10 text-red-200 px-4 py-3 mb-5 font-mono text-xs space-y-1">
        <?php foreach ($errors as $err): ?>
          <p><i class="fa-solid fa-triangle-exclamation mr-2"></i><?= e($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="flex flex-col gap-5">
      <input type="hidden" name="action" value="add_post">

      <div>
        <label for="titleInput" class="block font-mono text-[10px] tracking-[0.18em] uppercase text-patina mb-1.5">
          <i class="fa-solid fa-heading mr-1"></i>Titre de l'article
        </label>
        <input type="text" name="title" id="titleInput" required
               placeholder="ex. Une découverte au marché de Saint-Ouen"
               class="w-full bg-panell border border-seam text-ivory px-3 py-2.5 font-display text-xl outline-none focus:border-brass transition-colors">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-[1fr_200px] gap-5">
        <div>
          <label for="contentInput" class="block font-mono text-[10px] tracking-[0.18em] uppercase text-patina mb-1.5">
            <i class="fa-solid fa-pen-nib mr-1"></i>Contenu
          </label>
          <textarea name="content" id="contentInput" rows="5" required
                    placeholder="Racontez votre trouvaille, une recherche, une anecdote…"
                    class="w-full bg-panell border border-seam text-ivory px-3 py-2.5 font-body text-base leading-relaxed outline-none focus:border-brass transition-colors resize-y"></textarea>
        </div>

        <div>
          <label for="postDateInput" class="block font-mono text-[10px] tracking-[0.18em] uppercase text-patina mb-1.5">
            <i class="fa-regular fa-calendar mr-1"></i>Date
          </label>
          <input type="text" name="date" id="postDateInput" required
                 placeholder="ex. 12 juillet 2026"
                 class="w-full bg-panell border border-seam text-ivory px-3 py-2.5 font-body text-base outline-none focus:border-brass transition-colors mb-4">

          <label class="dropzone relative flex flex-col items-center justify-center gap-2 min-h-[110px] p-3 text-center border-[1.5px] border-dashed border-seam cursor-pointer">
            <input type="file" name="image" id="fileInput" accept="image/*" class="hidden">
            <img id="previewImg" class="preview absolute inset-0 w-full h-full object-cover hidden" alt="">
            <i id="dzIcon" class="fa-regular fa-image text-xl text-brassl/70"></i>
            <span id="dzText" class="text-[11px] text-muted leading-snug">Image (facultatif)</span>
          </label>
        </div>
      </div>

      <button type="submit"
              class="add-btn group self-start border border-brass text-brassl font-mono text-xs tracking-[0.14em] uppercase px-6 py-3 hover:bg-brass hover:text-bgroot transition-colors">
        <i class="fa-solid fa-plus mr-2 inline-block transition-transform duration-300 group-hover:rotate-90"></i>Publier l'article
      </button>
    </form>
  </section>

  <!-- ===== Liste des articles ===== -->
  <?php if (!$posts): ?>
    <div class="text-center py-16 px-5 text-muted italic border border-dashed border-seam">
      <i class="fa-solid fa-box-open text-2xl mb-3 block"></i>
      Aucun article pour le moment. Rédigez votre première note ci-dessus.
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-10">
      <?php foreach ($posts as $i => $post): ?>
        <article class="post-card relative bg-panel border border-seam p-7"
                 style="animation-delay: <?= min($i * 0.08, 0.9) ?>s;">
          <div class="flex items-start justify-between gap-4 mb-3">
            <div>
              <div class="font-mono text-[10px] tracking-[0.16em] text-patina mb-1.5">
                <i class="fa-regular fa-calendar mr-1"></i><?= e($post['post_date']) ?>
              </div>
              <h2 class="font-display font-semibold text-2xl leading-tight text-brassl"><?= e($post['title']) ?></h2>
            </div>
            <form method="post" onsubmit="return confirm('Supprimer cet article ?');" class="shrink-0">
              <input type="hidden" name="action" value="delete_post">
              <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
              <button type="submit" title="Supprimer"
                      class="w-8 h-8 rounded-full bg-panell border border-rust text-rust text-xs flex items-center justify-center hover:bg-rust hover:text-panel transition-colors">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </form>
          </div>

          <?php if (!empty($post['image_path'])): ?>
            <img src="uploads/<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>"
                 class="w-full max-h-80 object-cover border border-seam mb-4 sepia-[0.08]">
          <?php endif; ?>

          <p class="font-body text-[17px] leading-relaxed text-ivory/90 whitespace-pre-line"><?= nl2br(e($post['content'])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php include __DIR__ . '/footer.php'; ?>
</div>

<script>
const fileInput  = document.getElementById('fileInput');
const dropzone   = document.querySelector('.dropzone');
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
