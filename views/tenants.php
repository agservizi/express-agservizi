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
                <div class="form__group">
                    <label for="create_company_country">Paese P.IVA</label>
                    <input type="text" name="company_country" id="create_company_country" maxlength="2" placeholder="IT" value="IT">
                </div>
                <div class="form__group">
                    <label for="create_vat_number">P.IVA</label>
                    <div class="form__inline">
                        <input type="text" name="vat_number" id="create_vat_number" placeholder="IT12345678901">
                        <button type="button" class="btn btn--ghost btn--small" data-vies-button="create">Cerca VIES</button>
                    </div>
                    <small id="create_vies_status" class="vies-status"></small>
                </div>
                <div class="form__group">
                    <label for="create_company_name">Ragione sociale</label>
                    <input type="text" name="company_name" id="create_company_name">
                </div>
                <div class="form__group">
                    <label for="create_company_address">Indirizzo sede</label>
                    <textarea name="company_address" id="create_company_address"></textarea>
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
                <div class="form__group">
                    <label for="update_company_country">Paese P.IVA</label>
                    <input type="text" name="company_country" id="update_company_country" maxlength="2" placeholder="IT" value="IT">
                </div>
                <div class="form__group">
                    <label for="update_vat_number">P.IVA</label>
                    <div class="form__inline">
                        <input type="text" name="vat_number" id="update_vat_number" placeholder="IT12345678901">
                        <button type="button" class="btn btn--ghost btn--small" data-vies-button="update">Cerca VIES</button>
                    </div>
                    <small id="update_vies_status" class="vies-status"></small>
                </div>
                <div class="form__group">
                    <label for="update_company_name">Ragione sociale</label>
                    <input type="text" name="company_name" id="update_company_name">
                </div>
                <div class="form__group">
                    <label for="update_company_address">Indirizzo sede</label>
                    <textarea name="company_address" id="update_company_address"></textarea>
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
                                <?= $tenant['contact_phone'] ? htmlspecialchars((string) $tenant['contact_phone']) : '—' ?><br>
                                <?= !empty($tenant['company_name']) ? htmlspecialchars((string) $tenant['company_name']) : '—' ?><br>
                                <?php if (!empty($tenant['vat_number'])): ?>
                                    <?= 'P.IVA ' . htmlspecialchars((string) ($tenant['company_country'] ?? '')) . htmlspecialchars((string) $tenant['vat_number']) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
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

<script>
    (() => {
        const lookupUrl = 'index.php?page=vies_lookup';
        const buttons = document.querySelectorAll('[data-vies-button]');

        const setStatus = (statusEl, message, variant) => {
            if (!statusEl) {
                return;
            }
            statusEl.textContent = message || '';
            statusEl.classList.remove('is-success', 'is-error');
            if (variant) {
                statusEl.classList.add(variant === 'success' ? 'is-success' : 'is-error');
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', async () => {
                const prefix = button.dataset.viesButton;
                const vatInput = document.getElementById(`${prefix}_vat_number`);
                const countryInput = document.getElementById(`${prefix}_company_country`);
                const nameInput = document.getElementById(`${prefix}_company_name`);
                const addressInput = document.getElementById(`${prefix}_company_address`);
                const statusEl = document.getElementById(`${prefix}_vies_status`);

                if (!vatInput || !countryInput || !nameInput || !addressInput) {
                    return;
                }

                const vat = vatInput.value.trim();
                const country = countryInput.value.trim();
                if (!vat || !country) {
                    setStatus(statusEl, 'Inserisci paese e P.IVA.', 'error');
                    return;
                }

                setStatus(statusEl, 'Verifica in corso…');
                try {
                    const response = await fetch(lookupUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ vat, country }),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        setStatus(statusEl, data.message || 'Errore durante la verifica.', 'error');
                        return;
                    }
                    if (!data.valid) {
                        setStatus(statusEl, 'P.IVA non valida su VIES.', 'error');
                        return;
                    }

                    if (data.company_name) {
                        nameInput.value = data.company_name;
                    }
                    if (data.company_address) {
                        addressInput.value = data.company_address;
                    }
                    if (data.country) {
                        countryInput.value = data.country;
                    }
                    if (data.vat_number) {
                        vatInput.value = data.vat_number;
                    }

                    setStatus(statusEl, 'Dati recuperati da VIES.', 'success');
                } catch (error) {
                    setStatus(statusEl, 'Impossibile contattare VIES.', 'error');
                }
            });
        });
    })();
</script>
