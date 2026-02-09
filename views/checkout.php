<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ('Attiva piano - ' . $appName);
$planKey = $planKey ?? 'start';
$plan = isset($plan) && is_array($plan) ? $plan : null;
$billingCycle = isset($billingCycle) && is_string($billingCycle) ? $billingCycle : 'annual';
$resumeRequestId = isset($resumeRequestId) ? (int) $resumeRequestId : 0;
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$isLoggedIn = isset($currentUser) && $currentUser !== null;
$loginUrl = $isLoggedIn ? 'index.php?page=dashboard' : 'index.php?page=login';
$loginLabel = $isLoggedIn ? 'Area personale' : 'Accedi';
$landingUrl = 'index.php?page=landing&public=1';
$prezziUrl = 'index.php?page=prezzi&public=1';
$currentPage = 'checkout';
$tenantName = trim((string) ($oldInput['tenant_name'] ?? ''));
$tenantSlug = trim((string) ($oldInput['tenant_slug'] ?? ''));
$tenantEmail = trim((string) ($oldInput['tenant_email'] ?? ''));
$tenantPhone = trim((string) ($oldInput['tenant_phone'] ?? ''));
$tenantVatNumber = trim((string) ($oldInput['vat_number'] ?? ''));
$tenantCompanyCountry = 'IT';
$discountCode = trim((string) ($oldInput['discount_code'] ?? ''));
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
                <a class="landing-nav__link <?= $currentPage === 'demo' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=demo&public=1">Demo</a>
                <a class="landing-nav__link <?= $currentPage === 'funzionalita' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=funzionalita&public=1">Funzionalità</a>
                <a class="landing-nav__link <?= $currentPage === 'vantaggi' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=vantaggi&public=1">Vantaggi</a>
                <a class="landing-nav__link <?= $currentPage === 'piani' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=piani&public=1">Piani</a>
                <a class="landing-nav__link <?= $currentPage === 'prezzi' ? 'landing-nav__link--active' : '' ?>" href="<?= htmlspecialchars($prezziUrl) ?>">Prezzi</a>
                <a class="landing-nav__link <?= $currentPage === 'faq' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=faq&public=1">FAQ</a>
                <a class="landing-nav__link <?= $currentPage === 'contatto' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=contatto&public=1">Contatto</a>
            </nav>
            <a class="landing-btn landing-btn--ghost" href="<?= htmlspecialchars($loginUrl) ?>"><?= htmlspecialchars($loginLabel) ?></a>
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
                            <?php if ($resumeRequestId > 0): ?>
                                <input type="hidden" name="resume_request_id" value="<?= (int) $resumeRequestId ?>">
                            <?php endif; ?>
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
                                    Codice sconto
                                    <input type="text" name="discount_code" value="<?= htmlspecialchars($discountCode) ?>" placeholder="Inserisci il codice sconto">
                                </label>
                                <input type="hidden" name="company_country" value="IT">
                                <label class="landing-form__span">
                                    P.IVA
                                    <div class="landing-form__inline landing-form__inline--inside">
                                        <input type="text" name="vat_number" value="<?= htmlspecialchars($tenantVatNumber) ?>" placeholder="12345678901">
                                        <button type="button" class="landing-btn landing-btn--secondary landing-btn--small" data-vies-button>Verifica VIES</button>
                                    </div>
                                    <span class="landing-form__status" data-vies-status></span>
                                </label>
                            </div>
                            <label>
                                Ragione sociale
                                <input type="text" name="company_name" value="<?= htmlspecialchars($tenantCompanyName) ?>">
                            </label>
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

    <?php if ($plan !== null): ?>
        <div class="modal" id="checkout-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="checkout-confirm-title" data-open="false">
            <div class="modal__dialog checkout-confirm__dialog">
                <button type="button" class="modal__close" data-checkout-confirm-close aria-label="Chiudi">×</button>
                <div class="checkout-confirm__content">
                    <div class="checkout-confirm__body">
                        <p class="landing-kicker">Conferma pagamento</p>
                        <h2 id="checkout-confirm-title">Riepilogo prima di procedere</h2>
                        <p>Puoi modificare la frequenza di pagamento prima di continuare su Stripe.</p>

                        <div class="checkout-confirm__summary" data-checkout-confirm-summary>
                            <strong data-checkout-confirm-name></strong>
                            <span data-checkout-confirm-desc></span>
                            <div class="checkout-confirm__price" data-checkout-confirm-price></div>
                        </div>

                        <div class="checkout-confirm__tenant" data-checkout-confirm-tenant>
                            <h3>Dettagli tenant</h3>
                            <ul>
                                <li><strong>Nome:</strong> <span data-checkout-tenant-name></span></li>
                                <li><strong>Slug:</strong> <span data-checkout-tenant-slug></span></li>
                                <li><strong>Email:</strong> <span data-checkout-tenant-email></span></li>
                                <li><strong>Telefono:</strong> <span data-checkout-tenant-phone></span></li>
                                <li><strong>P.IVA:</strong> <span data-checkout-tenant-vat></span></li>
                                <li><strong>Codice sconto:</strong> <span data-checkout-tenant-discount></span></li>
                                <li><strong>Ragione sociale:</strong> <span data-checkout-tenant-company></span></li>
                                <li><strong>Indirizzo sede:</strong> <span data-checkout-tenant-address></span></li>
                            </ul>
                        </div>

                        <label class="checkout-confirm__field">
                            Frequenza pagamento
                            <select data-checkout-confirm-billing>
                                <option value="annual">Pagamento unico</option>
                                <option value="monthly">Abbonamento mensile</option>
                            </select>
                        </label>

                        <ul class="checkout-confirm__list">
                            <li>Pagamento gestito da Stripe in modalità sicura.</li>
                            <li>Le credenziali saranno inviate dopo la conferma.</li>
                            <li>Puoi tornare indietro in qualunque momento.</li>
                        </ul>
                    </div>
                    <div class="checkout-confirm__footer">
                        <button type="button" class="landing-btn landing-btn--secondary" data-checkout-confirm-close>Annulla</button>
                        <button type="button" class="landing-btn landing-btn--primary" data-checkout-confirm-submit>Conferma e vai al pagamento</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
                    setStatus('Inserisci la P.IVA.', 'error');
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

            const confirmModal = document.querySelector('#checkout-confirm-modal');
            const confirmName = document.querySelector('[data-checkout-confirm-name]');
            const confirmDesc = document.querySelector('[data-checkout-confirm-desc]');
            const confirmPrice = document.querySelector('[data-checkout-confirm-price]');
            const confirmBilling = document.querySelector('[data-checkout-confirm-billing]');
            const confirmCloseButtons = document.querySelectorAll('[data-checkout-confirm-close]');
            const confirmSubmit = document.querySelector('[data-checkout-confirm-submit]');
            const form = document.querySelector('form.landing-form');

            const tenantNameEl = document.querySelector('[data-checkout-tenant-name]');
            const tenantSlugEl = document.querySelector('[data-checkout-tenant-slug]');
            const tenantEmailEl = document.querySelector('[data-checkout-tenant-email]');
            const tenantPhoneEl = document.querySelector('[data-checkout-tenant-phone]');
            const tenantVatEl = document.querySelector('[data-checkout-tenant-vat]');
            const tenantDiscountEl = document.querySelector('[data-checkout-tenant-discount]');
            const tenantCompanyEl = document.querySelector('[data-checkout-tenant-company]');
            const tenantAddressEl = document.querySelector('[data-checkout-tenant-address]');

            const tenantNameInput = document.querySelector('input[name="tenant_name"]');
            const tenantSlugInput = document.querySelector('input[name="tenant_slug"]');
            const tenantEmailInput = document.querySelector('input[name="tenant_email"]');
            const tenantPhoneInput = document.querySelector('input[name="tenant_phone"]');
            const tenantVatInput = document.querySelector('input[name="vat_number"]');
            const tenantCountryInput = document.querySelector('input[name="company_country"]');
            const tenantDiscountInput = document.querySelector('input[name="discount_code"]');
            const tenantCompanyInput = document.querySelector('input[name="company_name"]');
            const tenantAddressInput = document.querySelector('textarea[name="company_address"]');

            let allowSubmit = false;

            if (!billingSelect || !planCard || !nameEl || !descEl || !priceEl) {
                return;
            }

            const formatPrice = (value, isMonthly) => {
                const formatter = new Intl.NumberFormat('it-IT', {
                    minimumFractionDigits: isMonthly ? 2 : 0,
                    maximumFractionDigits: isMonthly ? 2 : 0,
                });
                const suffix = isMonthly ? ' / mese' : '';
                return `€ ${formatter.format(Number.isFinite(value) ? value : 0)}${suffix}`;
            };

            const updateTenantSummary = () => {
                const setText = (el, value, fallback = '—') => {
                    if (!el) {
                        return;
                    }
                    const text = (value || '').toString().trim();
                    el.textContent = text !== '' ? text : fallback;
                };

                setText(tenantNameEl, tenantNameInput?.value);
                setText(tenantSlugEl, tenantSlugInput?.value);
                setText(tenantEmailEl, tenantEmailInput?.value);
                setText(tenantPhoneEl, tenantPhoneInput?.value);
                setText(tenantVatEl, tenantVatInput?.value);
                setText(tenantDiscountEl, tenantDiscountInput?.value);
                setText(tenantCompanyEl, tenantCompanyInput?.value);
                setText(tenantAddressEl, tenantAddressInput?.value);
            };

            const updateSummary = () => {
                const isMonthly = billingSelect.value === 'monthly';
                const priceValue = parseFloat(isMonthly ? planCard.dataset.monthlyPrice : planCard.dataset.annualPrice);
                nameEl.textContent = isMonthly ? (planCard.dataset.monthlyName || '') : (planCard.dataset.annualName || '');
                descEl.textContent = isMonthly ? (planCard.dataset.monthlyDesc || '') : (planCard.dataset.annualDesc || '');
                priceEl.textContent = formatPrice(priceValue, isMonthly);
                if (confirmName && confirmDesc && confirmPrice) {
                    confirmName.textContent = nameEl.textContent;
                    confirmDesc.textContent = descEl.textContent;
                    confirmPrice.textContent = formatPrice(priceValue, isMonthly);
                }
                if (confirmBilling && confirmBilling.value !== billingSelect.value) {
                    confirmBilling.value = billingSelect.value;
                }
                updateTenantSummary();
            };

            billingSelect.addEventListener('change', updateSummary);
            updateSummary();

            if (!confirmModal || !confirmBilling || !confirmSubmit || !form) {
                return;
            }

            const openModal = () => {
                confirmModal.setAttribute('data-open', 'true');
                updateSummary();
            };

            const closeModal = () => {
                confirmModal.setAttribute('data-open', 'false');
            };

            confirmCloseButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            confirmModal.addEventListener('click', (event) => {
                if (event.target === confirmModal) {
                    closeModal();
                }
            });

            form.addEventListener('submit', (event) => {
                if (allowSubmit) {
                    return;
                }
                event.preventDefault();
                openModal();
            });

            confirmBilling.addEventListener('change', () => {
                billingSelect.value = confirmBilling.value;
                updateSummary();
            });

            [tenantNameInput, tenantSlugInput, tenantEmailInput, tenantPhoneInput, tenantVatInput, tenantCountryInput, tenantCompanyInput, tenantAddressInput]
                .filter(Boolean)
                .forEach((input) => {
                    input.addEventListener('input', updateTenantSummary);
                });

            confirmSubmit.addEventListener('click', () => {
                allowSubmit = true;
                closeModal();
                form.submit();
            });
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
