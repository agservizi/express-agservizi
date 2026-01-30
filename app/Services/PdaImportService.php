<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class PdaImportService
{
    private const PDA_UPLOAD_DIR = __DIR__ . '/../../storage/uploads/pda';
    private const DEFAULT_OCR_MIN_CHARS = 200;

    /**
     * @var array<string, array<string, array<int, string>>>
     */
    private const FIELD_ALIASES = [
        'iccid' => ['ICCID', 'Seriale SIM', 'Serial Number', 'Codice SIM', 'Sim'],
        'msisdn' => ['MSISDN', 'Numero', 'Linea', 'Numero linea', 'Telefono linea'],
        'plan' => ['Offerta', 'Piano', 'Prodotto', 'Profilo', 'Canvass'],
        'price' => ['Prezzo', 'Canone', 'Totale', 'Costo', 'Importo'],
        'customer_fullname' => ['Intestatario', 'Cliente', 'Nominativo', 'Titolare'],
        'customer_tax_code' => ['Codice Fiscale', 'CF', 'Cod.Fisc.', 'Partita IVA', 'P.IVA'],
        'customer_email' => ['Email', 'E-mail', 'Mail'],
        'customer_phone' => ['Telefono', 'Cellulare', 'Contatto', 'Recapito'],
        'customer_address' => ['Indirizzo', 'Address', 'Residenza'],
    ];

    private array $templates = [];
    private array $pdaConfig = [];

    public function __construct(
        private PDO $pdo,
        private CustomerService $customerService
    ) {
        $templatesPath = __DIR__ . '/../../config/pda_templates.php';
        if (is_file($templatesPath)) {
            $loaded = require $templatesPath;
            if (is_array($loaded)) {
                $this->templates = $loaded;
            }
        }

        $this->pdaConfig = is_array($GLOBALS['config']['pda'] ?? null) ? $GLOBALS['config']['pda'] : [];
        $this->applyStoredOverrides();
    }

    private function applyStoredOverrides(): void
    {
        $settingsPath = __DIR__ . '/../../storage/config/pda_settings.json';
        if (!is_file($settingsPath)) {
            return;
        }
        $contents = file_get_contents($settingsPath);
        if ($contents === false || trim($contents) === '') {
            return;
        }
        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            return;
        }

        if (isset($decoded['templates']) && is_array($decoded['templates']) && $decoded['templates'] !== []) {
            $this->templates = $decoded['templates'];
        }

        if (isset($decoded['ocr']) && is_array($decoded['ocr'])) {
            $this->pdaConfig = array_merge($this->pdaConfig, $decoded['ocr']);
        }
    }

    /**
     * @param array<string, mixed>|null $file
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $currentUser
     * @return array{success:bool,message:string,warnings?:array<int,string>,errors?:array<int,string>,prefill?:array<string,mixed>}
     */
    public function processUpload(?array $file, array $input, ?array $currentUser = null): array
    {
        if ($file === null) {
            return [
                'success' => false,
                'message' => 'Nessun file ricevuto. Seleziona una PDA prima di procedere.',
                'errors' => ['File PDA mancante.'],
            ];
        }

        $providerId = isset($input['pda_provider_id']) ? (int) $input['pda_provider_id'] : 0;
        if ($providerId <= 0) {
            return [
                'success' => false,
                'message' => 'Seleziona il gestore prima di importare la PDA.',
                'errors' => ['Gestore non valido.'],
            ];
        }

        $provider = $this->fetchProvider($providerId);
        if ($provider === null) {
            return [
                'success' => false,
                'message' => 'Gestore non trovato. Aggiorna la pagina e riprova.',
                'errors' => ['Provider non presente a sistema.'],
            ];
        }

        if (strcasecmp((string) $provider['name'], 'iliad') === 0) {
            return [
                'success' => false,
                'message' => 'Il gestore selezionato non supporta l\'import automatico delle PDA.',
                'errors' => ['Iliad non supportato.'],
            ];
        }

        if (!isset($file['error']) || !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $message = $this->describeUploadError($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return [
                'success' => false,
                'message' => 'Caricamento PDA non riuscito: ' . $message,
                'errors' => [$message],
            ];
        }

        $originalName = isset($file['name']) ? (string) $file['name'] : 'pda_upload';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'bin';
        }

        $tmpPath = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return [
                'success' => false,
                'message' => 'Impossibile accedere al file caricato. Riprova.',
                'errors' => ['File temporaneo mancante o non valido.'],
            ];
        }

        $storagePath = $this->storeUploadedFile($tmpPath, $extension, (string) $provider['name']);

        $fileHash = hash_file('sha256', $storagePath) ?: null;
        $duplicate = $fileHash !== null ? $this->findDuplicateImport($fileHash, (int) $provider['id']) : null;
        if ($duplicate !== null) {
            $this->recordImport([
                'status' => 'Duplicate',
                'provider_id' => (int) $provider['id'],
                'provider_name' => (string) $provider['name'],
                'source_filename' => $originalName,
                'stored_path' => $storagePath,
                'file_hash' => $fileHash,
                'error_message' => 'PDA già importata (#' . $duplicate . ').',
                'user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
            ]);

            return [
                'success' => false,
                'message' => 'Questa PDA risulta già importata in precedenza.',
                'errors' => ['Import già presente (riferimento #' . $duplicate . ').'],
            ];
        }

        $extraction = $this->extractTextFromFile($storagePath, $extension);
        if (!($extraction['success'] ?? false)) {
            $this->recordImport([
                'status' => 'Failed',
                'provider_id' => (int) $provider['id'],
                'provider_name' => (string) $provider['name'],
                'source_filename' => $originalName,
                'stored_path' => $storagePath,
                'raw_text' => $extraction['raw_text'] ?? null,
                'ocr_text' => $extraction['ocr_text'] ?? null,
                'ocr_used' => $extraction['ocr_used'] ?? 0,
                'file_hash' => $fileHash,
                'error_message' => $extraction['error'] ?? 'Estrazione testo non riuscita.',
                'user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
            ]);

            return [
                'success' => false,
                'message' => 'Impossibile leggere il contenuto della PDA: ' . ($extraction['error'] ?? 'estrazione testo non riuscita'),
                'errors' => [$extraction['error'] ?? 'Estrazione testo non riuscita.'],
            ];
        }

        $text = (string) $extraction['text'];
        $parsed = $this->parsePayload($text, (string) $provider['name']);
        if (!($parsed['success'] ?? false)) {
            $this->recordImport([
                'status' => 'Failed',
                'provider_id' => (int) $provider['id'],
                'provider_name' => (string) $provider['name'],
                'source_filename' => $originalName,
                'stored_path' => $storagePath,
                'raw_text' => $text,
                'ocr_text' => $extraction['ocr_text'] ?? null,
                'ocr_used' => $extraction['ocr_used'] ?? 0,
                'file_hash' => $fileHash,
                'template_key' => $parsed['template_key'] ?? null,
                'error_message' => $parsed['error'] ?? 'Parsing PDA non riuscito.',
                'user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
            ]);

            return [
                'success' => false,
                'message' => 'PDA non riconosciuta: ' . ($parsed['error'] ?? 'impossibile estrarre i dati richiesti'),
                'errors' => [$parsed['error'] ?? 'Dati insufficienti nella PDA.'],
            ];
        }

        $warnings = $parsed['warnings'] ?? [];
        if (!empty($extraction['ocr_used'])) {
            $warnings[] = 'OCR utilizzato per estrarre il testo: verifica i campi sensibili.';
        }
        $customerProfile = $parsed['customer'] ?? [];
        $items = $parsed['items'] ?? [];

        $customerMatch = $this->matchCustomer($customerProfile);
        $resolvedItems = $this->resolveItems($items, (int) $provider['id']);
        $warnings = array_merge($warnings, $resolvedItems['warnings']);

        $validation = $this->validatePreview($customerProfile, $resolvedItems['items']);
        $coherence = $this->checkCoherence($customerProfile, $resolvedItems['items'], (string) $provider['name'], (string) ($parsed['detected_provider'] ?? ''));
        $warnings = array_merge($warnings, $validation['warnings'], $coherence['warnings']);
        $errors = array_merge($validation['errors'], $coherence['errors']);

        $contractDate = $parsed['contract_date'] ?? null;
        if ($contractDate !== null) {
            $duplicateKey = $this->findDuplicateByCustomer($customerMatch['id'] ?? null, (int) $provider['id'], $contractDate);
            if ($duplicateKey !== null) {
                $errors[] = 'PDA duplicata: stessa data e cliente (riferimento #' . $duplicateKey . ').';
            }
        }

        $preview = [
            'provider' => [
                'id' => (int) $provider['id'],
                'name' => (string) $provider['name'],
            ],
            'detected_provider' => $parsed['detected_provider'] ?? null,
            'template_key' => $parsed['template_key'] ?? null,
            'contract_date' => $contractDate,
            'customer' => [
                'id' => $customerMatch['id'] ?? null,
                'fullname' => $customerProfile['fullname'] ?? null,
                'email' => $customerProfile['email'] ?? null,
                'phone' => $customerProfile['phone'] ?? null,
                'tax_code' => $customerProfile['tax_code'] ?? null,
                'note' => $customerProfile['note'] ?? null,
            ],
            'items' => $resolvedItems['items'],
            'field_status' => $validation['field_status'],
            'item_status' => $validation['item_status'],
            'warnings' => $warnings,
            'errors' => $errors,
        ];

        $recordId = $this->recordImport([
            'status' => $errors !== [] ? 'Preview' : 'Preview',
            'provider_id' => (int) $provider['id'],
            'provider_name' => (string) $provider['name'],
            'source_filename' => $originalName,
            'stored_path' => $storagePath,
            'raw_text' => $text,
            'ocr_text' => $extraction['ocr_text'] ?? null,
            'ocr_used' => $extraction['ocr_used'] ?? 0,
            'file_hash' => $fileHash,
            'template_key' => $parsed['template_key'] ?? null,
            'contract_date' => $contractDate,
            'customer_id' => $customerMatch['id'] ?? null,
            'customer_payload' => $customerProfile,
            'sale_payload' => $resolvedItems['items'],
            'preview_payload' => $preview,
            'warnings' => $warnings,
            'errors' => $errors,
            'user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
            'notes' => $parsed['summary'] ?? null,
        ]);

        $messageParts = ['Analisi PDA completata.'];
        if ($errors !== []) {
            $messageParts[] = 'Sono presenti errori da correggere prima di confermare.';
        }
        $messageParts[] = 'Riferimento import: #' . $recordId;

        return [
            'success' => true,
            'message' => implode(' ', $messageParts),
            'warnings' => $warnings,
            'errors' => $errors,
            'preview' => $preview,
            'import_id' => $recordId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchProvider(int $providerId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM providers WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $providerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    private function describeUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File troppo grande: riduci le dimensioni o comprimi la PDA.',
            UPLOAD_ERR_PARTIAL => 'Caricamento interrotto: riprova.',
            UPLOAD_ERR_NO_FILE => 'Nessun file selezionato.',
            UPLOAD_ERR_NO_TMP_DIR => 'Directory temporanea mancante sul server.',
            UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file sul disco.',
            UPLOAD_ERR_EXTENSION => 'Caricamento bloccato da un\'estensione PHP.',
            default => 'Errore sconosciuto durante il caricamento (codice ' . $code . ').',
        };
    }

    private function storeUploadedFile(string $tmpPath, string $extension, string $providerName): string
    {
        if (!is_dir(self::PDA_UPLOAD_DIR)) {
            if (!mkdir(self::PDA_UPLOAD_DIR, 0775, true) && !is_dir(self::PDA_UPLOAD_DIR)) {
                throw new RuntimeException('Impossibile creare la cartella di upload PDA.');
            }
        }

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($providerName));
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'gestore';
        }

        $filename = date('Ymd_His') . '_' . $slug . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = self::PDA_UPLOAD_DIR . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            throw new RuntimeException('Impossibile spostare il file caricato.');
        }

        return $destination;
    }

    /**
     * @return array{success:bool,text?:string,error?:string,raw_text?:string,ocr_text?:string,ocr_used?:int}
     */
    private function extractTextFromFile(string $path, string $extension): array
    {
        $extension = strtolower($extension);
        if (in_array($extension, ['txt', 'csv', 'json', 'xml', 'tsv'], true)) {
            $content = file_get_contents($path);
            if ($content === false) {
                return ['success' => false, 'error' => 'Impossibile leggere il file caricato.'];
            }

            return ['success' => true, 'text' => $this->sanitizeText($content), 'ocr_used' => 0];
        }

        if ($extension === 'pdf') {
            $text = $this->convertPdfToText($path);
            $normalized = $text !== null ? $this->sanitizeText($text) : '';
            if ($normalized !== '' && mb_strlen($normalized) >= $this->getOcrMinChars()) {
                return ['success' => true, 'text' => $normalized, 'raw_text' => $text, 'ocr_used' => 0];
            }

            $ocrResult = $this->runOcrIfNeeded($path, $normalized);
            if ($ocrResult['success'] ?? false) {
                return [
                    'success' => true,
                    'text' => $ocrResult['text'],
                    'raw_text' => $text,
                    'ocr_text' => $ocrResult['ocr_text'] ?? null,
                    'ocr_used' => 1,
                ];
            }

            return [
                'success' => false,
                'error' => $ocrResult['error'] ?? 'Impossibile estrarre testo dal PDF. Installa smalot/pdfparser o abilita OCR.',
                'raw_text' => $text,
                'ocr_text' => $ocrResult['ocr_text'] ?? null,
                'ocr_used' => 0,
            ];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return ['success' => false, 'error' => 'Formato file non supportato.'];
        }

        return ['success' => true, 'text' => $this->sanitizeText($content), 'ocr_used' => 0];
    }

    private function convertPdfToText(string $path): ?string
    {
        if (!class_exists('Smalot\\PdfParser\\Parser')) {
            return null;
        }

        try {
            $parserClass = 'Smalot\\PdfParser\\Parser';
            $parser = new $parserClass();
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();
            if (!is_string($text)) {
                return null;
            }

            return $text !== '' ? $text : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function getOcrMinChars(): int
    {
        $min = (int) ($this->pdaConfig['ocr_min_chars'] ?? self::DEFAULT_OCR_MIN_CHARS);
        return $min > 0 ? $min : self::DEFAULT_OCR_MIN_CHARS;
    }

    /**
     * @return array{success:bool,text?:string,error?:string,ocr_text?:string}
     */
    private function runOcrIfNeeded(string $pdfPath, string $currentText): array
    {
        if (!($this->pdaConfig['ocr_enabled'] ?? false)) {
            return ['success' => false, 'error' => 'OCR disabilitato.'];
        }

        if ($currentText !== '' && mb_strlen($currentText) >= $this->getOcrMinChars()) {
            return ['success' => true, 'text' => $currentText, 'ocr_text' => $currentText];
        }

        $ocrText = $this->ocrPdfToText($pdfPath);
        if ($ocrText === null || trim($ocrText) === '') {
            return ['success' => false, 'error' => 'OCR non disponibile o fallito.'];
        }

        $normalized = $this->sanitizeText($ocrText);
        if ($normalized === '') {
            return ['success' => false, 'error' => 'OCR non ha prodotto testo leggibile.'];
        }

        return ['success' => true, 'text' => $normalized, 'ocr_text' => $ocrText];
    }

    private function ocrPdfToText(string $pdfPath): ?string
    {
        if (!$this->isCommandAvailable('tesseract') || !$this->isCommandAvailable('pdftoppm')) {
            return null;
        }

        $tmpDir = sys_get_temp_dir() . '/pda_ocr_' . bin2hex(random_bytes(4));
        if (!mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            return null;
        }

        $prefix = $tmpDir . '/page';
        $pdfEscaped = escapeshellarg($pdfPath);
        $prefixEscaped = escapeshellarg($prefix);
        $cmd = 'pdftoppm -r 200 -png ' . $pdfEscaped . ' ' . $prefixEscaped . ' 2>/dev/null';
        @shell_exec($cmd);

        $images = glob($tmpDir . '/page-*.png');
        if ($images === false || $images === []) {
            $this->cleanupTempDir($tmpDir);
            return null;
        }

        $lang = $this->pdaConfig['ocr_lang'] ?? 'ita';
        $texts = [];
        foreach ($images as $image) {
            $outBase = $image . '_ocr';
            $imgEscaped = escapeshellarg($image);
            $outEscaped = escapeshellarg($outBase);
            $langEscaped = escapeshellarg((string) $lang);
            $cmd = 'tesseract ' . $imgEscaped . ' ' . $outEscaped . ' -l ' . $langEscaped . ' --dpi 200 2>/dev/null';
            @shell_exec($cmd);
            $txtFile = $outBase . '.txt';
            if (is_file($txtFile)) {
                $content = file_get_contents($txtFile);
                if (is_string($content) && $content !== '') {
                    $texts[] = $content;
                }
            }
        }

        $this->cleanupTempDir($tmpDir);
        if ($texts === []) {
            return null;
        }

        return implode("\n", $texts);
    }

    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = glob($dir . '/*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    private function isCommandAvailable(string $command): bool
    {
        $result = @shell_exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null');
        return is_string($result) && trim($result) !== '';
    }

    private function sanitizeText(string $text): string
    {
        $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252'], true);
        if ($encoding && strtoupper($encoding) !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }

        $text = str_replace("\r", "\n", $text);
        $text = preg_replace("/\n+/", "\n", $text) ?? $text;
        $text = preg_replace('/[\t ]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);

        return $text;
    }

    /**
     * @return array{success:bool,customer?:array<string,mixed>,items?:array<int,array<string,mixed>>,warnings?:array<int,string>,error?:string,summary?:string,template_key?:string,detected_provider?:string,contract_date?:string}
     */
    private function parsePayload(string $text, string $providerName): array
    {
        $normalizedText = trim($text);
        if ($normalizedText === '') {
            return ['success' => false, 'error' => 'Il file è vuoto.'];
        }

        $jsonCandidate = ltrim($normalizedText);
        if ($jsonCandidate !== '' && ($jsonCandidate[0] === '{' || $jsonCandidate[0] === '[')) {
            try {
                $decoded = json_decode($jsonCandidate, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $this->parseFromArray($decoded, $providerName);
                }
            } catch (\JsonException) {
                // Ignora: verrà eseguito il parsing testuale.
            }
        }

        $template = $this->detectTemplate($normalizedText, $providerName);
        $result = $this->parseFromText($normalizedText, $providerName, $template);
        $result['template_key'] = $template['key'] ?? null;
        $result['detected_provider'] = $template['provider'] ?? null;
        $result['contract_date'] = $this->extractContractDate($normalizedText);
        if (($template['key'] ?? null) === null) {
            if (!isset($result['warnings']) || !is_array($result['warnings'])) {
                $result['warnings'] = [];
            }
            $result['warnings'][] = 'Template PDA non riconosciuto: applicato parsing generico.';
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success:bool,customer?:array<string,mixed>,items?:array<int,array<string,mixed>>,warnings?:array<int,string>,error?:string,summary?:string}
     */
    private function parseFromArray(array $payload, string $providerName): array
    {
        $warnings = [];
        $customer = [
            'fullname' => $this->stringOrNull($payload['customer_name'] ?? $payload['fullname'] ?? null),
            'email' => $this->stringOrNull($payload['customer_email'] ?? null),
            'phone' => $this->stringOrNull($payload['customer_phone'] ?? null),
            'tax_code' => $this->normalizeTaxCode($payload['customer_tax_code'] ?? null),
            'note' => $this->stringOrNull($payload['customer_note'] ?? null),
        ];

        $itemsRaw = $payload['items'] ?? null;
        $items = [];
        if (is_array($itemsRaw)) {
            foreach ($itemsRaw as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $iccid = $this->normalizeIccid($row['iccid'] ?? $row['sim'] ?? null);
                $plan = $this->stringOrNull($row['plan'] ?? $row['offer'] ?? null);
                $msisdn = $this->normalizeMsisdn($row['msisdn'] ?? $row['line'] ?? null);
                $price = $this->normalizePrice($row['price'] ?? $row['amount'] ?? null);
                if ($iccid === null && $plan === null) {
                    continue;
                }
                $items[] = [
                    'iccid' => $iccid,
                    'plan' => $plan,
                    'msisdn' => $msisdn,
                    'price' => $price,
                ];
            }
        }

        if ($items === []) {
            $warnings[] = 'Nessuna riga articoli trovata nella PDA JSON.';
        }

        return [
            'success' => true,
            'customer' => $customer,
            'items' => $items,
            'warnings' => $warnings,
            'summary' => 'Import automatico JSON per ' . $providerName,
        ];
    }

    /**
     * @param array<string, mixed>|null $template
     * @return array{success:bool,customer?:array<string,mixed>,items?:array<int,array<string,mixed>>,warnings?:array<int,string>,error?:string,summary?:string}
     */
    private function parseFromText(string $text, string $providerName, ?array $template = null): array
    {
        $warnings = [];

        $isFastweb = $this->isFastwebProvider($providerName);

        if ($template !== null && isset($template['patterns']) && is_array($template['patterns'])) {
            $customer = [
                'fullname' => $this->extractTemplateField($text, $template['patterns']['customer_fullname'] ?? []),
                'email' => $this->extractTemplateField($text, $template['patterns']['customer_email'] ?? []),
                'phone' => $this->normalizeMsisdn($this->extractTemplateField($text, $template['patterns']['customer_phone'] ?? [])),
                'tax_code' => $this->normalizeTaxCode($this->extractTemplateField($text, $template['patterns']['customer_tax_code'] ?? [])),
                'note' => null,
            ];

            $address = $this->extractTemplateField($text, $template['patterns']['customer_address'] ?? []);
            if ($address !== null) {
                $customer['note'] = 'Indirizzo PDA: ' . $address;
            }

            $iccids = $this->extractTemplateMulti($text, $template['patterns']['iccid'] ?? []);
            $plans = $this->extractTemplateMulti($text, $template['patterns']['plan'] ?? []);
            $msisdnList = array_map(fn (?string $value) => $this->normalizeMsisdn($value), $this->extractTemplateMulti($text, $template['patterns']['msisdn'] ?? []));
            $prices = array_map(fn (?string $value) => $this->normalizePrice($value), $this->extractTemplateMulti($text, $template['patterns']['price'] ?? []));

            $items = [];
            $rowCount = max(count($iccids), count($plans), count($msisdnList));
            for ($i = 0; $i < $rowCount; $i++) {
                $iccid = $iccids[$i] ?? $iccids[0] ?? null;
                $plan = $plans[$i] ?? $plans[0] ?? null;
                $msisdn = $msisdnList[$i] ?? $msisdnList[0] ?? null;
                $price = $prices[$i] ?? $prices[0] ?? null;

                if ($iccid === null && $plan === null && $msisdn === null) {
                    continue;
                }

                $items[] = [
                    'iccid' => $this->normalizeIccid($iccid),
                    'plan' => $plan,
                    'msisdn' => $msisdn,
                    'price' => $price,
                    'offer_hint' => $plan,
                    'offer_price_hint' => $price,
                ];
            }

            if ($items === []) {
                return ['success' => false, 'error' => 'Impossibile individuare ICCID o offerta nella PDA.'];
            }

            $required = $template['required'] ?? [];
            if (is_array($required)) {
                foreach ($required as $field) {
                    if ($field === 'iccid') {
                        $hasIccid = false;
                        foreach ($items as $item) {
                            if (!empty($item['iccid'])) {
                                $hasIccid = true;
                                break;
                            }
                        }
                        if (!$hasIccid) {
                            $warnings[] = 'Campo obbligatorio mancante: ICCID.';
                        }
                    }
                }
            }

            return [
                'success' => true,
                'customer' => $customer,
                'items' => $items,
                'warnings' => $warnings,
                'summary' => 'Parsing PDA da template per ' . $providerName,
            ];
        }

        $customer = [
            'fullname' => $this->matchFirst($text, self::FIELD_ALIASES['customer_fullname']),
            'email' => $this->matchFirst($text, self::FIELD_ALIASES['customer_email']),
            'phone' => $this->normalizeMsisdn($this->matchFirst($text, self::FIELD_ALIASES['customer_phone'])),
            'tax_code' => $this->normalizeTaxCode($this->matchFirst($text, self::FIELD_ALIASES['customer_tax_code'])),
            'note' => null,
        ];

        if ($isFastweb) {
            $fastwebName = $this->extractFastwebCustomerName($text);
            if ($fastwebName !== null) {
                $customer['fullname'] = $fastwebName;
            }
        }

        $address = $this->matchFirst($text, self::FIELD_ALIASES['customer_address']);
        if ($address !== null) {
            $customer['note'] = 'Indirizzo PDA: ' . $address;
        }

        $iccids = $this->matchMultiple($text, self::FIELD_ALIASES['iccid']);
        $plans = $this->matchMultiple($text, self::FIELD_ALIASES['plan']);
        $msisdnList = array_map(fn (?string $value) => $this->normalizeMsisdn($value), $this->matchMultiple($text, self::FIELD_ALIASES['msisdn']));
        $prices = array_map(fn (?string $value) => $this->normalizePrice($value), $this->matchMultiple($text, self::FIELD_ALIASES['price']));

        $items = [];
        $fastwebDuplicates = 0;

        if ($isFastweb) {
            $fastwebOffer = $this->extractFastwebOfferDetails($text);
            $uniqueIccids = [];

            foreach ($iccids as $rawIccid) {
                $normalized = $this->normalizeIccid($rawIccid);
                if ($normalized === null) {
                    continue;
                }
                if (isset($uniqueIccids[$normalized])) {
                    $fastwebDuplicates++;
                    continue;
                }
                $uniqueIccids[$normalized] = $normalized;
            }

            $preferredPlan = $this->resolveFastwebPlan($fastwebOffer['plan'], $plans);
            if ($preferredPlan === null && isset($fastwebOffer['plan'])) {
                $rawPlan = is_string($fastwebOffer['plan']) ? trim((string) $fastwebOffer['plan']) : '';
                if ($rawPlan !== '') {
                    $preferredPlan = $this->cleanFastwebPlanCandidate($rawPlan) ?? $rawPlan;
                }
            }
            $preferredPrice = $this->resolveFastwebPrice($fastwebOffer['price'], $prices);
            $preferredMsisdn = $this->firstNonNull($msisdnList);

            foreach (array_values($uniqueIccids) as $normalizedIccid) {
                $items[] = [
                    'iccid' => $normalizedIccid,
                    'plan' => $preferredPlan,
                    'msisdn' => $preferredMsisdn,
                    'price' => $preferredPrice,
                    'offer_hint' => $fastwebOffer['plan'] ?? null,
                    'offer_price_hint' => $fastwebOffer['price'] ?? null,
                ];
            }
        } else {
            $rowCount = max(count($iccids), count($plans), count($msisdnList));
            for ($i = 0; $i < $rowCount; $i++) {
                $iccid = $iccids[$i] ?? $iccids[0] ?? null;
                $plan = $plans[$i] ?? $plans[0] ?? null;
                $msisdn = $msisdnList[$i] ?? $msisdnList[0] ?? null;
                $price = $prices[$i] ?? $prices[0] ?? null;

                if ($iccid === null && $plan === null && $msisdn === null) {
                    continue;
                }

                $items[] = [
                    'iccid' => $this->normalizeIccid($iccid),
                    'plan' => $plan,
                    'msisdn' => $msisdn,
                    'price' => $price,
                    'offer_hint' => $plan,
                    'offer_price_hint' => $price,
                ];
            }
        }

        if ($items === []) {
            return ['success' => false, 'error' => 'Impossibile individuare ICCID o offerta nella PDA.'];
        }

        if ($isFastweb) {
            if ($fastwebDuplicates > 0) {
                $warnings[] = 'Rilevati ' . $fastwebDuplicates . ' riferimenti duplicati agli stessi ICCID: mantenute solo le SIM uniche.';
            }
        } elseif (count($iccids) > 1) {
            $warnings[] = 'Sono stati rilevati ' . count($iccids) . ' ICCID: verifica le righe prima di salvare la vendita.';
        }
        if ($customer['fullname'] === null) {
            $warnings[] = 'Nome cliente non presente nella PDA.';
        }

        return [
            'success' => true,
            'customer' => $customer,
            'items' => $items,
            'warnings' => $warnings,
            'summary' => 'Import testo per ' . $providerName,
        ];
    }

    /**
     * @param array<string, mixed> $profile
     * @return array{status:string,id?:int,fullname?:string,email?:string|null,phone?:string|null,tax_code?:string|null,note?:string|null,warnings?:array<int,string>}
     */
    private function ensureCustomer(array $profile): array
    {
        $warnings = [];

        $fullname = $this->stringOrNull($profile['fullname'] ?? null);
        $email = $this->stringOrNull($profile['email'] ?? null);
        $phone = $this->normalizeMsisdn($profile['phone'] ?? null);
        $taxCode = $this->normalizeTaxCode($profile['tax_code'] ?? null);
        $note = $this->stringOrNull($profile['note'] ?? null);

        $existing = null;
        if ($taxCode !== null) {
            $stmt = $this->pdo->prepare('SELECT id, fullname, email, phone, tax_code, note FROM customers WHERE tax_code = :tax LIMIT 1');
            $stmt->execute([':tax' => $taxCode]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if ($existing === null && $email !== null) {
            $stmt = $this->pdo->prepare('SELECT id, fullname, email, phone, tax_code, note FROM customers WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($existing !== null) {
            $existingSnapshot = [
                'fullname' => $this->stringOrNull($existing['fullname'] ?? null) ?? '',
                'email' => $this->stringOrNull($existing['email'] ?? null),
                'phone' => $this->normalizeMsisdn($existing['phone'] ?? null),
                'tax_code' => $this->normalizeTaxCode($existing['tax_code'] ?? null),
                'note' => $this->stringOrNull($existing['note'] ?? null),
            ];

            $payload = [
                'fullname' => $fullname ?? $existingSnapshot['fullname'],
                'email' => $email ?? $existingSnapshot['email'],
                'phone' => $phone ?? $existingSnapshot['phone'],
                'tax_code' => $taxCode ?? $existingSnapshot['tax_code'],
                'note' => $this->mergeNotes($existingSnapshot['note'], $note),
            ];

            $requiresUpdate = false;
            foreach ($payload as $field => $value) {
                if (($existingSnapshot[$field] ?? null) !== $value) {
                    $requiresUpdate = true;
                    break;
                }
            }

            if ($requiresUpdate) {
                $result = $this->customerService->update((int) $existing['id'], $payload);
                if (!($result['success'] ?? false)) {
                    $warnings[] = 'Aggiornamento cliente non riuscito: ' . ($result['message'] ?? 'errore sconosciuto');
                }

                return [
                    'status' => ($result['success'] ?? false) ? 'updated' : 'skipped',
                    'id' => (int) $existing['id'],
                    'fullname' => $payload['fullname'],
                    'email' => $payload['email'],
                    'phone' => $payload['phone'],
                    'tax_code' => $payload['tax_code'],
                    'note' => $payload['note'],
                    'warnings' => $warnings,
                ];
            }

            return [
                'status' => 'unchanged',
                'id' => (int) $existing['id'],
                'fullname' => $payload['fullname'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'tax_code' => $payload['tax_code'],
                'note' => $payload['note'],
                'warnings' => $warnings,
            ];
        }

        if ($fullname === null) {
            $fullname = 'Cliente PDA ' . date('d/m/Y H:i');
            $warnings[] = 'Nome cliente non presente nella PDA: usato un segnaposto.';
        }

        $result = $this->customerService->create([
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'tax_code' => $taxCode,
            'note' => $note,
        ]);

        if (!($result['success'] ?? false)) {
            $warnings[] = 'Creazione cliente non riuscita: ' . ($result['message'] ?? 'errore sconosciuto');
            return [
                'status' => 'skipped',
                'warnings' => $warnings,
            ];
        }

        return [
            'status' => 'created',
            'id' => (int) ($result['id'] ?? 0),
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'tax_code' => $taxCode,
            'note' => $note,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{items:array<int,array<string,mixed>>,warnings:array<int,string>}
     */
    private function resolveItems(array $items, int $providerId): array
    {
        $resolved = [];
        $warnings = [];

        $iccidStmt = $this->pdo->prepare(
            "SELECT id, provider_id, status FROM iccid_stock WHERE iccid = :iccid ORDER BY FIELD(status, 'InStock', 'Reserved', 'Sold') LIMIT 1"
        );

        foreach ($items as $item) {
            $iccid = $this->normalizeIccid($item['iccid'] ?? null);
            $plan = $this->stringOrNull($item['plan'] ?? null);
            $offerHint = $this->stringOrNull($item['offer_hint'] ?? null);
            $msisdn = $this->normalizeMsisdn($item['msisdn'] ?? null);
            $price = $this->normalizePrice($item['price'] ?? null);

            if ($price === null && isset($item['offer_price_hint'])) {
                $hintPrice = $this->normalizePrice($item['offer_price_hint']);
                if ($hintPrice !== null) {
                    $price = $hintPrice;
                }
            }

            $offerMatch = $this->matchActiveOffer($providerId, [$plan, $offerHint], $price);
            $offerId = null;
            if ($offerMatch !== null) {
                if (isset($offerMatch['title']) && is_string($offerMatch['title']) && $offerMatch['title'] !== '') {
                    $plan = $offerMatch['title'];
                }
                if (($price === null || $price <= 0.0) && isset($offerMatch['price'])) {
                    $matchedPrice = $this->normalizePrice($offerMatch['price']);
                    if ($matchedPrice !== null) {
                        $price = $matchedPrice;
                    }
                }
                if (isset($offerMatch['id'])) {
                    $offerId = (int) $offerMatch['id'];
                }
            }

            $descriptionParts = [];
            if ($plan !== null) {
                $descriptionParts[] = $plan;
            }
            if ($msisdn !== null) {
                $descriptionParts[] = 'MSISDN ' . $msisdn;
            }
            $description = $descriptionParts === [] ? 'Attivazione SIM' : implode(' • ', $descriptionParts);

            $iccidId = null;
            if ($iccid !== null) {
                $iccidStmt->execute([':iccid' => $iccid]);
                $row = $iccidStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($row !== null) {
                    if ((int) $row['provider_id'] !== $providerId) {
                        $warnings[] = 'La SIM ' . $iccid . ' appartiene a un altro gestore: verifica lo stock.';
                    }
                    if ((string) $row['status'] !== 'InStock') {
                        $warnings[] = 'La SIM ' . $iccid . ' risulta già ' . $row['status'] . '.';
                    }
                    $iccidId = (int) $row['id'];
                } else {
                    $warnings[] = 'SIM ' . $iccid . ' non trovata in magazzino: aggiungi manualmente l\'ICCID.';
                }
            }

            $resolved[] = [
                'iccid_id' => $iccidId,
                'iccid_code' => $iccid,
                'description' => $description,
                'price' => $price,
                'quantity' => 1,
                'offer_id' => $offerId,
                'offer_title' => $plan,
                'msisdn' => $msisdn,
            ];
        }

        return [
            'items' => $resolved,
            'warnings' => $warnings,
        ];
    }

    private function isFastwebProvider(string $providerName): bool
    {
        return stripos($providerName, 'fastweb') !== false;
    }

    private function extractFastwebCustomerName(string $text): ?string
    {
        $patterns = [
            '/Proposta\s+di\s+Abbonamento[\s\S]{0,120}?Cliente\s*[:\-]\s*([^\r\n]+)/i',
            '/Cliente\s*[:\-]\s*([^\r\n]+)/i',
            '/Proposta\s+di\s+Abbonamento\s*(?:\r?\n){1,2}([^\r\n]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = $this->cleanFastwebNameCandidate($matches[1] ?? '');
                if ($candidate !== null) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * @return array{plan:?string,price:?float}
     */
    private function extractFastwebOfferDetails(string $text): array
    {
        $plan = null;
        $price = null;

        $planPatterns = [
            '/Dettaglio\s+costi\s*[:\-]?\s*([A-Z0-9][^\r\n]+)/i',
            '/Dettaglio\s+costi\s*(?:\r?\n)+\s*([A-Z0-9][^\r\n]+)/i',
            '/\bFastweb\s+Mobile\s+[A-Za-z0-9 ]{3,60}/i',
        ];

        foreach ($planPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = $this->cleanFastwebPlanCandidate($matches[1] ?? $matches[0] ?? '');
                if ($candidate !== null) {
                    $plan = $candidate;
                    break;
                }
            }
        }

        $pricePatterns = [
            '/Contributo\s+SIM(?:\/eSIM)?\s+([0-9]+(?:[\.,][0-9]{1,2})?)/i',
            '/Prima\s+ricarica[^\r\n]*?([0-9]+(?:[\.,][0-9]{1,2})?)\s*€/i',
        ];

        foreach ($pricePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $normalized = $this->normalizePrice($matches[1] ?? null);
                if ($normalized !== null) {
                    $price = $normalized;
                    break;
                }
            }
        }

        if ($price === null && $plan !== null) {
            $planPos = stripos($text, $plan);
            if ($planPos !== false) {
                $snippet = substr($text, $planPos, 200);
                if (is_string($snippet) && preg_match('/([0-9]+(?:[\.,][0-9]{1,2})?)\s*€/', $snippet, $m)) {
                    $normalized = $this->normalizePrice($m[1]);
                    if ($normalized !== null) {
                        $price = $normalized;
                    }
                }
            }
        }

        return [
            'plan' => $plan,
            'price' => $price,
        ];
    }

    private function cleanFastwebNameCandidate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $candidate = trim($value);
        if ($candidate === '') {
            return null;
        }

        $candidate = preg_split('/\b(?:Offerta|Codice\s+Preventivo|Codice\s+Venditore|Numero\s+Cliente|Mobile\s+Number\s+Portability)\b/i', $candidate)[0] ?? $candidate;
        $candidate = preg_replace('/\b(?:Cliente|Intestatario)\b\s*[:\-]?/i', '', $candidate) ?? $candidate;
        $candidate = trim($candidate, " \t.:,;-");

        return $candidate !== '' ? $candidate : null;
    }

    private function cleanFastwebPlanCandidate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $candidate = trim($value);
        $candidate = preg_replace('/\b(?:Prima\s+ricarica|Contributo\s+SIM\/eSIM|Contributo\s+SIM)\b.*$/i', '', $candidate) ?? $candidate;
        $candidate = trim($candidate, " \t.:,;-");

        if ($candidate === '' || strlen($candidate) < 4) {
            return null;
        }

        return preg_replace('/\s{2,}/', ' ', $candidate);
    }

    private function resolveFastwebPlan(?string $offerPlan, array $plans): ?string
    {
        $candidates = [];

        if ($offerPlan !== null) {
            $clean = $this->cleanFastwebPlanCandidate($offerPlan);
            if ($clean !== null) {
                if (!$this->isFastwebGenericPlan($clean)) {
                    return $clean;
                }
                $candidates[] = $clean;
            }
        }

        foreach ($plans as $plan) {
            if (!is_string($plan)) {
                continue;
            }
            $clean = $this->cleanFastwebPlanCandidate($plan);
            if ($clean === null) {
                continue;
            }
            if (!$this->isFastwebGenericPlan($clean)) {
                return $clean;
            }
            $candidates[] = $clean;
        }

        return $candidates[0] ?? null;
    }

    private function resolveFastwebPrice(?float $offerPrice, array $prices): ?float
    {
        if ($offerPrice !== null && $offerPrice > 0) {
            return round($offerPrice, 2);
        }

        foreach ($prices as $price) {
            if (is_float($price) && $price > 0) {
                return round($price, 2);
            }
        }

        return null;
    }

    private function firstNonNull(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function isFastwebGenericPlan(string $plan): bool
    {
        $normalized = strtolower($plan);
        $genericTokens = [
            'offerta residenziale',
            'offerta business',
            'termini e condizioni',
            'condizioni generali',
            'scheda cliente',
            'modulo',
            'costi mensili',
        ];

        foreach ($genericTokens as $token) {
            if (str_contains($normalized, $token)) {
                return true;
            }
        }

        return false;
    }

    private function matchActiveOffer(int $providerId, array $candidatePlans, ?float $price): ?array
    {
        $normalizedCandidates = [];
        foreach ($candidatePlans as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }
            $trimmed = trim($candidate);
            if ($trimmed === '') {
                continue;
            }
            $normalized = $this->normalizeOfferTitle($trimmed);
            if ($normalized !== '') {
                $normalizedCandidates[$normalized] = $trimmed;
            }
        }

        $needsPriceMatch = $price !== null && $price > 0.0;
        if ($normalizedCandidates === [] && !$needsPriceMatch) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, title, price FROM operator_offers
             WHERE provider_id = :provider_id
               AND status = "Active"
               AND (valid_from IS NULL OR valid_from <= CURRENT_DATE())
               AND (valid_to IS NULL OR valid_to >= CURRENT_DATE())'
        );
        $stmt->execute([':provider_id' => $providerId]);
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $bestOffer = null;
        $bestScore = 0.0;
        $priceMatch = $price !== null ? (float) $price : null;

        foreach ($offers as $offer) {
            $title = isset($offer['title']) ? (string) $offer['title'] : '';
            if ($title === '') {
                continue;
            }
            $normalizedTitle = $this->normalizeOfferTitle($title);
            if ($normalizedTitle === '') {
                continue;
            }

            foreach ($normalizedCandidates as $candidate => $original) {
                if ($candidate === '') {
                    continue;
                }
                if ($normalizedTitle === $candidate) {
                    return [
                        'id' => (int) ($offer['id'] ?? 0),
                        'title' => $title,
                        'price' => isset($offer['price']) ? (float) $offer['price'] : null,
                    ];
                }
                if (str_contains($normalizedTitle, $candidate) || str_contains($candidate, $normalizedTitle)) {
                    if ($bestScore < 0.9) {
                        $bestOffer = $offer;
                        $bestScore = 0.9;
                    }
                }
            }

            if ($priceMatch !== null && isset($offer['price'])) {
                $offerPrice = $this->normalizePrice($offer['price']);
                if ($offerPrice !== null && abs($offerPrice - $priceMatch) < 0.01 && $bestScore < 0.6) {
                    $bestOffer = $offer;
                    $bestScore = 0.6;
                }
            }
        }

        if ($bestOffer !== null) {
            return [
                'id' => (int) ($bestOffer['id'] ?? 0),
                'title' => (string) ($bestOffer['title'] ?? ''),
                'price' => isset($bestOffer['price']) ? (float) $bestOffer['price'] : null,
            ];
        }

        if ($normalizedCandidates === []) {
            return null;
        }

        $firstOriginal = reset($normalizedCandidates);
        if (!is_string($firstOriginal) || $firstOriginal === '') {
            return null;
        }

        try {
            $created = $this->createOperatorOffer($providerId, $firstOriginal, $price);
            if ($created !== null) {
                return $created;
            }
        } catch (\Throwable $exception) {
            // Ignore offer creation failures; fallback to null match.
        }

        return null;
    }

    private function normalizeOfferTitle(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/u', '', $normalized) ?? $normalized;

        return $normalized;
    }

    private function createOperatorOffer(int $providerId, string $title, ?float $price): ?array
    {
        $trimmedTitle = trim($title);
        if ($trimmedTitle === '') {
            return null;
        }

        $amount = $price !== null && $price > 0 ? round($price, 2) : 0.00;

        $insert = $this->pdo->prepare(
            'INSERT INTO operator_offers (provider_id, title, description, price, status, valid_from, valid_to)
             VALUES (:provider_id, :title, NULL, :price, "Active", NULL, NULL)'
        );
        $insert->execute([
            ':provider_id' => $providerId,
            ':title' => $trimmedTitle,
            ':price' => $amount,
        ]);

        $offerId = (int) $this->pdo->lastInsertId();
        if ($offerId <= 0) {
            return null;
        }

        return [
            'id' => $offerId,
            'title' => $trimmedTitle,
            'price' => $amount,
        ];
    }

    private function normalizeProviderKey(string $providerName): string
    {
        $key = strtolower(trim($providerName));
        $key = preg_replace('/[^a-z0-9]+/', '', $key) ?? $key;
        return $key;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function detectTemplate(string $text, string $providerName): ?array
    {
        $providerKey = $this->normalizeProviderKey($providerName);
        $templates = $this->templates[$providerKey] ?? $this->templates['generic'] ?? [];
        foreach ($templates as $template) {
            if (!is_array($template)) {
                continue;
            }
            $matchers = $template['match'] ?? [];
            if (!is_array($matchers)) {
                continue;
            }
            $matched = true;
            foreach ($matchers as $pattern) {
                if (!is_string($pattern) || preg_match($pattern, $text) !== 1) {
                    $matched = false;
                    break;
                }
            }
            if ($matched) {
                $template['provider'] = $providerName;
                return $template;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $patterns
     */
    private function extractTemplateField(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (!is_string($pattern)) {
                continue;
            }
            if (preg_match($pattern, $text, $matches)) {
                $value = trim((string) ($matches[1] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $patterns
     * @return array<int, string>
     */
    private function extractTemplateMulti(string $text, array $patterns): array
    {
        $results = [];
        foreach ($patterns as $pattern) {
            if (!is_string($pattern)) {
                continue;
            }
            if (preg_match_all($pattern, $text, $matches)) {
                $values = $matches[1] ?? $matches[0] ?? [];
                foreach ($values as $value) {
                    $candidate = trim((string) $value);
                    if ($candidate !== '') {
                        $results[] = $candidate;
                    }
                }
                if ($results !== []) {
                    return $results;
                }
            }
        }

        return $results;
    }

    private function extractContractDate(string $text): ?string
    {
        $patterns = [
            '/\b(\d{2})[\/\.\-](\d{2})[\/\.\-](\d{4})\b/',
            '/\b(\d{4})[\/\.\-](\d{2})[\/\.\-](\d{2})\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (strlen($matches[1]) === 4) {
                    return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                }
                return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
        }

        return null;
    }

    private function findDuplicateImport(string $fileHash, int $providerId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM pda_imports WHERE file_hash = :hash AND provider_id = :provider_id ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([
            ':hash' => $fileHash,
            ':provider_id' => $providerId,
        ]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    private function findDuplicateByCustomer(?int $customerId, int $providerId, string $contractDate): ?int
    {
        if ($customerId === null || $customerId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM pda_imports
             WHERE customer_id = :customer_id AND provider_id = :provider_id AND contract_date = :contract_date
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([
            ':customer_id' => $customerId,
            ':provider_id' => $providerId,
            ':contract_date' => $contractDate,
        ]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    /**
     * @param array<string, mixed> $customer
     * @return array{id?:int,fullname?:string,email?:string,phone?:string,tax_code?:string}
     */
    private function matchCustomer(array $customer): array
    {
        $taxCode = $this->normalizeTaxCode($customer['tax_code'] ?? null);
        $email = $this->stringOrNull($customer['email'] ?? null);

        if ($taxCode !== null) {
            $stmt = $this->pdo->prepare('SELECT id, fullname, email, phone, tax_code FROM customers WHERE tax_code = :tax_code LIMIT 1');
            $stmt->execute([':tax_code' => $taxCode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                return [
                    'id' => (int) $row['id'],
                    'fullname' => (string) ($row['fullname'] ?? ''),
                    'email' => (string) ($row['email'] ?? ''),
                    'phone' => (string) ($row['phone'] ?? ''),
                    'tax_code' => (string) ($row['tax_code'] ?? ''),
                ];
            }
        }

        if ($email !== null) {
            $stmt = $this->pdo->prepare('SELECT id, fullname, email, phone, tax_code FROM customers WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                return [
                    'id' => (int) $row['id'],
                    'fullname' => (string) ($row['fullname'] ?? ''),
                    'email' => (string) ($row['email'] ?? ''),
                    'phone' => (string) ($row['phone'] ?? ''),
                    'tax_code' => (string) ($row['tax_code'] ?? ''),
                ];
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<int, array<string, mixed>> $items
     * @return array{warnings:array<int,string>,errors:array<int,string>,field_status:array<string,array<string,string>>,item_status:array<int,array<string,string>>}
     */
    private function validatePreview(array $customer, array $items): array
    {
        $warnings = [];
        $errors = [];
        $fieldStatus = [];
        $itemStatus = [];

        $name = $this->stringOrNull($customer['fullname'] ?? null);
        if ($name === null) {
            $fieldStatus['customer_name'] = ['status' => 'warning', 'message' => 'Nome cliente mancante'];
            $warnings[] = 'Nome cliente mancante nella PDA.';
        } else {
            $fieldStatus['customer_name'] = ['status' => 'valid', 'message' => 'Nome cliente presente'];
        }

        $email = $this->stringOrNull($customer['email'] ?? null);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fieldStatus['customer_email'] = ['status' => 'warning', 'message' => 'Email non valida'];
            $warnings[] = 'Email cliente non valida.';
        } elseif ($email !== null) {
            $fieldStatus['customer_email'] = ['status' => 'valid', 'message' => 'Email valida'];
        } else {
            $fieldStatus['customer_email'] = ['status' => 'warning', 'message' => 'Email non presente'];
        }

        $phone = $this->normalizeMsisdn($customer['phone'] ?? null);
        if ($phone !== null && !$this->isValidPhone($phone)) {
            $fieldStatus['customer_phone'] = ['status' => 'warning', 'message' => 'Telefono non valido'];
            $warnings[] = 'Numero di telefono cliente non valido.';
        } elseif ($phone !== null) {
            $fieldStatus['customer_phone'] = ['status' => 'valid', 'message' => 'Telefono valido'];
        } else {
            $fieldStatus['customer_phone'] = ['status' => 'warning', 'message' => 'Telefono non presente'];
        }

        $taxCode = $this->normalizeTaxCode($customer['tax_code'] ?? null);
        if ($taxCode !== null && !$this->isValidTaxCode($taxCode)) {
            $fieldStatus['customer_tax_code'] = ['status' => 'warning', 'message' => 'Codice fiscale non valido'];
            $warnings[] = 'Codice fiscale non valido.';
        } elseif ($taxCode !== null) {
            $fieldStatus['customer_tax_code'] = ['status' => 'valid', 'message' => 'Codice fiscale valido'];
        } else {
            $fieldStatus['customer_tax_code'] = ['status' => 'warning', 'message' => 'Codice fiscale non presente'];
        }

        $note = $this->stringOrNull($customer['note'] ?? null);
        if ($note !== null && strlen($note) >= 8) {
            $fieldStatus['customer_note'] = ['status' => 'valid', 'message' => 'Indirizzo presente'];
        } else {
            $fieldStatus['customer_note'] = ['status' => 'warning', 'message' => 'Indirizzo incompleto'];
            if ($note !== null) {
                $warnings[] = 'Indirizzo cliente troppo breve o incompleto.';
            }
        }

        foreach ($items as $index => $item) {
            $itemStatus[$index] = [];
            $iccid = $this->normalizeIccid($item['iccid_code'] ?? $item['iccid'] ?? null);
            if ($iccid === null || !$this->isValidIccid($iccid)) {
                $itemStatus[$index]['iccid'] = 'error';
                $errors[] = 'ICCID mancante o non valido alla riga ' . ($index + 1) . '.';
            } else {
                $itemStatus[$index]['iccid'] = 'valid';
            }
        }

        return [
            'warnings' => $warnings,
            'errors' => $errors,
            'field_status' => $fieldStatus,
            'item_status' => $itemStatus,
        ];
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<int, array<string, mixed>> $items
     * @return array{warnings:array<int,string>,errors:array<int,string>}
     */
    private function checkCoherence(array $customer, array $items, string $selectedProvider, string $detectedProvider): array
    {
        $warnings = [];
        $errors = [];

        $note = $this->stringOrNull($customer['note'] ?? null);
        $hasCivic = $note !== null && preg_match('/\b\d+\b/', $note) === 1;

        foreach ($items as $index => $item) {
            $plan = strtolower((string) ($item['offer_title'] ?? $item['description'] ?? ''));
            $msisdn = $this->normalizeMsisdn($item['msisdn'] ?? null);
            $iccid = $this->normalizeIccid($item['iccid_code'] ?? $item['iccid'] ?? null);

            if (str_contains($plan, 'ftth') && !$hasCivic) {
                $warnings[] = 'FTTH senza civico: verifica l\'indirizzo alla riga ' . ($index + 1) . '.';
            }

            if (str_contains($plan, 'migraz') && ($msisdn === null || $msisdn === '')) {
                $errors[] = 'Migrazione senza numero: aggiungi MSISDN alla riga ' . ($index + 1) . '.';
            }

            if ($iccid === null) {
                $errors[] = 'SIM senza ICCID: verifica la riga ' . ($index + 1) . '.';
            }
        }

        if ($detectedProvider !== '' && strcasecmp($detectedProvider, $selectedProvider) !== 0) {
            $errors[] = 'Operatore selezionato non corrisponde alla PDA (rilevato: ' . $detectedProvider . ').';
        }

        return [
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    private function isValidTaxCode(string $taxCode): bool
    {
        $taxCode = strtoupper(trim($taxCode));
        if (preg_match('/^[0-9]{11}$/', $taxCode)) {
            return true;
        }
        if (!preg_match('/^[A-Z0-9]{16}$/', $taxCode)) {
            return false;
        }

        $oddMap = [
            '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21,
            'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21,
            'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14,
            'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23,
        ];
        $evenMap = [
            '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9,
            'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19,
            'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25,
        ];

        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $char = $taxCode[$i];
            if (($i + 1) % 2 === 0) {
                $sum += $evenMap[$char] ?? 0;
            } else {
                $sum += $oddMap[$char] ?? 0;
            }
        }
        $check = chr(($sum % 26) + ord('A'));
        return $check === $taxCode[15];
    }

    private function isValidPhone(string $phone): bool
    {
        return preg_match('/^\+?[0-9]{7,15}$/', $phone) === 1;
    }

    private function isValidIccid(string $iccid): bool
    {
        return preg_match('/^\d{19,20}$/', $iccid) === 1;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $currentUser
     * @return array{success:bool,message:string,warnings?:array<int,string>,errors?:array<int,string>,prefill?:array<string,mixed>}
     */
    public function confirmImport(array $input, ?array $currentUser = null): array
    {
        $importId = isset($input['pda_import_id']) ? (int) $input['pda_import_id'] : 0;
        if ($importId <= 0) {
            return ['success' => false, 'message' => 'Riferimento import non valido.', 'errors' => ['Import PDA mancante.']];
        }

        $import = $this->fetchImport($importId);
        if ($import === null) {
            return ['success' => false, 'message' => 'Import PDA non trovato.', 'errors' => ['Riferimento non valido.']];
        }

        $providerId = (int) ($import['provider_id'] ?? 0);
        $providerName = (string) ($import['provider_name'] ?? '');
        if ($providerId <= 0) {
            return ['success' => false, 'message' => 'Gestore non valido per l\'import.', 'errors' => ['Provider mancante.']];
        }

        $customer = [
            'fullname' => $this->stringOrNull($input['pda_customer_name'] ?? null),
            'email' => $this->stringOrNull($input['pda_customer_email'] ?? null),
            'phone' => $this->stringOrNull($input['pda_customer_phone'] ?? null),
            'tax_code' => $this->normalizeTaxCode($input['pda_customer_tax_code'] ?? null),
            'note' => $this->stringOrNull($input['pda_customer_note'] ?? null),
        ];

        $items = [];
        $rawItems = $input['pda_items'] ?? [];
        if (is_array($rawItems)) {
            foreach ($rawItems as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $items[] = [
                    'iccid' => $row['iccid'] ?? null,
                    'plan' => $row['plan'] ?? null,
                    'msisdn' => $row['msisdn'] ?? null,
                    'price' => $row['price'] ?? null,
                    'offer_hint' => $row['plan'] ?? null,
                ];
            }
        }

        $resolvedItems = $this->resolveItems($items, $providerId);
        $validation = $this->validatePreview($customer, $resolvedItems['items']);
        $coherence = $this->checkCoherence($customer, $resolvedItems['items'], $providerName, $providerName);
        $warnings = array_merge($resolvedItems['warnings'], $validation['warnings'], $coherence['warnings']);
        $errors = array_merge($validation['errors'], $coherence['errors']);

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Correggi gli errori prima di confermare l\'import.',
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        $customerResult = $this->ensureCustomer($customer);
        $warnings = array_merge($warnings, $customerResult['warnings'] ?? []);

        $prefill = [
            'provider' => [
                'id' => $providerId,
                'name' => $providerName,
            ],
            'customer_id' => $customerResult['id'] ?? null,
            'customer_name' => $customerResult['fullname'] ?? $customer['fullname'],
            'customer_email' => $customerResult['email'] ?? $customer['email'],
            'customer_phone' => $customerResult['phone'] ?? $customer['phone'],
            'customer_tax_code' => $customerResult['tax_code'] ?? $customer['tax_code'],
            'customer_note' => $customerResult['note'] ?? $customer['note'],
            'items' => $resolvedItems['items'],
        ];

        $this->updateImport($importId, [
            'status' => 'Processed',
            'customer_id' => $customerResult['id'] ?? null,
            'customer_payload' => $customer,
            'sale_payload' => $resolvedItems['items'],
            'warnings' => $warnings,
            'errors' => $errors,
            'user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
        ]);

        $messageParts = ['Import PDA confermato.'];
        if (($customerResult['status'] ?? '') === 'created') {
            $messageParts[] = 'Cliente creato automaticamente.';
        } elseif (($customerResult['status'] ?? '') === 'updated') {
            $messageParts[] = 'Dati cliente aggiornati.';
        }

        return [
            'success' => true,
            'message' => implode(' ', $messageParts),
            'warnings' => $warnings,
            'prefill' => $prefill,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $currentUser
     * @return array{success:bool,message:string,errors?:array<int,string>}
     */
    public function cancelImport(array $input, ?array $currentUser = null): array
    {
        $importId = isset($input['pda_import_id']) ? (int) $input['pda_import_id'] : 0;
        if ($importId <= 0) {
            return ['success' => false, 'message' => 'Riferimento import non valido.', 'errors' => ['Import PDA mancante.']];
        }

        $import = $this->fetchImport($importId);
        if ($import === null) {
            return ['success' => false, 'message' => 'Import PDA non trovato.', 'errors' => ['Riferimento non valido.']];
        }

        $this->updateImport($importId, [
            'status' => 'Failed',
            'error_message' => 'Import annullato dall\'utente.',
            'user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
        ]);

        return [
            'success' => true,
            'message' => 'Import PDA annullato.',
        ];
    }

    private function fetchImport(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pda_imports WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateImport(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE pda_imports
             SET status = :status,
                 customer_id = :customer_id,
                 customer_payload = :customer_payload,
                 sale_payload = :sale_payload,
                 warnings = :warnings,
                 errors = :errors,
                 error_message = :error_message,
                 user_id = :user_id
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => $data['status'] ?? 'Processed',
            ':customer_id' => $data['customer_id'] ?? null,
            ':customer_payload' => $this->encodeJson($data['customer_payload'] ?? null),
            ':sale_payload' => $this->encodeJson($data['sale_payload'] ?? null),
            ':warnings' => $this->encodeJson($data['warnings'] ?? null),
            ':errors' => $this->encodeJson($data['errors'] ?? null),
            ':error_message' => $data['error_message'] ?? null,
            ':user_id' => $data['user_id'] ?? null,
            ':id' => $id,
        ]);
    }

    /**
     * @return array{rows:array<int,array<string,mixed>>,pagination:array<string,int|bool>}
     */
    public function listImports(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $total = (int) ($this->pdo->query('SELECT COUNT(*) FROM pda_imports')->fetchColumn() ?: 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT id, provider_name, source_filename, status, created_at, template_key, ocr_used, contract_date
             FROM pda_imports
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
            ],
        ];
    }

    public function getImportDetail(int $id): ?array
    {
        return $this->fetchImport($id);
    }

    /**
     * @param array<string, mixed>|null $currentUser
     * @return array{success:bool,message:string,warnings?:array<int,string>,errors?:array<int,string>,preview?:array<string,mixed>}
     */
    public function reprocessImport(int $id, ?array $currentUser = null): array
    {
        $import = $this->fetchImport($id);
        if ($import === null) {
            return ['success' => false, 'message' => 'Import PDA non trovato.', 'errors' => ['Riferimento non valido.']];
        }

        $storedPath = $import['stored_path'] ?? null;
        if (!is_string($storedPath) || $storedPath === '') {
            return ['success' => false, 'message' => 'File PDA non disponibile.', 'errors' => ['Percorso file mancante.']];
        }

        $absolute = realpath(__DIR__ . '/../../' . ltrim($storedPath, '/'));
        if ($absolute === false || !is_file($absolute)) {
            return ['success' => false, 'message' => 'File PDA non trovato sul server.', 'errors' => ['File non disponibile.']];
        }

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $extraction = $this->extractTextFromFile($absolute, $extension);
        if (!($extraction['success'] ?? false)) {
            return ['success' => false, 'message' => 'Estrazione testo non riuscita.', 'errors' => [$extraction['error'] ?? 'Errore OCR/PDF']];
        }

        $text = (string) $extraction['text'];
        $providerName = (string) ($import['provider_name'] ?? '');
        $parsed = $this->parsePayload($text, $providerName);
        if (!($parsed['success'] ?? false)) {
            return ['success' => false, 'message' => 'Parsing PDA non riuscito.', 'errors' => [$parsed['error'] ?? 'Errore parsing']];
        }

        $warnings = $parsed['warnings'] ?? [];
        $customerProfile = $parsed['customer'] ?? [];
        $items = $parsed['items'] ?? [];
        $resolvedItems = $this->resolveItems($items, (int) ($import['provider_id'] ?? 0));
        $warnings = array_merge($warnings, $resolvedItems['warnings']);

        $validation = $this->validatePreview($customerProfile, $resolvedItems['items']);
        $warnings = array_merge($warnings, $validation['warnings']);
        $errors = $validation['errors'];

        $preview = [
            'provider' => [
                'id' => (int) ($import['provider_id'] ?? 0),
                'name' => $providerName,
            ],
            'detected_provider' => $parsed['detected_provider'] ?? null,
            'template_key' => $parsed['template_key'] ?? null,
            'contract_date' => $parsed['contract_date'] ?? null,
            'customer' => $customerProfile,
            'items' => $resolvedItems['items'],
            'field_status' => $validation['field_status'],
            'item_status' => $validation['item_status'],
            'warnings' => $warnings,
            'errors' => $errors,
        ];

        $this->updateImport($id, [
            'status' => 'Preview',
            'customer_payload' => $customerProfile,
            'sale_payload' => $resolvedItems['items'],
            'warnings' => $warnings,
            'errors' => $errors,
            'user_id' => isset($currentUser['id']) ? (int) $currentUser['id'] : null,
        ]);

        return [
            'success' => true,
            'message' => 'Import rielaborato: verifica la preview.',
            'warnings' => $warnings,
            'errors' => $errors,
            'preview' => $preview,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function recordImport(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pda_imports (
                user_id, provider_id, provider_name, source_filename, stored_path, file_hash, contract_date,
                template_key, ocr_used, status,
                customer_id, customer_payload, sale_payload, raw_text, ocr_text, warnings, errors, preview_payload,
                notes, error_message
            ) VALUES (
                :user_id, :provider_id, :provider_name, :source_filename, :stored_path, :file_hash, :contract_date,
                :template_key, :ocr_used, :status,
                :customer_id, :customer_payload, :sale_payload, :raw_text, :ocr_text, :warnings, :errors, :preview_payload,
                :notes, :error_message
            )'
        );

        $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':provider_id' => $data['provider_id'] ?? null,
            ':provider_name' => $data['provider_name'] ?? '',
            ':source_filename' => $data['source_filename'] ?? '',
            ':stored_path' => $this->relativeStoragePath($data['stored_path'] ?? ''),
            ':file_hash' => $data['file_hash'] ?? null,
            ':contract_date' => $data['contract_date'] ?? null,
            ':template_key' => $data['template_key'] ?? null,
            ':ocr_used' => (int) ($data['ocr_used'] ?? 0),
            ':status' => $data['status'] ?? 'Processed',
            ':customer_id' => $data['customer_id'] ?? null,
            ':customer_payload' => $this->encodeJson($data['customer_payload'] ?? null),
            ':sale_payload' => $this->encodeJson($data['sale_payload'] ?? null),
            ':raw_text' => $data['raw_text'] ?? null,
            ':ocr_text' => $data['ocr_text'] ?? null,
            ':warnings' => $this->encodeJson($data['warnings'] ?? null),
            ':errors' => $this->encodeJson($data['errors'] ?? null),
            ':preview_payload' => $this->encodeJson($data['preview_payload'] ?? null),
            ':notes' => $data['notes'] ?? null,
            ':error_message' => $data['error_message'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function relativeStoragePath(string $absolutePath): string
    {
        if ($absolutePath === '') {
            return '';
        }

        $base = realpath(__DIR__ . '/../../');
        $absolute = realpath($absolutePath);
        if ($base === false || $absolute === false) {
            return $absolutePath;
        }

        if (str_starts_with($absolute, $base)) {
            return ltrim(str_replace($base, '', $absolute), DIRECTORY_SEPARATOR);
        }

        return $absolutePath;
    }

    private function encodeJson(mixed $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        try {
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException) {
            return null;
        }

        return $encoded;
    }

    /**
     * @param array<int, string> $labels
     */
    private function matchFirst(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $pattern = '/(?:^|\n)\s*' . preg_quote($label, '/') . '\s*[:\-]?\s*(.+)$/mi';
            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1]);
                if ($value !== '') {
                    return $this->stripTrailingLabelArtifacts($value);
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $labels
     * @return array<int, string|null>
     */
    private function matchMultiple(string $text, array $labels): array
    {
        $results = [];
        foreach ($labels as $label) {
            $pattern = '/(?:^|\n)\s*' . preg_quote($label, '/') . '\s*[:\-]?\s*(.+)$/mi';
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $value) {
                    $trimmed = $this->stripTrailingLabelArtifacts(trim((string) $value));
                    if ($trimmed !== '') {
                        $results[] = $trimmed;
                    }
                }
            }
        }

        return $results;
    }

    private function stripTrailingLabelArtifacts(string $value): string
    {
        return preg_replace('/\s+(?:Cod\.\s*Fisc\.|MSISDN|ICCID)\b.*$/i', '', $value) ?? $value;
    }

    private function normalizeIccid(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $digits = preg_replace('/[^0-9]/', '', $value);
        if ($digits === null || $digits === '' || strlen($digits) < 18) {
            return null;
        }

        return substr($digits, 0, 22);
    }

    private function normalizeMsisdn(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $digits = preg_replace('/[^0-9]/', '', $value);
        if ($digits === null || $digits === '') {
            return null;
        }
        if (strlen($digits) >= 9 && !str_starts_with($digits, '39')) {
            $digits = '39' . $digits;
        }

        return '+' . $digits;
    }

    private function normalizePrice(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }
        if (!is_string($value)) {
            return null;
        }
        $clean = preg_replace('/[^0-9,\.]/', '', $value);
        if ($clean === null || $clean === '') {
            return null;
        }
        $clean = trim($clean);
        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                // Formato europeo: usa la virgola come separatore decimale.
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // Formato anglosassone.
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($lastComma !== false) {
            $clean = str_replace(',', '.', $clean);
        }

        if (!is_numeric($clean)) {
            return null;
        }

        return round((float) $clean, 2);
    }

    private function normalizeTaxCode(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? $normalized;
        if ($normalized === '') {
            return null;
        }
        return $normalized;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function mergeNotes(?string $existing, ?string $note): ?string
    {
        if ($existing === null || $existing === '') {
            return $note;
        }
        if ($note === null || $note === '') {
            return $existing;
        }
        if (str_contains($existing, $note)) {
            return $existing;
        }

        return trim($existing . '\n' . $note);
    }
}
