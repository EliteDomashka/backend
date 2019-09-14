<?php
namespace App\Telegram\Helpers;


class TaskCropper{
    const MAX = 30;
    
    public static function crop(string $otask): array {
        $task = "";
        $desc = "";
        
        if(strlen($otask) > self::MAX){
            $line = mb_substr($otask, 0, self::MAX);
            
//            dump($line);
            $delimiters = [PHP_EOL, '. ', ', ', ';', ' '];
            foreach ($delimiters as $delimiter){
//                dump($delimiter);
                $exp = explode($delimiter, $line);
//                dump($exp);
                if(count($exp) > 1){
                    if($exp[count($exp)-1] == "") array_pop($exp);

                    $cline = $exp[count($exp)-1];

                    $pos = mb_strpos($otask, $cline);
                    $task = mb_substr($otask, 0, $pos-strlen($delimiter));
                    $desc = mb_substr($otask, $pos);
                    break; // stop foreach
                }
            }
        }else{
            $task = $otask;
        }
        
        return [$task, $desc];
    }
}
