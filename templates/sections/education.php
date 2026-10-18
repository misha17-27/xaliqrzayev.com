<?php /* 04 · Təhsil */ ?>
<?php if ($EDUCATION): ?>
<section id="tehsil" class="dark">
  <div class="container">
    <div class="head reveal"><div class="num">04</div><div><div class="label"><?= e($T['edu_tag']) ?></div><h2><?= e($T['edu_title']) ?></h2></div></div>
    <div class="edu reveal">
      <?php foreach ($EDUCATION as $ed): ?>
      <div class="e"><div class="yr"><?= e($ed['years']) ?></div><h4><?= e($ed['title']) ?></h4><p><?= nl2br(e($ed['descr']), false) ?></p><?php if ($ed['label'] !== ''): ?><span class="chip"><?= e($ed['label']) ?></span><?php endif; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
