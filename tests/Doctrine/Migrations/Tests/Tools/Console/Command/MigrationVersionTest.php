<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\Version1Test;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

use function file_exists;
use function mkdir;
use function sys_get_temp_dir;

use const DIRECTORY_SEPARATOR;

class MigrationVersionTest extends MigrationTestCase
{
    /** @var VersionCommand|MockObject */
    private $command;

    /** @var Configuration */
    private $configuration;

    protected function setUp(): void
    {
        $this->command = $this
            ->getMockBuilder(VersionCommand::class)
            ->setConstructorArgs(['migrations:version'])
            ->setMethods(['getMigrationConfiguration'])
            ->getMock();

        $this->configuration = new Configuration($this->getSqliteConnection());
        $this->configuration->setMigrationsNamespace('DoctrineMigrations');
        $migrationsDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'doctrine-migrations';
        if (! file_exists($migrationsDirectory)) {
            mkdir($migrationsDirectory);
        }

        $this->configuration->setMigrationsDirectory($migrationsDirectory);

        $this->command
            ->expects(self::once())
            ->method('getMigrationConfiguration')
            ->will(self::returnValue($this->configuration));
    }

    /**
     * Test "--add --range-to --range-from" options on migrate only versions in interval.
     */
    public function testAddRangeOption(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->registerMigration('1234', Version1Test::class);
        $this->configuration->registerMigration('1235', Version1Test::class);
        $this->configuration->registerMigration('1239', Version1Test::class);
        $this->configuration->registerMigration('1240', Version1Test::class);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'        => true,
                '--range-from' => '1234',
                '--range-to'   => '1239',
            ],
            ['interactive' => false]
        );

        self::assertFalse($this->configuration->getVersion('1233')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1234')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1235')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1239')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add --range-from" options without "--range-to".
     */
    public function testAddRangeWithoutRangeToOption(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options --range-to and --range-from should be used together.');

        $commandTester->execute(
            [
                '--add'        => true,
                '--range-from' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--add --range-to" options without "--range-from".
     */
    public function testAddRangeWithoutRangeFromOption(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options --range-to and --range-from should be used together.');

        $commandTester->execute(
            [
                '--add'      => true,
                '--range-to' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--add --all --range-to" options.
     */
    public function testAddAllOptionsWithRangeTo(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options --all and --range-to/--range-from both used. You should use only one of them.');

        $commandTester->execute(
            [
                '--add'      => true,
                '--all'      => true,
                '--range-to' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--add --all --range-from" options.
     */
    public function testAddAllOptionsWithRangeFrom(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options --all and --range-to/--range-from both used. You should use only one of them.');

        $commandTester->execute(
            [
                '--add'      => true,
                '--all'      => true,
                '--range-from' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--delete --range-to --range-from" options on migrate down only versions in interval.
     */
    public function testDeleteRangeOption(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->registerMigration('1234', Version1Test::class);
        $this->configuration->registerMigration('1235', Version1Test::class);
        $this->configuration->registerMigration('1239', Version1Test::class);
        $this->configuration->registerMigration('1240', Version1Test::class);

        $this->configuration->getVersion('1233')->markMigrated();
        $this->configuration->getVersion('1234')->markMigrated();
        $this->configuration->getVersion('1239')->markMigrated();
        $this->configuration->getVersion('1240')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--delete'     => true,
                '--range-from' => '1234',
                '--range-to'   => '1239',
            ],
            ['interactive' => false]
        );

        self::assertTrue($this->configuration->getVersion('1233')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1234')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1235')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1239')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add --all" options on migrate all versions.
     */
    public function testAddAllOption(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->registerMigration('1234', Version1Test::class);
        $this->configuration->registerMigration('1235', Version1Test::class);
        $this->configuration->registerMigration('1239', Version1Test::class);
        $this->configuration->registerMigration('1240', Version1Test::class);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add' => true,
                '--all' => true,
            ],
            ['interactive' => false]
        );

        self::assertTrue($this->configuration->getVersion('1233')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1234')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1235')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1239')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--delete --all" options on migrate down all versions.
     */
    public function testDeleteAllOption(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->registerMigration('1234', Version1Test::class);
        $this->configuration->registerMigration('1235', Version1Test::class);
        $this->configuration->registerMigration('1239', Version1Test::class);
        $this->configuration->registerMigration('1240', Version1Test::class);

        $this->configuration->getVersion('1233')->markMigrated();
        $this->configuration->getVersion('1234')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--delete' => true,
                '--all'    => true,
            ],
            ['interactive' => false]
        );

        self::assertFalse($this->configuration->getVersion('1233')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1234')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1235')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1239')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add" option on migrate one version.
     */
    public function testAddOption(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->registerMigration('1234', Version1Test::class);
        $this->configuration->registerMigration('1235', Version1Test::class);

        $this->configuration->getVersion('1233')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--add'   => true,
                'version' => '1234',
            ],
            ['interactive' => false]
        );

        self::assertTrue($this->configuration->getVersion('1233')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1234')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1235')->isMigrated());
    }

    /**
     * Test "--delete" options on migrate down one version.
     */
    public function testDeleteOption(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->registerMigration('1234', Version1Test::class);
        $this->configuration->registerMigration('1235', Version1Test::class);

        $this->configuration->getVersion('1234')->markMigrated();

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                '--delete' => true,
                'version'  => '1234',
            ],
            ['interactive' => false]
        );

        self::assertFalse($this->configuration->getVersion('1233')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1234')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1235')->isMigrated());
    }

    /**
     * Test "--add" option on migrate already migrated version.
     */
    public function testAddOptionIfVersionAlreadyMigrated(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->getVersion('1233')->markMigrated();

        $commandTester = new CommandTester($this->command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The version "1233" already exists in the version table.');

        $commandTester->execute(
            [
                '--add'   => true,
                'version' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--delete" option on not migrated version.
     */
    public function testDeleteOptionIfVersionNotMigrated(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);

        $commandTester = new CommandTester($this->command);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The version "1233" does not exist in the version table.');

        $commandTester->execute(
            [
                '--delete' => true,
                'version'  => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--delete" option on migrated version without existing version file.
     */
    public function testDeleteOptionIfVersionFileDoesNotExist(): void
    {
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->getVersion('1233')->markMigrated();

        $this->configuration->getDependencyFactory()->getMigrationRepository()->clearVersions();

        $this->command->setHelperSet(new HelperSet([new QuestionHelper()]));

        $commandTester = new CommandTester($this->command);

        $commandTester->execute(
            [
                '--delete' => true,
                'version'  => '1233',
            ],
            ['interactive' => false]
        );

        self::assertStringContainsString('1233 deleted from the version table.', $commandTester->getDisplay());
    }
}
