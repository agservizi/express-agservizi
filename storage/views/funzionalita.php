<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Funzionalità');
$isLoggedIn = isset($currentUser) && $currentUser !== null;
$loginUrl = $isLoggedIn ? 'index.php?page=dashboard' : 'index.php?page=login';
$loginLabel = $isLoggedIn ? 'Area personale' : 'Accedi';
$landingUrl = 'index.php?page=landing&public=1';
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'Funzionalità del gestionale: vendite, magazzino SIM, campagne sconto, gestori energia e sicurezza avanzata.';
$canonical = $baseUrl . '/index.php?page=funzionalita';
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
                <a class="landing-nav__link" href="<?= htmlspecialchars($landingUrl) ?>">Home</a>
                <a class="landing-nav__link" href="index.php?page=demo&public=1">Demo</a>
                <a class="landing-nav__link" href="index.php?page=funzionalita&public=1">Funzionalità</a>
                <a class="landing-nav__link" href="index.php?page=vantaggi&public=1">Vantaggi</a>
                <a class="landing-nav__link" href="index.php?page=piani&public=1">Piani</a>
                <a class="landing-nav__link" href="index.php?page=prezzi&public=1">Prezzi</a>
                <a class="landing-nav__link" href="index.php?page=faq&public=1">FAQ</a>
                <a class="landing-nav__link" href="index.php?page=contatto&public=1">Contatto</a>
            </nav>
            <a class="landing-btn landing-btn--ghost" href="<?= htmlspecialchars($loginUrl) ?>"><?= htmlspecialchars($loginLabel) ?></a>
        </div>
    </div>

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Operatività semplice, controlli avanzati</p>
                <h1>Funzionalità</h1>
                <p class="landing-subtitle">
                    Moduli pensati per negozi di telefonia: vendita, magazzino SIM, campagne, energia e sicurezza.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto&public=1">Richiedi una demo</a>
                    <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi&public=1">Vedi i piani</a>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section" id="funzionalita">
            <div class="landing-section__header">
                <h2>Un gestionale completo, pronto alla vendita</h2>
                <p>Automatizza i flussi core senza rinunciare al controllo operativo.</p>
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

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Pronto a testarle dal vivo?</h2>
                <p>Accedi alla demo o richiedi un affiancamento guidato per il tuo store.</p>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi alla demo</a>
                <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto&public=1">Parla con un consulente</a>
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
