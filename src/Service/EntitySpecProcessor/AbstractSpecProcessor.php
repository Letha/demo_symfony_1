<?php

namespace App\Service\EntitySpecProcessor;

use App\Service\EntityFactory;
use App\Service\EntityValidator;
use Doctrine\ORM\EntityManagerInterface;

class AbstractSpecProcessor
{
    const SPEC_TYPE_CREATE = 1;
    const SPEC_TYPE_UPDATE = 2;

    protected
        $entityManager,
        $entityFactory,
        $entityValidator,

        $allowedCreationFields,
        $mainIdentifyingField = 'id',
        $allowedIdentifyingFieldsSets,
        $propByPropValidationProps,
        
        $specErrors;

    function __construct(
        EntityManagerInterface $entityManager,
        EntityFactory $entityFactory,
        EntityValidator $entityValidator
    ) {
        $this->entityManager = $entityManager;
        $this->entityFactory = $entityFactory;
        $this->entityValidator = $entityValidator;
    }

    public function processSpecs(array $specs, int $specType = self::SPEC_TYPE_CREATE): array
    {
        $this->prepareToProcessOtherSpecs();
        $specErrors = [];

        foreach ($specs as $key => $spec) {
            if (!isset($spec['entityData'])) {
                $specErrors[] = [
                    'entitySpec' => $spec,
                    'errors' => ['"entityData" field must be set.']
                ];
                continue;
            }

            switch ($specType) {
                case self::SPEC_TYPE_CREATE:
                    $processedSpec = $this->processSpecToCreate($spec['entityData']);
                    break;

                case self::SPEC_TYPE_UPDATE:
                    $processedSpec = $this->processSpecToUpdate($spec);
                    break;
            }

            if ($processedSpec === null) {
                $specErrors[] = [
                    'entitySpec' => $spec,
                    'errors' => $this->getSpecErrors()
                ];
                unset($specs[$key]);
            } else {
                $specs[$key] = $processedSpec;
            }
        }

        $this->specErrors = $specErrors;
        return $specs;
    }

    public function getSpecErrors(): array
    {
        return $this->specErrors;
    }

    public function ifAnySpecErrorFounded(): bool
    {
        return (count($this->specErrors) > 0);
    }

    protected function prepareToProcessOtherSpecs(): void
    {
        $this->specErrors = [];
    }

    protected function checkCreationFields(array $creationEntityData): bool
    {
        $unallowedFields = array_diff(array_keys($creationEntityData), $this->allowedCreationFields);
        if (count($unallowedFields) > 0) {
            $this->specErrors[] = 
                'Unallowed creation fields of spec are requested: ' .
                implode(', ', $unallowedFields) . '.';
                return false;
        }
        return true;
    }

    protected function checkIdentifyingFields($identifyingEntityFields): bool
    {
        if (!is_array($identifyingEntityFields)) {
            $this->specErrors[] = '"identifyBy" field must be array.';
            return false;
        }

        foreach ($identifyingEntityFields as $identifyingEntityField) {
            if (!is_string($identifyingEntityField)) {
                $this->specErrors[] = '"identifyBy" field must contain only strings.';
                return false;
            }
        }

        $identifyingFieldsCount = count($identifyingEntityFields);
        if ($identifyingFieldsCount === 0) {
            $this->specErrors[] = 'At least one identifying value must be set.';
            return false;
        }

        $ifItIsAllowedIdFieldsSet = in_array(
            $identifyingEntityFields,
            $this->allowedIdentifyingFieldsSets
        );

        if (!$ifItIsAllowedIdFieldsSet) {
            $this->specErrors[] = 'Unallowed identifying fields of entity are requested.';
            return false;
        }

        return true;
    }

    protected function checkIfIdentifyingFieldsExist(array $spec, array $identifyingFields): bool
    {
        $absentIdFields = array_diff($identifyingFields, array_keys($spec));
        if (count($absentIdFields) > 0) {
            $this->specErrors[] = 'Identifying fields of entity must be present.';
            return false;
        }
        return true;
    }

    protected function checkIdentifyingData(array $identifyingData): bool
    {
        foreach ($identifyingData as $identifyingDataOne) {
            if ($identifyingDataOne === null) {
                $this->specErrors[] = 'Identifying data must not be null.';
                return false;
            }
        }

        $entityRepository = $this->entityManager->getRepository($this->entityClassFullName);
        $entity = $entityRepository->findOneBy($identifyingData);
        if (!$entity) {
            $this->specErrors[] = 'Entity for this spec not found.';
            return false;
        }

        return true;
    }

    protected function splitSpecToIdentifyingAndCreationData(array $spec, array $identifyingFields): array
    {
        foreach ($identifyingFields as $identifyingField) {
            $identifyingData[$identifyingField] = $spec[$identifyingField];
            unset($spec[$identifyingField]);
        }

        return [
            'identifyingData' => $identifyingData,
            'creationData' => $spec,
        ];
    }

    protected function validateSpecForEntity(
        array $spec, string $entityClassFullName
    ): void {
        $entity = $this->entityFactory->createEntity($entityClassFullName, $spec);
        $validationErrorsMessages = 
            $this->entityValidator->getValidationErrorsMessages($entity);

        if (count($validationErrorsMessages) > 0) {
            array_push(
                $this->specErrors, 
                ...$validationErrorsMessages
            );
        }
    }

    protected function validateSpecForEntityPropByProp(
        array $spec, string $entityClassFullName,
        ?array $entityPropsForValidation = null
    ): bool {
        $ifNoValidationError = true;
        if ($entityPropsForValidation === null) {
            $entityPropsForValidation = $this->propByPropValidationProps;
        }

        $entity = $this->entityFactory->createEntity($entityClassFullName);
        foreach ($spec as $specField => $specValue) {
            if (in_array($specField, $entityPropsForValidation, true)) {
                $this->entityFactory->createEntity(
                    $entityClassFullName,
                    ["$specField" => $specValue],
                    $entity
                );

                $entityValidationErrorsMessages = 
                    $this->entityValidator->getValidationErrorsMessages($entity, ["$specField"]);
                if (count($entityValidationErrorsMessages) > 0) {
                    $ifNoValidationError = false;
                    array_push(
                        $this->specErrors, 
                        ...$entityValidationErrorsMessages
                    );
                }
            }
        }

        return $ifNoValidationError;
    }
}