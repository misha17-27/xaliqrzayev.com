<?php
/* Panelin sahə və bölmə təsvirləri (sabitlər). */
declare(strict_types=1);

/* Mətn sahələrinin təsviri: açar => [etiket, tip, ipucu] */
const TEXT_FIELDS = [
    'Общее' => [
        'site_name'   => ['Имя на сайте (шапка, футер)', 'input'],
        'site_role'   => ['Подпись под именем в шапке', 'input'],
        'footer_text' => ['Текст в футере', 'input'],
    ],
    'Первый экран' => [
        'hero_badge'     => ['Строка над заголовком (золотая)', 'input'],
        'hero_title_1'   => ['Заголовок — первая строка', 'input'],
        'hero_title_2'   => ['Заголовок — вторая строка (золотой курсив)', 'input'],
        'hero_lead'      => ['Подзаголовок', 'textarea'],
        'hero_desc'      => ['Описание', 'textarea'],
        'hero_cap_label' => ['Плашка на фото — мелкая подпись', 'input'],
        'hero_cap_text'  => ['Плашка на фото — текст', 'input', 'Пусто — плашка не показывается'],
    ],
    '01 · Обо мне' => [
        'about_tag'   => ['Метка раздела', 'input'],
        'about_title' => ['Заголовок', 'input'],
        'about_quote' => ['Крупная цитата (курсив)', 'textarea'],
        'about_tags'  => ['Направления под цитатой (1., 2., 3. …)', 'textarea', 'Каждое направление — с новой строки'],
        'about_body'  => ['Текст справа', 'textarea', 'Абзацы разделяйте пустой строкой'],
        'award_text'  => ['Награда', 'textarea', 'Пусто — блок не показывается'],
    ],
    '02 · Обследование и диагностика' => [
        'diag_tag'         => ['Метка раздела', 'input'],
        'diag_title'       => ['Заголовок', 'input'],
        'diag_adult_title' => ['Вкладка «взрослые»', 'input'],
        'diag_child_title' => ['Вкладка «дети»', 'input'],
    ],
    '03 · Операции' => [
        'surg_tag'         => ['Метка раздела', 'input'],
        'surg_title'       => ['Заголовок', 'input'],
        'surg_aside_title' => ['Текст слева — заголовок', 'input'],
        'surg_sub'         => ['Текст слева — описание', 'textarea'],
        'surg_adult_title' => ['Блок «взрослые» — заголовок', 'input'],
        'surg_open_title'  => ['Взрослые — подзаголовок открытых операций', 'input'],
        'surg_endo_title'  => ['Взрослые — подзаголовок эндоскопических операций', 'input'],
        'surg_child_title' => ['Блок «дети» — заголовок', 'input'],
    ],
    '04 · Образование' => [
        'edu_tag'   => ['Метка раздела', 'input'],
        'edu_title' => ['Заголовок', 'input'],
    ],
    'Меню и кнопки' => [
        'nav_about'          => ['Меню: «Обо мне»', 'input'],
        'nav_diag'           => ['Меню: «Диагностика»', 'input'],
        'nav_surg'           => ['Меню: «Хирургия»', 'input'],
        'nav_edu'            => ['Меню: «Образование»', 'input'],
        'nav_contact'        => ['Меню: «Контакты»', 'input'],
        'btn_header'         => ['Кнопка в шапке', 'input'],
        'btn_call'           => ['Кнопка «Позвонить»', 'input'],
        'btn_wa'             => ['Кнопка WhatsApp (первый экран)', 'input'],
        'btn_wa_write'       => ['Кнопка WhatsApp (контакты)', 'input'],
        'btn_consult'        => ['Кнопка в блоке операций', 'input'],
        'scroll_hint'        => ['Подсказка внизу первого экрана', 'input', 'Пусто — не показывать'],
        'contact_card_label' => ['Подпись над телефоном в контактах', 'input'],
    ],
    '05 · Контакты и форма' => [
        'cta_tag'    => ['Метка раздела', 'input'],
        'cta_title'  => ['Заголовок (последнее слово — золотым курсивом)', 'input'],
        'cta_text'   => ['Текст', 'textarea'],
        'form_title' => ['Заголовок формы', 'input'],
        'form_ok'    => ['Сообщение после отправки', 'textarea'],
    ],
    'Форма записи — поля и ошибки' => [
        'form_sub'         => ['Подсказка под заголовком формы', 'input', 'Пусто — не показывать'],
        'form_name'        => ['Поле «Имя»', 'input'],
        'form_phone'       => ['Поле «Телефон»', 'input'],
        'form_phone_ph'    => ['Пример номера в поле телефона', 'input'],
        'form_email'       => ['Поле «E-mail» (необязательное)', 'input'],
        'form_msg'         => ['Поле «Сообщение»', 'input'],
        'form_optional'    => ['Пометка «необязательно»', 'input', 'Пусто — не показывать'],
        'form_btn'         => ['Кнопка отправки', 'input'],
        'form_err_name'    => ['Ошибка: не указано имя', 'input'],
        'form_err_phone'   => ['Ошибка: неверный телефон', 'input'],
        'form_err_email'   => ['Ошибка: неверный e-mail', 'input'],
        'form_err_long'    => ['Ошибка: слишком длинное сообщение', 'input'],
        'form_err_robot'   => ['Ошибка: проверка на робота', 'input'],
        'form_err_refresh' => ['Ошибка: устаревшая страница', 'input'],
        'form_err_rate'    => ['Ошибка: слишком много заявок', 'input'],
        'form_err_net'     => ['Ошибка: нет связи', 'input'],
        'form_wa_btn'      => ['Кнопка WhatsApp после отправки', 'input'],
        'form_wa_intro'    => ['Первая строка сообщения в WhatsApp', 'input'],
    ],
    'Страница 404' => [
        'nf_title' => ['Заголовок', 'input'],
        'nf_text'  => ['Текст', 'input'],
        'nf_btn'   => ['Кнопка', 'input'],
    ],
];

const CONTACT_FIELDS = [
    'phone'     => ['Телефон (как показывать)', '+994 50 622 54 53'],
    'phone_raw' => ['Телефон для ссылки tel:', '+994506225453'],
    'whatsapp'  => ['WhatsApp (только цифры)', '994506225453'],
    'email'     => ['E-mail', ''],
    'address'   => ['Адрес приёма', 'Пусто — не показывать'],
    'hours'     => ['Часы приёма', 'Напр.: B.e.–Cümə 10:00–18:00'],
    'map'       => ['Google Maps — ссылка для встраивания (src из iframe)', 'https://www.google.com/maps/embed?pb=...'],
    'instagram' => ['Instagram', 'https://instagram.com/...'],
    'facebook'  => ['Facebook', 'https://facebook.com/...'],
    'youtube'   => ['YouTube', ''],
    'tiktok'    => ['TikTok', ''],
];

const SVC_GROUPS = [
    'diag_adult' => 'Обследование — взрослые',
    'diag_child' => 'Обследование — дети',
    'surg_open'  => 'Операции — взрослые, открытые',
    'surg_endo'  => 'Операции — взрослые, эндоскопические',
    'surg_child' => 'Операции — дети',
];

const IMAGE_SLOTS = [
    'photo_hero'  => ['Фото на первом экране', 'Вертикальное, 4:5 (напр. 960×1200)'],
    'og_image'    => ['Картинка для соцсетей (OG)', 'Показывается при отправке ссылки в WhatsApp / Facebook'],
];

const SUB_STATUS = ['new' => 'Новая', 'called' => 'Связались', 'done' => 'Записан', 'spam' => 'Спам'];
