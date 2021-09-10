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

      slide.getDaysAgo = function (dateString) {
        var date = new Date(dateString);
        var today = new Date();

        var difference = today.getTime() - date.getTime();
        var daysAgo = parseInt(difference / 60 / 60 / 24 / 1000);

        if (daysAgo === 0) {
          return 'i dag';
        }

        return daysAgo + ' d.';
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
        slide.setDirection();

        if (!slide.currentItem) {
          region.nextSlide();
          return;
        }

        if (slide.currentItem.videoUrl) {
          region.$timeout(function () {
            slide.video = document.getElementById('sparkle-videoplayer-' + slide.uniqueId);

            function endedHandling(event) {
              region.itkLog.info("Video playback ended.", event);
              region.$timeout(function () {
                    slide.nextFeedItem(region, slide);
                  },
                  1000);
            }

            // Handle video ended.
            slide.video.removeEventListener('ended', endedHandling);
            slide.video.addEventListener('ended', endedHandling);

            function videoErrorHandling(event) {
              region.itkLog.info('Video playback error.', event);
              slide.video.removeEventListener('error', videoErrorHandling);
              slide.nextFeedItem(region, slide);
            }

            // Add/refresh error handling.
            slide.video.removeEventListener('error', videoErrorHandling);
            slide.video.addEventListener('error', videoErrorHandling);

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

        region.$timeout(function () {
          slide.fadeout = true;

          region.$timeout(function () {
            slide.fadeout = false;
            slide.feedIndex = newIndex;
            slide.currentItem = slide.external_data[newIndex];
            slide.play(region, slide);
          }, 200);
        });
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

      if (!slide.external_data || slide.external_data.length === 0) {
        region.$timeout(function () {
          region.nextSlide();
        }, 5000);
        return;
      }

      slide.feedIndex = 0;
      slide.currentItem = slide.external_data[slide.feedIndex];

      region.$timeout(function () {
        slide.setDirection();
        slide.play(region, slide);
      }, region.fadeTime);
    }
  };
}
