<?php
declare(strict_types=1);

namespace App\Services;

final class PdaSettingsService
{
    private const SETTINGS_PATH = __DIR__ . '/../../storage/config/pda_settings.json';
    private const DEFAULT_OCR = [
        'enabled' => true,
        'min_chars' => 200,
        'lang' => 'ita',
    ];

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $stored = $this->readSettings();
        $ocr = $stored['ocr'] ?? [];
        $templates = $stored['templates'] ?? null;

        $ocrSettings = [
            'enabled' => isset($ocr['enabled']) ? (bool) $ocr['enabled'] : self::DEFAULT_OCR['enabled'],
            'min_chars' => isset($ocr['min_chars']) ? (int) $ocr['min_chars'] : self::DEFAULT_OCR['min_chars'],
            'lang' => isset($ocr['lang']) ? (string) $ocr['lang'] : self::DEFAULT_OCR['lang'],
        ];

        $defaultTemplates = $this->loadDefaultTemplates();
        if (!is_array($templates) || $templates === []) {
            $templates = $defaultTemplates;
        }

        return [
            'ocr' => $ocrSettings,
            'templates' => $templates,
            'templates_json' => json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool,message:string,errors?:array<int,string>}
     */
    public function saveOcrSettings(array $input): array
    {
        $enabled = isset($input['pda_ocr_enabled']) ? (int) $input['pda_ocr_enabled'] === 1 : false;
        $minChars = isset($input['pda_ocr_min_chars']) ? (int) $input['pda_ocr_min_chars'] : self::DEFAULT_OCR['min_chars'];
        $lang = isset($input['pda_ocr_lang']) ? trim((string) $input['pda_ocr_lang']) : self::DEFAULT_OCR['lang'];

        $errors = [];
        if ($minChars <= 0) {
            $errors[] = 'La soglia OCR deve essere maggiore di zero.';
        }
        if ($lang === '') {
            $errors[] = 'Specifica una lingua OCR valida (es. ita, eng).';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile salvare le impostazioni OCR.',
                'errors' => $errors,
            ];
        }

        $settings = $this->readSettings();
        $settings['ocr'] = [
            'enabled' => $enabled,
            'min_chars' => $minChars,
            'lang' => $lang,
        ];

        $this->writeSettings($settings);

        return [
            'success' => true,
            'message' => 'Impostazioni OCR salvate correttamente.',
        ];
    }

    /**
     * @return array{success:bool,message:string,errors?:array<int,string>}
     */
    public function saveTemplatesJson(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'JSON non valido.',
                'errors' => ['Controlla la sintassi del JSON dei template.'],
            ];
        }

        $errors = $this->validateTemplates($decoded);
        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Template non validi.',
                'errors' => $errors,
            ];
        }

        $settings = $this->readSettings();
        $settings['templates'] = $decoded;
        $this->writeSettings($settings);

        return [
            'success' => true,
            'message' => 'Template PDA aggiornati con successo.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readSettings(): array
    {
        if (!is_file(self::SETTINGS_PATH)) {
            return [];
        }

        $contents = file_get_contents(self::SETTINGS_PATH);
        if ($contents === false || trim($contents) === '') {
            return [];
        }

        $decoded = json_decode($contents, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function writeSettings(array $settings): void
    {
        $dir = dirname(self::SETTINGS_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $encoded = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new \RuntimeException('Impossibile salvare le impostazioni PDA.');
        }

        file_put_contents(self::SETTINGS_PATH, $encoded);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDefaultTemplates(): array
    {
        $path = __DIR__ . '/../../config/pda_templates.php';
        if (!is_file($path)) {
            return [];
        }
        $templates = require $path;
        return is_array($templates) ? $templates : [];
    }

    /**
     * @param array<string, mixed> $templates
     * @return array<int, string>
     */
    private function validateTemplates(array $templates): array
    {
        $errors = [];
        foreach ($templates as $providerKey => $templateList) {
            if (!is_array($templateList)) {
                $errors[] = 'Template provider "' . $providerKey . '" non valido.';
                continue;
            }
            foreach ($templateList as $index => $template) {
                if (!is_array($template)) {
                    $errors[] = 'Template ' . $providerKey . '[' . $index . '] non valido.';
                    continue;
                }
                if (empty($template['key'])) {
                    $errors[] = 'Template ' . $providerKey . '[' . $index . '] senza chiave.';
                }
                if (!isset($template['match']) || !is_array($template['match'])) {
                    $errors[] = 'Template ' . $providerKey . '[' . $index . '] senza regole match.';
                }
                if (!isset($template['patterns']) || !is_array($template['patterns'])) {
                    $errors[] = 'Template ' . $providerKey . '[' . $index . '] senza patterns.';
                }
            }
        }
        return $errors;
    }
}
