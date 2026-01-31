<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/config/database.php';

$pdo = Database::getConnection();

function normalizeName(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}

function findLatestUrl(string $html, string $pattern): ?string
{
    if (!preg_match_all($pattern, $html, $matches)) {
        return null;
    }
    if (empty($matches[0])) {
        return null;
    }
    return $matches[0][0];
}

function buildPlacets(string $supply, string $version, string $date): string
{
    $prefix = $supply === 'luce' ? 'E' : 'G';
    return "https://www.ilportaleofferte.it/portaleOfferte/resources/opendata/csv/offerte/{$version}/PO_Offerte_{$prefix}_PLACET_{$date}.csv";
}

function buildMl(string $supply, string $version, string $date): string
{
    $prefix = $supply === 'luce' ? 'E' : ($supply === 'gas' ? 'G' : 'D');
    return "https://www.ilportaleofferte.it/portaleOfferte/resources/opendata/csv/offerteML/{$version}/PO_Offerte_{$prefix}_MLIBERO_{$date}.xml";
}

function urlExists(string $url): bool
{
    $headers = @get_headers($url);
    if (!is_array($headers) || $headers === []) {
        return false;
    }
    return str_contains($headers[0], '200') || str_contains($headers[0], '302');
}

function resolveUrl(?string $found, array $candidates): ?string
{
    if ($found !== null && $found !== '') {
        return $found;
    }
    foreach ($candidates as $candidate) {
        if (urlExists($candidate)) {
            return $candidate;
        }
    }
    return null;
}

function downloadCsv(string $url): array
{
    $handle = fopen($url, 'r');
    if ($handle === false) {
        throw new RuntimeException('Impossibile scaricare: ' . $url);
    }

    $header = fgetcsv($handle, 0, ',', '"', '\\');
    if ($header === false) {
        fclose($handle);
        throw new RuntimeException('CSV non valido: ' . $url);
    }

    $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if (count($row) !== count($header)) {
            continue;
        }
        $rows[] = array_combine($header, $row);
    }
    fclose($handle);

    return $rows;
}

function downloadXml(string $url): \SimpleXMLElement
{
    $content = file_get_contents($url);
    if ($content === false) {
        throw new RuntimeException('Impossibile scaricare: ' . $url);
    }
    $xml = simplexml_load_string($content);
    if ($xml === false) {
        throw new RuntimeException('XML non valido: ' . $url);
    }
    return $xml;
}

function xmlValue(\SimpleXMLElement $node, string $xpath, string $ns): string
{
    if ($ns !== '') {
        $node->registerXPathNamespace('ns', $ns);
    }
    $nodes = $node->xpath($xpath);
    if (!is_array($nodes) || $nodes === []) {
        return '';
    }
    return trim((string) $nodes[0]);
}

function xmlChildValue(\SimpleXMLElement $node, string $name, string $ns): string
{
    if ($ns !== '') {
        $child = $node->children($ns)->{$name};
    } else {
        $child = $node->{$name};
    }

    return $child !== null ? trim((string) $child) : '';
}

function xmlChildrenList(\SimpleXMLElement $node, string $name, string $ns): array
{
    $items = [];
    if ($ns !== '') {
        foreach ($node->children($ns)->{$name} as $child) {
            $items[] = $child;
        }
    } else {
        foreach ($node->{$name} as $child) {
            $items[] = $child;
        }
    }
    return $items;
}

$openDataUrl = 'https://www.ilportaleofferte.it/portaleOfferte/it/open-data.page';
$html = file_get_contents($openDataUrl);
if ($html === false) {
    $html = '';
}

