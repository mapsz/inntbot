<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use GuzzleHttp\Client;
use \GuzzleHttp\Cookie\CookieJar;

use App\Query;

class Parse extends Model{


  public static function parseRequest($client, $method, $link, $params, $name = -1){
    
    {//Request
      $request = $client->request($method, $link, $params);
      $response = (string) $request->getBody();
    }    
    
    {//Check response
      //File
      if(isset($params['sink']) && $params['sink'] != ''){
        $response = $params['sink'];
      }

      //Response > 65k
      if((strlen($response)*8) > 65000){
        $response = '>65k';
      }
    }

    {//Log Query
      $q = new Query;
      $q->link = $link;
      $q->status = $request->getStatusCode();
      $q->name = $name == -1 ? $link : $name;
      $q->response = $response;
      $q->save();
    };

    return $request;
  }

}
