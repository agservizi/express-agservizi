<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - FAQ');
$loginUrl = 'index.php?page=login';
$landingUrl = 'index.php?page=landing';
$currentPage = 'faq';
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'FAQ sul gestionale: tempi di attivazione, demo, piani e supporto.';
$canonical = $baseUrl . '/index.php?page=faq';
$ogImage = $baseUrl . '/assets/img/logo-collapsed.svg';
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
                <span class="landing-brand__dot"></span>
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
                <p class="landing-kicker">Risposte rapide e chiare</p>
                <h1>FAQ</h1>
                <p class="landing-subtitle">
                    Tempi di attivazione, demo, piani e supporto: trovi tutto qui.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto">Contattaci</a>
                    <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi">Vedi i prezzi</a>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section landing-faq" id="faq">
            <div class="landing-section__header">
                <h2>Le domande più frequenti</h2>
                <p>Se non trovi la risposta, scrivici: rispondiamo entro 24 ore.</p>
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

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Pronto a partire?</h2>
                <p>Accedi alla demo o richiedi una consulenza personalizzata.</p>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi alla demo</a>
                <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto">Parla con un consulente</a>
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
</body>
</html>
