<?php
/*
 * Saytın ilkin məzmunu. install.php bunu bazaya köçürür;
 * baza yoxdursa sayt birbaşa bu dəyərlərlə işləyir.
 */

/* ---------------- Mətnlər ---------------- */
$TEXTS = [
    'site_name'        => 'Dr. Xaliq Rzayev',
    'site_role'        => 'Uroloq-Androloq',

    'hero_badge'       => 'Uroloq-Androloq',
    'hero_title_1'     => 'Dr.',
    'hero_title_2'     => 'Xaliq Rzayev',
    'hero_lead'        => 'Böyüklər və uşaqlar üçün uroloji müayinə, diaqnostika və cərrahi müalicə',
    'hero_desc'        => 'Uroloji və androloji xəstəliklərin müayinəsi, diaqnostikası və müalicəsi. Endoskopik, rekonstruktiv, andrologik və uşaq uroloji cərrahi müdaxilələr.',
    'hero_chip_1'      => 'Uşaq urologiyası',
    'hero_chip_2'      => 'Endoskopik cərrahiyyə',

    'about_tag'        => 'Haqqımda',
    'about_title'      => 'Dr. Xaliq Rzayev — Uroloq-Androloq',
    'about_body'       => "Dr. Xaliq Rzayev böyüklərdə və uşaqlarda uroloji xəstəliklərin müayinəsi, diaqnostikası və müalicəsi üzrə fəaliyyət göstərən uroloq-androloqdur.\n\nRusiyanın Stavropol Dövlət Tibb Universitetində ali tibb təhsili almış, daha sonra urologiya-andrologiya ixtisası üzrə klinik ordinatura keçmişdir.\n\nPeşəkar fəaliyyətində böyrək və sidik yollarının daş xəstəlikləri, kişi reproduktiv sağlamlığı, prostat və xaya xəstəlikləri, uşaqlarda anadangəlmə uroloji patologiyalar, endoskopik və rekonstruktiv cərrahi müdaxilələr əsas istiqamətlərdəndir.\n\nYerli və beynəlxalq tibbi konfransların, simpoziumların və ixtisasartırma proqramlarının iştirakçısıdır. Urologiya sahəsində elmi məqalələrin müəllifi və müxtəlif elmi tədbirlərdə məruzəçidir.",
    'about_tags'       => "Daş xəstəlikləri\nKişi reproduktiv sağlamlığı\nProstat və xaya\nUşaq urologiyası\nEndoskopik cərrahiyyə\nRekonstruktiv cərrahiyyə",
    'award_text'       => 'Vətən Müharibəsində yaralanan hərbi qulluqçulara könüllü tibbi yardım göstərilməsində iştirakına görə fəxri fərmanla təltif olunmuşdur.',

    'diag_tag'         => 'Müayinə və diaqnostika',
    'diag_title'       => 'Hansı şikayətlərlə müraciət etmək olar?',
    'diag_sub'         => 'Böyüklər və uşaqlar üçün ayrıca yanaşma.',
    'diag_adult_title' => 'Böyüklərdə',
    'diag_adult_sub'   => 'Kişi və qadınlar',
    'diag_child_title' => 'Uşaqlarda',
    'diag_child_sub'   => 'Yenidoğulmuşdan yeniyetməyə qədər',

    'surg_tag'         => 'Cərrahi müalicə',
    'surg_title'       => 'Əməliyyatlar',
    'surg_sub'         => 'Endoskopik, rekonstruktiv, andrologik və uşaq uroloji cərrahi müdaxilələr.',
    'surg_adult_title' => 'Böyüklərdə uroloji əməliyyatlar',
    'surg_child_title' => 'Uşaq uroloji əməliyyatları',

    'edu_tag'          => 'Təhsil',
    'edu_title'        => 'Təhsil',

    'cta_title'        => 'Qəbula yazılın',
    'cta_text'         => 'Zəng edin, WhatsApp-a yazın və ya formu doldurun — uyğun vaxtı birlikdə seçək.',
    'form_title'       => 'Onlayn müraciət',
    'form_ok'          => 'Müraciətiniz qəbul olundu. Tezliklə sizinlə əlaqə saxlanılacaq.',

    'footer_text'      => 'Uroloq-Androloq',
];

