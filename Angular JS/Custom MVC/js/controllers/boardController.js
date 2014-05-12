var boardController = function($scope, $upload, $timeout, fileUploaderService) {
    
    $scope.fileReaderSupported = window.FileReader != null;
    $scope.uploadRightAway = true;
    $scope.changeAngularVersion = function() {
            window.location.hash = $scope.angularVersion;
            window.location.reload(true);
    }
    $scope.hasUploader = function(index) {
            return $scope.upload[index] != null;
    };
    $scope.abort = function(index) {
            $scope.upload[index].abort(); 
            $scope.upload[index] = null;
    };
    $scope.angularVersion = window.location.hash.length > 1 ? window.location.hash.substring(1) : '1.2.0';
    $scope.onFileSelect = function($files) {
            $scope.selectedFiles = [];
            $scope.progress = [];
            if ($scope.upload && $scope.upload.length > 0) {
                    for (var i = 0; i < $scope.upload.length; i++) {
                            if ($scope.upload[i] != null) {
                                    $scope.upload[i].abort();
                            }
                    }
            }
            $scope.upload = [];
            $scope.uploadResult = [];
            $scope.selectedFiles = $files;
            $scope.dataUrls = [];
            for ( var i = 0; i < $files.length; i++) {
                    var $file = $files[i];
                    if (window.FileReader && $file.type.indexOf('image') > -1) {
                            var fileReader = new FileReader();
                            fileReader.readAsDataURL($files[i]);
                            var loadFile = function(fileReader, index) {
                                    fileReader.onload = function(e) {
                                            $timeout(function() {
                                                    $scope.dataUrls[index] = e.target.result;
                                            });
                                    }
                            }(fileReader, i);
                    }
                    $scope.progress[i] = -1;
                    if ($scope.uploadRightAway) {
                            $scope.start(i);
                    }
            }
    }

    $scope.start = function(index) {
            $scope.progress[index] = 0;
            $scope.upload[index] = fileUploaderService.upload($upload, $scope.selectedFiles[index], 'system/upload.php', 'POST');
            $scope.upload[index].then(function(response) {
                    $scope.uploadResult.push(response.data);
            }, null, function(evt) {
                    $scope.progress[index] = parseInt(100.0 * evt.loaded / evt.total);
            }).xhr(function(xhr){
                    xhr.upload.addEventListener('abort', function(){console.log('aborted complete')}, false);
            });
    }
    
}


