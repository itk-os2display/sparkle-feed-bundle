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
      'use strict';

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

      slide.play = function (region, slide) {
        if (slide.currentItem.videoUrl) {
          region.$timeout(function () {
            slide.video = document.getElementById('sparkle-videoplayer-' + slide.uniqueId);

            // Handle video ended.
            slide.video.removeEventListener('ended', slide.video.onended);
            slide.video.onended = function ended(event) {
              region.itkLog.info("Video playback ended.", event);
              region.$timeout(function () {
                  slide.nextFeedItem(region, slide);
                },
                1000);
            };

            // Add/refresh error handling.
            slide.video.removeEventListener('error', slide.video.onerror);
            slide.video.onerror = function videoErrorHandling(event) {
              region.itkLog.info('Video playback error.', event);
              slide.video.removeEventListener('error', videoErrorHandling);
              slide.nextFeedItem(region, slide);
            };

            slide.video.play();
          });
        }
        else {
          slide.feedTimeout(region, slide);
        }
      };

      slide.nextFeedItem = function (region, slide) {
        var newIndex = (slide.feedIndex + 1) % slide.numberOfItemsToDisplay;

        if (newIndex === 0) {
          region.nextSlide();
          return;
        }

        slide.setDirection();
        slide.feedIndex = newIndex;
        slide.currentItem = slide.external_data[newIndex];
        slide.play(region, slide);
      };

      slide.feedTimeout = function (region, slide) {
        region.$timeout(function () {
          slide.nextFeedItem(region, slide);
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
      'use strict';

      region.itkLog.info("Running sparkle slide: " + slide.title);

      slide.feedIndex = 0;
      slide.currentItem = slide.external_data[slide.feedIndex];

      slide.setDirection();

      region.$timeout(function () {
        slide.play(region, slide);
      }, region.fadeTime);
    }
  };
}
