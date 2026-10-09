<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

$T   = $TEXTS;
$C   = $CONTACT;
$seo = $SEO['home'] ?? [];
$tel = tel_link($C);
$wa  = wa_link($C);

$sent    = isset($_GET['sent']);
$formErr = isset($_GET['err']) ? mb_substr((string)$_GET['err'], 0, 200) : '';

/* Uzun siyahılarda ilk N element görünür, qalanı «Hamısını göstər» ilə açılır */
const DIAG_VISIBLE = 8;

$icons = [
    'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>',
    'wa'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 2.5 1 3 .8 3.6.7.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>',
    'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>',
    'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H8v4h2v8h4v-8h3l1-4h-4V8z"/></svg>',
    'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 7.5a3 3 0 0 0-2.1-2.1C19 5 12 5 12 5s-7 0-8.9.4A3 3 0 0 0 1 7.5 31 31 0 0 0 .6 12 31 31 0 0 0 1 16.5a3 3 0 0 0 2.1 2.1C5 19 12 19 12 19s7 0 8.9-.4a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .4-4.5 31 31 0 0 0-.4-4.5zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg>',
    'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.3v12.4a2.6 2.6 0 1 1-2.6-2.6c.3 0 .5 0 .8.1V9.5a6 6 0 1 0 5.1 5.9V9.1a7.6 7.6 0 0 0 4.5 1.4V7.2a4.3 4.3 0 0 1-3.4-1.4z"/></svg>',
];

/** Siyahını <li>-lərə çevirir, ilk $visible elementdən sonrakıları gizlədir */
function check_list(array $items, int $visible): string {
    $out = '';
    foreach ($items as $i => $t) {
        $out .= '<li' . ($i >= $visible ? ' class="more"' : '') . '>' . e($t) . '</li>';
    }
    return $out;
}

$canonical = SITE_URL . '/';
$ogImage   = SITE_URL . img($IMAGES['og_image']);
?><!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($seo['title'] ?? $T['site_name']) ?></title>
<meta name="description" content="<?= e($seo['descr'] ?? '') ?>">
<meta name="robots" content="<?= e($seo['robots'] ?? 'index,follow') ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="az_AZ">
<meta property="og:title" content="<?= e($seo['title'] ?? $T['site_name']) ?>">
<meta property="og:description" content="<?= e($seo['descr'] ?? '') ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0f8b8d">
<link rel="icon" href="<?= e(img('favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<script type="application/ld+json"><?= json_encode([
    '@context'  => 'https://schema.org',
    '@type'     => 'Physician',
    'name'      => $T['site_name'],
    'medicalSpecialty' => 'Urologic',
    'description' => $seo['descr'] ?? '',
    'image'     => $ogImage,
    'url'       => $canonical,
    'telephone' => $C['phone_raw'],
] + ($C['address'] !== '' ? ['address' => $C['address']] : []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php if (($ga = cms_setting('ga_id')) !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config',<?= json_encode($ga) ?>);</script>
<?php endif; ?>
</head>
<body>

<header>
  <div class="container nav">
    <a href="<?= e(url('/')) ?>" class="logo"><i>XR</i><span><?= e($T['site_name']) ?></span></a>
    <nav class="menu" id="menu">
      <a href="#haqqimda">Haqqımda</a>
      <a href="#muayine">Müayinə</a>
      <a href="#emeliyyatlar">Əməliyyatlar</a>
      <a href="#tehsil">Təhsil</a>
      <a href="#elaqe">Əlaqə</a>
    </nav>
    <a class="btn btn-teal" href="<?= e($tel) ?>" style="padding:11px 18px;font-size:15px"><?= $icons['phone'] ?>Zəng</a>
    <button class="burger" aria-label="Menyu" aria-controls="menu" aria-expanded="false"><span></span><span></span><span></span></button>
  </div>
</header>

<main>
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

<section id="muayine">
  <div class="container">
    <div class="title"><span class="tg"><?= e($T['diag_tag']) ?></span><h2><?= e($T['diag_title']) ?></h2><p><?= e($T['diag_sub']) ?></p></div>
    <div class="aud">
      <?php foreach ([['diag_adult', 'adult', '🧑'], ['diag_child', 'child', '🧒']] as [$grp, $cls, $av]):
        $items = $SERVICES[$grp] ?? []; if (!$items) continue; ?>
      <div class="aud-card <?= $cls ?>">
        <div class="aud-head"><span class="av"><?= $av ?></span><div><h3><?= e($T[$grp . '_title']) ?></h3><small><?= e($T[$grp . '_sub']) ?></small></div></div>
        <ul class="checks"><?= check_list($items, DIAG_VISIBLE) ?></ul>
        <?php if (count($items) > DIAG_VISIBLE): ?><button class="toggle" type="button" aria-expanded="false">Hamısını göstər (<?= count($items) ?>) ↓</button><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

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

<?php if ($EDUCATION): ?>
<section id="tehsil">
  <div class="container">
    <div class="title"><span class="tg"><?= e($T['edu_tag']) ?></span><h2><?= e($T['edu_title']) ?></h2></div>
    <div class="edu">
      <?php foreach ($EDUCATION as $ed): ?>
      <div class="edu-card"><span class="yr"><?= e($ed['years']) ?></span><h4><?= e($ed['title']) ?></h4><p><?= nl2br(e($ed['descr']), false) ?></p><?php if ($ed['label'] !== ''): ?><span class="lbl"><?= e($ed['label']) ?></span><?php endif; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

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
</main>

<footer>© <?= date('Y') ?> <?= e($T['site_name']) ?> · <?= e($T['footer_text']) ?> · <a href="<?= e($tel) ?>"><?= e($C['phone']) ?></a></footer>

<a class="fab" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= $icons['wa'] ?></a>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
<?php if (turnstile_on()): ?><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script><?php endif; ?>
</body>
</html>
