<?php
/* Müayinə və diaqnostika: böyüklər / uşaqlar */
$visible = 8;   // ilk 8 bənd görünür, qalanı «Hamısını göstər» ilə açılır
?>
<section id="muayine">
  <div class="container">
    <div class="title"><span class="tg"><?= e($T['diag_tag']) ?></span><h2><?= e($T['diag_title']) ?></h2><p><?= e($T['diag_sub']) ?></p></div>
    <div class="aud">
      <?php foreach ([['diag_adult', 'adult', '🧑'], ['diag_child', 'child', '🧒']] as [$grp, $cls, $av]):
        $items = $SERVICES[$grp] ?? []; if (!$items) continue; ?>
      <div class="aud-card <?= $cls ?>">
        <div class="aud-head"><span class="av"><?= $av ?></span><div><h3><?= e($T[$grp . '_title']) ?></h3><small><?= e($T[$grp . '_sub']) ?></small></div></div>
        <ul class="checks"><?= check_list($items, $visible) ?></ul>
        <?php if (count($items) > $visible): ?><button class="toggle" type="button" aria-expanded="false">Hamısını göstər (<?= count($items) ?>) ↓</button><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
