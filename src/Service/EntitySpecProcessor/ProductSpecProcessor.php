<?php

namespace App\Service\EntitySpecProcessor;

use App\Entity\Category;
use App\Entity\Product;

class ProductSpecProcessor extends AbstractSpecProcessor
{
    protected
        $entityClassFullName = Product::class,

        $allowedCreationFields = [
            'title', 'price', 'eId',
            'categoriesEIds',
        ],
        $allowedIdentifyingFieldsSets = [
            ['id'],
            ['eId'],
        ],
        $propByPropValidationProps = [
            'id', 'title', 'price', 'eId',
        ];

    public function processSpecToCreate(array $spec): ?array
    {
        $this->prepareToProcessOtherSpecs();

        if (
            !$this->checkCreationFields($spec) ||
            !$this->processSpecForCategoriesEIds($spec)
        ) {
            return null;
        }

        $this->validateSpecForEntity($spec, Product::class);
        if ($this->ifAnySpecErrorFounded()) {
            return null;
        } else {
            return $spec;
        }
    }

    public function processSpecToUpdate(array $spec): ?array
    {
        $this->prepareToProcessOtherSpecs();

        if (!isset($spec['identifyBy'])) {
            $identifyingFields = [$this->mainIdentifyingField];
        } else {
            if (!$this->checkIdentifyingFields($spec['identifyBy'])) {
                return null;
            }
            $identifyingFields = $spec['identifyBy'];
            unset($spec['identifyBy']);
        }

        $spec = $spec['entityData'];

        $ifIdentifyingFieldsExist =
            $this->checkIfIdentifyingFieldsExist($spec, $identifyingFields);
        if (!$ifIdentifyingFieldsExist) {
            return null;
        }
        $splittedSpec =
            $this->splitSpecToIdentifyingAndCreationData($spec, $identifyingFields);
        unset($spec);

        if (
            !$this->checkIdentifyingData($splittedSpec['identifyingData']) ||
            !$this->checkCreationFields($splittedSpec['creationData']) ||
            !$this->processSpecForCategoriesEIds($splittedSpec['creationData'])
        ) {
            return null;
        }
        $this->validateSpecForEntityPropByProp(
            $splittedSpec['creationData'] + $splittedSpec['identifyingData'],
            Product::class
        );

        if ($this->ifAnySpecErrorFounded()) {
            return null;
        } else {
            return $splittedSpec;
        }
    }

    private function processSpecForCategoriesEIds(array &$creationProductData): bool
    {
        if (isset($creationProductData['categoriesEIds'])) {
            if (!is_array($creationProductData['categoriesEIds'])) {
                $this->specErrors[] = '"categoriesEIds" field must be an array.';
                return false;
            }
            $categoryRepository = $this->entityManager->getRepository(Category::class);

            foreach ($creationProductData['categoriesEIds'] as $categoryEId) {
                $categoryPartialSpec = ['eId' => $categoryEId];
                $ifNoValidationErrorOfCategory =
                    $this->validateSpecForEntityPropByProp(
                        $categoryPartialSpec,
                        Category::class,
                        ['eId']
                    );

                if ($ifNoValidationErrorOfCategory) {
                    $categoryOfEId = $categoryRepository->findOneBy(['eId' => $categoryEId]);
                    if (!$categoryOfEId) {
                        $this->specErrors[] = "No category with eId $categoryEId.";
                        return false;
                    }
                    $creationProductData['categories'][] = $categoryOfEId;

                } else {
                    return false;
                }
            }

            unset($creationProductData['categoriesEIds']);
        }
        return true;
    }
}