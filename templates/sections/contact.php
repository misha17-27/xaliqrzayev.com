<?php
/* 05 · Əlaqə: başlıq + qəbul formu (sol), telefon kartı (sağ), xəritə */
$ctaWords = preg_split('/\s+/u', trim($T['cta_title']));
$ctaLast  = count($ctaWords) > 1 ? array_pop($ctaWords) : '';
$soc = array_filter(['instagram', 'facebook', 'youtube', 'tiktok'], function ($k) use ($C) { return ($C[$k] ?? '') !== ''; });
?>
<section id="elaqe">
  <div class="container contact">
    <div class="reveal">
      <div class="label" style="font-size:12px;letter-spacing:.24em;text-transform:uppercase;color:var(--gold);font-weight:700;margin-bottom:14px">05 · <?= e($T['cta_tag']) ?></div>
      <h2><?= e(implode(' ', $ctaWords)) ?><?php if ($ctaLast !== ''): ?> <em><?= e($ctaLast) ?></em><?php endif; ?></h2>
      <p><?= e($T['cta_text']) ?></p>

      <form class="form-box" id="appt" method="post" action="<?= e(url('muraciet.php')) ?>" novalidate data-err-name="<?= e($T['form_err_name']) ?>" data-err-phone="<?= e($T['form_err_phone']) ?>" data-err-net="<?= e($T['form_err_net']) ?>">
        <h3><?= e($T['form_title']) ?></h3>
        <?php if ($T['form_sub'] !== ''): ?><p class="muted"><?= e($T['form_sub']) ?></p><?php endif; ?>
        <input type="hidden" name="_t" value="<?= e(form_token()) ?>">
        <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <label for="f-name"><?= e($T['form_name']) ?></label>
        <input id="f-name" type="text" name="name" required minlength="2" maxlength="80" autocomplete="name">
        <label for="f-phone"><?= e($T['form_phone']) ?></label>
        <input id="f-phone" type="tel" name="phone" required maxlength="25" placeholder="<?= e($T['form_phone_ph']) ?>" autocomplete="tel" inputmode="tel">
        <label for="f-msg"><?= e($T['form_msg']) ?><?php if ($T['form_optional'] !== ''): ?> <span>(<?= e($T['form_optional']) ?>)</span><?php endif; ?></label>
        <textarea id="f-msg" name="message" maxlength="1500"></textarea>
        <?php if (turnstile_on()): ?><div class="cf-turnstile" data-sitekey="<?= e(turnstile_site()) ?>" data-language="az"></div><?php endif; ?>
        <button class="btn btn-gold" type="submit"><?= e($T['form_btn']) ?></button>
        <div class="form-msg<?= $sent ? ' ok' : ($formErr !== '' ? ' err' : '') ?>" role="status" aria-live="polite"><?= $sent ? e($T['form_ok']) : e($formErr) ?></div>
      </form>
    </div>

    <div class="contact-card reveal">
      <small><?= e($T['contact_card_label']) ?></small>
      <a class="ph" href="<?= e($tel) ?>"><?= e($C['phone']) ?></a>
      <div class="btns">
        <a class="btn btn-gold" href="<?= e($tel) ?>"><?= e($T['btn_call']) ?></a>
        <a class="btn btn-ghost" href="<?= e($wa) ?>" target="_blank" rel="noopener"><?= e($T['btn_wa_write']) ?></a>
      </div>
      <?php if ($C['address'] !== '' || $C['hours'] !== '' || $C['email'] !== ''): ?>
      <ul class="info">
        <?php if ($C['address'] !== ''): ?><li><span>📍</span><span><?= nl2br(e($C['address']), false) ?></span></li><?php endif; ?>
        <?php if ($C['hours'] !== ''): ?><li><span>🕘</span><span><?= nl2br(e($C['hours']), false) ?></span></li><?php endif; ?>
        <?php if ($C['email'] !== ''): ?><li><span>✉</span><a href="mailto:<?= e($C['email']) ?>"><?= e($C['email']) ?></a></li><?php endif; ?>
      </ul>
      <?php endif; ?>
      <?php if ($soc): ?>
      <div class="socials"><?php foreach ($soc as $k): ?><a href="<?= e($C[$k]) ?>" target="_blank" rel="noopener" aria-label="<?= ucfirst($k) ?>"><?= icon($k) ?></a><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($C['map'] !== '' && preg_match('~^https://(www\.)?google\.[a-z.]+/maps/embed~', $C['map'])): ?>
  <div class="container"><div class="map"><iframe src="<?= e($C['map']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?= e($T['nav_contact']) ?>"></iframe></div></div>
  <?php endif; ?>
</section>
