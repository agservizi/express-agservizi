<?php
declare(strict_types=1);

/** @var array<string, mixed>|null $currentUser */
/** @var array{success:bool, message:string, error?:string}|null $feedback */
/** @var array<string, string>|null $oldInput */
/** @var array<string, array<string, mixed>> $catalog */
/** @var array<int, array<string, mixed>> $requests */
$pageTitle = $pageTitle ?? 'Supporto tecnico 24/7';
$currentUser = $currentUser ?? null;
$feedback = $feedback ?? null;
$oldInput = is_array($oldInput) ? $oldInput : [];
$catalog = is_array($catalog ?? null) ? $catalog : [];
$requests = is_array($requests ?? null) ? $requests : [];

$prefillEmail = (string) ($oldInput['support_email'] ?? ($currentUser['email'] ?? ''));
$prefillIssue = (string) ($oldInput['support_issue'] ?? '');
$prefillArea = (string) ($oldInput['support_area'] ?? '');
$areaOptions = [
    'Accesso',
    'Magazzino SIM',
    'Prodotti',
    'Vendite',
    'Clienti',
    'Report',
    'Impostazioni',
    'Prestazioni',
    'Altro',
];
$defaultAreas = $areaOptions;
$prefillDetails = (string) ($oldInput['support_details'] ?? '');
$prefillEscalate = (string) ($oldInput['support_escalate'] ?? '0');
?>

<section class="page page--support-auto">
    <header class="page__header">
        <h1>Supporto tecnico automatico 24/7</h1>
        <p>Seleziona il problema: riceverai subito una email con le istruzioni. Se non si risolve, inoltreremo la richiesta al supporto tecnico entro 24h lavorative.</p>
        <div class="page__actions">
            <a class="btn btn--secondary" href="index.php?page=support_requests">Lista richieste di supporto</a>
        </div>
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

    <form method="post" class="form" data-support-auto-form>
        <input type="hidden" name="action" value="support_auto_send">
        <div class="form__grid">
            <div class="form__group">
                <label for="support_issue">Problema *</label>
                <select id="support_issue" name="support_issue" required data-support-issue>
                    <option value="">Seleziona</option>
                    <?php foreach ($catalog as $key => $item): ?>
                        <?php
                            $areas = array_values(array_filter((array) ($item['areas'] ?? [])));
                            $areasJson = htmlspecialchars(json_encode($areas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]');
                        ?>
                        <option value="<?= htmlspecialchars($key) ?>" data-areas="<?= $areasJson ?>" <?= $prefillIssue === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($item['title'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group">
                <label for="support_area">Area</label>
                <select id="support_area" name="support_area" data-support-area data-default-areas="<?= htmlspecialchars(json_encode($defaultAreas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') ?>">
                    <option value="">Seleziona</option>
                    <?php foreach ($areaOptions as $area): ?>
                        <option value="<?= htmlspecialchars($area) ?>" <?= $prefillArea === $area ? 'selected' : '' ?>>
                            <?= htmlspecialchars($area) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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

        <div class="form__footer">
            <button type="submit" class="btn btn--primary">Invia istruzioni</button>
        </div>
    </form>

    <section class="page__section">
        <div class="card">
            <div class="card__header">
                <h3>Ultime richieste aperte</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Oggetto</th>
                            <th>Stato</th>
                            <th>Data</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests === []): ?>
                            <tr>
                                <td colspan="5">Nessuna richiesta disponibile.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $row): ?>
                                <?php
                                    $requestId = (int) ($row['id'] ?? 0);
                                    $subject = trim((string) ($row['subject'] ?? ''));
                                    $status = trim((string) ($row['status'] ?? 'Open'));
                                    $createdAt = trim((string) ($row['created_at'] ?? ''));
                                    $dateLabel = $createdAt !== '' && ($timestamp = strtotime($createdAt)) !== false
                                        ? date('d/m/Y H:i', $timestamp)
                                        : 'n/d';
                                ?>
                                <tr>
                                    <td>#<?= $requestId ?></td>
                                    <td><?= htmlspecialchars($subject !== '' ? $subject : 'Richiesta supporto') ?></td>
                                    <td><?= htmlspecialchars($status) ?></td>
                                    <td><?= htmlspecialchars($dateLabel) ?></td>
                                    <td>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="action" value="support_auto_escalate">
                                            <input type="hidden" name="request_id" value="<?= $requestId ?>">
                                            <button type="submit" class="btn btn--secondary">Non ho risolto: inoltra la richiesta al supporto tecnico</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</section>
