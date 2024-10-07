<?php 

namespace Selvi\Libraries;

use Selvi\Exception;
use Selvi\Factory;
use Selvi\Input\Request;

class File {

    function upload($name = 'file', $params = []) {
        try {
            $request = Factory::resolve(Request::class);
            $file = $request->file($name);

            $fileType = mime_content_type($file['tmp_name']);
            if(isset($params['allowedTypes']) && $params['allowedTypes'] > 0) {
                if(!in_array($fileType, $params['allowedTypes'])) {
                    Throw new Exception('Type not allowed', 'files/invalid-type', 500);
                }
            }

            $fileSize = filesize($file['tmp_name']);
            if(isset($params['maxSize'])) {
                if($fileSize > $params['maxSize']) {
                    Throw new Exception('File too large', 'files/file-too-large', 500);
                }
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filePath = ($params['name'] ?? basename($file['name'], '.'.$ext)).'.'.$ext ?: $file['name'];
            $fullPath = $filePath;
            if(isset($params['path']) && strlen($params['path']) > 0) {
                if(!is_dir($params['path'])) {
                    mkdir($params['path'], 0777, true);
                }
                $filePath = $params['path'].'/'.$filePath;
                $fullPath = $filePath;
            }
            if(move_uploaded_file($file['tmp_name'], $fullPath)) {
                $fileInfo = pathinfo($fullPath);
                return [
                    'fileName' => $file['name'],
                    'rawName' => $fileInfo['filename'],
                    'fileExt' => $fileInfo['extension'],
                    'fileType' => $fileType,
                    'filePath' => $filePath,
                    'fullPath' => $fullPath,
                    'fileSize' => $fileSize
                ];
            } else {
                Throw new Exception('Failed to upload', 'files/unknown-error', 500);    
            }
        } catch(\Exception $e) {
            Throw new Exception($e->getMessage(), 'files/failed-to-upload', 500);
        }
    }

}