/* ---------------- Əlaqə ---------------- */
$CONTACT = [
    'phone'     => '+994 55 622 54 53',
    'phone_raw' => '+994556225453',
    'whatsapp'  => '994556225453',
    'email'     => '',
    'address'   => '',
    'hours'     => '',
    'map'       => '',     // Google Maps embed src
    'instagram' => '',
    'facebook'  => '',
    'youtube'   => '',
    'tiktok'    => '',
];

/* ---------------- Şəkillər (assets/img daxilində) ---------------- */
$IMAGES = [
    'photo_hero'  => 'doctor-portrait.jpg',
    'photo_about' => 'doctor.jpg',
    'og_image'    => 'doctor-portrait.jpg',
];

/* ---------------- Qısa kartlar (hero altında) ---------------- */
$HIGHLIGHTS = [
    ['icon' => '🔬', 'title' => 'Diaqnostika',   'descr' => 'Şikayətlərin dəqiq araşdırılması'],
    ['icon' => '💎', 'title' => 'Daş xəstəliyi', 'descr' => 'URS, PCNL, endoskopik müalicə'],
    ['icon' => '🧸', 'title' => 'Uşaqlar',       'descr' => 'Anadangəlmə patologiyalar'],
    ['icon' => '♂',  'title' => 'Andrologiya',   'descr' => 'Kişi reproduktiv sağlamlığı'],
];

/* ---------------- Xidmət siyahıları ---------------- */
/* Qruplar: diag_adult, diag_child, surg_adult, surg_child */
$SERVICE_GROUPS = [
    'diag_adult' => 'Müayinə — böyüklər',
    'diag_child' => 'Müayinə — uşaqlar',
    'surg_adult' => 'Əməliyyatlar — böyüklər',
    'surg_child' => 'Əməliyyatlar — uşaqlar',
];

