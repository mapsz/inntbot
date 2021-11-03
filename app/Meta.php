<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
  public $guarded = [];

  // public static function beautify($metas){

  //   $fMetas = [];
  //   foreach ($metas as $key => $value) {
  //     $fMetas[$key] = $value;
  //   }
    
  //   return $fMetas;

  // }

  public static function get($metas, $toGet){
    foreach ($metas as $key => $meta) {
      if($meta['name'] == $toGet) return $meta['value'];
    }
  }
  
  public function metable(){
    return $this->morphTo();
  }
}