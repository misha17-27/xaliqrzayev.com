<?php /* Birinci ekran + altındakı zolaq (admin: «Полоса под первым экраном») */ ?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <div class="kicker"><?= e($T['hero_badge']) ?></div>
      <h1><?= e($T['hero_title_1']) ?><br><em><?= e($T['hero_title_2']) ?></em></h1>
      <p class="lead"><?= e($T['hero_lead']) ?></p>
      <p class="desc"><?= e($T['hero_desc']) ?></p>
      <div class="hero-actions">
        <a class="btn btn-gold" href="<?= e($tel) ?>"><?= e($T['btn_call']) ?></a>
        <a class="btn btn-ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= e($T['btn_wa']) ?></a>
      </div>
    </div>
    <div class="portrait">
      <div class="img"><?= picture($IMAGES['photo_hero'], $T['site_name'] . ' — ' . $T['hero_badge'], 960, 1200) ?></div>
      <?php if ($T['hero_cap_text'] !== ''): ?>
      <div class="cap"><small><?= e($T['hero_cap_label']) ?></small><b><?= e($T['hero_cap_text']) ?></b></div>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($T['scroll_hint'] !== ''): ?><div class="scroll-hint"><?= e($T['scroll_hint']) ?></div><?php endif; ?>
</section>

<?php if ($HIGHLIGHTS): ?>
<div class="strip">
  <div class="container">
    <?php foreach ($HIGHLIGHTS as $h): ?>
    <div class="cell"><b><?= e($h['title']) ?></b><small><?= e($h['descr']) ?></small></div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
