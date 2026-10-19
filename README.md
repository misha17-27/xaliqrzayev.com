# xaliqrzayev.com — сайт Dr. Xaliq Rzayev

Чистый PHP без фреймворков и WordPress. Дизайн — вариант **V2 «Premium»** (тёмно-синий + золото, заголовки Lora).
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
  notify.php                WhatsApp: ссылка для пациента + автоотправка через API
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
  sections/*.php            hero (+ полоса), about (01), diagnostics (02), surgery (03),
                            education (04), contact (05), not-found

config/
  config.sample.php         шаблон → скопировать в config.php (в git не попадает)
storage/                    SQLite-база, секрет формы, запасной лог заявок
tools/router.php            роутер для php -S (на сервер не нужен)
```

Порядок блоков главной задаётся в `public/index.php` (`render_page(..., ['hero', 'about', …])`).
Если БД нет или она недоступна, сайт работает на `app/defaults.php`.

## Админ-панель

Интерфейс на двух языках — **AZ** (по умолчанию) и **RU**, переключатель в верхней панели и на странице входа,
выбор запоминается в cookie. В коде строки пишутся по-русски через `t('…')`, перевод — `app/admin/lang/az.php`.
После правок админки проверить, что всё переведено:

```bash
php tools/i18n-check.php
```

Всё, что видно на сайте, редактируется в панели: тексты всех блоков, меню и кнопки, поля и ошибки формы записи,
страница 404, списки услуг, полоса, образование, фото, контакты, SEO.

| Раздел | Что управляет |
|---|---|
| Обзор | новые заявки, последние заявки, состояние системы |
| Заявки с сайта | статус (новая / связались / записан / спам), заметка, WhatsApp-ссылка, экспорт CSV |
| Тексты сайта | все тексты главной по блокам 01–05, меню и кнопки, поля и ошибки формы, страница 404 |
| Услуги и операции | 5 списков: обследование (взрослые, дети); операции взрослых — открытые и эндоскопические; операции детей. Порядок, скрытие, добавление списком |
| Полоса под экраном | 4 ячейки под первым экраном (золотой заголовок + подпись) |
| Образование | годы, учреждение, описание, метка |
| Изображения | фото на первом экране, OG-картинка; библиотека загрузок |
| Контакты и соцсети | телефон, WhatsApp, адрес, часы, Google-карта, соцсети |
| SEO и аналитика | title / description / robots, предпросмотр Google, Google Analytics |
| WhatsApp-уведомления | заявки в WhatsApp врача через TextMeBot / CallMeBot / свой URL; кнопка «отправить в WhatsApp» для пациента (только админ) |
| Безопасность | Cloudflare Turnstile для формы и входа (только админ) |
| Пользователи | администраторы и редакторы (только админ) |
| Мой профиль | имя, e-mail, пароль |

Защита: CSRF во всех формах, лимит 6 неудачных входов за 15 мин по IP, тайм-аут сессии,
`password_hash`, проверка содержимого загружаемых файлов, запрет выполнения скриптов в `uploads/`.
Форма записи: имя, телефон, сообщение. Заявка отправляется **только в WhatsApp**: после нажатия кнопки у пациента открывается WhatsApp с готовым текстом на номер врача; дополнительно — автоотправка через API (раздел «WhatsApp-уведомления»). Каждая заявка сохраняется в «Заявки с сайта». Защита: honeypot, подписанный токен времени (бот не может отправить быстрее 3 сек),
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
4. Открыть `https://xaliqrzayev.com/admin/` — пока нет `config/config.php`, там мастер установки:
   данные базы + логин и пароль администратора → он сам создаёт `config/config.php`, таблицы и пользователя.
   Мастер принимает только MySQL на `localhost` и после создания конфига отключается.
5. Если `config/config.php` уже создан вручную, а таблиц нет — `/admin/` попросит `install_token` из конфига
   и создаст таблицы (то же делает `/admin/install.php?token=…`).
6. Включить SSL (AutoSSL) и раскомментировать HTTPS-редирект в `public/.htaccess`.
7. **MultiPHP Manager** → PHP 8.1+.

`config/config.php`, `storage/*` и `public/assets/img/uploads/*` не хранятся в git и деплоем не перезаписываются.

Запасной вариант без Git: загрузить весь проект в `public_html` — корневой `.htaccess`
перенаправит все запросы в `public/`, а `app/`, `config/`, `storage/` закрыты своими `.htaccess`.

Дальнейшие обновления: `git push` → cPanel *Update from Remote* → *Deploy HEAD Commit*.

## Что ещё нужно от клиента

* Адрес и часы приёма, ссылки на соцсети — вносятся в панели.
* Ключи Cloudflare Turnstile (по желанию).
