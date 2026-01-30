<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $imports
 * @var array{page:int, per_page:int, total:int, total_pages:int, has_prev:bool, has_next:bool} $pagination
 * @var array<string, mixed>|null $detail
 * @var array{success:bool,message:string,errors?:array<int,string>,warnings?:array<int,string>}|null $feedback
 */
$pageTitle = 'Debug Import PDA';
$imports = $imports ?? [];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 10, 'total' => 0, 'total_pages' => 1, 'has_prev' => false, 'has_next' => false];
$detail = $detail ?? null;
$feedback = $feedback ?? null;
$buildPageUrl = static function (int $pageNo): string {
    return 'index.php?' . http_build_query(['page' => 'pda_imports', 'page_no' => max(1, $pageNo)]);
};
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
        <h2>Debug Import PDA</h2>
        <p class="muted">Strumenti amministratore per analisi, log e rielaborazione delle PDA importate.</p>
    </header>

    <?php if ($feedback !== null): ?>
        <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
            <p><?= htmlspecialchars((string) ($feedback['message'] ?? 'Operazione completata.')) ?></p>
            <?php foreach ($feedback['errors'] ?? [] as $error): ?>
                <p><?= htmlspecialchars((string) $error) ?></p>
            <?php endforeach; ?>
            <?php if (!empty($feedback['warnings'])): ?>
                <ul class="muted">
                    <?php foreach ($feedback['warnings'] as $warning): ?>
                        <li><?= htmlspecialchars((string) $warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <section class="page__section">
        <div class="table-wrapper">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Provider</th>
                        <th>File</th>
                        <th>Template</th>
                        <th>OCR</th>
                        <th>Data contratto</th>
                        <th>Stato</th>
                        <th>Creato</th>
                        <th class="table__col--actions">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($imports === []): ?>
                        <tr><td colspan="9">Nessun import PDA registrato.</td></tr>
                    <?php else: ?>
                        <?php foreach ($imports as $row): ?>
                            <tr>
                                <td>#<?= (int) $row['id'] ?></td>
                                <td><?= htmlspecialchars((string) ($row['provider_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['source_filename'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['template_key'] ?? '-')) ?></td>
                                <td><?= ((int) ($row['ocr_used'] ?? 0) === 1) ? 'Sì' : 'No' ?></td>
                                <td><?= htmlspecialchars($formatDate($row['contract_date'] ?? null, 'd/m/Y')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['status'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($formatDate($row['created_at'] ?? null)) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn btn--secondary btn--small" href="index.php?page=pda_imports&amp;detail=<?= (int) $row['id'] ?>">Dettagli</a>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="action" value="reprocess_pda">
                                            <input type="hidden" name="pda_import_id" value="<?= (int) $row['id'] ?>">
                                            <button type="submit" class="btn btn--secondary btn--small">Rielabora</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
            <?php $current = (int) ($pagination['page'] ?? 1); ?>
            <nav class="pagination">
                <a class="pagination__link <?= ($pagination['has_prev'] ?? false) ? '' : 'is-disabled' ?>" href="<?= ($pagination['has_prev'] ?? false) ? $buildPageUrl(1) : '#' ?>">«</a>
                <a class="pagination__link <?= ($pagination['has_prev'] ?? false) ? '' : 'is-disabled' ?>" href="<?= ($pagination['has_prev'] ?? false) ? $buildPageUrl($current - 1) : '#' ?>">‹</a>
                <span class="pagination__info">Pagina <?= $current ?> di <?= (int) ($pagination['total_pages'] ?? 1) ?></span>
                <a class="pagination__link <?= ($pagination['has_next'] ?? false) ? '' : 'is-disabled' ?>" href="<?= ($pagination['has_next'] ?? false) ? $buildPageUrl($current + 1) : '#' ?>">›</a>
                <a class="pagination__link <?= ($pagination['has_next'] ?? false) ? '' : 'is-disabled' ?>" href="<?= ($pagination['has_next'] ?? false) ? $buildPageUrl((int) ($pagination['total_pages'] ?? 1)) : '#' ?>">»</a>
            </nav>
        <?php endif; ?>
    </section>

    <?php if ($detail !== null): ?>
        <section class="page__section">
            <h3>Dettaglio import #<?= (int) ($detail['id'] ?? 0) ?></h3>
            <div class="dashboard-panel">
                <p><strong>Provider:</strong> <?= htmlspecialchars((string) ($detail['provider_name'] ?? '')) ?></p>
                <p><strong>File:</strong> <?= htmlspecialchars((string) ($detail['source_filename'] ?? '')) ?></p>
                <p><strong>Template:</strong> <?= htmlspecialchars((string) ($detail['template_key'] ?? '-')) ?></p>
                <p><strong>Stato:</strong> <?= htmlspecialchars((string) ($detail['status'] ?? '')) ?></p>
                <p><strong>OCR:</strong> <?= ((int) ($detail['ocr_used'] ?? 0) === 1) ? 'Sì' : 'No' ?></p>
                <p><strong>Errori:</strong> <?= htmlspecialchars((string) ($detail['errors'] ?? '')) ?></p>
                <p><strong>Warning:</strong> <?= htmlspecialchars((string) ($detail['warnings'] ?? '')) ?></p>
                <details>
                    <summary>Testo estratto</summary>
                    <pre><?= htmlspecialchars((string) ($detail['raw_text'] ?? '')) ?></pre>
                </details>
                <details>
                    <summary>Testo OCR</summary>
                    <pre><?= htmlspecialchars((string) ($detail['ocr_text'] ?? '')) ?></pre>
                </details>
            </div>
        </section>
    <?php endif; ?>
</section>
