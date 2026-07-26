<?php
/** @var string $currentPage défini par la page appelante ('index' ou 'blog') */
$currentPage = $currentPage ?? '';
?>
<nav class="sticky top-0 z-50 bg-bgroot/90 backdrop-blur border-b border-seam">
  <div class="max-w-5xl mx-auto flex items-center justify-between px-6 py-4">
    <a href="index.php" class="flex items-center gap-2 font-display font-semibold text-lg text-brassl hover:text-brass transition-colors">
      <i class="fa-solid fa-coins coin-spin"></i>
      <span>Musée des Monnaies</span>
    </a>
    <div class="flex items-center gap-7 font-mono text-[11px] tracking-[0.16em] uppercase">
      <a href="/"
         class="nav-link pb-1 border-b <?= $currentPage === 'index' ? 'text-brassl border-brass' : 'text-muted border-transparent hover:text-brassl' ?> transition-colors">
        <i class="fa-solid fa-landmark mr-1.5"></i>Collection
      </a>
      <a href="blog.php"
         class="nav-link pb-1 border-b <?= $currentPage === 'blog' ? 'text-brassl border-brass' : 'text-muted border-transparent hover:text-brassl' ?> transition-colors">
        <i class="fa-solid fa-feather-pointed mr-1.5"></i>Blog
      </a>
    </div>
  </div>
</nav>
