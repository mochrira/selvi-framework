<?php 

namespace App\Controllers;

use Selvi\Libraries\File;

class FileController {

    function upload(File $file) {
        $upload = $file->upload('file', [
            'path' => FILEPATH.'/'.date('Y').'/'.date('m'), 
            // 'name' => date('user-photo'),
            'allowedTypes' => ['image/jpg', 'image/jpeg', 'image/png']
        ]);
        return jsonResponse($upload);
    }

}