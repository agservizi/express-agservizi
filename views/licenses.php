<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $tenants
 * @var array<int, array<string, mixed>> $licenses
 * @var array<int, array<string, mixed>> $assignments
 * @var array{success:bool,message:string,error?:string}|null $feedback
 * @var array{code:string,label?:string}|null $licenseGeneratedCode
 * @var int $selectedLicenseId
 * @var int $selectedAssignmentId
 */
$pageTitle = $pageTitle ?? 'Licenze';
$tenants = isset($tenants) && is_array($tenants) ? $tenants : [];
$licenses = isset($licenses) && is_array($licenses) ? $licenses : [];
$assignments = isset($assignments) && is_array($assignments) ? $assignments : [];
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$licenseGeneratedCode = isset($licenseGeneratedCode) && is_array($licenseGeneratedCode) ? $licenseGeneratedCode : null;
$selectedLicenseId = isset($selectedLicenseId) ? (int) $selectedLicenseId : 0;
$selectedAssignmentId = isset($selectedAssignmentId) ? (int) $selectedAssignmentId : 0;

$selectedLicense = null;
if ($selectedLicenseId > 0) {
    foreach ($licenses as $license) {
        if ((int) ($license['id'] ?? 0) === $selectedLicenseId) {
            $selectedLicense = $license;
            break;
        }
    }
}

$selectedAssignment = null;
if ($selectedAssignmentId > 0) {
    foreach ($assignments as $assignment) {
        if ((int) ($assignment['id'] ?? 0) === $selectedAssignmentId) {
            $selectedAssignment = $assignment;
            break;
        }
    }
}

