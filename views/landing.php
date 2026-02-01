<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Gestionale multi-tenant');
$loginUrl = 'index.php?page=login';
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
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
            <a class="landing-btn landing-btn--ghost" href="<?= htmlspecialchars($loginUrl) ?>">Accedi</a>
        </div>
    </div>

    <header class="landing-hero">

        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Gestionale multi-tenant per telecomunicazioni & retail</p>
                <h1>Il cuore operativo per SIM, vendite e servizi digitali.</h1>
                <p class="landing-subtitle">
                    <?= htmlspecialchars($appName) ?> è una piattaforma professionale per negozi e reti di vendita che
                    vogliono governare magazzino SIM, attivazioni, vendite, campagne sconto e gestori energia da un unico cruscotto.
                    È pensata per essere venduta facilmente ai clienti: onboarding rapido, flussi guidati e dati sempre sotto controllo.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="#funzionalita">Scopri le funzionalità</a>
                </div>
                <div class="landing-metrics">
                    <div class="landing-metric">
                        <span class="landing-metric__value">24/7</span>
                        <span class="landing-metric__label">Operatività e supporto interno</span>
                    </div>
                    <div class="landing-metric">
                        <span class="landing-metric__value">Multi-tenant</span>
                        <span class="landing-metric__label">Controllo separato per ogni cliente</span>
                    </div>
                    <div class="landing-metric">
                        <span class="landing-metric__value">+40 KPI</span>
                        <span class="landing-metric__label">Dashboard avanzata e report</span>
                    </div>
                </div>
            </div>

            <div class="landing-hero__card">
                <div class="landing-card">
                    <h3>Perché sceglierlo</h3>
                    <ul>
                        <li>Gestione completa di SIM, stock, prodotti e vendite.</li>
                        <li>Campagne sconto, listini e promozioni centralizzate.</li>
                        <li>Fatturato, margini e performance sempre tracciati.</li>
                        <li>Ruoli operativi, MFA e audit per sicurezza totale.</li>
                        <li>Configurazione scontrino personalizzabile per ogni tenant.</li>
                    </ul>
                    <div class="landing-card__cta">
                        <span>Pronto a presentarlo ai clienti?</span>
                        <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Vai al login</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section id="funzionalita" class="landing-section">
            <div class="landing-section__header">
                <h2>Un gestionale completo, pronto alla vendita</h2>
                <p>Progettato per scalare: dal singolo store alla rete multi-sede.</p>
            </div>
            <div class="landing-grid">
                <article class="landing-feature">
                    <h3>Vendite e cassa veloce</h3>
                    <p>Flusso di vendita rapido, scontrini termici, annulli e resi tracciati. Ideale per SIM, accessori e servizi digitali.</p>
                </article>
                <article class="landing-feature">
                    <h3>Magazzino & soglie</h3>
                    <p>Monitoraggio SIM e hardware con soglie minime, suggerimenti di riordino e alert automatici.</p>
                </article>
                <article class="landing-feature">
                    <h3>Campagne sconto</h3>
                    <p>Gestisci promozioni fisse o percentuali, attivazioni automatiche e monitoraggio performance.</p>
                </article>
                <article class="landing-feature">
                    <h3>Gestori telefonia</h3>
                    <p>Catalogo gestori, note operative, offerte e parametri aggiornabili dal pannello impostazioni.</p>
                </article>
                <article class="landing-feature">
                    <h3>Energia luce & gas</h3>
                    <p>Configurazione gestori energia e import offerte, con gettoni e note per i consulenti.</p>
                </article>
                <article class="landing-feature">
                    <h3>Sicurezza e ruoli</h3>
                    <p>Ruoli, MFA e audit log integrati per un controllo totale di utenti e operazioni.</p>
                </article>
            </div>
        </section>

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Una piattaforma che accelera la vendita</h2>
                <p>Ogni tenant dispone delle proprie impostazioni fiscali, scontrino personalizzato e dashboard dedicata.</p>
            </div>
            <div class="landing-columns">
                <div>
                    <h3>Per i responsabili di rete</h3>
                    <ul>
                        <li>Standardizzazione dei processi operativi.</li>
                        <li>Controllo realtime su stock e vendite.</li>
                        <li>Rollout rapido a nuovi punti vendita.</li>
                    </ul>
                </div>
                <div>
                    <h3>Per gli store</h3>
                    <ul>
                        <li>Interfaccia guidata e intuitiva.</li>
                        <li>Report automatici e promozioni semplici.</li>
                        <li>Accesso sicuro e configurazioni personalizzate.</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="contatto" class="landing-section">
            <div class="landing-section__header">
                <h2>Richiedi informazioni sui piani</h2>
                <p>Seleziona “Richiesta informazioni piani” e riceverai subito una mail con tutti i dettagli.</p>
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
                        <label for="contact_name">Nome e cognome</label>
                        <input type="text" id="contact_name" name="contact_name" value="<?= htmlspecialchars((string) ($oldInput['contact_name'] ?? '')) ?>">
                    </div>
                    <div class="form__group">
                        <label for="contact_email">Email *</label>
                        <input type="email" id="contact_email" name="contact_email" required value="<?= htmlspecialchars((string) ($oldInput['contact_email'] ?? '')) ?>">
                    </div>
                    <div class="form__group">
                        <label for="contact_company">Azienda</label>
                        <input type="text" id="contact_company" name="contact_company" value="<?= htmlspecialchars((string) ($oldInput['contact_company'] ?? '')) ?>">
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
                <div class="form__group">
                    <label for="contact_message">Messaggio</label>
                    <textarea id="contact_message" name="contact_message" rows="4"><?= htmlspecialchars((string) ($oldInput['contact_message'] ?? '')) ?></textarea>
                </div>
                <footer class="form__footer">
                    <button type="submit" class="btn btn--primary">Invia richiesta</button>
                </footer>
            </form>
        </section>

        <section class="landing-section landing-cta">
            <div class="landing-cta__content">
                <h2>Pronto a portarlo ai tuoi clienti?</h2>
                <p>Attiva il gestionale in pochi minuti, presenta un’esperienza professionale e moderna, e scala il business con facilità.</p>
            </div>
            <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi ora</a>
        </section>
    </main>

    <footer class="landing-footer">
        <div>
            <strong><?= htmlspecialchars($appName) ?></strong>
            <span>· Gestionale multi-tenant per telecomunicazioni e retail.</span>
        </div>
        <a href="<?= htmlspecialchars($loginUrl) ?>">Login</a>
    </footer>
</body>
</html>
