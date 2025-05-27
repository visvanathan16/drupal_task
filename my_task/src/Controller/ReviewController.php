<?php

namespace Drupal\my_task\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the Mock Reviews API.
 */
class ReviewController extends ControllerBase {

  /**
   * Returns JSON-formatted mock review data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * A JSON response containing mock review data.
   */
  public function getReviews() {
     $all_reviews = [
      [
        "author" => "John Doe",
        "rating" => 5,
        "title" => "Excellent service!",
        "content" => "Great experience, highly recommended.",
        "date" => "2024-03-18"
      ],
      [
        "author" => "Jane Smith",
        "rating" => 4,
        "title" => "Good but could be better",
        "content" => "Generally satisfied, but shipping was a bit slow.",
        "date" => "2024-03-20"
      ],
      [
        "author" => "Peter Jones",
        "rating" => 5,
        "title" => "Fantastic!",
        "content" => "Smooth process from start to finish. Will use again.",
        "date" => "2024-03-21"
      ],
      [
        "author" => "Alice Williams",
        "rating" => 3,
        "title" => "Average experience",
        "content" => "It was okay, nothing special. Customer support was a bit slow to respond.",
        "date" => "2024-03-22"
      ],
      [
        "author" => "Bob Johnson",
        "rating" => 5,
        "title" => "Top-notch!",
        "content" => "Exceeded expectations. Very happy with the product and service.",
        "date" => "2024-03-23"
      ],
      [
        "author" => "Charlie Brown",
        "rating" => 4,
        "title" => "Solid product",
        "content" => "Works as advertised, no complaints so far.",
        "date" => "2024-03-24"
      ],
      [
        "author" => "Diana Prince",
        "rating" => 5,
        "title" => "Highly recommend!",
        "content" => "Fast delivery and excellent quality. Very impressed.",
        "date" => "2024-03-25"
      ],
    ];

    // Calculate aggregate stats
    $total_reviews = count($all_reviews);
   // echo "dd".$total_reviews;exit;
    $total_rating_sum = 0;
    foreach ($all_reviews as $review) {
      $total_rating_sum += $review['rating'];
    }
    $average_rating = ($total_reviews > 0) ? round($total_rating_sum / $total_reviews, 1) : 0; // Rounded to 1 decimal place

    // Determine average rating string (example mapping)
    $average_rating_string = 'No reviews';
    if ($average_rating >= 4.5) {
      $average_rating_string = 'Excellent';
    } elseif ($average_rating >= 3.5) {
      $average_rating_string = 'Great';
    } elseif ($average_rating >= 2.5) {
      $average_rating_string = 'Good';
    } else {
      $average_rating_string = 'Average';
    }


    // Wrap the reviews and aggregate data in a 'reviews' key as requested.
    $data = [
      'total_reviews' => $total_reviews,
      'average_rating' => $average_rating,
      'average_rating_string' => $average_rating_string,
      'reviews' => $all_reviews, // Still include all individual reviews
    ];

    // Return a JsonResponse object. Drupal will automatically handle
    // setting the Content-Type header to application/json.
    return new JsonResponse($data);
  }

}