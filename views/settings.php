<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $providerInsights
 * @var array<int, array<string, mixed>> $stockAlerts
 * @var array<int, array<string, mixed>> $roles
 * @var array<int, array<string, mixed>> $operators
 * @var array<int, array<string, mixed>> $providers
 * @var array<int, array<string, mixed>> $discountCampaigns
 * @var array<int, array<string, mixed>> $discountCodes
 * @var array<int, array<string, mixed>> $fiscalProducts
 * @var array<int, array<string, mixed>> $auditLogs
 * @var array{page:int, per_page:int, total:int, pages:int, has_prev:bool, has_next:bool} $auditPagination
 * @var callable $buildAuditPageUrl
 * @var array{success:bool, message:string, error?:string}|null $feedback
 * @var bool $isAdmin
 * @var bool $auditOpen
 * @var array<string, mixed>|null $operatorEdit
 * @var array<string, mixed>|null $operatorEditForm
 * @var bool|null $operatorsOpen
 * @var bool|null $providersOpen
 * @var bool $fiscalOpen
 * @var bool $ssoEnabled
 * @var array<int, array<string, mixed>> $ssoClients
 * @var array{success:bool, message:string, error?:string}|null $ssoFeedback
 * @var array{client_id:string, client_secret:string}|null $ssoSecretPreview
 * @var bool $ssoOpen
 * @var int $ssoTokenTtl
 * @var array<string, mixed> $pdaSettings
 * @var array{success:bool,message:string,errors?:array<int,string>}|null $pdaFeedback
 * @var bool $pdaOpen
 * @var array<string, mixed> $receiptSettings
 * @var array{success:bool,message:string,errors?:array<int,string>}|null $receiptFeedback
 * @var bool $receiptOpen
 * @var array<int, array<string, mixed>> $energyProviders
 * @var array{last_run?:int,last_started?:int,last_status?:string,last_message?:string,last_output?:string} $energyOffersImportStatus
 * @var array{success:bool,message:string,errors?:array<int,string>}|null $energyFeedback
 * @var bool $energyOpen
 * @var array<int, array<string, mixed>> $tenants
 * @var array<int, array<string, mixed>> $licenses
 * @var array{success:bool,message:string,error?:string}|null $licenseFeedback
 * @var array{code:string,label?:string}|null $licenseGeneratedCode
 * @var bool $licensesOpen
 * @var array<int, array<string, mixed>> $licenseActivations
 * @var int $licenseFocusId
 * @var bool $canManageTenantSettings
 * @var int $currentTenantId
 * @var bool $discountCodesOpen
 */
$pageTitle = $pageTitle ?? 'Impostazioni';
$roles = $roles ?? [];
$operators = $operators ?? [];
$providers = $providers ?? [];
$discountCampaigns = $discountCampaigns ?? [];
$discountCodes = $discountCodes ?? [];
$fiscalProducts = $fiscalProducts ?? [];
$isAdmin = $isAdmin ?? false;
$canManageTenantSettings = $canManageTenantSettings ?? $isAdmin;
$currentTenantId = isset($currentTenantId) ? (int) $currentTenantId : (int) ($currentUser['tenant_id'] ?? 1);
$operatorEdit = $operatorEdit ?? null;
$operatorEditForm = isset($operatorEditForm) && is_array($operatorEditForm) ? $operatorEditForm : null;
$auditLogs = $auditLogs ?? [];
$auditPagination = $auditPagination ?? [
    'page' => 1,
    'per_page' => 10,
    'total' => count($auditLogs),
    'pages' => 1,
    'has_prev' => false,
    'has_next' => false,
];
$buildAuditPageUrl = $buildAuditPageUrl ?? static fn(int $pageNo): string => 'index.php?page=settings&audit_page=' . max(1, $pageNo);
$auditOpen = $auditOpen ?? false;
$currentUserId = isset($currentUser['id']) ? (int) $currentUser['id'] : 0;
$operatorsOpenProp = $operatorsOpen ?? null;
$providersOpenProp = $providersOpen ?? null;
$inventoryOpen = $feedback !== null && isset($feedback['message']) && stripos((string) $feedback['message'], 'soglia') !== false;
$operatorsOpen = is_bool($operatorsOpenProp)
    ? $operatorsOpenProp
    : ($canManageTenantSettings && $feedback !== null && ($feedback['success'] ?? false) === false && ! $inventoryOpen);
$providersOpen = is_bool($providersOpenProp)
    ? $providersOpenProp
    : ($canManageTenantSettings && $feedback !== null && stripos((string) ($feedback['message'] ?? ''), 'gestore') !== false);
