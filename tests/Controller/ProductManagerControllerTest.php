<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductManagerControllerTest extends WebTestCase
{
    private
        $entityManager,
        $productRepository,

        $productForUpdateId,
        $productForUpdateEId,
        $productForDeleteId;

    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->productRepository = 
            $this->entityManager->getRepository(Product::class);

        $productForUpdate = new Product();
        $productForUpdate->setTitle('for_update_1');
        $productForUpdate->setPrice(7.6);
        $productForUpdate->setEId(1);
        $this->entityManager->persist($productForUpdate);
        $this->entityManager->flush();

        $this->productForUpdateId = $productForUpdate->getId();
        $this->productForUpdateEId = $productForUpdate->getEId();

        $productForDelete = new Product();
        $productForDelete->setTitle('for_delete_1');
        $productForDelete->setPrice(10.0);
        $this->entityManager->persist($productForDelete);
        $this->entityManager->flush();

        $this->productForDeleteId = $productForDelete->getId();

        $category = new Category();
        $category->setTitle('common');
        $category->setEId(1);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    public function creationProvider()
    {
        return [
            ['{"entityData": {"title":"created_1", "price":4.14}}'],
            ['{"entityData": {"title":"created_2", "price":4.14, "eId":null}}'],
            ['{"entityData": {"title":"created_3", "price":20.0, "eId":958}}'],
            ['{"entityData": {"title":"created_1", "price":4.14, "categoriesEIds":[1]}}'],
        ];
    }

    public function multipleCreationProvider()
    {
        return [
            // when all sets are correct
            ['{"entitiesSpecs": [ {"entityData": {"title":"created_1.1", "price":4.14}}, {"entityData": {"title":"created_1.2", "price":7.0, "eId":9}} ]}'],
            // when there is incorrect set
            ['{"entitiesSpecs": [ {"entityData": {"title":"created_1.1", "price":"incorrect"}}, {"entityData": {"title":"created_1.2", "price":7.0}} ]}', 0],
        ];
    }

    public function creationDenyProvider()
    {
        return [
            // not JSON or enpty
            ['string'],
            ['{}'],

            // not allowed data
            ['{"entityData": {"notAllowed":3, "title":"common", "price":4.14}}'],

            // absense of required data
            ['{"entityData": {"title":"common"}}'],
            ['{"entityData": {"price":4.14}}'],

            // incorrect title
            ['{"entityData": {"title":"1", "price":4.14}}'],
            ['{"entityData": {"title":"more_12_symbols", "price":4.14}}'],
            ['{"entityData": {"title":1, "price":4.14}}'],

            // incorrect price
            ['{"entityData": {"title":"common", "price":20}}'],
            ['{"entityData": {"title":"common", "price":"string"}}'],

            // incorrect eId
            ['{"entityData": {"title":"created_1", "price":4.14, "eId":"string"}}'],

            // absent category eId
            ['{"entityData": {"title":"created_1", "price":4.14, "categoriesEIds":[1, 2]}}'],
            // incorrect categoriesEIds format
            ['{"entityData": {"title":"created_1", "price":4.14, "categoriesEIds":"incorrect"}}'],
            ['{"entityData": {"title":"created_1", "price":4.14, "categoriesEIds":["incorrect"]}}'],
        ];
    }

    public function updateProvider()
    {
        return [
            // set "identifyBy" field when a request to URI specifies entity id (useless)
            ['{"entityData": {"eId":1, "title":"created_1", "price":4.14}, "identifyBy":["eId"]}'],
            // set "identifyBy" field when a request to URI does not specify entity id (useless)
            ['{"entityData": {"eId":1, "title":"created_1", "price":4.14}, "identifyBy":["eId"]}', true],
        ];
    }

    public function updateDenyProvider()
    {
        return [
            // set "identifyBy" field to null
            ['{"entityData": {"eId":null, "title":"created_1", "price":4.14}, "identifyBy":["eId"]}', true],

            // not JSON or enpty
            ['string'], ['string', true],
            ['{}'], ['{}', true],

            // not allowed data
            ['{"entityData": {"notAllowed":3}}'],

            // incorrect title
            ['{"entityData": {"title":"1"}}'],
            ['{"entityData": {"title":"more_12_symbols"}}'],
            ['{"entityData": {"title":1}'],

            // incorrect price
            ['{"entityData": {"price":20}}'],
            ['{"entityData": {"price":"string"}}'],

            // incorrect eId
            ['{"entityData": {"eId":"string"}}'],
        ];
    }

    public function testGetAllProducts()
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $client->request('GET', '/api/products');
        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(isset($responseContent['products']));

        $this->deleteProductsFromDatabase();
        $client->request('GET', '/api/products');
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetProduct()
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $client->request('GET', "/api/product/{$this->productForUpdateId}");
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $client->request('GET', "/api/product/0");
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @dataProvider creationProvider
     */
    public function testCreateProduct(string $requestBody): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->sendJsonRequest(
            $client,
            'POST',
            '/api/product',
            $requestBody
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $productCreationData = json_decode($requestBody, true)['entityData'];
        $products = $this->productRepository->findBy([
            'title' => $productCreationData['title'],
        ]);

        $this->assertTrue(count($products) === 1);
        $this->assertTrue($products[0]->getTitle() === $productCreationData['title']);
        $this->assertTrue($products[0]->getPrice() === $productCreationData['price']);
        $this->assertTrue($products[0]->getEId()   === ($productCreationData['eId'] ?? null));

        if (isset($productCreationData['categoriesEIds'])) {
            $this->assertTrue($products[0]->getCategories()[0]->getEId() === $productCreationData['categoriesEIds'][0]);
        }
    }

    /**
     * @dataProvider multipleCreationProvider
     */
    public function testCreateProducts(string $requestBody, ?int $incorrectSpecIndex = null): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->sendJsonRequest(
            $client,
            'POST',
            '/api/products',
            $requestBody
        );
        $response = $client->getResponse();
        $responseContent = $response->getContent();
        $responseContent = json_decode($responseContent, true);
        $this->assertEquals(200, $response->getStatusCode());

        $productSpecs = json_decode($requestBody, true)['entitiesSpecs'];
        foreach ($productSpecs as $key => $productSpec) {
            if (isset($incorrectSpecIndex) && $incorrectSpecIndex === $key) {
                $product = $this->productRepository->findOneBy([
                    'title' => $productSpec['entityData']['title'],
                ]); 
                $this->assertTrue(!$product);
                $this->assertTrue(isset($responseContent['errors']));

            } else {
                $products = $this->productRepository->findBy([
                    'title' => $productSpec['entityData']['title'],
                ]);
                $this->assertTrue(count($products) === 1);
                $this->assertTrue($products[0]->getTitle() === $productSpec['entityData']['title']);
                $this->assertTrue($products[0]->getPrice() === $productSpec['entityData']['price']);
                $this->assertTrue($products[0]->getEId()   === ($productSpec['entityData']['eId'] ?? null));
            }
        }
    }

    /**
     * @dataProvider creationDenyProvider
     */
    public function testDenyCreateProduct(string $requestBody): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->sendJsonRequest(
            $client,
            'POST',
            '/api/product',
            $requestBody
        );
        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent(), true);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue(isset($responseContent['errors']));
    }

    /**
     * @dataProvider creationProvider
     * @dataProvider updateProvider
     */
    public function testUpdateProduct(string $requestBody, ?bool $ifNotUseIdInUri = null): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->sendJsonRequest(
            $client,
            'PUT',
            $ifNotUseIdInUri ? '/api/product' : "/api/product/{$this->productForUpdateId}",
            $requestBody
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $productCreationData = json_decode($requestBody, true)['entityData'];
        $product = $this->productRepository->find($this->productForUpdateId); 

        $this->assertTrue($product->getTitle() === $productCreationData['title']);
        $this->assertTrue($product->getPrice() === $productCreationData['price']);
        $this->assertTrue($product->getEId()   === ($productCreationData['eId'] ?? $this->productForUpdateEId));
    }

    /**
     * @dataProvider updateDenyProvider
     */
    public function testUpdateDenyProduct(string $requestBody, ?bool $ifNotUseIdInUri = null): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->sendJsonRequest(
            $client,
            'PUT',
            $ifNotUseIdInUri ? '/api/product' : "/api/product/{$this->productForUpdateId}",
            $requestBody
        );
        $response = $client->getResponse();
        $responseContent = json_decode($response->getContent(), true);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue(isset($responseContent['errors']));
    }

    public function testDeleteProduct(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $client->request('DELETE', "/api/product/{$this->productForDeleteId}");
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $product = $this->productRepository->find($this->productForDeleteId);
        $this->assertTrue(!$product);

        $client->request('DELETE', "/api/product/0");
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    private function sendJsonRequest($client, string $requestMethod, string $uri, string $requestBody): void
    {
        $client->request(
            $requestMethod,
            $uri, 
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $requestBody
        );
    }

    private function deleteProductsFromDatabase(): void
    {
        $products = $this->productRepository->findAll();
        foreach ($products as $product) {
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        }
    }
}