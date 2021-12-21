<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Meta;
use App\Proxy;
use Carbon\Carbon;

// $a = new App\Tasks\EgrulNalogStop(7811754298); $a->parse(); $a->getError();

class Task extends Model
{

  
  function __construct($metas = false) {
    if(is_array($metas) && isset($metas['inn'])){
      $this->setInn($metas['inn']);
    }
    if(is_int($metas)){
      $this->setInn($metas);
    }
    if(is_string($metas)){
      $this->setInn($metas);
    }
  }
  
  private $inn;
  public function getInn(){return $this->inn;}
  private function setInn($inn){$this->inn = $inn;}

  //Errors
  private $error = null;
  private function refreshError(){
    $this->error = null;
  }  
  public function getError(){
    return $this->error;
  }
  public function setError($err ){
    $this->error = $err;
    return false;
  }

  //Metas
  private $metas = [];
  private function getMetas(){return $this->metas;}
  private function setMetas($metas){$this->metas = $metas;}

  //Parse
  private $parse = false;
  public function getParse(){return $this->parse;}
  private function setParse($parse){$this->parse = $parse;}

  //Data
  private $data = [];
  public function getData(){return $this->data;}
  private function setData($data){$this->data = $data;}

  //Proxy
  private $proxy = false;
  public function getProxy(){return $this->proxy;}
  private function setProxy($proxy){$this->proxy = $proxy;}

  //Captcha
  public function getCaptchaDelay(){return 5;}
  
  public static function add($name, $metas){

    {//Make task
      $task = new Task;
      $task->name = $name;
      $task->status = 0;
      $task->save();
    }

    $task = Task::where('id', $task->id)->first();

    return $task;

  }

  public function run(){
    
    $metas = $this->getMetas();

    //Get Inn
    self::rr('Get Inn');
    $inn = $this->getInn();
    if(!$inn){
      $this->setError('no inn');
      return 0;
    }

    {//Check captcha
      self::rr('Check Captcha');      
      $this->refreshError();
      $captcha = $this->checkCaptcha();
      if($captcha){

        dump('captcha');

        $exludes = self::makeProxyExludeFromCaptchas($captcha);

        dump($exludes);

        $proxy = Proxy::get(false, $exludes);

        $this->setProxy($proxy->proxy);

        dump('proxy - ' . $this->getProxy());
      }

      if($captcha && $this->getProxy() == false){
        $this->setError('captcha, no proxy');
        return 0;
      } 
    }
    
    {//Parse
      self::rr('Parse');
      $this->refreshError();
      $parse = $this->parse($inn);
      if(!$parse || $parse == ""){
        if($this->getError() == null) $this->setError('no parse');
        return 0;
      }
      $this->setParse($parse);
    }
        
    {//Decode
      self::rr('Decode');
      $this->refreshError();
      $data = $this->decode($parse);
      if(!$data || $data == ""){
        if($this->getError() == null) $this->setError('no Decode data');        
        return 0;
      }
      $this->setData($data);
    }

    {//Save
      self::rr('Save');
      $this->refreshError();
      $save = $this->saveData($data);
      if(!$save){
        if($this->getError() == null) $this->setError('save data error');
        return 0;
      }
    }

    return true;

  }

  public function saveData($data){
    return true;
  }

  public function checkCaptcha($proxy = false){
    
    {//Get class name
      $classNameExplode   = explode('\\', get_class($this));
      $className          = array_pop($classNameExplode);
    }       

    //Get class captcha
    // $captcha = false;
    $captcha = (      
      Meta::
        where('metable_type', $className . "_captcha")
      ->where('value', '>', now()->add( -($this->getCaptchaDelay()), 'minutes'))
      ->get()
    );

    if(!$captcha->count()) return false;

    return $captcha;
    
    // dd($captcha);

    // if(
    //   isset($captcha->value) && 
    //   Carbon::parse($captcha->value)->diffInMinutes(now()) < $this->getCaptchaDelay()
    // ){
    //   return $this->getCaptchaDelay() - Carbon::parse($captcha->value)->diffInMinutes(now());
    // }else{
    //   return false;
    // }
    
  }

  public static function makeProxyExludeFromCaptchas($captchas){

    $exludes = [];

    foreach ($captchas as $key => $captcha) {
      if($captcha->name) array_push($exludes, $captcha->name);
    }

    return $exludes;

  }

  public static function clean($str){
    $str = str_replace(["\r", "\n", "\t"],' ',$str);    
    return str_replace("   ",' ',$str);
  }

  public static function rr($text){
    return 0;
    dump($inn . ' - ' . $text);
  }

  public function metas(){
    return $this->morphMany(Meta::class, 'metable');
  }  

}
