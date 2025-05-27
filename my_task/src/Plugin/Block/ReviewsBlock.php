<?php

namespace Drupal\my_task\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RequestStack; // <-- Make sure this is present!
use Drupal\my_task\MockReviewsApiClientInterface;

/**
 * Provides a 'Latest Reviews' Block.
 *
 * @Block(
 * id = "reviews_block",
 * admin_label = @Translation("Latest Reviews"),
 * category = @Translation("Custom"),
 * )
 */
class ReviewsBlock extends BlockBase implements ContainerFactoryPluginInterface {


  // Add the RequestStack service for more granular request info
  protected $requestStack;

   /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

    /**
   * The mock reviews API client.
   *
   * @var \Drupal\my_task\MockReviewsApiClientInterface
   */
  protected $mockReviewsApiClient;

  /**
   * Constructs a new LatestReviewsBlock instance.
   *
   * @param array $configuration
   * A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   * The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   * The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   * The Guzzle HTTP client.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack // <-- Add this argument
   * The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->requestStack = $request_stack; 
  //  $this->mockReviewsApiClient = $mock_reviews_api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('request_stack'), // <-- This line must be here and correct!
      //$container->get('mock_reviews_api.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'number_of_reviews' => 3, // Default to 3 reviews
      'api_endpoint' => '/mock-reviews', // Default API endpoint
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['number_of_reviews'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of reviews to display'),
      '#min' => 1,
      '#max' => 10, // Arbitrary max, adjust as needed
      '#default_value' => $config['number_of_reviews'],
      '#description' => $this->t('Enter the maximum number of latest reviews to show.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['number_of_reviews'] = $form_state->getValue('number_of_reviews');    
   // $this->configuration['api_endpoint'] = $form_state->getValue('api_endpoint');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $num_reviews_slideshow = $this->configuration['number_of_reviews'];
    $api_endpoint = $this->configuration['api_endpoint'];
    $reviews = [];
    $total_reviews = 0;
    $average_rating = 0;
    $average_rating_string = '';

    try {
      
      // Constructing the full API URL reliably for an internal route:
      // Option 1: Use Url::fromRoute() for absolute path generation
      $full_api_url = \Drupal\Core\Url::fromRoute('my_task.reviews', [], ['absolute' => TRUE])->toString();
      //\Drupal::logger('my_task')->debug('Full API URL (fromRoute): @url', ['@url' => $full_api_url]);

      // Make the HTTP GET request to your mock API.
      $response = $this->httpClient->request('GET', $full_api_url);

      // Ensure the response was successful (HTTP 200 OK).
      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody()->getContents(), TRUE); // Decode JSON to associative array

        // Extract aggregate data
        $total_reviews = isset($data['total_reviews']) ? (int) $data['total_reviews'] : 0;
        $average_rating = isset($data['average_rating']) ? (float) $data['average_rating'] : 0;
        $average_rating_string = isset($data['average_rating_string']) ? $data['average_rating_string'] : $this->t('No rating');


        if (isset($data['reviews']) && is_array($data['reviews'])) {
          // Pass all reviews to Twig. Slick Carousel will handle 'slidesToShow'.
          $reviews = $data['reviews'];
        }
      }
    }
    catch (RequestException $e) {
      // Log the exception for debugging.
      $this->messenger()->addError($this->t('Could not retrieve reviews from the API: @error', ['@error' => $e->getMessage()]));
     // $this->getLogger('my_task')->error('Error fetching reviews: @message', ['@message' => $e->getMessage()]);
    }
    catch (\Exception $e) {
      // Catch other potential exceptions (e.g., json_decode errors).
      $this->messenger()->addError($this->t('An unexpected error occurred while processing reviews: @error', ['@error' => $e->getMessage()]));
   //   $this->getLogger('my_task')->error('Unexpected error fetching reviews: @message', ['@message' => $e->getMessage()]);
    }

    // Prepare a render array for the block content.
    return [
      '#theme' => 'reviews_block',
      '#reviews' => $reviews,
      '#total_reviews' => $total_reviews,          // <-- New variable
      '#average_rating' => $average_rating,        // <-- New variable
      '#average_rating_string' => $average_rating_string, // <-- New variable
      // Cacheability based on the API endpoint.
      // If the API data itself doesn't change frequently, you might set a longer max-age.
      // If the API can be busted by an admin action, add cache tags.
      // For a purely external API, you might have to rely on time-based caching.
        '#cache' => [
            //'tags' => $this->getCacheTags(),
            //'contexts' => $this->getCacheContexts(),
            'max-age' => 3600,
            'tags' => ['mock_reviews_api_reviews_cache'],
        ],
         // Attach your library here!
        '#attached' => [
          'library' => [
            'my_task/slick', // This refers to the library defined in .libraries.yml
          ],
          'drupalSettings' => [
            'mockReviewsApi' => [
              'carouselSettings' => [
                'slidesToShow' => $num_reviews_slideshow,
              ],
            ],
          ],
        ],
    ];
  }

   /**
   * {@inheritdoc}
   *
   * Override to include your custom cache tag.
   */
  public function getCacheTags() {
    // Merge parent cache tags (if any) with your custom tag.
    // In this case, parent::getCacheTags() might return empty for a block.
    return \Drupal\Core\Cache\Cache::mergeTags(parent::getCacheTags(), ['mock_reviews_api_reviews_cache']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // If the block content can vary based on user, permissions, etc.,
    // add appropriate cache contexts. For this simple block, no extra
    // contexts are strictly needed unless sorting/filtering by user.
    return parent::getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache permanently, relying on cache tags for invalidation.
    // If review content changes frequently, you might set a specific max-age (e.g., 3600 for 1 hour).
    return \Drupal\Core\Cache\Cache::PERMANENT;
  }

}