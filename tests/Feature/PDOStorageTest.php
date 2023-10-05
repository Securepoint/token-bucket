<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Tests\Feature;

use InvalidArgumentException;
use LengthException;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Securepoint\TokenBucket\Storage\PDOStorage;
use Securepoint\TokenBucket\Storage\Storage;
use Securepoint\TokenBucket\Storage\StorageException;

/**
 * Tests for PDOStorage.
 *
 * If you want to run vendor specific PDO tests you should provide these
 * environment variables:
 *
 * - MYSQL_DSN, MYSQL_USER
 * - PGSQL_DSN, PGSQL_USER
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 * @see PDOStorage
 */
class PDOStorageTest extends TestCase
{
    /**
     * @var Storage[] The tested storages;
     */
    private array $storages = [];

    protected function tearDown(): void
    {
        foreach ($this->storages as $storage) {
            $storage->remove();
        }
    }

    /**
     * Provides the PDO.
     *
     * @return PDO[][] The PDOs.
     */
    public static function providePDO()
    {
        $cases = [[new PDO('sqlite::memory:')]];
        if (getenv('MYSQL_DSN')) {
            $pdo = new PDO(getenv('MYSQL_DSN'), getenv('MYSQL_USER'));
            $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
            $cases[] = [$pdo];
        }
        if (getenv('PGSQL_DSN')) {
            $pdo = new PDO(getenv('PGSQL_DSN'), getenv('PGSQL_USER'));
            $cases[] = [$pdo];
        }
        foreach ($cases as $case) {
            $case[0]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $cases;
    }

    /**
     * Tests instantiation with a too long name should fail.
     */
    public function testTooLongNameFails()
    {
        $this->expectException(LengthException::class);
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        new PDOStorage(str_repeat(' ', 129), $pdo);
    }

    /**
     * Tests instantiation with a long name should not fail.
     */
    public function testLongName()
    {
        $this->expectNotToPerformAssertions();
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        new PDOStorage(str_repeat(' ', 128), $pdo);
    }

    /**
     * Tests instantiation with PDO in wrong error mode should fail.
     *
     * @param int $errorMode The invalid error mode.
     */
    #[DataProvider('provideTestInvalidErrorMode')]
    public function testInvalidErrorMode($errorMode)
    {
        $this->expectException(InvalidArgumentException::class);
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, $errorMode);
        new PDOStorage('test', $pdo);
    }

    /**
     * Provides test cases for testInvalidErrorMode()
     *
     * @return int[][] Invalid error modes.
     */
    public static function provideTestInvalidErrorMode()
    {
        return [[PDO::ERRMODE_SILENT], [PDO::ERRMODE_WARNING]];
    }

    /**
     * Tests instantiation with PDO in valid error mode.
     */
    public function testValidErrorMode()
    {
        $this->expectNotToPerformAssertions();
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        new PDOStorage('test', $pdo);
    }

    /**
     * Tests bootstrap() adds a row to an existing table.
     *
     * @param PDO $pdo The PDO.
     */
    #[DataProvider('providePDO')]
    public function testBootstrapAddsRow(PDO $pdo)
    {
        $storageA = new PDOStorage('A', $pdo);
        $storageA->bootstrap(1);
        $this->storages[] = $storageA;

        $storageB = new PDOStorage('B', $pdo);
        $storageB->bootstrap(2);
        $this->storages[] = $storageB;

        $this->assertEquals(1, $storageA->getMicrotime());
        $this->assertEquals(2, $storageB->getMicrotime());
    }

    /**
     * Tests bootstrap() would add a row to an existing table, but fails.
     *
     * @param PDO $pdo The PDO.
     */
    #[DataProvider('providePDO')]
    public function testBootstrapFailsForExistingRow(PDO $pdo)
    {
        $this->expectException(StorageException::class);
        $storageA = new PDOStorage('A', $pdo);
        $storageA->bootstrap(0);
        $this->storages[] = $storageA;

        $storageA2 = new PDOStorage('A', $pdo);
        $storageA2->bootstrap(0);
    }

    /**
     * Tests remove() removes only one row.
     *
     * @param PDO $pdo The PDO.
     */
    #[DataProvider('providePDO')]
    public function testRemoveOneRow(PDO $pdo)
    {
        $storageA = new PDOStorage('A', $pdo);
        $storageA->bootstrap(0);
        $this->storages[] = $storageA;

        $storageB = new PDOStorage('B', $pdo);
        $storageB->bootstrap(0);
        $storageB->remove();

        $this->assertTrue($storageA->isBootstrapped());
        $this->assertFalse($storageB->isBootstrapped());
    }

    /**
     * Tests remove() removes the table after the last row.
     *
     * @param PDO $pdo The PDO.
     */
    #[DataProvider('providePDO')]
    public function testRemoveTable(PDO $pdo)
    {
        $storage = new PDOStorage('test', $pdo);
        $storage->bootstrap(0);
        $storage->remove();
        $this->assertFalse($storage->isBootstrapped());
    }
}