$formatDate = static function (?string $value, string $pattern = 'd/m/Y H:i'): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $timestamp = strtotime($value);
    return $timestamp !== false ? date($pattern, $timestamp) : '—';
};
?>
<section class="page">
    <header class="page__header">
        <h2>Licenze</h2>
        <p>Gestisci le licenze e le assegnazioni multi-tenant dal pannello amministratore.</p>
    </header>

    <?php if ($feedback): ?>
        <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
            <p><?= htmlspecialchars($feedback['message'] ?? 'Operazione completata.') ?></p>
            <?php if (!empty($feedback['error'])): ?>
                <p><?= htmlspecialchars((string) $feedback['error']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($licenseGeneratedCode): ?>
        <div class="alert alert--success">
            <p>Codice licenza generato: <strong><?= htmlspecialchars($licenseGeneratedCode['code']) ?></strong></p>
            <?php if (!empty($licenseGeneratedCode['label'])): ?>
                <p>Etichetta: <?= htmlspecialchars((string) $licenseGeneratedCode['label']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <?php if ($selectedAssignment): ?>
        <?php
            $assignmentPaidAt = $selectedAssignment['renewal_paid_at'] ?? null;
            $assignmentPaid = !empty($assignmentPaidAt);
        ?>
        <section class="page__section" id="assignment-detail">
            <header class="section__header">
                <h3>Dettaglio assegnazione licenza</h3>
            </header>
            <div class="card">
                <div class="card__header">
                    <h3><?= htmlspecialchars((string) ($selectedAssignment['tenant_name'] ?? '')) ?></h3>
                    <span class="badge badge--<?= $assignmentPaid ? 'success' : 'muted' ?>">
                        <?= $assignmentPaid ? 'Pagata' : 'Da pagare' ?>
                    </span>
                </div>
                <p class="card__meta">
                    <?= htmlspecialchars((string) ($selectedAssignment['license_code'] ?? '')) ?>
                    <?= !empty($selectedAssignment['license_label']) ? ' - ' . htmlspecialchars((string) $selectedAssignment['license_label']) : '' ?>
                </p>
                <ul class="card__list">
                    <li>Assegnata: <?= htmlspecialchars($formatDate($selectedAssignment['assigned_at'] ?? null, 'd/m/Y H:i')) ?></li>
                    <li>Scadenza: <?= htmlspecialchars($formatDate($selectedAssignment['license_expires_at'] ?? null, 'd/m/Y')) ?></li>
                    <li>Max utenti: <?= htmlspecialchars($selectedAssignment['max_users_override'] ?? 'Default licenza') ?></li>
                </ul>
                <form method="post" class="form" style="margin-top:12px;">
                    <input type="hidden" name="action" value="update_assignment_payment">
                    <input type="hidden" name="assignment_id" value="<?= (int) ($selectedAssignment['id'] ?? 0) ?>">
                    <div class="form__group">
                        <label for="payment_status">Quota adesione</label>
                        <select id="payment_status" name="payment_status" required>
                            <option value="unpaid" <?= !$assignmentPaid ? 'selected' : '' ?>>Da pagare</option>
                            <option value="paid" <?= $assignmentPaid ? 'selected' : '' ?>>Pagata</option>
                        </select>
                    </div>
                    <footer class="form__footer">
                        <button type="submit" class="btn btn--primary">Salva stato</button>
                    </footer>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($selectedLicense): ?>
        <?php
            $selectedTerm = isset($selectedLicense['term_months']) && (int) $selectedLicense['term_months'] > 0
                ? (int) $selectedLicense['term_months'] . ' mesi'
                : '—';
        ?>
        <section class="page__section" id="license-detail">
            <header class="section__header">
                <h3>Dettaglio licenza</h3>
            </header>
            <div class="card">
                <div class="card__header">
                    <h3><?= htmlspecialchars((string) ($selectedLicense['code'] ?? '')) ?></h3>
                    <span class="badge badge--<?= (int) ($selectedLicense['is_active'] ?? 0) === 1 ? 'success' : 'muted' ?>">
                        <?= (int) ($selectedLicense['is_active'] ?? 0) === 1 ? 'Attiva' : 'Disattiva' ?>
                    </span>
                </div>
                <p class="card__meta">
                    <?= !empty($selectedLicense['label']) ? htmlspecialchars((string) $selectedLicense['label']) : '—' ?>
                </p>
                <ul class="card__list">
                    <li>Max utenti: <?= (int) ($selectedLicense['max_users'] ?? 1) ?></li>
                    <li>Durata: <?= htmlspecialchars($selectedTerm) ?></li>
                    <li>Scadenza: <?= htmlspecialchars($formatDate($selectedLicense['expires_at'] ?? null, 'd/m/Y')) ?></li>
                </ul>
            </div>
        </section>
    <?php endif; ?>

    <section class="page__section">
        <header class="section__header">
            <h3>Crea licenza</h3>
        </header>
        <form method="post" class="form">
            <input type="hidden" name="action" value="create_license">
            <div class="form__grid">
                <div class="form__group">
                    <label for="license_label">Etichetta</label>
                    <input type="text" name="license_label" id="license_label" placeholder="Piano Business, Gold...">
                </div>
                <div class="form__group">
                    <label for="license_max_users">Numero massimo utenti *</label>
                    <input type="number" min="1" step="1" name="license_max_users" id="license_max_users" value="1" required>
                </div>
                <div class="form__group">
                    <label for="license_term_months">Durata licenza</label>
                    <select name="license_term_months" id="license_term_months" required>
                        <option value="12">12 mesi</option>
                        <option value="24">24 mesi</option>
                        <option value="36">36 mesi</option>
                    </select>
                </div>
            </div>
            <footer class="form__footer">
                <button type="submit" class="btn btn--primary">Crea licenza</button>
            </footer>
        </form>
    </section>

    <section class="page__section">
        <header class="section__header">
            <h3>Licenze disponibili</h3>
        </header>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Codice</th>
                        <th>Etichetta</th>
                        <th>Max utenti</th>
                        <th>Durata</th>
                        <th>Scadenza</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($licenses === []): ?>
                        <tr><td colspan="7">Nessuna licenza creata.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($licenses as $license): ?>
                        <?php
                            $termMonths = isset($license['term_months']) && (int) $license['term_months'] > 0
                                ? (int) $license['term_months'] . ' mesi'
                                : '—';
                        ?>
                        <tr id="license-<?= (int) $license['id'] ?>" class="<?= $selectedLicenseId === (int) $license['id'] ? 'highlight' : '' ?>">
                            <td><?= htmlspecialchars((string) $license['code']) ?></td>
                            <td><?= $license['label'] ? htmlspecialchars((string) $license['label']) : '—' ?></td>
                            <td><?= (int) ($license['max_users'] ?? 1) ?></td>
                            <td><?= htmlspecialchars($termMonths) ?></td>
                            <td><?= htmlspecialchars($formatDate($license['expires_at'] ?? null, 'd/m/Y')) ?></td>
                            <td>
                                <span class="badge badge--<?= (int) $license['is_active'] === 1 ? 'success' : 'muted' ?>">
                                    <?= (int) $license['is_active'] === 1 ? 'Attiva' : 'Disattiva' ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" class="table-actions">
                                    <input type="hidden" name="action" value="toggle_license">
                                    <input type="hidden" name="license_id" value="<?= (int) $license['id'] ?>">
                                    <input type="hidden" name="enabled" value="<?= (int) $license['is_active'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn--secondary">
                                        <?= (int) $license['is_active'] === 1 ? 'Disattiva' : 'Attiva' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="page__section">
        <header class="section__header">
            <h3>Assegna licenza a tenant</h3>
        </header>
        <form method="post" class="form">
            <input type="hidden" name="action" value="assign_license">
            <div class="form__grid">
                <div class="form__group">
                    <label for="assign_tenant">Tenant</label>
                    <select name="tenant_id" id="assign_tenant" required>
                        <option value="">Seleziona tenant</option>
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?= (int) $tenant['id'] ?>">
                                <?= htmlspecialchars((string) $tenant['name']) ?> (<?= htmlspecialchars((string) $tenant['slug']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="assign_license">Licenza</label>
                    <select name="license_id" id="assign_license" required>
                        <option value="">Seleziona licenza</option>
                        <?php foreach ($licenses as $license): ?>
                            <option value="<?= (int) $license['id'] ?>">
                                <?= htmlspecialchars((string) $license['code']) ?><?= $license['label'] ? ' - ' . htmlspecialchars((string) $license['label']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="assign_max_users">Max utenti (override)</label>
                    <input type="number" min="1" step="1" name="max_users_override" id="assign_max_users" placeholder="Usa max licenza">
                </div>
                <div class="form__group">
                    <label for="assign_notes">Note</label>
                    <input type="text" name="assignment_notes" id="assign_notes" placeholder="Note opzionali">
                </div>
            </div>
            <footer class="form__footer">
                <button type="submit" class="btn btn--primary">Assegna licenza</button>
            </footer>
        </form>
    </section>

    <section class="page__section">
        <header class="section__header">
            <h3>Assegnazioni licenze</h3>
        </header>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Licenza</th>
                        <th>Max utenti</th>
                        <th>Note</th>
                        <th>Assegnata</th>
                        <th>Scadenza</th>
                        <th>Quota adesione</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assignments === []): ?>
                        <tr><td colspan="9">Nessuna assegnazione registrata.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <?php
                            $override = $assignment['max_users_override'] ?? null;
                            $maxUsersLabel = $override !== null ? (string) $override : 'Default licenza';
                            $isRevoked = !empty($assignment['revoked_at']);
                            $renewalPaidAt = $assignment['renewal_paid_at'] ?? null;
                        ?>
                        <tr class="<?= $selectedAssignmentId === (int) ($assignment['id'] ?? 0) ? 'highlight' : '' ?>">
                            <td>
                                <?= htmlspecialchars((string) $assignment['tenant_name']) ?><br>
                                <small><?= htmlspecialchars((string) $assignment['tenant_slug']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars((string) $assignment['license_code']) ?><br>
                                <small><?= htmlspecialchars((string) ($assignment['license_label'] ?? '')) ?></small>
                                <?php if (!empty($assignment['license_term_months'])): ?>
                                    <br><small><?= (int) $assignment['license_term_months'] ?> mesi</small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($maxUsersLabel) ?></td>
                            <td><?= $assignment['notes'] ? htmlspecialchars((string) $assignment['notes']) : '—' ?></td>
                            <td><?= htmlspecialchars($formatDate($assignment['assigned_at'] ?? null, 'd/m/Y H:i')) ?></td>
                            <td><?= htmlspecialchars($formatDate($assignment['license_expires_at'] ?? null, 'd/m/Y')) ?></td>
                            <td>
                                <?php if ($renewalPaidAt): ?>
                                    <span class="badge badge--success">Pagata</span><br>
                                    <small><?= htmlspecialchars($formatDate($renewalPaidAt, 'd/m/Y')) ?></small>
                                <?php else: ?>
                                    <span class="badge badge--muted">Da pagare</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge--<?= $isRevoked ? 'muted' : 'success' ?>">
                                    <?= $isRevoked ? 'Revocata' : 'Attiva' ?>
                                </span>
                            </td>
                            <td>
                                <a class="btn btn--ghost btn--small" href="index.php?page=licenses&assignment_id=<?= (int) $assignment['id'] ?>#assignment-detail">Dettaglio</a>
                                <?php if (!$isRevoked): ?>
                                    <form method="post" class="table-actions">
                                        <input type="hidden" name="action" value="revoke_tenant_license">
                                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                                        <button type="submit" class="btn btn--secondary">Revoca</button>
                                    </form>
                                    <form method="post" class="table-actions">
                                        <input type="hidden" name="action" value="renew_tenant_license">
                                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                                        <button type="submit" class="btn btn--primary">Rinnova</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
