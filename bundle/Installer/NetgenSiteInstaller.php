<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteInstallerBundle\Installer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function file_exists;
use function sprintf;

class NetgenSiteInstaller extends BaseInstaller
{
    private string $installerDataPath;

    private string $storagePath;

    /**
     * @var array<string[]>
     */
    private array $additionalSchemaFiles = [];

    public function setInstallerDataPath(string $installerDataPath): void
    {
        $this->installerDataPath = $installerDataPath;
    }

    public function setStoragePath(string $storagePath): void
    {
        $this->storagePath = $storagePath;
    }

    public function addSchemaFile(string $schemaFile, string $controlTable): void
    {
        $this->additionalSchemaFiles[] = [$schemaFile, $controlTable];
    }

    public function importSchema(): void
    {
        $this->importSchemaFile(
            $this->installerDataPath . '/../schema/schema.sql',
            'ezcontentobject',
        );

        foreach ($this->additionalSchemaFiles as $additionalSchemaFile) {
            if (file_exists($additionalSchemaFile[0])) {
                $this->importSchemaFile(
                    $additionalSchemaFile[0],
                    $additionalSchemaFile[1],
                );
            }
        }
    }

    public function importData(): void
    {
        $this->importDataFile(
            $this->installerDataPath . '/data.sql',
            'ezcontentobject',
        );
    }

    public function createConfiguration(): void {}

    public function importBinaries(): void
    {
        $fs = new Filesystem();

        if ($fs->exists($this->storagePath)) {
            $finder = new Finder();
            $finder
                ->followLinks()
                ->ignoreVCS(false)
                ->ignoreDotFiles(false)
                ->ignoreUnreadableDirs(false)
                ->in($this->storagePath);

            if ($finder->count() > 0) {
                $this->output->writeln(
                    sprintf(
                        '<comment>Storage directory <info>%s</info> already exists and is not empty, skipping creation...</comment>',
                        $this->storagePath,
                    ),
                );

                return;
            }

            $fs->remove($this->storagePath);
        }

        if (!$fs->exists($this->installerDataPath . '/storage')) {
            return;
        }

        $this->output->writeln(
            sprintf(
                'Copying storage directory to <info>%s</info>',
                $this->storagePath,
            ),
        );

        $fs->mirror(
            $this->installerDataPath . '/storage',
            $this->storagePath,
        );
    }
}
