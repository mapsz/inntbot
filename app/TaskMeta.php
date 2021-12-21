<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaskMeta extends Model
{
  public static function beautify($metas){

    $bMetas = [];

    foreach ($metas as $key => $meta) {
      $bMetas[$meta->name] = $meta->value;
    }

    return $bMetas;

  }
}
