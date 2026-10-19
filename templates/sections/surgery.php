<?php /* 03 · Əməliyyatlar: böyüklər (açıq + endoskopik) və uşaqlar */ ?>
<section id="cerrahi" class="light">
  <div class="container">
    <div class="head reveal"><div class="num">03</div><div><div class="label"><?= e($T['surg_tag']) ?></div><h2><?= e($T['surg_title']) ?></h2></div></div>
    <div class="surg">
      <aside class="reveal">
        <h3><?= e($T['surg_aside_title']) ?></h3>
        <p><?= e($T['surg_sub']) ?></p>
        <a class="btn btn-gold" style="margin-top:24px" href="#elaqe"><?= e($T['btn_consult']) ?></a>
      </aside>
      <div>
        <?php if (!empty($SERVICES['surg_open']) || !empty($SERVICES['surg_endo'])): ?>
        <div class="surg-block reveal">
          <h4><?= e($T['surg_adult_title']) ?></h4>
          <div class="surg-cols">
            <?php foreach (['surg_open', 'surg_endo'] as $grp): if (empty($SERVICES[$grp])) continue; ?>
            <div>
              <h5><?= e($T[$grp . '_title']) ?></h5>
              <ul class="list"><?php foreach ($SERVICES[$grp] as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($SERVICES['surg_child'])): ?>
        <div class="surg-block reveal">
          <h4><?= e($T['surg_child_title']) ?></h4>
          <ul class="list"><?php foreach ($SERVICES['surg_child'] as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
