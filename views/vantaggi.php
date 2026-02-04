<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Vantaggi');
$loginUrl = 'index.php?page=login';
$landingUrl = 'index.php?page=landing';
$currentPage = 'vantaggi';
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'Vantaggi per store e responsabili di rete: processi standardizzati, controllo realtime e rollout rapido.';
$canonical = $baseUrl . '/index.php?page=vantaggi';
$ogImage = $baseUrl . '/assets/img/logo-collapsed.svg';
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:type" content="website">
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
</head>
<body class="landing-body">
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

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Efficienza reale per network e store</p>
                <h1>Vantaggi</h1>
                <p class="landing-subtitle">
                    Ogni tenant ha impostazioni fiscali, scontrini personalizzati e dashboard dedicate per una gestione scalabile.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto">Parla con un consulente</a>
                    <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi">Scopri i piani</a>
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
                <a class="landing-btn landing-btn--primary" href="index.php?page=contatto">Richiedi informazioni</a>
                <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi alla demo</a>
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
