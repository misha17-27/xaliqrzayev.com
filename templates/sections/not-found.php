<?php /* 404 səhifəsinin məzmunu */ ?>
<section style="min-height:60vh;display:grid;place-items:center;text-align:center">
  <div class="container">
    <div style="font-size:96px;font-weight:900;color:var(--teal);line-height:1">404</div>
    <h1 style="font-size:28px;margin:12px 0 8px">Səhifə tapılmadı</h1>
    <p style="color:var(--muted);margin-bottom:24px">Axtardığınız səhifə mövcud deyil və ya köçürülüb.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-teal" href="<?= e(url('/')) ?>">Ana səhifə</a>
      <a class="btn btn-soft" href="<?= e($tel) ?>"><?= e($C['phone']) ?></a>
    </div>
  </div>
</section>
