<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use PDO;
use PDOException;

final class LicenseService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listLicenses(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, code, label, max_users, is_active, expires_at, created_at, updated_at
             FROM licenses
             ORDER BY created_at DESC'
        );

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActivations(int $licenseId): array
    {
        if ($licenseId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, license_id, activated_at, revoked_at, notes
             FROM license_activations
             WHERE license_id = :id
             ORDER BY activated_at DESC'
        );
        $stmt->execute([':id' => $licenseId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array{success:bool,message:string,error?:string,code?:string}
     */
    public function createLicense(?string $label, int $maxUsers, ?string $expiresAt): array
    {
        if ($maxUsers <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile creare la licenza.',
                'error' => 'Numero massimo utenti non valido.',
            ];
        }

        $expiresValue = null;
        if ($expiresAt !== null && trim($expiresAt) !== '') {
            $expiresAt = trim($expiresAt);
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $expiresAt);
            if ($date === false) {
                $timestamp = strtotime($expiresAt);
                if ($timestamp === false) {
                    return [
                        'success' => false,
                        'message' => 'Impossibile creare la licenza.',
                        'error' => 'Data di scadenza non valida.',
                    ];
                }
                $date = (new DateTimeImmutable())->setTimestamp($timestamp);
            }
            $expiresValue = $date->format('Y-m-d');
        }

        $labelValue = $label !== null ? trim($label) : null;
        if ($labelValue === '') {
            $labelValue = null;
        }

        $attempts = 0;
        while ($attempts < 5) {
            $attempts++;
            $code = $this->generateCode();
            try {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO licenses (code, label, max_users, is_active, expires_at)
                     VALUES (:code, :label, :max_users, 1, :expires_at)'
                );
                $stmt->execute([
                    ':code' => $code,
                    ':label' => $labelValue,
                    ':max_users' => $maxUsers,
                    ':expires_at' => $expiresValue,
                ]);

                return [
                    'success' => true,
                    'message' => 'Licenza creata correttamente.',
                    'code' => $code,
                ];
            } catch (PDOException $exception) {
                $errorInfo = $exception->errorInfo;
                $codeNumber = is_array($errorInfo) ? ($errorInfo[1] ?? null) : null;
                if ((int) $codeNumber === 1062) {
                    continue;
                }
                return [
                    'success' => false,
                    'message' => 'Impossibile creare la licenza.',
                    'error' => 'Errore database durante la creazione.',
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Impossibile creare la licenza.',
            'error' => 'Impossibile generare un codice univoco.',
        ];
    }

    /**
     * @return array{success:bool,message:string,error?:string}
     */
    public function toggleLicense(int $licenseId, bool $enabled): array
    {
        if ($licenseId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile aggiornare la licenza.',
                'error' => 'Licenza non valida.',
            ];
        }

        $stmt = $this->pdo->prepare('UPDATE licenses SET is_active = :active WHERE id = :id');
        $stmt->execute([
            ':active' => $enabled ? 1 : 0,
            ':id' => $licenseId,
        ]);

        return [
            'success' => true,
            'message' => $enabled ? 'Licenza attivata.' : 'Licenza disattivata.',
        ];
    }

    /**
     * @return array{success:bool,message:string,error?:string}
     */
    public function revokeActivation(int $activationId): array
    {
        if ($activationId <= 0) {
            return [
                'success' => false,
                'message' => 'Impossibile revocare l\'attivazione.',
                'error' => 'Attivazione non valida.',
            ];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE license_activations SET revoked_at = NOW() WHERE id = :id AND revoked_at IS NULL'
        );
        $stmt->execute([':id' => $activationId]);

        return [
            'success' => true,
            'message' => 'Attivazione revocata.',
        ];
    }

    private function generateCode(): string
    {
        return 'LIC-' . strtoupper(bin2hex(random_bytes(6)));
    }
}
