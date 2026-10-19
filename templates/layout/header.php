<?php /* Sayt başlığı: ad, menyu, telefon. Ana səhifədə şəffaf, sürüşdürəndə tündləşir (main.js). */ ?>
<header id="hdr"<?= $navBase !== '' ? ' class="scrolled" data-solid' : '' ?>>
  <div class="container nav">
    <a href="<?= e(url('/')) ?>" class="brand"><?= e($T['site_name']) ?><small><?= e($T['site_role']) ?></small></a>
    <nav class="menu" id="menu">
      <a href="<?= e($navBase) ?>#haqqimda"><?= e($T['nav_about']) ?></a>
      <a href="<?= e($navBase) ?>#diaqnostika"><?= e($T['nav_diag']) ?></a>
      <a href="<?= e($navBase) ?>#cerrahi"><?= e($T['nav_surg']) ?></a>
      <a href="<?= e($navBase) ?>#tehsil"><?= e($T['nav_edu']) ?></a>
      <a href="<?= e($navBase) ?>#elaqe"><?= e($T['nav_contact']) ?></a>
    </nav>
    <div class="nav-right">
      <a class="nav-phone" href="<?= e($tel) ?>"><?= e($C['phone']) ?></a>
      <a class="btn btn-ghost" href="<?= e($navBase) ?>#elaqe"><?= e($T['btn_header']) ?></a>
      <button class="burger" type="button" aria-label="Menyu" aria-controls="menu" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>
