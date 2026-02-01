<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

final class UserService
{
    public function __construct(
        private PDO $pdo,
        private ?string $resendApiKey = null,
        private ?string $resendFrom = null,
        private ?string $resendFromName = null,
        private ?string $appName = null
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOperators(?int $tenantId = null): array
    {
        if ($tenantId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT u.id, u.username, u.email, u.fullname, u.created_at, u.updated_at,
                        u.role_id, r.name AS role_name, u.mfa_enabled, u.mfa_enabled_at,
                        u.tenant_id, t.name AS tenant_name
                 FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 LEFT JOIN tenants t ON t.id = u.tenant_id
                 WHERE u.tenant_id = :tenant_id
                 ORDER BY u.fullname ASC, u.username ASC'
            );
            $stmt->execute([':tenant_id' => $tenantId]);
            $rows = $stmt->fetchAll();

            return is_array($rows) ? $rows : [];
        }

        $stmt = $this->pdo->query(
            'SELECT u.id, u.username, u.email, u.fullname, u.created_at, u.updated_at,
                    u.role_id, r.name AS role_name, u.mfa_enabled, u.mfa_enabled_at,
                    u.tenant_id, t.name AS tenant_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN tenants t ON t.id = u.tenant_id
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
            'SELECT u.id, u.username, u.email, u.fullname, u.role_id, u.created_at, u.updated_at,
                    r.name AS role_name, u.mfa_enabled, u.mfa_enabled_at, u.tenant_id, t.name AS tenant_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN tenants t ON t.id = u.tenant_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool,message:string,error?:string}
     */
    public function updateOwnPassword(int $userId, array $input): array
    {
        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la password.',
                'error' => 'Utente non valido.',
            ];
        }

        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmPassword = (string) ($input['new_password_confirmation'] ?? '');

