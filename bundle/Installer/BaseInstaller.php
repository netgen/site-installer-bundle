<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteInstallerBundle\Installer;

use Ibexa\Bundle\RepositoryInstaller\Installer\DbBasedInstaller;
use Ibexa\Bundle\RepositoryInstaller\Installer\Installer;

abstract class BaseInstaller extends DbBasedInstaller implements Installer
{
    /**
     * Handle inserting of schema, schema should ideally be in ISO SQL format.
     *
     * Schema file is created with: mysqldump ngsite --no-data > schema.sql
     */
    protected function importSchemaFile(string $schemaFile, ?string $controlTableName = null): void
    {
        if ($controlTableName !== null && $this->db->getSchemaManager()->tablesExist([$controlTableName])) {
            $this->output->writeln('<comment>Schema already exists in the database, skipping schema import for file <info>' . $schemaFile . '</info></comment>');

            return;
        }

        $this->runQueriesFromFile($schemaFile);
    }

    /**
     * Handle inserting of sql dump, sql dump should ideally be in ISO SQL format.
     *
     * Data file is created with: mysqldump ngsite --no-create-info --extended-insert=false > data.sql
     */
    protected function importDataFile(string $dataFile, ?string $controlTableName = null): void
    {
        if ($controlTableName !== null) {
            $query = $this->db->createQueryBuilder();
            $query->select('count(*) AS count')
                ->from($controlTableName);

            $data = $query->execute()->fetchAll();

            $contentCount = (int) $data[0]['count'];
            if ($contentCount > 0) {
                $this->output->writeln('<comment>Data already exists in the database, skipping data import for file <info>' . $dataFile . '</info></comment>');

                return;
            }
        }

        $this->runQueriesFromFile($dataFile);
    }
}