$SERVICES = [
    'diag_adult' => [
        'Kişi və qadınlarda uroloji müayinə',
        'Böyrək, sidik axarları və sidik kisəsi xəstəliklərinin diaqnostikası',
        'Sidik ifrazında ağrı, yanma və çətinliyin araşdırılması',
        'Tez-tez sidiyə getmə və sidik qaçırma',
        'Sidikdə qan (hematuriya)',
        'Təkrarlanan sidik yolu infeksiyaları',
        'Böyrək və sidik yolları daşları',
        'Prostat vəzi xəstəlikləri və prostat böyüməsi',
        'Xaya ağrısı, şişkinlik və xaya xəstəlikləri',
        'Varikosel və hidrosel',
        'Uretra darlığı',
        'Sidik kisəsi və sidik yollarında törəmələr',
        'Kişi sonsuzluğu və spermoqrammanın qiymətləndirilməsi',
        'Erektil disfunksiya və erkən boşalma',
        'Kişilərdə hormonal və cinsi sağlamlıq problemləri',
        'HPV və digər cinsi yolla keçən infeksiyalar',
        'Əməliyyatöncəsi və əməliyyatsonrası uroloji nəzarət',
    ],
    'diag_child' => [
        'Uşaqlarda ümumi uroloji müayinə',
        'Enməmiş xaya (kriptorxizm)',
        'Xayanın yerində görünməməsi',
        'Xaya ağrısı və xaya burulması şübhəsi',
        'Hidrosel və qasıq yırtığı',
        'Hipospadiya və anadangəlmə genital anomaliyalar',
        'Xarici cinsiyyət orqanlarının inkişaf problemləri',
        'Penis və xayaların inkişafının qiymətləndirilməsi',
        'Fimoz və parafimoz',
        'Sünnətöncəsi müayinə və sünnət sonrası problemlər',
        'Sidik ifrazında ağrı və narahatlıq',
        'Tez-tez sidiyə getmə və sidik qaçırma',
        'Gecə sidik qaçırma (enurez)',
        'Təkrarlanan sidik yolu infeksiyaları',
        'Böyrək və sidik yollarının anadangəlmə xəstəlikləri',
        'Vezikoureteral reflüks və sidik axını pozğunluqları',
        'Uşaqlarda böyrək və sidik yolları daşları',
        'Genital nahiyə travmaları',
    ],
    'surg_adult' => [
        'Ureteroskopiya (URS)',
        'Böyrək və sidik yolları daşlarının endoskopik müalicəsi',
        'Perkutan nefrolitotomiya (PCNL)',
        'Prostatın transuretral rezeksiyası (TURP)',
        'Sidik kisəsi törəmələrinin transuretral rezeksiyası (TURBT)',
        'Sistoskopiya',
        'Varikoselektomiya',
        'Uretroplastika',
        'Rekonstruktiv uroloji əməliyyatlar',
    ],
    'surg_child' => [
        'Uşaq sünnəti (sirkumsiziya)',
        'Fimoz və parafimozun cərrahi müalicəsi',
        'Gizli/batmış penis əməliyyatı',
        'Penoskrotal webbing-in cərrahi korreksiyası',
        'V–Y skrotoplastika',
        'Meatotomiya və meatoplastika',
        'Hipospadiyanın cərrahi korreksiyası',
        'Uretroplastika',
        'Enməmiş xaya əməliyyatı (orxiopeksiya)',
        'İkitərəfli kriptorxizmin cərrahi müalicəsi',
        'Qasıq kanalının eksplorasiyası',
        'Xaya burulması zamanı detorsiya və orxiopeksiya',
        'Hidroselektomiya',
        'Qasıq yırtığının cərrahi müalicəsi',
        'Epididimal və paratestikulyar kistaların çıxarılması',
        'Uşaq varikoselektomiyası',
        'Ureteroskopiya və daşların endoskopik müalicəsi',
        'Sistoskopiya',
        'Sidik axarı daşlarının müalicəsi',
        'Sidik kisəsi daşlarının endoskopik çıxarılması',
        'Vezikoureteral reflüksün cərrahi və endoskopik müalicəsi',
        'Ureteroneosistostomiya',
        'Hidroureteronefroz zamanı rekonstruktiv əməliyyatlar',
        'UPJ obstruksiyası zamanı pieloplastika',
        'Sidik yollarının anadangəlmə patologiyalarının cərrahi korreksiyası',
        'Penis travmalarında plastik-bərpa əməliyyatları',
        'Sünnət sonrası fəsadların cərrahi korreksiyası',
        'Xaya və xayaətrafı törəmələrin cərrahi müalicəsi',
    ],
];

/* ---------------- Təhsil ---------------- */
$EDUCATION = [
    ['years' => '2008–2015', 'title' => 'Stavropol Dövlət Tibb Universiteti', 'descr' => "Rusiya Federasiyası\nFakültə: Müalicə işi", 'label' => '🎓 Ali tibb təhsili'],
    ['years' => '2015–2017', 'title' => 'Stavropol Dövlət Tibb Universiteti', 'descr' => "Rusiya Federasiyası\nİxtisas: Urologiya-Andrologiya", 'label' => '🩺 Klinik ordinatura'],
];

/* ---------------- SEO ---------------- */
$SEO = [
    'home' => [
        'title' => 'Dr. Xaliq Rzayev — Uroloq-Androloq | Böyüklər və uşaqlar üçün urologiya',
        'descr' => 'Dr. Xaliq Rzayev — böyüklər və uşaqlar üçün uroloji müayinə, diaqnostika və cərrahi müalicə. Endoskopik, rekonstruktiv, andrologik və uşaq uroloji əməliyyatlar. ☎ +994 55 622 54 53',
        'robots' => 'index,follow',
    ],
];

