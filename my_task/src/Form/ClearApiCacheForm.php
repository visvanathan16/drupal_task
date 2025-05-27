<?php

namespace Drupal\my_task\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface; // <-- Correct interface to use!
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to clear the Mock Reviews API cache.
 */
class ClearApiCacheForm extends FormBase {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator; // <-- Change property name

  /**
   * Constructs a new ClearApiCacheForm.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator // <-- Change argument type
   * The cache tags invalidator.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator; // <-- Assign to new property
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache_tags.invalidator') // <-- Inject 'cache_tags.invalidator' service
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mock_reviews_api_clear_api_cache_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => $this->t('Click the button below to manually clear the cached responses from the Mock Reviews API. This will force the "Latest Reviews" block to fetch fresh data.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear API Cache'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Invalidate the specific cache tag we added to the block.
    $this->cacheTagsInvalidator->invalidateTags(['mock_reviews_api_reviews_cache']);

    $this->messenger()->addStatus($this->t('Mock Reviews API cache has been cleared.'));
    // Optional: Redirect back to the same page.
    // $form_state->setRedirect('mock_reviews_api.clear_api_cache_form');
  }

}