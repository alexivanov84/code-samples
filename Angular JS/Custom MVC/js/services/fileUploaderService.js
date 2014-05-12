var fileUploaderService = function($http) {

    var boardAPI = {};

    boardAPI.upload = function($upload, file, url, method) {
        return $upload.upload({
            url: url, //upload.php script, node.js route, or servlet url
            method: method, //POST or PUT,
            // headers: {'header-key': 'header-value'},
            withCredentials: true,
            file: file, // or list of files: $files for html5 only
            /* set the file formData name ('Content-Desposition'). Default is 'file' */
            //fileFormDataName: myFile, //or a list of names for multiple files (html5).
            /* customize how data is added to formData. See #40#issuecomment-28612000 for sample code */
            formDataAppender: function(formData, key, val){}
          });
    }

    return boardAPI;
}

