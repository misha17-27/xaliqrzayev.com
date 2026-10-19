<?php /* 404 səhifəsinin məzmunu */ ?>
<section class="page-hero">
  <div class="container">
    <div class="num">404</div>
    <h1><?= e($T['nf_title']) ?></h1>
    <p><?= e($T['nf_text']) ?></p>
    <div class="hero-actions">
      <a class="btn btn-gold" href="<?= e(url('/')) ?>"><?= e($T['nf_btn']) ?></a>
      <a class="btn btn-ghost" href="<?= e($tel) ?>"><?= e($C['phone']) ?></a>
    </div>
  </div>
</section>
