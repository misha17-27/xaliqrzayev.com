<?php /* Təhsil */ ?>
<?php if ($EDUCATION): ?>
<section id="tehsil">
  <div class="container">
    <div class="title"><span class="tg"><?= e($T['edu_tag']) ?></span><h2><?= e($T['edu_title']) ?></h2></div>
    <div class="edu">
      <?php foreach ($EDUCATION as $ed): ?>
      <div class="edu-card"><span class="yr"><?= e($ed['years']) ?></span><h4><?= e($ed['title']) ?></h4><p><?= nl2br(e($ed['descr']), false) ?></p><?php if ($ed['label'] !== ''): ?><span class="lbl"><?= e($ed['label']) ?></span><?php endif; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
