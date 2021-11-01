<?php

namespace App\Tasks;

use GuzzleHttp\Client;
use \GuzzleHttp\Cookie\CookieJar;

use App\Query;
use App\Task;
use App\Parse;
use App\Inn;



class EgrulNalog extends Task{

    private $site = 'egrul.nalog.ru';

    private $error = null;

    private function refreshError(){
      $this->error = null;
    }

    public function getError(){
      return $this->error;
    }

    public function run($metas){
      
      //Refresh errors
      $this->refreshError();

      //Get Inn
      if(!isset($metas['inn'])){
        $this->error = 'no Inn';
        return 0;
      }
      $inn = $metas['inn'];

      //Parse
      $fileName = $this->parse($inn);
      if(!$fileName) return 0;

      // $this->pdfDecode($fileName);

      return 1;
      
    }

    public function parse($inn){
      //Client
      $client = new Client([
        'base_uri' => 'http://egrul.nalog.ru',
      ]);  
      
      {//Get cookie
        $cookies = new CookieJar;
        $r = Parse::parseRequest($client, 'GET', 'https://egrul.nalog.ru', ['cookies' => $cookies], 'getCookie');
      }   

      dd(11,$cookies);
      
      {//Post Inn
        $pr = Parse::parseRequest($client, 'POST', 'https://egrul.nalog.ru', [
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
        $pcon = (string) $pr->getBody();          
        $pcon = json_decode($pcon);
  
        //Check json
        if (json_last_error() !== 0) {
          $this->error = 'Get post token json error';
          return 0;
        }
      }

      {//Check errors
        if(isset($pcon->ERRORS)){
          $this->error = json_encode($pcon->ERRORS);
          return 0;
        }
      }

      {//Check captcha
        if(isset($pcon->captchaRequired) && $pcon->captchaRequired){
          $this->error = 'captcha';
          return 0;
        }
      }
      
      
      {//Post token  
        //Check token in data
        if(!isset( $pcon->t)){
          $this->error = 'Get post token no token';
          return 0;
        }  
        $postToken = $pcon->t;
      }
        
      {//Get token
        $rg = Parse::parseRequest($client, 'GET', "https://egrul.nalog.ru/search-result/$postToken", [
          'cookies' => $cookies
        ], 'getToken');
        $gcon = (string) $rg->getBody();
        $gcon = json_decode($gcon);

        //Check json
        if (json_last_error() !== 0) {
          $this->error = 'Get token json error';
          return 0;
        }

        //Check token in data
        if(!isset($gcon->rows) || !isset($gcon->rows[0]) || !isset($gcon->rows[0]->t)){
          $this->error = 'Get token no token';
          return 0;
        }

        $getToken = $gcon->rows[0]->t;
      }
        
      {//Refresh required before download
        $rr = Parse::parseRequest($client, 'GET', "https://egrul.nalog.ru/vyp-request/$getToken", [
          'cookies' => $cookies,
        ], 'refreshBeforeDownload');
      }
  
      //Get file
      $fileName = storage_path(time() . '.pdf');
      $rf = Parse::parseRequest($client, 'GET', "https://egrul.nalog.ru/vyp-download/$getToken", [
        'cookies' => $cookies,
        'sink' => $fileName
      ], 'getFile');

      return $fileName;
        
    }

    // private function pdfDecode($fileName){
    public function pdfDecode($fileName){
      $fileName = 'c:\OpenServer\domains\inntbot.loc\storage\1635154795.pdf';
            
      {//Get text from file
        // Parse pdf file and build necessary objects.
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($fileName);
        
        // Retrieve all pages from the pdf file.
        $pages  = $pdf->getPages();
        
        $text = '';
        // Loop over each page to extract text.
        foreach ($pages as $page) {
          $text .= $page->getText();
        }
      }

      {//Check Inn exists
        $exists = $text;
        $exists = self::clean($exists);

        if(strpos($exists, 'не является индивидуальным  предпринимателем') !== false){
          $this->error = 'bad Inn';
          return 0;
        }

      }
        
      {//Check type
        $type = false;
        
        $fizLico = strpos($text, 'налогоплательщика (ИНН)');
        if($fizLico !== false){ $type = 'fiz'; }

        $jurLico = strpos($text, 'ИНН юридического лица');
        if($jurLico !== false){ $type = 'jur'; }

        
        if($type === false){
          $this->error = 'bad Type';
          return 0;
        }
      }
      
      {//Get Attributes
        dump($type);
        $attributes = [];
        $attributes = $type == 'fiz' ? $this->textToAttributesFiz($text) : $this->textToAttributesJur($text);

        if(!$attributes) return 0;
      }


      Inn::make($attributes['inn'], $attributes);

      // $details  = $pdf->getDetails();
      // dd($text, $attributes);
      dd(11);
      // dd($attributes, $text);

    }

    private function textToAttributesFiz($text){

      $attributes = [];

      //Inn
      $attributes['inn'] = self::getAttr($text, 'налогоплательщика (ИНН)', 45, 'Дата постановки на учет', 5);
      
      {//FIO
        $fio = self::getAttr($text, 
          'Фамилия, имя, отчество (при наличии) индивидуального предпринимателя', 170,
          'ГРН и дата внесения в ЕГРИП записи', 3
        );

        // dd($fio);
        $fio = str_replace("\n",'/',$fio);
        $fio = str_replace("\t",'',$fio);
        $fioArr = explode('/',$fio);

        $attributes['firstname'] = $fioArr[0];
        $attributes['lastname'] = $fioArr[1];
        $attributes['fathersname'] = $fioArr[2];
      }
      
      {//Register Date
        $buffer = self::getAttr($text, 'Сведения о регистрации индивидуального предпринимателя', 100);
        $attributes['registerDate'] = self::getAttr(
          $buffer, 'Дата регистрации', 32, 'Сведения о регистрирующем органе по месту ж', 2
        );
      }    

      {//Сведения об учете в налоговом органе
        $buffer = self::getAttr($text, 'Сведения об учете в налоговом органе', 100);
        $buffer = self::getAttr($buffer, 'Наименование налогового органа', 59, 'ГРН и дата внесения в ЕГРИП', 5);
        $buffer = str_replace("\n",' ',$buffer);
        $buffer = str_replace("\t",'',$buffer);
        $buffer = str_replace("Санкт- Петербургу",'Санкт-Петербургу',$buffer);
        $attributes['SvedenieObUchete'] = $buffer;
      }

      {//Сведения о видах экономической деятельности по Общероссийскому классификатору  видов экономической деятельности
        $buffer = self::getAttr($text, 'Сведения о видах экономической деятельности', 100);
        $buffer = self::getAttr($buffer, 'Код и наименование вида деятельности', 68, 'ГРН и дата внесения в ЕГРИП записи', 5);
        $buffer = str_replace("\n",' ',$buffer);
        $buffer = str_replace("\t",'',$buffer);
        $attributes['SvedenieOVidahEkonomicheskojDejatelnosti'] = $buffer;
      }

      {//Сведения о состоянии индивидуального предпринимателя
        $attributes['SvedenieOSostojanieIP'] = [];
        $attributes['SvedenieOSostojanieIP']['sostojanie']    = 'отсутствуют';
        $attributes['SvedenieOSostojanieIP']['data']          = 'отсутствуют';
        $pos = strpos($text, 'Сведения о состоянии индивидуального предпринимателя');
        if($pos !== false){
          // dump('Сведения о состоянии индивидуального предпринимателя');
        }
        
      }

      {//Сведения о прекращении деятельности в качестве индивидуального предпринимателя
        $attributes['SvedenieOPrekrashenieDejatelnosti'] = [];
        $attributes['SvedenieOPrekrashenieDejatelnosti']['sposob']  = 'отсутствуют';
        $attributes['SvedenieOPrekrashenieDejatelnosti']['data']    = 'отсутствуют';
        $pos = strpos($text, 'Сведения о прекращении деятельности в качестве индивидуального предпринимателя');
        if($pos !== false){

          $buffer2 = self::getAttr($text, 'Сведения о прекращении деятельности в качестве индивидуального предпринимателя', 0, 'Идентификационный номер');

          {//Sposob
            $pos = strpos($buffer2, 'Способ прекращения');
            if($pos !== false){
              $buffer = self::getAttr($buffer2, 'Способ прекращения', 36, 'Дата прекращения деятельности', 5);
              $buffer = str_replace("\n",' ',$buffer);
              $buffer = str_replace("\t",'',$buffer);
              $attributes['SvedenieOPrekrashenieDejatelnosti']['sposob'] = $buffer;
            }
          }
          
          {//Data
            $pos = strpos($buffer2, 'Дата прекращения деятельности');
            if($pos !== false){
              $buffer = self::getAttr($buffer2, 'Дата прекращения деятельности', 56, "\t\n", 0);
              $buffer = str_replace("\n",' ',$buffer);
              $buffer = str_replace("\t",'',$buffer);
              $attributes['SvedenieOPrekrashenieDejatelnosti']['data'] = $buffer;
            }
          }

        }


      }

      dd( $attributes);

      return $attributes;

    }

    private function textToAttributesJur($text){
      $this->error = 'Jur to do';
      return 0;
    }


    private static function getAttr($text, $start, $startNumber, $end = false, $endNumber = 0){

      $pos = strpos($text, $start);

      $attr = substr($text, $pos + $startNumber);

      if(!$end) return $attr;

      $endPos = strpos($attr, $end);

      $attr = substr($attr, 0, $endPos - $endNumber);

      return $attr;

    }

    private static function clean($buffer){
      $buffer = str_replace("\n",' ',$buffer);
      return str_replace("\t",'',$buffer);

    }

}