$patternE = '~https://www\.ilportaleofferte\.it/portaleOfferte/resources/opendata/csv/offerte/[^\"]*/PO_Offerte_E_PLACET_[0-9]{8}\.csv~';
$patternG = '~https://www\.ilportaleofferte\.it/portaleOfferte/resources/opendata/csv/offerte/[^\"]*/PO_Offerte_G_PLACET_[0-9]{8}\.csv~';
$patternEml = '~https://www\.ilportaleofferte\.it/portaleOfferte/resources/opendata/csv/offerteML/[^\"]*/PO_Offerte_E_MLIBERO_[0-9]{8}\.xml~';
$patternGml = '~https://www\.ilportaleofferte\.it/portaleOfferte/resources/opendata/csv/offerteML/[^\"]*/PO_Offerte_G_MLIBERO_[0-9]{8}\.xml~';
$patternDml = '~https://www\.ilportaleofferte\.it/portaleOfferte/resources/opendata/csv/offerteML/[^\"]*/PO_Offerte_D_MLIBERO_[0-9]{8}\.xml~';

$urlE = findLatestUrl($html, $patternE);
$urlG = findLatestUrl($html, $patternG);
$urlEml = findLatestUrl($html, $patternEml);
$urlGml = findLatestUrl($html, $patternGml);
$urlDml = findLatestUrl($html, $patternDml);

$today = new DateTimeImmutable('now');
$dates = [
    $today->format('Ymd'),
    $today->modify('-1 day')->format('Ymd'),
    $today->modify('-7 days')->format('Ymd'),
];
$year = (int) $today->format('Y');
$semester = ((int) $today->format('n')) <= 6 ? '1' : '2';
$versions = [
    $year . '_' . $semester,
    $year . '_' . ($semester === '1' ? '2' : '1'),
    ($year - 1) . '_2',
];

$placetECandidates = [];
$placetGCandidates = [];
$mlECandidates = [];
$mlGCandidates = [];
$mlDCandidates = [];
foreach ($versions as $version) {
    foreach ($dates as $date) {
        $placetECandidates[] = buildPlacets('luce', $version, $date);
        $placetGCandidates[] = buildPlacets('gas', $version, $date);
        $mlECandidates[] = buildMl('luce', $version, $date);
        $mlGCandidates[] = buildMl('gas', $version, $date);
        $mlDCandidates[] = buildMl('luce_gas', $version, $date);
    }
}

$urlE = resolveUrl($urlE, $placetECandidates);
$urlG = resolveUrl($urlG, $placetGCandidates);
$urlEml = resolveUrl($urlEml, $mlECandidates);
$urlGml = resolveUrl($urlGml, $mlGCandidates);
$urlDml = resolveUrl($urlDml, $mlDCandidates);

if ($urlE === null || $urlG === null) {
    throw new RuntimeException('Impossibile individuare i CSV PLACET.');
}

$tenantsStmt = $pdo->query('SELECT id, name FROM tenants WHERE is_active = 1 ORDER BY id ASC');
$tenants = $tenantsStmt !== false ? $tenantsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
if ($tenants === []) {
    echo "Nessun tenant attivo.\n";
    exit(0);
}

/**
 * @param array<int, array{id:int,name:string,normalized:string}> $providers
 * @param array<string, array{id:int,name:string,normalized:string}> $providersByNormalized
 */
function matchProvider(array $providers, array $providersByNormalized, string $providerName): ?array
{
    $normalized = normalizeName($providerName);
    if ($normalized !== '' && isset($providersByNormalized[$normalized])) {
        return $providersByNormalized[$normalized];
    }
    foreach ($providers as $provider) {
        if ($provider['normalized'] === '') {
            continue;
        }
        if (str_contains($normalized, $provider['normalized']) || str_contains($provider['normalized'], $normalized)) {
            return $provider;
        }

        $tokens = array_filter(explode(' ', $provider['normalized']), static fn (string $token): bool => mb_strlen($token) >= 4);
        foreach ($tokens as $token) {
            if (str_contains($normalized, $token)) {
                return $provider;
            }
        }
    }
    return null;
}

