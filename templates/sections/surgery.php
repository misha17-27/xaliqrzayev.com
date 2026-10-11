<?php /* Əməliyyatlar (akkordeon) */ ?>
<section id="emeliyyatlar" class="bg-mint">
  <div class="container">
    <div class="title"><span class="tg" style="background:#fff"><?= e($T['surg_tag']) ?></span><h2><?= e($T['surg_title']) ?></h2><p><?= e($T['surg_sub']) ?></p></div>
    <div class="acc">
      <?php foreach ([['surg_adult', '🏥', 'var(--sky)', true], ['surg_child', '🧸', 'var(--peach)', false]] as [$grp, $ic, $bg, $open]):
        $items = $SERVICES[$grp] ?? []; if (!$items) continue; ?>
      <details<?= $open ? ' open' : '' ?>>
        <summary><span class="ic" style="background:<?= $bg ?>"><?= $ic ?></span><span><?= e($T[$grp . '_title']) ?><small><?= count($items) ?> növ müdaxilə</small></span><span class="pm">+</span></summary>
        <div class="acc-body"><ul><?php foreach ($items as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
