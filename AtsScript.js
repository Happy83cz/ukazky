var ATSApp = angular.module("ATSApp", ['ngSanitize']);

ATSApp.filter('abs', function() {
  return function(num) { return Math.abs(num); }
});
ATSApp.filter('trim', function() {
  return function(s) { return s.replace(/^[\s\r\n]+|[\s\r\n]+$/gm,''); }
});
ATSApp.filter('range', function() {
  return function(list, total) {
    total = parseInt(total, 10);
    
    for (var i = 0; i < total; i++) {
      list.push(i);
    }
    return list;
  };
});
ATSApp.directive('onFinishRender', ['$timeout', '$parse', function ($timeout, $parse) {
    return {
        restrict: 'A',
        link: function (scope, element, attr) {
            if (scope.$last === true) {
              $timeout(function () {
                  scope.$emit('ngRepeatFinished');
                  if(!!attr.onFinishRender){
                    $parse(attr.onFinishRender)(scope);
                  }
              });
            }
        }
    };
}]);

ATSApp.controller("FilesController", function($scope, $http) {
  $scope.dir = "../../ats-global\.com/images"; //"..\\..\\storage";
  $scope.dirs = {};
  $scope.files = {};
  $scope.filter = "";

  $scope.load = function() {
    $http.get("ajax.php?files&dir=" + $scope.dir + "&filter=" + $scope.filter + "&a" + (10000 * Math.random()))
      .success(function (response) {
        $scope.dirs = response.dirs;
        $scope.files = response.files;
      });
  }
  $scope.load();

  $scope.changeDir = function($dir) {
    $scope.dir = $dir;
    upload_directories = $dir;
    $scope.load();
  }

  $scope.$on('ngRepeatFinished', function(ngRepeatFinishedEvent) {
    file_context_menu();
    file_loading();
  });

  $scope.selectFile = function($file) {
    if ($file.type == "image") {
      preview_image($file.fullPath);
    }
  }
});

ATSApp.controller("ParentController", function($scope, $http) {
  $scope.pages = 0;
  $scope.page = 0;
  $scope.filter = "";

  $scope.$watch('page', function(new_val) {
    if (new_val) {
      $scope.getPages();
    }
  });

  $scope.filtering = function() {
    $scope.page = 0;
    $scope.load();
  }

  $scope.enter = function(kc) {
    if (kc == 13) {
      if ($scope.categories && $scope.categories.length == 1) {
        location.href = "/CMS2/?categories&edit=" + $scope.categories[0].cat_ID;
      }
      if ($scope.articles && $scope.articles.length == 1) {
        location.href = "/CMS2/?articles&edit=" + $scope.articles[0].art_ID;
      }
    }
  }

  $scope.setPage = function(p) {
    $scope.page = p;
    $scope.load();
  }

  $scope.sortBy = function(b) {
    $scope.sort = b;
    $scope.page = 0;
    $scope.load();
  }

  $scope.getPages = function() {
    max_buttons = 10;
    start = 0;
    end = max_buttons;
    if ($scope.page > max_buttons / 2) {
      start = $scope.page - max_buttons / 2;
    }
    if ($scope.page + (max_buttons / 2 - 1) > ($scope.pages - 1)) {
      start = $scope.pages - max_buttons;
    }
    if (start < 0) {
      start = 0;
      end = $scope.pages;
    }
    if (end > $scope.pages)
      end = $scope.pages;

    p = Array.apply(0, Array(end))
        .map(function (element, index) { 
          return index + start;  
      });
      
    return p;
  }
});

ATSApp.controller("ChangesController", function($scope, $http, $controller, $window) {
  $controller('ParentController', {$scope: $scope});
  $scope.type = "";
  $scope.id = 0;
  $scope.records = [];
  $scope.columns = [];

  $scope.initC = function(id, type) {
    $scope.id = id;
    $scope.type = type;

    $scope.load();
  }

  $scope.load = function() {
    $http.get("ajax.php?changes=" + $scope.id + "&type=" + $scope.type + "&filter=" + $scope.filter + "&a" + (10000 * Math.random()))
      .success(function (response) {
        $scope.records = response.records;
        $scope.columns = response.columns;
      });
  }
});

ATSApp.controller("ArticleController", function($scope, $http, $controller, $window) {
  $controller('ParentController', {$scope: $scope});
  $scope.articles = {};
  $scope.sort = "art_ID*";
  $scope.category_filter = "";
  $scope.type = "art";

  $scope.clickToEdit = function(event, id) {
    if ((event.ctrlKey == 1) || (event.which === 2))
      $window.open("?articles&edit=" + id);
    else
      $window.location.href = "?articles&edit=" + id;
  }

  $scope.load = function() {
    $http.get("ajax.php?articles&page=" + $scope.page + "&sort=" + $scope.sort + "&filter=" + $scope.filter + "&a" + (10000 * Math.random()))
      .success(function (response) {
        $scope.articles = response.articles;
        $scope.pages = Math.ceil(response.all / 30);
        $scope.getPages();
      });
  }
  $scope.load();
});

ATSApp.controller("CategoryController", function($scope, $http, $controller) {
  $controller('ParentController', {$scope: $scope});
  $scope.categories = {};
  $scope.sort = "cat_ID";
  $scope.langs = {};
  $scope.type = "cat";

  $scope.clickToEdit = function(id) {
    location.href = "?categories&edit=" + id;
  }

  $scope.load = function() {
    $http.get("ajax.php?categories&page=" + $scope.page + "&sort=" + $scope.sort + "&filter=" + $scope.filter + "&a" + (10000 * Math.random()))
      .success(function (response) {
        $scope.categories = response.categories;
        $scope.pages = Math.ceil(response.all / 30);
        $scope.langs = response.langs;
        $scope.getPages();
      });
  }
  $scope.load();
});

ATSApp.controller("TrainingController", function($scope, $http, $controller) {
  $controller('ParentController', {$scope: $scope});
  $scope.trainings = {};
  $scope.sort = "tr_Code";
  $scope.langs = {};

  $scope.clickToEdit = function(id) {
    location.href = "?trainings&edit=" + id;
  }

  $scope.load = function() {
    $http.get("ajax.php?trainings&page=" + $scope.page + "&sort=" + $scope.sort + "&filter=" + $scope.filter + "&a" + (10000 * Math.random()))
      .success(function (response) {
        $scope.trainings = response.trainings;
        $scope.pages = Math.ceil(response.all / 30);
        $scope.langs = response.langs;
        $scope.getPages();
      });
  }
  $scope.load();
});

ATSApp.controller("TranslateController", function($scope, $http, $controller, $window) {
  $controller('ParentController', {$scope: $scope});
  $scope.translates = {};
  $scope.sort = "id*";

  $scope.clickToEdit = function(event, id) {
    if ((event.ctrlKey == 1) || (event.which === 2))
      $window.open("?translates&edit=" + id);
    else
      $window.location.href = "?translates&edit=" + id;
  }

  $scope.load = function() {
    $http.get("ajax.php?translates&page=" + $scope.page + "&sort=" + $scope.sort + "&filter=" + $scope.filter + "&a" + (10000 * Math.random()))
      .success(function (response) {
        $scope.translates = response.translates;
        $scope.pages = Math.ceil(response.all / 30);
        $scope.getPages();
      });
  }
  $scope.load();
});