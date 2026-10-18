<?php /* 01 · Haqqımda */ ?>
<section id="haqqimda">
  <div class="container">
    <div class="head reveal"><div class="num">01</div><div><div class="label"><?= e($T['about_tag']) ?></div><h2><?= e($T['about_title']) ?></h2></div></div>
    <div class="about">
      <div class="reveal">
        <?php if ($T['about_quote'] !== ''): ?><p class="quote"><?= e($T['about_quote']) ?></p><?php endif; ?>
        <?php if ($focus = lines($T['about_tags'])): ?>
        <div class="focus">
          <?php foreach ($focus as $i => $f): ?><div><span><?= $i + 1 ?>.</span><?= e($f) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="reveal">
        <?= paragraphs($T['about_body']) ?>
        <?php if ($T['award_text'] !== ''): ?>
        <div class="honor">
          <div class="ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="6"/><path d="M8.2 13.2 7 22l5-3 5 3-1.2-8.8"/></svg></div>
          <div><?= e($T['award_text']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
