<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $profile
 * @var string $roleLabel
 * @var string $roleSummary
 * @var array<int, array<string, string>> $shortcuts
 * @var array{total_sales:int,total_revenue:float,last_sale_at:?string,status_breakdown:array<string,int>} $salesSummary
 * @var string|null $tenantLicenseLabel
 * @var string|null $tenantLicenseExpiresAt
 * @var array{success:bool,message:string,error?:string}|null $passwordFeedback
 */
$profile = $profile ?? [];
$roleLabel = $roleLabel ?? 'Operatore';
$roleSummary = $roleSummary ?? '';
$shortcuts = $shortcuts ?? [];
$salesSummary = $salesSummary ?? [
    'total_sales' => 0,
    'total_revenue' => 0.0,
    'last_sale_at' => null,
    'status_breakdown' => [],
];
$tenantLicenseLabel = isset($tenantLicenseLabel) && is_string($tenantLicenseLabel) ? trim($tenantLicenseLabel) : '';
$tenantLicenseExpiresAt = isset($tenantLicenseExpiresAt) && is_string($tenantLicenseExpiresAt)
    ? trim($tenantLicenseExpiresAt)
    : '';
$passwordFeedback = isset($passwordFeedback) && is_array($passwordFeedback) ? $passwordFeedback : null;
$isAdmin = isset($isAdmin) ? (bool) $isAdmin : false;

$fullname = (string) ($profile['fullname'] ?? '');
$username = (string) ($profile['username'] ?? '');
$tenantName = (string) ($profile['tenant_name'] ?? '');
$tenantSlug = (string) ($profile['tenant_slug'] ?? '');
$tenantEmail = (string) ($profile['tenant_email'] ?? '');
$tenantPhone = (string) ($profile['tenant_phone'] ?? '');
$tenantVatNumber = (string) ($profile['tenant_vat_number'] ?? '');
$tenantCompanyCountry = (string) ($profile['tenant_company_country'] ?? '');
$tenantCompanyName = (string) ($profile['tenant_company_name'] ?? '');
$tenantCompanyAddress = (string) ($profile['tenant_company_address'] ?? '');

$displayName = $fullname !== '' ? $fullname : $username;
if ($displayName === '') {
    $displayName = 'Operatore';
}

$initial = 'C';
$nameForInitial = $fullname !== '' ? $fullname : $username;
if ($nameForInitial !== '') {
    if (function_exists('mb_substr')) {
        $initial = (string) mb_substr($nameForInitial, 0, 1, 'UTF-8');
        if (function_exists('mb_strtoupper')) {
            $initial = mb_strtoupper($initial, 'UTF-8');
        } else {
            $initial = strtoupper($initial);
        }
    } else {
        $initial = strtoupper(substr($nameForInitial, 0, 1));
    }
}

$createdAt = $profile['created_at'] ?? null;
$updatedAt = $profile['updated_at'] ?? null;
$roleReadable = $roleLabel;
$lastSaleAt = $salesSummary['last_sale_at'] ?? null;
$statusBreakdown = $salesSummary['status_breakdown'] ?? [];
$statusLabels = [
    'Completed' => 'Completate',
    'Cancelled' => 'Annullate',
    'Refunded' => 'Rimborsate',
];

$formatDateTime = static function (?string $value): string {
    if ($value === null || $value === '') {
        return 'n/d';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return 'n/d';
    }
    return date('d/m/Y H:i', $timestamp);
};

