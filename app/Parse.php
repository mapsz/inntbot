<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use GuzzleHttp\Client;
use \GuzzleHttp\Cookie\CookieJar;

use App\Query;

class Parse extends Model{


  public static function nalog($inn){

    // dd(storage_path());

    $inn = 7814735871;


    //Client
    $client = new Client([
      'base_uri' => 'http://egrul.nalog.ru',
    ]);

    //Get cookie
    $cookies = new CookieJar;
    
    $r = self::parseRequest($client, 'GET', 'https://egrul.nalog.ru', ['cookies' => $cookies], 'getCookie');


    //Post
    $pr = self::parseRequest($client, 'POST', 'https://egrul.nalog.ru', [
        'cookies' => $cookies,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36',
            'Accept'     => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Accept-Encoding' => 'gzip, deflate, br',
        ],
        'form_params' => [
          'vyp3CaptchaToken' => '',
          'page' => '',
          'query' => $inn,
          'region' => '',
          'PreventChromeAutocomplete' => '',
        ],
        'http_errors' => false,
    ], 'postInn');
    $pcon = $pr->getBody()->getContents();

    if(!isset(json_decode($pcon)->captchaRequired)){
      return -1;
    }
    
    $pt = json_decode($pcon)->t;

    //Get 
    $rg = self::parseRequest($client, 'GET', "https://egrul.nalog.ru/search-result/$pt", [
      'cookies' => $cookies
    ], 'refreshBeforeDownload');

    $gcon = $rg->getBody()->getContents();
    $gt = json_decode($gcon)->rows[0]->t;

    //Get
    $rr = self::parseRequest($client, 'GET', "https://egrul.nalog.ru/vyp-request/$gt", [
      'cookies' => $cookies,
    ], 'refreshBeforeDownload2');

    //Get file
    $rf = self::parseRequest($client, 'GET', "https://egrul.nalog.ru/vyp-download/$gt", [
      'cookies' => $cookies,
      'sink' => storage_path(time() . '.pdf')
    ], 'getFile');
    

    dump($rf->getHeaders()['Content-Type']);
    dump($rf);
    dump($rf->getBody()->getContents());
    // dump($pr, $cookies, $con);
    // dump(json_decode($con)->t);


    // $client = new \GuzzleHttp\Client();
    // $response = $client->get($url, ['save_to' => $file_path]);

  }

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
