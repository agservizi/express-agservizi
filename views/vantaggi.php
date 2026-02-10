<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Vantaggi');
$isLoggedIn = isset($currentUser) && $currentUser !== null;
$loginUrl = $isLoggedIn ? 'index.php?page=dashboard' : 'index.php?page=login';
$loginLabel = $isLoggedIn ? 'Area personale' : 'Accedi';
$loginDemoLabel = $isLoggedIn ? 'Area personale' : 'Accedi alla demo';
$landingUrl = 'index.php?page=landing&public=1';
$currentPage = 'vantaggi';
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'Vantaggi per store e responsabili di rete: processi standardizzati, controllo realtime e rollout rapido.';
$canonical = $baseUrl . '/index.php?page=vantaggi';
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
                <p class="landing-kicker">Efficienza reale per network e store</p>
                <h1>Vantaggi</h1>
                <p class="landing-subtitle">
                    Ogni tenant ha impostazioni fiscali, scontrini personalizzati e dashboard dedicate per una gestione scalabile.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto&public=1">Parla con un consulente</a>
                    <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi&public=1">Scopri i piani</a>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section" id="vantaggi">
            <div class="landing-section__header">
                <h2>Una piattaforma pensata per crescere</h2>
                <p>Standardizza i processi e migliora la visibilità su vendite e stock.</p>
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

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Vuoi attivare un rollout rapido?</h2>
                <p>Ti aiutiamo a configurare licenze, utenti e stock iniziale in pochi giorni.</p>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="index.php?page=contatto&public=1">Richiedi informazioni</a>
                <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($loginUrl) ?>"><?= htmlspecialchars($loginDemoLabel) ?></a>
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
