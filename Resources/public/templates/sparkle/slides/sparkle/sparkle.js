/**
 * Sparkle slide js.
 *
 * Cycles through the feed.
 */

// Register the function, if it does not already exist.
if (!window.slideFunctions['sparkle']) {
  window.slideFunctions['sparkle'] = {
    /**
     * Setup the slide for rendering.
     * @param scope
     *   The slide scope.
     */
    setup: function setupSparkleSlide(scope) {
      var slide = scope.ikSlide;

      // Setup the inline styling
      scope.theStyle = {
        width: "100%",
        height: "100%",
        fontsize: slide.options.fontsize * (scope.scale ? scope.scale : 1.0)+ "px"
      };

      slide.feedIndex = 0;
      slide.numberOfItemsToDisplay = Math.min(slide.options.numberOfItems, slide.external_data.length);

      slide.setDirection = function () {
        var slideElement = document.getElementsByClassName('slide-' + slide.uniqueId);
        if (slideElement[0].offsetWidth >= slideElement[0].offsetHeight) {
          slide.direction = 'row';
        } else {
          slide.direction = 'column';
        }
      };

      slide.feedTimeout = function (region, slide) {
        region.$timeout(function () {
          var newIndex = (slide.feedIndex + 1) % slide.numberOfItemsToDisplay;

          if (newIndex === 0) {
            region.nextSlide();
            return;
          }

          slide.setDirection();
          slide.feedIndex = newIndex;

          slide.currentItem = slide.external_data[newIndex];

          slide.feedTimeout(region, slide);
        }, slide.options.duration * 1000);
      };
    },

    /**
     * Run the slide.
     *
     * @param slide
     *   The slide.
     * @param region
     *   The region object.
     */
    run: function runSparkleSlide(slide, region) {
      region.itkLog.info("Running sparkle slide: " + slide.title);

      slide.feedIndex = 0;
      slide.feedIndex = (slide.feedIndex + 1) % slide.external_data.length;
      slide.currentItem = slide.external_data[slide.feedIndex];

      slide.setDirection();

      // Wait fadeTime before start to account for fade in.
      region.$timeout(function () {
        // Set the progress bar animation.
        region.progressBar.start(slide.options.duration * slide.numberOfItemsToDisplay);

        // Wait for slide duration, then show next slide.
        // + fadeTime to account for fade out.
        region.$timeout(
          function () {
            slide.feedTimeout(region, slide);
          }, slide.options.duration * 1000);
      }, region.fadeTime);
    }
  };
}