$totalRevenueFormatted = number_format((float) $salesSummary['total_revenue'], 2, ',', '.');
?>
<section class="page profile-page">
    <header class="page__header">
        <h2>Profilo personale</h2>
        <p class="muted"><?= htmlspecialchars($roleSummary) ?></p>
    </header>

    <section class="page__section">
        <div class="profile-summary">
            <article class="card profile-card">
                <div class="profile-card__header">
                    <div class="profile-avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
                    <div class="profile-card__titles">
                        <h3 class="profile-card__name"><?= htmlspecialchars($displayName) ?></h3>
                        <span class="profile-card__role"><?= htmlspecialchars($roleReadable) ?></span>
                    </div>
                </div>
                <dl class="profile-meta">
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Nome utente</dt>
                        <dd class="profile-meta__value"><?= htmlspecialchars($username !== '' ? $username : 'n/d') ?></dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Piano attivo</dt>
                        <dd class="profile-meta__value">
                            <?= htmlspecialchars($isAdmin ? 'Illimitata' : ($tenantLicenseLabel !== '' ? $tenantLicenseLabel : 'n/d')) ?>
                        </dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Scadenza licenza</dt>
                        <dd class="profile-meta__value">
                            <?= htmlspecialchars($isAdmin ? 'Illimitata' : ($tenantLicenseExpiresAt !== '' ? $formatDateTime($tenantLicenseExpiresAt) : 'n/d')) ?>
                        </dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Creato il</dt>
                        <dd class="profile-meta__value"><?= htmlspecialchars($formatDateTime(is_string($createdAt) ? $createdAt : null)) ?></dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Ultimo aggiornamento</dt>
                        <dd class="profile-meta__value"><?= htmlspecialchars($formatDateTime(is_string($updatedAt) ? $updatedAt : null)) ?></dd>
                    </div>
                    <?php if ($lastSaleAt !== null): ?>
                        <div class="profile-meta__row">
                            <dt class="profile-meta__label">Ultima vendita</dt>
                            <dd class="profile-meta__value"><?= htmlspecialchars($formatDateTime($lastSaleAt)) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </article>

            <article class="card profile-card">
                <h3 class="profile-card__section">Dati tenant</h3>
                <dl class="profile-meta">
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Tenant</dt>
                        <dd class="profile-meta__value">
                            <?= htmlspecialchars($tenantName !== '' ? $tenantName : 'n/d') ?>
                        </dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Slug</dt>
                        <dd class="profile-meta__value">
                            <?= htmlspecialchars($tenantSlug !== '' ? $tenantSlug : 'n/d') ?>
                        </dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Contatti</dt>
                        <dd class="profile-meta__value">
                            <?= htmlspecialchars($tenantEmail !== '' ? $tenantEmail : 'n/d') ?><br>
                            <?= htmlspecialchars($tenantPhone !== '' ? $tenantPhone : 'n/d') ?>
                        </dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Ragione sociale</dt>
                        <dd class="profile-meta__value">
                            <?= htmlspecialchars($tenantCompanyName !== '' ? $tenantCompanyName : 'n/d') ?>
                        </dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">P.IVA</dt>
                        <dd class="profile-meta__value">
                            <?php if ($tenantVatNumber !== ''): ?>
                                <?= htmlspecialchars($tenantCompanyCountry . $tenantVatNumber) ?>
                            <?php else: ?>
                                n/d
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div class="profile-meta__row">
                        <dt class="profile-meta__label">Indirizzo sede</dt>
                        <dd class="profile-meta__value">
                            <?= htmlspecialchars($tenantCompanyAddress !== '' ? $tenantCompanyAddress : 'n/d') ?>
                        </dd>
                    </div>
                </dl>
            </article>

            <article class="card profile-card">
                <h3 class="profile-card__section">Andamento vendite</h3>
                <div class="profile-metric">
                    <span class="profile-metric__value"><?= (int) $salesSummary['total_sales'] ?></span>
                    <span class="profile-metric__label">Vendite completate</span>
                </div>
                <div class="profile-metric">
                    <span class="profile-metric__value">€ <?= $totalRevenueFormatted ?></span>
                    <span class="profile-metric__label">Fatturato generato</span>
                </div>
                <?php if ($statusBreakdown !== []): ?>
                    <ul class="profile-status-list">
                        <?php foreach ($statusBreakdown as $status => $count): ?>
                            <li class="profile-status-list__item">
                                <?php $statusLabel = $statusLabels[$status] ?? $status; ?>
                                <span class="profile-status-list__label"><?= htmlspecialchars($statusLabel) ?></span>
                                <span class="profile-status-list__value"><?= (int) $count ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        </div>
    </section>

    <section class="page__section">
        <div class="profile-summary">
            <article class="card profile-card">
                <h3 class="profile-card__section">Sicurezza account</h3>
                <?php if ($passwordFeedback !== null): ?>
                    <div class="alert <?= ($passwordFeedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                        <p><?= htmlspecialchars((string) ($passwordFeedback['message'] ?? 'Operazione completata.')) ?></p>
                        <?php if (!empty($passwordFeedback['error'])): ?>
                            <p class="muted">Dettaglio: <?= htmlspecialchars((string) $passwordFeedback['error']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <form method="post" class="form">
                    <input type="hidden" name="action" value="update_password">
                    <div class="form__grid">
                        <div class="form__group">
                            <label for="profile_current_password">Password attuale</label>
                            <input type="password" id="profile_current_password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="form__group">
                            <label for="profile_new_password">Nuova password</label>
                            <input type="password" id="profile_new_password" name="new_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form__group">
                            <label for="profile_new_password_confirmation">Conferma nuova password</label>
                            <input type="password" id="profile_new_password_confirmation" name="new_password_confirmation" required minlength="8" autocomplete="new-password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary">Aggiorna password</button>
                </form>
            </article>
        </div>
    </section>

    <?php if ($shortcuts !== []): ?>
        <section class="page__section">
            <h3>Azioni rapide</h3>
            <div class="profile-shortcuts">
                <?php foreach ($shortcuts as $shortcut): ?>
                    <?php
                        $href = isset($shortcut['href']) ? (string) $shortcut['href'] : '#';
                        $label = isset($shortcut['label']) ? (string) $shortcut['label'] : 'Apri';
                        $description = isset($shortcut['description']) ? (string) $shortcut['description'] : '';
                    ?>
                    <a class="profile-shortcut" href="<?= htmlspecialchars($href) ?>">
                        <span class="profile-shortcut__title"><?= htmlspecialchars($label) ?></span>
                        <?php if ($description !== ''): ?>
                            <span class="profile-shortcut__description"><?= htmlspecialchars($description) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
