<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Piani');
$loginUrl = 'index.php?page=login';
$landingUrl = 'index.php?page=landing';
$currentPage = 'piani';
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'Confronto piani: Start, Start Plus, Core. Scopri costi e funzionalità per il tuo punto vendita.';
$canonical = $baseUrl . '/index.php?page=piani';
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
                <p class="landing-kicker">Scegli il piano giusto per il tuo store</p>
                <h1>Piani</h1>
                <p class="landing-subtitle">
                    Durate e limiti pensati per garantire controllo operativo e crescita graduale della rete vendita.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto">Richiedi informazioni</a>
                    <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi">Confronta i prezzi</a>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section" id="piani">
            <div class="landing-section__header">
                <h2>Dettaglio completo dei piani</h2>
                <p>Di seguito trovi, punto per punto, tutto ciò che è incluso in ogni livello di servizio.</p>
            </div>

            <article class="landing-pricing__card">
                <header>
                    <h3>Piano Start</h3>
                    <p>12 mesi · max 1 cassiere · € 550 (oppure € 45,83/mese)</p>
                </header>
                <ul>
                    <li><strong>Dashboard operativa:</strong> panoramica giornaliera su vendite, incassi e performance.</li>
                    <li><strong>Magazzino SIM:</strong> stato SIM, provider, note e storicità movimenti.</li>
                    <li><strong>Catalogo prodotti:</strong> anagrafiche, prezzi, IVA, barcode e scorte.</li>
                    <li><strong>Clienti:</strong> rubrica, dati fiscali, note e storico acquisti.</li>
                    <li><strong>Listini e offerte:</strong> gestione offerte base e prezzi predefiniti.</li>
                    <li><strong>Nuova vendita:</strong> workflow guidato, scontrino e stampa rapida.</li>
                    <li><strong>Storico vendite:</strong> ricerca e filtri principali per controllo cassa.</li>
                    <li><strong>Guida operativa:</strong> manuale completo per l’uso quotidiano.</li>
                    <li><strong>Impostazioni:</strong> configurazioni essenziali dello store.</li>
                </ul>
            </article>

            <article class="landing-pricing__card landing-pricing__card--highlight">
                <header>
                    <h3>Piano Start Plus</h3>
                    <p>12 mesi · max 1 cassiere · € 650 (oppure € 54,17/mese)</p>
                </header>
                <ul>
                    <li><strong>Tutto del Piano Start</strong> incluso senza limitazioni aggiuntive.</li>
                    <li><strong>Report avanzati:</strong> KPI di vendita, margini e performance periodiche.</li>
                    <li><strong>Richieste supporto clienti:</strong> gestione ticket e tracciamento richieste.</li>
                    <li><strong>Ordini store:</strong> gestione richieste prodotti e riordini interni.</li>
                    <li><strong>Notifiche operative:</strong> alert su eventi chiave e priorità.</li>
                </ul>
            </article>

            <article class="landing-pricing__card">
                <header>
                    <h3>Piano Core</h3>
                    <p>24 mesi · max 2 cassieri · € 850 (oppure € 35,42/mese)</p>
                </header>
                <ul>
                    <li><strong>Tutto del Piano Start Plus</strong> incluso.</li>
                    <li><strong>Contratti energia:</strong> gestione pratiche luce & gas e simulazioni.</li>
                    <li><strong>Report KPI avanzati:</strong> analisi dettagliate per prodotti, canali e operatori.</li>
                    <li><strong>Supporto prioritario:</strong> canale dedicato con tempi di risposta accelerati.</li>
                    <li><strong>Accessi estesi:</strong> fino a 2 cassieri attivi.</li>
                </ul>
            </article>

            <article class="landing-pricing__card">
                <header>
                    <h3>Piano Business</h3>
                    <p>36 mesi · max 4 cassieri · € 1200 (oppure € 33,33/mese)</p>
                </header>
                <ul>
                    <li><strong>Tutto del Piano Core</strong> incluso.</li>
                    <li><strong>Report personalizzati:</strong> cruscotti su misura per la direzione.</li>
                    <li><strong>SLA dedicato:</strong> livelli di servizio concordati e monitorati.</li>
                    <li><strong>Onboarding e training:</strong> affiancamento operativo iniziale.</li>
                    <li><strong>Integrazioni avanzate:</strong> collegamenti custom con sistemi esterni.</li>
                    <li><strong>Accessi estesi:</strong> fino a 4 cassieri attivi.</li>
                </ul>
            </article>
        </section>

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Serve un piano su misura?</h2>
                <p>Descrivici i volumi, le sedi e i processi: prepariamo una proposta personalizzata.</p>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="index.php?page=contatto">Parla con un consulente</a>
                <a class="landing-btn landing-btn--secondary" href="index.php?page=prezzi">Vai ai prezzi</a>
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
