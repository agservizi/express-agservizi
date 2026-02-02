<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ('Attiva piano - ' . $appName);
$planKey = $planKey ?? 'start';
$plan = isset($plan) && is_array($plan) ? $plan : null;
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$landingUrl = 'index.php?page=landing';
$prezziUrl = 'index.php?page=prezzi';
$tenantName = trim((string) ($oldInput['tenant_name'] ?? ''));
$tenantSlug = trim((string) ($oldInput['tenant_slug'] ?? ''));
$tenantEmail = trim((string) ($oldInput['tenant_email'] ?? ''));
$tenantPhone = trim((string) ($oldInput['tenant_phone'] ?? ''));
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
                <span class="landing-brand__dot"></span>
                <span class="landing-brand__name"><?= htmlspecialchars($appName) ?></span>
            </div>
            <nav class="landing-nav" aria-label="Navigazione principale">
                <a class="landing-nav__link" href="<?= htmlspecialchars($landingUrl) ?>">Home</a>
                <a class="landing-nav__link" href="<?= htmlspecialchars($prezziUrl) ?>">Prezzi</a>
            </nav>
            <a class="landing-btn landing-btn--ghost" href="index.php?page=login">Accedi</a>
        </div>
    </div>

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Attivazione online</p>
                <h1>Completa i dati del tuo tenant</h1>
                <p class="landing-subtitle">Inserisci i dati necessari, poi verrai reindirizzato al pagamento Stripe del piano selezionato.</p>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section landing-checkout">
            <div class="landing-checkout__grid">
                <div class="landing-checkout__panel">
                    <h2>Dati tenant</h2>
                    <p>Le credenziali di accesso verranno inviate solo dopo il pagamento completato.</p>

                    <?php if ($feedback): ?>
                        <div class="landing-alert landing-alert--<?= ($feedback['success'] ?? false) ? 'success' : 'error' ?>">
                            <p><?= htmlspecialchars($feedback['message'] ?? 'Operazione non riuscita.') ?></p>
                            <?php foreach (($feedback['errors'] ?? []) as $error): ?>
                                <p><?= htmlspecialchars((string) $error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($plan === null): ?>
                        <div class="landing-alert landing-alert--error">
                            <p>Piano non valido. Torna alla pagina prezzi per riprovare.</p>
                        </div>
                        <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($prezziUrl) ?>">Vai ai prezzi</a>
                    <?php else: ?>
                        <form method="post" class="landing-form">
                            <input type="hidden" name="action" value="checkout_start">
                            <input type="hidden" name="plan_key" value="<?= htmlspecialchars($planKey) ?>">
                            <div class="landing-form__grid">
                                <label>
                                    Nome tenant *
                                    <input type="text" name="tenant_name" value="<?= htmlspecialchars($tenantName) ?>" required>
                                </label>
                                <label>
                                    Slug tenant *
                                    <input type="text" name="tenant_slug" value="<?= htmlspecialchars($tenantSlug) ?>" placeholder="es. ag-servizi" required>
                                </label>
                                <label>
                                    Email contatto *
                                    <input type="email" name="tenant_email" value="<?= htmlspecialchars($tenantEmail) ?>" required>
                                </label>
                                <label>
                                    Telefono contatto
                                    <input type="text" name="tenant_phone" value="<?= htmlspecialchars($tenantPhone) ?>">
                                </label>
                            </div>
                            <div class="landing-form__footer">
                                <button type="submit" class="landing-btn landing-btn--primary">Procedi al pagamento</button>
                                <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($prezziUrl) ?>">Torna ai prezzi</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <aside class="landing-checkout__summary">
                    <h3>Piano selezionato</h3>
                    <?php if ($plan !== null): ?>
                        <div class="landing-checkout__plan">
                            <strong><?= htmlspecialchars((string) $plan['stripe_name']) ?></strong>
                            <span><?= htmlspecialchars((string) $plan['stripe_description']) ?></span>
                            <div class="landing-checkout__price">€ <?= number_format((float) $plan['price_eur'], 0, ',', '.') ?></div>
                        </div>
                        <ul>
                            <li>Attivazione immediata dopo il pagamento.</li>
                            <li>Licenza assegnata con quota adesione pagata.</li>
                            <li>Credenziali inviate via email.</li>
                        </ul>
                    <?php else: ?>
                        <p>Seleziona un piano valido dalla pagina prezzi.</p>
                    <?php endif; ?>
                </aside>
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
