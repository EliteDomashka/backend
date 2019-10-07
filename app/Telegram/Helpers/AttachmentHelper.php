<?php


namespace App\Telegram\Helpers;


use Longman\TelegramBot\Entities\PhotoSize;

class AttachmentHelper{
    public static function group(array $photos): ?array {
        $realPhotos = [];
        foreach ($photos as $photo){
            if($photo instanceof PhotoSize){
                if(!isset($realPhotos[$photo->getFileId()])) $realPhotos[$photo->getFileId()] = [];
                $realPhotos[$photo->getFileId()][$photo->getFileSize()] = $photo;
            }
        }
        if(empty($realPhotos)) return null;
        
        foreach ($realPhotos as &$photo_arr){
            ksort($photo_arr);
//            $photo_arr = array_values($photo_arr);
        }
        return $realPhotos;
    }
    
    public static function toType(string $type): string {
        switch ($type){
            case "PhotoSize":
                return "Photo";
                break;
            default:
                return $type;
        }
    }
}
