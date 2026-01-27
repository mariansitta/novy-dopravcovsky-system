<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'filename',
        'path',
        'type',
    ];

    protected $dates = ['deleted_at'];

    public function fileable(){
        return $this->morphTo('fileable');
    }

    public function get_path(){
        return $this->path . $this->filename;
    }

}
