<?php

namespace Drupal\my_task;

/**
 * Defines an interface for the Mock Reviews API client service.
 */
interface MockReviewsApiClientInterface {

  /**
   * Retrieves review data from the mock API.
   *
   * @return array
   * An array of review data, each review being an associative array.
   */
  public function getReviews(): array;

}