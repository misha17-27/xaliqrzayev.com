<?php /* Əlaqə + qəbul formu + xəritə */ ?>
<section id="elaqe" style="padding-top:0">
  <div class="container">
    <div class="contact-grid">
      <div class="cta">
        <h2><?= e($T['cta_title']) ?></h2>
        <p><?= e($T['cta_text']) ?></p>
        <a class="ph" href="<?= e($tel) ?>"><?= e($C['phone']) ?></a>
        <div class="acts">
          <a class="btn btn-white" href="<?= e($tel) ?>">📞 Zəng et</a>
          <a class="btn btn-wa" href="<?= e($wa) ?>" target="_blank" rel="noopener">💬 WhatsApp</a>
        </div>
        <?php if ($C['address'] !== '' || $C['hours'] !== '' || $C['email'] !== ''): ?>
        <ul class="cinfo">
          <?php if ($C['address'] !== ''): ?><li>📍 <span><?= nl2br(e($C['address']), false) ?></span></li><?php endif; ?>
          <?php if ($C['hours'] !== ''): ?><li>🕘 <span><?= nl2br(e($C['hours']), false) ?></span></li><?php endif; ?>
          <?php if ($C['email'] !== ''): ?><li>✉️ <a href="mailto:<?= e($C['email']) ?>"><?= e($C['email']) ?></a></li><?php endif; ?>
        </ul>
        <?php endif; ?>
        <?php $soc = array_filter(['instagram', 'facebook', 'youtube', 'tiktok'], function ($k) use ($C) { return ($C[$k] ?? '') !== ''; }); if ($soc): ?>
        <div class="socials"><?php foreach ($soc as $k): ?><a href="<?= e($C[$k]) ?>" target="_blank" rel="noopener" aria-label="<?= ucfirst($k) ?>"><?= $icons[$k] ?></a><?php endforeach; ?></div>
        <?php endif; ?>
      </div>

      <form class="form-card" id="appt" method="post" action="<?= e(url('muraciet.php')) ?>" novalidate>
        <h3><?= e($T['form_title']) ?></h3>
        <p class="muted">Adınızı və nömrənizi yazın — sizə zəng edək.</p>
        <input type="hidden" name="_t" value="<?= e(form_token()) ?>">
        <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <label for="f-name">Ad, soyad</label>
        <input id="f-name" type="text" name="name" required minlength="2" maxlength="80" autocomplete="name">
        <label for="f-phone">Telefon</label>
        <input id="f-phone" type="tel" name="phone" required maxlength="25" placeholder="+994 __ ___ __ __" autocomplete="tel" inputmode="tel">
        <label for="f-msg">Şikayət / sual <span style="font-weight:600;color:var(--muted)">(istəyə bağlı)</span></label>
        <textarea id="f-msg" name="message" maxlength="1500"></textarea>
        <?php if (turnstile_on()): ?><div class="cf-turnstile" data-sitekey="<?= e(turnstile_site()) ?>" data-language="az"></div><?php endif; ?>
        <button class="btn btn-teal" type="submit">Göndər</button>
        <div class="form-msg<?= $sent ? ' ok' : ($formErr !== '' ? ' err' : '') ?>" role="status" aria-live="polite"><?= $sent ? e($T['form_ok']) : e($formErr) ?></div>
      </form>
    </div>
    <?php if ($C['map'] !== '' && preg_match('~^https://(www\.)?google\.[a-z.]+/maps/embed~', $C['map'])): ?>
    <div class="map"><iframe src="<?= e($C['map']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Xəritə"></iframe></div>
    <?php endif; ?>
  </div>
</section>
