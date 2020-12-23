<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\Setup;
use UnexpectedValueException;

/**
 * Tests the OrmSchemaProvider using a real entity manager.
 */
class OrmSchemaProviderTest extends MigrationTestCase
{
    /** @var  Connection */
    private $conn;

    /** @var  Configuration */
    private $config;

    /** @var  EntityManagerInterface */
    private $entityManager;

    /** @var  OrmSchemaProvider */
    private $ormProvider;

    public function testCreateSchemaFetchesMetadataFromEntityManager(): void
    {
        $schema = $this->ormProvider->createSchema();

        self::assertSame(
            [
                'public.a',
                'public.b',
                'public.c',
            ],
            $schema->getTableNames()
        );

        foreach (['a', 'b', 'c'] as $expectedTable) {
            $table = $schema->getTable($expectedTable);
            self::assertTrue($table->hasColumn('id'));
        }
    }

    public function testEntityManagerWithoutMetadataCausesError(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->config->setMetadataDriverImpl(new XmlDriver([]));

        $this->ormProvider->createSchema();
    }

    protected function setUp(): void
    {
        $this->config = Setup::createXMLMetadataConfiguration([__DIR__ . '/_files'], true);
        $this->config->setClassMetadataFactoryName(ClassMetadataFactory::class);

        $this->conn          = $this->getSqliteConnection();
        $this->entityManager = EntityManager::create($this->conn, $this->config);
        $this->ormProvider   = new OrmSchemaProvider($this->entityManager);
    }
}
