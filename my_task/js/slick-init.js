(function ($, Drupal) {
    /**
     * Initializes Slick Carousel on elements with the '.slick-slider' class.
     */
    Drupal.behaviors.slickInit = {
        attach: function (context, settings) {
            
            const slidesToShowVal = settings.mockReviewsApi && settings.mockReviewsApi.carouselSettings && settings.mockReviewsApi.carouselSettings.slidesToShow  ? settings.mockReviewsApi.carouselSettings.slidesToShow : 1; // Default to 1 if setting is not found

            console.log("sli",slidesToShowVal);
            // The context parameter ensures it works correctly with AJAX content.
            $(context).find('.slick-slider').each(function () {
                $(this).slick({
                    dots: true,
                    infinite: true,
                    speed: 300,
                    slidesToShow: slidesToShowVal,
                    slidesToScroll: slidesToShowVal,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 3,
                                infinite: true,
                                dots: true
                            }
                        },
                        {
                            breakpoint: 600,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 2
                            }
                        },
                        {
                            breakpoint: 480,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1
                            }
                        }
                        // You can unslick at a given breakpoint now by adding:
                        // settings: "unslick"
                        // instead of a settings object
                    ]
                });
            });
        }
    };
    Drupal.behaviors.slickNavigationDebug = {
    attach: function (context, settings) {
      // Ensure this runs only once for the entire document context
      $(context).find('.slick-prev, .slick-next, .slick-dots button').each(function() {
        $(this).on('click', function(e) {
          console.log('Slick Navigation Element Clicked!', this);
          // Optional: Prevent default to see if it's Slick's handler or something else
          // e.preventDefault();
        });
      });
    }
  };
})(jQuery, Drupal);