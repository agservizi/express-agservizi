<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Piani');
$isLoggedIn = isset($currentUser) && $currentUser !== null;
$loginUrl = $isLoggedIn ? 'index.php?page=dashboard' : 'index.php?page=login';
$loginLabel = $isLoggedIn ? 'Area personale' : 'Accedi';
$landingUrl = 'index.php?page=landing&public=1';
$currentPage = 'piani';
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'Confronto piani: Start, Start Plus, Core. Scopri costi e funzionalità per il tuo punto vendita.';
$canonical = $baseUrl . '/index.php?page=piani';
$ogImage = $baseUrl . '/assets/img/logo-collapsed.svg';
$seoConfig = $GLOBALS['config']['seo'] ?? [];
$seoGoogleVerification = $seoConfig['google_site_verification'] ?? null;
$seoBingVerification = $seoConfig['bing_site_verification'] ?? null;
$seoGa4Id = $seoConfig['ga4_id'] ?? null;
$seoGtmId = $seoConfig['gtm_id'] ?? null;
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $pageTitle,
    'url' => $canonical,
    'description' => $metaDescription,
    'inLanguage' => 'it-IT',
];
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="format-detection" content="telephone=no">
    <?php if (!empty($seoGoogleVerification)): ?>
        <meta name="google-site-verification" content="<?= htmlspecialchars($seoGoogleVerification) ?>">
    <?php endif; ?>
    <?php if (!empty($seoBingVerification)): ?>
        <meta name="msvalidate.01" content="<?= htmlspecialchars($seoBingVerification) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <link rel="alternate" href="<?= htmlspecialchars($canonical) ?>" hreflang="it-IT">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= htmlspecialchars($appName) ?>">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="theme-color" content="#2563eb">
    <link rel="preload" href="assets/css/styles.css" as="style">
    <link rel="stylesheet" href="assets/css/styles.css">
    <?php if (!empty($seoGtmId)): ?>
        <script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0];
            var j = d.createElement(s);
            var dl = l !== 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '<?= htmlspecialchars($seoGtmId) ?>');
        </script>
    <?php elseif (!empty($seoGa4Id)): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($seoGa4Id) ?>"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($seoGa4Id) ?>');
        </script>
    <?php endif; ?>
    <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="landing-body">
    <?php if (!empty($seoGtmId)): ?>
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id=<?= htmlspecialchars($seoGtmId) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
    <?php endif; ?>
    <div class="landing-topbar">
        <div class="landing-topbar__inner">
            <div class="landing-brand">
                <span class="landing-brand__logo" aria-hidden="true">
                    <img src="assets/img/logo-collapsed.svg" alt="">
                </span>
                <span class="landing-brand__name"><?= htmlspecialchars($appName) ?></span>
            </div>
            <nav class="landing-nav" aria-label="Navigazione principale">
                <a class="landing-nav__link <?= $currentPage === 'landing' ? 'landing-nav__link--active' : '' ?>" href="<?= htmlspecialchars($landingUrl) ?>">Home</a>
                <a class="landing-nav__link <?= $currentPage === 'demo' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=demo&public=1">Demo</a>
                <a class="landing-nav__link <?= $currentPage === 'funzionalita' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=funzionalita&public=1">Funzionalità</a>
                <a class="landing-nav__link <?= $currentPage === 'vantaggi' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=vantaggi&public=1">Vantaggi</a>
                <a class="landing-nav__link <?= $currentPage === 'piani' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=piani&public=1">Piani</a>
                <a class="landing-nav__link <?= $currentPage === 'prezzi' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=prezzi&public=1">Prezzi</a>
                <a class="landing-nav__link <?= $currentPage === 'faq' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=faq&public=1">FAQ</a>
                <a class="landing-nav__link <?= $currentPage === 'contatto' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=contatto&public=1">Contatto</a>
            </nav>
            <a class="landing-btn landing-btn--ghost" href="<?= htmlspecialchars($loginUrl) ?>"><?= htmlspecialchars($loginLabel) ?></a>
        </div>
    </div>

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Scegli il piano giusto per il tuo store</p>
                <h1>Piani</h1>
                <p class="landing-subtitle">
                    Durate e limiti pensati per garantire controllo operativo e crescita graduale della rete vendita.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto&public=1">Richiedi informazioni</a>
                    <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi&public=1">Confronta i prezzi</a>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="premium-section" id="piani">
            <div class="premium-header">
                <div>
                    <p class="premium-kicker">Panoramica completa</p>
                    <h2>Piani con dettagli operativi</h2>
                    <p>Ogni piano è pensato per crescere con il tuo punto vendita. Qui trovi cosa include davvero, senza scorciatoie.</p>
                </div>
                <div class="premium-badges">
                    <span>Attivazione rapida</span>
                    <span>Assistenza dedicata</span>
                    <span>Multi-tenant pronto</span>
                </div>
            </div>

            <div class="premium-grid">
                <article class="premium-card">
                    <header class="premium-card__header">
                        <div>
                            <h3>Start</h3>
                            <p>12 mesi · max 1 cassiere</p>
                        </div>
                        <div class="premium-card__price">
                            <strong>€ 550</strong>
                            <span>€ 45,83/mese</span>
                        </div>
                    </header>
                    <div class="premium-card__body">
                        <p class="premium-card__intro">Il set essenziale per partire in modo ordinato e controllare ogni vendita.</p>
                        <ul class="premium-list">
                            <li>Dashboard con KPI base, incassi e trend giornalieri.</li>
                            <li>Magazzino SIM con stati, provider, note e storico.</li>
                            <li>Catalogo prodotti con prezzi, IVA, barcode e stock.</li>
                            <li>Clienti con anagrafica, dati fiscali e storico acquisti.</li>
                            <li>Listini e offerte base per velocizzare la vendita.</li>
                            <li>Nuova vendita guidata e stampa scontrino.</li>
                            <li>Storico vendite con filtri principali.</li>
                            <li>Guida operativa e impostazioni fondamentali.</li>
                        </ul>
                    </div>
                </article>

                <article class="premium-card premium-card--featured">
                    <header class="premium-card__header">
                        <div>
                            <h3>Start Plus</h3>
                            <p>12 mesi · max 1 cassiere</p>
                        </div>
                        <div class="premium-card__price">
                            <strong>€ 650</strong>
                            <span>€ 54,17/mese</span>
                        </div>
                    </header>
                    <div class="premium-card__body">
                        <p class="premium-card__intro">Per chi vuole misurare performance e gestire le richieste in modo evoluto.</p>
                        <ul class="premium-list">
                            <li>Tutto il piano Start incluso.</li>
                            <li>Report avanzati su vendite, margini e rotazione.</li>
                            <li>Gestione richieste supporto e tracciamento ticket.</li>
                            <li>Ordini store integrati con monitoraggio stato.</li>
                            <li>Notifiche operative per eventi chiave e priorità.</li>
                        </ul>
                    </div>
                </article>

                <article class="premium-card">
                    <header class="premium-card__header">
                        <div>
                            <h3>Core</h3>
                            <p>24 mesi · max 2 cassieri</p>
                        </div>
                        <div class="premium-card__price">
                            <strong>€ 850</strong>
                            <span>€ 35,42/mese</span>
                        </div>
                    </header>
                    <div class="premium-card__body">
                        <p class="premium-card__intro">Il piano per reti vendita attive che vogliono servizi energia e KPI evoluti.</p>
                        <ul class="premium-list">
                            <li>Tutto il piano Start Plus incluso.</li>
                            <li>Gestione contratti energia luce & gas con simulazioni.</li>
                            <li>Report KPI avanzati per canali, operatori e prodotti.</li>
                            <li>Supporto prioritario con tempi di risposta accelerati.</li>
                            <li>Fino a 2 cassieri attivi con controllo accessi.</li>
                        </ul>
                    </div>
                </article>

                <article class="premium-card">
                    <header class="premium-card__header">
                        <div>
                            <h3>Business</h3>
                            <p>36 mesi · max 4 cassieri</p>
                        </div>
                        <div class="premium-card__price">
                            <strong>€ 1200</strong>
                            <span>€ 33,33/mese</span>
                        </div>
                    </header>
                    <div class="premium-card__body">
                        <p class="premium-card__intro">Per chi vuole personalizzazione, SLA dedicato e onboarding completo.</p>
                        <ul class="premium-list">
                            <li>Tutto il piano Core incluso.</li>
                            <li>Report personalizzati con dashboard su misura.</li>
                            <li>SLA dedicato e monitoraggio livelli di servizio.</li>
                            <li>Onboarding operativo e training del team.</li>
                            <li>Integrazioni avanzate con sistemi esterni.</li>
                            <li>Fino a 4 cassieri attivi con ruoli e permessi.</li>
                        </ul>
                    </div>
                </article>
            </div>
        </section>

        <section class="premium-section premium-section--cta">
            <div class="premium-cta">
                <div>
                    <h2>Hai bisogno di una consulenza guidata?</h2>
                    <p>Raccontaci volumi, sedi e servizi: ti prepariamo una proposta precisa, senza margini nascosti.</p>
                </div>
                <div class="premium-cta__actions">
                    <a class="landing-btn landing-btn--primary" href="index.php?page=contatto&public=1">Parla con un consulente</a>
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=prezzi&public=1">Vai ai prezzi</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div>
            <strong><?= htmlspecialchars($appName) ?></strong>
            <span>· Gestionale multi-tenant per telecomunicazioni e retail.</span>
        </div>
        <span>Sviluppato e distribuito da AG SERVIZI P.Iva 08442881218</span>
    </footer>
</body>
</html>
