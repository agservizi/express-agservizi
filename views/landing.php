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
            <nav class="landing-nav" aria-label="Navigazione principale">
                <a class="landing-nav__link" href="index.php?page=landing">Home</a>
                <a class="landing-nav__link" href="#funzionalita">Funzionalità</a>
                <a class="landing-nav__link" href="#vantaggi">Vantaggi</a>
                <a class="landing-nav__link" href="#piani">Piani</a>
                <a class="landing-nav__link" href="index.php?page=prezzi">Prezzi</a>
                <a class="landing-nav__link" href="#faq">FAQ</a>
                <a class="landing-nav__link" href="#contatto">Contatto</a>
            </nav>
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

        <section id="vantaggi" class="landing-section landing-section--accent">
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

        <section id="piani" class="landing-section">
            <div class="landing-section__header">
                <h2>Piani pensati per scalare</h2>
                <p>Confronta velocemente le opzioni e scopri i dettagli completi nella pagina Prezzi.</p>
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
            <div class="landing-section__cta">
                <a class="landing-btn landing-btn--primary" href="index.php?page=prezzi">Vai ai prezzi completi</a>
            </div>
        </section>

        <section class="landing-section landing-steps">
            <div class="landing-section__header">
                <h2>Attivazione in 3 step</h2>
                <p>Onboarding guidato per partire in tempi rapidi con i tuoi store.</p>
            </div>
            <div class="landing-steps__grid">
                <article class="landing-step">
                    <span class="landing-step__index">1</span>
                    <h3>Setup tenant</h3>
                    <p>Configura licenze, operatori, IVA e diciture scontrino.</p>
                </article>
                <article class="landing-step">
                    <span class="landing-step__index">2</span>
                    <h3>Carica catalogo</h3>
                    <p>Importa SIM, prodotti e listini con workflow guidati.</p>
                </article>
                <article class="landing-step">
                    <span class="landing-step__index">3</span>
                    <h3>Vai in vendita</h3>
                    <p>Monitora KPI, stock e supporto clienti in tempo reale.</p>
                </article>
            </div>
        </section>

        <section id="faq" class="landing-section landing-faq">
            <div class="landing-section__header">
                <h2>FAQ</h2>
                <p>Le risposte alle domande più frequenti.</p>
            </div>
            <div class="landing-faq__grid">
                <article class="landing-faq__item">
                    <h3>È adatto anche a un singolo punto vendita?</h3>
                    <p>Sì. Il piano Start è pensato per singoli store e può crescere con il tuo business.</p>
                </article>
                <article class="landing-faq__item">
                    <h3>Quanto tempo serve per partire?</h3>
                    <p>Con il setup guidato e i template preconfigurati si parte in pochi giorni.</p>
                </article>
                <article class="landing-faq__item">
                    <h3>Supportate l’energia luce & gas?</h3>
                    <p>Sì, dal piano Core con gestione offerte e contratti energia.</p>
                </article>
                <article class="landing-faq__item">
                    <h3>È possibile richiedere una demo?</h3>
                    <p>Certo. Compila il modulo contatti per ricevere una demo guidata.</p>
                </article>
            </div>
        </section>

        <section id="contatto" class="landing-section landing-contact">
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
        <span>Sviluppato e distribuito da AG SERVIZI P.Iva 08442881218</span>
    </footer>
</body>
</html>
