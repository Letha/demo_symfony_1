<?php

namespace App\Service;

use App\Entity\EntityInterface;
use App\Entity\Product;
use App\Entity\Category;
use App\Service\EntityFactory;
use Doctrine\ORM\EntityManagerInterface;

class AppEntityManager
{
    private
        $entityManager,
        $entityFactory;

    function __construct(
        EntityManagerInterface $entityManager,
        EntityFactory $entityFactory
    ) {
        $this->entityManager = $entityManager;
        $this->entityFactory = $entityFactory;
    }

    public function create(Product $product): void
    {
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function createFromSpec(string $entityClassFullName, array $spec): void
    {
        $this->checkIfClassExists($entityClassFullName);
        $product = $this->entityFactory->createEntity($entityClassFullName, $spec);
        $this->create($product);
    }

    public function createFromSpecs(string $entityClassFullName, array $specs): void
    {
        foreach ($specs as $spec) {
            $this->createFromSpec($entityClassFullName, $spec);
        }
    }

    public function update(): void
    {
        $this->entityManager->flush();
    }

    public function updateFromSpec(array $spec, EntityInterface $entity): void
    {
        $entityClassFullName = get_class($entity);
        $this->entityFactory->createEntity($entityClassFullName, $spec['creationData'], $entity);
        $this->update();
    }

    public function updateFromSpecs(array $specs, string $entityClassFullName): void
    {
        foreach ($specs as $spec) {
            $entityRepository = $this->entityManager->getRepository($entityClassFullName);
            $entity = $entityRepository->findOneBy($spec['identifyingData']);
            $this->updateFromSpec($spec, $entity);
        }
    }

    public function delete(EntityInterface $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    private function checkIfClassExists($entityClassFullName): void
    {
        if(!class_exists($entityClassFullName)) {
            throw new \Exception('Required class does not exists.');
        }
    }
}