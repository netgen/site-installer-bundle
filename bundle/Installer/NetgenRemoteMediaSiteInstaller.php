<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteInstallerBundle\Installer;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\ContentTypeDraft;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use Throwable;

class NetgenRemoteMediaSiteInstaller extends NetgenSiteInstaller
{
    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    private $contentTypeService;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    private $repository;

    /**
     * @var string[]
     */
    private $fieldTypesToMigrate = [];

    /**
     * @var string[]
     */
    private $excludedFieldIdentifiers = [];

    public function setRepository(Repository $repository): void
    {
        $this->repository = $repository;
        $this->contentTypeService = $repository->getContentTypeService();
    }

    public function setFieldTypesToMigrate(array $fieldTypeIdentifiers): void
    {
        $this->fieldTypesToMigrate = $fieldTypeIdentifiers;
    }

    public function setExcludedFieldIdentifiers(array $excludedFieldIdentifiers): void
    {
        $this->excludedFieldIdentifiers = $excludedFieldIdentifiers;
    }

    public function importSchema(): void
    {
        parent::importSchema();

        $this->importSchemaFile(
            $this->installerDataPath . '/../schema/remote_media_schema.sql',
            'ngremotemedia_field_link',
        );
    }

    public function importData(): void
    {
        parent::importData();

        $this->output->writeln('<comment>Migrating <info>ezimage, ezbinaryfile, ezmedia</info> fields to <info>ngremotemedia</info></comment>');

        $contentTypes = $this->getContentTypes();
        foreach ($contentTypes as $contentType) {
            $fieldsToMigrate = $this->getFieldsToMigrate($contentType);

            $this->repository->beginTransaction();

            try {
                $contentTypeDraft = $this->repository->sudo(
                    static function (Repository $repository) use ($contentType): ContentTypeDraft {
                        return $repository->getContentTypeService()->createContentTypeDraft($contentType);
                    }
                );

                foreach ($fieldsToMigrate as $fieldDefinition) {
                    $this->migrateField($fieldDefinition, $contentTypeDraft);
                }

                $this->repository->sudo(
                    static function (Repository $repository) use ($contentTypeDraft): void {
                        $repository->getContentTypeService()->publishContentTypeDraft($contentTypeDraft);
                    }
                );

                $this->repository->commit();
            } catch (Throwable $e) {
                $this->repository->rollback();

                throw $e;
            }
        }
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentType[]
     */
    private function getContentTypes(): array
    {
        $contentTypeGroups = $this->contentTypeService->loadContentTypeGroups();

        $contentTypes = [];
        foreach ($contentTypeGroups as $contentTypeGroup) {
            $contentTypes = array_merge($contentTypes, $this->contentTypeService->loadContentTypes($contentTypeGroup));
        }

        return $contentTypes;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\ContentType\FieldDefinition[]
     */
    private function getFieldsToMigrate(ContentType $contentType): array
    {
        $fields = $contentType->getFieldDefinitions();
        $fieldsToMigrate = [];
        foreach ($fields as $field) {
            if (
                in_array($field->fieldTypeIdentifier, $this->fieldTypesToMigrate, true)
                && !in_array($field->identifier, $this->excludedFieldIdentifiers, true)
            ) {
                $fieldsToMigrate[] = $field;
            }
        }

        return $fieldsToMigrate;
    }

    private function migrateField(FieldDefinition $fieldDefinition, ContentTypeDraft $contentTypeDraft): void
    {
        $this->repository->sudo(
            static function (Repository $repository) use ($contentTypeDraft, $fieldDefinition): void {
                $repository->getContentTypeService()->removeFieldDefinition(
                    $contentTypeDraft,
                    $fieldDefinition
                );
            }
        );

        $fieldDefinitionCreateStruct = $this->contentTypeService->newFieldDefinitionCreateStruct(
            $fieldDefinition->identifier,
            'ngremotemedia'
        );
        $fieldDefinitionCreateStruct->names = $fieldDefinition->getNames();
        $fieldDefinitionCreateStruct->position = $fieldDefinition->position;

        $this->repository->sudo(
            static function (Repository $repository) use ($contentTypeDraft, $fieldDefinitionCreateStruct): void {
                $repository->getContentTypeService()->addFieldDefinition($contentTypeDraft, $fieldDefinitionCreateStruct);
            }
        );
    }
}
