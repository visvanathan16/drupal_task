<?php

namespace Drupal\Tests\my_task\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\my_task\MockReviewsApiClientInterface; // Use the interface for mocking
use Drupal\block\Entity\Block; // To programmatically place the block
use Drupal\Core\Routing\RouteMatchInterface; // For block configuration form
use Drupal\Core\Form\FormState;

/**
 * Tests the ReviewsBlock plugin.
 *
 * @group my_task
 */
class ReviewsBlockKernelTest extends KernelTestBase {

  /**
   * The mocked reviews API client.
   *
   * @var \Drupal\my_task\MockReviewsApiClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mockReviewsApiClient;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user', // Often required for config, block.module dependencies
    'block',
    'my_task',
    'serialization', // Needed for the serializer service dependency of our client
  ];

  /**
   * {@inheritdoc}
   */

  protected function setUp(): void {  

    parent::setUp();
    $this->mockReviewsApiClient = $this->createMock(MockReviewsApiClientInterface::class);
    $this->mockReviewsApiClient
        ->method('getReviews')
        ->willReturn([
        ['title' => 'Excellent!', 'rating' => 5],
        ]);

    // Only allowed if service is already defined
    $this->container->set('mock_reviews_api.client', $this->mockReviewsApiClient);
}

  /**
   * Test the block's configuration form.
   */
  public function testBlockConfigurationForm(): void {
    // Create a mock RouteMatchInterface if your block form needs it.
    // Most simple blocks don't, but some forms rely on route parameters.
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->container->set('current_route_match', $routeMatch);

    // Get an instance of the block plugin.
    $block_manager = $this->container->get('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('reviews_block');

    // Test default configuration values.
    $default_config = $block_plugin->defaultConfiguration();
    $this->assertEquals(3, $default_config['number_of_reviews']);
   // $this->assertEquals('Latest Reviews', $default_config['label']);
    //$this->assertFalse($default_config['label_display']);

    // Build the configuration form.
    $form_state = new \Drupal\Core\Form\FormState();
    $form = $block_plugin->blockForm([], $form_state);

    // Assert form elements exist and have correct types/default values.
    $this->assertArrayHasKey('number_of_reviews', $form);
    $this->assertEquals('number', $form['number_of_reviews']['#type']);
    $this->assertEquals('Number of reviews to display', $form['number_of_reviews']['#title']);
    $this->assertEquals(3, $form['number_of_reviews']['#default_value']);

    // Simulate form submission.
    $form_state->setValue('number_of_reviews', 5); // Set a new value
    $block_plugin->blockSubmit($form, $form_state);

    // Assert configuration was updated.
    $updated_config = $block_plugin->getConfiguration();
    $this->assertEquals(5, $updated_config['number_of_reviews']);
  }

  /**
   * Test the build method when no reviews are returned from the API.
   */
  public function testBlockBuildNoReviews(): void {
    // Configure the mock client to return no reviews.
    $this->mockReviewsApiClient->expects($this->once())
      ->method('getReviews')
      ->willReturn([]);

    // Get an instance of the block plugin.
    $block_manager = $this->container->get('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('reviews_block', [
      'number_of_reviews' => 4, // Test with a different config value
    ]);

    // Build the render array.
    $build = $block_plugin->build();

    // Assertions.
    $this->assertArrayHasKey('#theme', $build);
    $this->assertEquals('reviews_block', $build['#theme']);
    $this->assertEquals([], $build['#reviews']);
    $this->assertEquals(0, $build['#total_reviews']);
    $this->assertArrayHasKey('library', $build['#attached']);
    $this->assertContains('my_task/slick', $build['#attached']['library']);
    $this->assertArrayHasKey('drupalSettings', $build['#attached']);
    $this->assertEquals(4, $build['#attached']['drupalSettings']['mockReviewsApi']['carouselSettings']['slidesToShow']);

    // Test cacheability.
    $this->assertArrayHasKey('#cache', $build);
    $this->assertArrayHasKey('max-age', $build['#cache']);
    $this->assertEquals(3600, $build['#cache']['max-age']);
    $this->assertArrayHasKey('tags', $build['#cache']);
    $this->assertContains('mock_reviews_api_reviews_cache', $build['#cache']['tags']);
  }

  /**
   * Test the build method with mock review data.
   */
  public function testBlockBuildWithReviews(): void {
    $reviews_data = [
      ['rating' => 5, 'title' => 'Great!', 'content' => 'Lorem ipsum', 'author' => 'User1', 'date' => '2023-01-01'],
      ['rating' => 4, 'title' => 'Good!', 'content' => 'Dolor sit amet', 'author' => 'User2', 'date' => '2023-01-02'],
      ['rating' => 3, 'title' => 'Okay.', 'content' => 'Consectetur adipiscing', 'author' => 'User3', 'date' => '2023-01-03'],
    ];

    // Configure the mock client to return review data.
    $this->mockReviewsApiClient->expects($this->once())
      ->method('getReviews')
      ->willReturn($reviews_data);

    // Get an instance of the block plugin.
    $block_manager = $this->container->get('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('reviews_block'); // Use default config

    // Build the render array.
    $build = $block_plugin->build();

    // Assertions.
    $this->assertEquals(3, $build['#attached']['drupalSettings']['mockReviewsApi']['carouselSettings']['slidesToShow']); // Default config is 3
   
    // Verify cacheability again (should be the same as no reviews).
    $this->assertArrayHasKey('#cache', $build);
    $this->assertEquals(3600, $build['#cache']['max-age']);
    $this->assertContains('mock_reviews_api_reviews_cache', $build['#cache']['tags']);
  }
}