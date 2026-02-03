<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\TenantContext;

final class ReceiptSettingsService
{
    private ?string $customPath;
    private string $baseDir;
    private string $legacyPath;

    /**
     * @var array<string, mixed>
     */
    private array $defaults;

    public function __construct(?string $path = null)
    {
        $this->customPath = $path !== null ? $path : null;
        $this->baseDir = dirname(__DIR__, 2) . '/storage/config/tenants';
        $this->legacyPath = dirname(__DIR__, 2) . '/storage/config/receipt_settings.json';
        $this->defaults = [
            'header_lines' => [
                'Negozio Esempio S.r.l.',
            ],
            'document_title' => 'DOCUMENTO GESTIONALE',
            'document_number_template' => '{{document_title}} #{{sale_id}}',
            'thanks_text' => 'Grazie per il tuo acquisto!',
            'footer_text' => 'Hai bisogno di stampare di nuovo? Puoi sempre recuperare questo DOCUMENTO GESTIONALE dalla sezione vendite.',
            'labels' => [
                'date' => 'Data',
                'operator' => 'Operatore',
                'customer' => 'Cliente',
                'vat' => 'IVA',
                'vat_included' => 'IVA compresa',
                'vat_codes' => 'Codici IVA applicati',
                'discount' => 'Sconto',
                'total' => 'Totale',
                'total_original' => 'Totale originario',
                'payment' => 'Pagamento',
                'refund_amount' => 'Importo reso',
                'cancelled_at' => 'Annullato il',
                'cancellation_reason' => 'Motivo annullo',
                'refunded_at' => 'Reso registrato il',
                'refund_note' => 'Note reso',
            ],
            'status_labels' => [
                'cancelled' => 'ANNULLATO',
                'refunded' => 'RESO',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $settings = $this->mergeDefaults($this->readSettings());
        return $this->normalizeDemoHeader($settings);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool,message:string,errors?:array<int,string>}
     */
    public function saveSettings(array $input): array
    {
        $errors = [];

        $headerRaw = (string) ($input['receipt_header_lines'] ?? '');
        $headerLines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $headerRaw) ?: [])));
        if ($headerLines === []) {
            $errors[] = 'Inserisci almeno una riga di intestazione.';
        }

        $documentTitle = trim((string) ($input['receipt_document_title'] ?? ''));
        if ($documentTitle === '') {
            $documentTitle = (string) ($this->defaults['document_title'] ?? '');
        }

        $documentNumberTemplate = trim((string) ($input['receipt_document_number_template'] ?? ''));
        if ($documentNumberTemplate === '') {
            $documentNumberTemplate = (string) ($this->defaults['document_number_template'] ?? '');
        }

        $thanksText = trim((string) ($input['receipt_thanks_text'] ?? ''));
        if ($thanksText === '') {
            $thanksText = (string) ($this->defaults['thanks_text'] ?? '');
        }

        $footerText = trim((string) ($input['receipt_footer_text'] ?? ''));
        if ($footerText === '') {
            $footerText = (string) ($this->defaults['footer_text'] ?? '');
        }

        $labels = $this->sanitizeLabels($input);
        $statusLabels = $this->sanitizeStatusLabels($input);

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Configurazione scontrino non salvata.',
                'errors' => $errors,
            ];
        }

        $settings = [
            'header_lines' => $headerLines,
            'document_title' => $documentTitle,
            'document_number_template' => $documentNumberTemplate,
            'thanks_text' => $thanksText,
            'footer_text' => $footerText,
            'labels' => $labels,
            'status_labels' => $statusLabels,
            'configured_at' => date('Y-m-d H:i:s'),
        ];

        $path = $this->resolvePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                return [
                    'success' => false,
                    'message' => 'Impossibile salvare la configurazione dello scontrino.',
                    'errors' => ['La cartella di configurazione non è accessibile.'],
                ];
            }
        }

        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return [
                'success' => false,
                'message' => 'Configurazione scontrino non salvata.',
                'errors' => ['Formato JSON non valido.'],
            ];
        }

        if (file_put_contents($path, $json) === false) {
            return [
                'success' => false,
                'message' => 'Configurazione scontrino non salvata.',
                'errors' => ['Impossibile scrivere il file di configurazione.'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Configurazione scontrino aggiornata.',
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function mergeDefaults(array $data): array
    {
        $merged = $this->defaults;
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = array_replace_recursive((array) $merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        if (!isset($merged['header_lines']) || !is_array($merged['header_lines']) || $merged['header_lines'] === []) {
            $merged['header_lines'] = $this->defaults['header_lines'];
        }

        if (!isset($merged['labels']) || !is_array($merged['labels'])) {
            $merged['labels'] = $this->defaults['labels'];
        }

        if (!isset($merged['status_labels']) || !is_array($merged['status_labels'])) {
            $merged['status_labels'] = $this->defaults['status_labels'];
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function normalizeDemoHeader(array $settings): array
    {
        $tenantId = TenantContext::id();
        if ($tenantId !== 1) {
            return $settings;
        }

        $header = $settings['header_lines'] ?? [];
        if (!is_array($header)) {
            $header = [];
        }
        $header = array_values(array_filter(array_map('trim', $header)));

        $hasLegacyHeader = in_array('TRT Service', $header, true);
        if ($header === [] || $hasLegacyHeader) {
            $settings['header_lines'] = [
                'Telefonia Plinio',
                'Via Roma 10, Milano',
                'P.IVA IT12345678901',
            ];
        }

        return $settings;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function sanitizeLabels(array $input): array
    {
        $defaults = $this->defaults['labels'];
        $labels = [];
        foreach ($defaults as $key => $defaultValue) {
            $field = 'receipt_label_' . $key;
            $value = trim((string) ($input[$field] ?? ''));
            $labels[$key] = $value !== '' ? $value : (string) $defaultValue;
        }

        return $labels;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function sanitizeStatusLabels(array $input): array
    {
        $defaults = $this->defaults['status_labels'];
        $labels = [];
        foreach ($defaults as $key => $defaultValue) {
            $field = 'receipt_status_' . $key;
            $value = trim((string) ($input[$field] ?? ''));
            $labels[$key] = $value !== '' ? $value : (string) $defaultValue;
        }

        return $labels;
    }

    public function isConfigured(): bool
    {
        $path = $this->resolvePath();
        if (!is_file($path)) {
            if ($this->customPath === null && $this->shouldFallbackToLegacy()) {
                $path = $this->legacyPath;
            } else {
                return false;
            }
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return false;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || $decoded === []) {
            return false;
        }

        if (!empty($decoded['configured_at'])) {
            return true;
        }

        return isset($decoded['header_lines'])
            || isset($decoded['document_title'])
            || isset($decoded['document_number_template'])
            || isset($decoded['labels'])
            || isset($decoded['status_labels']);
    }

    public function initializeForTenant(int $tenantId, ?string $tenantName = null): void
    {
        if ($tenantId <= 0) {
            return;
        }

        $path = $this->resolvePath($tenantId);
        if (is_file($path)) {
            return;
        }

        $settings = $this->buildDefaultSettings($tenantName);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }

        file_put_contents($path, $json);
    }

    /**
     * @return array<string, mixed>
     */
    private function readSettings(?int $tenantId = null): array
    {
        $path = $this->resolvePath($tenantId);
        $usedLegacy = false;
        if (!is_file($path)) {
            if ($this->customPath === null && $this->shouldFallbackToLegacy($tenantId)) {
                $path = $this->legacyPath;
                $usedLegacy = true;
            } else {
                return [];
            }
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        if ($usedLegacy && $this->customPath === null) {
            $resolvedTenantId = $tenantId ?? TenantContext::id();
            if ($resolvedTenantId === 1) {
                $tenantPath = $this->resolvePath($resolvedTenantId);
                if (!is_file($tenantPath)) {
                    $dir = dirname($tenantPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                    $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if ($json !== false) {
                        file_put_contents($tenantPath, $json);
                    }
                }
            }
        }

        return $decoded;
    }

    private function resolvePath(?int $tenantId = null): string
    {
        if ($this->customPath !== null) {
            return $this->customPath;
        }

        $resolvedTenantId = $tenantId ?? TenantContext::id();
        return $this->baseDir . '/tenant_' . $resolvedTenantId . '/receipt_settings.json';
    }

    private function shouldFallbackToLegacy(?int $tenantId = null): bool
    {
        $resolvedTenantId = $tenantId ?? TenantContext::id();
        return $resolvedTenantId === 1 && is_file($this->legacyPath);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDefaultSettings(?string $tenantName = null): array
    {
        $header = $tenantName !== null && trim($tenantName) !== ''
            ? [trim($tenantName)]
            : (array) ($this->defaults['header_lines'] ?? []);

        return [
            'header_lines' => $header,
            'document_title' => (string) ($this->defaults['document_title'] ?? 'DOCUMENTO GESTIONALE'),
            'document_number_template' => (string) ($this->defaults['document_number_template'] ?? '{{document_title}} #{{sale_id}}'),
            'thanks_text' => (string) ($this->defaults['thanks_text'] ?? 'Grazie per il tuo acquisto!'),
            'footer_text' => (string) ($this->defaults['footer_text'] ?? ''),
            'labels' => (array) ($this->defaults['labels'] ?? []),
            'status_labels' => (array) ($this->defaults['status_labels'] ?? []),
            'configured_at' => date('Y-m-d H:i:s'),
        ];
    }
}
