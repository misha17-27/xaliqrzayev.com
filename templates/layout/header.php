<?php /* Sayt başlığı: ad, menyu, telefon. Ana səhifədə şəffaf, sürüşdürəndə tündləşir (main.js). */ ?>
<header id="hdr"<?= $navBase !== '' ? ' class="scrolled" data-solid' : '' ?>>
  <div class="container nav">
    <a href="<?= e(url('/')) ?>" class="brand"><?= e($T['site_name']) ?><small><?= e($T['site_role']) ?></small></a>
    <nav class="menu" id="menu">
      <a href="<?= e($navBase) ?>#haqqimda">Haqqımda</a>
      <a href="<?= e($navBase) ?>#diaqnostika">Diaqnostika</a>
      <a href="<?= e($navBase) ?>#cerrahi">Cərrahiyyə</a>
      <a href="<?= e($navBase) ?>#tehsil">Təhsil</a>
      <a href="<?= e($navBase) ?>#elaqe">Əlaqə</a>
    </nav>
    <div class="nav-right">
      <a class="nav-phone" href="<?= e($tel) ?>"><?= e($C['phone']) ?></a>
      <a class="btn btn-ghost" href="<?= e($navBase) ?>#elaqe">Qəbul</a>
      <button class="burger" type="button" aria-label="Menyu" aria-controls="menu" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>
