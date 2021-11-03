<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use GuzzleHttp\Client;
use \GuzzleHttp\Cookie\CookieJar;

use App\Query;

class Parse extends Model{


  public static function parseRequest($client, $method, $link, $params, $name = -1){
    
    {//Log Query
      $q = new Query;
      $q->link = $link;
      $q->name = $name == -1 ? $link : $name;
    };
    
    {//Request
      $a = false;
      try {
        $request = $client->request($method, $link, $params);
      } catch (\Exception $e) {
        if(strpos($e->getMessage(), 'truncated...')){
          $q->status  = -1;
          $q->response = 'truncated';
          $q->save();
          return 0;
        }

        return 0;
      }
      
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

    {//Log Result
      $q->status = $request->getStatusCode();
      $q->response = $response;
      $q->save();
    }

    return $request;
  }

}
