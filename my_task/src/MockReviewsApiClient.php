<?php

namespace Drupal\my_task;

use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Service for fetching mock review data with caching.
 */
class MockReviewsApiClient implements MockReviewsApiClientInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The serializer.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache ID for reviews.
   *
   * @var string
   */
  const CACHE_ID = 'mock_reviews_api_reviews';

  /**
   * The cache tag for reviews.
   *
   * @var string
   */
  const CACHE_TAG = 'mock_reviews_api_reviews_cache';

  /**
   * The API endpoint for reviews.
   *
   * @var string
   */
  const API_ENDPOINT = 'http://localhost/d10/web/mock-reviews'; // <--- IMPORTANT: Update this to your actual mock API URL

  /**
   * Constructs a new MockReviewsApiClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   * The Guzzle HTTP client.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   * The serializer service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   * The cache backend.
   */
  public function __construct(ClientInterface $http_client, SerializationInterface $serializer, CacheBackendInterface $cache_backend) {
    $this->httpClient = $http_client;
    $this->serializer = $serializer;
    $this->cache = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function getReviews(): array {
    // 1. Try to get data from cache first.
    if ($cache = $this->cache->get(self::CACHE_ID)) {
      return $cache->data;
    }

    // 2. If not in cache, fetch from the API.
    try {
      $response = $this->httpClient->get(self::API_ENDPOINT);
      $data = $this->serializer->decode($response->getBody()->getContents());

      // Ensure data is an array, even if API returns something else or empty.
      if (!is_array($data)) {
        $data = [];
      }

      // 3. Cache the data.
      // Cache for 1 hour (3600 seconds) from now, with specific cache tags.
      $this->cache->set(self::CACHE_ID, $data, time() + 3600, [self::CACHE_TAG]);

      return $data;

    } catch (\Exception $e) {
      // In a real application, you'd log the error.
      // For a mock, we'll return an empty array or handle gracefully.
      \Drupal::logger('mock_reviews_api')->error('Failed to fetch reviews from API: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

}