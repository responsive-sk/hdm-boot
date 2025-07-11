<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Services;

use HdmBoot\Modules\Core\Security\Enum\SecurityType;
use HdmBoot\Modules\Core\Security\Exceptions\SecurityException;
use PDO;
use RuntimeException;

/**
 * Security Login Checker.
 *
 * Handles login throttling and security checks.
 * Adapted from the original Security module with simplified implementation.
 */
final class SecurityLoginChecker
{
    private const USER_LOGIN_ATTEMPTS_LIMIT = 1000; // Very high for development
    private const USER_LOGIN_WINDOW_MINUTES = 15;
    private const GLOBAL_LOGIN_ATTEMPTS_LIMIT = 10000; // Very high for development
    private const GLOBAL_LOGIN_WINDOW_MINUTES = 5;

    public function __construct(
        private readonly PDO $pdo
    ) {
        try {
            $this->initializeDatabase();
        } catch (\Exception $e) {
            // Log database initialization error but don't fail
            // This allows the application to continue working even if security table creation fails
        }
    }

    /**
     * Check login security before authentication attempt.
     */
    public function checkLoginSecurity(string $email, string $clientIp): void
    {
        // Note: Login security throttling disabled for development
        // Enable in production with proper configuration

        // Skip throttling in testing environment or for localhost
        if (
            ($_ENV['SECURITY_THROTTLING_DISABLED'] ?? 'false') === 'true' ||
            $clientIp === '127.0.0.1' ||
            $clientIp === '::1'
        ) {
            return;
        }

        // Check global throttling first
        $this->checkGlobalThrottling();

        // Check user-specific throttling
        $this->checkUserThrottling($email, $clientIp);
    }

    /**
     * Record failed login attempt.
     */
    public function recordFailedAttempt(string $email, string $clientIp): void
    {
        // Note: Failed attempt recording disabled for development
        // Enable in production with proper configuration
        if (($_ENV['SECURITY_LOGGING_DISABLED'] ?? 'false') === 'true') {
            return;
        }

        $this->pdo->prepare('
            INSERT INTO security_login_attempts (email, ip_address, success, attempted_at)
            VALUES (?, ?, 0, datetime("now"))
        ')->execute([$email, $clientIp]);
    }

    /**
     * Record successful login attempt.
     */
    public function recordSuccessfulAttempt(string $email, string $clientIp): void
    {
        // Note: Successful attempt recording disabled for development
        // Enable in production with proper configuration
        if (($_ENV['SECURITY_LOGGING_DISABLED'] ?? 'false') === 'true') {
            return;
        }

        $this->pdo->prepare('
            INSERT INTO security_login_attempts (email, ip_address, success, attempted_at)
            VALUES (?, ?, 1, datetime("now"))
        ')->execute([$email, $clientIp]);
    }

    /**
     * Check global login throttling.
     */
    private function checkGlobalThrottling(): void
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as attempts
            FROM security_login_attempts 
            WHERE success = 0 
            AND attempted_at > datetime("now", "-' . self::GLOBAL_LOGIN_WINDOW_MINUTES . ' minutes")
        ');
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $attempts = is_array($result) && isset($result['attempts']) ? $result['attempts'] : 0;
        $attemptsCount = is_numeric($attempts) ? (int) $attempts : 0;

        if ($attemptsCount >= self::GLOBAL_LOGIN_ATTEMPTS_LIMIT) {
            throw new SecurityException(
                'captcha',
                SecurityType::GLOBAL_LOGIN,
                'Global login throttling activated'
            );
        }
    }

    /**
     * Check user-specific login throttling.
     */
    private function checkUserThrottling(string $email, string $clientIp): void
    {
        // Check failed attempts for this email/IP combination
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as attempts,
                   MAX(attempted_at) as last_attempt
            FROM security_login_attempts 
            WHERE (email = ? OR ip_address = ?)
            AND success = 0 
            AND attempted_at > datetime("now", "-' . self::USER_LOGIN_WINDOW_MINUTES . ' minutes")
        ');
        $stmt->execute([$email, $clientIp]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (is_array($result) && isset($result['attempts'], $result['last_attempt'])) {
            $attemptsValue = $result['attempts'];
            $attemptsCount = is_numeric($attemptsValue) ? (int) $attemptsValue : 0;

            if ($attemptsCount >= self::USER_LOGIN_ATTEMPTS_LIMIT) {
                // Calculate remaining delay
                $lastAttemptValue = $result['last_attempt'];
                $lastAttemptString = is_string($lastAttemptValue) ? $lastAttemptValue : '';
                $lastAttempt = new \DateTimeImmutable($lastAttemptString);
                $windowEnd = $lastAttempt->modify('+' . self::USER_LOGIN_WINDOW_MINUTES . ' minutes');
                $now = new \DateTimeImmutable();

                if ($windowEnd > $now) {
                    $remainingSeconds = $windowEnd->getTimestamp() - $now->getTimestamp();

                    throw new SecurityException(
                        $remainingSeconds,
                        SecurityType::USER_LOGIN,
                        'User login throttling activated'
                    );
                }
            }
        }
    }

    /**
     * Get login attempt statistics.
     *
     * @return array<string, mixed>
     */
    public function getLoginStatistics(): array
    {
        // Recent failed attempts (last hour)
        $stmt = $this->pdo->query('
            SELECT COUNT(*) as failed_attempts
            FROM security_login_attempts
            WHERE success = 0
            AND attempted_at > datetime("now", "-1 hour")
        ');
        if ($stmt === false) {
            throw new RuntimeException('Failed to query recent failed attempts');
        }
        $recentFailed = $stmt->fetchColumn();

        // Recent successful attempts (last hour)
        $stmt = $this->pdo->query('
            SELECT COUNT(*) as successful_attempts
            FROM security_login_attempts
            WHERE success = 1
            AND attempted_at > datetime("now", "-1 hour")
        ');
        if ($stmt === false) {
            throw new RuntimeException('Failed to query recent successful attempts');
        }
        $recentSuccessful = $stmt->fetchColumn();

        // Top failed IPs (last 24 hours)
        $stmt = $this->pdo->query('
            SELECT ip_address, COUNT(*) as attempts
            FROM security_login_attempts
            WHERE success = 0
            AND attempted_at > datetime("now", "-24 hours")
            GROUP BY ip_address
            ORDER BY attempts DESC
            LIMIT 10
        ');
        if ($stmt === false) {
            throw new RuntimeException('Failed to query top failed IPs');
        }
        $topFailedIps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'recent_failed_attempts'     => (int) $recentFailed,
            'recent_successful_attempts' => (int) $recentSuccessful,
            'top_failed_ips'             => $topFailedIps,
            'global_throttling_active'   => $recentFailed >= self::GLOBAL_LOGIN_ATTEMPTS_LIMIT,
        ];
    }

    /**
     * Clean old login attempts (for maintenance).
     */
    public function cleanOldAttempts(): int
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM security_login_attempts 
            WHERE attempted_at < datetime("now", "-7 days")
        ');
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Initialize database table.
     */
    private function initializeDatabase(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS security_login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                success BOOLEAN NOT NULL DEFAULT 0,
                attempted_at TEXT NOT NULL,
                user_agent TEXT NULL
            )
        ');

        // Create indexes for performance
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_security_login_email ON security_login_attempts(email)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_security_login_ip ON security_login_attempts(ip_address)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_security_login_attempted_at ON security_login_attempts(attempted_at)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_security_login_success ON security_login_attempts(success)');
    }
}
