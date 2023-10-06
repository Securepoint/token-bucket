<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage;

use Exception;
use InvalidArgumentException;
use LengthException;
use malkusch\lock\mutex\Mutex;
use malkusch\lock\mutex\TransactionalMutex;
use PDO;
use PDOException;
use Securepoint\TokenBucket\Storage\Scope\GlobalScope;

/**
 * PDO based storage which can be shared over a common DBS.
 *
 * This storage is in the global scope.
 *
 * @license WTFPL
 */
final class PDOStorage implements Storage, GlobalScope
{
    /**
     * @var PDO The pdo.
     */
    private readonly PDO $pdo;

    /**
     * @var string The shared name of the token bucket.
     */
    private readonly string $name;

    /**
     * @var TransactionalMutex The mutex.
     */
    private readonly TransactionalMutex $mutex;

    /**
     * Sets the PDO and the bucket's name for the shared storage.
     *
     * The name should be the same for all token buckets which share the same
     * token storage.
     *
     * The transaction isolation level should avoid lost updates, i.e. it should
     * be at least Repeatable Read.
     *
     * @param string $name The name of the token bucket.
     * @param PDO    $pdo  The PDO.
     */
    public function __construct(string $name, PDO $pdo)
    {
        if (strlen($name) > 128) {
            throw new LengthException('The name should not be longer than 128 characters.');
        }
        if ($pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            throw new InvalidArgumentException('The pdo must have PDO::ERRMODE_EXCEPTION set.');
        }
        $this->pdo = $pdo;
        $this->name = $name;
        $this->mutex = new TransactionalMutex($pdo);
    }

    /**
     * @throws StorageException
     */
    public function bootstrap(float $microtime): void
    {
        try {
            try {
                $this->onErrorRollback(function () {
                    $options = $this->forVendor([
                        'mysql' => 'ENGINE=InnoDB CHARSET=utf8',
                    ]);
                    $this->pdo->exec(
                        "CREATE TABLE token_bucket (
                            name      VARCHAR(128)     PRIMARY KEY,
                            microtime DOUBLE PRECISION NOT NULL
                         ) {$options};"
                    );
                });
            } catch (PDOException) {
                /*
                 * This exception is ignored to provide a portable way
                 * to create a table only if it doesn't exist yet.
                 */
            }

            $insert = $this->pdo->prepare('INSERT INTO token_bucket (name, microtime) VALUES (?, ?)');
            $insert->execute([$this->name, $microtime]);
            if ($insert->rowCount() !== 1) {
                throw new StorageException("Failed to insert token bucket into storage '{$this->name}'");
            }
        } catch (PDOException $e) {
            throw new StorageException("Failed to bootstrap storage '{$this->name}'", 0, $e);
        }
    }

    /**
     * @throws StorageException
     */
    public function isBootstrapped(): bool
    {
        try {
            /** @var bool $result */
            $result = $this->onErrorRollback(
                fn () => (bool) $this->querySingleValue('SELECT 1 FROM token_bucket WHERE name=?', [$this->name])
            );;
            return $result;
        } catch (StorageException) {
            // This seems to be a portable way to determine if the table exists or not.
            return false;
        } catch (PDOException $e) {
            throw new StorageException("Can't check bootstrapped state", 0, $e);
        }
    }

    /**
     * @throws StorageException
     */
    public function remove(): void
    {
        try {
            $delete = $this->pdo->prepare('DELETE FROM token_bucket WHERE name = ?');
            $delete->execute([$this->name]);

            $count = $this->querySingleValue('SELECT count(*) FROM token_bucket');
            if ($count === 0) {
                $this->pdo->exec('DROP TABLE token_bucket');
            }
        } catch (PDOException $e) {
            throw new StorageException('Failed to remove the storage.', 0, $e);
        }
    }

    /**
     * @throws StorageException
     */
    public function setMicrotime(float $microtime): void
    {
        try {
            $update = $this->pdo->prepare('UPDATE token_bucket SET microtime = ? WHERE name = ?');
            $update->execute([$microtime, $this->name]);
        } catch (PDOException $e) {
            throw new StorageException("Failed to write to storage '{$this->name}'.", 0, $e);
        }
    }

    /**
     * @throws StorageException
     */
    public function getMicrotime(): float
    {
        $forUpdate = $this->forVendor([
            'sqlite' => '',
        ], 'FOR UPDATE');
        return (float) $this->querySingleValue(
            "SELECT microtime from token_bucket WHERE name = ? {$forUpdate}",
            [$this->name]
        );
    }

    public function getMutex(): Mutex
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged(): void
    {
    }

    /**
     * Returns a vendor specific dialect value.
     *
     * @param string[] $map     The vendor dialect map.
     * @param string   $default The default value, which is empty per default.
     *
     * @return string The vendor specific value.
     */
    private function forVendor(array $map, string $default = ''): string
    {
        $vendor = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $map[$vendor] ?? $default;
    }

    /**
     * Returns one value from a query.
     *
     * @param string $sql The SQL query.
     * @param array<int,string> $parameters The optional query parameters.
     *
     * @return int|float|string|null The value.
     * @throws StorageException
     */
    private function querySingleValue(string $sql, array $parameters = []): int|float|string|null
    {
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);

            $value = $statement->fetchColumn();

            $statement->closeCursor();
            if ($value === false) {
                throw new StorageException('The query returned no result.');
            }
            return $value;
        } catch (PDOException $e) {
            throw new StorageException('The query failed.', 0, $e);
        }
    }

    /**
     * Rollback to an implicit savepoint.
     * @throws StorageException
     * @throws PDOException
     */
    private function onErrorRollback(callable $code): mixed
    {
        if (! $this->pdo->inTransaction()) {
            return call_user_func($code);
        }

        $this->pdo->exec('SAVEPOINT onErrorRollback');
        try {
            $result = call_user_func($code);
        } catch (Exception $e) {
            $this->pdo->exec('ROLLBACK TO SAVEPOINT onErrorRollback');
            throw new StorageException('Error while executing callable', 0, $e);
        }
        $this->pdo->exec('RELEASE SAVEPOINT onErrorRollback');
        return $result;
    }
}
