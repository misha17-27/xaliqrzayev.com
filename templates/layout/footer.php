<?php /* Futer, WhatsApp düyməsi, skriptlər. Səhifəni bağlayır. */ ?>
<footer><div class="container"><span>© <?= date('Y') ?> <?= e($T['site_name']) ?> — <?= e($T['footer_text']) ?></span><a href="<?= e($tel) ?>"><?= e($C['phone']) ?></a></div></footer>

<a class="fab" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('wa') ?></a>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
<?php if (turnstile_on()): ?><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script><?php endif; ?>
</body>
</html>
