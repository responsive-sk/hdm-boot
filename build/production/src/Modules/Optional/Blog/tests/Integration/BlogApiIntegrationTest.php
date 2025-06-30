<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Optional\Blog\Tests\Integration;

use HdmBoot\Modules\Optional\Blog\Tests\BlogTestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Integration tests for Blog API endpoints.
 */
class BlogApiIntegrationTest extends BlogTestCase
{
    private ServerRequestFactory $requestFactory;

    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    public function testApiListReturnsArticles(): void
    {
        // Create test articles
        $this->createTestArticles(3);

        $request = $this->requestFactory->createServerRequest('GET', '/api/blog/articles');
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiList($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $body = (string) $result->getBody();
        $this->assertValidJson($body);

        $data = json_decode($body, true);
        $this->assertSuccessResponse($data);
        $this->assertArrayHasKey('articles', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertCount(3, $data['articles']);
    }

    public function testApiListWithEmptyDatabase(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/api/blog/articles');
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiList($request, $response);

        $this->assertEquals(200, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertSuccessResponse($data);
        $this->assertCount(0, $data['articles']);
        $this->assertEquals(0, $data['count']);
    }

    public function testApiShowWithValidSlug(): void
    {
        $article = $this->createTestArticle([
            'slug'  => 'api-test-article',
            'title' => 'API Test Article',
        ]);

        $request = $this->requestFactory->createServerRequest('GET', '/api/blog/articles/api-test-article');
        $response = $this->responseFactory->createResponse();
        $args = ['slug' => 'api-test-article'];

        $result = $this->blogController->apiShow($request, $response, $args);

        $this->assertEquals(200, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('article', $data);
        $this->assertEquals('API Test Article', $data['article']['title']);
        $this->assertEquals('api-test-article', $data['article']['slug']);
    }

    public function testApiShowWithInvalidSlug(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/api/blog/articles/nonexistent');
        $response = $this->responseFactory->createResponse();
        $args = ['slug' => 'nonexistent'];

        $result = $this->blogController->apiShow($request, $response, $args);

        $this->assertEquals(404, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertErrorResponse($data);
        $this->assertStringContainsString('not found', $data['error']);
    }

    public function testApiCreateWithValidData(): void
    {
        $postData = [
            'title'    => 'New API Article',
            'content'  => 'This article was created via API',
            'author'   => 'API User',
            'category' => 'api-test',
        ];

        $request = $this->requestFactory->createServerRequest('POST', '/api/blog/articles')
            ->withParsedBody($postData);
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiCreate($request, $response);

        $this->assertEquals(201, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertSuccessResponse($data);
        $this->assertArrayHasKey('article', $data);
        $this->assertEquals('New API Article', $data['article']['title']);
    }

    public function testApiCreateWithMissingTitle(): void
    {
        $postData = [
            'content' => 'Content without title',
            'author'  => 'API User',
        ];

        $request = $this->requestFactory->createServerRequest('POST', '/api/blog/articles')
            ->withParsedBody($postData);
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiCreate($request, $response);

        $this->assertEquals(400, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertErrorResponse($data);
        $this->assertStringContainsString('required', $data['error']);
    }

    public function testApiCreateWithMissingContent(): void
    {
        $postData = [
            'title'  => 'Title without content',
            'author' => 'API User',
        ];

        $request = $this->requestFactory->createServerRequest('POST', '/api/blog/articles')
            ->withParsedBody($postData);
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiCreate($request, $response);

        $this->assertEquals(400, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertErrorResponse($data);
        $this->assertStringContainsString('required', $data['error']);
    }

    public function testApiCreateWithInvalidData(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/api/blog/articles')
            ->withParsedBody(null); // Invalid data
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiCreate($request, $response);

        $this->assertEquals(400, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertErrorResponse($data);
    }

    public function testApiUpdateReturnsNotImplemented(): void
    {
        $request = $this->requestFactory->createServerRequest('PUT', '/api/blog/articles/test-slug');
        $response = $this->responseFactory->createResponse();
        $args = ['slug' => 'test-slug'];

        $result = $this->blogController->apiUpdate($request, $response, $args);

        $this->assertEquals(501, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertErrorResponse($data);
        $this->assertStringContainsString('not implemented', $data['error']);
    }

    public function testApiDeleteReturnsNotImplemented(): void
    {
        $request = $this->requestFactory->createServerRequest('DELETE', '/api/blog/articles/test-slug');
        $response = $this->responseFactory->createResponse();
        $args = ['slug' => 'test-slug'];

        $result = $this->blogController->apiDelete($request, $response, $args);

        $this->assertEquals(501, $result->getStatusCode());

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertErrorResponse($data);
        $this->assertStringContainsString('not implemented', $data['error']);
    }

    public function testApiStatsReturnsNotImplemented(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/api/blog/stats');
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiStats($request, $response);

        $this->assertEquals(501, $result->getStatusCode());
    }

    public function testApiSearchReturnsNotImplemented(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/api/blog/search');
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiSearch($request, $response);

        $this->assertEquals(501, $result->getStatusCode());
    }

    public function testApiCategoriesReturnsNotImplemented(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/api/blog/categories');
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiCategories($request, $response);

        $this->assertEquals(501, $result->getStatusCode());
    }

    public function testApiTagsReturnsNotImplemented(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/api/blog/tags');
        $response = $this->responseFactory->createResponse();

        $result = $this->blogController->apiTags($request, $response);

        $this->assertEquals(501, $result->getStatusCode());
    }
}
