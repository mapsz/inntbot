<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InnStop extends Model
{
  //About rows
    // number //Номер решения о приостановлении
    // date //Дата решения о приостановлении
    // kodosnov //Код основания
    // infs //Код налогового органа
    // bik //БИК банка, в который направлено решение
    // dateInfo //Дата и время размещения информации в сервисе (Мск)  

  public $guarded = [];

  public static function getStopCount($inn){
    return self::where('inn_id', $inn)->count();
  }
  
}
