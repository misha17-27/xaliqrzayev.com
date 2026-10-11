<?php
/* Panelin sahə və bölmə təsvirləri (sabitlər). */
declare(strict_types=1);

/* Mətn sahələrinin təsviri: açar => [etiket, tip, ipucu] */
const TEXT_FIELDS = [
    'Общее' => [
        'site_name'   => ['Имя на сайте (логотип, футер)', 'input'],
        'site_role'   => ['Специальность', 'input'],
        'footer_text' => ['Текст в футере', 'input'],
    ],
    'Первый экран' => [
        'hero_badge'   => ['Плашка над заголовком', 'input'],
        'hero_title_1' => ['Заголовок — первая часть (тёмная)', 'input'],
        'hero_title_2' => ['Заголовок — вторая часть (цветная)', 'input'],
        'hero_lead'    => ['Подзаголовок', 'textarea'],
        'hero_desc'    => ['Описание', 'textarea'],
        'hero_chip_1'  => ['Плашка на фото №1', 'input', 'Пусто — не показывать'],
        'hero_chip_2'  => ['Плашка на фото №2', 'input', 'Пусто — не показывать'],
    ],
    'Обо мне' => [
        'about_tag'   => ['Метка раздела', 'input'],
        'about_title' => ['Заголовок', 'input'],
        'about_body'  => ['Текст', 'textarea', 'Абзацы разделяйте пустой строкой. Теги выводятся после 2-го абзаца.'],
        'about_tags'  => ['Теги (направления)', 'textarea', 'Каждый тег — с новой строки'],
        'award_text'  => ['Награда', 'textarea', 'Пусто — блок не показывается'],
    ],
    'Обследование и диагностика' => [
        'diag_tag'         => ['Метка раздела', 'input'],
        'diag_title'       => ['Заголовок', 'input'],
        'diag_sub'         => ['Подзаголовок', 'input'],
        'diag_adult_title' => ['Карточка «взрослые» — заголовок', 'input'],
        'diag_adult_sub'   => ['Карточка «взрослые» — подпись', 'input'],
        'diag_child_title' => ['Карточка «дети» — заголовок', 'input'],
        'diag_child_sub'   => ['Карточка «дети» — подпись', 'input'],
    ],
    'Операции' => [
        'surg_tag'         => ['Метка раздела', 'input'],
        'surg_title'       => ['Заголовок', 'input'],
        'surg_sub'         => ['Подзаголовок', 'textarea'],
        'surg_adult_title' => ['Блок «взрослые» — заголовок', 'input'],
        'surg_child_title' => ['Блок «дети» — заголовок', 'input'],
    ],
    'Образование' => [
        'edu_tag'   => ['Метка раздела', 'input'],
        'edu_title' => ['Заголовок', 'input'],
    ],
    'Запись и форма' => [
        'cta_title'  => ['Заголовок блока', 'input'],
        'cta_text'   => ['Текст блока', 'textarea'],
        'form_title' => ['Заголовок формы', 'input'],
        'form_ok'    => ['Сообщение после отправки', 'textarea'],
    ],
];

const CONTACT_FIELDS = [
    'phone'     => ['Телефон (как показывать)', '+994 55 622 54 53'],
    'phone_raw' => ['Телефон для ссылки tel:', '+994556225453'],
    'whatsapp'  => ['WhatsApp (только цифры)', '994556225453'],
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
    'surg_adult' => 'Операции — взрослые',
    'surg_child' => 'Операции — дети',
];

const IMAGE_SLOTS = [
    'photo_hero'  => ['Фото на первом экране', 'Вертикальное, 4:5 (напр. 960×1200)'],
    'photo_about' => ['Фото в разделе «Обо мне»', 'Вертикальное, 2:3 (напр. 1000×1500)'],
    'og_image'    => ['Картинка для соцсетей (OG)', 'Показывается при отправке ссылки в WhatsApp / Facebook'],
];

const SUB_STATUS = ['new' => 'Новая', 'called' => 'Связались', 'done' => 'Записан', 'spam' => 'Спам'];
