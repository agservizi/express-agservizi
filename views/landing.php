<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Gestionale multi-tenant');
$loginUrl = 'index.php?page=login';
$currentPage = 'landing';
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$baseUrl = rtrim($baseUrl, '/');
$canonicalUrl = $baseUrl . '/';
$metaDescription = 'Gestionale per negozi di telefonia: SIM, vendite, stock e report in un solo cruscotto. Demo rapida e onboarding guidato.';
$ogImage = $baseUrl . '/assets/img/logo-collapsed.svg';
$logoUrl = $baseUrl . '/assets/img/logo-collapsed.svg';
$structuredData = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $appName,
        'url' => $canonicalUrl,
        'logo' => $logoUrl,
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $appName,
        'url' => $canonicalUrl,
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $pageTitle,
        'url' => $canonicalUrl,
        'description' => $metaDescription,
        'keywords' => 'gestionale telefonia, gestionale per negozi di telefonia, gestionale SIM, software punto vendita telefonia',
        'inLanguage' => 'it-IT',
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => $appName,
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => $metaDescription,
        'keywords' => 'gestionale telefonia, gestionale per negozi di telefonia, gestionale SIM, software punto vendita telefonia',
        'url' => $canonicalUrl,
    ],
];
$demoSlides = [
    'Screenshot 2026-01-31 alle 18.27.35.png',
    'Screenshot 2026-01-31 alle 18.27.42.png',
    'Screenshot 2026-01-31 alle 18.27.56.png',
    'Screenshot 2026-01-31 alle 18.28.03.png',
];
$faqItems = [
    [
        'question' => 'È adatto anche a un singolo punto vendita?',
        'answer' => 'Sì. Il piano Start è pensato per singoli store e può crescere con il tuo business.',
    ],
    [
        'question' => 'Quanto tempo serve per partire?',
        'answer' => 'Con il setup guidato e i template preconfigurati si parte in pochi giorni.',
    ],
    [
        'question' => 'Supportate l’energia luce & gas?',
        'answer' => 'Sì, dal piano Core con gestione offerte e contratti energia.',
    ],
    [
        'question' => 'È possibile richiedere una demo?',
        'answer' => 'Certo. Compila il modulo contatti per ricevere una demo guidata.',
    ],
];
$faqStructuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(static function (array $item): array {
        return [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['answer'],
            ],
        ];
    }, $faqItems),
];
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="keywords" content="gestionale telefonia, gestionale per negozi di telefonia, gestionale SIM, software punto vendita telefonia">
    <meta name="subject" content="Gestionale telefonia">
    <meta name="category" content="BusinessApplication">
    <meta name="application-name" content="<?= htmlspecialchars($appName) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="format-detection" content="telephone=no">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <link rel="alternate" href="<?= htmlspecialchars($canonicalUrl) ?>" hreflang="it-IT">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= htmlspecialchars($appName) ?>">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="theme-color" content="#2563eb">
    <link rel="preload" href="assets/css/styles.css" as="style">
    <link rel="stylesheet" href="assets/css/styles.css">
    <?php foreach ($structuredData as $entry): ?>
        <script type="application/ld+json"><?= json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endforeach; ?>
    <script type="application/ld+json"><?= json_encode($faqStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="landing-body">
    <div class="landing-topbar">
        <div class="landing-topbar__inner">
            <div class="landing-brand">
                <span class="landing-brand__logo" aria-hidden="true">
                    <img src="assets/img/logo-collapsed.svg" alt="Logo <?= htmlspecialchars($appName) ?>">
                </span>
                <span class="landing-brand__name"><?= htmlspecialchars($appName) ?></span>
            </div>
            <nav class="landing-nav" aria-label="Navigazione principale">
                <a class="landing-nav__link <?= $currentPage === 'landing' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=landing">Home</a>
                <a class="landing-nav__link <?= $currentPage === 'demo' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=demo">Demo</a>
                <a class="landing-nav__link <?= $currentPage === 'funzionalita' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=funzionalita">Funzionalità</a>
                <a class="landing-nav__link <?= $currentPage === 'vantaggi' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=vantaggi">Vantaggi</a>
                <a class="landing-nav__link <?= $currentPage === 'piani' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=piani">Piani</a>
                <a class="landing-nav__link <?= $currentPage === 'prezzi' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=prezzi">Prezzi</a>
                <a class="landing-nav__link <?= $currentPage === 'faq' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=faq">FAQ</a>
                <a class="landing-nav__link <?= $currentPage === 'contatto' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=contatto">Contatto</a>
            </nav>
            <a class="landing-btn landing-btn--ghost" href="<?= htmlspecialchars($loginUrl) ?>">Accedi</a>
        </div>
    </div>

    <div class="landing-sticky-cta" role="region" aria-label="CTA rapida">
        <div class="landing-sticky-cta__inner">
            <span>Pronto a vedere <?= htmlspecialchars($appName) ?> in azione?</span>
            <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Prenota una demo</a>
        </div>
    </div>

    <header class="landing-hero">

        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Gestionale retail per negozi di telefonia</p>
                <h1>Più vendite, meno caos in cassa.</h1>
                <p class="landing-subtitle">
                    <?= htmlspecialchars($appName) ?> centralizza SIM, stock, vendite e report in un'unica dashboard.
                    Usato da store e reti retail che vogliono velocizzare le operazioni e avere margini sempre visibili.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--primary" href="#contatto">Prenota una demo</a>
                    <a class="landing-btn landing-btn--secondary" href="#demo">Guarda la demo</a>
                </div>
                <div class="landing-metrics">
                    <div class="landing-metric">
                        <span class="landing-metric__value">24/7</span>
                        <span class="landing-metric__label">Operatività e supporto interno</span>
                    </div>
                    <div class="landing-metric">
                        <span class="landing-metric__value">Multi-tenant</span>
                        <span class="landing-metric__label">Controllo separato per ogni punto vendita</span>
                    </div>
                    <div class="landing-metric">
                        <span class="landing-metric__value">+40 KPI</span>
                        <span class="landing-metric__label">Dashboard avanzata e report</span>
                    </div>
                </div>
            </div>

            <div class="landing-hero__card">
                <div class="landing-card">
                    <h3>Perché sceglierlo</h3>
                    <ul>
                        <li>Gestione completa di SIM, stock, prodotti e vendite.</li>
                        <li>Campagne sconto, listini e promozioni centralizzate.</li>
                        <li>Fatturato, margini e performance sempre tracciati.</li>
                        <li>Ruoli operativi, MFA e audit per sicurezza totale.</li>
                        <li>Configurazione scontrino personalizzabile per ogni tenant.</li>
                    </ul>
                    <div class="landing-card__cta">
                        <span>Pronto a far crescere il tuo punto vendita?</span>
                        <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Vai al login</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-proof">
            <div class="landing-proof__inner">
                <div class="landing-proof__badge">Piattaforma in crescita</div>
                <div class="landing-proof__grid">
                    <div>
                        <strong>+40 KPI</strong>
                        <span>Report avanzati e alert operativi</span>
                    </div>
                    <div>
                        <strong>Setup rapido</strong>
                        <span>Onboarding guidato in poche ore</span>
                    </div>
                    <div>
                        <strong>Multi-sede</strong>
                        <span>Controllo separato per ogni store</span>
                    </div>
                    <div>
                        <strong>Supporto dedicato</strong>
                        <span>Affiancamento per rete vendita</span>
                    </div>
                </div>
            </div>
        </section>


        <section id="demo" class="landing-section landing-demo">
            <div class="landing-demo__grid">
                <div>
                    <div class="landing-section__header">
                        <h2>Guarda come funziona</h2>
                        <p>Una demo guidata di 90 secondi mostra vendita, magazzino e report in un solo flusso.</p>
                    </div>
                    <ul class="landing-demo__list">
                        <li>Flusso vendita rapido con stampa scontrino.</li>
                        <li>Gestione SIM e scorte con alert automatici.</li>
                        <li>Dashboard KPI per ogni punto vendita.</li>
                    </ul>
                    <a class="landing-btn landing-btn--primary" href="#contatto">Richiedi demo personalizzata</a>
                </div>
                <div class="landing-demo__frame" aria-label="Anteprima demo piattaforma">
                    <div class="landing-demo__slider" data-demo-slider>
                        <div class="landing-demo__slides">
                            <?php foreach ($demoSlides as $index => $slide): ?>
                                <figure class="landing-demo__slide" data-demo-slide <?= $index === 0 ? 'data-active="true"' : '' ?>">
                                    <img src="assets/img/<?= rawurlencode($slide) ?>" alt="Anteprima gestionale <?= $index + 1 ?>" loading="lazy">
                                </figure>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="landing-demo__nav landing-demo__nav--prev" data-demo-prev aria-label="Precedente">‹</button>
                        <button type="button" class="landing-demo__nav landing-demo__nav--next" data-demo-next aria-label="Successivo">›</button>
                        <div class="landing-demo__dots" role="tablist" aria-label="Selezione slide">
                            <?php foreach ($demoSlides as $index => $_): ?>
                                <button type="button" class="landing-demo__dot" data-demo-dot="<?= $index ?>" aria-label="Slide <?= $index + 1 ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section id="funzionalita" class="landing-section">
            <div class="landing-section__header">
                <h2>Un gestionale completo, pronto alla vendita</h2>
                <p>Progettato per scalare: dal singolo store alla rete multi-sede.</p>
            </div>
            <div class="landing-grid">
                <article class="landing-feature">
                    <h3>Vendite e cassa veloce</h3>
                    <p>Flusso di vendita rapido, scontrini termici, annulli e resi tracciati. Ideale per SIM, accessori e servizi digitali.</p>
                </article>
                <article class="landing-feature">
                    <h3>Magazzino & soglie</h3>
                    <p>Monitoraggio SIM e hardware con soglie minime, suggerimenti di riordino e alert automatici.</p>
                </article>
                <article class="landing-feature">
                    <h3>Campagne sconto</h3>
                    <p>Gestisci promozioni fisse o percentuali, attivazioni automatiche e monitoraggio performance.</p>
                </article>
                <article class="landing-feature">
                    <h3>Gestori telefonia</h3>
                    <p>Catalogo gestori, note operative, offerte e parametri aggiornabili dal pannello impostazioni.</p>
                </article>
                <article class="landing-feature">
                    <h3>Energia luce & gas</h3>
                    <p>Configurazione gestori energia e import offerte, con gettoni e note per i consulenti.</p>
                </article>
                <article class="landing-feature">
                    <h3>Sicurezza e ruoli</h3>
                    <p>Ruoli, MFA e audit log integrati per un controllo totale di utenti e operazioni.</p>
                </article>
            </div>
        </section>

        <section id="vantaggi" class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Una piattaforma che accelera la vendita</h2>
                <p>Ogni tenant dispone delle proprie impostazioni fiscali, scontrino personalizzato e dashboard dedicata.</p>
            </div>
            <div class="landing-columns">
                <div>
                    <h3>Per i responsabili di rete</h3>
                    <ul>
                        <li>Standardizzazione dei processi operativi.</li>
                        <li>Controllo realtime su stock e vendite.</li>
                        <li>Rollout rapido a nuovi punti vendita.</li>
                    </ul>
                </div>
                <div>
                    <h3>Per gli store</h3>
                    <ul>
                        <li>Interfaccia guidata e intuitiva.</li>
                        <li>Report automatici e promozioni semplici.</li>
                        <li>Accesso sicuro e configurazioni personalizzate.</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="landing-section landing-before-after">
            <div class="landing-section__header">
                <h2>Prima / Dopo</h2>
                <p>Risultati misurabili dopo l’adozione della piattaforma.</p>
            </div>
            <div class="landing-before-after__grid">
                <div class="landing-before-after__card">
                    <h3>Prima</h3>
                    <ul>
                        <li>Vendite disperse tra fogli e app.</li>
                        <li>Riordini manuali e stock impreciso.</li>
                        <li>Report lenti e poco affidabili.</li>
                    </ul>
                </div>
                <div class="landing-before-after__card landing-before-after__card--highlight">
                    <h3>Dopo</h3>
                    <ul>
                        <li>-35% tempo in cassa grazie a flussi guidati.</li>
                        <li>+18% rotazione SIM con alert automatici.</li>
                        <li>Report giornalieri pronti in 1 click.</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="piani" class="landing-section">
            <div class="landing-section__header">
                <h2>Piani pensati per scalare</h2>
                <p>Confronta velocemente le opzioni e scopri i dettagli completi nella pagina Prezzi.</p>
            </div>
            <div class="landing-pricing__grid landing-pricing__grid--compact">
                <article class="landing-pricing__card">
                    <header>
                        <h3>Start</h3>
                        <p>12 mesi · max 1 cassiere</p>
                        <span class="landing-pricing__price">€ 550</span>
                        <span class="muted">€ 45,83 / mese</span>
                    </header>
                    <ul>
                        <li>Dashboard, SIM, prodotti e vendite.</li>
                        <li>Guida operativa completa.</li>
                    </ul>
                </article>
                <article class="landing-pricing__card landing-pricing__card--highlight">
                    <header>
                        <h3>Start Plus</h3>
                        <p>12 mesi · max 1 cassiere</p>
                        <span class="landing-pricing__price">€ 650</span>
                        <span class="muted">€ 54,17 / mese</span>
                    </header>
                    <ul>
                        <li>Tutto del Start.</li>
                        <li>Report e richieste supporto.</li>
                    </ul>
                </article>
                <article class="landing-pricing__card">
                    <header>
                        <h3>Core</h3>
                        <p>24 mesi · max 2 cassieri</p>
                        <span class="landing-pricing__price">€ 850</span>
                        <span class="muted">€ 35,42 / mese</span>
                    </header>
                    <ul>
                        <li>Contratti energia e KPI avanzati.</li>
                        <li>Supporto prioritario.</li>
                    </ul>
                </article>
            </div>
            <div class="landing-pricing__table landing-pricing__table--compare" aria-label="Confronto piani">
                <table>
                    <thead>
                        <tr>
                            <th>Funzionalità</th>
                            <th>Start</th>
                            <th>Start Plus</th>
                            <th>Core</th>
                            <th>Business</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Dashboard e KPI</td>
                            <td>✔</td>
                            <td>✔</td>
                            <td>✔</td>
                            <td>✔</td>
                        </tr>
                        <tr>
                            <td>Magazzino SIM e prodotti</td>
                            <td>✔</td>
                            <td>✔</td>
                            <td>✔</td>
                            <td>✔</td>
                        </tr>
                        <tr>
                            <td>Report avanzati</td>
                            <td>—</td>
                            <td>✔</td>
                            <td>✔</td>
                            <td>✔</td>
                        </tr>
                        <tr>
                            <td>Supporto clienti</td>
                            <td>—</td>
                            <td>✔</td>
                            <td>✔</td>
                            <td>✔</td>
                        </tr>
                        <tr>
                            <td>Contratti energia</td>
                            <td>—</td>
                            <td>—</td>
                            <td>✔</td>
                            <td>✔</td>
                        </tr>
                        <tr>
                            <td>Report personalizzati</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>✔</td>
                        </tr>
                        <tr>
                            <td>Prezzo</td>
                            <td>€ 550 · € 45,83/mese</td>
                            <td>€ 650 · € 54,17/mese</td>
                            <td>€ 850 · € 35,42/mese</td>
                            <td>€ 1200 · € 33,33/mese</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="landing-section__cta">
                <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi">Vai ai prezzi completi</a>
            </div>
        </section>

        <section class="landing-section landing-lead">
            <div class="landing-lead__content">
                <h2>Ricevi il kit gratuito “Avvio Store”</h2>
                <p>Checklist operativa + template report per partire subito con margini sotto controllo.</p>
            </div>
            <a class="landing-btn landing-btn--secondary" href="assets/kits/avvio-store-kit.pdf" download>Scarica il kit</a>
        </section>

        <section class="landing-section landing-steps">
            <div class="landing-section__header">
                <h2>Attivazione in 3 step</h2>
                <p>Onboarding guidato per partire in tempi rapidi con i tuoi store.</p>
            </div>
            <div class="landing-steps__grid">
                <article class="landing-step">
                    <span class="landing-step__index">1</span>
                    <h3>Setup tenant</h3>
                    <p>Configura licenze, operatori, IVA e diciture scontrino.</p>
                </article>
                <article class="landing-step">
                    <span class="landing-step__index">2</span>
                    <h3>Carica catalogo</h3>
                    <p>Importa SIM, prodotti e listini con workflow guidati.</p>
                </article>
                <article class="landing-step">
                    <span class="landing-step__index">3</span>
                    <h3>Vai in vendita</h3>
                    <p>Monitora KPI, stock e supporto clienti in tempo reale.</p>
                </article>
            </div>
        </section>

        <section id="faq" class="landing-section landing-faq">
            <div class="landing-section__header">
                <h2>FAQ</h2>
                <p>Le risposte alle domande più frequenti.</p>
            </div>
            <div class="landing-faq__grid">
                <?php foreach ($faqItems as $item): ?>
                    <article class="landing-faq__item">
                        <h3><?= htmlspecialchars($item['question']) ?></h3>
                        <p><?= htmlspecialchars($item['answer']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="landing-section landing-testimonials">
            <div class="landing-section__header">
                <h2>Risultati concreti per i punti vendita</h2>
                <p>Chi usa <?= htmlspecialchars($appName) ?> riduce i tempi di gestione e aumenta la visibilità sui margini.</p>
            </div>
            <div class="landing-testimonials__grid">
                <article class="landing-testimonial">
                    <p>“Abbiamo centralizzato 5 negozi in una settimana. Ora il riordino SIM è automatico.”</p>
                    <span class="landing-testimonial__author">Responsabile retail, Napoli</span>
                </article>
                <article class="landing-testimonial">
                    <p>“Report giornalieri chiari: sappiamo subito cosa performa meglio.”</p>
                    <span class="landing-testimonial__author">Store manager, Roma</span>
                </article>
                <article class="landing-testimonial">
                    <p>“La demo ha convinto tutto il team. Onboarding semplicissimo.”</p>
                    <span class="landing-testimonial__author">Owner, Milano</span>
                </article>
            </div>
        </section>

        <section id="contatto" class="landing-section landing-contact">
            <div class="landing-section__header">
                <h2>Richiedi informazioni rapide</h2>
                <p>Lascia email e tipo richiesta: ti rispondiamo entro 24 ore.</p>
            </div>
            <?php if ($feedback): ?>
                <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                    <p><?= htmlspecialchars((string) ($feedback['message'] ?? '')) ?></p>
                    <?php if (!empty($feedback['error'])): ?>
                        <p><?= htmlspecialchars((string) $feedback['error']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($feedback['errors']) && is_array($feedback['errors'])): ?>
                        <ul>
                            <?php foreach ($feedback['errors'] as $error): ?>
                                <li><?= htmlspecialchars((string) $error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <form method="post" class="form">
                <input type="hidden" name="action" value="landing_contact">
                <div class="form__grid">
                    <div class="form__group">
                        <label for="contact_email">Email *</label>
                        <input type="email" id="contact_email" name="contact_email" required value="<?= htmlspecialchars((string) ($oldInput['contact_email'] ?? '')) ?>">
                    </div>
                    <div class="form__group">
                        <label for="contact_request">Tipo richiesta *</label>
                        <select id="contact_request" name="contact_request" required>
                            <option value="">Seleziona</option>
                            <option value="info_piani" <?= (($oldInput['contact_request'] ?? '') === 'info_piani') ? 'selected' : '' ?>>Richiesta informazioni piani</option>
                            <option value="demo" <?= (($oldInput['contact_request'] ?? '') === 'demo') ? 'selected' : '' ?>>Richiesta demo</option>
                            <option value="contatto" <?= (($oldInput['contact_request'] ?? '') === 'contatto') ? 'selected' : '' ?>>Richiesta contatto commerciale</option>
                        </select>
                    </div>
                </div>
                <footer class="form__footer">
                    <button type="submit" class="btn btn--primary">Invia richiesta</button>
                </footer>
            </form>
        </section>

        <section class="landing-section landing-cta">
            <div class="landing-cta__content">
                <h2>Pronto a potenziare il tuo negozio?</h2>
                <p>Attiva il gestionale in pochi minuti, organizza il team con flussi guidati e fai crescere vendite e margini con dati sempre sotto controllo.</p>
            </div>
            <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi ora</a>
        </section>
    </main>

    <footer class="landing-footer">
        <div>
            <strong><?= htmlspecialchars($appName) ?></strong>
            <span>· Gestionale multi-tenant per telecomunicazioni e retail.</span>
        </div>
        <span>Sviluppato e distribuito da AG SERVIZI P.Iva 08442881218</span>
    </footer>

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "<?= htmlspecialchars($appName) ?>",
            "url": "<?= htmlspecialchars($baseUrl) ?>"
        }
        </script>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [
                <?php foreach ($faqItems as $index => $item): ?>
                {
                    "@type": "Question",
                    "name": "<?= htmlspecialchars($item['question']) ?>",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "<?= htmlspecialchars($item['answer']) ?>"
                    }
                }<?= $index < count($faqItems) - 1 ? ',' : '' ?>
                <?php endforeach; ?>
            ]
        }
        </script>

    <script>
    (function () {
        const slider = document.querySelector('[data-demo-slider]');
        if (!slider) {
            return;
        }
        const slides = Array.from(slider.querySelectorAll('[data-demo-slide]'));
        const dots = Array.from(slider.querySelectorAll('[data-demo-dot]'));
        const prevBtn = slider.querySelector('[data-demo-prev]');
        const nextBtn = slider.querySelector('[data-demo-next]');
        if (slides.length === 0) {
            return;
        }
        let index = slides.findIndex(slide => slide.dataset.active === 'true');
        if (index < 0) {
            index = 0;
            slides[0].dataset.active = 'true';
        }

        let timerId = 0;

        const update = (nextIndex) => {
            slides.forEach((slide, idx) => {
                if (idx === nextIndex) {
                    slide.dataset.active = 'true';
                } else {
                    delete slide.dataset.active;
                }
            });
            dots.forEach((dot, idx) => {
                dot.setAttribute('aria-pressed', idx === nextIndex ? 'true' : 'false');
            });
            index = nextIndex;
        };

        const go = (direction) => {
            const nextIndex = (index + direction + slides.length) % slides.length;
            update(nextIndex);
        };

        const startAutoplay = () => {
            if (timerId) {
                window.clearInterval(timerId);
            }
            timerId = window.setInterval(() => go(1), 4500);
        };

        const stopAutoplay = () => {
            if (timerId) {
                window.clearInterval(timerId);
                timerId = 0;
            }
        };

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                go(-1);
                startAutoplay();
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                go(1);
                startAutoplay();
            });
        }
        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                update(idx);
                startAutoplay();
            });
        });

        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', startAutoplay);
        startAutoplay();
    })();
    </script>
</body>
</html>
