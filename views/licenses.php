<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $tenants
 * @var array<int, array<string, mixed>> $licenses
 * @var array<int, array<string, mixed>> $assignments
 * @var array{success:bool,message:string,error?:string}|null $feedback
 * @var array{code:string,label?:string}|null $licenseGeneratedCode
 */
$pageTitle = $pageTitle ?? 'Licenze & Tenant';
$tenants = isset($tenants) && is_array($tenants) ? $tenants : [];
$licenses = isset($licenses) && is_array($licenses) ? $licenses : [];
$assignments = isset($assignments) && is_array($assignments) ? $assignments : [];
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$licenseGeneratedCode = isset($licenseGeneratedCode) && is_array($licenseGeneratedCode) ? $licenseGeneratedCode : null;

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
        <h2>Licenze &amp; Tenant</h2>
        <p>Gestisci i tenant, le licenze e le assegnazioni multi-tenant dal pannello amministratore.</p>
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

    <section class="page__section">
        <header class="section__header">
            <h3>Crea tenant</h3>
        </header>
        <form method="post" class="form">
            <input type="hidden" name="action" value="create_tenant">
            <div class="form__grid">
                <div class="form__group">
                    <label for="tenant_name">Nome *</label>
                    <input type="text" name="tenant_name" id="tenant_name" required>
                </div>
                <div class="form__group">
                    <label for="tenant_slug">Slug *</label>
                    <input type="text" name="tenant_slug" id="tenant_slug" required>
                </div>
                <div class="form__group">
                    <label for="tenant_email">Email contatto</label>
                    <input type="email" name="tenant_email" id="tenant_email">
                </div>
                <div class="form__group">
                    <label for="tenant_phone">Telefono</label>
                    <input type="text" name="tenant_phone" id="tenant_phone">
                </div>
            </div>
            <footer class="form__footer">
                <button type="submit" class="btn btn--primary">Crea tenant</button>
            </footer>
        </form>
    </section>

    <section class="page__section">
        <header class="section__header">
            <h3>Aggiorna tenant</h3>
        </header>
        <form method="post" class="form">
            <input type="hidden" name="action" value="update_tenant">
            <div class="form__grid">
                <div class="form__group">
                    <label for="update_tenant_id">Tenant</label>
                    <select name="tenant_id" id="update_tenant_id" required>
                        <option value="">Seleziona tenant</option>
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?= (int) $tenant['id'] ?>">
                                <?= htmlspecialchars((string) $tenant['name']) ?> (<?= htmlspecialchars((string) $tenant['slug']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="update_tenant_name">Nome *</label>
                    <input type="text" name="tenant_name" id="update_tenant_name" required>
                </div>
                <div class="form__group">
                    <label for="update_tenant_slug">Slug *</label>
                    <input type="text" name="tenant_slug" id="update_tenant_slug" required>
                </div>
                <div class="form__group">
                    <label for="update_tenant_email">Email contatto</label>
                    <input type="email" name="tenant_email" id="update_tenant_email">
                </div>
                <div class="form__group">
                    <label for="update_tenant_phone">Telefono</label>
                    <input type="text" name="tenant_phone" id="update_tenant_phone">
                </div>
            </div>
            <footer class="form__footer">
                <button type="submit" class="btn btn--secondary">Aggiorna tenant</button>
            </footer>
        </form>
    </section>

    <section class="page__section">
        <header class="section__header">
            <h3>Elenco tenant</h3>
        </header>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Contatti</th>
                        <th>Stato</th>
                        <th>Creato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tenants === []): ?>
                        <tr><td colspan="6">Nessun tenant configurato.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $tenant['name']) ?></td>
                            <td><?= htmlspecialchars((string) $tenant['slug']) ?></td>
                            <td>
                                <?= $tenant['contact_email'] ? htmlspecialchars((string) $tenant['contact_email']) : '—' ?><br>
                                <?= $tenant['contact_phone'] ? htmlspecialchars((string) $tenant['contact_phone']) : '—' ?>
                            </td>
                            <td>
                                <span class="badge badge--<?= (int) $tenant['is_active'] === 1 ? 'success' : 'muted' ?>">
                                    <?= (int) $tenant['is_active'] === 1 ? 'Attivo' : 'Disattivo' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($formatDate($tenant['created_at'] ?? null, 'd/m/Y')) ?></td>
                            <td>
                                <form method="post" class="table-actions">
                                    <input type="hidden" name="action" value="toggle_tenant">
                                    <input type="hidden" name="tenant_id" value="<?= (int) $tenant['id'] ?>">
                                    <input type="hidden" name="enabled" value="<?= (int) $tenant['is_active'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn--secondary">
                                        <?= (int) $tenant['is_active'] === 1 ? 'Disattiva' : 'Attiva' ?>
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
                    <label for="license_expires_at">Scadenza</label>
                    <input type="date" name="license_expires_at" id="license_expires_at">
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
                        <th>Scadenza</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($licenses === []): ?>
                        <tr><td colspan="6">Nessuna licenza creata.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($licenses as $license): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $license['code']) ?></td>
                            <td><?= $license['label'] ? htmlspecialchars((string) $license['label']) : '—' ?></td>
                            <td><?= (int) ($license['max_users'] ?? 1) ?></td>
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
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assignments === []): ?>
                        <tr><td colspan="7">Nessuna assegnazione registrata.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <?php
                            $override = $assignment['max_users_override'] ?? null;
                            $maxUsersLabel = $override !== null ? (string) $override : 'Default licenza';
                            $isRevoked = !empty($assignment['revoked_at']);
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars((string) $assignment['tenant_name']) ?><br>
                                <small><?= htmlspecialchars((string) $assignment['tenant_slug']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars((string) $assignment['license_code']) ?><br>
                                <small><?= htmlspecialchars((string) ($assignment['license_label'] ?? '')) ?></small>
                            </td>
                            <td><?= htmlspecialchars($maxUsersLabel) ?></td>
                            <td><?= $assignment['notes'] ? htmlspecialchars((string) $assignment['notes']) : '—' ?></td>
                            <td><?= htmlspecialchars($formatDate($assignment['assigned_at'] ?? null)) ?></td>
                            <td>
                                <span class="badge badge--<?= $isRevoked ? 'muted' : 'success' ?>">
                                    <?= $isRevoked ? 'Revocata' : 'Attiva' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!$isRevoked): ?>
                                    <form method="post" class="table-actions">
                                        <input type="hidden" name="action" value="revoke_tenant_license">
                                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                                        <button type="submit" class="btn btn--secondary">Revoca</button>
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
