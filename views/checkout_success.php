<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ('Pagamento completato - ' . $appName);
$request = isset($request) && is_array($request) ? $request : null;
$isLoggedIn = isset($currentUser) && $currentUser !== null;
$loginUrl = $isLoggedIn ? 'index.php?page=dashboard' : 'index.php?page=login';
$loginLabel = $isLoggedIn ? 'Area personale' : 'Accedi';
$prezziUrl = 'index.php?page=prezzi';
$landingUrl = 'index.php?page=landing&public=1';
$status = $request['status'] ?? null;
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
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
                <a class="landing-nav__link" href="<?= htmlspecialchars($prezziUrl) ?>">Prezzi</a>
            </nav>
        </div>
    </div>

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Pagamento ricevuto</p>
                <h1>Grazie per l'acquisto</h1>
                <p class="landing-subtitle">Stiamo attivando il tuo tenant. Riceverai a breve una mail con le credenziali di accesso.</p>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Stato attivazione</h2>
                <?php if ($status === 'paid'): ?>
                    <p>Pagamento confermato. Le credenziali sono in invio.</p>
                <?php elseif ($status === 'processing'): ?>
                    <p>Pagamento confermato. Attivazione in corso.</p>
                <?php else: ?>
                    <p>Se non ricevi l’email entro pochi minuti, contattaci: verificheremo lo stato del pagamento.</p>
                <?php endif; ?>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($landingUrl) ?>#contatto">Contatta il supporto</a>
                <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($loginUrl) ?>"><?= htmlspecialchars($loginLabel) ?></a>
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
