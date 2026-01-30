<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $energyProviders
 * @var array{rows: array<int, array<string, mixed>>, total: float, period_label: string, range: array{start:string,end:string}} $contractsData
 * @var array{success:bool, message:string, errors?:array<int, string>}|null $feedback
 */
$pageTitle = $pageTitle ?? 'Contratti energia';
$energyProviders = isset($energyProviders) && is_array($energyProviders) ? $energyProviders : [];
$contractsData = $contractsData ?? ['rows' => [], 'total' => 0.0, 'period_label' => 'Periodo', 'range' => ['start' => '', 'end' => '']];
$feedback = isset($feedback) && is_array($feedback) ? $feedback : null;
$energyOffers = isset($energyOffers) && is_array($energyOffers) ? $energyOffers : [];
$energyProvidersList = isset($energyProviders) && is_array($energyProviders)
    ? array_values(array_filter(array_map(static fn (array $provider): string => (string) ($provider['name'] ?? ''), $energyProviders)))
    : [];
$period = $period ?? 'month';
$dateValue = $dateValue ?? '';
$focusContractId = isset($focusContractId) && is_numeric($focusContractId) ? (int) $focusContractId : null;

$labels = [
    'luce' => 'Luce',
    'gas' => 'Gas',
    'luce_gas' => 'Luce + Gas',
];
?>
<section class="page">
    <header class="page__header">
        <h2>Contratti luce & gas</h2>
        <p>Registra i contratti energia e monitora le provvigioni maturate per periodo.</p>
    </header>

    <?php
        $energyOffersJson = htmlspecialchars(
            json_encode($energyOffers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]',
            ENT_QUOTES,
            'UTF-8'
        );
        $energyProvidersJson = htmlspecialchars(
            json_encode($energyProvidersList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]',
            ENT_QUOTES,
            'UTF-8'
        );
    ?>
    <section class="page__section energy-sim" data-energy-sim data-energy-offers="<?= $energyOffersJson ?>" data-energy-providers="<?= $energyProvidersJson ?>">
        <div class="energy-sim__hero">
            <div class="energy-sim__hero-content">
                <h3>Scopri quanto puoi risparmiare sulla tua bolletta</h3>
                <p>Simula luce e gas in pochi passi, confronta le offerte e attiva senza stress.</p>
                <button type="button" class="btn btn--primary energy-sim__cta" data-energy-sim-cta>
                    Simula ora la tua bolletta
                </button>
                <p class="muted">Gratis, senza impegno · ⚡ Luce · 🔥 Gas</p>
            </div>
            <div class="energy-sim__hero-badges">
                <span class="energy-sim__badge">Risparmio annuo stimato</span>
                <span class="energy-sim__badge">Supporto dedicato</span>
                <span class="energy-sim__badge">Partner ufficiali</span>
            </div>
        </div>

        <div class="energy-sim__panel" id="energy-sim">
            <div class="energy-sim__progress" aria-hidden="true">
                <span class="energy-sim__progress-bar" data-energy-sim-progress></span>
            </div>
            <div class="energy-sim__steps" data-energy-sim-steps>
                <div class="energy-sim__step" data-energy-step="1">
                    <header>
                        <h4>Step 1 · Tipo di fornitura</h4>
                        <p class="muted">Seleziona la tua utenza.</p>
                    </header>
                    <div class="energy-sim__options">
                        <label class="energy-sim__option">
                            <input type="radio" name="energy_supply" value="luce" data-energy-supply>
                            <span>⚡ Luce</span>
                        </label>
                        <label class="energy-sim__option">
                            <input type="radio" name="energy_supply" value="gas" data-energy-supply>
                            <span>🔥 Gas</span>
                        </label>
                        <label class="energy-sim__option">
                            <input type="radio" name="energy_supply" value="luce_gas" data-energy-supply>
                            <span>⚡🔥 Luce + Gas</span>
                        </label>
                    </div>
                </div>

                <div class="energy-sim__step" data-energy-step="2" hidden>
                    <header>
                        <h4>Step 2 · Tipo di cliente</h4>
                        <p class="muted">Ti serve una simulazione per uso personale o business?</p>
                    </header>
                    <div class="energy-sim__options">
                        <label class="energy-sim__option">
                            <input type="radio" name="energy_customer" value="privato">
                            <span>Privato</span>
                        </label>
                        <label class="energy-sim__option">
                            <input type="radio" name="energy_customer" value="azienda">
                            <span>Azienda / Partita IVA</span>
                        </label>
                    </div>
                </div>

                <div class="energy-sim__step" data-energy-step="3" hidden>
                    <header>
                        <h4>Step 3 · Dati di consumo</h4>
                        <p class="muted">Non conosci i valori? Usa le alternative rapide.</p>
                    </header>
                    <div class="energy-sim__grid">
                        <div class="energy-sim__block" data-energy-consumption="luce">
                            <h5>Consumi luce</h5>
                            <label>
                                Consumo annuo (kWh)
                                <input type="number" min="0" step="50" name="luce_kwh" placeholder="Es. 2700">
                            </label>
                            <label>
                                Numero componenti nucleo familiare
                                <input type="number" min="1" max="8" step="1" name="luce_members" placeholder="Es. 3">
                            </label>
                            <label>
                                Fascia oraria
                                <select name="luce_fascia">
                                    <option value="monoraria">Monoraria</option>
                                    <option value="bioraria">Bioraria</option>
                                </select>
                            </label>
                        </div>
                        <div class="energy-sim__block" data-energy-consumption="gas">
                            <h5>Consumi gas</h5>
                            <label>
                                Consumo annuo (Smc)
                                <input type="number" min="0" step="10" name="gas_smc" placeholder="Es. 900">
                            </label>
                            <div class="energy-sim__checks">
                                <span>Tipo di utilizzo</span>
                                <label><input type="checkbox" name="gas_use" value="cottura"> Cottura cibi</label>
                                <label><input type="checkbox" name="gas_use" value="acqua"> Acqua calda</label>
                                <label><input type="checkbox" name="gas_use" value="riscaldamento"> Riscaldamento</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="energy-sim__step" data-energy-step="4" hidden>
                    <header>
                        <h4>Step 4 · Spesa attuale (facoltativo)</h4>
                        <p class="muted">Se inserisci la bolletta attuale, la stima è più accurata.</p>
                    </header>
                    <div class="energy-sim__grid">
                        <label>
                            Bolletta (PDF)
                            <div class="energy-sim__drop" data-energy-bill-drop>
                                <input type="file" accept="application/pdf" data-energy-bill-file hidden>
                                <div class="energy-sim__drop-content">
                                    <strong>Trascina qui il PDF</strong>
                                    <span class="muted">oppure clicca per selezionare</span>
                                    <span class="muted" data-energy-bill-status></span>
                                </div>
                            </div>
                        </label>
                        <label>
                            Importo medio bolletta (€)
                            <input type="number" min="0" step="1" name="bill_amount" placeholder="Es. 120">
                        </label>
                        <label>
                            Frequenza
                            <select name="bill_frequency">
                                <option value="monthly">Mensile</option>
                                <option value="bimonthly">Bimestrale</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="energy-sim__step" data-energy-step="5" hidden>
                    <header>
                        <h4>Step 5 · Area geografica</h4>
                        <p class="muted">Aiuta a simulare tariffe più realistiche.</p>
                    </header>
                    <div class="energy-sim__grid">
                        <label>
                            CAP o Comune
                            <input type="text" name="location" placeholder="Es. 20100 Milano">
                        </label>
                    </div>
                </div>
            </div>

            <div class="energy-sim__actions">
                <button type="button" class="btn btn--secondary" data-energy-sim-prev disabled>Indietro</button>
                <button type="button" class="btn btn--primary" data-energy-sim-next>Continua</button>
            </div>
        </div>

        <div class="energy-sim__results" data-energy-sim-results hidden>
            <header class="energy-sim__results-header">
                <h3>Risultato della simulazione</h3>
                <p class="muted">Confronto tra spesa attuale e nuove offerte disponibili.</p>
            </header>
            <div class="energy-sim__results-cards">
                <article class="energy-sim__result-card">
                    <span class="muted">Costo annuo attuale</span>
                    <strong data-energy-result-current>€ 0</strong>
                </article>
                <article class="energy-sim__result-card energy-sim__result-card--highlight">
                    <span class="muted">Costo annuo nuove offerte</span>
                    <strong data-energy-result-new>€ 0</strong>
                </article>
                <article class="energy-sim__result-card energy-sim__result-card--save">
                    <span class="muted">Risparmio annuo stimato</span>
                    <strong data-energy-result-save>€ 0</strong>
                </article>
            </div>
            <div class="energy-sim__results-list" data-energy-results-list></div>
            <div class="alert alert--success" style="margin-top:12px;">
                <p>
                    Nota: i risultati sono una simulazione basata su dati ARERA e sulle informazioni inserite.
                    Per una lettura approfondita si consiglia una consulenza diretta con il cliente.
                </p>
            </div>
        </div>

        <div class="energy-sim__benefits">
            <h4>Perché conviene</h4>
            <ul>
                <li>Nessun costo per la simulazione</li>
                <li>Nessun obbligo di attivazione</li>
                <li>Supporto umano dedicato</li>
                <li>Pratiche gestite al 100%</li>
                <li>Partner ufficiali dei principali fornitori</li>
            </ul>
        </div>
    </section>

    <?php if ($feedback !== null): ?>
        <section class="page__section">
            <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
                <p><?= htmlspecialchars($feedback['message']) ?></p>
                <?php if (!empty($feedback['errors']) && is_array($feedback['errors'])): ?>
                    <ul class="alert__list">
                        <?php foreach ($feedback['errors'] as $error): ?>
                            <li><?= htmlspecialchars((string) $error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="page__section">
        <div class="cards">
            <section class="card">
            <h3>Nuovo contratto</h3>
            <?php if (empty($energyProviders)): ?>
                <p class="muted">Nessun gestore energia configurato. Aggiungilo in Impostazioni → Gestori luce/gas.</p>
            <?php else: ?>
                <form method="post" class="form">
                    <input type="hidden" name="action" value="create_energy_contract">
                    <div class="form__grid">
                        <div class="form__group">
                            <label for="energy_customer_name">Nome cliente</label>
                            <input type="text" id="energy_customer_name" name="energy_customer_name" required>
                        </div>
                        <div class="form__group">
                            <label for="energy_contract_type">Tipologia contratto</label>
                            <select id="energy_contract_type" name="energy_contract_type" required>
                                <option value="">Seleziona</option>
                                <option value="luce">Luce</option>
                                <option value="gas">Gas</option>
                                <option value="luce_gas">Luce + Gas</option>
                            </select>
                        </div>
                        <div class="form__group">
                            <label for="energy_provider_id">Gestore</label>
                            <select id="energy_provider_id" name="energy_provider_id" required>
                                <option value="">Seleziona</option>
                                <?php foreach ($energyProviders as $provider): ?>
                                    <option value="<?= (int) $provider['id'] ?>">
                                        <?= htmlspecialchars((string) $provider['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form__group">
                            <label for="energy_contract_notes">Note</label>
                            <input type="text" id="energy_contract_notes" name="energy_contract_notes" placeholder="(opzionale)">
                        </div>
                    </div>
                    <div class="form__footer">
                        <button type="submit" class="btn btn--primary">Registra contratto</button>
                    </div>
                </form>
            <?php endif; ?>
            </section>

            <section class="card">
            <h3>Provvigioni per periodo</h3>
            <form method="get" class="form">
                <input type="hidden" name="page" value="energy_contracts">
                <div class="form__grid">
                    <div class="form__group">
                        <label for="energy_period">Periodo</label>
                        <select id="energy_period" name="period">
                            <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Giorno</option>
                            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Mese</option>
                            <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Anno</option>
                        </select>
                    </div>
                    <div class="form__group">
                        <label for="energy_date">Data riferimento</label>
                        <input type="date" id="energy_date" name="date" value="<?= htmlspecialchars($dateValue) ?>">
                    </div>
                </div>
                <div class="form__footer">
                    <button type="submit" class="btn btn--secondary">Filtra</button>
                </div>
            </form>
            <div class="card">
                <div class="card__header">
                    <h3><?= htmlspecialchars((string) ($contractsData['period_label'] ?? 'Periodo')) ?></h3>
                </div>
                <div class="card__value">€ <?= number_format((float) ($contractsData['total'] ?? 0.0), 2, ',', '.') ?></div>
                <div class="card__meta">
                    <?= htmlspecialchars(($contractsData['range']['start'] ?? '') !== '' ? $contractsData['range']['start'] : '') ?>
                    <?php if (!empty($contractsData['range']['end'])): ?>
                        → <?= htmlspecialchars((string) $contractsData['range']['end']) ?>
                    <?php endif; ?>
                </div>
            </div>
            </section>
        </div>
    </section>

    <section class="page__section">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Tipologia</th>
                        <th>Gestore</th>
                        <th>Provvigione</th>
                                                <th class="table__col--actions">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contractsData['rows'])): ?>
                                                <tr><td colspan="6">Nessun contratto registrato nel periodo selezionato.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contractsData['rows'] as $row): ?>
                            <?php
                                $createdAt = !empty($row['created_at']) ? date('d/m/Y H:i', strtotime((string) $row['created_at'])) : 'n/d';
                                $typeKey = (string) ($row['contract_type'] ?? '');
                                $typeLabel = $labels[$typeKey] ?? $typeKey;
                                $notes = trim((string) ($row['notes'] ?? ''));
                                $contractId = (int) ($row['id'] ?? 0);
                                $isFocus = $focusContractId !== null && $contractId > 0 && $contractId === $focusContractId;
                            ?>
                            <tr class="<?= $isFocus ? 'energy-contract--focus' : '' ?>" data-energy-contract-id="<?= $contractId ?>" <?= $isFocus ? 'data-energy-focus="true"' : '' ?>>
                                <td><?= htmlspecialchars($createdAt) ?></td>
                                <td><?= htmlspecialchars((string) ($row['customer_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($typeLabel) ?></td>
                                <td><?= htmlspecialchars((string) ($row['provider_name'] ?? '')) ?></td>
                                <td>€ <?= number_format((float) ($row['token_value'] ?? 0), 2, ',', '.') ?></td>
                                                                <td class="table__col--actions">
                                                                        <div class="table-actions">
                                                                                <button
                                                                                        type="button"
                                                                                        class="btn btn--secondary btn--small"
                                                                                        data-energy-detail
                                                                                        data-contract-id="<?= $contractId ?>"
                                                                                        data-contract-date="<?= htmlspecialchars($createdAt) ?>"
                                                                                        data-contract-customer="<?= htmlspecialchars((string) ($row['customer_name'] ?? '')) ?>"
                                                                                        data-contract-type="<?= htmlspecialchars($typeLabel) ?>"
                                                                                        data-contract-provider="<?= htmlspecialchars((string) ($row['provider_name'] ?? '')) ?>"
                                                                                        data-contract-token="€ <?= number_format((float) ($row['token_value'] ?? 0), 2, ',', '.') ?>"
                                                                                        data-contract-notes="<?= htmlspecialchars($notes) ?>"
                                                                                >Dettaglio</button>
                                                                                <form method="post" class="inline-form" data-energy-delete-form>
                                                                                        <input type="hidden" name="action" value="delete_energy_contract">
                                                                                        <input type="hidden" name="contract_id" value="<?= $contractId ?>">
                                                                                        <button
                                                                                                type="button"
                                                                                                class="btn btn--danger btn--small"
                                                                                                data-energy-delete
                                                                                                data-contract-customer="<?= htmlspecialchars((string) ($row['customer_name'] ?? '')) ?>"
                                                                                                data-contract-provider="<?= htmlspecialchars((string) ($row['provider_name'] ?? '')) ?>"
                                                                                                data-contract-date="<?= htmlspecialchars($createdAt) ?>"
                                                                                        >Elimina</button>
                                                                                </form>
                                                                        </div>
                                                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<div class="modal" data-energy-modal aria-hidden="true" style="display:none;">
        <div class="modal__backdrop" data-energy-dismiss></div>
        <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="Dettaglio contratto energia">
                <button type="button" class="modal__close" data-energy-dismiss aria-label="Chiudi">×</button>
                <div class="modal__content">
                        <div class="modal__body" data-energy-modal-body></div>
                </div>
        </div>
</div>


<div class="modal" data-energy-delete-modal aria-hidden="true" style="display:none;">
        <div class="modal__backdrop" data-energy-delete-dismiss></div>
        <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="Conferma eliminazione contratto">
                <button type="button" class="modal__close" data-energy-delete-dismiss aria-label="Chiudi">×</button>
                <div class="modal__content">
                        <div class="modal__body">
                                <h3>Elimina contratto</h3>
                                <p>Confermi l’eliminazione del contratto selezionato?</p>
                                <p class="muted" data-energy-delete-summary></p>
                                <div class="form__footer">
                                        <button type="button" class="btn btn--secondary" data-energy-delete-dismiss>Annulla</button>
                                        <button type="button" class="btn btn--danger" data-energy-delete-confirm>Elimina</button>
                                </div>
                        </div>
                </div>
        </div>
</div>

<script>
(() => {
    const detailModal = document.querySelector('[data-energy-modal]');
    const detailBody = document.querySelector('[data-energy-modal-body]');
    const detailDismiss = detailModal ? detailModal.querySelectorAll('[data-energy-dismiss]') : [];
    const deleteModal = document.querySelector('[data-energy-delete-modal]');
    const deleteSummary = document.querySelector('[data-energy-delete-summary]');
    const deleteConfirm = document.querySelector('[data-energy-delete-confirm]');
    const deleteDismiss = deleteModal ? deleteModal.querySelectorAll('[data-energy-delete-dismiss]') : [];
    let deleteForm = null;
    let restoreFocus = null;
    let previousBodyOverflow = '';

    const setOpen = (modal, open) => {
        if (!modal) return;
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        modal.style.display = open ? 'flex' : 'none';
        if (open) {
            modal.dataset.open = 'true';
        } else {
            delete modal.dataset.open;
        }
    };

    const lockScroll = () => {
        previousBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    };

    const unlockScroll = () => {
        document.body.style.overflow = previousBodyOverflow;
    };

    const closeDetail = () => {
        setOpen(detailModal, false);
        document.removeEventListener('keydown', handleKey);
        document.removeEventListener('click', handleBackdropClick, true);
        unlockScroll();
        if (restoreFocus instanceof HTMLElement) {
            restoreFocus.focus({ preventScroll: true });
        }
    };

    const closeDelete = () => {
        setOpen(deleteModal, false);
        document.removeEventListener('keydown', handleKey);
        document.removeEventListener('click', handleBackdropClick, true);
        unlockScroll();
        deleteForm = null;
        if (restoreFocus instanceof HTMLElement) {
            restoreFocus.focus({ preventScroll: true });
        }
    };

    const handleKey = event => {
        if (event.key === 'Escape') {
            if (detailModal && detailModal.dataset.open) {
                event.preventDefault();
                closeDetail();
            }
            if (deleteModal && deleteModal.dataset.open) {
                event.preventDefault();
                closeDelete();
            }
        }
    };

    const handleBackdropClick = event => {
        if (detailModal && detailModal.contains(event.target)) {
            if (event.target instanceof HTMLElement && event.target.hasAttribute('data-energy-dismiss')) {
                event.preventDefault();
                closeDetail();
            }
        }
        if (deleteModal && deleteModal.contains(event.target)) {
            if (event.target instanceof HTMLElement && event.target.hasAttribute('data-energy-delete-dismiss')) {
                event.preventDefault();
                closeDelete();
            }
        }
    };

    document.querySelectorAll('[data-energy-detail]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!(btn instanceof HTMLElement) || !detailModal || !detailBody) return;
            restoreFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            const notes = btn.dataset.contractNotes || '';
            detailBody.innerHTML = `
                <h3>Dettaglio contratto</h3>
                <ul class="card__list">
                    <li><strong>ID:</strong> ${btn.dataset.contractId || ''}</li>
                    <li><strong>Data:</strong> ${btn.dataset.contractDate || ''}</li>
                    <li><strong>Cliente:</strong> ${btn.dataset.contractCustomer || ''}</li>
                    <li><strong>Tipologia:</strong> ${btn.dataset.contractType || ''}</li>
                    <li><strong>Gestore:</strong> ${btn.dataset.contractProvider || ''}</li>
                    <li><strong>Provvigione:</strong> ${btn.dataset.contractToken || ''}</li>
                    ${notes ? `<li><strong>Note:</strong> ${notes}</li>` : ''}
                </ul>
            `;
            lockScroll();
            setOpen(detailModal, true);
            document.addEventListener('keydown', handleKey);
            document.addEventListener('click', handleBackdropClick, true);
            const closeBtn = detailModal.querySelector('[data-energy-dismiss]');
            if (closeBtn instanceof HTMLElement) {
                closeBtn.focus({ preventScroll: true });
            }
        });
    });

    document.querySelectorAll('[data-energy-delete]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!(btn instanceof HTMLElement) || !deleteModal || !deleteSummary) return;
            restoreFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            deleteForm = btn.closest('form');
            const summary = [
                btn.dataset.contractCustomer ? `Cliente: ${btn.dataset.contractCustomer}` : '',
                btn.dataset.contractProvider ? `Gestore: ${btn.dataset.contractProvider}` : '',
                btn.dataset.contractDate ? `Data: ${btn.dataset.contractDate}` : ''
            ].filter(Boolean).join(' • ');
            deleteSummary.textContent = summary;
            lockScroll();
            setOpen(deleteModal, true);
            document.addEventListener('keydown', handleKey);
            document.addEventListener('click', handleBackdropClick, true);
            const closeBtn = deleteModal.querySelector('[data-energy-delete-dismiss]');
            if (closeBtn instanceof HTMLElement) {
                closeBtn.focus({ preventScroll: true });
            }
        });
    });

    if (deleteConfirm instanceof HTMLElement) {
        deleteConfirm.addEventListener('click', () => {
            if (deleteForm instanceof HTMLFormElement) {
                deleteForm.submit();
            }
        });
    }

    if (detailDismiss.length) {
        detailDismiss.forEach(btn => btn.addEventListener('click', closeDetail));
    }
    if (deleteDismiss.length) {
        deleteDismiss.forEach(btn => btn.addEventListener('click', closeDelete));
    }

    const focusRow = document.querySelector('[data-energy-focus="true"]');
    if (focusRow instanceof HTMLElement) {
        focusRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const detailTrigger = focusRow.querySelector('[data-energy-detail]');
        if (detailTrigger instanceof HTMLElement) {
            window.setTimeout(() => detailTrigger.click(), 200);
        }
    }
})();
</script>
