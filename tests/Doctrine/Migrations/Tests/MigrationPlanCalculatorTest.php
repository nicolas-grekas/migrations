<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\MigrationPlanCalculator;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MigrationPlanCalculatorTest extends TestCase
{
    /** @var MigrationRepository|MockObject */
    private $migrationRepository;

    /** @var MigrationPlanCalculator */
    private $migrationPlanCalculator;

    public function testGetMigrationsToExecuteUp(): void
    {
        $version1 = $this->createMock(Version::class);
        $version1->expects(self::any())
            ->method('getVersion')
            ->willReturn('01');

        $version2 = $this->createMock(Version::class);
        $version2->expects(self::any())
            ->method('getVersion')
            ->willReturn('02');

        $version3 = $this->createMock(Version::class);
        $version3->expects(self::any())
            ->method('getVersion')
            ->willReturn('03');

        $version4 = $this->createMock(Version::class);
        $version4->expects(self::any())
            ->method('getVersion')
            ->willReturn('04');

        $this->migrationRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn([
                '01' => $version1,
                '02' => $version2,
                '03' => $version3,
                '04' => $version4,
            ]);

        $this->migrationRepository->expects(self::once())
            ->method('getMigratedVersions')
            ->willReturn([
                '02',
                '03',
            ]);

        $expected = [
            '01' => $version1,
            '04' => $version4,
        ];

        $migrationsToExecute = $this->migrationPlanCalculator->getMigrationsToExecute(
            Direction::UP,
            '04'
        );

        self::assertSame($expected, $migrationsToExecute);
    }

    public function testGetMigrationsToExecuteDown(): void
    {
        $version1 = $this->createMock(Version::class);
        $version1->expects(self::any())
            ->method('getVersion')
            ->willReturn('01');

        $version2 = $this->createMock(Version::class);
        $version2->expects(self::any())
            ->method('getVersion')
            ->willReturn('02');

        $version3 = $this->createMock(Version::class);
        $version3->expects(self::any())
            ->method('getVersion')
            ->willReturn('03');

        $version4 = $this->createMock(Version::class);
        $version4->expects(self::any())
            ->method('getVersion')
            ->willReturn('04');

        $this->migrationRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn([
                '01' => $version1,
                '02' => $version2,
                '03' => $version3,
                '04' => $version4,
            ]);

        $this->migrationRepository->expects(self::once())
            ->method('getMigratedVersions')
            ->willReturn([
                '02',
                '03',
            ]);

        $expected = [
            '03' => $version3,
            '02' => $version2,
        ];

        $migrationsToExecute = $this->migrationPlanCalculator->getMigrationsToExecute(
            Direction::DOWN,
            '01'
        );

        self::assertSame($expected, $migrationsToExecute);
    }

    protected function setUp(): void
    {
        $this->migrationRepository = $this->createMock(MigrationRepository::class);

        $this->migrationPlanCalculator = new MigrationPlanCalculator($this->migrationRepository);
    }
}
