<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ('Pagamento annullato - ' . $appName);
$planKey = $planKey ?? 'start';
$billingCycle = isset($billingCycle) && is_string($billingCycle) ? $billingCycle : 'annual';
$prezziUrl = 'index.php?page=prezzi';
$billingParam = $billingCycle === 'monthly' ? '&billing=monthly' : '';
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
                <a class="landing-nav__link" href="<?= htmlspecialchars($prezziUrl) ?>">Prezzi</a>
            </nav>
        </div>
    </div>

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Pagamento annullato</p>
                <h1>Vuoi riprovare?</h1>
                <p class="landing-subtitle">Nessun addebito è stato effettuato. Puoi tornare al checkout quando vuoi.</p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--primary" href="index.php?page=checkout&amp;plan=<?= htmlspecialchars($planKey) ?><?= htmlspecialchars($billingParam) ?>">Torna al checkout</a>
                    <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($prezziUrl) ?>">Vai ai prezzi</a>
                </div>
            </div>
        </div>
    </header>

    <footer class="landing-footer">
        <div>
            <strong><?= htmlspecialchars($appName) ?></strong>
            <span>· Gestionale multi-tenant per telecomunicazioni e retail.</span>
        </div>
        <span>Sviluppato e distribuito da AG SERVIZI P.Iva 08442881218</span>
    </footer>
</body>
</html>
