<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Monitoring\Infrastructure\HealthChecks;

use MvaBootstrap\SharedKernel\HealthChecks\Contracts\HealthCheckInterface;
use MvaBootstrap\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Database Health Check.
 *
 * Checks database connectivity and basic functionality.
 */
final class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'database';
    }

    public function check(): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            // Test basic connectivity
            $stmt = $this->pdo->query('SELECT 1 as test');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['test'] !== 1) {
                return HealthCheckResult::unhealthy(
                    $this->getName(),
                    'Database query returned unexpected result',
                    ['expected' => 1, 'actual' => $result['test']],
                    microtime(true) - $startTime
                );
            }

            // Test write capability (if possible)
            $writeTest = $this->testWriteCapability();

            // Get database info
            $dbInfo = $this->getDatabaseInfo();

            $duration = microtime(true) - $startTime;

            // Check if response time is acceptable (< 1 second)
            if ($duration > 1.0) {
                return HealthCheckResult::degraded(
                    $this->getName(),
                    'Database response time is slow',
                    array_merge($dbInfo, [
                        'response_time' => $duration,
                        'write_test'    => $writeTest,
                    ]),
                    $duration
                );
            }

            return HealthCheckResult::healthy(
                $this->getName(),
                'Database is accessible and responsive',
                array_merge($dbInfo, [
                    'response_time' => $duration,
                    'write_test'    => $writeTest,
                ]),
                $duration
            );
        } catch (\Exception $e) {
            $this->logger->error('Database health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return HealthCheckResult::unhealthy(
                $this->getName(),
                'Database connection failed: ' . $e->getMessage(),
                ['error_type' => get_class($e)],
                microtime(true) - $startTime
            );
        }
    }

    public function getTimeout(): int
    {
        return 5; // 5 seconds timeout
    }

    public function isCritical(): bool
    {
        return true; // Database is critical
    }

    public function getCategory(): string
    {
        return 'database';
    }

    public function getTags(): array
    {
        return ['database', 'infrastructure', 'critical'];
    }

    /**
     * Test write capability.
     */
    private function testWriteCapability(): bool
    {
        try {
            // Try to create a temporary table (if permissions allow)
            $this->pdo->exec('CREATE TEMPORARY TABLE health_check_test (id INT)');
            $this->pdo->exec('DROP TEMPORARY TABLE health_check_test');

            return true;
        } catch (\Exception $e) {
            // Write test failed, but this might be due to permissions
            // Don't fail the health check for this
            return false;
        }
    }

    /**
     * Get database information.
     *
     * @return array<string, mixed>
     */
    private function getDatabaseInfo(): array
    {
        try {
            $info = [];

            // Get driver name (always supported)
            try {
                $info['driver'] = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            } catch (\Exception $e) {
                $info['driver'] = 'unknown';
            }

            // Get server version (try, but don't fail if not supported)
            try {
                $info['server_version'] = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            } catch (\Exception $e) {
                $info['server_version'] = 'unknown';
            }

            // Get client version (try, but don't fail if not supported)
            try {
                $info['client_version'] = $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
            } catch (\Exception $e) {
                $info['client_version'] = 'unknown';
            }

            // Connection status is not supported by SQLite, skip it for SQLite
            if ($info['driver'] !== 'sqlite') {
                try {
                    $info['connection_status'] = $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
                } catch (\Exception $e) {
                    $info['connection_status'] = 'unknown';
                }
            }

            // Get driver-specific information
            if ($info['driver'] === 'sqlite') {
                $info = array_merge($info, $this->getSqliteInfo());
            } elseif ($info['driver'] === 'mysql') {
                $info = array_merge($info, $this->getMysqlInfo());
            } elseif ($info['driver'] === 'pgsql') {
                $info = array_merge($info, $this->getPostgresInfo());
            }

            return $info;
        } catch (\Exception $e) {
            return ['error' => 'Could not retrieve database info: ' . $e->getMessage()];
        }
    }

    /**
     * Get SQLite-specific information.
     *
     * @return array<string, mixed>
     */
    private function getSqliteInfo(): array
    {
        try {
            $info = [];

            // Get SQLite version
            $stmt = $this->pdo->query('SELECT sqlite_version() as version');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $info['sqlite_version'] = $result['version'];
            }

            // Get database list
            $stmt = $this->pdo->query('PRAGMA database_list');
            $databases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $info['databases'] = $databases;

            // Get database file size if main database
            foreach ($databases as $db) {
                if ($db['name'] === 'main' && !empty($db['file'])) {
                    if (file_exists($db['file'])) {
                        $info['database_size'] = filesize($db['file']);
                        $info['database_size_mb'] = round(filesize($db['file']) / 1024 / 1024, 2);
                    }
                }
            }

            return $info;
        } catch (\Exception $e) {
            return ['sqlite_error' => $e->getMessage()];
        }
    }

    /**
     * Get MySQL-specific information.
     *
     * @return array<string, mixed>
     */
    private function getMysqlInfo(): array
    {
        try {
            $info = [];

            // Get MySQL variables
            $stmt = $this->pdo->query("SHOW VARIABLES LIKE 'version%'");
            $variables = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $info['mysql_variables'] = $variables;

            return $info;
        } catch (\Exception $e) {
            return ['mysql_error' => $e->getMessage()];
        }
    }

    /**
     * Get PostgreSQL-specific information.
     *
     * @return array<string, mixed>
     */
    private function getPostgresInfo(): array
    {
        try {
            $info = [];

            // Get PostgreSQL version
            $stmt = $this->pdo->query('SELECT version()');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $info['postgres_version'] = $result['version'];
            }

            return $info;
        } catch (\Exception $e) {
            return ['postgres_error' => $e->getMessage()];
        }
    }
}
