<?php
declare(strict_types=1);

/** @var array<string, mixed>|null $currentUser */
/** @var array{success:bool, message:string, error?:string}|null $feedback */
/** @var array<string, string>|null $oldInput */
$pageTitle = $pageTitle ?? 'Suggerisci una funzionalità';
$currentUser = $currentUser ?? null;
$feedback = $feedback ?? null;
$oldInput = is_array($oldInput) ? $oldInput : [];

$prefillName = (string) ($oldInput['feature_contact_name'] ?? ($currentUser['fullname'] ?? $currentUser['username'] ?? ''));
$prefillEmail = (string) ($oldInput['feature_contact_email'] ?? ($currentUser['email'] ?? ''));
$prefillTitle = (string) ($oldInput['feature_title'] ?? '');
$prefillArea = (string) ($oldInput['feature_area'] ?? '');
$prefillPriority = (string) ($oldInput['feature_priority'] ?? '');
$prefillImpact = (string) ($oldInput['feature_impact'] ?? '');
$prefillDescription = (string) ($oldInput['feature_description'] ?? '');
$prefillBenefit = (string) ($oldInput['feature_benefit'] ?? '');
$prefillWorkaround = (string) ($oldInput['feature_workaround'] ?? '');
?>

<section class="page page--feature-requests">
    <header class="page__header">
        <h1>Suggerisci una nuova funzionalità</h1>
        <p>Raccontaci cosa ti aiuterebbe a lavorare meglio: raccoglieremo le proposte e le useremo per la roadmap del gestionale.</p>
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
                    <h3>Come descrivere l’idea</h3>
                </div>
                <ul class="card__list">
                    <li>Indica l’area del gestionale coinvolta.</li>
                    <li>Spiega il problema o il bisogno che risolve.</li>
                    <li>Segnala l’impatto operativo o commerciale.</li>
                </ul>
            </article>
            <article class="card">
                <div class="card__header">
                    <h3>Valore aggiunto</h3>
                </div>
                <ul class="card__list">
                    <li>Riduce tempi o errori in cassa.</li>
                    <li>Migliora la gestione clienti o report.</li>
                    <li>Aiuta a vendere o a rispettare le policy.</li>
                </ul>
            </article>
        </div>
    </section>

    <form method="post" class="form">
        <input type="hidden" name="action" value="send_feature_request">
        <div class="form__grid">
            <div class="form__group">
                <label for="feature_title">Titolo *</label>
                <input id="feature_title" name="feature_title" type="text" required placeholder="Es. Alert per stock critico" value="<?= htmlspecialchars($prefillTitle) ?>">
            </div>
            <div class="form__group">
                <label for="feature_area">Area *</label>
                <select id="feature_area" name="feature_area" required>
                    <option value="">Seleziona</option>
                    <option value="Magazzino SIM" <?= $prefillArea === 'Magazzino SIM' ? 'selected' : '' ?>>Magazzino SIM</option>
                    <option value="Prodotti" <?= $prefillArea === 'Prodotti' ? 'selected' : '' ?>>Prodotti</option>
                    <option value="Vendite" <?= $prefillArea === 'Vendite' ? 'selected' : '' ?>>Vendite</option>
                    <option value="Clienti" <?= $prefillArea === 'Clienti' ? 'selected' : '' ?>>Clienti</option>
                    <option value="Report" <?= $prefillArea === 'Report' ? 'selected' : '' ?>>Report</option>
                    <option value="Impostazioni" <?= $prefillArea === 'Impostazioni' ? 'selected' : '' ?>>Impostazioni</option>
                    <option value="Altro" <?= $prefillArea === 'Altro' ? 'selected' : '' ?>>Altro</option>
                </select>
            </div>
            <div class="form__group">
                <label for="feature_priority">Priorità</label>
                <select id="feature_priority" name="feature_priority">
                    <option value="">Non indicata</option>
                    <option value="bassa" <?= $prefillPriority === 'bassa' ? 'selected' : '' ?>>Bassa</option>
                    <option value="media" <?= $prefillPriority === 'media' ? 'selected' : '' ?>>Media</option>
                    <option value="alta" <?= $prefillPriority === 'alta' ? 'selected' : '' ?>>Alta</option>
                    <option value="critica" <?= $prefillPriority === 'critica' ? 'selected' : '' ?>>Critica</option>
                </select>
            </div>
            <div class="form__group">
                <label for="feature_impact">Impatto</label>
                <select id="feature_impact" name="feature_impact">
                    <option value="">Non indicato</option>
                    <option value="vendite" <?= $prefillImpact === 'vendite' ? 'selected' : '' ?>>Vendite e fatturato</option>
                    <option value="operativo" <?= $prefillImpact === 'operativo' ? 'selected' : '' ?>>Operatività quotidiana</option>
                    <option value="report" <?= $prefillImpact === 'report' ? 'selected' : '' ?>>Report e analisi</option>
                    <option value="clienti" <?= $prefillImpact === 'clienti' ? 'selected' : '' ?>>Esperienza cliente</option>
                    <option value="compliance" <?= $prefillImpact === 'compliance' ? 'selected' : '' ?>>Compliance / sicurezza</option>
                    <option value="altro" <?= $prefillImpact === 'altro' ? 'selected' : '' ?>>Altro</option>
                </select>
            </div>
        </div>
        <div class="form__group">
            <label for="feature_description">Descrizione *</label>
            <textarea id="feature_description" name="feature_description" rows="4" required placeholder="Spiega il problema e cosa vorresti vedere."><?= htmlspecialchars($prefillDescription) ?></textarea>
            <small>Più dettagli aggiungi, più velocemente possiamo valutare la proposta.</small>
        </div>
        <div class="form__group">
            <label for="feature_benefit">Beneficio atteso</label>
            <textarea id="feature_benefit" name="feature_benefit" rows="3" placeholder="Es. Ridurre le chiamate di assistenza, velocizzare le vendite..."><?= htmlspecialchars($prefillBenefit) ?></textarea>
        </div>
        <div class="form__group">
            <label for="feature_workaround">Soluzione temporanea</label>
            <textarea id="feature_workaround" name="feature_workaround" rows="3" placeholder="Se stai usando un workaround, descrivilo qui."><?= htmlspecialchars($prefillWorkaround) ?></textarea>
        </div>
        <div class="form__grid">
            <div class="form__group">
                <label for="feature_contact_name">Referente</label>
                <input id="feature_contact_name" name="feature_contact_name" type="text" value="<?= htmlspecialchars($prefillName) ?>" placeholder="Nome e cognome">
            </div>
            <div class="form__group">
                <label for="feature_contact_email">Email</label>
                <input id="feature_contact_email" name="feature_contact_email" type="email" value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="nome@azienda.it">
            </div>
        </div>
        <div class="form__footer">
            <button type="submit" class="btn btn--primary">Invia proposta</button>
        </div>
    </form>
</section>
