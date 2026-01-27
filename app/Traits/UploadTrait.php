<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait UploadTrait{

    public function upload_file(Request $request, $file_input, $dir, Model $model, $type = null, $name = null){
        $file = $request->hasFile($file_input) ? $request->file($file_input) : null;
        if(!isset($file)) return;

        $path = $file->store($dir . '/' . $model->id . '/files');
        $basename = basename($path);

        $data = [
            'filename' => $basename,
            'path' => 'data/' . $dir . '/' . $model->id . '/files/',
        ];
        if(isset($type)) $data['type'] = $type;
        if(isset($name)) $data['name'] = $name;

        // Create or update images of model
        if(isset($type) && $model->files()->where('type', $type)->count() > 0){
            $file = $model->files()->where('type', $type)->first();
            $files = scandir($file->path);
            unlink($file->path . $file->filename);
            $file->update($data);
        }else{
            $model->files()->create($data);
        }
        return $basename;
    }

}
