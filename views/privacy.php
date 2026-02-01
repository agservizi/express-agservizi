<?php
declare(strict_types=1);

/** @var array<string, mixed>|null $policy */
$pageTitle = $pageTitle ?? 'Privacy policy';
$policy = $policy ?? null;
$updatedAt = null;
if (is_array($policy) && !empty($policy['updated_at'])) {
    $updatedAt = date('d/m/Y', strtotime((string) $policy['updated_at']));
}
?>
<section class="page">
    <header class="page__header">
        <h2>Privacy policy</h2>
        <?php if ($policy !== null): ?>
            <p>Versione <?= htmlspecialchars((string) ($policy['version'] ?? '')) ?><?= $updatedAt ? ' · aggiornata al ' . htmlspecialchars($updatedAt) : '' ?></p>
        <?php else: ?>
            <p>Nessuna policy attiva disponibile.</p>
        <?php endif; ?>
    </header>

    <section class="page__section">
        <?php if ($policy === null): ?>
            <p class="muted">Contenuto non disponibile.</p>
        <?php else: ?>
            <article class="card">
                <h3><?= htmlspecialchars((string) ($policy['title'] ?? 'Informativa privacy')) ?></h3>
                <div class="policy-content">
                    <?= nl2br(htmlspecialchars((string) ($policy['content'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
                </div>
            </article>
        <?php endif; ?>
    </section>
</section>