function toDate(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }
    $normalized = trim($value);
    if (str_contains($normalized, '_')) {
        $normalized = explode('_', $normalized, 2)[0];
    }
    $parts = explode('/', $normalized);
    if (count($parts) !== 3) {
        return null;
    }
    return sprintf('%04d-%02d-%02d', (int) $parts[2], (int) $parts[1], (int) $parts[0]);
}

function toFloat(?string $value): ?float
{
    if ($value === null || trim($value) === '') {
        return null;
    }
    return (float) str_replace(',', '.', $value);
}

function toNullIfZero(float $value): ?float
{
    return abs($value) > 0.0000001 ? $value : null;
}

function parseMlPrices(\SimpleXMLElement $offer, string $ns, string $supply): array
{
    $fixed = 0.0;
    $f1 = 0.0;
    $f2 = 0.0;
    $f3 = 0.0;
    $mono = 0.0;
    $vol = 0.0;

    $components = xmlChildrenList($offer, 'ComponenteImpresa', $ns);
    foreach ($components as $component) {
        $intervals = xmlChildrenList($component, 'IntervalloPrezzi', $ns);
        foreach ($intervals as $interval) {
            $unit = xmlChildValue($interval, 'UNITA_MISURA', $ns);
            $fascia = xmlChildValue($interval, 'FASCIA_COMPONENTE', $ns);
            $price = toFloat(xmlChildValue($interval, 'PREZZO', $ns));
            if ($price === null) {
                continue;
            }

            if ($unit === '01') {
                $fixed += $price;
                continue;
            }

            if ($unit === '03') {
                if ($fascia === '01') {
                    $f1 += $price;
                } elseif ($fascia === '02') {
                    $f2 += $price;
                } elseif ($fascia === '03') {
                    $f3 += $price;
                } else {
                    $mono += $price;
                }
                continue;
            }

            if ($unit === '04') {
                $vol += $price;
            }
        }
    }

    return [
        'p_fix_f' => toNullIfZero($fixed),
        'p_vol_f1' => toNullIfZero($f1),
        'p_vol_f2' => toNullIfZero($f2),
        'p_vol_f3' => toNullIfZero($f3),
        'p_vol_mono' => toNullIfZero($mono),
        'p_vol' => toNullIfZero($vol),
    ];
}

$datasets = [
    ['url' => $urlE, 'supply' => 'luce'],
    ['url' => $urlG, 'supply' => 'gas'],
];