        $errors = [];
        if ($currentPassword === '') {
            $errors[] = 'Inserisci la password attuale.';
        }
        if ($newPassword === '' || strlen($newPassword) < 8) {
            $errors[] = 'La nuova password deve contenere almeno 8 caratteri.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Le nuove password non coincidono.';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la password.',
                'error' => implode(' ', $errors),
            ];
        }

        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || empty($row['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la password.',
                'error' => 'Utente non trovato.',
            ];
        }

        if (!password_verify($currentPassword, (string) $row['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la password.',
                'error' => 'La password attuale non è corretta.',
            ];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hash === false) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la password.',
                'error' => 'Errore durante la generazione della password.',
            ];
        }

        $update = $this->pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $update->execute([
            ':hash' => $hash,
            ':id' => $userId,
        ]);

        return [
            'success' => true,
            'message' => 'Password aggiornata correttamente.',
        ];
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
     * @return array<int, array<string, mixed>>
     */
    public function getRolesForTenant(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM roles WHERE LOWER(name) <> :admin ORDER BY name ASC');
        $stmt->execute([':admin' => 'admin']);
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
        $email = trim((string) ($input['operator_email'] ?? ''));
        $tenantId = isset($input['operator_tenant_id']) ? (int) $input['operator_tenant_id'] : 0;
        $sendCredentials = isset($input['operator_send_credentials']) && (int) $input['operator_send_credentials'] === 1;

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

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email non è valida.';
        }

        if ($sendCredentials && $email === '') {
            $errors[] = 'Indica un\'email valida per inviare le credenziali.';
        }

        $role = $this->findRole($roleId);
        if ($role === null) {
            $errors[] = 'Seleziona un ruolo valido.';
        }

        $tenant = $this->findTenant($tenantId);
        if ($tenant === null) {
            $errors[] = 'Seleziona un tenant valido.';
        }

        $roleName = strtolower((string) ($role['name'] ?? ''));
        if ($roleName === 'admin' && $tenantId !== 1) {
            $errors[] = 'I tenant non possono avere utenti con ruolo admin.';
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

        if ($email !== '') {
            $emailStmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $emailStmt->execute([':email' => $email]);
            if ($emailStmt->fetchColumn()) {
                return [
                    'success' => false,
                    'message' => 'Impossibile creare l\'operatore.',
                    'error' => 'Esiste già un operatore con questa email.',
                ];
            }
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
                'INSERT INTO users (username, email, password_hash, role_id, fullname, tenant_id)
                 VALUES (:username, :email, :hash, :role, :fullname, :tenant_id)'
            );
            $stmt->execute([
                ':username' => $normalizedUsername,
                ':email' => $email !== '' ? $email : null,
                ':hash' => $passwordHash,
                ':role' => $roleId,
                ':fullname' => $fullname,
                ':tenant_id' => $tenantId,
            ]);
        } catch (PDOException $exception) {
            return [
                'success' => false,
                'message' => 'Impossibile creare l\'operatore.',
                'error' => 'Errore database durante la creazione.',
            ];
        }

        $emailSent = false;
        if ($sendCredentials && $email !== '') {
            $emailSent = $this->sendCredentialsEmail($email, $normalizedUsername, $password, $fullname);
        }

        if ($sendCredentials && !$emailSent) {
            $message = 'Operatore creato correttamente. Invio credenziali non riuscito: verifica la configurazione email.';
        } elseif ($emailSent) {
            $message = 'Operatore creato correttamente. Credenziali inviate via email.';
        } else {
            $message = 'Operatore creato correttamente.';
        }

        return [
            'success' => true,
            'message' => $message,
            'email_sent' => $emailSent,
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
        $email = trim((string) ($input['operator_edit_email'] ?? ''));
        $tenantId = isset($input['operator_edit_tenant_id']) ? (int) $input['operator_edit_tenant_id'] : 0;
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

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email non è valida.';
        }

        $tenant = $this->findTenant($tenantId);
        if ($tenant === null) {
            $errors[] = 'Seleziona un tenant valido.';
        }

        $roleName = strtolower((string) ($role['name'] ?? ''));
        if ($roleName === 'admin' && $tenantId !== 1) {
            $errors[] = 'I tenant non possono avere utenti con ruolo admin.';
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

        if ($email !== '') {
            $emailStmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $emailStmt->execute([
                ':email' => $email,
                ':id' => $operatorId,
            ]);
            if ($emailStmt->fetchColumn()) {
                $errors[] = 'Esiste già un operatore con questa email.';
            }
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

        $sql = 'UPDATE users SET fullname = :fullname, username = :username, email = :email, role_id = :role, tenant_id = :tenant_id, updated_at = NOW()';
        $params = [
            ':fullname' => $fullname,
            ':username' => $normalizedUsername,
            ':email' => $email !== '' ? $email : null,
            ':role' => $roleId,
            ':tenant_id' => $tenantId,
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

    /**
     * @return array<string, mixed>|null
     */
    private function findTenant(int $tenantId): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, name FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $tenantId]);
        $tenant = $stmt->fetch();

        return $tenant !== false ? $tenant : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findTenantDetails(int $tenantId): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, name, slug, contact_email, contact_phone
             FROM tenants
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $tenantId]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        return $tenant !== false ? $tenant : null;
    }

    /**
     * @param array<string, mixed> $tenant
     * @return array{success:bool,message:string,error?:string,operator?:array<string,mixed>,password?:string}
     */
    private function createTenantOperator(array $tenant): array
    {
        $tenantId = (int) ($tenant['id'] ?? 0);
        $tenantName = trim((string) ($tenant['name'] ?? ''));
        $tenantSlug = trim((string) ($tenant['slug'] ?? ''));
        $email = trim((string) ($tenant['contact_email'] ?? ''));

        if ($tenantId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Tenant non valido.',
            ];
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Email contatto tenant non valida o mancante.',
            ];
        }

        if ($this->emailExists($email)) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Email contatto già usata da un altro operatore.',
            ];
        }

        $roleId = $this->findRoleIdByName('cassiere');
        if ($roleId === null) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Ruolo cassiere non configurato.',
            ];
        }

        $baseUsername = $tenantSlug !== '' ? $tenantSlug : ('tenant' . $tenantId);
        $username = $this->ensureUniqueUsername($baseUsername);

        $password = $this->generateTempPassword();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Errore durante la generazione della password.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role_id, fullname, tenant_id)
             VALUES (:username, :email, :hash, :role, :fullname, :tenant_id)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':hash' => $hash,
            ':role' => $roleId,
            ':fullname' => $tenantName !== '' ? $tenantName : $username,
            ':tenant_id' => $tenantId,
        ]);

        return [
            'success' => true,
            'message' => 'Operatore tenant creato.',
            'operator' => [
                'id' => (int) $this->pdo->lastInsertId(),
                'username' => $username,
                'fullname' => $tenantName !== '' ? $tenantName : $username,
                'email' => $email,
            ],
            'password' => $password,
        ];
    }

    private function findRoleIdByName(string $roleName): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE LOWER(name) = :name LIMIT 1');
        $stmt->execute([':name' => strtolower($roleName)]);
        $roleId = $stmt->fetchColumn();
        return $roleId !== false ? (int) $roleId : null;
    }

    private function ensureUniqueUsername(string $base): string
    {
        $base = preg_replace('/[^a-z0-9._-]/i', '', strtolower($base)) ?: 'tenant';
        $candidate = $base;
        $suffix = 1;
        while ($this->usernameExists($candidate)) {
            $candidate = $base . $suffix;
            $suffix++;
        }
        return $candidate;
    }

    private function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() !== false;
    }

    private function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * @return array{success:bool,message:string,error?:string,email_sent?:bool}
     */
    public function resendTenantCredentials(int $tenantId): array
    {
        if ($tenantId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Tenant non valido.',
            ];
        }

        $tenantRow = $this->findTenantDetails($tenantId);
        if ($tenantRow === null) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Tenant non trovato.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.username, u.fullname, u.email, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.tenant_id = :tenant_id AND LOWER(r.name) <> "admin"
             ORDER BY u.created_at ASC
             LIMIT 1'
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        $operator = $stmt->fetch(PDO::FETCH_ASSOC);
        $password = null;
        $skipUpdate = false;

        if ($operator === false) {
            $created = $this->createTenantOperator($tenantRow);
            if (!$created['success']) {
                return $created;
            }
            $operator = $created['operator'] ?? null;
            $password = $created['password'] ?? null;
            $skipUpdate = $password !== null;
        }

        if (!is_array($operator)) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Nessun operatore associato al tenant.',
            ];
        }

        $email = trim((string) ($operator['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Impossibile inviare le credenziali.',
                'error' => 'Email operatore non valida o mancante.',
            ];
        }

        if (!$skipUpdate) {
            $password = $this->generateTempPassword();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($hash === false) {
                return [
                    'success' => false,
                    'message' => 'Impossibile inviare le credenziali.',
                    'error' => 'Errore durante la generazione della password.',
                ];
            }

            $update = $this->pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
            $update->execute([
                ':hash' => $hash,
                ':id' => (int) ($operator['id'] ?? 0),
            ]);
        }

        $sent = $this->sendResetCredentialsEmail(
            $email,
            (string) ($operator['username'] ?? ''),
            (string) ($password ?? ''),
            (string) ($operator['fullname'] ?? '')
        );

        if (!$sent) {
            $this->logTenantCredentialEmailFailure($tenantId, $email);
        }

        return [
            'success' => $sent,
            'message' => $sent
                ? 'Credenziali inviate correttamente.'
                : 'Password aggiornata, ma invio email non riuscito.',
            'email_sent' => $sent,
        ];
    }

    private function sendCredentialsEmail(string $recipient, string $username, string $password, string $fullname): bool
    {
        $recipient = trim($recipient);
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $appName = $this->appName !== null && $this->appName !== '' ? $this->appName : 'Coresuite Express';
        $loginUrl = $this->buildLoginUrl();
        $displayName = $fullname !== '' ? $fullname : $username;

        $subject = '[' . $appName . '] Credenziali di accesso';
        $textBody = "Ciao {$displayName},\n\n";
        $textBody .= "Il tuo accesso è stato creato.\n\n";
        $textBody .= "Username: {$username}\n";
        $textBody .= "Password: {$password}\n\n";
        $textBody .= "Accedi qui: {$loginUrl}\n\n";
        $textBody .= "Ti consigliamo di cambiare la password al primo accesso.\n";

        $brandColor = '#1f2937';
        $accentColor = '#2563eb';
        $htmlBody = '<!doctype html>';
        $htmlBody .= '<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        $htmlBody .= '<title>' . htmlspecialchars($appName) . '</title>';
        $htmlBody .= '</head><body style="margin:0;padding:0;background:#f4f6fb;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#111827;">';
        $htmlBody .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:32px 12px;">';
        $htmlBody .= '<tr><td align="center">';
        $htmlBody .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,0.08);">';
        $htmlBody .= '<tr><td style="padding:24px 28px;background:' . $brandColor . ';color:#ffffff;">';
        $htmlBody .= '<h1 style="margin:0;font-size:20px;font-weight:600;">' . htmlspecialchars($appName) . '</h1>';
        $htmlBody .= '<p style="margin:6px 0 0;font-size:14px;opacity:0.85;">Credenziali di accesso</p>';
        $htmlBody .= '</td></tr>';
        $htmlBody .= '<tr><td style="padding:28px;">';
        $htmlBody .= '<p style="margin:0 0 12px;font-size:16px;">Ciao <strong>' . htmlspecialchars($displayName) . '</strong>,</p>';
        $htmlBody .= '<p style="margin:0 0 20px;color:#4b5563;">Il tuo accesso è stato creato. Trovi qui le credenziali personali:</p>';
        $htmlBody .= '<div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;margin-bottom:22px;">';
        $htmlBody .= '<p style="margin:0 0 8px;font-size:14px;color:#6b7280;">Username</p>';
        $htmlBody .= '<p style="margin:0 0 14px;font-size:16px;font-weight:600;color:#111827;">' . htmlspecialchars($username) . '</p>';
        $htmlBody .= '<p style="margin:0 0 8px;font-size:14px;color:#6b7280;">Password</p>';
        $htmlBody .= '<p style="margin:0;font-size:16px;font-weight:600;color:#111827;">' . htmlspecialchars($password) . '</p>';
        $htmlBody .= '</div>';
        $htmlBody .= '<a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;background:' . $accentColor . ';color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:600;">Accedi al gestionale</a>';
        $htmlBody .= '<p style="margin:20px 0 0;color:#6b7280;font-size:13px;">Ti consigliamo di cambiare la password al primo accesso.</p>';
        $htmlBody .= '</td></tr>';
        $htmlBody .= '<tr><td style="padding:20px 28px;border-top:1px solid #e5e7eb;background:#fafafa;color:#9ca3af;font-size:12px;">';
        $htmlBody .= 'Se non riconosci questa email, contatta subito l\'amministratore.';
        $htmlBody .= '</td></tr>';
        $htmlBody .= '</table>';
        $htmlBody .= '</td></tr></table>';
        $htmlBody .= '</body></html>';

        $sent = false;
        if ($this->resendApiKey !== null && $this->sendEmailViaResend($recipient, $subject, $textBody, $htmlBody)) {
            $sent = true;
        }

        if (!$sent) {
            $fromEmail = $this->getFromEmail();
            $fromName = $this->getFromDisplayName();
            $formattedFrom = $this->formatEmailAddress($fromEmail, $fromName);
            $headers = [
                'From: ' . $formattedFrom,
                'Reply-To: ' . $fromEmail,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ];
            $sent = @mail($recipient, $subject, $textBody, implode("\r\n", $headers));
        }

        return $sent;
    }

    private function sendResetCredentialsEmail(string $recipient, string $username, string $password, string $fullname): bool
    {
        $recipient = trim($recipient);
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $appName = $this->appName !== null && $this->appName !== '' ? $this->appName : 'Coresuite Express';
        $loginUrl = $this->buildLoginUrl();
        $displayName = $fullname !== '' ? $fullname : $username;

        $subject = '[' . $appName . '] Reset credenziali di accesso';
        $textBody = "Ciao {$displayName},\n\n";
        $textBody .= "La tua password è stata reimpostata.\n\n";
        $textBody .= "Username: {$username}\n";
        $textBody .= "Password temporanea: {$password}\n\n";
        $textBody .= "Accedi qui: {$loginUrl}\n\n";
        $textBody .= "Ti consigliamo di cambiare la password al primo accesso.\n";

        $brandColor = '#1f2937';
        $accentColor = '#2563eb';
        $htmlBody = '<!doctype html>';
        $htmlBody .= '<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        $htmlBody .= '<title>' . htmlspecialchars($appName) . '</title>';
        $htmlBody .= '</head><body style="margin:0;padding:0;background:#f4f6fb;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#111827;">';
        $htmlBody .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:32px 12px;">';
        $htmlBody .= '<tr><td align="center">';
        $htmlBody .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,0.08);">';
        $htmlBody .= '<tr><td style="padding:24px 28px;background:' . $brandColor . ';color:#ffffff;">';
        $htmlBody .= '<h1 style="margin:0;font-size:20px;font-weight:600;">' . htmlspecialchars($appName) . '</h1>';
        $htmlBody .= '<p style="margin:6px 0 0;font-size:14px;opacity:0.85;">Reset credenziali</p>';
        $htmlBody .= '</td></tr>';
        $htmlBody .= '<tr><td style="padding:28px;">';
        $htmlBody .= '<p style="margin:0 0 12px;font-size:16px;">Ciao <strong>' . htmlspecialchars($displayName) . '</strong>,</p>';
        $htmlBody .= '<p style="margin:0 0 20px;color:#4b5563;">La tua password è stata reimpostata. Trovi qui le credenziali aggiornate:</p>';
        $htmlBody .= '<div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;margin-bottom:22px;">';
        $htmlBody .= '<p style="margin:0 0 8px;font-size:14px;color:#6b7280;">Username</p>';
        $htmlBody .= '<p style="margin:0 0 14px;font-size:16px;font-weight:600;color:#111827;">' . htmlspecialchars($username) . '</p>';
        $htmlBody .= '<p style="margin:0 0 8px;font-size:14px;color:#6b7280;">Password temporanea</p>';
        $htmlBody .= '<p style="margin:0;font-size:16px;font-weight:600;color:#111827;">' . htmlspecialchars($password) . '</p>';
        $htmlBody .= '</div>';
        $htmlBody .= '<a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;background:' . $accentColor . ';color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:600;">Accedi al gestionale</a>';
        $htmlBody .= '<p style="margin:20px 0 0;color:#6b7280;font-size:13px;">Ti consigliamo di cambiare la password al primo accesso.</p>';
        $htmlBody .= '</td></tr>';
        $htmlBody .= '<tr><td style="padding:20px 28px;border-top:1px solid #e5e7eb;background:#fafafa;color:#9ca3af;font-size:12px;">';
        $htmlBody .= 'Se non riconosci questa email, contatta subito l\'amministratore.';
        $htmlBody .= '</td></tr>';
        $htmlBody .= '</table>';
        $htmlBody .= '</td></tr></table>';
        $htmlBody .= '</body></html>';

        $sent = false;
        if ($this->resendApiKey !== null && $this->sendEmailViaResend($recipient, $subject, $textBody, $htmlBody)) {
            $sent = true;
        }

        if (!$sent) {
            $fromEmail = $this->getFromEmail();
            $fromName = $this->getFromDisplayName();
            $formattedFrom = $this->formatEmailAddress($fromEmail, $fromName);
            $headers = [
                'From: ' . $formattedFrom,
                'Reply-To: ' . $fromEmail,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ];
            $sent = @mail($recipient, $subject, $textBody, implode("\r\n", $headers));
        }

        return $sent;
    }

    private function generateTempPassword(int $length = 12): string
    {
        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    private function logTenantCredentialEmailFailure(int $tenantId, string $email): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf(
            "[%s] Tenant %d - invio credenziali fallito a %s\n",
            date('Y-m-d H:i:s'),
            $tenantId,
            $email
        );
        @file_put_contents($dir . '/tenant_emails.log', $line, FILE_APPEND);
    }

    private function buildLoginUrl(): string
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $httpsValue = $_SERVER['HTTPS'] ?? null;
            $scheme = (is_string($httpsValue) && strtolower((string) $httpsValue) !== 'off' && $httpsValue !== '') ? 'https' : 'http';
            return $scheme . '://' . $_SERVER['HTTP_HOST'] . '/public/index.php?page=login';
        }
        return 'index.php?page=login';
    }

    private function sendEmailViaResend(string $recipient, string $subject, string $textBody, ?string $htmlBody = null): bool
    {
        if (!function_exists('curl_init') || $this->resendApiKey === null) {
            return false;
        }

        $fromEmail = $this->getFromEmail();
        $fromName = $this->getFromDisplayName();
        $from = $this->formatEmailAddress($fromEmail, $fromName);
        $payloadData = [
            'from' => $from,
            'to' => [$recipient],
            'subject' => $subject,
            'text' => $textBody,
        ];
        $payloadData['reply_to'] = [$fromEmail];
        if ($htmlBody !== null) {
            $payloadData['html'] = $htmlBody;
        }
        $payload = json_encode($payloadData);
        if ($payload === false) {
            return false;
        }

        $ch = curl_init('https://api.resend.com/emails');
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->resendApiKey,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);

        return $status >= 200 && $status < 300;
    }

    private function getFromEmail(): string
    {
        $email = trim((string) ($this->resendFrom ?? ''));
        return $email !== '' ? $email : 'alerts@coresuite.test';
    }

    private function getFromDisplayName(): ?string
    {
        $name = trim((string) ($this->resendFromName ?? ''));
        if ($name !== '') {
            return str_replace(["\r", "\n"], '', $name);
        }
        $fallback = trim((string) ($this->appName ?? ''));
        if ($fallback !== '') {
            return str_replace(["\r", "\n"], '', $fallback);
        }
        return null;
    }

    private function formatEmailAddress(string $email, ?string $name = null): string
    {
        $cleanEmail = trim(str_replace(["\r", "\n"], '', $email));
        if ($name === null || trim($name) === '') {
            return $cleanEmail;
        }
        $cleanName = trim(str_replace(["\r", "\n"], '', $name));
        if ($cleanName === '') {
            return $cleanEmail;
        }
        $needsQuotes = strpbrk($cleanName, ',;"') !== false;
        $encodedName = $needsQuotes ? '"' . addcslashes($cleanName, '"\\') . '"' : $cleanName;
        return $encodedName . ' <' . $cleanEmail . '>';
    }
}
