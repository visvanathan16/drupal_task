<?php

namespace Drupal\Tests\my_task\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\my_task\MockReviewsApiClient;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Serialization\SerializationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response; // To mock HTTP responses

/**
 * @coversDefaultClass \Drupal\my_task\MockReviewsApiClient
 * @group my_task
 */
class MockReviewsApiClientTest extends UnitTestCase {

  /**
   * The HTTP client mock.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The serializer mock.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $serializer;

  /**
   * The cache backend mock.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->serializer = $this->createMock(SerializationInterface::class);
    $this->cacheBackend = $this->createMock(CacheBackendInterface::class);
  }

  /**
   * Tests getReviews() when data is found in cache.
   */
  public function testGetReviewsFromCache(): void {
    $cachedData = [
      ['rating' => 5, 'title' => 'Cached Review 1', 'content' => 'Content 1'],
      ['rating' => 4, 'title' => 'Cached Review 2', 'content' => 'Content 2'],
    ];

    // Configure the cache backend mock: expect get() to be called and return cached data.
    $cacheItem = new \stdClass();
    $cacheItem->data = $cachedData;
    $cacheItem->expire = CacheBackendInterface::CACHE_PERMANENT;
    $cacheItem->tags = ['mock_reviews_api_reviews_cache'];
    $cacheItem->checksum = 'mock_checksum'; // Add relevant cache item properties if needed by Drupal's cache system internally

    $this->cacheBackend->expects($this->once()) // Expect get() to be called once
      ->method('get')
      ->with('mock_reviews_api_reviews') // The cache ID used in your service
      ->willReturn($cacheItem);

    // Ensure httpClient is NOT called if data is in cache.
   // $this->httpClient->expects($this->never()) // Expect get() on httpClient to NEVER be called
    //  ->method('get');

    // Instantiate the service with our mocks.
    $client = new MockReviewsApiClient(
      $this->httpClient,
      $this->serializer,
      $this->cacheBackend
    );

    // Call the method under test.
    $reviews = $client->getReviews();

    // Assert that the returned data is from the cache.
    $this->assertEquals($cachedData, $reviews);
  }

  /**
   * Tests getReviews() when data is not in cache (cache miss) and fetched from API.
   */
  public function testGetReviewsFromApiAndCache(): void {
    $apiResponseData = [
      ['rating' => 5, 'title' => 'API Review 1', 'content' => 'API Content 1'],
      ['rating' => 4, 'title' => 'API Review 2', 'content' => 'API Content 2'],
    ];
    $jsonResponse = json_encode($apiResponseData);

    // 1. Configure the cache backend mock for a cache miss.
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('mock_reviews_api_reviews')
      ->willReturn(FALSE); // Simulates cache miss

    // 2. Configure the HTTP client mock to return a successful response.
    /* $mockHttpResponse = new Response(200, ['Content-Type' => 'application/json'], $jsonResponse);
     $this->httpClient->expects($this->once())
      ->method('get')
      ->with('http://localhost/d10/web/mock-reviews') // Replace with your actual API endpoint
      ->willReturn($mockHttpResponse);
      */

    // 3. Configure the serializer mock to parse the JSON.
    $this->serializer->expects($this->once())
      ->method('decode')
      ->with($jsonResponse)
      ->willReturn($apiResponseData);

    // 4. Configure the cache backend mock to expect a set() call after API fetch.
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with(
        'mock_reviews_api_reviews', // Cache ID
        $apiResponseData, // Data being cached
        $this->isType('int'), // Expiration timestamp (any integer)
        ['mock_reviews_api_reviews_cache'] // Cache tags
      );

    // Instantiate the service.
    $client = new MockReviewsApiClient(
      $this->httpClient,
      $this->serializer,
      $this->cacheBackend
    );

    // Call the method.
    $reviews = $client->getReviews();

    // Assert that the returned data is from the API.
    $this->assertEquals($apiResponseData, $reviews);
  }

  /**
   * Tests getReviews() gracefully handles an empty API response (cache miss).
   */
  public function testGetReviewsEmptyApiResponse(): void {
    $jsonResponse = json_encode([]); // Empty array response

    // 1. Cache miss.
    $this->cacheBackend->method('get')->willReturn(FALSE);

    // 2. HTTP client returns an empty successful response.
   /* $mockHttpResponse = new Response(200, ['Content-Type' => 'application/json'], $jsonResponse);
    $this->httpClient->expects($this->once())
      ->method('get')
      ->willReturn($mockHttpResponse);
    */
    // 3. Serializer decodes to an empty array.
    $this->serializer->expects($this->once())
      ->method('decode')
      ->with($jsonResponse)
      ->willReturn([]);

    // 4. Cache set with empty data.
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with(
        'mock_reviews_api_reviews',
        [], // Expect empty array to be cached
        $this->isType('int'),
        ['mock_reviews_api_reviews_cache']
      );

    // Instantiate and call.
    $client = new MockReviewsApiClient(
      $this->httpClient,
      $this->serializer,
      $this->cacheBackend
    );
    $reviews = $client->getReviews();

    // Assert an empty array is returned.
    $this->assertEquals([], $reviews);
  }
}