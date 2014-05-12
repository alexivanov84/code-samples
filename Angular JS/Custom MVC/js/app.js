'use strict';

angular.module('UserBoard', [
    'UserBoard.controllers',
    'UserBoard.services',
    'ngRoute',
    'angularFileUpload'
]).
config(['$routeProvider', function($routeProvider) {
    $routeProvider.
          when("/", {templateUrl: "views/home.html", controller: "homeController"}).
          when("/board", {templateUrl: "views/board.html", controller: "boardController"}).
          when("/test/upload", {templateUrl: "views/test.html", controller: "testController", action: "create"}).
          when("/test/create", {templateUrl: "views/test.html", controller: "testController", action: "create"}).
          otherwise({redirectTo: '/'});
}]);

/* Controllers*/
angular.module('UserBoard.controllers', []).
    /* Home controller */
    controller('homeController', homeController).
    /* Test controller */
    controller('testController', testController).
    /* Board controller */
    controller('boardController', boardController);


/* Services */
angular.module('UserBoard.services', []).
  /* File Uploader Service */
  factory('fileUploaderService', fileUploaderService);
