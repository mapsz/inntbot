<?php

namespace App\Tasks;

use GuzzleHttp\Client;
use \GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;

use App\Query;
use App\Task;
use App\Parse;
use App\Inn;
use App\InnStop;
use App\Meta;
use Carbon\Carbon;

// 780110866402   no stops
// 7811754298     got stops



class EgrulNalogStop extends Task{

  private $captchaDelay = 3;

  public static function exampleGotRows(){
    return json_decode('{"datePRS":"24.11.2021 12:25:10","formToken":"196BE9CEB8D29858974CD6BC99DB3D78D53E1C2B1EC6EA67D93BBFE8E0DDAB4A9E53CF01AD54852281CEB56E2CA2EEC56F840788910E42227AB6419C0034DDD38E68E45BAD8F1AAFFC8112DAF8A86F19","innPRS":"7811754298","bikPRS":"111111111","rows":[{"R":1,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.06.2021","BIK":"044030786","NOMER":"41889","DATABI":"01.06.2021 12:45:10","KODOSNOV":"02","TOKEN":"995BF4F0E3A7D1DAC1D6E55E767895E4C7E80AE13185EFF863DFC800B899A2F3FB51CE617BE7715130B7751CC114936E277F4B2844A203064F5F134335C783D2","ID":-1},{"R":2,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.09.2021","BIK":"044030786","NOMER":"66855","DATABI":"01.09.2021 17:05:12","KODOSNOV":"02","TOKEN":"3D2AF9CC33A8AA2FA47569A19021B7F27BC8105886283F40AE840702DF5CB77D","ID":-1},{"R":3,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"04.10.2021","BIK":"044030786","NOMER":"76866","DATABI":"05.10.2021 16:55:12","KODOSNOV":"02","TOKEN":"444647B893A0865F061B3BC5C82655713E38BE23722A918CAC36CDF94AB96804ADE02203ABFDA70796DF3D35568A54FB3572BFCB34C6B239A0D68DDDF28C5E15","ID":-1},{"R":4,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.06.2021","BIK":"044030795","NOMER":"41892","DATABI":"01.06.2021 12:45:10","KODOSNOV":"02","TOKEN":"F96CE5FA329F41FE447E9119681C6CC7F6EA4D487576CFAEAE858BFCC3068FC5FB51CE617BE7715130B7751CC114936E5DA34BCA5379CA21EDF21AD1083D27AF","ID":-1},{"R":5,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.09.2021","BIK":"044030795","NOMER":"66858","DATABI":"01.09.2021 17:05:12","KODOSNOV":"02","TOKEN":"0FADE1937FD8E468C503A6F4BBB9C7A002CDC86994E6052AE90113F6BE88EC3B889BEDAD8F3B41B86D59836CA499824F4145383E99B2050571142B328C2BF03B","ID":-1},{"R":6,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"04.10.2021","BIK":"044030795","NOMER":"76869","DATABI":"05.10.2021 16:55:12","KODOSNOV":"02","TOKEN":"1F1936D6E9AF335FFE6CF21F69190166EA8C274F33DBC609B5DEC8E6918F4637","ID":-1},{"R":7,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.06.2021","BIK":"044525411","NOMER":"41891","DATABI":"01.06.2021 12:45:10","KODOSNOV":"02","TOKEN":"EA259EFA71B1DC99EAA2095C824C16F3CC14E38260B7FB2F6AD99C4BBFEC2D9F8B977E268C3E9C72AFA3675C16BA5E2B","ID":-1},{"R":8,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.09.2021","BIK":"044525411","NOMER":"66857","DATABI":"01.09.2021 17:05:12","KODOSNOV":"02","TOKEN":"13DB11E5DF38D58D7B5D601039A22B17A61E8329C35E14179A218B90086822957CB23CCE2DD9AC377D5494BB775E4C93","ID":-1},{"R":9,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"04.10.2021","BIK":"044525411","NOMER":"76868","DATABI":"05.10.2021 16:55:12","KODOSNOV":"02","TOKEN":"61A1035E6AF7D8AFD186B18E825DCB27ADE02203ABFDA70796DF3D35568A54FB87B663BF4392FD5B5BF5EDBEC0D6BBB2","ID":-1},{"R":10,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.06.2021","BIK":"045805772","NOMER":"41890","DATABI":"01.06.2021 12:45:10","KODOSNOV":"02","TOKEN":"DB95CB351CDB41DED82BB38FD008FA7BFF9112712DD5651A619B44AE09E785C9","ID":-1},{"R":11,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.09.2021","BIK":"045805772","NOMER":"66856","DATABI":"01.09.2021 17:05:12","KODOSNOV":"02","TOKEN":"7BC1390A01A985A224914A8D3C7C8C7DBB6DAF2A2EDDD5AA65AAAAD22874CF8DD14DC94ED3AE1A9B4AE6FFA09B765BF1","ID":-1},{"R":12,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"04.10.2021","BIK":"045805772","NOMER":"76867","DATABI":"05.10.2021 16:55:12","KODOSNOV":"02","TOKEN":"DE17F4684CC89EDDB5195F9FB0E365F6A4D06CC8F84374811039F20A63717F1F","ID":-1},{"R":13,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.06.2021","BIK":"049205805","NOMER":"41888","DATABI":"01.06.2021 12:45:10","KODOSNOV":"02","TOKEN":"777F2E013D6A85A6CD3F2E2D94856236DD9042BC2D12C67875A36B20C608BFF919F6008AFF76F05BD31B10524D1AD429","ID":-1},{"R":14,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"01.09.2021","BIK":"049205805","NOMER":"66854","DATABI":"01.09.2021 17:05:12","KODOSNOV":"02","TOKEN":"215F3B44E8E1A0FAD3CCE2EA10407FFF25D3578C859EADCACFE3ACD36DFF5140BD1846E0EAFCFA6198C12C506911C2EBE00CB2FBAAE43FF44C4DCB8CCF2E9A83","ID":-1},{"R":15,"INN":"7811754298","NAIM":"\u041e\u041e\u041e \"\u0421\u0422\u0420\u041e\u0419-\u041f\u0420\u0415\u0421\u0422\u0418\u041621\"","IFNS":"7811","DATA":"04.10.2021","BIK":"049205805","NOMER":"76865","DATABI":"05.10.2021 16:55:12","KODOSNOV":"02","TOKEN":"05922F0D04CD7EDDB0450F3A35014138DD9042BC2D12C67875A36B20C608BFF9A680164DB70A0AF0A20AD64520D6ED90","ID":-1}],"captchaRequired":true,"queryType":"FINDPRS"}');
  }

