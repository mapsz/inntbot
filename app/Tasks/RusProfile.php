<?php

namespace App\Tasks;

use GuzzleHttp\Client;
use \GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;

use App\Query;
use App\Task;
use App\Parse;
use App\Inn;

class RusProfile extends Task{

  public function getCaptchaDelay(){return 60;}

  public function parse($inn){
    
    //Client
    $client = new Client([
      'base_uri' => ' https://www.rusprofile.ru',
    ]);  
    
    {//Parse
      $r = Parse::parseRequest($client, 'GET', "https://www.rusprofile.ru/search?query=$inn", [], 'getData', $this->getProxy());      
      if(!$r){
        return 0;
      } 
    }

    return (string) $r->getBody();

  }

  public function decode($parse){
    
    //Clean
    $parse = self::clean($parse);
    $parse = str_replace('  ','',$parse);

    {//Get inns
      $inns = [];
      $crawler = new Crawler($parse);
      $crawler = $crawler->filter('.company-item > .company-item-info');
      foreach ($crawler as $key => $dom) {
        $val = $dom->nodeValue;
        if(strpos($val, 'ИНН') !== false){
          foreach ($dom->childNodes as $child) {
            $val = $child->nodeValue;
            if(strpos($val, 'ИНН') !== false){
              $inn = str_replace(['ИНН'," "],'',$val);
              array_push($inns, $inn);
            }
          }
        }
      }        
    }

    return $inns;
  }

  public function saveData($data){
    return Inn::DBUpdate($this->getInn(), ['memberIn' => $data]);
  }

}