<?php

namespace App\Traits;

trait DeleteTrait{

    public function delete_files($file_collection){
        foreach ($file_collection as $file){
            //unlink($file->path . $file->filename);
            $file->delete();
        }
    }

    public function delete_file($file){
        //unlink($file->path . $file->filename);
        $file->delete();
    }

}
