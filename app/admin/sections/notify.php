<?php
/* Bölmə: notify — WhatsApp bildirişləri (TextMeBot / CallMeBot / öz URL) və pasiyent üçün WhatsApp düyməsi. */
declare(strict_types=1);

$waOn   = setting('wa_api_on') === '1';
$btnOn  = setting('wa_visitor_btn') !== '0';
$prov   = setting('wa_api_provider') ?: 'textmebot';
$phone  = setting('wa_api_phone');
$sitePh = (string)($CONTACT['whatsapp'] ?? '');
$hasKey = setting('wa_api_key') !== '';
$ready  = $waOn && ($hasKey || ($prov === 'custom' && setting('wa_api_url') !== ''));

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="save">';

echo '<h3>' . e(t('Автоматическое уведомление в WhatsApp')) . '</h3>';
echo '<p class="muted">' . e(t('Каждая новая заявка с сайта приходит сообщением на WhatsApp врача. Нужен сервис-посредник: официальный WhatsApp Business API требует долгого подключения через Meta.')) . '</p>';
echo '<p>' . e(t('Статус:')) . ' ' . ($ready ? '<span class="pill">' . e(t('Включено')) . '</span>' : '<span class="pill warn">' . e(t('Выключено')) . '</span>') . '</p>';

echo '<label>' . e(t('Сервис')) . '</label><select name="wa_api_provider">';
foreach (['textmebot' => t('TextMeBot — рекомендуется ($1/мес, 2 дня бесплатно)'), 'callmebot' => t('CallMeBot — бесплатно (если есть свободные места)'), 'custom' => t('Свой URL (другой сервис)')] as $k => $l) {
    echo '<option value="' . $k . '"' . ($prov === $k ? ' selected' : '') . '>' . e($l) . '</option>';
}
echo '</select>';

echo '<fieldset><legend>TextMeBot</legend><ol class="muted" style="line-height:1.8;margin:6px 0 0;padding-left:20px">'
   . '<li>' . t('На <a href="https://textmebot.com" target="_blank" rel="noopener">textmebot.com</a> нажмите «Get Demo APIKey» — ключ придёт на e-mail.') . '</li>'
   . '<li>' . e(t('По ссылке из письма добавьте номер врача и привяжите его WhatsApp (сканирование QR-кода в WhatsApp → Связанные устройства).')) . '</li>'
   . '<li>' . e(t('Вставьте apikey ниже, сохраните и нажмите «Отправить тест». После демо оплатите тариф «1 recipient» — сообщения будут приходить врачу самому себе.')) . '</li>'
   . '</ol></fieldset>';
echo '<fieldset><legend>CallMeBot</legend><p class="muted" style="margin:6px 0 0">'
   . t('На <a href="https://www.callmebot.com/blog/free-api-whatsapp-messages/" target="_blank" rel="noopener">callmebot.com</a> возьмите номер бота, добавьте его в контакты и отправьте с WhatsApp врача: <code>I allow callmebot to send me messages</code>. В ответ придёт apikey. Сервис бесплатный, но иногда не принимает новых пользователей.')
   . '</p></fieldset>';

echo '<label class="inline" style="margin-top:16px"><input type="checkbox" name="wa_api_on" value="1"' . ($waOn ? ' checked' : '') . '> ' . e(t('Отправлять заявки в WhatsApp автоматически')) . '</label>';
echo '<div class="row"><div><label>' . e(t('Номер WhatsApp для уведомлений')) . '</label><input type="text" name="wa_api_phone" value="' . e($phone) . '" placeholder="' . e($sitePh) . '">'
   . '<div class="hint">' . e(t('Пусто — номер WhatsApp из раздела «Контакты»')) . ': ' . e($sitePh) . '</div></div>';
echo '<div><label>API key</label><input type="password" name="wa_api_key" value="" placeholder="' . ($hasKey ? e(t('•••••••• (сохранён)')) : '') . '" autocomplete="new-password"></div></div>';
echo '<label>' . e(t('Свой URL (только для «Свой URL»)')) . '</label><input type="text" name="wa_api_url" value="' . e(setting('wa_api_url')) . '" placeholder="https://api.example.com/send?to={phone}&amp;key={key}&amp;text={text}">';
echo '<div class="hint">' . e(t('Подставляются: {phone} — номер цифрами, {text} — текст заявки, {key} — API key.')) . '</div>';

echo '<h4>' . e(t('Кнопка для пациента')) . '</h4>';
echo '<label class="inline"><input type="checkbox" name="wa_visitor_btn" value="1"' . ($btnOn ? ' checked' : '') . '> ' . e(t('После отправки формы показывать кнопку «отправить заявку в WhatsApp»')) . '</label>';
echo '<div class="hint">' . e(t('Кнопка открывает WhatsApp пациента с готовым текстом заявки на номер врача. Работает без настроек. Текст кнопки — в «Тексты сайта» → «Форма записи».')) . '</div>';

echo '<p><button class="btn">' . e(t('Сохранить')) . '</button></p></form>';

echo '<form method="post" class="panel">' . csrf_field() . '<input type="hidden" name="action" value="test">';
echo '<h3>' . e(t('Проверка')) . '</h3><p class="muted">' . e(t('Отправит тестовое сообщение на номер для уведомлений.')) . '</p>';
echo '<button class="btn navy">' . e(t('Отправить тест в WhatsApp')) . '</button></form>';
