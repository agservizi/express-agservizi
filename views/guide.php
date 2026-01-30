<?php
declare(strict_types=1);

/** @var array<string, mixed>|null $currentUser */
/** @var array{success:bool, message:string, error?:string}|null $feedback */
$pageTitle = $pageTitle ?? 'Guida Coresuite Express';
$currentUser = $currentUser ?? null;
$feedback = $feedback ?? null;
?>
<section class="page page--guide">
    <?php if ($feedback !== null): ?>
        <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
            <p><?= htmlspecialchars($feedback['message']) ?></p>
            <?php if (!empty($feedback['error'])): ?>
                <p class="muted">Dettaglio: <?= htmlspecialchars((string) $feedback['error']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <header class="guide-hero">
        <div class="guide-hero__content">
            <span class="guide-hero__badge">Guida completa</span>
            <h2>Usa Coresuite Express in modo professionale</h2>
            <p>Una panoramica pratica delle funzionalità principali per gestire vendite, magazzino, clienti e report in modo fluido e sicuro.</p>
            <div class="guide-hero__chips">
                <span class="guide-chip">Operatività quotidiana</span>
                <span class="guide-chip">Sicurezza e ruoli</span>
                <span class="guide-chip">Report e notifiche</span>
            </div>
        </div>
        <div class="guide-hero__aside">
            <div class="guide-hero__card">
                <h3>Indice rapido</h3>
                <ul>
                    <li>Accesso e profilo</li>
                    <li>Magazzino SIM</li>
                    <li>Prodotti e listini</li>
                    <li>Vendite</li>
                    <li>Clienti e richieste</li>
                    <li>Report e notifiche</li>
                    <li>Impostazioni e sicurezza</li>
                </ul>
            </div>
            <div class="guide-hero__highlight">
                <span class="guide-hero__highlight-title">Suggerimento</span>
                <p>Imposta subito le soglie di stock e i ruoli: migliora alert e sicurezza operativa.</p>
            </div>
        </div>
    </header>

    <div class="guide-grid">
        <article class="guide-card">
            <header>
                <span class="guide-card__icon">🔐</span>
                <h3>Accesso e profilo</h3>
            </header>
            <ul>
                <li>Accedi con credenziali e completa l’MFA se richiesto.</li>
                <li>Verifica i dati personali nella pagina Profilo.</li>
                <li>Controlla ruolo e permessi associati.</li>
            </ul>
        </article>

        <article class="guide-card">
            <header>
                <span class="guide-card__icon">📥</span>
                <h3>Magazzino SIM</h3>
            </header>
            <ul>
                <li>Importa ICCID da CSV o inserisci manualmente.</li>
                <li>Monitora disponibilità e stato (InStock, Reserved, Sold).</li>
                <li>Imposta soglie minime per ricevere alert.</li>
            </ul>
        </article>

        <article class="guide-card">
            <header>
                <span class="guide-card__icon">🛒</span>
                <h3>Prodotti e listini</h3>
            </header>
            <ul>
                <li>Gestisci catalogo prodotti, prezzi e codici IVA.</li>
                <li>Configura offerte operatore e periodi di validità.</li>
                <li>Controlla stock prodotti e movimenti.</li>
            </ul>
        </article>

        <article class="guide-card">
            <header>
                <span class="guide-card__icon">🧾</span>
                <h3>Vendite</h3>
            </header>
            <ul>
                <li>Registra nuove vendite con prodotti, SIM e sconti.</li>
                <li>Consulta storico, annulla o rimborsa quando serve.</li>
                <li>Stampa ricevute e verifica i log di vendita.</li>
            </ul>
        </article>

        <article class="guide-card">
            <header>
                <span class="guide-card__icon">👥</span>
                <h3>Clienti e richieste</h3>
            </header>
            <ul>
                <li>Gestisci anagrafiche clienti e richieste di prodotto.</li>
                <li>Apri ticket di supporto e monitora lo stato.</li>
                <li>Allinea comunicazioni con il portale clienti.</li>
            </ul>
        </article>

        <article class="guide-card">
            <header>
                <span class="guide-card__icon">📈</span>
                <h3>Report e notifiche</h3>
            </header>
            <ul>
                <li>Usa i report per analizzare vendite e performance.</li>
                <li>Controlla la campanella per notifiche di sistema.</li>
                <li>Valuta trend e anomalie di stock.</li>
            </ul>
        </article>

        <article class="guide-card">
            <header>
                <span class="guide-card__icon">⚙️</span>
                <h3>Impostazioni e sicurezza</h3>
            </header>
            <ul>
                <li>Gestisci operatori, ruoli e sicurezza MFA.</li>
                <li>Configura gestori e soglie di magazzino.</li>
                <li>Verifica SSO e integrazioni attive.</li>
            </ul>
        </article>
    </div>

    <section class="guide-callout">
        <div>
            <h3>Hai bisogno di supporto?</h3>
            <p>Apri una richiesta nella sezione Supporto clienti o consulta le notifiche per aggiornamenti di sistema.</p>
        </div>
        <form method="post" class="guide-callout__form">
            <input type="hidden" name="action" value="send_guide_support">
            <button type="submit" class="guide-callout__cta">Apri una richiesta</button>
        </form>
    </section>
</section>
