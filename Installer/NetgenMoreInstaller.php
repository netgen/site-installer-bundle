<?php

namespace Netgen\Bundle\MoreInstallerBundle\Installer;

use Symfony\Component\Filesystem\Filesystem;

class NetgenMoreInstaller extends BaseInstaller
{
    /**
     * @var string
     */
    private $installerDataPath;

    /**
     * @var string
     */
    private $storagePath;

    /**
     * Sets the installer data path.
     *
     * @param string $installerDataPath
     */
    public function setInstallerDataPath($installerDataPath)
    {
        $this->installerDataPath = $installerDataPath;
    }

    /**
     * Sets the storage path.
     *
     * @param string $storagePath
     */
    public function setStoragePath($storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /**
     * Handle inserting of schema, schema should ideally be in ISO SQL format.
     *
     * Schema file is created with: mysqldump ngmore --no-data > schema.sql
     */
    public function importSchema()
    {
        $this->importSchemaFile(
            $this->installerDataPath . '/../schema/schema.sql',
            'ezcontentobject'
        );
    }

    /**
     * Handle inserting of sql dump, sql dump should ideally be in ISO SQL format.
     *
     * Data file is created with: mysqldump ngmore --no-create-info --extended-insert=false > data.sql
     */
    public function importData()
    {
        $this->importDataFile(
            $this->installerDataPath . '/data.sql',
            'ezcontentobject'
        );
    }

    /**
     * @deprecated Inactive since 6.1, further info: https://jira.ez.no/browse/EZP-25369
     */
    public function createConfiguration()
    {
    }

    /**
     * Handle optional import of binary files to var folder.
     */
    public function importBinaries()
    {
        $fs = new Filesystem();

        if ($fs->exists($this->storagePath)) {
            $this->output->writeln('<comment>Storage directory <info>' . $this->storagePath . '</info> already exists, skipping creation...</comment>');

            return;
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
