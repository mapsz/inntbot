<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use GuzzleHttp\Client;
use \GuzzleHttp\Cookie\CookieJar;

use App\Query;
use App\Meta;
use App\Proxy;

class Parse extends Model{

  public static function parseRequest($client, $method, $link, $params, $name = -1, $proxy = false){
    
    {//Log Query
      $q = new Query;
      $q->link = $link;
      $q->name = $name == -1 ? $link : $name;
    };
    
    if($proxy){//Set proxy
      //Set params
      $params['proxy'] = [
        'http'  => $proxy,
        'https' => $proxy,
      ];
      //Set query
      $q->proxy = $proxy;
    }
    
    {//Request
      $a = false;
      try {
        $request = $client->request($method, $link, $params);
        $response = (string) $request->getBody();
        $q->status = $request->getStatusCode();
      } catch (\Exception $e) {
        $response = 'something gone wrong';
        $q->status = -1;
        if(strpos($e->getMessage(), 'truncated...')){
          $response = 'Truncated';
        }
        if(strpos($e->getMessage(), 'Too Many Requests')){
          $response = 'Too Many Requests';
        }
      }      
      
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

    {//Log Query Result
      $q->response = $response;
      $q->save();
    }

    //Check captcha
    if(self::captcha($response, $link, $proxy)) return 0; 

    {//Proxy report
      if($proxy) if($q->status != -1) Proxy::success($proxy); else Proxy::fail($proxy);
    }

    
    if(!isset($request)) return 0;
    if($q->status < 1) return 0;
    


    return $request;
  }

  public static function captcha($response, $link, $proxy = false){

    //Egrul
    if(strpos($link, "egrul.nalog.ru")){
      if(strpos($response, "ERRORS") && strpos($response, "captcha")){
        Meta::updateOrCreate(
          [
            'metable_id' => '1',
            'metable_type' => 'EgrulNalog_captcha',
            'name' => $proxy ? $proxy : '',
          ],
          ['value' => now()]
        );
        dump("egrul.nalog.ru - captcha");
        return 1;
      }
    }elseif(strpos($link, "rusprofile.ru")){
      if(strpos($response, "Many Requests")){
        Meta::updateOrCreate(
          [
            'metable_id' => '1',
            'metable_type' => 'RusProfile_captcha',
            'name' => $proxy ? $proxy : '',
          ],
          ['value' => now()]
        );
        dump("rusprofile.ru - captcha");
        return 1;
      }
    }elseif(strpos($link, "service.nalog.ru")){
      if(strpos($response, "Требуется ввести цифры с картинки")){
        Meta::updateOrCreate(
          [
            'metable_id' => '1',
            'metable_type' => 'EgrulNalogStop_captcha',
            'name' => $proxy ? $proxy : '',
          ],
          ['value' => now()]
        );
        dump("EgrulNalogStop - captcha");
        return 1;
      }      
    }


    return 0;

  }


}