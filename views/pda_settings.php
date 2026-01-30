<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $settings
 * @var array{success:bool,message:string,errors?:array<int,string>}|null $feedback
 */
$pageTitle = 'Configurazione PDA';
$settings = $settings ?? [];
$feedback = $feedback ?? null;
$ocr = $settings['ocr'] ?? ['enabled' => true, 'min_chars' => 200, 'lang' => 'ita'];
$templatesJson = $settings['templates_json'] ?? '{}';
?>
<section class="page">
    <header class="page__header">
        <h2>Configurazione PDA</h2>
        <p class="muted">Gestisci soglie OCR e template di parsing per provider e layout.</p>
    </header>

    <?php if ($feedback !== null): ?>
        <div class="alert <?= ($feedback['success'] ?? false) ? 'alert--success' : 'alert--error' ?>">
            <p><?= htmlspecialchars((string) ($feedback['message'] ?? 'Operazione completata.')) ?></p>
            <?php foreach ($feedback['errors'] ?? [] as $error): ?>
                <p><?= htmlspecialchars((string) $error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="page__section">
        <h3>OCR automatico</h3>
        <form method="post" class="form">
            <input type="hidden" name="action" value="save_pda_ocr">
            <div class="form__grid">
                <div class="form__group">
                    <label for="pda_ocr_enabled">OCR abilitato</label>
                    <select id="pda_ocr_enabled" name="pda_ocr_enabled">
                        <option value="1" <?= !empty($ocr['enabled']) ? 'selected' : '' ?>>Attivo</option>
                        <option value="0" <?= empty($ocr['enabled']) ? 'selected' : '' ?>>Disattivo</option>
                    </select>
                </div>
                <div class="form__group">
                    <label for="pda_ocr_min_chars">Soglia minima caratteri</label>
                    <input type="number" id="pda_ocr_min_chars" name="pda_ocr_min_chars" min="50" value="<?= (int) ($ocr['min_chars'] ?? 200) ?>">
                    <small class="muted">Se il testo estratto è inferiore alla soglia, viene attivato OCR.</small>
                </div>
                <div class="form__group">
                    <label for="pda_ocr_lang">Lingua OCR</label>
                    <input type="text" id="pda_ocr_lang" name="pda_ocr_lang" value="<?= htmlspecialchars((string) ($ocr['lang'] ?? 'ita')) ?>">
                    <small class="muted">Codice lingua Tesseract (es. ita, eng).</small>
                </div>
            </div>
            <button type="submit" class="btn btn--primary">Salva OCR</button>
        </form>
    </section>

    <section class="page__section">
        <h3>Template PDA</h3>
        <p class="muted">Aggiorna i template in formato JSON. Verranno usati per il matching per provider.</p>
        <form method="post" class="form">
            <input type="hidden" name="action" value="save_pda_templates">
            <div class="form__group">
                <label for="pda_templates_json">JSON Template</label>
                <textarea id="pda_templates_json" name="pda_templates_json" rows="16" class="form__textarea"><?= htmlspecialchars($templatesJson) ?></textarea>
            </div>
            <button type="submit" class="btn btn--primary">Salva template</button>
        </form>
    </section>
</section>