$imported = 0;
foreach ($tenants as $tenant) {
    $tenantId = (int) ($tenant['id'] ?? 0);
    if ($tenantId <= 0) {
        continue;
    }

    $providers = [];
    $providersByNormalized = [];
    $pivaMap = [];
    $stmtProviders = $pdo->prepare('SELECT id, name FROM energy_providers WHERE tenant_id = :tenant_id');
    $stmtProviders->execute([':tenant_id' => $tenantId]);
    foreach ($stmtProviders->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $normalized = normalizeName((string) $row['name']);
        $provider = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'normalized' => $normalized,
        ];
        $providers[] = $provider;
        if ($normalized !== '') {
            $providersByNormalized[$normalized] = $provider;
        }
    }

    if ($providers === []) {
        echo 'Nessun gestore energia configurato per tenant #' . $tenantId . ".\n";
        continue;
    }

    $insert = $pdo->prepare(
        'INSERT INTO energy_offers
            (tenant_id, provider_id, provider_name, offer_code, offer_name, supply_type, customer_type, offer_type, price_type,
             p_fix_f, p_fix_v, p_vol_f1, p_vol_f2, p_vol_f3, p_vol_bf1, p_vol_bf23, p_vol_mono, p_vol, alpha,
             region, province, municipality, offer_url, valid_from, valid_to)
         VALUES
            (:tenant_id, :provider_id, :provider_name, :offer_code, :offer_name, :supply_type, :customer_type, :offer_type, :price_type,
             :p_fix_f, :p_fix_v, :p_vol_f1, :p_vol_f2, :p_vol_f3, :p_vol_bf1, :p_vol_bf23, :p_vol_mono, :p_vol, :alpha,
             :region, :province, :municipality, :offer_url, :valid_from, :valid_to)
         ON DUPLICATE KEY UPDATE
            provider_id = VALUES(provider_id),
            provider_name = VALUES(provider_name),
            offer_name = VALUES(offer_name),
            supply_type = VALUES(supply_type),
            customer_type = VALUES(customer_type),
            offer_type = VALUES(offer_type),
            price_type = VALUES(price_type),
            p_fix_f = VALUES(p_fix_f),
            p_fix_v = VALUES(p_fix_v),
            p_vol_f1 = VALUES(p_vol_f1),
            p_vol_f2 = VALUES(p_vol_f2),
            p_vol_f3 = VALUES(p_vol_f3),
            p_vol_bf1 = VALUES(p_vol_bf1),
            p_vol_bf23 = VALUES(p_vol_bf23),
            p_vol_mono = VALUES(p_vol_mono),
            p_vol = VALUES(p_vol),
            alpha = VALUES(alpha),
            region = VALUES(region),
            province = VALUES(province),
            municipality = VALUES(municipality),
            offer_url = VALUES(offer_url),
            valid_from = VALUES(valid_from),
            valid_to = VALUES(valid_to)'
    );

    $importedTenant = 0;
    foreach ($datasets as $dataset) {
        $rows = downloadCsv($dataset['url']);
        foreach ($rows as $row) {
            $providerName = (string) ($row['denominazione'] ?? '');
            $providerInfo = matchProvider($providers, $providersByNormalized, $providerName);
            if ($providerName === '' || $providerInfo === null) {
                continue;
            }

            $piva = trim((string) ($row['p_iva'] ?? $row['codice_fiscale'] ?? ''));
            if ($piva !== '') {
                $pivaMap[$piva] = [
                    'id' => $providerInfo['id'],
                    'name' => $providerInfo['name'],
                ];
            }

            $insert->execute([
                ':tenant_id' => $tenantId,
                ':provider_id' => $providerInfo['id'],
                ':provider_name' => $providerName,
                ':offer_code' => (string) ($row['cod_offerta'] ?? ''),
                ':offer_name' => (string) ($row['nome_offerta'] ?? ''),
                ':supply_type' => $dataset['supply'],
                ':customer_type' => (string) ($row['tipo_cliente'] ?? ''),
                ':offer_type' => (string) ($row['tipo_offerta'] ?? ''),
                ':price_type' => (string) ($row['tipo_offerta'] ?? ''),
                ':p_fix_f' => toFloat($row['p_fix_f'] ?? null),
                ':p_fix_v' => toFloat($row['p_fix_v'] ?? null),
                ':p_vol_f1' => toFloat($row['p_vol_f1'] ?? null),
                ':p_vol_f2' => toFloat($row['p_vol_f2'] ?? null),
                ':p_vol_f3' => toFloat($row['p_vol_f3'] ?? null),
                ':p_vol_bf1' => toFloat($row['p_vol_bf1'] ?? null),
                ':p_vol_bf23' => toFloat($row['p_vol_bf23'] ?? null),
                ':p_vol_mono' => toFloat($row['p_vol_mono'] ?? null),
                ':p_vol' => toFloat($row['p_vol'] ?? null),
                ':alpha' => toFloat($row['alpha'] ?? null),
                ':region' => (string) ($row['regione'] ?? ''),
                ':province' => (string) ($row['provincia'] ?? ''),
                ':municipality' => (string) ($row['comune'] ?? ''),
                ':offer_url' => (string) ($row['url_offerta'] ?? ''),
                ':valid_from' => toDate($row['data_inizio'] ?? null),
                ':valid_to' => toDate($row['data_fine'] ?? null),
            ]);
            $importedTenant++;
            $imported++;
        }
    }

    if ($urlEml !== null || $urlGml !== null || $urlDml !== null) {
        $xmlSources = array_filter([
            ['url' => $urlEml, 'supply' => 'luce'],
            ['url' => $urlGml, 'supply' => 'gas'],
            ['url' => $urlDml, 'supply' => 'luce_gas'],
        ], static fn (array $item): bool => !empty($item['url']));

        foreach ($xmlSources as $source) {
            $xml = downloadXml($source['url']);
            $namespaces = $xml->getNamespaces(true);
            $ns = $namespaces[''] ?? '';
            if ($ns !== '') {
                $xml->registerXPathNamespace('ns', $ns);
                $offers = $xml->xpath('//ns:offerta');
            } else {
                $offers = $xml->xpath('//offerta');
            }

            if (!is_array($offers)) {
                continue;
            }

            foreach ($offers as $offer) {
                $prefix = $ns !== '' ? 'ns:' : '';
                $piva = xmlValue($offer, $prefix . 'IdentificativiOfferta/' . $prefix . 'PIVA_UTENTE', $ns);
                $code = xmlValue($offer, $prefix . 'IdentificativiOfferta/' . $prefix . 'COD_OFFERTA', $ns);
                if ($piva === '' || $code === '' || !isset($pivaMap[$piva])) {
                    continue;
                }

                $name = xmlValue($offer, $prefix . 'DettaglioOfferta/' . $prefix . 'NOME_OFFERTA', $ns);
                $customerType = xmlValue($offer, $prefix . 'DettaglioOfferta/' . $prefix . 'TIPO_CLIENTE', $ns);
                $offerType = xmlValue($offer, $prefix . 'DettaglioOfferta/' . $prefix . 'TIPO_OFFERTA', $ns);
                $validFrom = toDate(xmlValue($offer, $prefix . 'ValiditaOfferta/' . $prefix . 'DATA_INIZIO', $ns));
                $validTo = toDate(xmlValue($offer, $prefix . 'ValiditaOfferta/' . $prefix . 'DATA_FINE', $ns));
                $offerUrl = xmlValue($offer, $prefix . 'DettaglioOfferta/' . $prefix . 'Contatti/' . $prefix . 'URL_SITO_VENDITORE', $ns);
                $prices = parseMlPrices($offer, $ns, $source['supply']);

                $providerInfo = $pivaMap[$piva];

                $insert->execute([
                    ':tenant_id' => $tenantId,
                    ':provider_id' => $providerInfo['id'],
                    ':provider_name' => $providerInfo['name'],
                    ':offer_code' => $code,
                    ':offer_name' => $name !== '' ? $name : 'Offerta mercato libero',
                    ':supply_type' => $source['supply'],
                    ':customer_type' => $customerType,
                    ':offer_type' => $offerType,
                    ':price_type' => 'mercato_libero',
                    ':p_fix_f' => $prices['p_fix_f'],
                    ':p_fix_v' => null,
                    ':p_vol_f1' => $prices['p_vol_f1'],
                    ':p_vol_f2' => $prices['p_vol_f2'],
                    ':p_vol_f3' => $prices['p_vol_f3'],
                    ':p_vol_bf1' => null,
                    ':p_vol_bf23' => null,
                    ':p_vol_mono' => $prices['p_vol_mono'],
                    ':p_vol' => $prices['p_vol'],
                    ':alpha' => null,
                    ':region' => null,
                    ':province' => null,
                    ':municipality' => null,
                    ':offer_url' => $offerUrl !== '' ? $offerUrl : null,
                    ':valid_from' => $validFrom,
                    ':valid_to' => $validTo,
                ]);
                $importedTenant++;
                $imported++;
            }
        }
    }

    echo 'Tenant #' . $tenantId . ' import completato. Offerte importate: ' . $importedTenant . "\n";
}

echo "Import completato. Offerte importate: {$imported}\n";
