<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ('Attiva piano - ' . $appName);
$planKey = $planKey ?? 'start';
$plan = isset($plan) && is_array($plan) ? $plan : null;
$billingCycle = isset($billingCycle) && is_string($billingCycle) ? $billingCycle : 'annual';
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$landingUrl = 'index.php?page=landing';
$prezziUrl = 'index.php?page=prezzi';
$currentPage = 'checkout';
$tenantName = trim((string) ($oldInput['tenant_name'] ?? ''));
$tenantSlug = trim((string) ($oldInput['tenant_slug'] ?? ''));
$tenantEmail = trim((string) ($oldInput['tenant_email'] ?? ''));
$tenantPhone = trim((string) ($oldInput['tenant_phone'] ?? ''));
$tenantVatNumber = trim((string) ($oldInput['vat_number'] ?? ''));
$tenantCompanyCountry = trim((string) ($oldInput['company_country'] ?? 'IT'));
$tenantCompanyName = trim((string) ($oldInput['company_name'] ?? ''));
$tenantCompanyAddress = trim((string) ($oldInput['company_address'] ?? ''));
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="landing-body">
    <div class="landing-topbar">
        <div class="landing-topbar__inner">
            <div class="landing-brand">
                <span class="landing-brand__logo" aria-hidden="true">
                    <img src="assets/img/logo-collapsed.svg" alt="">
                </span>
                <span class="landing-brand__name"><?= htmlspecialchars($appName) ?></span>
            </div>
            <nav class="landing-nav" aria-label="Navigazione principale">
                <a class="landing-nav__link <?= $currentPage === 'landing' ? 'landing-nav__link--active' : '' ?>" href="<?= htmlspecialchars($landingUrl) ?>">Home</a>
                <a class="landing-nav__link <?= $currentPage === 'demo' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=demo">Demo</a>
                <a class="landing-nav__link <?= $currentPage === 'funzionalita' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=funzionalita">Funzionalità</a>
                <a class="landing-nav__link <?= $currentPage === 'vantaggi' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=vantaggi">Vantaggi</a>
                <a class="landing-nav__link <?= $currentPage === 'piani' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=piani">Piani</a>
                <a class="landing-nav__link <?= $currentPage === 'prezzi' ? 'landing-nav__link--active' : '' ?>" href="<?= htmlspecialchars($prezziUrl) ?>">Prezzi</a>
                <a class="landing-nav__link <?= $currentPage === 'faq' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=faq">FAQ</a>
                <a class="landing-nav__link <?= $currentPage === 'contatto' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=contatto">Contatto</a>
            </nav>
            <a class="landing-btn landing-btn--ghost" href="index.php?page=login">Accedi</a>
        </div>
    </div>

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Attivazione online</p>
                <h1>Completa i dati del tuo tenant</h1>
                <p class="landing-subtitle">Inserisci i dati necessari, poi verrai reindirizzato al pagamento Stripe del piano selezionato.</p>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section landing-checkout">
            <div class="landing-checkout__grid">
                <div class="landing-checkout__panel">
                    <h2>Dati tenant</h2>
                    <p>Le credenziali di accesso verranno inviate solo dopo il pagamento completato.</p>

                    <?php if ($feedback): ?>
                        <div class="landing-alert landing-alert--<?= ($feedback['success'] ?? false) ? 'success' : 'error' ?>">
                            <p><?= htmlspecialchars($feedback['message'] ?? 'Operazione non riuscita.') ?></p>
                            <?php foreach (($feedback['errors'] ?? []) as $error): ?>
                                <p><?= htmlspecialchars((string) $error) ?></p>
                            <?php endforeach; ?>
                            <?php if (!empty($feedback['recovery_url'])): ?>
                                <p><a class="landing-btn landing-btn--secondary landing-btn--small" href="<?= htmlspecialchars((string) $feedback['recovery_url']) ?>"><?= htmlspecialchars((string) ($feedback['recovery_label'] ?? 'Riprendi attivazione')) ?></a></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($plan === null): ?>
                        <div class="landing-alert landing-alert--error">
                            <p>Piano non valido. Torna alla pagina prezzi per riprovare.</p>
                        </div>
                        <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($prezziUrl) ?>">Vai ai prezzi</a>
                    <?php else: ?>
                        <form method="post" class="landing-form">
                            <input type="hidden" name="action" value="checkout_start">
                            <input type="hidden" name="plan_key" value="<?= htmlspecialchars($planKey) ?>">
                            <div class="landing-form__grid">
                                <label>
                                    Nome tenant *
                                    <input type="text" name="tenant_name" value="<?= htmlspecialchars($tenantName) ?>" required>
                                </label>
                                <label>
                                    Slug tenant *
                                    <input type="text" name="tenant_slug" value="<?= htmlspecialchars($tenantSlug) ?>" placeholder="es. ag-servizi" required readonly>
                                </label>
                                <label>
                                    Email contatto *
                                    <input type="email" name="tenant_email" value="<?= htmlspecialchars($tenantEmail) ?>" required>
                                </label>
                                <label>
                                    Frequenza pagamento *
                                    <select name="billing_cycle" required>
                                        <option value="annual" <?= $billingCycle === 'monthly' ? '' : 'selected' ?>>Pagamento unico</option>
                                        <option value="monthly" <?= $billingCycle === 'monthly' ? 'selected' : '' ?>>Abbonamento mensile</option>
                                    </select>
                                </label>
                                <label>
                                    Telefono contatto
                                    <input type="text" name="tenant_phone" value="<?= htmlspecialchars($tenantPhone) ?>">
                                </label>
                                <label>
                                    Paese P.IVA
                                    <input type="text" name="company_country" value="<?= htmlspecialchars($tenantCompanyCountry !== '' ? $tenantCompanyCountry : 'IT') ?>" maxlength="2" placeholder="IT">
                                </label>
                                <label>
                                    P.IVA
                                    <div class="landing-form__inline landing-form__inline--inside">
                                        <input type="text" name="vat_number" value="<?= htmlspecialchars($tenantVatNumber) ?>" placeholder="IT12345678901">
                                        <button type="button" class="landing-btn landing-btn--secondary landing-btn--small" data-vies-button>Verifica VIES</button>
                                    </div>
                                    <span class="landing-form__status" data-vies-status></span>
                                </label>
                                <label>
                                    Ragione sociale
                                    <input type="text" name="company_name" value="<?= htmlspecialchars($tenantCompanyName) ?>">
                                </label>
                            </div>
                            <label>
                                Indirizzo sede
                                <textarea name="company_address" rows="3"><?= htmlspecialchars($tenantCompanyAddress) ?></textarea>
                            </label>
                            <div class="landing-form__footer">
                                <button type="submit" class="landing-btn landing-btn--primary">Procedi al pagamento</button>
                                <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($prezziUrl) ?>">Torna ai prezzi</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <aside class="landing-checkout__summary">
                    <h3>Piano selezionato</h3>
                    <?php if ($plan !== null): ?>
                        <?php
                            $priceValue = (float) ($plan['billing_price_eur'] ?? $plan['price_eur'] ?? 0);
                            $isMonthly = ($plan['billing_cycle'] ?? '') === 'monthly';
                            $priceDecimals = $isMonthly ? 2 : 0;
                            $priceSuffix = $isMonthly ? ' / mese' : '';
                            $termMonths = (int) ($plan['term_months'] ?? 12);
                            $termMonths = $termMonths > 0 ? $termMonths : 12;
                            $annualPrice = (float) ($plan['price_eur'] ?? 0);
                            $monthlyPrice = $termMonths > 0 ? round($annualPrice / $termMonths, 2) : $annualPrice;
                            $rawName = (string) ($plan['stripe_name'] ?? '');
                            $baseName = str_replace(' (mensile)', '', $rawName);
                            $rawDesc = (string) ($plan['stripe_description'] ?? '');
                            $baseDesc = str_starts_with($rawDesc, 'Abbonamento mensile')
                                ? trim(str_replace('Abbonamento mensile ·', '', $rawDesc))
                                : $rawDesc;
                            $annualDesc = $baseDesc;
                            $monthlyDesc = 'Abbonamento mensile' . ($baseDesc !== '' ? ' · ' . $baseDesc : '');
                        ?>
                        <div class="landing-checkout__plan" data-checkout-plan
                            data-annual-name="<?= htmlspecialchars($baseName) ?>"
                            data-monthly-name="<?= htmlspecialchars($baseName . ' (mensile)') ?>"
                            data-annual-desc="<?= htmlspecialchars($annualDesc) ?>"
                            data-monthly-desc="<?= htmlspecialchars($monthlyDesc) ?>"
                            data-annual-price="<?= number_format($annualPrice, 2, '.', '') ?>"
                            data-monthly-price="<?= number_format($monthlyPrice, 2, '.', '') ?>">
                            <strong data-checkout-plan-name><?= htmlspecialchars((string) $plan['stripe_name']) ?></strong>
                            <span data-checkout-plan-desc><?= htmlspecialchars((string) $plan['stripe_description']) ?></span>
                            <div class="landing-checkout__price" data-checkout-plan-price>€ <?= number_format($priceValue, $priceDecimals, ',', '.') ?><?= $priceSuffix ?></div>
                        </div>
                        <ul>
                            <li>Attivazione immediata dopo il pagamento.</li>
                            <li>Licenza assegnata con quota adesione pagata.</li>
                            <li>Credenziali inviate via email.</li>
                            <?php if ($isMonthly): ?>
                                <li>Addebito mensile per tutta la durata della licenza selezionata.</li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <p>Seleziona un piano valido dalla pagina prezzi.</p>
                    <?php endif; ?>
                </aside>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div>
            <strong><?= htmlspecialchars($appName) ?></strong>
            <span>· Gestionale multi-tenant per telecomunicazioni e retail.</span>
        </div>
        <span>Sviluppato e distribuito da AG SERVIZI P.Iva 08442881218</span>
    </footer>
    <script>
        (() => {
            const button = document.querySelector('[data-vies-button]');
            const status = document.querySelector('[data-vies-status]');
            const vatInput = document.querySelector('input[name="vat_number"]');
            const countryInput = document.querySelector('input[name="company_country"]');
            const nameInput = document.querySelector('input[name="company_name"]');
            const addressInput = document.querySelector('textarea[name="company_address"]');

            if (!button || !status || !vatInput || !countryInput || !nameInput || !addressInput) {
                return;
            }

            const setStatus = (message, variant) => {
                status.textContent = message || '';
                status.classList.remove('is-success', 'is-error');
                if (variant) {
                    status.classList.add(variant === 'success' ? 'is-success' : 'is-error');
                }
            };

            button.addEventListener('click', async () => {
                const vat = vatInput.value.trim();
                const country = countryInput.value.trim();
                if (!vat || !country) {
                    setStatus('Inserisci paese e P.IVA.', 'error');
                    return;
                }

                setStatus('Verifica in corso…');
                try {
                    const response = await fetch('index.php?page=vies_lookup', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ vat, country }),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        setStatus(data.message || 'Errore durante la verifica.', 'error');
                        return;
                    }
                    if (!data.valid) {
                        setStatus('P.IVA non valida su VIES.', 'error');
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

                    setStatus('Dati recuperati da VIES.', 'success');
                } catch (error) {
                    setStatus('Impossibile contattare VIES.', 'error');
                }
            });
        })();

        (() => {
            const billingSelect = document.querySelector('select[name="billing_cycle"]');
            const planCard = document.querySelector('[data-checkout-plan]');
            const nameEl = document.querySelector('[data-checkout-plan-name]');
            const descEl = document.querySelector('[data-checkout-plan-desc]');
            const priceEl = document.querySelector('[data-checkout-plan-price]');

            if (!billingSelect || !planCard || !nameEl || !descEl || !priceEl) {
                return;
            }

            const updateSummary = () => {
                const isMonthly = billingSelect.value === 'monthly';
                const priceValue = parseFloat(isMonthly ? planCard.dataset.monthlyPrice : planCard.dataset.annualPrice);
                const formatter = new Intl.NumberFormat('it-IT', {
                    minimumFractionDigits: isMonthly ? 2 : 0,
                    maximumFractionDigits: isMonthly ? 2 : 0,
                });
                nameEl.textContent = isMonthly ? (planCard.dataset.monthlyName || '') : (planCard.dataset.annualName || '');
                descEl.textContent = isMonthly ? (planCard.dataset.monthlyDesc || '') : (planCard.dataset.annualDesc || '');
                const suffix = isMonthly ? ' / mese' : '';
                priceEl.textContent = `€ ${formatter.format(Number.isFinite(priceValue) ? priceValue : 0)}${suffix}`;
            };

            billingSelect.addEventListener('change', updateSummary);
            updateSummary();
        })();
    </script>
    <script>
        (() => {
            const nameInput = document.querySelector('input[name="tenant_name"]');
            const slugInput = document.querySelector('input[name="tenant_slug"]');
            const form = document.querySelector('form.landing-form');
            if (!nameInput || !slugInput) {
                return;
            }

            let slugTouched = false;

            const slugify = (value) => {
                if (!value) {
                    return '';
                }
                return value
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .replace(/-+/g, '-');
            };

            const syncSlug = () => {
                if (slugTouched && slugInput.value.trim() !== '') {
                    return;
                }
                const nextSlug = slugify(nameInput.value.trim());
                if (nextSlug !== '') {
                    slugInput.value = nextSlug;
                }
            };

            slugInput.addEventListener('input', () => {
                slugTouched = slugInput.value.trim() !== '';
            });

            nameInput.addEventListener('input', syncSlug);
            nameInput.addEventListener('blur', syncSlug);

            if (form) {
                form.addEventListener('submit', syncSlug);
            }
        })();
    </script>
</body>
</html>
