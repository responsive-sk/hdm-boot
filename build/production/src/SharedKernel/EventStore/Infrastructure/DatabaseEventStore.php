<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\EventStore\Infrastructure;

use HdmBoot\SharedKernel\CQRS\Events\DomainEventInterface;
use HdmBoot\SharedKernel\EventStore\Contracts\EventStoreInterface;
use HdmBoot\SharedKernel\EventStore\ValueObjects\StoredEvent;
use PDO;

/**
 * Database Event Store Implementation.
 *
 * Stores events in a database table for persistence.
 * Supports transactions and concurrent access.
 */
final class DatabaseEventStore implements EventStoreInterface
{
    private const TABLE_NAME = 'event_store';

    public function __construct(
        private readonly PDO $pdo
    ) {
        $this->ensureTableExists();
    }

    public function store(DomainEventInterface $event): void
    {
        $this->storeMany([$event]);
    }

    public function storeMany(array $events): void
    {
        if (empty($events)) {
            return;
        }

        $this->pdo->beginTransaction();

        try {
            foreach ($events as $event) {
                if (!$event instanceof DomainEventInterface) {
                    throw new \InvalidArgumentException('All events must implement DomainEventInterface');
                }

                $this->storeEvent($event);
            }

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getEventsForAggregate(string $aggregateId, ?string $aggregateType = null): array
    {
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE aggregate_id = :aggregate_id';
        $params = ['aggregate_id' => $aggregateId];

        if ($aggregateType !== null) {
            $sql .= ' AND aggregate_type = :aggregate_type';
            $params['aggregate_type'] = $aggregateType;
        }

        $sql .= ' ORDER BY version ASC';

        return $this->executeQuery($sql, $params);
    }

    public function getEventsFromVersion(string $aggregateId, int $fromVersion, ?string $aggregateType = null): array
    {
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE aggregate_id = :aggregate_id AND version >= :from_version';
        $params = [
            'aggregate_id' => $aggregateId,
            'from_version' => $fromVersion,
        ];

        if ($aggregateType !== null) {
            $sql .= ' AND aggregate_type = :aggregate_type';
            $params['aggregate_type'] = $aggregateType;
        }

        $sql .= ' ORDER BY version ASC';

        return $this->executeQuery($sql, $params);
    }

    public function getEventsByType(string $eventType): array
    {
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE event_type = :event_type ORDER BY stored_at ASC';

        return $this->executeQuery($sql, ['event_type' => $eventType]);
    }

    public function getEventsByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE occurred_at BETWEEN :from AND :to ORDER BY occurred_at ASC';

        return $this->executeQuery($sql, [
            'from' => $from->format('Y-m-d H:i:s'),
            'to'   => $to->format('Y-m-d H:i:s'),
        ]);
    }

    public function getAggregateVersion(string $aggregateId, ?string $aggregateType = null): int
    {
        $sql = 'SELECT MAX(version) as max_version FROM ' . self::TABLE_NAME . ' WHERE aggregate_id = :aggregate_id';
        $params = ['aggregate_id' => $aggregateId];

        if ($aggregateType !== null) {
            $sql .= ' AND aggregate_type = :aggregate_type';
            $params['aggregate_type'] = $aggregateType;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($result) || !isset($result['max_version'])) {
            return 0;
        }

        return is_numeric($result['max_version']) ? (int) $result['max_version'] : 0;
    }

    public function aggregateExists(string $aggregateId, ?string $aggregateType = null): bool
    {
        return $this->getAggregateVersion($aggregateId, $aggregateType) > 0;
    }

    public function getEventCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM ' . self::TABLE_NAME);
        if ($stmt === false) {
            throw new \RuntimeException('Failed to execute count query');
        }

        $result = $stmt->fetchColumn();

        return is_numeric($result) ? (int) $result : 0;
    }

    public function getEventsPaginated(int $offset = 0, int $limit = 100): array
    {
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' ORDER BY stored_at ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row)) {
                /** @var array<string, mixed> $typedRow */
                $typedRow = $row;
                $events[] = StoredEvent::fromArray($typedRow);
            }
        }

        return $events;
    }

    public function clear(): void
    {
        $this->pdo->exec('DELETE FROM ' . self::TABLE_NAME);
    }

    /**
     * Store a single event.
     */
    private function storeEvent(DomainEventInterface $event): void
    {
        // Extract aggregate info from event with safe type casting
        $aggregateIdRaw = method_exists($event, 'getAggregateId')
            ? $event->getAggregateId()
            : 'unknown';
        $aggregateId = is_string($aggregateIdRaw) ? $aggregateIdRaw : 'unknown';

        $aggregateTypeRaw = method_exists($event, 'getAggregateType')
            ? $event->getAggregateType()
            : get_class($event);
        $aggregateType = is_string($aggregateTypeRaw) ? $aggregateTypeRaw : get_class($event);

        // Get next version
        $version = $this->getAggregateVersion($aggregateId, $aggregateType) + 1;

        // Create stored event
        $storedEvent = StoredEvent::fromDomainEvent(
            $event,
            $aggregateId,
            $aggregateType,
            $version
        );

        // Insert into database
        $data = $storedEvent->toArray();
        $sql = 'INSERT INTO ' . self::TABLE_NAME . ' (' . implode(', ', array_keys($data)) . ') VALUES (' .
               implode(', ', array_map(fn ($key) => ":$key", array_keys($data))) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    /**
     * Execute query and return StoredEvent objects.
     *
     * @param array<string, mixed> $params
     *
     * @return StoredEvent[]
     */
    private function executeQuery(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row)) {
                /** @var array<string, mixed> $typedRow */
                $typedRow = $row;
                $events[] = StoredEvent::fromArray($typedRow);
            }
        }

        return $events;
    }

    /**
     * Ensure the event store table exists.
     */
    private function ensureTableExists(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS ' . self::TABLE_NAME . ' (
                id VARCHAR(36) PRIMARY KEY,
                aggregate_id VARCHAR(255) NOT NULL,
                aggregate_type VARCHAR(255) NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                event_data TEXT NOT NULL,
                metadata TEXT NOT NULL,
                version INTEGER NOT NULL,
                occurred_at DATETIME NOT NULL,
                stored_at DATETIME NOT NULL,
                INDEX idx_aggregate (aggregate_id, aggregate_type),
                INDEX idx_event_type (event_type),
                INDEX idx_occurred_at (occurred_at),
                UNIQUE KEY unique_aggregate_version (aggregate_id, aggregate_type, version)
            )
        ';

        $this->pdo->exec($sql);
    }
}
