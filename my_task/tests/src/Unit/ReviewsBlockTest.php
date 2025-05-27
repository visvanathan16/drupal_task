<?php

namespace Drupal\Tests\my_task\Unit;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\my_task\Plugin\Block\ReviewsBlock;
use Drupal\my_task\MockReviewsApiClientInterface; // Assuming you have an interface for your client
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\my_task\Plugin\Block\ReviewsBlock
 * @group my_task
 */
class ReviewsBlockTest extends UnitTestCase {

  /**
   * The block manager mock.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $blockManager;

  /**
   * The module handler mock.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mock reviews API client mock.
   *
   * @var \Drupal\my_task\MockReviewsApiClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mockReviewsApiClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the BlockManagerInterface
    $this->blockManager = $this->createMock(BlockManagerInterface::class);

    // Mock the ModuleHandlerInterface (often needed by block plugins)
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    // Mock the module_handler->invokeAll() method if your block uses hooks
    // Example: $this->moduleHandler->method('invokeAll')->willReturn([]);

    // Mock the MockReviewsApiClientInterface
    $this->mockReviewsApiClient = $this->createMock(MockReviewsApiClientInterface::class);

    // Set up a mock container for dependency injection.
    $container = new ContainerBuilder();
    $container->set('plugin.manager.block', $this->blockManager);
    $container->set('module_handler', $this->moduleHandler);
  //  $container->set('mock_reviews_api.clientt', $this->mockReviewsApiClient); // Register your mocked service

    // Set the container for the test case.
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    // Clean up the container to avoid interference with other tests.
    \Drupal::setContainer(null);
  }

  /**
   * Tests the build method with no reviews.
   */
  public function testBuildNoReviews(): void {
    // Configure the mock API client to return an empty array.
    $this->mockReviewsApiClient->method('getReviews')->willReturn([]);

    // Instantiate the block with a configuration.
    $configuration = [
      'number_of_reviews' => 3,
    ];
    $block = ReviewsBlock::create($this->container, $configuration, 'latest_reviews_block', []);

    // Call the build method.
    $build = $block->build();

    // Assertions for no reviews scenario.
    $this->assertArrayHasKey('#theme', $build);
    $this->assertEquals('latest_reviews_block', $build['#theme']);
    $this->assertEquals([], $build['#reviews']);
    $this->assertEquals(0, $build['#total_reviews']);
    $this->assertEquals(0.0, $build['#average_rating']);
    $this->assertEquals('No reviews', $build['#average_rating_string']); // Assuming this is your default string
    $this->assertArrayHasKey('library', $build['#attached']);
    $this->assertContains('my_theme/slick_init', $build['#attached']['library']);
    $this->assertArrayHasKey('drupalSettings', $build['#attached']);
    $this->assertEquals(3, $build['#attached']['drupalSettings']['mockReviewsApi']['carouselSettings']['slidesToShow']);
  }

  /**
   * Tests the build method with multiple reviews and various ratings.
   */
  public function testBuildWithReviews(): void {
    $reviews = [
      ['rating' => 5, 'title' => 'Great product', 'content' => 'Lorem ipsum', 'author' => 'A', 'date' => '2023-01-01'],
      ['rating' => 4, 'title' => 'Good experience', 'content' => 'Dolor sit amet', 'author' => 'B', 'date' => '2023-01-02'],
      ['rating' => 3, 'title' => 'Average', 'content' => 'Consectetur adipiscing', 'author' => 'C', 'date' => '2023-01-03'],
      ['rating' => 5, 'title' => 'Amazing service', 'content' => 'Sed do eiusmod', 'author' => 'D', 'date' => '2023-01-04'],
    ];

    // Configure the mock API client to return our test reviews.
    $this->mockReviewsApiClient->method('getReviews')->willReturn($reviews);

    // Instantiate the block with a configuration.
    $configuration = [
      'number_of_reviews' => 2, // Testing slidesToShow limit
    ];
    $block = ReviewsBlock::create($this->container, $configuration, 'latest_reviews_block', []);

    // Call the build method.
    $build = $block->build();

    // Assertions for reviews scenario.
    $this->assertArrayHasKey('#theme', $build);
    $this->assertEquals('latest_reviews_block', $build['#theme']);

    // Ensure #reviews contains the expected reviews (all of them, as array_slice was removed)
    $this->assertCount(4, $build['#reviews']);
    $this->assertEquals($reviews[0]['title'], $build['#reviews'][0]['title']);

    $this->assertEquals(4, $build['#total_reviews']);

    // Test average rating calculation (5+4+3+5 = 17 / 4 = 4.25)
    $this->assertEquals(4.25, $build['#average_rating']);
    $this->assertEquals('Very good', $build['#average_rating_string']); // Assuming 4.25 maps to 'Very good'

    $this->assertArrayHasKey('library', $build['#attached']);
    $this->assertContains('my_task/slick_init', $build['#attached']['library']);
    $this->assertArrayHasKey('drupalSettings', $build['#attached']);
    $this->assertEquals(2, $build['#attached']['drupalSettings']['mockReviewsApi']['carouselSettings']['slidesToShow']);
  }

  /**
   * Tests the default configuration of the block.
   */
  public function testDefaultConfiguration(): void {
    // Instantiate the block without custom configuration.
    $block = ReviewsBlock::create($this->container, [], 'latest_reviews_block', []);

    // Get the default configuration.
    $defaultConfiguration = $block->defaultConfiguration();

    // Assert default values.
    $this->assertArrayHasKey('number_of_reviews', $defaultConfiguration);
    $this->assertEquals(3, $defaultConfiguration['number_of_reviews']); // Assuming default is 3
    $this->assertArrayHasKey('label', $defaultConfiguration);
    $this->assertEquals('Latest Reviews', $defaultConfiguration['label']);
    $this->assertArrayHasKey('label_display', $defaultConfiguration);
    $this->assertFalse($defaultConfiguration['label_display']);
  }

}