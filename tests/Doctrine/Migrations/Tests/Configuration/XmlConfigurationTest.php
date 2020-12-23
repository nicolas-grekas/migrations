<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Exception\XmlNotValid;
use Doctrine\Migrations\Configuration\XmlConfiguration;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\OutputWriter;

use const DIRECTORY_SEPARATOR;

class XmlConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration(
        string $configFileSuffix = '',
        ?OutputWriter $outputWriter = null,
        ?MigrationFinder $migrationFinder = null
    ): AbstractFileConfiguration {
        $configFile = 'config.xml';
        if ($configFileSuffix !== '') {
            $configFile = 'config_' . $configFileSuffix . '.xml';
        }

        $configFileSuffix = new XmlConfiguration($this->getSqliteConnection(), $outputWriter, $migrationFinder);
        $configFileSuffix->load(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile);

        return $configFileSuffix;
    }

    public function testInvalid(): void
    {
        $this->expectException(XmlNotValid::class);

        $this->loadConfiguration('malformed');
    }
}
