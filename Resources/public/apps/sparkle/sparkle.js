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

        scope.selectFeed = function (selectedFeed) {
          scope.slide.options.selectedFeed = selectedFeed.id;
          console.log("selectFeed", selectedFeed);
        };

        $http.get("/sparkle/feeds").then(
          function success(response) {
            $timeout(function () {
              scope.feeds = response.data.feeds;
              console.log(scope.feeds);
            });
          }
        );
      },
      templateUrl: '/bundles/os2displaysparklefeed/apps/sparkle/sparkle.html'
    };
  }
]);
