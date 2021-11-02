<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
  public $guarded = [];
  
  public function metable(){
    return $this->morphTo();
  }
}