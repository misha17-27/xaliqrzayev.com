<?php
/*
 * Saytın ilkin məzmunu. install.php bunu bazaya köçürür;
 * baza yoxdursa sayt birbaşa bu dəyərlərlə işləyir.
 */

/* ---------------- Mətnlər ---------------- */
$TEXTS = [
    'site_name'        => 'Dr. Xaliq Rzayev',
    'site_role'        => 'Uroloq · Androloq',

    'hero_badge'       => 'Uroloq-Androloq',
    'hero_title_1'     => 'Dr. Xaliq',
    'hero_title_2'     => 'Rzayev',
    'hero_lead'        => 'Böyüklər və uşaqlar üçün uroloji müayinə, diaqnostika və cərrahi müalicə',
    'hero_desc'        => 'Uroloji və androloji xəstəliklərin müayinəsi, diaqnostikası və müalicəsi. Endoskopik, rekonstruktiv, andrologik və uşaq uroloji cərrahi müdaxilələr.',
    'hero_cap_label'   => 'İxtisas',
    'hero_cap_text'    => 'Urologiya & Andrologiya',

    'about_tag'        => 'Haqqımda',
    'about_title'      => 'Peşəkar yanaşma, hər yaşda',
    'about_quote'      => 'Böyüklərdə və uşaqlarda uroloji xəstəliklərin müayinəsi, androloji diaqnostikası və müalicəsi üzrə fəaliyyət göstərən uroloq-androloq.',
    'about_body'       => "Rusiyanın Stavropol Dövlət Tibb Universitetində ali tibb təhsili almış, daha sonra urologiya-andrologiya ixtisası üzrə klinik ordinatura keçmişdir.\n\nPeşəkar fəaliyyətində böyrək və sidik yollarının daş xəstəlikləri, kişi reproduktiv sağlamlığı, prostat və xaya xəstəlikləri, uşaqlarda anadangəlmə uroloji patologiyalar, endoskopik və rekonstruktiv cərrahi müdaxilələr əsas istiqamətlərdəndir.\n\nYerli və beynəlxalq tibbi konfransların, simpoziumların və ixtisasartırma proqramlarının iştirakçısıdır. Urologiya sahəsində elmi məqalələrin müəllifi və müxtəlif elmi tədbirlərdə məruzəçidir.",
    'about_tags'       => "Böyrək və sidik yollarının daş xəstəlikləri\nKişi reproduktiv sağlamlığı\nProstat və xaya xəstəlikləri\nUşaqlarda anadangəlmə uroloji patologiyalar",
    'award_text'       => 'Vətən Müharibəsində yaralanan hərbi qulluqçulara könüllü tibbi yardım göstərilməsində iştirakına görə fəxri fərmanla təltif olunmuşdur.',

    'diag_tag'         => 'Müayinə və diaqnostika',
    'diag_title'       => 'Uroloji müayinə',
    'diag_adult_title' => 'Böyüklər',
    'diag_child_title' => 'Uşaqlar',

    'surg_tag'         => 'Cərrahi müalicə',
    'surg_title'       => 'Əməliyyatlar',
    'surg_aside_title' => 'Endoskopik, rekonstruktiv və uşaq uroloji cərrahiyyəsi',
    'surg_sub'         => 'Minimal invaziv texnikalar və anadangəlmə patologiyaların korreksiyası — böyüklər və uşaqlar üçün.',
    'surg_adult_title' => 'Böyüklərdə',
    'surg_open_title'  => 'Açıq əməliyyatlar',
    'surg_endo_title'  => 'Endoskopik əməliyyatlar',
    'surg_child_title' => 'Uşaqlarda',

    'edu_tag'          => 'Təhsil',
    'edu_title'        => 'Akademik yol',

    'cta_tag'          => 'Əlaqə',
    'cta_title'        => 'Qəbula yazılın',
    'cta_text'         => 'Zəng edin, WhatsApp vasitəsilə yazın və ya formu doldurun — sizin üçün uyğun vaxtı birlikdə təyin edək.',
    'form_title'       => 'Onlayn müraciət',
    'form_ok'          => 'Müraciətiniz qəbul olundu. Tezliklə sizinlə əlaqə saxlanılacaq.',

    'footer_text'      => 'Uroloq-Androloq',
];

