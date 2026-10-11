# xaliqrzayev.com — сайт Dr. Xaliq Rzayev

Чистый PHP без фреймворков и WordPress. Дизайн — вариант **V3** (мятный/персиковый, «для семьи»).
Админ-панель повторяет устройство caucasuscl.com: SQLite локально, MySQL на cPanel.

Требования: PHP 7.4+ (рекомендуется 8.1+), расширения `pdo_sqlite` или `pdo_mysql`, `mbstring`, `fileinfo`; `gd` — желательно (уменьшение фото + WebP).

## Локальный запуск

```bash
C:/Users/Asus/.config/herd/bin/php83/php.exe -S localhost:8020 -t D:/xaliqrzayev.com/public D:/xaliqrzayev.com/tools/router.php
```

Сайт: <http://localhost:8020>, панель: <http://localhost:8020/admin/>.
Логин и пароль локальной панели — в `config/config.php` (`install_admin_email` / `install_admin_pass`).

## Структура

Корень сайта — только `public/`. Код, шаблоны, конфиг и база лежат вне веб-корня.

```
public/                     ← document root (на cPanel — public_html)
  _init.php                 находит корень проекта и подключает app/bootstrap.php
  index.php                 главная: собирает страницу из templates/
  muraciet.php              обработка формы записи (JSON для JS, redirect без JS)
  404.php, sitemap.php      404 и /sitemap.xml
  robots.txt, .htaccess     правила Apache/LiteSpeed: кеш, gzip, заголовки безопасности
  admin/
    index.php               вход в панель → app/admin/controller.php
    install.php             установка БД (защищена install_token)
  assets/
    css/style.css           стили сайта
    css/admin.css           стили панели
    js/main.js, js/admin.js
    img/                    фото врача, favicon; uploads/ — загрузки из панели

app/                        ← логика (вне веб-корня)
  bootstrap.php             константы путей и сайта, подключение всего ниже
  defaults.php              ⚙ начальный контент: тексты, услуги, образование, контакты, SEO
  content.php               подменяет defaults значениями из БД (если панель установлена)
  functions.php             e(), url(), asset(), picture(), icon(), render_page(), Turnstile
  mailer.php                SMTP без PHPMailer
  db.php, schema.php        PDO (SQLite/MySQL), таблицы и перенос defaults в БД
  compat.php                функции PHP 8 для PHP 7.4
  admin/
    controller.php          вход, авторизация, маршрутизация разделов
    lib.php                 сессии, CSRF, лимит входов, загрузка файлов, layout
    fields.php              описания полей и разделов
    helpers.php             табличный редактор строк
    actions.php             обработка POST + экспорт CSV
    sections/*.php          по файлу на раздел (overview, texts, services, …)

templates/                  ← HTML-шаблоны
  layout/head.php           <head>: SEO, OG, JSON-LD, аналитика
  layout/header.php         шапка и меню
  layout/footer.php         футер, кнопка WhatsApp, скрипты
  sections/*.php            hero, about, diagnostics, surgery, education, contact, not-found

config/
  config.sample.php         шаблон → скопировать в config.php (в git не попадает)
storage/                    SQLite-база, секрет формы, запасной лог заявок
design/mockups/             исходные HTML-макеты V1–V3
tools/router.php            роутер для php -S (на сервер не нужен)
```

Порядок блоков главной задаётся в `public/index.php` (`render_page(..., ['hero', 'about', …])`).
Если БД нет или она недоступна, сайт работает на `app/defaults.php`.

## Админ-панель

| Раздел | Что управляет |
|---|---|
| Обзор | новые заявки, последние заявки, состояние системы |
| Заявки с сайта | статус (новая / связались / записан / спам), заметка, WhatsApp-ссылка, экспорт CSV |
| Тексты сайта | все заголовки и тексты главной, «Обо мне», награда, тексты формы |
| Услуги и операции | 4 списка (обследование/операции × взрослые/дети): порядок, скрытие, добавление списком |
| Карточки | 4 карточки под первым экраном (эмодзи + текст) |
| Образование | годы, учреждение, описание, метка |
| Изображения | фото на первом экране, в «Обо мне», OG-картинка; библиотека загрузок |
| Контакты и соцсети | телефон, WhatsApp, e-mail, адрес, часы, Google-карта, соцсети |
| SEO и аналитика | title / description / robots, предпросмотр Google, Google Analytics |
| Почта (SMTP) | получатель заявок, SMTP, тестовое письмо (только админ) |
| Безопасность | Cloudflare Turnstile для формы и входа (только админ) |
| Пользователи | администраторы и редакторы (только админ) |
| Мой профиль | имя, e-mail, пароль |

Защита: CSRF во всех формах, лимит 6 неудачных входов за 15 мин по IP, тайм-аут сессии,
`password_hash`, проверка содержимого загружаемых файлов, запрет выполнения скриптов в `uploads/`.
Форма записи: honeypot, подписанный токен времени (бот не может отправить быстрее 3 сек),
не более 3 заявок за 10 мин с одного IP, опционально Turnstile.

## Деплой: GitHub → cPanel

Репозиторий: <https://github.com/misha17-27/xaliqrzayev.com>

`.cpanel.yml` раскладывает проект так:

```
/home/USER/xaliqrzayev.com/   app/, config/, templates/, storage/   (вне веб-доступа)
/home/USER/public_html/       содержимое public/
```

`public/_init.php` сам находит проект рядом с `public_html`.

1. **cPanel → MySQL Databases**: создать базу и пользователя, дать ALL PRIVILEGES.
2. **cPanel → Git Version Control → Create**: Clone URL репозитория, путь — например `/home/USER/repositories/xaliqrzayev.com`.
3. Вкладка *Pull or Deploy* → **Deploy HEAD Commit**.
4. В File Manager создать `/home/USER/xaliqrzayev.com/config/config.php` из `config.sample.php`:
   `driver => 'mysql'`, данные БД, свой `install_admin_pass` и длинный `install_token`.
5. Открыть `https://xaliqrzayev.com/admin/install.php?token=…` (повторный запуск безопасен — заполненные таблицы не трогает).
6. Включить SSL (AutoSSL) и раскомментировать HTTPS-редирект в `public/.htaccess`.
7. **MultiPHP Manager** → PHP 8.1+.

`config/config.php`, `storage/*` и `public/assets/img/uploads/*` не хранятся в git и деплоем не перезаписываются.

Запасной вариант без Git: загрузить весь проект в `public_html` — корневой `.htaccess`
перенаправит все запросы в `public/`, а `app/`, `config/`, `storage/` закрыты своими `.htaccess`.

Дальнейшие обновления: `git push` → cPanel *Update from Remote* → *Deploy HEAD Commit*.

## Что ещё нужно от клиента

* Адрес и часы приёма, e-mail для заявок, ссылки на соцсети — вносятся в панели.
* Ключи Cloudflare Turnstile (по желанию).
