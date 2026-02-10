<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ('Prezzi - ' . $appName);
$isLoggedIn = isset($currentUser) && $currentUser !== null;
$loginUrl = $isLoggedIn ? 'index.php?page=dashboard' : 'index.php?page=login';
$loginLabel = $isLoggedIn ? 'Area personale' : 'Accedi';
$loginDemoLabel = $isLoggedIn ? 'Area personale' : 'Accedi alla demo';
$landingUrl = 'index.php?page=landing&public=1';
$currentPage = 'prezzi';
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'Prezzi dei piani Coresuite Express: confronto completo e opzioni di acquisto per punti vendita telefonia.';
$canonical = $baseUrl . '/index.php?page=prezzi';
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
                <p class="landing-kicker">Prezzi trasparenti, valore immediato</p>
                <h1>I piani Coresuite Express</h1>
                <p class="landing-subtitle">
                    Scegli il piano più adatto alla tua rete vendita. Ogni piano include onboarding rapido,
                    dashboard KPI e gestione multi-tenant per crescere in modo scalabile.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($landingUrl) ?>#contatto">Richiedi informazioni</a>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section landing-pricing">
            <div class="landing-section__header">
                <h2>Confronta i piani</h2>
                <p>Durate e limiti pensati per offrire il massimo controllo operativo.</p>
            </div>
            <div class="landing-pricing__grid">
                <article class="landing-pricing__card">
                    <header>
                        <h3>Piano Start</h3>
                        <p>12 mesi · max 1 cassiere</p>
                        <span class="landing-pricing__price">€ 550</span>
                        <span class="muted">€ 45,83 / mese</span>
                    </header>
                    <ul>
                        <li>Dashboard e KPI essenziali.</li>
                        <li>Magazzino SIM e prodotti.</li>
                        <li>Clienti, listini e vendite.</li>
                        <li>Guida completa e impostazioni.</li>
                    </ul>
                    <footer class="landing-pricing__cta">
                        <a class="landing-btn landing-btn--primary" href="index.php?page=checkout&amp;plan=start">Acquista</a>
                        <a class="landing-btn landing-btn--secondary" href="index.php?page=checkout&amp;plan=start&amp;billing=monthly">Mensile</a>
                    </footer>
                </article>
                <article class="landing-pricing__card landing-pricing__card--highlight">
                    <header>
                        <h3>Piano Start Plus</h3>
                        <p>12 mesi · max 1 cassiere</p>
                        <span class="landing-pricing__price">€ 650</span>
                        <span class="muted">€ 54,17 / mese</span>
                    </header>
                    <ul>
                        <li>Tutto del Start.</li>
                        <li>Report avanzati.</li>
                        <li>Richieste supporto clienti.</li>
                        <li>Ordini store integrati.</li>
                    </ul>
                    <footer class="landing-pricing__cta">
                        <a class="landing-btn landing-btn--primary" href="index.php?page=checkout&amp;plan=start_plus">Acquista</a>
                        <a class="landing-btn landing-btn--secondary" href="index.php?page=checkout&amp;plan=start_plus&amp;billing=monthly">Mensile</a>
                    </footer>
                </article>
                <article class="landing-pricing__card">
                    <header>
                        <h3>Piano Core</h3>
                        <p>24 mesi · max 2 cassieri</p>
                        <span class="landing-pricing__price">€ 850</span>
                        <span class="muted">€ 35,42 / mese</span>
                    </header>
                    <ul>
                        <li>Tutto del Start Plus.</li>
                        <li>Contratti energia luce & gas.</li>
                        <li>Report KPI avanzati.</li>
                        <li>Supporto prioritario.</li>
                    </ul>
                    <footer class="landing-pricing__cta">
                        <a class="landing-btn landing-btn--primary" href="index.php?page=checkout&amp;plan=core">Acquista</a>
                        <a class="landing-btn landing-btn--secondary" href="index.php?page=checkout&amp;plan=core&amp;billing=monthly">Mensile</a>
                    </footer>
                </article>
                <article class="landing-pricing__card">
                    <header>
                        <h3>Piano Business</h3>
                        <p>36 mesi · max 4 cassieri</p>
                        <span class="landing-pricing__price">€ 1200</span>
                        <span class="muted">€ 33,33 / mese</span>
                    </header>
                    <ul>
                        <li>Tutto del Core.</li>
                        <li>Report personalizzati.</li>
                        <li>SLA dedicato e onboarding.</li>
                        <li>Integrazioni avanzate.</li>
                    </ul>
                    <footer class="landing-pricing__cta">
                        <a class="landing-btn landing-btn--primary" href="index.php?page=checkout&amp;plan=business">Acquista</a>
                        <a class="landing-btn landing-btn--secondary" href="index.php?page=checkout&amp;plan=business&amp;billing=monthly">Mensile</a>
                    </footer>
                </article>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-section__header">
                <h2>Tabella prezzi comparativa</h2>
                <p>Una panoramica veloce delle funzionalità principali per piano.</p>
            </div>
            <div class="landing-pricing__table">
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
        </section>

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Vuoi una demo guidata?</h2>
                <p>Seleziona “Richiesta informazioni piani” e ti inviamo subito i dettagli completi.</p>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($landingUrl) ?>#contatto">Richiedi informazioni</a>
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
