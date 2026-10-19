<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

http_response_code(404);
render_page(['title' => $TEXTS['nf_title'] . ' — ' . $TEXTS['site_name'], 'robots' => 'noindex'], ['not-found']);
