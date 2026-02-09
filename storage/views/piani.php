<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Piani');
$isLoggedIn = isset($currentUser) && $currentUser !== null;
$loginUrl = $isLoggedIn ? 'index.php?page=dashboard' : 'index.php?page=login';
$loginLabel = $isLoggedIn ? 'Area personale' : 'Accedi';
$landingUrl = 'index.php?page=landing&public=1';
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
        <section class="landing-section" id="piani">
            <div class="landing-section__header">
                <h2>Confronto rapido</h2>
                <p>Le opzioni principali per avviare o potenziare il tuo negozio.</p>
            </div>
            <div class="landing-pricing__grid landing-pricing__grid--compact">
                <article class="landing-pricing__card">
                    <header>
                        <h3>Start</h3>
                        <p>12 mesi · max 1 cassiere</p>
                        <span class="landing-pricing__price">€ 550</span>
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
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SIM, prodotti, vendite</td>
                            <td>✓</td>
                            <td>✓</td>
                            <td>✓</td>
                        </tr>
                        <tr>
                            <td>Report avanzati</td>
                            <td>—</td>
                            <td>✓</td>
                            <td>✓</td>
                        </tr>
                        <tr>
                            <td>Contratti energia</td>
                            <td>—</td>
                            <td>—</td>
                            <td>✓</td>
                        </tr>
                        <tr>
                            <td>Supporto prioritario</td>
                            <td>—</td>
                            <td>—</td>
                            <td>✓</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="landing-section__cta">
                <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi&public=1">Vai ai prezzi completi</a>
            </div>
        </section>
        <section class="landing-section landing-lead">
            <div class="landing-lead__content">
                <h2>Ricevi il kit gratuito “Avvio Store”</h2>
                <p>Checklist operativa + template report per partire subito con margini sotto controllo.</p>
            </div>
            <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto&public=1">Scarica il kit</a>
        </section>

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Vuoi una consulenza rapida?</h2>
                <p>Ti aiutiamo a scegliere il piano in base ai volumi e al team.</p>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="index.php?page=contatto&public=1">Parla con un consulente</a>
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
