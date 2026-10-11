<?php /* Sayt başlığı: loqo, menyu, zəng düyməsi */ ?>
<header>
  <div class="container nav">
    <a href="<?= e(url('/')) ?>" class="logo"><i>XR</i><span><?= e($T['site_name']) ?></span></a>
    <nav class="menu" id="menu">
      <a href="<?= e($navBase) ?>#haqqimda">Haqqımda</a>
      <a href="<?= e($navBase) ?>#muayine">Müayinə</a>
      <a href="<?= e($navBase) ?>#emeliyyatlar">Əməliyyatlar</a>
      <a href="<?= e($navBase) ?>#tehsil">Təhsil</a>
      <a href="<?= e($navBase) ?>#elaqe">Əlaqə</a>
    </nav>
    <a class="btn btn-teal" href="<?= e($tel) ?>" style="padding:11px 18px;font-size:15px"><?= icon('phone') ?>Zəng</a>
    <button class="burger" aria-label="Menyu" aria-controls="menu" aria-expanded="false"><span></span><span></span><span></span></button>
  </div>
</header>
