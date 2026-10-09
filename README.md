# xaliqrzayev.com — сайт Dr. Xaliq Rzayev

Чистый PHP без фреймворков и WordPress. Дизайн — вариант **V3** (мятный/персиковый, «для семьи»).
Админ-панель повторяет устройство caucasuscl.com: SQLite локально, MySQL на cPanel.

Требования: PHP 7.4+ (рекомендуется 8.1+), расширения `pdo_sqlite` или `pdo_mysql`, `mbstring`, `fileinfo`; `gd` — желательно (уменьшение фото + WebP).

## Локальный запуск

```bash
C:/Users/Asus/.config/herd/bin/php83/php.exe -S localhost:8020 -t D:/xaliqrzayev.com D:/xaliqrzayev.com/router.php
```

Сайт: <http://localhost:8020>, панель: <http://localhost:8020/admin/>.
Логин и пароль локальной панели — в `admin/config.php` (`install_admin_email` / `install_admin_pass`).

## Структура

```
index.php              Главная страница (шаблон V3)
muraciet.php           Обработка формы записи (JSON для JS, redirect без JS)
404.php, sitemap.php   404 и /sitemap.xml
router.php             Роутер для php -S (на сервер не нужен)
.htaccess              Apache/LiteSpeed: запреты, кеш, gzip, /sitemap.xml
includes/
  config.php           Константы (SITE_URL, BASE_URL…) + загрузка контента
  defaults.php         ⚙ Начальный контент: тексты, услуги, образование, контакты, SEO
  content.php          Подменяет defaults значениями из БД (если панель установлена)
  functions.php        e(), url(), asset(), picture(), Turnstile, токен формы
  mailer.php           SMTP-отправка без PHPMailer
  compat.php           Функции PHP 8 для PHP 7.4
admin/
  index.php            Все разделы панели
  lib.php              Сессии, вход, CSRF, загрузка файлов, layout и CSS
  schema.php           Таблицы + перенос defaults.php в БД
  install.php          Установка (один раз, затем удалить на сервере)
  config.sample.php    Шаблон → скопировать в config.php (в git не попадает)
assets/                css/style.css, js/main.js, img/ (uploads/ — загрузки из панели)
storage/               SQLite-база, секрет формы, запасной лог заявок (закрыто .htaccess)
_designs/              Исходные HTML-макеты V1–V3 (закрыты .htaccess)
```

Если БД нет или она недоступна, сайт продолжает работать на `includes/defaults.php`.

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

1. **cPanel → MySQL Databases**: создать базу и пользователя, дать ALL PRIVILEGES.
2. **cPanel → Git Version Control → Create**: Clone URL репозитория, путь — например `/home/USER/repositories/xaliqrzayev.com`.
3. Файл `.cpanel.yml` в репозитории копирует сайт в `public_html` (нажать **Deploy HEAD Commit** во вкладке *Pull or Deploy*).
   Если `public_html` у аккаунта другой — поправить `DEPLOYPATH` в `.cpanel.yml`.
4. На сервере (File Manager) создать `public_html/admin/config.php` из `config.sample.php`:
   `driver => 'mysql'`, данные БД, свой `install_admin_pass` и `install_token`.
5. Открыть `https://xaliqrzayev.com/admin/install.php?token=…` (повторный запуск безопасен — заполненные таблицы не трогает).
   Деплой восстанавливает `install.php`, поэтому защищает его длинный случайный `install_token`.
6. Включить SSL (AutoSSL) и раскомментировать HTTPS-редирект в `.htaccess`.
7. **MultiPHP Manager** → PHP 8.1+.

`admin/config.php`, `storage/*` и `assets/img/uploads/*` не хранятся в git и деплоем не перезаписываются.

Дальнейшие обновления: `git push` → cPanel *Update from Remote* → *Deploy HEAD Commit*.

## Что ещё нужно от клиента

* Адрес и часы приёма, e-mail для заявок, ссылки на соцсети — вносятся в панели.
* Ключи Cloudflare Turnstile (по желанию).
