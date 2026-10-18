<?php /* 404 səhifəsinin məzmunu */ ?>
<section class="page-hero">
  <div class="container">
    <div class="num">404</div>
    <h1>Səhifə tapılmadı</h1>
    <p>Axtardığınız səhifə mövcud deyil və ya köçürülüb.</p>
    <div class="hero-actions">
      <a class="btn btn-gold" href="<?= e(url('/')) ?>">Ana səhifə</a>
      <a class="btn btn-ghost" href="<?= e($tel) ?>"><?= e($C['phone']) ?></a>
    </div>
  </div>
</section>
