<?php /* Haqqımda */ ?>
<section id="haqqimda" style="padding-top:24px">
  <div class="container about">
    <div class="about-img"><?= picture($IMAGES['photo_about'], $T['site_name'], 1000, 1500, true) ?></div>
    <div>
      <span class="title" style="display:block;text-align:left;margin:0"><span class="tg"><?= e($T['about_tag']) ?></span></span>
      <h2><?= e($T['about_title']) ?></h2>
      <?php
        $paras = preg_split('/\R{2,}/', trim($T['about_body']));
        // ilk iki abzas teqlərdən əvvəl, qalanı sonra
        echo paragraphs(implode("\n\n", array_slice($paras, 0, 2)));
      ?>
      <?php if ($tags = lines($T['about_tags'])): ?>
      <div class="tags"><?php foreach ($tags as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></div>
      <?php endif; ?>
      <?= paragraphs(implode("\n\n", array_slice($paras, 2))) ?>
      <?php if ($T['award_text'] !== ''): ?>
      <div class="medal"><span class="m">🎖️</span><span><?= e($T['award_text']) ?></span></div>
      <?php endif; ?>
    </div>
  </div>
</section>
