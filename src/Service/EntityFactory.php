<?php

namespace App\Service;

use App\Entity\EntityInterface;
use App\Entity\Category;
use App\Entity\Product;

class EntityFactory
{
    public function createEntity(
        string $entityClassFullName,
        array $spec = [], 
        ?EntityInterface $entity = null
    ): EntityInterface {
        $entityClassLastName = $this->getClassLastName($entityClassFullName);
        $entityFactoryMethodName = 'create' . ucfirst($entityClassLastName);

        $entity = call_user_func_array(
            [$this, $entityFactoryMethodName],
            [$spec, $entity]
        );
        if ($entity === false) {
            throw new \Exception('Unexpected behavior.');
        }

        return $entity;
    }

    private function createProduct(
        array $spec = [], ?Product $entity = null
    ): Product {
        if ($entity === null) {
            $entity = new Product();
        }
        
        if (isset($spec['id'])) {
            $entity->setId($spec['id']);    
        }
        if (isset($spec['title'])) {
            $entity->setTitle($spec['title']);    
        }
        if (isset($spec['price'])) {
            $entity->setPrice($spec['price']);    
        }
        if (isset($spec['eId'])) {
            $entity->setEId($spec['eId']);    
        }

        if (isset($spec['categories'])) {
            foreach ($spec['categories'] as $category) {
                $entity->addCategory($category);
            }
        }
        return $entity;
    }

    private function createCategory(
        array $spec = [], ?Category $entity = null
    ): Category {
        if ($entity === null) {
            $entity = new Category();
        }
        
        if (isset($spec['title'])) {
            $entity->setTitle($spec['title']);    
        }
        if (isset($spec['eId'])) {
            $entity->setEId($spec['eId']);    
        }

        return $entity;
    }

    private function getClassLastName(string $classFullName): string
    {
        $classFullNameExploded = explode('\\', $classFullName);
        if ($classFullNameExploded === false) {
            throw new \Exception('Unexpected behavior.');
        }

        $classLastName = end($classFullNameExploded);
        if ($classLastName === false) {
            throw new \Exception('Unexpected behavior.');
        }

        return $classLastName;
    }
}