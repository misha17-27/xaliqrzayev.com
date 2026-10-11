<?php /* Birinci ekran + qısa kartlar */ ?>
<section class="hero">
  <div class="container">
    <div class="hero-card">
      <div>
        <span class="pill">👨‍⚕️ <?= e($T['hero_badge']) ?></span>
        <h1><?= e($T['hero_title_1']) ?> <span><?= e($T['hero_title_2']) ?></span></h1>
        <p class="lead"><?= e($T['hero_lead']) ?></p>
        <p class="desc"><?= e($T['hero_desc']) ?></p>
        <div class="hero-actions">
          <a class="btn btn-teal" href="<?= e($tel) ?>">Zəng et</a>
          <a class="btn btn-wa" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
        </div>
      </div>
      <div class="hero-img">
        <div class="blob"><?= picture($IMAGES['photo_hero'], $T['site_name'] . ' — ' . $T['site_role'], 960, 1200) ?></div>
        <?php if ($T['hero_chip_1'] !== ''): ?><div class="chip c1"><span class="e">🧒</span><?= e($T['hero_chip_1']) ?></div><?php endif; ?>
        <?php if ($T['hero_chip_2'] !== ''): ?><div class="chip c2"><span class="e">🩺</span><?= e($T['hero_chip_2']) ?></div><?php endif; ?>
      </div>
    </div>
    <?php if ($HIGHLIGHTS): ?>
    <div class="quick">
      <?php $bgs = ['var(--mint)', 'var(--sky)', 'var(--peach)', '#f1ecff']; foreach ($HIGHLIGHTS as $i => $h): ?>
      <div class="q"><div class="ic" style="background:<?= $bgs[$i % 4] ?>"><?= e($h['icon']) ?></div><h3><?= e($h['title']) ?></h3><p><?= e($h['descr']) ?></p></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
