<?php /* 02 · Müayinə və diaqnostika — böyüklər / uşaqlar tabları */ ?>
<section id="diaqnostika" class="dark">
  <div class="container">
    <div class="head reveal"><div class="num">02</div><div><div class="label"><?= e($T['diag_tag']) ?></div><h2><?= e($T['diag_title']) ?></h2></div></div>
    <div class="switch" role="tablist">
      <button type="button" class="active" data-pane="p-adult" role="tab"><?= e($T['diag_adult_title']) ?></button>
      <button type="button" data-pane="p-child" role="tab"><?= e($T['diag_child_title']) ?></button>
    </div>
    <?php foreach (['diag_adult' => 'p-adult', 'diag_child' => 'p-child'] as $grp => $id): ?>
    <div class="pane<?= $id === 'p-adult' ? ' active' : '' ?>" id="<?= $id ?>"><ul class="list">
      <?php foreach ($SERVICES[$grp] ?? [] as $t): ?><li><?= e($t) ?></li><?php endforeach; ?>
    </ul></div>
    <?php endforeach; ?>
  </div>
</section>
