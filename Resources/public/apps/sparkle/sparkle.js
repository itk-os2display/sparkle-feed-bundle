angular.module('sparkleModule').directive('sparkle', [
  '$timeout', '$http', function ($timeout, $http) {
    return {
      restrict: 'E',
      replace: true,
      scope: {
        slide: '=',
        close: '&'
      },
      link: function (scope, element, attrs) {
        scope.feeds = [];
        scope.saving = false;

        // Load available feeds.
        $http.get("/sparkle/feeds").then(
          function success(response) {
            $timeout(function () {
              scope.feeds = response.data.feeds;
            });
          }
        );

        /**
         * Select a feed.
         * @param selectedFeed
         */
        scope.selectFeed = function (selectedFeed) {
          scope.slide.options.selectedFeed = selectedFeed.id;
        };

        /**
         * Close the tool.
         *
         * Save the selection and load the first element for display in the administration.
         */
        scope.closeTool = function () {
          scope.saving = true;

          $http.get("/sparkle/feed/" + scope.slide.options.selectedFeed).then(
            function success(response) {
              $timeout(function () {
                if (response.data && response.data.feeds.length > 0) {
                  var first = response.data.feeds[0];

                  scope.slide.options.firstElement = {
                    'text': first.text,
                    'textMarkup': first.textMarkup,
                    'mediaUrl': first.mediaUrl,
                    'videoUrl': first.videoUrl,
                    'username': first.username,
                    'createdTime': first.createdTime,
                  };
                }

                scope.saving = false;
                scope.close();
              });
            }
          );
        };
      },
      templateUrl: '/bundles/os2displaysparklefeed/apps/sparkle/sparkle.html'
    };
  }
]);
