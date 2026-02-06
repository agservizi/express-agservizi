<?php
declare(strict_types=1);

/** @var array<string, mixed>|null $currentUser */
/** @var array{success:bool, message:string, error?:string}|null $feedback */
/** @var array<string, string>|null $oldInput */
/** @var array<string, array<string, mixed>> $catalog */
$pageTitle = $pageTitle ?? 'Supporto tecnico 24/7';
$currentUser = $currentUser ?? null;
$feedback = $feedback ?? null;
$oldInput = is_array($oldInput) ? $oldInput : [];
$catalog = is_array($catalog ?? null) ? $catalog : [];

$prefillEmail = (string) ($oldInput['support_email'] ?? ($currentUser['email'] ?? ''));
$prefillIssue = (string) ($oldInput['support_issue'] ?? '');
$prefillArea = (string) ($oldInput['support_area'] ?? '');
$prefillDetails = (string) ($oldInput['support_details'] ?? '');
$prefillEscalate = (string) ($oldInput['support_escalate'] ?? '0');
?>

<section class="page page--support-auto">
    <header class="page__header">
        <h1>Supporto tecnico automatico 24/7</h1>
        <p>Seleziona il problema: riceverai subito una email con le istruzioni. Se non si risolve, inoltreremo la richiesta al supporto tecnico entro 24h lavorative.</p>
    </header>

    <?php if ($feedback !== null): ?>
        <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
            <p><?= htmlspecialchars($feedback['message']) ?></p>
            <?php if (!empty($feedback['error'])): ?>
                <p class="alert__detail">Dettaglio: <?= htmlspecialchars((string) $feedback['error']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <section class="page__section">
        <div class="cards">
            <article class="card">
                <div class="card__header">
                    <h3>Come funziona</h3>
                </div>
                <ul class="card__list">
                    <li>Seleziona la tipologia di problema.</li>
                    <li>Ricevi istruzioni immediate via email.</li>
                    <li>Se non risolvi, apri la richiesta al supporto tecnico.</li>
                </ul>
            </article>
            <article class="card">
                <div class="card__header">
                    <h3>Copertura completa</h3>
                </div>
                <ul class="card__list">
                    <li>Valido per tutte le aree del gestionale.</li>
                    <li>Istruzioni aggiornate e step-by-step.</li>
                    <li>Supporto umano entro 24h lavorative se necessario.</li>
                </ul>
            </article>
        </div>
    </section>

    <form method="post" class="form">
        <input type="hidden" name="action" value="support_auto_send">
        <div class="form__grid">
            <div class="form__group">
                <label for="support_issue">Problema *</label>
                <select id="support_issue" name="support_issue" required>
                    <option value="">Seleziona</option>
                    <?php foreach ($catalog as $key => $item): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $prefillIssue === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($item['title'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group">
                <label for="support_area">Area</label>
                <input id="support_area" name="support_area" type="text" placeholder="Es. Vendite, Magazzino SIM" value="<?= htmlspecialchars($prefillArea) ?>">
            </div>
            <div class="form__group">
                <label for="support_email">Email *</label>
                <input id="support_email" name="support_email" type="email" required value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="nome@azienda.it">
            </div>
            <div class="form__group">
                <label for="support_details">Dettagli utili</label>
                <input id="support_details" name="support_details" type="text" placeholder="Es. Errore su stampa scontrino" value="<?= htmlspecialchars($prefillDetails) ?>">
            </div>
        </div>

        <div class="form__group">
            <label class="checkbox">
                <input type="checkbox" name="support_escalate" value="1" <?= $prefillEscalate === '1' ? 'checked' : '' ?>>
                <span>Non ho risolto: inoltra la richiesta al supporto tecnico.</span>
            </label>
        </div>

        <div class="form__footer">
            <button type="submit" class="btn btn--primary">Invia istruzioni</button>
        </div>
    </form>
</section>