  public function parse(){

    dump("parse");

    $inn = $this->getInn();

    //Client
    $client = new Client([
      'base_uri' => 'https://service.nalog.ru/bi.do',
    ]);  
    
    {//Get cookie
      $cookies = new CookieJar;
      Parse::parseRequest($client, 'GET', 'https://service.nalog.ru/bi.do', ['cookies' => $cookies], 'getCookie', $this->getProxy());
    }
    
    {//Get stops
      $r = Parse::parseRequest($client, 'POST', 'https://service.nalog.ru/bi2-proc.json', [
        'cookies' => $cookies,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36',
            'Accept'     => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Accept-Encoding' => 'gzip, deflate, br',
        ],
        'form_params' => [
          'requestType' => 'FINDPRS',
          'innPRS' => $inn,
          'bikPRS' => 111111111,
          'fileName' => '','bik' => '','kodTU' => '','dateSAFN' => '','bikAFN' => '','dateAFN' => '',
          'fileNameED' => '','captcha' => '','captchaToken' => '',
        ],
          'http_errors' => false,
      ], 'getStops', $this->getProxy());

      //Check for errors
      if(!$r){
        $this->setError('Bad response');
        return 0;
      }

      //Decode
      $decodedR = (string) $r->getBody();
      $decodedR = json_decode($decodedR);

      //Check for errors
      if(json_last_error() !== 0 || gettype($decodedR) != 'object'){
        $this->setError('Bad response');
        return 0;
      }
    }

    dump("parse result");
    dump($decodedR);

    return $decodedR;
  }

  public function decode($parse){

    // $parse = self::exampleGotRows();

    {//Form data
      $data = [];
      if(isset($parse->rows) && is_array($parse->rows)){      
        foreach ($parse->rows as $key => $v) {  
          array_push($data, 
            [
              'inn_id'    => $v->INN,
              'number'    => $v->NOMER,
              'date'      => Carbon::createFromFormat('d.m.Y', $v->DATA)->format('Y-m-d'),
              'kodosnov'  => $v->KODOSNOV,
              'infs'      => $v->IFNS,
              'bik'       => $v->BIK,
              'dateInfo'  => Carbon::createFromFormat('d.m.Y H:i:s', $v->DATABI)->format('Y-m-d H:i:s'),
            ]
          );
        }
      }
    }

    if(count($data) == 0) $data = 'no stops';

    return $data;

  }

  public function saveData($data){

    $inn = $this->getInn();

    //Save Detail Stops
    if(is_array($data)){
      foreach ($data as $k => $row) {
        
        {// Check exists
          $innStop = new InnStop;
          foreach ($row as $k => $v) {
            if($k == 'dateInfo') continue;
            $innStop = $innStop->where($k, $v);
          }
          if($innStop->exists()) continue;
        }
  
        //Save
        $innStop = new InnStop;
        $innStop->fill($row);
        $innStop->save();
      }
    }

    //Get stops count
    $stopCount = InnStop::getStopCount($inn);
    $stopCount = $stopCount == 0 ? 'no' : $stopCount;
    dump('stop count - ' . $stopCount);

    //Save Meta Stops
    return Inn::DBUpdate($this->getInn(), ['stops' => $stopCount]);
    
  }


}