$ssoClients = isset($ssoClients) && is_array($ssoClients) ? $ssoClients : [];
$ssoFeedback = isset($ssoFeedback) && is_array($ssoFeedback) ? $ssoFeedback : null;
$ssoSecretPreview = isset($ssoSecretPreview) && is_array($ssoSecretPreview) ? $ssoSecretPreview : null;
$ssoOpen = isset($ssoOpen) ? (bool) $ssoOpen : false;
$ssoTokenTtl = isset($ssoTokenTtl) ? (int) $ssoTokenTtl : 0;
$pdaSettings = isset($pdaSettings) && is_array($pdaSettings) ? $pdaSettings : [];
$pdaFeedback = isset($pdaFeedback) && is_array($pdaFeedback) ? $pdaFeedback : null;
$pdaOpen = isset($pdaOpen) ? (bool) $pdaOpen : false;
$pdaOpen = $pdaOpen || $pdaFeedback !== null;
$pdaOcr = $pdaSettings['ocr'] ?? ['enabled' => true, 'min_chars' => 200, 'lang' => 'ita'];
$pdaTemplatesJson = $pdaSettings['templates_json'] ?? '{}';
$receiptSettings = isset($receiptSettings) && is_array($receiptSettings) ? $receiptSettings : [];
$receiptFeedback = isset($receiptFeedback) && is_array($receiptFeedback) ? $receiptFeedback : null;
$receiptOpen = isset($receiptOpen) ? (bool) $receiptOpen : false;
$receiptOpen = $receiptOpen || $receiptFeedback !== null;
$energyProviders = isset($energyProviders) && is_array($energyProviders) ? $energyProviders : [];
$energyOffersImportStatus = isset($energyOffersImportStatus) && is_array($energyOffersImportStatus) ? $energyOffersImportStatus : [];
$energyFeedback = isset($energyFeedback) && is_array($energyFeedback) ? $energyFeedback : null;
$energyOpen = isset($energyOpen) ? (bool) $energyOpen : false;
$energyOpen = $energyOpen || $energyFeedback !== null;
$tenants = isset($tenants) && is_array($tenants) ? $tenants : [];
$licenses = isset($licenses) && is_array($licenses) ? $licenses : [];
$licenseFeedback = isset($licenseFeedback) && is_array($licenseFeedback) ? $licenseFeedback : null;
$licenseGeneratedCode = isset($licenseGeneratedCode) && is_array($licenseGeneratedCode) ? $licenseGeneratedCode : null;
$licensesOpen = isset($licensesOpen) ? (bool) $licensesOpen : false;
$licensesOpen = $licensesOpen || $licenseFeedback !== null || $licenseGeneratedCode !== null;
$licenseActivations = isset($licenseActivations) && is_array($licenseActivations) ? $licenseActivations : [];
$licenseFocusId = isset($licenseFocusId) ? (int) $licenseFocusId : 0;
$enabledModules = isset($enabledModules) && is_array($enabledModules) ? $enabledModules : null;
$moduleEnabled = static function (string $module) use ($enabledModules): bool {
    if ($enabledModules === null) {
        return true;
    }
    return in_array($module, $enabledModules, true);
};
$receiptHeaderLines = $receiptSettings['header_lines'] ?? [];
if (!is_array($receiptHeaderLines)) {
    $receiptHeaderLines = [];
}
$receiptHeaderText = implode("\n", array_filter(array_map('trim', $receiptHeaderLines)));
$receiptDocumentTitle = (string) ($receiptSettings['document_title'] ?? 'DOCUMENTO GESTIONALE');
$receiptDocumentNumberTemplate = (string) ($receiptSettings['document_number_template'] ?? '{{document_title}} #{{sale_id}}');
$receiptThanksText = (string) ($receiptSettings['thanks_text'] ?? 'Grazie per il tuo acquisto!');
$receiptFooterText = (string) ($receiptSettings['footer_text'] ?? '');
$receiptLabels = $receiptSettings['labels'] ?? [];
if (!is_array($receiptLabels)) {
    $receiptLabels = [];
}
$receiptStatusLabels = $receiptSettings['status_labels'] ?? [];
if (!is_array($receiptStatusLabels)) {
    $receiptStatusLabels = [];
}
if (!$fiscalOpen && $feedback !== null) {
    $messagesToInspect = [];
    if (isset($feedback['message'])) {
        $messagesToInspect[] = (string) $feedback['message'];
    }
    if (!empty($feedback['error'])) {
        $messagesToInspect[] = (string) $feedback['error'];
    }
    if (!empty($feedback['errors']) && is_array($feedback['errors'])) {
        foreach ($feedback['errors'] as $errorText) {
            $messagesToInspect[] = (string) $errorText;
        }
    }
    foreach ($messagesToInspect as $messagePart) {
        if (stripos($messagePart, 'iva') !== false || stripos($messagePart, 'fisc') !== false) {
            $fiscalOpen = true;
            break;
        }
    }
}
$campaignsOpen = $feedback !== null && isset($feedback['message']) && strpos((string) $feedback['message'], 'Campagna') !== false;
$discountCodesOpen = isset($discountCodesOpen) ? (bool) $discountCodesOpen : false;
if (!$discountCodesOpen && $feedback !== null && isset($feedback['message'])) {
    $discountCodesOpen = stripos((string) $feedback['message'], 'codice sconto') !== false;
}
$ssoOpen = $ssoOpen || $ssoFeedback !== null;
$auditCurrentPage = max(1, (int) ($auditPagination['page'] ?? 1));
$totalAuditPages = max(1, (int) ($auditPagination['pages'] ?? 1));
$totalAuditEvents = max(0, (int) ($auditPagination['total'] ?? count($auditLogs)));
$hasAuditPrev = (bool) ($auditPagination['has_prev'] ?? ($auditCurrentPage > 1));
$hasAuditNext = (bool) ($auditPagination['has_next'] ?? ($auditCurrentPage < $totalAuditPages));
?>
<section class="page page--settings">
    <header class="page__header">
        <h2>Impostazioni</h2>
        <p>Configura il gestionale per categorie: magazzino, alert e anagrafiche operatori.</p>
    </header>

    <?php if ($feedback !== null): ?>
        <section class="page__section">
            <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                <p><?= htmlspecialchars($feedback['message']) ?></p>
                <?php if (!empty($feedback['error'])): ?>
                    <p class="muted">Dettaglio: <?= htmlspecialchars((string) $feedback['error']) ?></p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="settings-accordion" data-accordion-group>
        <?php if ($moduleEnabled('sim_stock')): ?>
        <article class="settings-accordion__item" data-accordion data-open="<?= $inventoryOpen ? 'true' : 'false' ?>">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $inventoryOpen ? 'true' : 'false' ?>">
                <span class="settings-accordion__title">Magazzino e soglie</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content <?= $inventoryOpen ? '' : 'hidden' ?>>
                <?php if (!$canManageTenantSettings): ?>
                    <p class="muted">Solo i responsabili del tenant possono modificare le soglie.</p>
                <?php endif; ?>
                <p class="muted">Definisci il livello minimo di SIM disponibili per ciascun operatore. Al raggiungimento della soglia viene generato un alert visibile in dashboard e via email.</p>
                <div class="table-wrapper table-wrapper--embedded">
                    <table class="table table--compact">
                        <thead>
                            <tr>
                                <th>Operatore</th>
                                <th>Soglia minima</th>
                                <th>Disponibili</th>
                                <th>Media vendite / giorno</th>
                                <th>Copertura stimata</th>
                                <th>Suggerimento riordino</th>
                                <th>Ultimo movimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($providerInsights)): ?>
                                <tr><td colspan="7">Nessun operatore configurato.</td></tr>
                            <?php else: ?>
                                <?php foreach ($providerInsights as $insight): ?>
                                    <?php $isLow = !empty($insight['below_threshold']); ?>
                                    <tr class="<?= $isLow ? 'table-row--warning' : '' ?>">
                                        <td><?= htmlspecialchars((string) $insight['provider_name']) ?></td>
                                        <td>
                                            <form method="post" class="inline-form">
                                                <input type="hidden" name="action" value="update_threshold">
                                                <input type="hidden" name="provider_id" value="<?= (int) $insight['provider_id'] ?>">
                                                <div class="table-field table-field--compact">
                                                    <input type="number" min="0" name="reorder_threshold" value="<?= (int) $insight['threshold'] ?>" class="table-field__input table-field__input--number">
                                                </div>
                                                <button type="submit" class="btn btn--secondary btn--small">Salva</button>
                                            </form>
                                        </td>
                                        <td><?= (int) $insight['current_stock'] ?></td>
                                        <td><?= number_format((float) $insight['average_daily_sales'], 2, ',', '.') ?></td>
                                        <td>
                                            <?php if ($insight['days_cover'] === null): ?>
                                                n/d
                                            <?php else: ?>
                                                <?= number_format((float) $insight['days_cover'], 1, ',', '.') ?> giorni
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ((int) $insight['suggested_reorder'] > 0): ?>
                                                Riordina almeno <?= (int) $insight['suggested_reorder'] ?> SIM
                                            <?php else: ?>
                                                Nessun riordino urgente
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($insight['last_movement'])): ?>
                                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $insight['last_movement']))) ?>
                                            <?php else: ?>
                                                n/d
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($moduleEnabled('products')): ?>
        <article class="settings-accordion__item" data-accordion data-open="<?= $fiscalOpen ? 'true' : 'false' ?>">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $fiscalOpen ? 'true' : 'false' ?>">
                <span class="settings-accordion__title">Impostazioni fiscali</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content <?= $fiscalOpen ? '' : 'hidden' ?>>
                <?php if (!$canManageTenantSettings): ?>
                    <p class="muted">Solo i responsabili del tenant possono gestire le impostazioni fiscali dei prodotti.</p>
                <?php else: ?>
                    <p class="muted">Imposta aliquota e codice IVA per ciascun prodotto: i valori vengono riportati automaticamente nello scontrino.</p>
                    <?php if (empty($fiscalProducts)): ?>
                        <p class="muted">Nessun prodotto a catalogo. Aggiungi articoli dalla sezione Prodotti.</p>
                    <?php else: ?>
                        <div class="table-wrapper table-wrapper--embedded">
                            <table class="table table--compact">
                                <thead>
                                    <tr>
                                        <th>Prodotto</th>
                                        <th>Aliquota IVA (%)</th>
                                        <th>Codice IVA</th>
                                        <th>Stato</th>
                                        <th class="table__col--actions">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fiscalProducts as $product): ?>
                                        <?php
                                            $productId = (int) ($product['id'] ?? 0);
                                            $productName = trim((string) ($product['name'] ?? ''));
                                            $productSku = trim((string) ($product['sku'] ?? ''));
                                            $taxRateValue = number_format((float) ($product['tax_rate'] ?? 0.0), 2, '.', '');
                                            $vatCodeValue = isset($product['vat_code']) ? trim((string) $product['vat_code']) : '';
                                            $isActiveProduct = (int) ($product['is_active'] ?? 0) === 1;
                                            $formId = 'fiscal-form-' . $productId;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($productName !== '' ? $productName : 'Prodotto #' . $productId) ?></strong>
                                                <?php if ($productSku !== ''): ?>
                                                    <div class="muted">SKU <?= htmlspecialchars($productSku) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="table-field table-field--compact">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="100"
                                                        name="product_tax_rate"
                                                        value="<?= htmlspecialchars($taxRateValue) ?>"
                                                        class="table-field__input table-field__input--number"
                                                        required
                                                        form="<?= htmlspecialchars($formId) ?>"
                                                    >
                                                </div>
                                            </td>
                                            <td>
                                                <div class="table-field table-field--compact">
                                                    <input
                                                        type="text"
                                                        name="product_vat_code"
                                                        value="<?= htmlspecialchars($vatCodeValue) ?>"
                                                        maxlength="32"
                                                        class="table-field__input"
                                                        placeholder="Es. A22"
                                                        form="<?= htmlspecialchars($formId) ?>"
                                                    >
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $isActiveProduct ? 'badge--success' : 'badge--muted' ?>">
                                                    <?= $isActiveProduct ? 'Attivo' : 'Disattivato' ?>
                                                </span>
                                            </td>
                                            <td class="table__col--actions">
                                                <form method="post" class="inline-form" id="<?= htmlspecialchars($formId) ?>">
                                                    <input type="hidden" name="action" value="update_product_tax">
                                                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                                                    <button type="submit" class="btn btn--secondary btn--small">Salva</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($moduleEnabled('sim_stock')): ?>
        <article class="settings-accordion__item" data-accordion data-open="<?= $alertsOpen ? 'true' : 'false' ?>">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $alertsOpen ? 'true' : 'false' ?>">
                <span class="settings-accordion__title">Alert in corso</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content <?= $alertsOpen ? '' : 'hidden' ?>>
                <?php if (empty($stockAlerts)): ?>
                    <p class="muted">Nessun alert attivo al momento.</p>
                <?php else: ?>
                    <div class="alert-list">
                        <?php foreach ($stockAlerts as $alert): ?>
                            <article class="alert-card">
                                <header class="alert-card__header">
                                    <h4><?= htmlspecialchars((string) $alert['provider_name']) ?></h4>
                                    <span class="badge badge--warning">Sotto soglia</span>
                                </header>
                                <p class="alert-card__message"><?= htmlspecialchars((string) $alert['message']) ?></p>
                                <p class="alert-card__meta">Ultimo controllo: <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $alert['updated_at']))) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($moduleEnabled('offers')): ?>
        <article class="settings-accordion__item" data-accordion data-open="<?= $campaignsOpen ? 'true' : 'false' ?>">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $campaignsOpen ? 'true' : 'false' ?>">
                <span class="settings-accordion__title">Campagne sconto</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content <?= $campaignsOpen ? '' : 'hidden' ?>>
                <?php if (!$canManageTenantSettings): ?>
                    <p class="muted">Solo i responsabili del tenant possono gestire le campagne sconto.</p>
                <?php else: ?>
                    <div class="settings-operators">
                        <section class="settings-operators__panel">
                            <h4>Crea campagna</h4>
                            <form method="post" class="form settings-form">
                                <input type="hidden" name="action" value="create_discount_campaign">
                                <div class="settings-form__grid">
                                    <div class="settings-form__field">
                                        <label for="campaign_name">Nome</label>
                                        <input type="text" id="campaign_name" name="campaign_name" required>
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="campaign_type">Tipo sconto</label>
                                        <select id="campaign_type" name="campaign_type" required>
                                            <option value="fixed">Importo fisso</option>
                                            <option value="percent">Percentuale</option>
                                        </select>
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="campaign_value">Valore</label>
                                        <input type="number" id="campaign_value" name="campaign_value" min="0" step="0.01" required>
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="campaign_starts_at">Valido dal</label>
                                        <input type="date" id="campaign_starts_at" name="campaign_starts_at">
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="campaign_ends_at">Valido fino al</label>
                                        <input type="date" id="campaign_ends_at" name="campaign_ends_at">
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="campaign_description">Descrizione (opzionale)</label>
                                        <div class="table-field">
                                            <textarea id="campaign_description" name="campaign_description" rows="2" class="table-field__input" placeholder="Note per gli operatori..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn--primary">Salva campagna</button>
                            </form>
                        </section>

                        <section class="settings-operators__panel">
                            <h4>Campagne attive e archivio</h4>
                            <?php if (empty($discountCampaigns)): ?>
                                <p class="muted">Nessuna campagna configurata.</p>
                            <?php else: ?>
                                <div class="table-wrapper table-wrapper--embedded">
                                    <table class="table table--compact">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Tipo</th>
                                                <th>Valore</th>
                                                <th>Validità</th>
                                                <th>Stato</th>
                                                <th>Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($discountCampaigns as $campaign): ?>
                                                <?php
                                                    $isActive = (int) ($campaign['is_active'] ?? 0) === 1;
                                                    $type = (string) ($campaign['type'] ?? 'Fixed');
                                                    $value = (float) ($campaign['value'] ?? 0);
                                                    $starts = !empty($campaign['starts_at']) ? date('d/m/Y', strtotime((string) $campaign['starts_at'])) : null;
                                                    $ends = !empty($campaign['ends_at']) ? date('d/m/Y', strtotime((string) $campaign['ends_at'])) : null;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars((string) $campaign['name']) ?></strong>
                                                        <?php if (!empty($campaign['description'])): ?>
                                                            <div class="muted"><?= htmlspecialchars((string) $campaign['description']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $type === 'Percent' ? 'Percentuale' : 'Importo fisso' ?></td>
                                                    <td>
                                                        <?php if ($type === 'Percent'): ?>
                                                            <?= number_format($value, 2, ',', '.') ?>%
                                                        <?php else: ?>
                                                            € <?= number_format($value, 2, ',', '.') ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($starts === null && $ends === null): ?>
                                                            Sempre
                                                        <?php else: ?>
                                                            <?= $starts ?? 'n/d' ?> → <?= $ends ?? 'n/d' ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $isActive ? 'Attiva' : 'Disattivata' ?></td>
                                                    <td>
                                                        <form method="post" class="inline-form">
                                                            <input type="hidden" name="action" value="toggle_discount_campaign">
                                                            <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                                            <input type="hidden" name="target_status" value="<?= $isActive ? '0' : '1' ?>">
                                                            <button type="submit" class="btn btn--secondary btn--small">
                                                                <?= $isActive ? 'Disattiva' : 'Attiva' ?>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                <?php endif; ?>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($canManageTenantSettings): ?>
        <article class="settings-accordion__item" data-accordion data-open="<?= $discountCodesOpen ? 'true' : 'false' ?>">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $discountCodesOpen ? 'true' : 'false' ?>">
                <span class="settings-accordion__title">Codici sconto checkout</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content <?= $discountCodesOpen ? '' : 'hidden' ?>>
                <div class="settings-operators">
                    <section class="settings-operators__panel">
                        <h4>Crea codice sconto</h4>
                        <form method="post" class="form settings-form">
                            <input type="hidden" name="action" value="create_discount_code">
                            <div class="settings-form__grid">
                                <div class="settings-form__field">
                                    <label for="discount_code">Codice</label>
                                    <input type="text" id="discount_code" name="discount_code" maxlength="32" placeholder="ES. PROMO10" required>
                                </div>
                                <div class="settings-form__field">
                                    <label for="discount_type">Tipo sconto</label>
                                    <select id="discount_type" name="discount_type" required>
                                        <option value="fixed">Importo fisso</option>
                                        <option value="percent">Percentuale</option>
                                    </select>
                                </div>
                                <div class="settings-form__field">
                                    <label for="discount_value">Valore</label>
                                    <input type="number" id="discount_value" name="discount_value" min="0" step="0.01" required>
                                </div>
                                <div class="settings-form__field">
                                    <label for="discount_starts_at">Valido dal</label>
                                    <input type="date" id="discount_starts_at" name="discount_starts_at">
                                </div>
                                <div class="settings-form__field">
                                    <label for="discount_ends_at">Valido fino al</label>
                                    <input type="date" id="discount_ends_at" name="discount_ends_at">
                                </div>
                            </div>
                            <button type="submit" class="btn btn--primary">Salva codice</button>
                        </form>
                        <p class="muted">Usa solo lettere, numeri o trattini (3-32 caratteri). Il codice viene applicato al checkout del tenant.</p>
                    </section>

                    <section class="settings-operators__panel">
                        <h4>Codici attivi e archivio</h4>
                        <?php if (empty($discountCodes)): ?>
                            <p class="muted">Nessun codice sconto configurato.</p>
                        <?php else: ?>
                            <div class="table-wrapper table-wrapper--embedded">
                                <table class="table table--compact">
                                    <thead>
                                        <tr>
                                            <th>Codice</th>
                                            <th>Tipo</th>
                                            <th>Valore</th>
                                            <th>Validità</th>
                                            <th>Stato</th>
                                            <th>Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($discountCodes as $code): ?>
                                            <?php
                                                $isActive = (int) ($code['is_active'] ?? 0) === 1;
                                                $type = (string) ($code['type'] ?? 'Fixed');
                                                $value = (float) ($code['value'] ?? 0);
                                                $starts = !empty($code['starts_at']) ? date('d/m/Y', strtotime((string) $code['starts_at'])) : null;
                                                $ends = !empty($code['ends_at']) ? date('d/m/Y', strtotime((string) $code['ends_at'])) : null;
                                            ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars((string) $code['code']) ?></strong></td>
                                                <td><?= $type === 'Percent' ? 'Percentuale' : 'Importo fisso' ?></td>
                                                <td>
                                                    <?php if ($type === 'Percent'): ?>
                                                        <?= number_format($value, 2, ',', '.') ?>%
                                                    <?php else: ?>
                                                        € <?= number_format($value, 2, ',', '.') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($starts === null && $ends === null): ?>
                                                        Sempre
                                                    <?php else: ?>
                                                        <?= $starts ?? 'n/d' ?> → <?= $ends ?? 'n/d' ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $isActive ? 'Attivo' : 'Disattivato' ?></td>
                                                <td>
                                                    <form method="post" class="inline-form">
                                                        <input type="hidden" name="action" value="toggle_discount_code">
                                                        <input type="hidden" name="discount_code_id" value="<?= (int) $code['id'] ?>">
                                                        <input type="hidden" name="target_status" value="<?= $isActive ? '0' : '1' ?>">
                                                        <button type="submit" class="btn btn--secondary btn--small">
                                                            <?= $isActive ? 'Disattiva' : 'Attiva' ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </article>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <article class="settings-accordion__item" data-accordion data-open="<?= $pdaOpen ? 'true' : 'false' ?>">
                <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $pdaOpen ? 'true' : 'false' ?>">
                    <span class="settings-accordion__title">Configurazione PDA</span>
                    <span class="settings-accordion__icon" aria-hidden="true"></span>
                </button>
                <div class="settings-accordion__content" data-accordion-content <?= $pdaOpen ? '' : 'hidden' ?>>
                    <p class="muted">Gestisci soglie OCR e template di parsing per provider e layout.</p>

                    <?php if ($pdaFeedback !== null): ?>
                        <div class="alert <?= ($pdaFeedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                            <p><?= htmlspecialchars((string) ($pdaFeedback['message'] ?? 'Operazione completata.')) ?></p>
                            <?php foreach ($pdaFeedback['errors'] ?? [] as $error): ?>
                                <p><?= htmlspecialchars((string) $error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <section class="page__section">
                        <h4>OCR automatico</h4>
                        <form method="post" class="form">
                            <input type="hidden" name="action" value="save_pda_ocr">
                            <div class="form__grid">
                                <div class="form__group">
                                    <label for="pda_ocr_enabled">OCR abilitato</label>
                                    <select id="pda_ocr_enabled" name="pda_ocr_enabled">
                                        <option value="1" <?= !empty($pdaOcr['enabled']) ? 'selected' : '' ?>>Attivo</option>
                                        <option value="0" <?= empty($pdaOcr['enabled']) ? 'selected' : '' ?>>Disattivo</option>
                                    </select>
                                </div>
                                <div class="form__group">
                                    <label for="pda_ocr_min_chars">Soglia minima caratteri</label>
                                    <input type="number" id="pda_ocr_min_chars" name="pda_ocr_min_chars" min="50" value="<?= (int) ($pdaOcr['min_chars'] ?? 200) ?>">
                                    <small class="muted">Se il testo estratto è inferiore alla soglia, viene attivato OCR.</small>
                                </div>
                                <div class="form__group">
                                    <label for="pda_ocr_lang">Lingua OCR</label>
                                    <input type="text" id="pda_ocr_lang" name="pda_ocr_lang" value="<?= htmlspecialchars((string) ($pdaOcr['lang'] ?? 'ita')) ?>">
                                    <small class="muted">Codice lingua Tesseract (es. ita, eng).</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn--primary">Salva OCR</button>
                        </form>
                    </section>

                    <section class="page__section">
                        <h4>Template PDA</h4>
                        <p class="muted">Aggiorna i template in formato JSON. Verranno usati per il matching per provider.</p>
                        <form method="post" class="form">
                            <input type="hidden" name="action" value="save_pda_templates">
                            <div class="form__group">
                                <label for="pda_templates_json">JSON Template</label>
                                <textarea id="pda_templates_json" name="pda_templates_json" rows="16" class="form__textarea"><?= htmlspecialchars((string) $pdaTemplatesJson) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn--primary">Salva template</button>
                        </form>
                    </section>
                </div>
            </article>
        <?php endif; ?>

        <?php if ($canManageTenantSettings): ?>
            <article class="settings-accordion__item" data-accordion data-open="<?= $receiptOpen ? 'true' : 'false' ?>">
                <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $receiptOpen ? 'true' : 'false' ?>">
                    <span class="settings-accordion__title">Configurazione scontrino</span>
                    <span class="settings-accordion__icon" aria-hidden="true"></span>
                </button>
                <div class="settings-accordion__content" data-accordion-content <?= $receiptOpen ? '' : 'hidden' ?>>
                    <p class="muted">Personalizza ogni dicitura stampata sullo scontrino. Puoi usare i placeholder {{sale_id}}, {{document_title}}, {{date}}, {{operator}}, {{customer}}.</p>

                    <?php if ($receiptFeedback !== null): ?>
                        <div class="alert <?= ($receiptFeedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                            <p><?= htmlspecialchars((string) ($receiptFeedback['message'] ?? 'Operazione completata.')) ?></p>
                            <?php foreach ($receiptFeedback['errors'] ?? [] as $error): ?>
                                <p><?= htmlspecialchars((string) $error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <section class="page__section">
                        <h4>Intestazione e chiusura</h4>
                        <form method="post" class="form">
                            <input type="hidden" name="action" value="save_receipt_settings">
                            <div class="form__grid">
                                <div class="form__group" style="grid-column: span 2;">
                                    <label for="receipt_header_lines">Intestazione (una riga per riga)</label>
                                    <textarea id="receipt_header_lines" name="receipt_header_lines" rows="3" class="form__textarea" required><?= htmlspecialchars($receiptHeaderText) ?></textarea>
                                </div>
                                <div class="form__group">
                                    <label for="receipt_document_title">Titolo documento</label>
                                    <input type="text" id="receipt_document_title" name="receipt_document_title" value="<?= htmlspecialchars($receiptDocumentTitle) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_document_number_template">Template numero documento</label>
                                    <input type="text" id="receipt_document_number_template" name="receipt_document_number_template" value="<?= htmlspecialchars($receiptDocumentNumberTemplate) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_thanks_text">Testo di ringraziamento</label>
                                    <input type="text" id="receipt_thanks_text" name="receipt_thanks_text" value="<?= htmlspecialchars($receiptThanksText) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_footer_text">Footer pagina PDF</label>
                                    <input type="text" id="receipt_footer_text" name="receipt_footer_text" value="<?= htmlspecialchars($receiptFooterText) ?>">
                                </div>
                            </div>

                            <h4>Etichette principali</h4>
                            <div class="form__grid">
                                <div class="form__group">
                                    <label for="receipt_label_date">Etichetta data</label>
                                    <input type="text" id="receipt_label_date" name="receipt_label_date" value="<?= htmlspecialchars((string) ($receiptLabels['date'] ?? 'Data')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_operator">Etichetta operatore</label>
                                    <input type="text" id="receipt_label_operator" name="receipt_label_operator" value="<?= htmlspecialchars((string) ($receiptLabels['operator'] ?? 'Operatore')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_customer">Etichetta cliente</label>
                                    <input type="text" id="receipt_label_customer" name="receipt_label_customer" value="<?= htmlspecialchars((string) ($receiptLabels['customer'] ?? 'Cliente')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_vat">Etichetta IVA</label>
                                    <input type="text" id="receipt_label_vat" name="receipt_label_vat" value="<?= htmlspecialchars((string) ($receiptLabels['vat'] ?? 'IVA')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_vat_included">Etichetta IVA compresa</label>
                                    <input type="text" id="receipt_label_vat_included" name="receipt_label_vat_included" value="<?= htmlspecialchars((string) ($receiptLabels['vat_included'] ?? 'IVA compresa')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_vat_codes">Etichetta codici IVA</label>
                                    <input type="text" id="receipt_label_vat_codes" name="receipt_label_vat_codes" value="<?= htmlspecialchars((string) ($receiptLabels['vat_codes'] ?? 'Codici IVA applicati')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_discount">Etichetta sconto</label>
                                    <input type="text" id="receipt_label_discount" name="receipt_label_discount" value="<?= htmlspecialchars((string) ($receiptLabels['discount'] ?? 'Sconto')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_total">Etichetta totale</label>
                                    <input type="text" id="receipt_label_total" name="receipt_label_total" value="<?= htmlspecialchars((string) ($receiptLabels['total'] ?? 'Totale')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_total_original">Etichetta totale originario</label>
                                    <input type="text" id="receipt_label_total_original" name="receipt_label_total_original" value="<?= htmlspecialchars((string) ($receiptLabels['total_original'] ?? 'Totale originario')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_payment">Etichetta pagamento</label>
                                    <input type="text" id="receipt_label_payment" name="receipt_label_payment" value="<?= htmlspecialchars((string) ($receiptLabels['payment'] ?? 'Pagamento')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_refund_amount">Etichetta importo reso</label>
                                    <input type="text" id="receipt_label_refund_amount" name="receipt_label_refund_amount" value="<?= htmlspecialchars((string) ($receiptLabels['refund_amount'] ?? 'Importo reso')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_cancelled_at">Etichetta annullato il</label>
                                    <input type="text" id="receipt_label_cancelled_at" name="receipt_label_cancelled_at" value="<?= htmlspecialchars((string) ($receiptLabels['cancelled_at'] ?? 'Annullato il')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_cancellation_reason">Etichetta motivo annullo</label>
                                    <input type="text" id="receipt_label_cancellation_reason" name="receipt_label_cancellation_reason" value="<?= htmlspecialchars((string) ($receiptLabels['cancellation_reason'] ?? 'Motivo annullo')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_refunded_at">Etichetta reso registrato il</label>
                                    <input type="text" id="receipt_label_refunded_at" name="receipt_label_refunded_at" value="<?= htmlspecialchars((string) ($receiptLabels['refunded_at'] ?? 'Reso registrato il')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_label_refund_note">Etichetta note reso</label>
                                    <input type="text" id="receipt_label_refund_note" name="receipt_label_refund_note" value="<?= htmlspecialchars((string) ($receiptLabels['refund_note'] ?? 'Note reso')) ?>">
                                </div>
                            </div>

                            <h4>Stati e banner</h4>
                            <div class="form__grid">
                                <div class="form__group">
                                    <label for="receipt_status_cancelled">Banner annullato</label>
                                    <input type="text" id="receipt_status_cancelled" name="receipt_status_cancelled" value="<?= htmlspecialchars((string) ($receiptStatusLabels['cancelled'] ?? 'ANNULLATO')) ?>">
                                </div>
                                <div class="form__group">
                                    <label for="receipt_status_refunded">Banner reso</label>
                                    <input type="text" id="receipt_status_refunded" name="receipt_status_refunded" value="<?= htmlspecialchars((string) ($receiptStatusLabels['refunded'] ?? 'RESO')) ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn--primary">Salva configurazione scontrino</button>
                        </form>
                    </section>
                </div>
            </article>
        <?php endif; ?>

        <article class="settings-accordion__item" data-accordion data-open="false">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="false">
                <span class="settings-accordion__title">Bridge fiscale (CUSTOM)</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content hidden>
                <p class="muted">Queste impostazioni vengono salvate localmente nel browser e valgono per la postazione corrente.</p>
                <form class="form" data-bridge-settings-form>
                    <div class="form__grid">
                        <div class="form__group" style="grid-column: span 2;">
                            <label>
                                <input type="checkbox" name="bridge_enabled">
                                Abilita stampa fiscale con bridge locale
                            </label>
                        </div>
                        <div class="form__group">
                            <label for="bridge_url">URL bridge</label>
                            <input type="text" id="bridge_url" name="bridge_url" placeholder="http://127.0.0.1:4789">
                            <small class="muted">Porta HTTP configurata nel file bridge/config.json.</small>
                        </div>
                        <div class="form__group">
                            <label for="bridge_device_host">IP stampante (bridge)</label>
                            <input type="text" id="bridge_device_host" name="bridge_device_host" placeholder="192.168.1.50" readonly>
                            <small class="muted">Aggiornato automaticamente dalla ricerca LAN.</small>
                        </div>
                        <div class="form__group">
                            <label for="bridge_device_port">Porta stampante (bridge)</label>
                            <input type="text" id="bridge_device_port" name="bridge_device_port" placeholder="9100" readonly>
                        </div>
                        <div class="form__group">
                            <label for="bridge_api_key">API key</label>
                            <input type="text" id="bridge_api_key" name="bridge_api_key" placeholder="change-me">
                            <small class="muted">Deve combaciare con api_key nel bridge.</small>
                        </div>
                        <div class="form__group">
                            <label for="bridge_device_id">ID dispositivo</label>
                            <input type="text" id="bridge_device_id" name="bridge_device_id" placeholder="cassa_1">
                            <small class="muted">Usa la chiave definita in devices nel config del bridge.</small>
                        </div>
                        <div class="form__group">
                            <label for="bridge_dept">Reparto di default</label>
                            <input type="number" id="bridge_dept" name="bridge_dept" min="1" value="1">
                        </div>
                        <div class="form__group">
                            <label for="bridge_scan_port">Porta stampante (scan LAN)</label>
                            <input type="number" id="bridge_scan_port" name="bridge_scan_port" min="1" value="9100">
                            <small class="muted">Default 9100 per stampanti fiscali TCP.</small>
                        </div>
                        <div class="form__group">
                            <label for="bridge_operator">Operatore RT</label>
                            <input type="text" id="bridge_operator" name="bridge_operator" placeholder="1">
                            <small class="muted">Opzionale. Se vuoto non viene inviato.</small>
                        </div>
                        <div class="form__group" style="grid-column: span 2;">
                            <label>
                                <input type="checkbox" name="bridge_include_subtotal">
                                Invia subtotale automatico
                            </label>
                        </div>
                    </div>
                    <div class="form__inline">
                        <button type="submit" class="btn btn--primary">Salva impostazioni locali</button>
                        <button type="button" class="btn btn--secondary" data-bridge-test>Test connessione</button>
                        <button type="button" class="btn btn--secondary" data-bridge-scan>Cerca stampante in LAN</button>
                    </div>
                    <div class="alert" data-bridge-status hidden></div>
                    <div class="table-actions-inline" data-bridge-scan-results></div>
                </form>
            </div>
        </article>

        <article class="settings-accordion__item" data-accordion data-open="<?= $ssoOpen ? 'true' : 'false' ?>">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $ssoOpen ? 'true' : 'false' ?>">
                <span class="settings-accordion__title">Single sign-on interno</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content <?= $ssoOpen ? '' : 'hidden' ?>>
                <?php if (!$isAdmin): ?>
                    <p class="muted">Solo gli amministratori possono gestire il single sign-on.</p>
                <?php elseif (!$ssoEnabled): ?>
                    <p class="muted">Per attivare l'SSO interno imposta la variabile <code>SSO_SHARED_SECRET</code> nel file <code>.env</code> e riavvia l'applicazione. Il secret viene usato per firmare i token JWT.</p>
                <?php else: ?>
                    <?php if ($ssoFeedback !== null): ?>
                        <section class="page__section">
                            <div class="alert <?= ($ssoFeedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                                <p><?= htmlspecialchars($ssoFeedback['message'] ?? '') ?></p>
                                <?php if (!empty($ssoFeedback['error'])): ?>
                                    <p class="muted">Dettaglio: <?= htmlspecialchars((string) $ssoFeedback['error']) ?></p>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($ssoSecretPreview !== null && !empty($ssoSecretPreview['client_secret'])): ?>
                        <section class="page__section">
                            <article class="card card--highlight">
                                <h4>Secret generato</h4>
                                <p>Copia questi valori e conservali in un password manager: non saranno più mostrati.</p>
                                <ul class="key-list">
                                    <li><strong>Client ID:</strong> <code><?= htmlspecialchars((string) ($ssoSecretPreview['client_id'] ?? '')) ?></code></li>
                                    <li><strong>Client secret:</strong> <code><?= htmlspecialchars((string) $ssoSecretPreview['client_secret']) ?></code></li>
                                </ul>
                            </article>
                        </section>
                    <?php endif; ?>

                    <section class="settings-operators__panel">
                        <h4>Registra un'applicazione</h4>
                        <p class="muted">Crea un client da condividere con i servizi interni che devono delegare l'autenticazione.</p>
                        <form method="post" class="form settings-form">
                            <input type="hidden" name="action" value="sso_create_client">
                            <div class="settings-form__grid">
                                <div class="settings-form__field">
                                    <label for="sso_client_name">Nome applicazione</label>
                                    <input type="text" id="sso_client_name" name="sso_client_name" maxlength="100" required>
                                </div>
                                <div class="settings-form__field">
                                    <label for="sso_redirect_uri">Redirect URI autorizzato</label>
                                    <input type="url" id="sso_redirect_uri" name="sso_redirect_uri" placeholder="https://servizio.interno/callback" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn--primary">Genera client</button>
                        </form>
                        <p class="muted">I token di accesso emessi durano circa <?= $ssoTokenTtl > 0 ? (int) floor($ssoTokenTtl / 60) : 0 ?> minuti. Usa l'endpoint <code>index.php?page=sso_authorize</code> per avviare il flusso OAuth2/PKCE e <code>index.php?page=sso_token</code> per lo scambio codice-token.</p>
                    </section>

                    <section class="settings-operators__panel">
                        <h4>Client registrati</h4>
                        <?php if (empty($ssoClients)): ?>
                            <p class="muted">Nessun client configurato. Crea il primo per abilitare l'SSO.</p>
                        <?php else: ?>
                            <div class="table-wrapper table-wrapper--embedded">
                                <table class="table table--compact">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Client ID</th>
                                            <th>Redirect URI</th>
                                            <th>Confidenziale</th>
                                            <th>Stato</th>
                                            <th>Creato il</th>
                                            <th class="table__col--actions">Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ssoClients as $client): ?>
                                            <?php
                                                $clientRowId = (int) ($client['id'] ?? 0);
                                                $clientIdentifier = (string) ($client['client_id'] ?? '');
                                                $clientName = trim((string) ($client['name'] ?? ''));
                                                $clientRedirect = trim((string) ($client['redirect_uri'] ?? ''));
                                                $isActiveClient = (int) ($client['is_active'] ?? 0) === 1;
                                                $isConfidential = (int) ($client['is_confidential'] ?? 1) === 1;
                                                $createdAt = !empty($client['created_at']) ? date('d/m/Y H:i', strtotime((string) $client['created_at'])) : 'n/d';
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($clientName !== '' ? $clientName : 'Client #' . $clientRowId) ?></td>
                                                <td><code><?= htmlspecialchars($clientIdentifier) ?></code></td>
                                                <td><?= htmlspecialchars($clientRedirect) ?></td>
                                                <td><?= $isConfidential ? 'Sì' : 'No' ?></td>
                                                <td>
                                                    <span class="badge <?= $isActiveClient ? 'badge--success' : 'badge--muted' ?>">
                                                        <?= $isActiveClient ? 'Attivo' : 'Disattivato' ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($createdAt) ?></td>
                                                <td class="table__col--actions">
                                                    <div class="table-actions">
                                                        <form method="post" class="inline-form" onsubmit="return confirm('Rigenerare il secret per questo client?');">
                                                            <input type="hidden" name="action" value="sso_rotate_client_secret">
                                                            <input type="hidden" name="client_id" value="<?= $clientRowId ?>">
                                                            <input type="hidden" name="client_identifier" value="<?= htmlspecialchars($clientIdentifier) ?>">
                                                            <input type="hidden" name="client_label" value="<?= htmlspecialchars($clientName) ?>">
                                                            <button type="submit" class="btn btn--secondary btn--small">Rigenera secret</button>
                                                        </form>
                                                        <form method="post" class="inline-form">
                                                            <input type="hidden" name="action" value="sso_toggle_client">
                                                            <input type="hidden" name="client_id" value="<?= $clientRowId ?>">
                                                            <input type="hidden" name="target_status" value="<?= $isActiveClient ? '0' : '1' ?>">
                                                            <button type="submit" class="btn btn--secondary btn--small"><?= $isActiveClient ? 'Disattiva' : 'Attiva' ?></button>
                                                        </form>
                                                        <form method="post" class="inline-form" onsubmit="return confirm('Eliminare definitivamente questo client SSO?');">
                                                            <input type="hidden" name="action" value="sso_delete_client">
                                                            <input type="hidden" name="client_id" value="<?= $clientRowId ?>">
                                                            <button type="submit" class="btn btn--danger btn--small">Elimina</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </article>

        <?php if ($moduleEnabled('sim_stock')): ?>
            <article class="settings-accordion__item" data-accordion data-open="<?= ($providersOpen ? 'true' : 'false') ?>">
                <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $providersOpen ? 'true' : 'false' ?>">
                    <span class="settings-accordion__title">Gestori</span>
                    <span class="settings-accordion__icon" aria-hidden="true"></span>
                </button>
                <div class="settings-accordion__content" data-accordion-content <?= $providersOpen ? '' : 'hidden' ?>>
                    <?php if (!$canManageTenantSettings): ?>
                        <p class="muted">Solo i responsabili del tenant possono creare o modificare i gestori.</p>
                    <?php else: ?>
                        <div class="settings-operators">
                            <section class="settings-operators__panel">
                                <h4>Crea un nuovo gestore</h4>
                                <form method="post" class="form settings-form">
                                    <input type="hidden" name="action" value="create_provider">
                                    <div class="settings-form__grid">
                                        <div class="settings-form__field">
                                            <label for="provider_name">Nome gestore</label>
                                            <input type="text" id="provider_name" name="provider_name" required>
                                        </div>
                                        <div class="settings-form__field">
                                            <label for="provider_threshold">Soglia minima</label>
                                            <input type="number" id="provider_threshold" name="provider_threshold" min="0" step="1" value="0">
                                        </div>
                                        <div class="settings-form__field">
                                            <label for="provider_notes">Note</label>
                                            <input type="text" id="provider_notes" name="provider_notes" placeholder="(opzionale)">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn--primary">Crea gestore</button>
                                </form>
                            </section>

                            <section class="settings-operators__panel">
                                <h4>Gestori configurati</h4>
                                <?php if (empty($providers)): ?>
                                    <p class="muted">Nessun gestore configurato.</p>
                                <?php else: ?>
                                    <div class="table-wrapper table-wrapper--embedded">
                                        <table class="table table--compact">
                                            <thead>
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>Soglia minima</th>
                                                    <th>Note</th>
                                                    <th>Creato il</th>
                                                    <th class="table__col--actions">Azioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($providers as $provider): ?>
                                                    <?php
                                                        $providerId = (int) ($provider['id'] ?? 0);
                                                        $createdAt = !empty($provider['created_at'])
                                                            ? date('d/m/Y H:i', strtotime((string) $provider['created_at']))
                                                            : 'n/d';
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <input type="text" name="provider_name" form="provider-update-<?= $providerId ?>" value="<?= htmlspecialchars((string) $provider['name']) ?>" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" min="0" name="reorder_threshold" form="provider-update-<?= $providerId ?>" value="<?= (int) ($provider['reorder_threshold'] ?? 0) ?>" required>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="provider_notes" form="provider-update-<?= $providerId ?>" value="<?= htmlspecialchars((string) ($provider['notes'] ?? '')) ?>" placeholder="(opzionale)">
                                                        </td>
                                                        <td><?= htmlspecialchars($createdAt) ?></td>
                                                        <td class="table__col--actions">
                                                            <div class="table-actions">
                                                                <form id="provider-update-<?= $providerId ?>" method="post" class="inline-form">
                                                                    <input type="hidden" name="action" value="update_provider">
                                                                    <input type="hidden" name="provider_id" value="<?= $providerId ?>">
                                                                    <button type="submit" class="btn btn--secondary btn--small">Salva</button>
                                                                </form>
                                                                <form method="post" onsubmit="return confirm('Eliminare definitivamente questo gestore?');">
                                                                    <input type="hidden" name="action" value="delete_provider">
                                                                    <input type="hidden" name="provider_id" value="<?= $providerId ?>">
                                                                    <button type="submit" class="btn btn--danger btn--small">Elimina</button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </section>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endif; ?>

        <?php if ($moduleEnabled('energy_contracts')): ?>
            <article class="settings-accordion__item" data-accordion data-open="<?= ($energyOpen ? 'true' : 'false') ?>">
                <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $energyOpen ? 'true' : 'false' ?>">
                    <span class="settings-accordion__title">Gestori luce &amp; gas</span>
                    <span class="settings-accordion__icon" aria-hidden="true"></span>
                </button>
                <div class="settings-accordion__content" data-accordion-content <?= $energyOpen ? '' : 'hidden' ?>>
                    <?php if ($energyFeedback !== null): ?>
                        <div class="alert <?= ($energyFeedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                            <p><?= htmlspecialchars($energyFeedback['message']) ?></p>
                            <?php if (!empty($energyFeedback['errors']) && is_array($energyFeedback['errors'])): ?>
                                <ul class="alert__list">
                                    <?php foreach ($energyFeedback['errors'] as $error): ?>
                                        <li><?= htmlspecialchars((string) $error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$canManageTenantSettings): ?>
                        <p class="muted">Solo i responsabili del tenant possono creare o modificare i gestori energia.</p>
                    <?php else: ?>
                        <div class="settings-operators">
                            <section class="settings-operators__panel">
                                <h4>Crea un nuovo gestore energia</h4>
                                <form method="post" class="form settings-form">
                                    <input type="hidden" name="action" value="create_energy_provider">
                                    <div class="settings-form__grid">
                                        <div class="settings-form__field">
                                            <label for="energy_provider_name">Nome gestore</label>
                                            <input type="text" id="energy_provider_name" name="energy_provider_name" required>
                                        </div>
                                        <div class="settings-form__field">
                                            <label for="energy_provider_type">Tipologia</label>
                                            <select id="energy_provider_type" name="energy_provider_type" required>
                                                <option value="luce">Luce</option>
                                                <option value="gas">Gas</option>
                                                <option value="luce_gas" selected>Luce + Gas</option>
                                            </select>
                                        </div>
                                        <div class="settings-form__field">
                                            <label for="energy_token_luce">Gettone luce (€)</label>
                                            <input type="number" id="energy_token_luce" name="energy_token_luce" min="0" step="0.01" value="0">
                                        </div>
                                        <div class="settings-form__field">
                                            <label for="energy_token_gas">Gettone gas (€)</label>
                                            <input type="number" id="energy_token_gas" name="energy_token_gas" min="0" step="0.01" value="0">
                                        </div>
                                        <div class="settings-form__field">
                                            <label for="energy_provider_notes">Note</label>
                                            <input type="text" id="energy_provider_notes" name="energy_provider_notes" placeholder="(opzionale)">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn--primary">Crea gestore energia</button>
                                </form>
                            </section>

                            <section class="settings-operators__panel">
                                <h4>Gestori energia configurati</h4>
                                <?php if ($isAdmin): ?>
                                    <div class="settings-operators__actions" style="margin:10px 0 16px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                                        <form method="post" class="inline-form" onsubmit="return confirm('Importare ora le offerte ARERA?');">
                                            <input type="hidden" name="action" value="import_energy_offers">
                                            <button type="submit" class="btn btn--secondary btn--small">Importa offerte ARERA</button>
                                        </form>
                                        <?php if (!empty($energyOffersImportStatus['last_run']) || !empty($energyOffersImportStatus['last_started'])): ?>
                                            <?php
                                                $lastRun = !empty($energyOffersImportStatus['last_run'])
                                                    ? date('d/m/Y H:i', (int) $energyOffersImportStatus['last_run'])
                                                    : null;
                                                $lastStarted = !empty($energyOffersImportStatus['last_started'])
                                                    ? date('d/m/Y H:i', (int) $energyOffersImportStatus['last_started'])
                                                    : null;
                                                $statusLabel = $energyOffersImportStatus['last_status'] ?? '';
                                                $statusText = $statusLabel === 'success'
                                                    ? 'Completato'
                                                    : ($statusLabel === 'error' ? 'Errore' : 'In corso');
                                            ?>
                                            <span class="muted">Ultimo import: <?= htmlspecialchars($lastRun ?? $lastStarted ?? 'n/d') ?> · Stato: <?= htmlspecialchars($statusText) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($energyProviders)): ?>
                                    <p class="muted">Nessun gestore energia configurato.</p>
                                <?php else: ?>
                                    <div class="table-wrapper table-wrapper--embedded">
                                        <table class="table table--compact">
                                            <thead>
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>Tipologia</th>
                                                    <th>Gettone luce</th>
                                                    <th>Gettone gas</th>
                                                    <th>Note</th>
                                                    <th class="table__col--actions">Azioni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($energyProviders as $energyProvider): ?>
                                                    <?php $energyProviderId = (int) ($energyProvider['id'] ?? 0); ?>
                                                    <tr>
                                                        <td>
                                                            <input type="text" name="energy_provider_name" form="energy-provider-update-<?= $energyProviderId ?>" value="<?= htmlspecialchars((string) ($energyProvider['name'] ?? '')) ?>" required>
                                                        </td>
                                                        <td>
                                                            <select name="energy_provider_type" form="energy-provider-update-<?= $energyProviderId ?>">
                                                                <?php $currentType = (string) ($energyProvider['service_type'] ?? 'luce_gas'); ?>
                                                                <option value="luce" <?= $currentType === 'luce' ? 'selected' : '' ?>>Luce</option>
                                                                <option value="gas" <?= $currentType === 'gas' ? 'selected' : '' ?>>Gas</option>
                                                                <option value="luce_gas" <?= $currentType === 'luce_gas' ? 'selected' : '' ?>>Luce + Gas</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" min="0" step="0.01" name="energy_token_luce" form="energy-provider-update-<?= $energyProviderId ?>" value="<?= number_format((float) ($energyProvider['token_luce'] ?? 0), 2, '.', '') ?>">
                                                        </td>
                                                        <td>
                                                            <input type="number" min="0" step="0.01" name="energy_token_gas" form="energy-provider-update-<?= $energyProviderId ?>" value="<?= number_format((float) ($energyProvider['token_gas'] ?? 0), 2, '.', '') ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="energy_provider_notes" form="energy-provider-update-<?= $energyProviderId ?>" value="<?= htmlspecialchars((string) ($energyProvider['notes'] ?? '')) ?>" placeholder="(opzionale)">
                                                        </td>
                                                        <td class="table__col--actions">
                                                            <div class="table-actions">
                                                                <form id="energy-provider-update-<?= $energyProviderId ?>" method="post" class="inline-form">
                                                                    <input type="hidden" name="action" value="update_energy_provider">
                                                                    <input type="hidden" name="energy_provider_id" value="<?= $energyProviderId ?>">
                                                                    <button type="submit" class="btn btn--secondary btn--small">Salva</button>
                                                                </form>
                                                                <form method="post" onsubmit="return confirm('Eliminare definitivamente questo gestore energia?');">
                                                                    <input type="hidden" name="action" value="delete_energy_provider">
                                                                    <input type="hidden" name="energy_provider_id" value="<?= $energyProviderId ?>">
                                                                    <button type="submit" class="btn btn--danger btn--small">Elimina</button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </section>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endif; ?>

        <article class="settings-accordion__item" data-accordion data-open="<?= ($operatorsOpen ? 'true' : 'false') ?>">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $operatorsOpen ? 'true' : 'false' ?>">
                <span class="settings-accordion__title">Gestione operatori</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content <?= $operatorsOpen ? '' : 'hidden' ?>>
                <?php if (!$canManageTenantSettings): ?>
                    <p class="muted">Solo i responsabili del tenant possono creare o modificare operatori.</p>
                <?php else: ?>
                    <div class="settings-operators">
                        <section class="settings-operators__panel">
                            <h4>Crea un nuovo operatore</h4>
                            <form method="post" class="form settings-form">
                                <input type="hidden" name="action" value="create_operator">
                                <div class="settings-form__grid">
                                    <div class="settings-form__field">
                                        <label for="operator_fullname">Nome completo</label>
                                        <input type="text" id="operator_fullname" name="operator_fullname" required>
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="operator_username">Nome utente</label>
                                        <input type="text" id="operator_username" name="operator_username" minlength="3" required>
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="operator_email">Email</label>
                                        <input type="email" id="operator_email" name="operator_email" placeholder="nome@azienda.it">
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="operator_role">Ruolo</label>
                                        <select id="operator_role" name="operator_role" required>
                                            <option value="">Seleziona...</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= (int) $role['id'] ?>"><?= htmlspecialchars((string) $role['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if ($isAdmin): ?>
                                        <div class="settings-form__field">
                                            <label for="operator_tenant_id">Tenant</label>
                                            <select id="operator_tenant_id" name="operator_tenant_id" required>
                                                <option value="">Seleziona...</option>
                                                <?php foreach ($tenants as $tenant): ?>
                                                    <option value="<?= (int) $tenant['id'] ?>">
                                                        <?= htmlspecialchars((string) $tenant['name']) ?> (<?= htmlspecialchars((string) $tenant['slug']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <input type="hidden" name="operator_tenant_id" value="<?= $currentTenantId ?>">
                                    <?php endif; ?>
                                    <div class="settings-form__field">
                                        <label for="operator_password">Password</label>
                                        <input type="password" id="operator_password" name="operator_password" minlength="8" required>
                                    </div>
                                    <div class="settings-form__field">
                                        <label for="operator_password_confirmation">Conferma password</label>
                                        <input type="password" id="operator_password_confirmation" name="operator_password_confirmation" minlength="8" required>
                                    </div>
                                    <?php if ($isAdmin): ?>
                                        <div class="settings-form__field">
                                            <label for="operator_license_id">Licenza da assegnare</label>
                                            <select id="operator_license_id" name="operator_license_id">
                                                <option value="">Nessuna</option>
                                                <?php foreach ($licenses as $license): ?>
                                                    <option value="<?= (int) $license['id'] ?>">
                                                        <?= htmlspecialchars((string) $license['code']) ?><?= $license['label'] ? ' - ' . htmlspecialchars((string) $license['label']) : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <div class="settings-form__field">
                                        <label class="checkbox checkbox--compact">
                                            <input type="checkbox" name="operator_send_credentials" value="1" checked>
                                            <span>Invia credenziali via email</span>
                                        </label>
                                        <p class="muted">L'invio richiede un'email valida e un provider configurato.</p>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn--primary">Crea operatore</button>
                            </form>
                        </section>

                        <section class="settings-operators__panel">
                            <h4>Operatori attivi</h4>
                            <?php if (empty($operators)): ?>
                                <p class="muted">Nessun operatore registrato oltre all'amministratore.</p>
                            <?php else: ?>
                                <div class="table-wrapper table-wrapper--embedded">
                                    <table class="table table--compact">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Nome utente</th>
                                                <th>Email</th>
                                                <?php if ($isAdmin): ?>
                                                    <th>Tenant</th>
                                                <?php endif; ?>
                                                <th>Ruolo</th>
                                                <th>MFA</th>
                                                <th>Creato il</th>
                                                <th>Aggiornato il</th>
                                                <th class="table__col--actions">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($operators as $operator): ?>
                                                <?php
                                                    $operatorId = (int) ($operator['id'] ?? 0);
                                                    $createdAt = !empty($operator['created_at'])
                                                        ? date('d/m/Y H:i', strtotime((string) $operator['created_at']))
                                                        : 'n/d';
                                                    $updatedAt = !empty($operator['updated_at'])
                                                        ? date('d/m/Y H:i', strtotime((string) $operator['updated_at']))
                                                        : $createdAt;
                                                    $isSelf = $currentUserId === $operatorId;
                                                    $mfaEnabled = (int) ($operator['mfa_enabled'] ?? 0) === 1;
                                                    $mfaEnabledAt = null;
                                                    if (!empty($operator['mfa_enabled_at'])) {
                                                        $mfaEnabledAt = date('d/m/Y H:i', strtotime((string) $operator['mfa_enabled_at']));
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) ($operator['fullname'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string) $operator['username']) ?></td>
                                                    <td><?= !empty($operator['email']) ? htmlspecialchars((string) $operator['email']) : '—' ?></td>
                                                    <?php if ($isAdmin): ?>
                                                        <td><?= !empty($operator['tenant_name']) ? htmlspecialchars((string) $operator['tenant_name']) : '—' ?></td>
                                                    <?php endif; ?>
                                                    <td><?= htmlspecialchars((string) $operator['role_name']) ?></td>
                                                    <td>
                                                        <?php if ($mfaEnabled): ?>
                                                            <span class="badge badge--success">Attiva</span>
                                                            <?php if ($mfaEnabledAt !== null): ?>
                                                                <div class="muted">dal <?= htmlspecialchars($mfaEnabledAt) ?></div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge badge--muted">Non attiva</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($createdAt) ?></td>
                                                    <td><?= htmlspecialchars($updatedAt) ?></td>
                                                    <td class="table__col--actions">
                                                        <div class="table-actions">
                                                            <a class="btn btn--secondary btn--small" href="index.php?page=settings&amp;operators_open=1&amp;edit_operator=<?= $operatorId ?>">Modifica</a>
                                                            <?php if (!$isSelf): ?>
                                                                <form method="post" onsubmit="return confirm('Confermi l\'eliminazione di questo operatore?');">
                                                                    <input type="hidden" name="action" value="delete_operator">
                                                                    <input type="hidden" name="operator_id" value="<?= $operatorId ?>">
                                                                    <button type="submit" class="btn btn--danger btn--small">Elimina</button>
                                                                </form>
                                                                <?php if ($mfaEnabled): ?>
                                                                    <form method="post" onsubmit="return confirm('Disattivare l\'MFA per questo operatore?');">
                                                                        <input type="hidden" name="action" value="force_disable_mfa">
                                                                        <input type="hidden" name="operator_id" value="<?= $operatorId ?>">
                                                                        <button type="submit" class="btn btn--secondary btn--small">Reset MFA</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="badge badge--info">Attivo</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <?php if ($operatorEdit !== null): ?>
                                <?php
                                    $editFullname = trim((string) ($operatorEditForm['fullname'] ?? ($operatorEdit['fullname'] ?? '')));
                                    $editUsername = trim((string) ($operatorEditForm['username'] ?? ($operatorEdit['username'] ?? '')));
                                    $editRoleId = (int) ($operatorEditForm['role_id'] ?? ($operatorEdit['role_id'] ?? 0));
                                    $editEmail = trim((string) ($operatorEditForm['email'] ?? ($operatorEdit['email'] ?? '')));
                                    $editTenantId = (int) ($operatorEditForm['tenant_id'] ?? ($operatorEdit['tenant_id'] ?? 0));
                                    $editUpdatedAt = !empty($operatorEdit['updated_at'])
                                        ? date('d/m/Y H:i', strtotime((string) $operatorEdit['updated_at']))
                                        : null;
                                ?>
                                <section class="settings-operators__panel">
                                    <h5>Modifica operatore</h5>
                                    <p class="muted">Aggiorna i dati di <?= htmlspecialchars($editFullname !== '' ? $editFullname : (string) ($operatorEdit['username'] ?? 'operatore')) ?>.<?= $editUpdatedAt !== null ? ' Ultima modifica il ' . htmlspecialchars($editUpdatedAt) . '.' : '' ?></p>
                                    <form method="post" class="form settings-form">
                                        <input type="hidden" name="action" value="update_operator">
                                        <input type="hidden" name="operator_id" value="<?= (int) $operatorEdit['id'] ?>">
                                        <div class="settings-form__grid">
                                            <div class="settings-form__field">
                                                <label for="operator_edit_fullname">Nome completo</label>
                                                <input type="text" id="operator_edit_fullname" name="operator_edit_fullname" value="<?= htmlspecialchars($editFullname) ?>" required>
                                            </div>
                                            <div class="settings-form__field">
                                                <label for="operator_edit_username">Nome utente</label>
                                                <input type="text" id="operator_edit_username" name="operator_edit_username" value="<?= htmlspecialchars($editUsername) ?>" minlength="3" required>
                                            </div>
                                            <div class="settings-form__field">
                                                <label for="operator_edit_email">Email</label>
                                                <input type="email" id="operator_edit_email" name="operator_edit_email" value="<?= htmlspecialchars($editEmail) ?>" placeholder="nome@azienda.it">
                                            </div>
                                            <div class="settings-form__field">
                                                <label for="operator_edit_role">Ruolo</label>
                                                <select id="operator_edit_role" name="operator_edit_role" required>
                                                    <option value="">Seleziona...</option>
                                                    <?php foreach ($roles as $role): ?>
                                                        <?php $roleId = (int) ($role['id'] ?? 0); ?>
                                                        <option value="<?= $roleId ?>" <?= $roleId === $editRoleId ? 'selected' : '' ?>><?= htmlspecialchars((string) $role['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <?php if ($isAdmin): ?>
                                                <div class="settings-form__field">
                                                    <label for="operator_edit_tenant_id">Tenant</label>
                                                    <select id="operator_edit_tenant_id" name="operator_edit_tenant_id" required>
                                                        <option value="">Seleziona...</option>
                                                        <?php foreach ($tenants as $tenant): ?>
                                                            <?php $tenantId = (int) ($tenant['id'] ?? 0); ?>
                                                            <option value="<?= $tenantId ?>" <?= $tenantId === $editTenantId ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars((string) $tenant['name']) ?> (<?= htmlspecialchars((string) $tenant['slug']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            <?php else: ?>
                                                <input type="hidden" name="operator_edit_tenant_id" value="<?= $currentTenantId ?>">
                                            <?php endif; ?>
                                            <div class="settings-form__field">
                                                <label for="operator_edit_password">Nuova password <span class="muted">(opzionale)</span></label>
                                                <input type="password" id="operator_edit_password" name="operator_edit_password" minlength="8" placeholder="Lascia vuoto per non cambiare">
                                            </div>
                                            <div class="settings-form__field">
                                                <label for="operator_edit_password_confirmation">Conferma password</label>
                                                <input type="password" id="operator_edit_password_confirmation" name="operator_edit_password_confirmation" minlength="8" placeholder="Ripeti la password">
                                            </div>
                                        </div>
                                        <div class="table-actions-inline">
                                            <button type="submit" class="btn btn--primary">Salva modifiche</button>
                                            <a class="btn btn--secondary" href="index.php?page=settings&amp;operators_open=1">Annulla</a>
                                        </div>
                                        <p class="muted">Le password vengono aggiornate solo se inserite e confermate correttamente.</p>
                                    </form>
                                </section>
                            <?php endif; ?>
                        </section>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <?php if ($isAdmin): ?>
            <article class="settings-accordion__item" data-accordion data-open="<?= $licensesOpen ? 'true' : 'false' ?>">
                <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $licensesOpen ? 'true' : 'false' ?>">
                    <span class="settings-accordion__title">Licenze</span>
                    <span class="settings-accordion__icon" aria-hidden="true"></span>
                </button>
                <div class="settings-accordion__content" data-accordion-content <?= $licensesOpen ? '' : 'hidden' ?>>
                    <p class="muted">Genera e gestisci le licenze per l'attivazione del gestionale.</p>

                    <?php if ($licenseFeedback !== null): ?>
                        <div class="alert <?= ($licenseFeedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                            <p><?= htmlspecialchars((string) $licenseFeedback['message']) ?></p>
                            <?php if (!empty($licenseFeedback['error'])): ?>
                                <p class="muted">Dettaglio: <?= htmlspecialchars((string) $licenseFeedback['error']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($licenseGeneratedCode !== null): ?>
                        <div class="alert alert--success">
                            <p>Licenza generata: <strong><?= htmlspecialchars((string) ($licenseGeneratedCode['code'] ?? '')) ?></strong></p>
                            <?php if (!empty($licenseGeneratedCode['label'])): ?>
                                <p class="muted">Etichetta: <?= htmlspecialchars((string) $licenseGeneratedCode['label']) ?></p>
                            <?php endif; ?>
                            <p class="muted">Annota il codice: verrà mostrato solo una volta.</p>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="form settings-form">
                        <input type="hidden" name="action" value="create_license_key">
                        <div class="settings-form__grid">
                            <div class="settings-form__field">
                                <label for="license_label">Etichetta</label>
                                <input type="text" id="license_label" name="license_label" placeholder="Es. Abbonamento annuale">
                            </div>
                            <div class="settings-form__field">
                                <label for="license_max_users">Max utenti</label>
                                <input type="number" min="1" id="license_max_users" name="license_max_users" value="1" required>
                            </div>
                            <div class="settings-form__field">
                                <label for="license_term_months">Durata licenza</label>
                                <select id="license_term_months" name="license_term_months" required>
                                    <option value="12">12 mesi</option>
                                    <option value="24">24 mesi</option>
                                    <option value="36">36 mesi</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary">Genera licenza</button>
                    </form>

                    <div class="table-wrapper table-wrapper--embedded">
                        <table class="table table--compact">
                            <thead>
                                <tr>
                                    <th>Codice</th>
                                    <th>Etichetta</th>
                                    <th>Max utenti</th>
                                    <th>Scadenza</th>
                                    <th>Stato</th>
                                    <th>Creata il</th>
                                    <th class="table__col--actions">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($licenses)): ?>
                                    <tr><td colspan="7">Nessuna licenza disponibile.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($licenses as $license): ?>
                                        <?php
                                            $licenseId = (int) ($license['id'] ?? 0);
                                            $isActive = (int) ($license['is_active'] ?? 0) === 1;
                                            $createdAt = !empty($license['created_at'])
                                                ? date('d/m/Y', strtotime((string) $license['created_at']))
                                                : 'n/d';
                                            $expiresAt = !empty($license['expires_at'])
                                                ? date('d/m/Y', strtotime((string) $license['expires_at']))
                                                : 'n/d';
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($license['code'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string) ($license['label'] ?? '')) ?></td>
                                            <td><?= (int) ($license['max_users'] ?? 1) ?></td>
                                            <td><?= htmlspecialchars($expiresAt) ?></td>
                                            <td>
                                                <?php if ($isActive): ?>
                                                    <span class="badge badge--success">Attiva</span>
                                                <?php else: ?>
                                                    <span class="badge badge--muted">Disattiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($createdAt) ?></td>
                                            <td class="table__col--actions">
                                                <div class="table-actions">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="toggle_license_key">
                                                        <input type="hidden" name="license_id" value="<?= $licenseId ?>">
                                                        <input type="hidden" name="target_status" value="<?= $isActive ? '0' : '1' ?>">
                                                        <button type="submit" class="btn btn--secondary btn--small">
                                                            <?= $isActive ? 'Disattiva' : 'Attiva' ?>
                                                        </button>
                                                    </form>
                                                    <a class="btn btn--secondary btn--small" href="index.php?page=settings&amp;licenses_open=1&amp;license_id=<?= $licenseId ?>">Dettagli</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($licenseFocusId > 0): ?>
                        <section class="settings-accordion__subsection">
                            <h4>Attivazioni licenza</h4>
                            <?php if (empty($licenseActivations)): ?>
                                <p class="muted">Nessuna attivazione registrata.</p>
                            <?php else: ?>
                                <div class="table-wrapper table-wrapper--embedded">
                                    <table class="table table--compact">
                                        <thead>
                                            <tr>
                                                <th>Attivata il</th>
                                                <th>Revocata il</th>
                                                <th>Note</th>
                                                <th class="table__col--actions">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($licenseActivations as $activation): ?>
                                                <?php
                                                    $activationId = (int) ($activation['id'] ?? 0);
                                                    $activatedAt = !empty($activation['activated_at'])
                                                        ? date('d/m/Y H:i', strtotime((string) $activation['activated_at']))
                                                        : 'n/d';
                                                    $revokedAt = !empty($activation['revoked_at'])
                                                        ? date('d/m/Y H:i', strtotime((string) $activation['revoked_at']))
                                                        : null;
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($activatedAt) ?></td>
                                                    <td><?= $revokedAt !== null ? htmlspecialchars($revokedAt) : '—' ?></td>
                                                    <td><?= htmlspecialchars((string) ($activation['notes'] ?? '')) ?></td>
                                                    <td class="table__col--actions">
                                                        <?php if ($revokedAt === null): ?>
                                                            <form method="post" onsubmit="return confirm('Revocare questa attivazione?');">
                                                                <input type="hidden" name="action" value="revoke_license_activation">
                                                                <input type="hidden" name="activation_id" value="<?= $activationId ?>">
                                                                <input type="hidden" name="license_focus_id" value="<?= $licenseFocusId ?>">
                                                                <button type="submit" class="btn btn--danger btn--small">Revoca</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="badge badge--muted">Revocata</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>
            </article>
        <?php endif; ?>

        <article class="settings-accordion__item" data-accordion data-open="<?= $auditOpen ? 'true' : 'false' ?>">
            <button type="button" class="settings-accordion__toggle" data-accordion-toggle aria-expanded="<?= $auditOpen ? 'true' : 'false' ?>">
                <span class="settings-accordion__title">Registro attività</span>
                <span class="settings-accordion__icon" aria-hidden="true"></span>
            </button>
            <div class="settings-accordion__content" data-accordion-content <?= $auditOpen ? '' : 'hidden' ?>>
                <?php if (empty($auditLogs)): ?>
                    <p class="muted">Nessuna attività registrata negli audit log.</p>
                <?php else: ?>
                    <ul class="activity-list">
                        <?php foreach ($auditLogs as $event): ?>
                            <?php
                                $metaParts = [];
                                if (!empty($event['user'])) {
                                    $metaParts[] = 'Operatore: ' . htmlspecialchars((string) $event['user']);
                                }
                                if (!empty($event['created_at_display'])) {
                                    $metaParts[] = 'Registrato il ' . htmlspecialchars((string) $event['created_at_display']);
                                }
                                $metaText = implode(' • ', $metaParts);
                            ?>
                            <li class="activity-entry">
                                <span class="activity-entry__title"><?= htmlspecialchars((string) $event['action_label']) ?></span>
                                <?php if ($metaText !== ''): ?>
                                    <span class="activity-entry__meta"><?= $metaText ?></span>
                                <?php endif; ?>
                                <?php if (($event['description'] ?? '') !== ''): ?>
                                    <p class="activity-entry__value"><?= nl2br(htmlspecialchars((string) $event['description']), false) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($totalAuditPages > 1): ?>
                        <nav class="pagination">
                            <a class="pagination__link <?= $hasAuditPrev ? '' : 'is-disabled' ?>" href="<?= $hasAuditPrev ? htmlspecialchars($buildAuditPageUrl(1)) : '#' ?>" aria-label="Prima pagina">«</a>
                            <a class="pagination__link <?= $hasAuditPrev ? '' : 'is-disabled' ?>" href="<?= $hasAuditPrev ? htmlspecialchars($buildAuditPageUrl($auditCurrentPage - 1)) : '#' ?>" aria-label="Pagina precedente">‹</a>
                            <span class="pagination__info">Pagina <?= $auditCurrentPage ?> di <?= $totalAuditPages ?> (<?= $totalAuditEvents ?> eventi)</span>
                            <a class="pagination__link <?= $hasAuditNext ? '' : 'is-disabled' ?>" href="<?= $hasAuditNext ? htmlspecialchars($buildAuditPageUrl($auditCurrentPage + 1)) : '#' ?>" aria-label="Pagina successiva">›</a>
                            <a class="pagination__link <?= $hasAuditNext ? '' : 'is-disabled' ?>" href="<?= $hasAuditNext ? htmlspecialchars($buildAuditPageUrl($totalAuditPages)) : '#' ?>" aria-label="Ultima pagina">»</a>
                        </nav>
                    <?php else: ?>
                        <p class="muted">Totale <?= $totalAuditEvents ?> eventi nei log.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>
