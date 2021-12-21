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

    //Get data
    $rr = Parse::parseRequest($client, 'GET', "https://zachestnyibiznes.ru/search?query=780604379335", [
      'query' => ['query' => '780604379335'],
      'headers' => [
          // ':authority'=> 'zachestnyibiznes.ru',
          // ':method'=> 'GET',
          // ':path'=> '/search?query=780604379335',
          // ':scheme'=> 'https',
          'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
          'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
          // 'accept-encoding' => 'gzip, deflate, br',
          'accept-language' => 'en-GB,en;q=0.9,ru-RU;q=0.8,ru;q=0.7,en-US;q=0.6,lv;q=0.5',
          'cache-control' => 'no-cache',
          'pragma' => 'no-cache',
          'referer'  => 'https://zachestnyibiznes.ru/',
          'sec-ch-ua' => 'Google Chrome";v="95", "Chromium";v="95", ";Not A Brand";v="99',
          'sec-ch-ua-mobile' => '?0',
          'sec-ch-ua-platform' => 'Windows',
          'sec-ch-ua-dest' => 'document',
          'sec-ch-ua-mode' => 'navigate',
          'sec-ch-ua-site' => 'same-origin',
          'sec-ch-ua-user' => '?1',
          'upgrade-insecure-requests' => '1',
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36',
      ],
    ], 'getData');
    if(!$rr) return 0;

    return (string) $rr->getBody();

    // strpos($response,"ТК ЛАГУНА");

    // echo $response;
  
  }

  public function decode($parse){
    //clear string
    $parse = str_replace(["\n","\t"], '', $parse);

    //Get lines
    $pattern = '`itemprop="legalName".*?</div><div`ui';
    $result = preg_match_all($pattern, $parse, $lines);
    $lines = $lines[0];
    
    //Get data from lines
    $inns = [];
    foreach ($lines as $key => $value) {
      //Refresh
      $inn = false; $name = false;

      //Get data
      preg_match("`>(.*?)</`",$value,$name);
      preg_match("`ИНН</.*?>.*?>(.*?)</`",$value,$inn);

      //Save data
      if(isset($inn[1]) && $inn[1] > 0){
        array_push($inns, $inn[1]);
      }
      
    }

    return $inns;
  }

  public function saveData($data){
    return Inn::DBUpdate($this->getInn(), ['memberIn' => $data]);
  }


}