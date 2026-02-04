<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Contatto');
$loginUrl = 'index.php?page=login';
$landingUrl = 'index.php?page=landing';
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'Contattaci per una demo o informazioni sui piani del gestionale.';
$canonical = $baseUrl . '/index.php?page=contatto';
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
                <a class="landing-nav__link" href="index.php?page=demo">Demo</a>
                <a class="landing-nav__link" href="index.php?page=funzionalita">Funzionalità</a>
                <a class="landing-nav__link" href="index.php?page=vantaggi">Vantaggi</a>
                <a class="landing-nav__link" href="index.php?page=piani">Piani</a>
                <a class="landing-nav__link" href="index.php?page=prezzi">Prezzi</a>
                <a class="landing-nav__link" href="index.php?page=faq">FAQ</a>
                <a class="landing-nav__link" href="index.php?page=contatto">Contatto</a>
            </nav>
            <a class="landing-btn landing-btn--ghost" href="<?= htmlspecialchars($loginUrl) ?>">Accedi</a>
        </div>
    </div>

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Supporto rapido e personalizzato</p>
                <h1>Contatto</h1>
                <p class="landing-subtitle">
                    Raccontaci cosa ti serve: demo, informazioni sui piani o supporto commerciale.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=prezzi">Scopri i piani</a>
                    <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi alla demo</a>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section landing-contact" id="contatto">
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

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Preferisci una demo guidata?</h2>
                <p>Ti aiutiamo a configurare il primo punto vendita e a importare lo stock iniziale.</p>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi alla demo</a>
                <a class="landing-btn landing-btn--secondary" href="index.php?page=prezzi">Confronta i prezzi</a>
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
