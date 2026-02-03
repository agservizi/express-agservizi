<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

final class UserService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOperators(): array
    {
        $stmt = $this->pdo->query(
            'SELECT u.id, u.username, u.fullname, u.created_at, u.updated_at, u.role_id, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             ORDER BY u.fullname ASC, u.username ASC'
        );

        $rows = $stmt ? $stmt->fetchAll() : [];

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findUser(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.username, u.email, u.fullname, u.role_id, u.created_at, u.updated_at, r.name AS role_name,
                    u.tenant_id, t.name AS tenant_name, t.slug AS tenant_slug, t.contact_email AS tenant_email,
                    t.contact_phone AS tenant_phone, t.vat_number AS tenant_vat_number,
                    t.company_country AS tenant_company_country, t.company_name AS tenant_company_name,
                    t.company_address AS tenant_company_address
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN tenants t ON t.id = u.tenant_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        $tenantId = (int) ($user['tenant_id'] ?? 0);
        $tenantEmail = trim((string) ($user['tenant_email'] ?? ''));
        $tenantPhone = trim((string) ($user['tenant_phone'] ?? ''));
        $tenantVatNumber = trim((string) ($user['tenant_vat_number'] ?? ''));
        $tenantCompanyCountry = trim((string) ($user['tenant_company_country'] ?? ''));
        $tenantCompanyName = trim((string) ($user['tenant_company_name'] ?? ''));
        $tenantCompanyAddress = trim((string) ($user['tenant_company_address'] ?? ''));

        if ($tenantId > 0 && (
            $tenantEmail === ''
            || $tenantPhone === ''
            || $tenantVatNumber === ''
            || $tenantCompanyCountry === ''
            || $tenantCompanyName === ''
            || $tenantCompanyAddress === ''
        )) {
            try {
                $fallbackStmt = $this->pdo->prepare(
                    'SELECT tenant_email, tenant_phone, vat_number, company_country, company_name, company_address
                     FROM tenant_checkout_requests
                     WHERE tenant_id = :tenant_id AND status IN ("paid", "processing")
                     ORDER BY paid_at DESC, updated_at DESC
                     LIMIT 1'
                );
                $fallbackStmt->execute([':tenant_id' => $tenantId]);
                $fallback = $fallbackStmt->fetch(PDO::FETCH_ASSOC);

                if ($fallback !== false) {
                    $fallbackEmail = trim((string) ($fallback['tenant_email'] ?? ''));
                    $fallbackPhone = trim((string) ($fallback['tenant_phone'] ?? ''));
                    $fallbackVatNumber = trim((string) ($fallback['vat_number'] ?? ''));
                    $fallbackCompanyCountry = trim((string) ($fallback['company_country'] ?? ''));
                    $fallbackCompanyName = trim((string) ($fallback['company_name'] ?? ''));
                    $fallbackCompanyAddress = trim((string) ($fallback['company_address'] ?? ''));
                    $updateFields = [];
                    $updateParams = [':id' => $tenantId];

                    if ($tenantEmail === '' && $fallbackEmail !== '') {
                        $user['tenant_email'] = $fallbackEmail;
                        $updateFields[] = 'contact_email = :contact_email';
                        $updateParams[':contact_email'] = $fallbackEmail;
                    }
                    if ($tenantPhone === '' && $fallbackPhone !== '') {
                        $user['tenant_phone'] = $fallbackPhone;
                        $updateFields[] = 'contact_phone = :contact_phone';
                        $updateParams[':contact_phone'] = $fallbackPhone;
                    }
                    if ($tenantVatNumber === '' && $fallbackVatNumber !== '') {
                        $user['tenant_vat_number'] = $fallbackVatNumber;
                        $updateFields[] = 'vat_number = :vat_number';
                        $updateParams[':vat_number'] = $fallbackVatNumber;
                    }
                    if ($tenantCompanyCountry === '' && $fallbackCompanyCountry !== '') {
                        $user['tenant_company_country'] = $fallbackCompanyCountry;
                        $updateFields[] = 'company_country = :company_country';
                        $updateParams[':company_country'] = $fallbackCompanyCountry;
                    }
                    if ($tenantCompanyName === '' && $fallbackCompanyName !== '') {
                        $user['tenant_company_name'] = $fallbackCompanyName;
                        $updateFields[] = 'company_name = :company_name';
                        $updateParams[':company_name'] = $fallbackCompanyName;
                    }
                    if ($tenantCompanyAddress === '' && $fallbackCompanyAddress !== '') {
                        $user['tenant_company_address'] = $fallbackCompanyAddress;
                        $updateFields[] = 'company_address = :company_address';
                        $updateParams[':company_address'] = $fallbackCompanyAddress;
                    }

                    if ($updateFields !== []) {
                        $this->pdo->prepare(
                            'UPDATE tenants SET ' . implode(', ', $updateFields) . ' WHERE id = :id'
                        )->execute($updateParams);
                    }
                }
            } catch (PDOException) {
                // Ignora fallback se la tabella non esiste o la query fallisce.
            }
        }

        return $user;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRoles(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM roles ORDER BY name ASC');
        $rows = $stmt ? $stmt->fetchAll() : [];

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, error?:string}
     */
    public function createOperator(array $input): array
    {
        $fullname = trim((string) ($input['operator_fullname'] ?? ''));
        $username = trim((string) ($input['operator_username'] ?? ''));
        $password = (string) ($input['operator_password'] ?? '');
        $confirm = (string) ($input['operator_password_confirmation'] ?? '');
        $roleId = isset($input['operator_role']) ? (int) $input['operator_role'] : 0;

        $errors = [];

        if ($fullname === '') {
            $errors[] = 'Inserisci un nome completo.';
        }

        if ($username === '') {
            $errors[] = 'Inserisci un nome utente.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,}$/', $username)) {
            $errors[] = 'Il nome utente deve avere almeno 3 caratteri e può contenere lettere, numeri, punto, trattino e trattino basso.';
        }

        if ($password === '' || strlen($password) < 8) {
            $errors[] = 'La password deve contenere almeno 8 caratteri.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Le password non coincidono.';
        }

        $role = $this->findRole($roleId);
        if ($role === null) {
            $errors[] = 'Seleziona un ruolo valido.';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile creare l\'operatore.',
                'error' => implode(' ', $errors),
            ];
        }

        $normalizedUsername = function_exists('mb_strtolower') ? mb_strtolower($username) : strtolower($username);

        $existsStmt = $this->pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $existsStmt->execute([':username' => $normalizedUsername]);
        if ($existsStmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Impossibile creare l\'operatore.',
                'error' => 'Esiste già un operatore con questo nome utente.',
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            return [
                'success' => false,
                'message' => 'Impossibile creare l\'operatore.',
                'error' => 'Non è stato possibile generare la password.',
            ];
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, password_hash, role_id, fullname) VALUES (:username, :hash, :role, :fullname)'
            );
            $stmt->execute([
                ':username' => $normalizedUsername,
                ':hash' => $passwordHash,
                ':role' => $roleId,
                ':fullname' => $fullname,
            ]);
        } catch (PDOException $exception) {
            return [
                'success' => false,
                'message' => 'Impossibile creare l\'operatore.',
                'error' => 'Errore database durante la creazione.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Operatore creato correttamente.',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, error?:string}
     */
    public function updateOperator(int $operatorId, array $input): array
    {
        if ($operatorId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare l\'operatore.',
                'error' => 'Identificativo operatore non valido.',
            ];
        }

        $operator = $this->findUser($operatorId);
        if ($operator === null) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare l\'operatore.',
                'error' => 'Operatore non trovato.',
            ];
        }

        $fullname = trim((string) ($input['operator_edit_fullname'] ?? ''));
        $username = trim((string) ($input['operator_edit_username'] ?? ''));
        $roleId = isset($input['operator_edit_role']) ? (int) $input['operator_edit_role'] : 0;
        $password = (string) ($input['operator_edit_password'] ?? '');
        $confirm = (string) ($input['operator_edit_password_confirmation'] ?? '');

        $errors = [];

        if ($fullname === '') {
            $errors[] = 'Inserisci il nome completo.';
        }

        if ($username === '') {
            $errors[] = 'Inserisci un nome utente valido.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,}$/', $username)) {
            $errors[] = 'Il nome utente deve avere almeno 3 caratteri alfanumerici (o ., -, _).';
        }

        $role = $this->findRole($roleId);
        if ($role === null) {
            $errors[] = 'Seleziona un ruolo valido.';
        }

        $normalizedUsername = function_exists('mb_strtolower') ? mb_strtolower($username) : strtolower($username);

        $existsStmt = $this->pdo->prepare('SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1');
        $existsStmt->execute([
            ':username' => $normalizedUsername,
            ':id' => $operatorId,
        ]);
        if ($existsStmt->fetchColumn()) {
            $errors[] = 'Esiste già un operatore con questo nome utente.';
        }

        $passwordHash = null;
        if ($password !== '' || $confirm !== '') {
            if ($password === '' || strlen($password) < 8) {
                $errors[] = 'La nuova password deve contenere almeno 8 caratteri.';
            }
            if ($password !== $confirm) {
                $errors[] = 'Le nuove password non coincidono.';
            }
            if ($errors === []) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if ($passwordHash === false) {
                    $errors[] = 'Errore durante la generazione della password.';
                }
            }
        }

        $isOperatorAdmin = strtolower((string) ($operator['role_name'] ?? '')) === 'admin';
        $targetRoleName = strtolower((string) ($role['name'] ?? ''));
        if ($isOperatorAdmin && $targetRoleName !== 'admin') {
            if ($this->countAdmins() <= 1) {
                $errors[] = 'Non è possibile modificare il ruolo dell\'ultimo amministratore attivo.';
            }
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare l\'operatore.',
                'error' => implode(' ', $errors),
            ];
        }

        $sql = 'UPDATE users SET fullname = :fullname, username = :username, role_id = :role, updated_at = NOW()';
        $params = [
            ':fullname' => $fullname,
            ':username' => $normalizedUsername,
            ':role' => $roleId,
            ':id' => $operatorId,
        ];
        if ($passwordHash !== null) {
            $sql .= ', password_hash = :hash';
            $params[':hash'] = $passwordHash;
        }
        $sql .= ' WHERE id = :id';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $exception) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare l\'operatore.',
                'error' => 'Errore database durante l\'aggiornamento.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Operatore aggiornato correttamente.',
        ];
    }

    /**
     * @return array{success:bool, message:string, error?:string}
     */
    public function deleteOperator(int $operatorId, int $actingUserId): array
    {
        if ($operatorId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile eliminare l\'operatore.',
                'error' => 'Identificativo operatore non valido.',
            ];
        }

        if ($operatorId === $actingUserId) {
            return [
                'success' => false,
                'message' => 'Impossibile eliminare l\'operatore.',
                'error' => 'Non puoi eliminare il tuo stesso account.',
            ];
        }

        $operator = $this->findUser($operatorId);
        if ($operator === null) {
            return [
                'success' => false,
                'message' => 'Impossibile eliminare l\'operatore.',
                'error' => 'Operatore non trovato.',
            ];
        }

        $isAdmin = strtolower((string) ($operator['role_name'] ?? '')) === 'admin';
        if ($isAdmin && $this->countAdmins() <= 1) {
            return [
                'success' => false,
                'message' => 'Impossibile eliminare l\'operatore.',
                'error' => 'Non è possibile eliminare l\'ultimo amministratore attivo.',
            ];
        }

        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $operatorId]);

        return [
            'success' => true,
            'message' => 'Operatore eliminato correttamente.',
        ];
    }

    private function countAdmins(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*)
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE LOWER(r.name) = 'admin'"
        );

        return (int) ($stmt?->fetchColumn() ?? 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findRole(int $roleId): ?array
    {
        if ($roleId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, name FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $roleId]);
        $role = $stmt->fetch();

        return $role !== false ? $role : null;
    }
}
