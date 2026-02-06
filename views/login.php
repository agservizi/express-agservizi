<?php
declare(strict_types=1);

/** @var array<int, string> $errors */
$appName = $appName ?? 'Gestionale Telefonia';
$oldInput = $oldInput ?? ['username' => '', 'remember_me' => false];
$oldUsername = htmlspecialchars((string) ($oldInput['username'] ?? ''), ENT_QUOTES, 'UTF-8');
$rememberChecked = !empty($oldInput['remember_me']);
$oldDemoPlan = htmlspecialchars((string) ($oldInput['demo_plan'] ?? 'start'), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accedi - <?= htmlspecialchars($appName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-body">
    <div class="login-shell">
        <section class="login-hero">
            <div class="login-hero__decor"></div>
            <div class="login-hero__brand">
                <span class="login-hero__badge">Retail Operations 2025</span>
                <h1 class="login-hero__logo">Coresuite <span>Express</span></h1>
                <p class="login-hero__subtitle">La control room digitale per coordinare vendite, magazzino e customer care in tempo reale.</p>
            </div>
            <ul class="login-hero__features">
                <li>Indicatori stock critici e riordini automatici</li>
                <li>Dashboard KPI filtrabile per giorno, mese, anno</li>
                <li>Workflow resi con tracciamento audit</li>
            </ul>
            <footer class="login-hero__footer">
                <span class="login-hero__footnote">Progettato per corner multi-brand e store flagship.</span>
            </footer>
        </section>
        <section class="login-card">
            <header class="login-card__header">
                <h1>Benvenuto</h1>
                <p>Accedi a <?= htmlspecialchars($appName) ?> con le tue credenziali aziendali.</p>
            </header>

            <?php if (!empty($errors)): ?>
                <div class="alert alert--error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?page=login" class="login-form">
                <div class="form__group">
                    <label for="username">Nome utente</label>
                    <input type="text" name="username" id="username" value="<?= $oldUsername ?>" required autofocus>
                </div>
                <div class="form__group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="login-form__options">
                    <label class="checkbox">
                        <input type="checkbox" name="remember_me" value="1" <?= $rememberChecked ? 'checked' : '' ?>>
                        <span>Ricordami su questo dispositivo</span>
                    </label>
                    <a class="login-form__help" href="index.php?page=forgot_password">Recupera password</a>
                </div>
                <button type="submit" class="btn btn--primary btn--full">Accedi</button>
            </form>
            <div class="login-divider">
                <span>oppure prova la demo</span>
            </div>
            <form method="post" action="index.php?page=login" class="login-form">
                <input type="hidden" name="action" value="demo_login">
                <div class="form__group">
                    <label for="demo_plan">Piano demo</label>
                    <select id="demo_plan" name="demo_plan" required>
                        <option value="start" <?= $oldDemoPlan === 'start' ? 'selected' : '' ?>>Start</option>
                        <option value="start_plus" <?= $oldDemoPlan === 'start_plus' ? 'selected' : '' ?>>Start Plus</option>
                        <option value="core" <?= $oldDemoPlan === 'core' ? 'selected' : '' ?>>Core</option>
                        <option value="business" <?= $oldDemoPlan === 'business' ? 'selected' : '' ?>>Business</option>
                    </select>
                </div>
                <button type="submit" class="btn btn--secondary btn--full">Accedi in demo (1 ora)</button>
            </form>
            <p class="login-card__footer">Accesso protetto e tracciato per garantirti compliance e sicurezza.</p>
        </section>
    </div>
</body>
</html>
