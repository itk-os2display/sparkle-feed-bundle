angular.module('sparkleModule').directive('sparkle', [
  '$timeout', function ($timeout) {
    return {
      restrict: 'E',
      replace: true,
      scope: {
        slide: '=',
        close: '&'
      },
      link: function (scope, element, attrs) {
        // @TODO
      },
      templateUrl: '/bundles/os2displaysparklefeed/apps/sparkle/sparkle.html'
    };
  }
]);
