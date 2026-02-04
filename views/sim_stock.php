<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $providers
 * @var array<int, array<string, mixed>> $stock
 * @var array{success:bool, message:string, error?:string, errors?:array<int, string>}|null $feedback
 * @var array{page:int, per_page:int, total:int, pages:int}|null $pagination
 * @var string $searchTerm
 * @var string $statusFilter
 * @var int $providerFilter
 * @var array<int, string> $statusOptions
 * @var int $perPage
 */
$pageTitle = 'Magazzino SIM';
$feedback = $feedback ?? null;
$searchTerm = $searchTerm ?? '';
$statusFilter = $statusFilter ?? '';
$providerFilter = (int) ($providerFilter ?? 0);
$statusOptions = $statusOptions ?? [];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 7, 'total' => count($stock), 'pages' => 1];
$perPage = isset($perPage) ? (int) $perPage : (int) ($pagination['per_page'] ?? 7);
$statusLabels = [
    'InStock' => 'Disponibili',
    'Assigned' => 'Assegnate',
    'Reserved' => 'Riservate',
    'Sold' => 'Vendute',
    'Cancelled' => 'Annullate',
];
$refreshParams = [
    'page' => 'sim_stock',
    'action' => 'refresh',
    'per_page' => $perPage,
];
if ($searchTerm !== '') {
    $refreshParams['search'] = $searchTerm;
}
if ($statusFilter !== '') {
    $refreshParams['status'] = $statusFilter;
}
if ($providerFilter > 0) {
    $refreshParams['provider_id'] = $providerFilter;
}
$refreshUrl = 'index.php?' . http_build_query($refreshParams);
$buildStockPageUrl = static function (int $pageNo) use ($searchTerm, $statusFilter, $providerFilter, $perPage): string {
    $params = [
        'page' => 'sim_stock',
        'page_no' => $pageNo,
    ];
    if ($searchTerm !== '') {
        $params['search'] = $searchTerm;
    }
    if ($statusFilter !== '') {
        $params['status'] = $statusFilter;
    }
    if ($providerFilter > 0) {
        $params['provider_id'] = $providerFilter;
    }
    if ($perPage > 0) {
        $params['per_page'] = $perPage;
    }
    return 'index.php?' . http_build_query($params);
};
$formatDate = static function (?string $value, string $pattern = 'd/m/Y H:i'): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $timestamp = strtotime($value);
    return $timestamp !== false ? date($pattern, $timestamp) : '—';
};
?>
<section
    class="page"
    data-live-refresh="sim_stock"
    data-refresh-url="<?= htmlspecialchars($refreshUrl) ?>"
    data-refresh-interval="15000"
    data-refresh-page="<?= (int) $pagination['page'] ?>"
    data-refresh-per-page="<?= (int) $pagination['per_page'] ?>"