/* ---------------- Əlaqə ---------------- */
$CONTACT = [
    'phone'     => '+994 50 622 54 53',
    'phone_raw' => '+994506225453',
    'whatsapp'  => '994506225453',
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
    'og_image'    => 'doctor-portrait.jpg',
];

/* ---------------- Zolaq (birinci ekranın altında) ---------------- */
$HIGHLIGHTS = [
    ['icon' => '', 'title' => 'Endoskopik',       'descr' => 'URS, PCNL, TURP, TURBT'],
    ['icon' => '', 'title' => 'Rekonstruktiv',    'descr' => 'Uretroplastika, pieloplastika'],
    ['icon' => '', 'title' => 'Andrologiya',      'descr' => 'Kişi reproduktiv sağlamlığı'],
    ['icon' => '', 'title' => 'Uşaq urologiyası', 'descr' => 'Anadangəlmə patologiyalar'],
];

/* ---------------- Xidmət siyahıları ---------------- */
/* Qruplar: diag_adult, diag_child, surg_open + surg_endo (böyüklər), surg_child */
$SERVICE_GROUPS = [
    'diag_adult' => 'Müayinə — böyüklər',
    'diag_child' => 'Müayinə — uşaqlar',
    'surg_open'  => 'Əməliyyatlar — böyüklər, açıq',
    'surg_endo'  => 'Əməliyyatlar — böyüklər, endoskopik',
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
    'surg_open' => [
        'Varikoselektomiya',
        'Hidroselektomiya',
        'Orxiopeksiya',
        'Orxiektomiya',
        'Epididimektomiya',
        'Spermatoselektomiya',
        'Vazektomiya',
        'Uretroplastika',
        'Hipospadiyanın cərrahi korreksiyası',
        'Gizli penisin cərrahi korreksiyası',
        'Penoskrotal torun korreksiyası',
        'Testis biopsiyası',
        'Testis protezinin implantasiyası',
        'Pieloplastika',
        'Nefrektomiya',
        'Qismən nefrektomiya',
        'Ureteroneosistostomiya',
        'Nefrostomanın qoyulması',
        'Sistostomiya (sidik kisəsinin daşının çıxarılması)',
    ],
    'surg_endo' => [
        'Diaqnostik sistoskopiya',
        'Uretroskopiya',
        'Ureteroskopiya',
        'Lazer ureterolitotripsiya (sidik kanalı daşının lazerlə ləğvi)',
        'Perkutan nefrolitotomiya (PCNL)',
        'Transuretral prostat rezeksiyası (TURP)',
        'Sidik kisəsi şişinin transuretral rezeksiyası (TURBT)',
        'Sistolitotripsiya (sidik kisəsi daşının lazerlə ləğvi)',
        'Uretranın daxili optik uretrotomiyası (DVIU)',
        'Double-J stentinin qoyulması və çıxarılması',
        'Ureterin endoskopik daş ekstraksiyası',
        'Vezikoureteral reflüksün endoskopik korreksiyası',
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
    ['years' => '2008–2015', 'title' => 'Stavropol Dövlət Tibb Universiteti', 'descr' => "Rusiya Federasiyası\nFakültə: Müalicə işi", 'label' => 'Ali tibb təhsili'],
    ['years' => '2015–2017', 'title' => 'Stavropol Dövlət Tibb Universiteti', 'descr' => "Rusiya Federasiyası\nİxtisas: Urologiya-Andrologiya", 'label' => 'Klinik ordinatura'],
];

/* ---------------- SEO ---------------- */
$SEO = [
    'home' => [
        'title' => 'Dr. Xaliq Rzayev — uroloq-androloq, uşaq urologiyası',
        'descr' => 'Uroloq-androloq Dr. Xaliq Rzayev: böyüklər və uşaqlar üçün müayinə, diaqnostika, endoskopik və açıq uroloji əməliyyatlar. ☎ +994 50 622 54 53',
        'robots' => 'index,follow',
    ],
];

