<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\AppEntityManager;
use App\Service\EntitySpecProcessor\ProductSpecProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/api")
 */
class ProductManagerController extends AbstractJSONAPIController
{
    /**
     * @Route("/products", name="api_products_get", methods={"GET"})
     */
    public function getAllProducts(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        if (count($products) === 0) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }
        return $this->json(['products' => $products]);
    }

    /**
     * @Route("/product/{id}", name="api_product_get", methods={"GET"})
     */
    public function getProduct(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }
        return $this->json(['product' => $product]);
    }

    /**
     * @Route("/product", name="api_product_create", methods={"POST"})
     */
    public function createProduct(
        Request $request, SerializerInterface $serializer, 
        ProductSpecProcessor $productSpecProcessor, AppEntityManager $appEntityManager
    ): Response {
        $requestContent = $request->getContent();
        if (!$this->isJson($requestContent) || $requestContent === '') {
            return $this->json(
                ['errors' => ['Request body must be of JSON type.']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $requestContent = $serializer->decode($requestContent, 'json');

        if (!isset($requestContent['entityData'])) {
            return $this->json(
                ['errors' => ['"entityData" field must be set.']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $processedProductSpec =
            $productSpecProcessor->processSpecToCreate($requestContent['entityData']);
        if ($processedProductSpec === null) {
            return $this->json(
                ['errors' => $productSpecProcessor->getSpecErrors()],
                Response::HTTP_BAD_REQUEST
            );
        } else {
            $appEntityManager->createFromSpec(Product::class, $processedProductSpec);
            return $this->json([]);
        }
    }

    /**
     * @Route("/products", name="api_products_create", methods={"POST"})
     */
    public function createProducts(
        Request $request, SerializerInterface $serializer,
        ProductSpecProcessor $productSpecProcessor, AppEntityManager $appEntityManager
    ): Response {
        $requestContent = $request->getContent();
        if (!$this->isJson($requestContent) || $requestContent === '') {
            return $this->json(
                ['errors' => ['Request body must be of JSON type.']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $requestContent = $serializer->decode($requestContent, 'json');

        if (
            !isset($requestContent['entitiesSpecs']) ||
            !is_array($requestContent['entitiesSpecs'])
        ) {
            return $this->json(
                ['errors' => ['"entitiesSpecs" field must be set and be an array.']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $processedProductSpecs = $productSpecProcessor->processSpecs(
            $requestContent['entitiesSpecs'], ProductSpecProcessor::SPEC_TYPE_CREATE
        );
        $appEntityManager->createFromSpecs(Product::class, $processedProductSpecs);
        
        if ($productSpecProcessor->ifAnySpecErrorFounded()) {
            return $this->json([
                'errors' => $productSpecProcessor->getSpecErrors()
            ]);
        } else {
            return $this->json([]);
        }
    }

    /**
     * @Route("/product/{id}", name="api_product_update", methods={"PUT"})
     * @Route("/product", name="api_product_update_specifying_identification", methods={"PUT"})
     */
    public function update(
        ?int $id = null, Request $request, 
        EntityManagerInterface $entityManager, SerializerInterface $serializer,
        ProductSpecProcessor $productSpecProcessor, AppEntityManager $appEntityManager
    ): Response {
        $requestContent = $request->getContent();
        if (!$this->isJson($requestContent)) {
            return $this->json(
                ['errors' => ['Request body must be of JSON type.']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $requestContent = $serializer->decode($requestContent, 'json');

        if (!isset($requestContent['entityData'])) {
            return $this->json(
                ['errors' => ['"entityData" field must be set.']],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($id !== null) {
            $requestContent['entityData']['id'] = $id;
            if (isset($requestContent['identifyBy'])) {
                unset($requestContent['identifyBy']);
            }
        }

        $processedProductSpec = 
            $productSpecProcessor->processSpecToUpdate($requestContent);

        if ($processedProductSpec === null) {
            return $this->json(
                ['errors' => $productSpecProcessor->getSpecErrors()],
                Response::HTTP_BAD_REQUEST
            );
        } else {
            $productRepository = $entityManager->getRepository(Product::class);
            $product = $productRepository->findOneBy($processedProductSpec['identifyingData']);
            if (!$product) {
                return $this->json([], Response::HTTP_NOT_FOUND);
            }

            $appEntityManager->updateFromSpec($processedProductSpec, $product);
            return $this->json([]);
        }
    }

    /**
     * @Route("/product/{id}", name="api_product_delete", methods={"DELETE"})
     */
    public function delete(
        int $id, ProductRepository $productRepository, AppEntityManager $appEntityManager
    ): Response {
        $product = $productRepository->find($id);
        if (!$product) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        $appEntityManager->delete($product);
        return $this->json([]);
    }
}
