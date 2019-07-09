<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteInstallerBundle\Installer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class NetgenSiteInstaller extends BaseInstaller
{
    /**
     * @var string
     */
    protected $installerDataPath;

    /**
     * @var string
     */
    protected $storagePath;

    public function setInstallerDataPath(string $installerDataPath): void
    {
        $this->installerDataPath = $installerDataPath;
    }

    public function setStoragePath(string $storagePath): void
    {
        $this->storagePath = $storagePath;
    }

    public function importSchema(): void
    {
        $this->importSchemaFile(
            $this->installerDataPath . '/../schema/schema.sql',
            'ezcontentobject'
        );
    }

    public function importData(): void
    {
        $this->importDataFile(
            $this->installerDataPath . '/data.sql',
            'ezcontentobject'
        );
    }

    public function createConfiguration(): void
    {
    }

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
                $this->output->writeln('<comment>Storage directory <info>' . $this->storagePath . '</info> already exists and is not empty, skipping creation...</comment>');

                return;
            }

            $fs->remove($this->storagePath);
        }

        if (!$fs->exists($this->installerDataPath . '/storage')) {
            return;
        }

        $this->output->writeln('Copying storage directory to <info>' . $this->storagePath . '</info>');

        $fs->mirror(
            $this->installerDataPath . '/storage',
            $this->storagePath
        );
    }
}