>
    <header class="page__header">
        <h2>Magazzino SIM</h2>
        <p>Gestisci le SIM a magazzino, aggiungi nuove schede e verifica disponibilità per la vendita.</p>
    </header>

    <section class="page__section">
        <h3>Aggiungi SIM</h3>
        <p class="muted">Inserisci manualmente ICCID (19-20 cifre) e seleziona l'operatore.</p>

        <div data-live-slot="feedback">
            <?php if ($feedback !== null): ?>
                <div class="alert <?= $feedback['success'] ? 'alert--success' : 'alert--error' ?>">
                    <p><?= htmlspecialchars($feedback['message']) ?></p>
                    <?php if (!empty($feedback['error'])): ?>
                        <p class="muted">Dettaglio: <?= htmlspecialchars($feedback['error']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($feedback['errors']) && is_array($feedback['errors'])): ?>
                        <?php foreach ($feedback['errors'] as $error): ?>
                            <p class="muted"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" class="form" autocomplete="off" data-live-form>
            <input type="hidden" name="action" value="add_sim">
            <input type="hidden" name="page_no" value="<?= (int) $pagination['page'] ?>">
            <input type="hidden" name="per_page" value="<?= (int) $pagination['per_page'] ?>">
            <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <input type="hidden" name="filter_provider_id" value="<?= (int) $providerFilter ?>">
            <div class="form__grid">
                <div class="form__group">
                    <label for="iccid">ICCID</label>
                    <input type="text" name="iccid" id="iccid" pattern="[0-9]{19,20}" minlength="19" maxlength="20" placeholder="Esempio: 8931..." required>
                </div>
                <div class="form__group">
                    <label for="provider_id">Operatore</label>
                    <select name="provider_id" id="provider_id" required>
                        <option value="">Seleziona</option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?= (int) $provider['id'] ?>"><?= htmlspecialchars((string) $provider['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="notes">Note</label>
                    <input type="text" name="notes" id="notes" placeholder="Note facoltative">
                </div>
            </div>
            <div class="form__footer">
                <button type="submit" class="btn btn--primary">Salva in magazzino</button>
            </div>
        </form>
    </section>

    <section class="page__section">
        <h3>Aggiunta massiva</h3>
        <p class="muted">Incolla una lista di ICCID (uno per riga o separati da virgola). Puoi caricare decine di SIM in un'unica volta.</p>

        <form method="post" class="form" autocomplete="off" data-live-form>
            <input type="hidden" name="action" value="bulk_add">
            <input type="hidden" name="page_no" value="<?= (int) $pagination['page'] ?>">
            <input type="hidden" name="per_page" value="<?= (int) $pagination['per_page'] ?>">
            <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <input type="hidden" name="filter_provider_id" value="<?= (int) $providerFilter ?>">
            <div class="form__grid">
                <div class="form__group">
                    <label for="bulk_iccids">Lista ICCID</label>
                    <textarea
                        name="bulk_iccids"
                        id="bulk_iccids"
                        rows="6"
                        placeholder="8939123456789012345&#10;89391234567890123456&#10;89391234567890123457"
                        required
                    ></textarea>
                </div>
                <div class="form__group">
                    <label for="bulk_provider_id">Operatore</label>
                    <select name="provider_id" id="bulk_provider_id" required>
                        <option value="">Seleziona</option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?= (int) $provider['id'] ?>"><?= htmlspecialchars((string) $provider['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="bulk_notes">Note</label>
                    <input type="text" name="bulk_notes" id="bulk_notes" placeholder="Note facoltative per tutte le SIM">
                </div>
            </div>
            <div class="form__footer">
                <button type="submit" class="btn btn--primary">Carica lista SIM</button>
            </div>
        </form>
    </section>

    <section class="page__section">
        <div class="section__header">
            <div>
                <h3>SIM a magazzino</h3>
                <p class="muted" data-live-slot="status">Ultimo aggiornamento: <span data-live-slot="timestamp">--:--</span></p>
            </div>
            <form method="get" class="search-field" id="sim-stock-filter-form" data-live-form>
                <input type="hidden" name="page" value="sim_stock">
                <span class="search-field__icon" aria-hidden="true">🔎</span>
                <input
                    type="text"
                    name="search"
                    class="search-field__control"
                    placeholder="Cerca ICCID o operatore"
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    data-auto-search
                >
                <select name="status" class="search-field__control search-field__control--compact" data-auto-submit>
                    <option value="">Tutti gli stati</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <?php $label = $statusLabels[$status] ?? $status; ?>
                        <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="provider_id" class="search-field__control search-field__control--compact" data-auto-submit>
                    <option value="">Tutti gli operatori</option>
                    <?php foreach ($providers as $provider): ?>
                        <option value="<?= (int) $provider['id'] ?>" <?= $providerFilter === (int) $provider['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $provider['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="per_page" class="search-field__control search-field__control--compact" data-auto-submit>
                    <?php foreach ([7, 14, 25, 50] as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?> / pagina</option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="table-wrapper">
            <table class="table" data-live-slot="table">
                <thead>
                    <tr>
                        <th>ICCID</th>
                        <th>Operatore</th>
                        <th>Stato</th>
                        <th>Ultimo aggiornamento</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody data-live-slot="rows">
                    <?php if ($stock === []): ?>
                        <tr><td colspan="5">Nessuna SIM presente.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stock as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $row['iccid']) ?></td>
                                <td><?= htmlspecialchars((string) $row['provider_name']) ?></td>
                                <td><?= htmlspecialchars((string) $row['status']) ?></td>
                                <td><?= htmlspecialchars($formatDate($row['updated_at'] ?? null)) ?></td>
                                <td><?= htmlspecialchars((string) ($row['notes'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination['pages'] > 1): ?>
            <nav class="pagination" data-live-slot="pagination">
                <?php $current = (int) $pagination['page']; ?>
                <a class="pagination__link <?= $current === 1 ? 'is-disabled' : '' ?>" href="<?= $current === 1 ? '#' : $buildStockPageUrl(1) ?>">«</a>
                <a class="pagination__link <?= $current === 1 ? 'is-disabled' : '' ?>" href="<?= $current === 1 ? '#' : $buildStockPageUrl($current - 1) ?>">‹</a>
                <span class="pagination__info">Pagina <?= $current ?> di <?= (int) $pagination['pages'] ?> (<?= (int) $pagination['total'] ?> risultati)</span>
                <a class="pagination__link <?= $current === (int) $pagination['pages'] ? 'is-disabled' : '' ?>" href="<?= $current === (int) $pagination['pages'] ? '#' : $buildStockPageUrl($current + 1) ?>">›</a>
                <a class="pagination__link <?= $current === (int) $pagination['pages'] ? 'is-disabled' : '' ?>" href="<?= $current === (int) $pagination['pages'] ? '#' : $buildStockPageUrl((int) $pagination['pages']) ?>">»</a>
            </nav>
        <?php else: ?>
            <nav class="pagination" data-live-slot="pagination" hidden></nav>
        <?php endif; ?>
    </section>
</section>

<script>
(function () {
    const container = document.querySelector('[data-live-refresh="sim_stock"]');
    const form = document.getElementById('sim-stock-filter-form');
    if (!container || !form) {
        return;
    }

    const submitForm = () => {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    };

    let debounceId = 0;
    const searchInput = form.querySelector('[data-auto-search]');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            window.clearTimeout(debounceId);
            debounceId = window.setTimeout(submitForm, 350);
        });
    }

    form.querySelectorAll('[data-auto-submit]').forEach((field) => {
        field.addEventListener('change', submitForm);
    });

    form.addEventListener('submit', () => {
        const url = new URL(form.getAttribute('action') || window.location.href, window.location.href);
        const data = new FormData(form);
        data.forEach((value, key) => {
            url.searchParams.set(key, String(value));
        });
        if (!url.searchParams.has('page')) {
            url.searchParams.set('page', 'sim_stock');
        }
        url.searchParams.set('action', 'refresh');
        url.searchParams.delete('page_no');
        container.dataset.refreshUrl = url.toString();
    });
})();
</script>
