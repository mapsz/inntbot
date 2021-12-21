<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class Proxy extends Model{

  public static function success($proxy){

    //Get model
    if(!isset($proxy->id)) $proxy = Proxy::where('proxy', $proxy)->first();

    //Edit status
    $proxy->status = $proxy->status > 50 ? $proxy->status : $proxy->status+1;
    return $proxy->save();

  }

  public static function fail($proxy){

    //Get model
    if(!isset($proxy->id)) $proxy = Proxy::where('proxy', $proxy)->first();
    
    //Edit status
    $proxy->status = $proxy->status < -50 ? $proxy->status : $proxy->status-1;
    $proxy->failed = now();
    return $proxy->save();
    
  }

  public static function get($payed = false, $exclude = []){

    //Get from DB
    $proxy = self::getFromDB($payed, $exclude);

    //Generate and get
    if(!$proxy){
      self::generate();
      $proxy = self::getFromDB($payed, $exclude);
    }

    if(!$proxy) return false;

    return $proxy;
  }

  public static function preQuery($payed, $excludes){

    $pre = Proxy::where('payed', $payed)->orderBy('status', 'DESC')->orderBy('created_at', 'DESC');

    foreach ($excludes as $key => $exclude) {
      $pre = $pre->where('proxy', '<>', $exclude);
    }
    
    return $pre;
  }
  
  private static function getFromDB($payed, $exclude){
        
    do {//Get from DB

      //failed = null
      $proxy = self::preQuery($payed, $exclude)->where('failed', NULL)->first();
      if($proxy) break;

      //failed > hour
      $proxy = self::preQuery($payed, $exclude)->where('failed', '<', Carbon::now()->add(-1,'hour'))->first();
      if($proxy) break;      
      
    } while (0);

    return $proxy;
  }

  private static function generate(){

    //Scrab
    $proxies = self::scrab();
    if(!$proxies) return false;
    
    //Save
    foreach($proxies as $proxy){
      self::new($proxy);
    }

    return true;

  }

  public static function new($value){

    $proxy = new Proxy;
    $proxy->proxy = $value;
    $proxy->status = 1;
    $proxy->payed = 0;
    $proxy->save();

    return $proxy;

  }

  private static function scrab(){

    //Get proxy
    do{

      $proxy = self::getPubproxy();
      if($proxy) break;

      $proxy = self::getProxyorbit();
      if($proxy) break;

    }while(0);

    if(!$proxy) return false;

    //To array
    if(!is_array($proxy)){
      $proxy = [$proxy];
    } 

    return $proxy;

  }  

  private static function getPubproxy(){

    //http://pubproxy.com/api/proxy

    $get = Http::get('http://pubproxy.com/api/proxy');
    $response = (string) $get->getBody();
    $response = json_decode($response);
    if(!$response) return false;
    if(!isset($response->data)) return false;

    //Form proxies
    $proxies = is_array($response->data) ? $response->data : $response->data[0];
    if(!isset($proxies[0]->ipPort)) return false;

    $proxiesToReturn = [];
    foreach ($proxies as $proxy) {
      if(isset($proxy->ipPort)) array_push($proxiesToReturn, $proxy->ipPort);      
    }
    
    if(count($proxiesToReturn)) return false;

    return $proxiesToReturn;

  }

  private static function getProxyorbit(){
    // xifac17459@videour.com
    // karto6ka
    
    $get = Http::get('https://api.proxyorbit.com/v1/?token=nTG0FqTIEFZpXXpBoQDB4FFofWJn4deTIHObrhvE9sw&protocols=http');
    $response = (string) $get->getBody();
    $response = json_decode($response);
    if(!$response) return false;
    if(!isset($response->curl)) return false;
    if(strpos($response->curl, 'ttp://') === false) return false;
 
    $proxy = str_replace('http://','',$response->curl);

    return $proxy;

  }



}