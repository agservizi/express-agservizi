<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $tenants
 * @var array{success:bool,message:string,error?:string}|null $feedback
 */
$pageTitle = $pageTitle ?? 'Tenant';
$tenants = isset($tenants) && is_array($tenants) ? $tenants : [];
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;

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
        <h2>Tenant</h2>
        <p>Gestisci i tenant dal pannello amministratore.</p>
    </header>

    <?php if ($feedback): ?>
        <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
            <p><?= htmlspecialchars($feedback['message'] ?? 'Operazione completata.') ?></p>
            <?php if (!empty($feedback['error'])): ?>
                <p><?= htmlspecialchars((string) $feedback['error']) ?></p>
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
                                <form method="post" class="table-actions">
                                    <input type="hidden" name="action" value="resend_tenant_credentials">
                                    <input type="hidden" name="tenant_id" value="<?= (int) $tenant['id'] ?>">
                                    <button type="submit" class="btn btn--primary">
                                        Invia credenziali
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
