<?php
declare(strict_types=1);

/** @var array{success:bool,message:string,error?:string}|null $feedback */
/** @var array<string, string>|null $oldInput */
$appName = $appName ?? 'Gestionale Telefonia';
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$prefillIdentifier = htmlspecialchars((string) ($oldInput['identifier'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recupera password - <?= htmlspecialchars($appName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-body">
    <div class="login-shell">
        <section class="login-hero">
            <div class="login-hero__decor"></div>
            <div class="login-hero__brand">
                <span class="login-hero__logo">Coresuite <span>Express</span></span>
                <p class="login-hero__subtitle">Recupera l’accesso in pochi passaggi.</p>
            </div>
            <ul class="login-hero__features">
                <li>Ricevi una password temporanea via email</li>
                <li>Accedi subito e aggiorna le credenziali</li>
                <li>Supporto tecnico disponibile se serve</li>
            </ul>
        </section>
        <section class="login-card">
            <header class="login-card__header">
                <h1>Recupera password</h1>
                <p>Inserisci email o username per ricevere le istruzioni.</p>
            </header>

            <?php if ($feedback !== null): ?>
                <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                    <p><?= htmlspecialchars((string) ($feedback['message'] ?? 'Operazione completata.')) ?></p>
                    <?php if (!empty($feedback['error'])): ?>
                        <p class="alert__detail">Dettaglio: <?= htmlspecialchars((string) $feedback['error']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?page=forgot_password" class="login-form">
                <div class="form__group">
                    <label for="identifier">Email o username</label>
                    <input type="text" name="identifier" id="identifier" value="<?= $prefillIdentifier ?>" required autocomplete="username">
                </div>
                <button type="submit" class="btn btn--primary btn--full">Invia istruzioni</button>
            </form>
            <p class="login-card__footer">Se non ricevi l’email entro qualche minuto, contatta l’amministratore.</p>
            <div class="login-divider">
                <span>hai già le credenziali?</span>
            </div>
            <a class="btn btn--secondary btn--full" href="index.php?page=login">Torna al login</a>
        </section>
    </div>
</body>
</html>
