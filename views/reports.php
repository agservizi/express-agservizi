<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $report
 * @var array<string, string> $filters
 * @var array<string, mixed> $filterOptions
 * @var string $view
 */
$pageTitle = $pageTitle ?? 'Report vendite';
$report = $report ?? [];
$view = $view ?? ($report['granularity'] ?? 'daily');
$filters = $filters ?? ($report['filters'] ?? []);
$filterOptions = $filterOptions ?? ($report['filter_options'] ?? []);
$filters = array_merge(['date' => '', 'month' => '', 'year' => '', 'payment' => '', 'operator_id' => ''], $filters);
$paymentOptions = array_values(array_filter(
    (array) ($filterOptions['payments'] ?? []),
    static fn ($value): bool => is_string($value) && $value !== ''
));
$operatorOptionsRaw = array_filter(
    (array) ($filterOptions['operators'] ?? []),
    static fn ($row): bool => is_array($row)
);
$operatorOptions = array_values(array_filter(array_map(
    static function (array $operator): array {
        return [
            'id' => (int) ($operator['id'] ?? 0),
            'name' => (string) ($operator['name'] ?? ''),
        ];
    },
    $operatorOptionsRaw
), static fn (array $option): bool => $option['id'] > 0));
$selectedPayment = (string) $filters['payment'];
$selectedOperator = (string) $filters['operator_id'];
$totals = array_merge([
    'sales_count' => 0,
    'gross_revenue' => 0.0,
    'net_revenue' => 0.0,
    'discount_total' => 0.0,
    'refund_total' => 0.0,
    'credit_total' => 0.0,
    'average_ticket' => 0.0,
    'average_ticket_net' => 0.0,
], (array) ($report['totals'] ?? []));
$payments = (array) ($report['payments'] ?? []);
$operators = (array) ($report['operators'] ?? []);
$trend = (array) ($report['trend'] ?? []);
$trendPoints = (array) ($trend['points'] ?? []);
$energy = (array) ($report['energy'] ?? []);
$energyTotals = array_merge([
    'contracts' => 0,
    'total_commission' => 0.0,
    'average_commission' => 0.0,
], (array) ($energy['totals'] ?? []));
$energyProviders = (array) ($energy['providers'] ?? []);
$energyTypes = (array) ($energy['types'] ?? []);
$energyTrend = (array) ($energy['trend'] ?? []);
$energyTrendPoints = (array) ($energyTrend['points'] ?? []);
$comparison = (array) ($report['comparison'] ?? []);
$comparisonDeltas = (array) ($comparison['deltas'] ?? []);
$previousPeriodLabel = (string) (($comparison['previous_period']['label'] ?? '') ?: 'Periodo precedente');
$maxEnergyCount = 0;
$maxEnergyCommission = 0.0;
foreach ($energyTrendPoints as $point) {
    $count = (int) ($point['contract_count'] ?? 0);
    $commission = max((float) ($point['total_commission'] ?? 0.0), 0.0);
    if ($count > $maxEnergyCount) {
        $maxEnergyCount = $count;
    }
    if ($commission > $maxEnergyCommission) {
        $maxEnergyCommission = $commission;
    }
}
$period = (array) ($report['period'] ?? []);
$referenceForFile = $period['reference'] ?? '';
if ($view === 'daily' && $filters['date'] !== '') {
    $referenceForFile = (string) $filters['date'];
} elseif ($view === 'monthly' && $filters['month'] !== '') {
    $referenceForFile = (string) $filters['month'];
} elseif ($view === 'yearly' && $filters['year'] !== '') {
    $referenceForFile = (string) $filters['year'];
}
if ($referenceForFile === '') {
    $referenceForFile = date('Y-m-d');
}
$referenceSlug = preg_replace('/[^0-9\-]/', '', str_replace(['/', ' '], '-', (string) $referenceForFile));
if ($referenceSlug === '') {
    $referenceSlug = date('Ymd');
}
$exportFilename = sprintf('report-%s-%s.pdf', $view, $referenceSlug);
$exportCsvFilename = sprintf('report-%s-%s.csv', $view, $referenceSlug);
$exportCsvUrl = 'index.php?page=reports_export&format=csv&' . http_build_query([
    'view' => $view,
    'date' => (string) $filters['date'],
    'month' => (string) $filters['month'],
    'year' => (string) $filters['year'],
    'payment' => (string) $filters['payment'],
    'operator_id' => (string) $filters['operator_id'],
]);
$viewLabels = [
    'daily' => 'Giornaliero',
    'monthly' => 'Mensile',
    'yearly' => 'Annuale',
];
$viewDescriptions = [
    'daily' => 'Analisi delle vendite giornaliere con focus sulle ultime 24 ore.',
    'monthly' => 'Statistiche aggregate per il mese selezionato.',
    'yearly' => 'Panoramica su base annuale per confrontare l\'andamento.',
];
$formatCurrency = static function (float $value): string {
    return number_format($value, 2, ',', '.');
};
$formatDeltaBadge = static function (?array $delta, string $format, string $previousPeriodLabel) use ($formatCurrency): ?array {
    if (!is_array($delta)) {
        return null;
    }
    $direction = (string) ($delta['direction'] ?? 'flat');
    $class = match ($direction) {
        'up' => 'delta-badge--up',
        'down' => 'delta-badge--down',
        default => 'delta-badge--flat',
    };
    $absolute = (float) ($delta['absolute'] ?? 0.0);
    $percent = $delta['percent'] ?? null;
    if ($percent !== null) {
        $prefix = $percent > 0 ? '+' : '';
        $label = $prefix . number_format((float) $percent, 1, ',', '.') . '%';
    } else {
        $prefix = $absolute > 0 ? '+' : ($absolute < 0 ? '-' : '');
        $value = abs($absolute);
        $label = $format === 'currency'
            ? $prefix . $formatCurrency($value) . '€'
            : $prefix . number_format($value, 0, ',', '.');
    }

    return [
        'class' => $class,
        'label' => $label,
        'title' => $previousPeriodLabel,
    ];
};
$rangeStart = null;
$rangeEnd = null;
try {
    if (!empty($period['start'])) {
        $rangeStart = new DateTimeImmutable((string) $period['start']);
    }
    if (!empty($period['end'])) {
        $rangeEnd = (new DateTimeImmutable((string) $period['end']))->modify('-1 day');
    }
} catch (\Throwable) {
    $rangeStart = null;
    $rangeEnd = null;
}
$rangeLabel = $period['label'] ?? '';
if ($rangeStart !== null && $rangeEnd !== null) {
    $rangeLabel = sprintf(
        '%s · %s → %s',
        $period['label'] ?? $viewLabels[$view] ?? 'Periodo',
        $rangeStart->format('d/m/Y'),
        $rangeEnd->format('d/m/Y')
    );
}
$maxTrendCount = 0;
$maxTrendRevenue = 0.0;
foreach ($trendPoints as $point) {
    $count = (int) ($point['sale_count'] ?? 0);
    $net = max((float) ($point['net_revenue'] ?? 0.0), 0.0);
    if ($count > $maxTrendCount) {
        $maxTrendCount = $count;
    }
    if ($net > $maxTrendRevenue) {
        $maxTrendRevenue = $net;
    }
}
$trendSummary = sprintf(
    'Totale: %d vendite · Incasso netto € %s',
    (int) ($trend['total_count'] ?? 0),
    $formatCurrency((float) ($trend['total_net'] ?? 0.0))
);
$chartTrendLabels = json_encode(array_map(static fn (array $p): string => (string) ($p['label'] ?? ''), $trendPoints), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartTrendNet = json_encode(array_map(static fn (array $p): float => (float) ($p['net_revenue'] ?? 0.0), $trendPoints), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartTrendCount = json_encode(array_map(static fn (array $p): int => (int) ($p['sale_count'] ?? 0), $trendPoints), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartEnergyLabels = json_encode(array_map(static fn (array $p): string => (string) ($p['label'] ?? ''), $energyTrendPoints), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartEnergyCommission = json_encode(array_map(static fn (array $p): float => (float) ($p['total_commission'] ?? 0.0), $energyTrendPoints), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartEnergyCount = json_encode(array_map(static fn (array $p): int => (int) ($p['contract_count'] ?? 0), $energyTrendPoints), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartPaymentLabels = json_encode(array_map(static fn (array $p): string => (string) ($p['method'] ?? ''), $payments), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartPaymentNet = json_encode(array_map(static fn (array $p): float => (float) ($p['net_revenue'] ?? 0.0), $payments), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartOperatorLabels = json_encode(array_map(static fn (array $p): string => (string) ($p['operator_name'] ?? ''), $operators), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$chartOperatorNet = json_encode(array_map(static fn (array $p): float => (float) ($p['net_revenue'] ?? 0.0), $operators), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<section class="page">
    <header class="page__header">
        <h2>Report vendite</h2>
        <p><?= htmlspecialchars($viewDescriptions[$view] ?? 'Analisi delle vendite registrate.') ?></p>
    </header>

    <div id="report-export-target">
        <form method="get" class="filters-bar" id="reports-filter-form" data-html2canvas-ignore="true">
            <input type="hidden" name="page" value="reports">
            <div class="filters-bar__row" style="flex-wrap: wrap; gap: 1rem;">
                <div class="form__group">
                    <label for="filter-view">Intervallo</label>
                    <select name="view" id="filter-view" data-report-filter="view">
                        <?php foreach ($viewLabels as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $view === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group" data-report-filter="date"<?= $view === 'daily' ? '' : ' hidden' ?>>
                    <label for="filter-date">Giorno</label>
                    <input type="date" name="date" id="filter-date" value="<?= htmlspecialchars((string) $filters['date']) ?>">
                </div>
                <div class="form__group" data-report-filter="month"<?= $view === 'monthly' ? '' : ' hidden' ?>>
                    <label for="filter-month">Mese</label>
                    <input type="month" name="month" id="filter-month" value="<?= htmlspecialchars((string) $filters['month']) ?>">
                </div>
                <div class="form__group" data-report-filter="year"<?= $view === 'yearly' ? '' : ' hidden' ?>>
                    <label for="filter-year">Anno</label>
                    <input type="number" name="year" id="filter-year" min="2000" max="<?= (int) date('Y') + 1 ?>" value="<?= htmlspecialchars((string) $filters['year']) ?>">
                </div>
            </div>
            <div class="filters-bar__row" style="flex-wrap: wrap; gap: 1rem;">
                <div class="form__group">
                    <label for="filter-payment">Metodo di pagamento</label>
                    <select name="payment" id="filter-payment">
                        <option value="">Tutti</option>
                        <?php foreach ($paymentOptions as $method): ?>
                            <option value="<?= htmlspecialchars($method) ?>" <?= $selectedPayment === $method ? 'selected' : '' ?>><?= htmlspecialchars($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form__group">
                    <label for="filter-operator">Operatore</label>
                    <select name="operator_id" id="filter-operator">
                        <option value="">Tutti</option>
                        <?php foreach ($operatorOptions as $option): ?>
                            <?php $optionName = $option['name'] !== '' ? $option['name'] : 'Operatore #' . (int) $option['id']; ?>
                            <option value="<?= (int) $option['id'] ?>" <?= $selectedOperator === (string) $option['id'] ? 'selected' : '' ?>><?= htmlspecialchars($optionName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="filters-bar__actions filters-bar__actions--quick" data-html2canvas-ignore="true">
                <span class="filters-bar__label">Scorciatoie:</span>
                <button type="button" class="btn btn--chip" data-report-quick-action="today">Oggi</button>
                <button type="button" class="btn btn--chip" data-report-quick-action="yesterday">Ieri</button>
                <button type="button" class="btn btn--chip" data-report-quick-action="this-month">Questo mese</button>
                <button type="button" class="btn btn--chip" data-report-quick-action="last-month">Mese scorso</button>
                <button type="button" class="btn btn--chip" data-report-quick-action="this-year">Anno corrente</button>
                <button type="button" class="btn btn--chip" data-report-quick-action="last-year">Anno scorso</button>
            </div>
            <div class="filters-bar__actions">
                <button type="submit" class="btn btn--primary">Applica filtri</button>
                <a class="btn btn--secondary" href="index.php?page=reports&amp;view=<?= htmlspecialchars($view) ?>">Reset</a>
                <button type="button" class="btn btn--secondary" id="btn-report-export" data-filename="<?= htmlspecialchars($exportFilename) ?>">Esporta PDF</button>
                <a class="btn btn--secondary" href="<?= htmlspecialchars($exportCsvUrl) ?>" download="<?= htmlspecialchars($exportCsvFilename) ?>">Esporta CSV</a>
            </div>
        </form>

        <p class="muted" style="margin-bottom: 1.5rem;">Periodo analizzato: <?= htmlspecialchars($rangeLabel) ?></p>

        <div class="cards" data-draggable-container="reports-metrics">
            <article class="card" data-draggable-card="metric-net">
                <h3>Incasso netto</h3>
                <p class="card__value">€ <?= $formatCurrency((float) $totals['net_revenue']) ?></p>
                <?php $delta = $formatDeltaBadge($comparisonDeltas['net_revenue'] ?? null, 'currency', $previousPeriodLabel); ?>
                <?php if ($delta): ?>
                    <p class="card__meta"><span class="delta-badge <?= $delta['class'] ?>" title="<?= htmlspecialchars($delta['title']) ?>"><?= htmlspecialchars($delta['label']) ?></span> vs periodo precedente</p>
                <?php else: ?>
                    <p class="card__meta">Ticket medio netto € <?= $formatCurrency((float) $totals['average_ticket_net']) ?></p>
                <?php endif; ?>
            </article>
            <article class="card" data-draggable-card="metric-gross">
                <h3>Incasso lordo</h3>
                <p class="card__value">€ <?= $formatCurrency((float) $totals['gross_revenue']) ?></p>
                <?php $delta = $formatDeltaBadge($comparisonDeltas['gross_revenue'] ?? null, 'currency', $previousPeriodLabel); ?>
                <?php if ($delta): ?>
                    <p class="card__meta"><span class="delta-badge <?= $delta['class'] ?>" title="<?= htmlspecialchars($delta['title']) ?>"><?= htmlspecialchars($delta['label']) ?></span> vs periodo precedente</p>
                <?php else: ?>
                    <p class="card__meta">Ticket medio € <?= $formatCurrency((float) $totals['average_ticket']) ?></p>
                <?php endif; ?>
            </article>
            <article class="card" data-draggable-card="metric-count">
                <h3>Vendite registrate</h3>
                <p class="card__value"><?= (int) $totals['sales_count'] ?></p>
                <?php $delta = $formatDeltaBadge($comparisonDeltas['sales_count'] ?? null, 'number', $previousPeriodLabel); ?>
                <?php if ($delta): ?>
                    <p class="card__meta"><span class="delta-badge <?= $delta['class'] ?>" title="<?= htmlspecialchars($delta['title']) ?>"><?= htmlspecialchars($delta['label']) ?></span> vs periodo precedente</p>
                <?php else: ?>
                    <p class="card__meta">Sconti erogati € <?= $formatCurrency((float) $totals['discount_total']) ?></p>
                <?php endif; ?>
            </article>
            <article class="card" data-draggable-card="metric-refund">
                <h3>Resi e crediti</h3>
                <p class="card__value">€ <?= $formatCurrency((float) $totals['refund_total'] + (float) $totals['credit_total']) ?></p>
                <p class="card__meta">Resi € <?= $formatCurrency((float) $totals['refund_total']) ?> · Crediti € <?= $formatCurrency((float) $totals['credit_total']) ?></p>
            </article>
    </div>

    <div class="dashboard-grid" data-draggable-container="reports-panels">
        <section class="dashboard-panel dashboard-panel--wide" data-draggable-card="panel-trend">
            <header class="dashboard-panel__header">
                <h3>Tendenza <?= htmlspecialchars($viewLabels[$view] ?? 'Periodo') ?></h3>
                <div class="report-chart-tools">
                    <p class="dashboard-panel__meta"><?= htmlspecialchars($trendSummary) ?></p>
                    <div class="report-chart-tools__actions">
                        <label class="report-chart-tools__label">
                            Ultimi
                            <select data-chart-filter="trend-range">
                                <option value="0">Tutti</option>
                                <option value="7">7</option>
                                <option value="30">30</option>
                                <option value="90">90</option>
                            </select>
                        </label>
                        <button type="button" class="btn btn--chip" data-chart-download="reportTrendNet">PNG netto</button>
                        <button type="button" class="btn btn--chip" data-chart-download="reportTrendCount">PNG vendite</button>
                    </div>
                </div>
            </header>
            <div class="report-chart-grid">
                <div class="report-chart">
                    <canvas id="reportTrendNet" height="160" aria-label="Trend incasso netto" role="img"></canvas>
                </div>
                <div class="report-chart">
                    <canvas id="reportTrendCount" height="160" aria-label="Trend vendite" role="img"></canvas>
                </div>
            </div>
            <?php if ($trendPoints === []): ?>
                <p class="muted">Non ci sono vendite nel periodo selezionato.</p>
            <?php else: ?>
                <div class="trend-grid">
                    <?php foreach ($trendPoints as $point): ?>
                        <?php
                            $count = (int) ($point['sale_count'] ?? 0);
                            $net = (float) ($point['net_revenue'] ?? 0.0);
                            $countPct = $maxTrendCount > 0 ? (int) round(($count / $maxTrendCount) * 100) : 0;
                            $revenuePct = $maxTrendRevenue > 0 ? (int) round((max($net, 0.0) / $maxTrendRevenue) * 100) : 0;
                            $label = (string) ($point['label'] ?? '');
                        ?>
                        <article class="trend-card" title="Vendite <?= $count ?> · Netto € <?= $formatCurrency($net) ?>">
                            <span class="trend-card__label"><?= htmlspecialchars($label) ?></span>
                            <span class="trend-card__value"><?= $count ?> vendite</span>
                            <div class="trend-bar">
                                <span class="trend-bar__fill" style="width: <?= $countPct ?>%;"></span>
                            </div>
                            <div class="trend-bar trend-bar--secondary">
                                <span class="trend-bar__fill" style="width: <?= $revenuePct ?>%;"></span>
                            </div>
                            <span class="trend-card__meta">Netto € <?= $formatCurrency($net) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel" data-draggable-card="panel-payments">
            <header class="dashboard-panel__header">
                <h3>Metodi di pagamento</h3>
                <div class="report-chart-tools">
                    <p class="dashboard-panel__meta">Distribuzione incassi</p>
                    <div class="report-chart-tools__actions">
                        <label class="report-chart-tools__label">
                            Top
                            <select data-chart-filter="payments-top">
                                <option value="0">Tutti</option>
                                <option value="5">5</option>
                                <option value="10">10</option>
                            </select>
                        </label>
                        <button type="button" class="btn btn--chip" data-chart-download="reportPayments">PNG</button>
                    </div>
                </div>
            </header>
            <div class="report-chart">
                <canvas id="reportPayments" height="200" aria-label="Distribuzione incassi" role="img"></canvas>
            </div>
            <?php if ($payments === []): ?>
                <p class="muted">Nessuna vendita disponibile per il periodo filtrato.</p>
            <?php else: ?>
                <div class="table-wrapper table-wrapper--embedded">
                    <table class="table table--compact">
                        <thead>
                            <tr>
                                <th>Metodo</th>
                                <th>Vendite</th>
                                <th>Incasso lordo</th>
                                <th>Incasso netto</th>
                                <th>Sconti</th>
                                <th>Resi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $row['method']) ?></td>
                                    <td><?= (int) $row['sales_count'] ?></td>
                                    <td>€ <?= $formatCurrency((float) $row['gross_revenue']) ?></td>
                                    <td>€ <?= $formatCurrency((float) $row['net_revenue']) ?></td>
                                    <td>€ <?= $formatCurrency((float) $row['discount_total']) ?></td>
                                    <td>€ <?= $formatCurrency((float) $row['refund_total'] + (float) $row['credit_total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel" data-draggable-card="panel-operators">
            <header class="dashboard-panel__header">
                <h3>Top operatori</h3>
                <div class="report-chart-tools">
                    <p class="dashboard-panel__meta">Netto periodo selezionato</p>
                    <div class="report-chart-tools__actions">
                        <label class="report-chart-tools__label">
                            Top
                            <select data-chart-filter="operators-top">
                                <option value="0">Tutti</option>
                                <option value="5">5</option>
                                <option value="10">10</option>
                            </select>
                        </label>
                        <button type="button" class="btn btn--chip" data-chart-download="reportOperators">PNG</button>
                    </div>
                </div>
            </header>
            <div class="report-chart">
                <canvas id="reportOperators" height="220" aria-label="Incasso netto per operatore" role="img"></canvas>
            </div>
            <?php if ($operators === []): ?>
                <p class="muted">Nessuna vendita registrata dagli operatori nel periodo.</p>
            <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($operators as $operator): ?>
                        <li class="activity-entry">
                            <span class="activity-entry__title"><?= htmlspecialchars((string) $operator['operator_name']) ?></span>
                            <span class="activity-entry__meta">Vendite <?= (int) $operator['sales_count'] ?> · Sconti € <?= $formatCurrency((float) $operator['discount_total']) ?></span>
                            <span class="activity-entry__value">Netto € <?= $formatCurrency((float) $operator['net_revenue']) ?> · Lordo € <?= $formatCurrency((float) $operator['gross_revenue']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel dashboard-panel--wide" data-draggable-card="panel-energy">
            <header class="dashboard-panel__header">
                <h3>Contratti energia</h3>
                <div class="report-chart-tools">
                    <p class="dashboard-panel__meta">Sintesi periodo selezionato</p>
                    <div class="report-chart-tools__actions">
                        <label class="report-chart-tools__label">
                            Ultimi
                            <select data-chart-filter="energy-range">
                                <option value="0">Tutti</option>
                                <option value="7">7</option>
                                <option value="30">30</option>
                                <option value="90">90</option>
                            </select>
                        </label>
                        <button type="button" class="btn btn--chip" data-chart-download="reportEnergyCommission">PNG provvigioni</button>
                        <button type="button" class="btn btn--chip" data-chart-download="reportEnergyCount">PNG contratti</button>
                    </div>
                </div>
            </header>
            <div class="report-chart-grid">
                <div class="report-chart">
                    <canvas id="reportEnergyCommission" height="160" aria-label="Trend provvigioni energia" role="img"></canvas>
                </div>
                <div class="report-chart">
                    <canvas id="reportEnergyCount" height="160" aria-label="Trend contratti energia" role="img"></canvas>
                </div>
            </div>
            <ul class="insight-list">
                <li class="insight-list__item">
                    <span class="insight-list__label">Contratti registrati</span>
                    <span class="insight-list__value"><?= (int) $energyTotals['contracts'] ?></span>
                </li>
                <li class="insight-list__item">
                    <span class="insight-list__label">Provvigioni totali</span>
                    <span class="insight-list__value">€ <?= $formatCurrency((float) $energyTotals['total_commission']) ?></span>
                </li>
                <li class="insight-list__item">
                    <span class="insight-list__label">Provvigione media</span>
                    <span class="insight-list__value">€ <?= $formatCurrency((float) $energyTotals['average_commission']) ?></span>
                </li>
            </ul>
            <div class="insight-split">
                <div>
                    <h4 class="insight-title">Top gestori</h4>
                    <?php if ($energyProviders === []): ?>
                        <p class="muted">Nessun contratto energia nel periodo.</p>
                    <?php else: ?>
                        <ul class="insight-list">
                            <?php foreach ($energyProviders as $provider): ?>
                                <li class="insight-list__item">
                                    <span class="insight-list__label"><?= htmlspecialchars((string) ($provider['name'] ?? 'Gestore')) ?></span>
                                    <span class="insight-list__value"><?= (int) ($provider['contracts'] ?? 0) ?> · € <?= $formatCurrency((float) ($provider['total_commission'] ?? 0.0)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div>
                    <h4 class="insight-title">Tipologie contratto</h4>
                    <?php if ($energyTypes === []): ?>
                        <p class="muted">Nessuna tipologia registrata.</p>
                    <?php else: ?>
                        <ul class="insight-list">
                            <?php foreach ($energyTypes as $type): ?>
                                <li class="insight-list__item">
                                    <span class="insight-list__label"><?= htmlspecialchars((string) ($type['type'] ?? 'n/d')) ?></span>
                                    <span class="insight-list__value"><?= (int) ($type['contracts'] ?? 0) ?> · € <?= $formatCurrency((float) ($type['total_commission'] ?? 0.0)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <h4 class="insight-title">Trend contratti energia</h4>
            <?php if ($energyTrendPoints === []): ?>
                <p class="muted">Nessun contratto energia nel periodo.</p>
            <?php else: ?>
                <div class="trend-grid">
                    <?php foreach ($energyTrendPoints as $point): ?>
                        <?php
                            $count = (int) ($point['contract_count'] ?? 0);
                            $commission = (float) ($point['total_commission'] ?? 0.0);
                            $countPct = $maxEnergyCount > 0 ? (int) round(($count / $maxEnergyCount) * 100) : 0;
                            $commissionPct = $maxEnergyCommission > 0 ? (int) round((max($commission, 0.0) / $maxEnergyCommission) * 100) : 0;
                            $label = (string) ($point['label'] ?? '');
                        ?>
                        <article class="trend-card" title="Contratti <?= $count ?> · Provvigioni € <?= $formatCurrency($commission) ?>">
                            <span class="trend-card__label"><?= htmlspecialchars($label) ?></span>
                            <span class="trend-card__value"><?= $count ?> contratti</span>
                            <div class="trend-bar">
                                <span class="trend-bar__fill" style="width: <?= $countPct ?>%;"></span>
                            </div>
                            <div class="trend-bar trend-bar--secondary">
                                <span class="trend-bar__fill" style="width: <?= $commissionPct ?>%;"></span>
                            </div>
                            <span class="trend-card__meta">Provvigioni € <?= $formatCurrency($commission) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    const form = document.getElementById('reports-filter-form');
    if (!form) {
        return;
    }

    const viewSelect = form.querySelector('[data-report-filter="view"]');
    const controls = {
        daily: form.querySelector('[data-report-filter="date"]'),
        monthly: form.querySelector('[data-report-filter="month"]'),
        yearly: form.querySelector('[data-report-filter="year"]'),
    };

    const toggleControls = () => {
    const mode = ((viewSelect && viewSelect.value) ? viewSelect.value : 'daily').toLowerCase();
        Object.entries(controls).forEach(([key, element]) => {
            if (!element) {
                return;
            }
            if (key === mode) {
                element.removeAttribute('hidden');
            } else {
                element.setAttribute('hidden', 'hidden');
            }
        });
    };

    toggleControls();
    if (viewSelect) {
        viewSelect.addEventListener('change', toggleControls);
    }

    const dateInput = form.querySelector('#filter-date');
    const monthInput = form.querySelector('#filter-month');
    const yearInput = form.querySelector('#filter-year');
    const quickButtons = form.querySelectorAll('[data-report-quick-action]');

    const toLocalDate = (date) => {
        const local = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
        return local.toISOString().slice(0, 10);
    };
    const toLocalMonth = (date) => {
        const local = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
        return local.toISOString().slice(0, 7);
    };

    const applyQuick = (action) => {
        const today = new Date();
        switch (action) {
            case 'today': {
                if (viewSelect) viewSelect.value = 'daily';
                if (dateInput) dateInput.value = toLocalDate(today);
                break;
            }
            case 'yesterday': {
                const d = new Date(today);
                d.setDate(d.getDate() - 1);
                if (viewSelect) viewSelect.value = 'daily';
                if (dateInput) dateInput.value = toLocalDate(d);
                break;
            }
            case 'this-month': {
                if (viewSelect) viewSelect.value = 'monthly';
                if (monthInput) monthInput.value = toLocalMonth(today);
                break;
            }
            case 'last-month': {
                const d = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                if (viewSelect) viewSelect.value = 'monthly';
                if (monthInput) monthInput.value = toLocalMonth(d);
                break;
            }
            case 'this-year': {
                if (viewSelect) viewSelect.value = 'yearly';
                if (yearInput) yearInput.value = String(today.getFullYear());
                break;
            }
            case 'last-year': {
                if (viewSelect) viewSelect.value = 'yearly';
                if (yearInput) yearInput.value = String(today.getFullYear() - 1);
                break;
            }
            default:
                return;
        }
        toggleControls();
        form.submit();
    };

    quickButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.getAttribute('data-report-quick-action') || '';
            applyQuick(action);
        });
    });

    const exportBtn = document.getElementById('btn-report-export');
    const exportTarget = document.getElementById('report-export-target');
    if (!exportBtn || !exportTarget) {
        return;
    }

    exportBtn.addEventListener('click', async () => {
        if (typeof window.html2pdf !== 'function') {
            alert('Impossibile generare il PDF: libreria non caricata.');
            return;
        }

        const originalLabel = exportBtn.textContent;
        exportBtn.disabled = true;
        exportBtn.textContent = 'Generazione PDF...';

        try {
            await window.html2pdf()
                .set({
                    filename: exportBtn.dataset.filename || 'report.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                })
                .from(exportTarget)
                .save();
        } catch (error) {
            console.error('Report PDF export error', error);
            alert('Si è verificato un errore durante la generazione del PDF.');
        } finally {
            exportBtn.disabled = false;
            if (originalLabel) {
                exportBtn.textContent = originalLabel;
            }
        }
    });

    if (window.Chart) {
        const labelsTrend = <?= $chartTrendLabels ?: '[]' ?>;
        const trendNet = <?= $chartTrendNet ?: '[]' ?>;
        const trendCount = <?= $chartTrendCount ?: '[]' ?>;
        const labelsEnergy = <?= $chartEnergyLabels ?: '[]' ?>;
        const energyCommission = <?= $chartEnergyCommission ?: '[]' ?>;
        const energyCount = <?= $chartEnergyCount ?: '[]' ?>;
        const labelsPayments = <?= $chartPaymentLabels ?: '[]' ?>;
        const paymentNet = <?= $chartPaymentNet ?: '[]' ?>;
        const labelsOperators = <?= $chartOperatorLabels ?: '[]' ?>;
        const operatorNet = <?= $chartOperatorNet ?: '[]' ?>;

        const formatCurrency = (value) => new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0);
        const formatNumber = (value) => new Intl.NumberFormat('it-IT', { maximumFractionDigits: 0 }).format(value || 0);
        const tooltipCurrency = (context) => {
            const value = context.parsed?.y ?? context.parsed ?? 0;
            return `€ ${formatCurrency(value)}`;
        };
        const tooltipCount = (context) => {
            const value = context.parsed?.y ?? context.parsed ?? 0;
            return `${formatNumber(value)}`;
        };

        const createLine = (ctx, labels, data, label, color) => new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label,
                    data,
                    borderColor: color,
                    backgroundColor: color + '33',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: label.toLowerCase().includes('vendite') || label.toLowerCase().includes('contratti')
                                ? tooltipCount
                                : tooltipCurrency,
                        },
                    },
                },
                scales: { y: { beginAtZero: true } },
            },
        });

        const createBar = (ctx, labels, data, label) => new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label,
                    data,
                    backgroundColor: 'rgba(37,99,235,0.45)',
                    borderColor: 'rgba(37,99,235,0.9)',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: tooltipCurrency } },
                },
                scales: { y: { beginAtZero: true } },
            },
        });

        const createDoughnut = (ctx, labels, data) => new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: ['#2563eb', '#38bdf8', '#22c55e', '#f59e0b', '#f97316', '#ef4444'],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const value = context.parsed ?? 0;
                                const total = context.dataset.data.reduce((sum, item) => sum + (item || 0), 0) || 1;
                                const pct = (value / total) * 100;
                                return `${context.label}: € ${formatCurrency(value)} (${pct.toFixed(1)}%)`;
                            },
                        },
                    },
                },
            },
        });

        const trendNetCanvas = document.getElementById('reportTrendNet');
        const trendCountCanvas = document.getElementById('reportTrendCount');
        const energyCommissionCanvas = document.getElementById('reportEnergyCommission');
        const energyCountCanvas = document.getElementById('reportEnergyCount');
        const paymentsCanvas = document.getElementById('reportPayments');
        const operatorsCanvas = document.getElementById('reportOperators');

        const charts = {};

        if (trendNetCanvas && labelsTrend.length) {
            charts.reportTrendNet = createLine(trendNetCanvas, labelsTrend, trendNet, 'Incasso netto', '#2563eb');
        }
        if (trendCountCanvas && labelsTrend.length) {
            charts.reportTrendCount = createLine(trendCountCanvas, labelsTrend, trendCount, 'Vendite', '#16a34a');
        }
        if (energyCommissionCanvas && labelsEnergy.length) {
            charts.reportEnergyCommission = createLine(energyCommissionCanvas, labelsEnergy, energyCommission, 'Provvigioni', '#7c3aed');
        }
        if (energyCountCanvas && labelsEnergy.length) {
            charts.reportEnergyCount = createLine(energyCountCanvas, labelsEnergy, energyCount, 'Contratti', '#0ea5e9');
        }
        if (paymentsCanvas && labelsPayments.length) {
            charts.reportPayments = createDoughnut(paymentsCanvas, labelsPayments, paymentNet);
        }
        if (operatorsCanvas && labelsOperators.length) {
            charts.reportOperators = createBar(operatorsCanvas, labelsOperators, operatorNet, 'Incasso netto');
        }

        const sliceLast = (labels, data, count) => {
            if (!count || count <= 0 || labels.length <= count) {
                return { labels, data };
            }
            return { labels: labels.slice(-count), data: data.slice(-count) };
        };

        const updateLine = (chartId, labels, data) => {
            const chart = charts[chartId];
            if (!chart) return;
            chart.data.labels = labels;
            chart.data.datasets[0].data = data;
            chart.update();
        };

        const updateDoughnut = (chartId, labels, data) => {
            const chart = charts[chartId];
            if (!chart) return;
            chart.data.labels = labels;
            chart.data.datasets[0].data = data;
            chart.update();
        };

        const updateBar = (chartId, labels, data) => {
            const chart = charts[chartId];
            if (!chart) return;
            chart.data.labels = labels;
            chart.data.datasets[0].data = data;
            chart.update();
        };

        const trendFilter = document.querySelector('[data-chart-filter="trend-range"]');
        if (trendFilter) {
            trendFilter.addEventListener('change', () => {
                const count = parseInt(trendFilter.value || '0', 10);
                const slicedNet = sliceLast(labelsTrend, trendNet, count);
                const slicedCount = sliceLast(labelsTrend, trendCount, count);
                updateLine('reportTrendNet', slicedNet.labels, slicedNet.data);
                updateLine('reportTrendCount', slicedCount.labels, slicedCount.data);
            });
        }

        const energyFilter = document.querySelector('[data-chart-filter="energy-range"]');
        if (energyFilter) {
            energyFilter.addEventListener('change', () => {
                const count = parseInt(energyFilter.value || '0', 10);
                const slicedCommission = sliceLast(labelsEnergy, energyCommission, count);
                const slicedEnergyCount = sliceLast(labelsEnergy, energyCount, count);
                updateLine('reportEnergyCommission', slicedCommission.labels, slicedCommission.data);
                updateLine('reportEnergyCount', slicedEnergyCount.labels, slicedEnergyCount.data);
            });
        }

        const paymentsFilter = document.querySelector('[data-chart-filter="payments-top"]');
        if (paymentsFilter) {
            paymentsFilter.addEventListener('change', () => {
                const count = parseInt(paymentsFilter.value || '0', 10);
                const paired = labelsPayments.map((label, idx) => ({ label, value: paymentNet[idx] || 0 }));
                paired.sort((a, b) => b.value - a.value);
                const filtered = count > 0 ? paired.slice(0, count) : paired;
                updateDoughnut('reportPayments', filtered.map(item => item.label), filtered.map(item => item.value));
            });
        }

        const operatorsFilter = document.querySelector('[data-chart-filter="operators-top"]');
        if (operatorsFilter) {
            operatorsFilter.addEventListener('change', () => {
                const count = parseInt(operatorsFilter.value || '0', 10);
                const paired = labelsOperators.map((label, idx) => ({ label, value: operatorNet[idx] || 0 }));
                paired.sort((a, b) => b.value - a.value);
                const filtered = count > 0 ? paired.slice(0, count) : paired;
                updateBar('reportOperators', filtered.map(item => item.label), filtered.map(item => item.value));
            });
        }

        document.querySelectorAll('[data-chart-download]').forEach((button) => {
            button.addEventListener('click', () => {
                const chartId = button.getAttribute('data-chart-download');
                const chart = charts[chartId];
                if (!chart) {
                    return;
                }
                const link = document.createElement('a');
                link.href = chart.toBase64Image('image/png', 1);
                link.download = `${chartId}.png`;
                link.click();
            });
        });
    }
})();
</script>
