angular.module('WhoPlayMusic')
.directive('filterTest', function(Artist) {
  return {
    replace: true,
    restrict: "E",
    scope: {
      selected: "=",
    },
    templateUrl: '/templates/directives/filter-test.html',
    link: function(scope, element, attrs) {
    }
  };
});