<?php

namespace App\Tasks;

use GuzzleHttp\Client;
use \GuzzleHttp\Cookie\CookieJar;

use App\Query;
use App\Task;
use App\Parse;
use App\Inn;



class ZaChestnyiBiznes extends Task{


  public function parse($inn){
    //Client
    $client = new Client([
      'base_uri' => 'https://zachestnyibiznes.ru',
    ]);  
  
    
    {//Get cookie
      $cookies = new CookieJar;
      $r = Parse::parseRequest($client, 'GET', 'https://zachestnyibiznes.ru', ['cookies' => $cookies], 'getCookie');
    }

    // $r = Parse::parseRequest($client, 'GET', "https://zachestnyibiznes.ru/site/ajax-login", [
    //   'cookies' => $cookies,
    // ], 'refreshBeforeDownload');

    $response = (string) $r->getBody();

    $csrf = substr($response, strpos($response,'<meta name="csrf-token" content="') + 33, 88);

    dump('csrf: '.$csrf);

    $r = Parse::parseRequest($client, 'GET', "https://zachestnyibiznes.ru/site/ajax-login", [
      'cookies' => $cookies,
      'headers' => [
        'x-csrf-token' => $csrf,
        'x-requested-with' => 'XMLHttpRequest',
      ],
    ], 'refreshBeforeDownload');

    $response = (string) $r->getBody();

    $csrf2 = substr($response, 16, 88);

    dd('csrf2: '.$csrf2);

    // dd((string) $r->getBody());

    // __gads=ID=0ec6d6c1566c6ce6-226cc84902cb00ad:T=1635528138:RT=1635528138:S=ALNI_MZQUWa2sdDL_nxzfdjABH8xHu9vUQ;
    //  _ym_uid=1635528176905290825; 
    //  _ym_d=1635528176; 
    //  _csrf-zchb=a3a1aaae31babb813d37b59424bfd4ef32a9f027c6b8055028b068f08b40154da%3A2%3A%7Bi%3A0%3Bs%3A10%3A%22_csrf-zchb%22%3Bi%3A1%3Bs%3A32%3A%22yAlw7qOepQYIdsffAIub6EsNRqeonqlm%22%3B%7D; 
    //  _ym_isad=1; 
    // advanced-zchb=n6p8v8l5ufkbjb904oc7ss6cc8; 
    // cf_chl_prog=b

    dd($cookies);

    $rr = Parse::parseRequest($client, 'GET', "https://zachestnyibiznes.ru/search?query=780604379335", [
      'cookies' => $cookies,
      'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36',
          'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
          'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
          // 'accept-encoding' => 'gzip, deflate, br',
          'accept-language' => 'en-GB,en;q=0.9,ru-RU;q=0.8,ru;q=0.7,en-US;q=0.6,lv;q=0.5',
          'upgrade-insecure-requests' => '1',
          'sec-ch-ua' => 'Google Chrome";v="95", "Chromium";v="95", ";Not A Brand";v="99',
          'sec-ch-ua-mobile' => '?0',
          'sec-ch-ua-platform' => 'Windows',
          'sec-ch-ua-dest' => 'document',
          'sec-ch-ua-mode' => 'navigate',
          'sec-ch-ua-site' => 'same-origin',
          'sec-ch-ua-user' => '?1',
      ],
    ], 'refreshBeforeDownload');

    $response = (string) $r->getBody();

    // strpos($response,"ТК ЛАГУНА");

    dd(strpos($response,"ТК ЛАГУНА"));
  
  }


}