<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

use App\Queue;
use App\Meta;
use App\Inn;


class Telegram extends Model
{

  public static function getToken(){
    return "2061389685:AAGcqdTFBIqTPXIzNNpG2GSWYETIp-mI78Q";
  }
  public static function getUrl(){
    return "https://api.telegram.org/bot" . self::getToken() . "/";
  }

  public static function send($id, $text ,$buttons = false){
    
    //Params
    $params = [
      "chat_id" => $id,
      "text" => $text,
      'parse_mode' => 'html'
    ];    

    //Buttons
    if($buttons)
      $params["reply_markup"] = $buttons;

    $send = self::getUrl() . 'sendMessage';

    $r = Http::post($send, $params);   

    $responseText = json_decode((string) $r->getBody());

    if($r->getStatusCode() == 200) return $responseText->result->message_id;

    return false;

  }

  public static function edit($id, $messageId, $text, $buttons = false){

    //Params
    $params = [
      "chat_id" => $id,
      "message_id" => $messageId,
      "text" => $text,
      'parse_mode' => 'html'
    ];
      
    $send = self::getUrl() . 'editMessageText';
    $r = Http::post($send, $params);

    dump(json_decode((string) $r->getBody()));
    
    //Buttons
    if($buttons){
      $params["reply_markup"] = $buttons;
      $send = self::getUrl() . 'editMessageReplyMarkup';
      $r = Http::post($send, $params);

      dump($buttons);
      dump('buttons');
      dump(json_decode((string) $r->getBody()));
    }

    // dump(json_decode((string) $r->getBody()));

    return $r;
      
    if($r->getStatusCode() == 200) return true;

    return false;

  }

  public static function answerCallbackQuery($id, $text){

    //Params
    $params = [
      "callback_query_id" => $id,
      "text" => $text,
    ];

    $send = self::getUrl() . 'answerCallbackQuery';
    $r = Http::post($send, $params);

    dump((string) $r->getBody());
      
    if($r->getStatusCode() == 200) return true;

    return false;

  }

  public static function buttons($buttons){
    //Single button
    if(isset($buttons['text'])) $buttons = [$buttons];

    $reply_markup = json_encode([
      "inline_keyboard" => [
        $buttons
      ]
    ]);

    return $reply_markup;   

  }

  public static function inMessage($input){

    //Chat id
    $chatId = $input->message->chat->id;

    {//Inn
      $inn = $input->message->text;
      $inn = str_replace("/", "", $inn);
      $result = 0;
      $result+=preg_match("~^[0-9]{10}$~",$inn);
      $result+=preg_match("~^[0-9]{12}$~",$inn);  
      if(!$result){
        Telegram::send($chatId, 'bad inn');
        return;
      }
    }

    $messageId = Telegram::send($chatId, 'Loading...', self::buttons(["text" => 'ping',"callback_data" => 'ping']));
    Queue::addWork('innShow', $inn, ['chatId' => $chatId, 'messageId' => $messageId,]);
  }

  public static function callback($input){
    //Get params
    $queryId = $input->callback_query->id;
    $chatId = $input->callback_query->message->chat->id;
    $data = $input->callback_query->data;

    //Answer
    Telegram::answerCallbackQuery($queryId, "🐠🐠");

    if($data == 'ping'){
      Queue::doActualTask();
      return;
    }

    if($data == 'captcha'){
      Telegram::captchaSend($chatId);
      return;
    }   

    if(strpos($data, 'track_') !== false){
      $toTrack = str_replace('track_' , '', $data);
      Telegram::send($chatId, $toTrack . ' - tracking 🟢');
      return;
    }
    
    if(strpos($data, 'stopTrack_') !== false){
      $toTrack = str_replace('stopTrack_', '', $data);
      Telegram::send($chatId, $toTrack . ' - stop tracking 🔴');
      return;
    }

    
    Telegram::send($chatId, 'Loading...');

    $data = json_decode($input->callback_query->data);

    if(!$data || !isset($data->work)){
      Telegram::send($chatId, 'no work');
    }

    if(isset($data->metaId)){
      $meta = Meta::find($data->metaId);
      $inn = json_decode($meta->value);
    }

    Queue::addWork($data->work, $inn, $chatId);    


    exit;
  }

  public static function innSend($chatId, $messageId, $inn){

    dump('telegram ' . $chatId . 'innSend ' . $inn);

    $mInn = Inn::jGet($inn);

    $toSend = self::formInnMessageText($mInn);
    $toSend .= "\n" . self::formStopsMessageText($mInn);
    $toSend .= "\n" . self::formMemberInMessageText($mInn);

    $buttons = self::buttons([
      [
        "text" => 'Track',
        "callback_data" => "track_$inn"
      ],
      [
        "text" => 'Stop track',
        "callback_data" => "stopTrack_$inn"
      ],
      [
        "text" => 'ping',
        "callback_data" => 'ping'
      ],
      [
        "text" => 'captcha',
        "callback_data" => 'captcha'
      ],

    ]);

    $send = Telegram::edit($chatId, $messageId, $toSend, $buttons);

    //Check if ok
    if($send->getStatusCode() == 200) return true;

    //Check same message
    if($send->getStatusCode() == 400){
      $decodedSend = json_decode((string) $send->getBody());
      if($decodedSend->description == "Bad Request: message is not modified: specified new message content and reply markup are exactly the same as a current content and reply markup of the message"){
        return true;
      }
    }

    return false;

  }

  public static function formInnMessageText($mInn){
    
    //Check exists
    if(!Inn::_exists($mInn)){
      $text = "<b>ИНН:</b> " . $mInn->inn . "\n";
      $text .= "\nLoading...";
      return $text;
    }

    $noData = '👀';

    if($mInn->isEntity){//Entity
      {//Название
        $text['full_name'] = "<b>";
        $text['full_name'] .= Meta::get($mInn->metas, 'full_name') == null ? $noData : Meta::get($mInn->metas, 'full_name');
        $text['full_name'] .= "</b>\n";
      }

      {//ИНН
        $text['inn'] = "<b>ИНН:</b> " . $mInn->inn . "\n";
      }

      {//Members
        $text['members'] = "<b>Members:</b> ";

        $buffer = Meta::get($mInn->metas, 'members');
        $buffer = json_decode($buffer);
        if(is_array($buffer)){
          foreach ($buffer as $member) {
            $text['members'] .= "\n/" . $member;
          }      
        }

        $text['members'] .= "\n";
      }

    }else{//Individual
      
      {//ФИО
        $text['FIO'] = "<b>";
        $text['FIO'] .= (
          (Meta::get($mInn->metas, 'lastname') == null ? "" : Meta::get($mInn->metas, 'lastname')) . " " .
          (Meta::get($mInn->metas, 'firstname') == null ? "" : Meta::get($mInn->metas, 'firstname')) . " " .
          (Meta::get($mInn->metas, 'fathersname') == null ? "" : Meta::get($mInn->metas, 'fathersname'))
        );
        $text['FIO'] .= "</b>";
        $text['FIO'] .= "\n";
      }
      
      {//ИНН
        $text['inn'] = "<b>ИНН:</b> " . $mInn->inn . "\n";
      }
      
      {//ОГРНИП
        $text['ogrip'] = "<b>ОГРНИП:</b> ";
        $text['ogrip'] .= Meta::get($mInn->metas, 'ogrip') == null ? $noData : Meta::get($mInn->metas, 'ogrip');
        $text['ogrip'] .= "\n";
      }

      {//Дата регистрации
        $text['DataRegistracii'] = "<b>Дата регистрации:</b> ";
        $text['DataRegistracii'] .= Meta::get($mInn->metas, 'DataRegistracii') == null ? $noData : Meta::get($mInn->metas, 'DataRegistracii');
        $text['DataRegistracii'] .= "\n";
      }
      
      {//Наименование регистрирующего органа
        $text['NaimenovanieRegistrirujushegoOrgana'] = "<b>Наименование регистрирующего органа:</b> ";
        $text['NaimenovanieRegistrirujushegoOrgana'] .= Meta::get($mInn->metas, 'NaimenovanieRegistrirujushegoOrgana') == null ? $noData : Meta::get($mInn->metas, 'NaimenovanieRegistrirujushegoOrgana');
        $text['NaimenovanieRegistrirujushegoOrgana'] .= "\n";
      }    
      
      {//Вид деятельности 
        $text['SvedenieOVidahEkonomicheskojDejatelnosti'] = "<b>Вид деятельности:</b> ";
        $text['SvedenieOVidahEkonomicheskojDejatelnosti'] .= Meta::get($mInn->metas, 'SvedenieOVidahEkonomicheskojDejatelnosti') == null ? $noData : Meta::get($mInn->metas, 'SvedenieOVidahEkonomicheskojDejatelnosti');
        $text['SvedenieOVidahEkonomicheskojDejatelnosti'] .= "\n";
      }      
      
      {//Сведения о прекращении деятельности
        $text['SvedenieOPrekrashenieDejatelnosti'] = "<b>Сведения о прекращении деятельности:</b> ";

        $buffer = Meta::get($mInn->metas, 'SvedenieOPrekrashenieDejatelnosti');
        $buffer = json_decode($buffer);
        if($buffer){
          $text['SvedenieOPrekrashenieDejatelnosti'] .= isset($buffer->data) ? $buffer->data : "";
          $text['SvedenieOPrekrashenieDejatelnosti'] .= isset($buffer->sposob) ? " " . $buffer->sposob : "";
        }

        $text['SvedenieOPrekrashenieDejatelnosti'] .= "\n";
      }

      {//Сведения о состоянии индивидуального предпринимателя
        $text['SvedenieOSostojanieIP'] = "<b>Сведения о состоянии индивидуального предпринимателя:</b> ";

        $buffer = Meta::get($mInn->metas, 'SvedenieOSostojanieIP');
        $buffer = json_decode($buffer);
        if($buffer){
          $text['SvedenieOSostojanieIP'] .= isset($buffer->data) ? $buffer->data : "";
          $text['SvedenieOSostojanieIP'] .= isset($buffer->sposob) ? " " . $buffer->sposob : "";
        }

        $text['SvedenieOSostojanieIP'] .= "\n";
      }

    }

    //Date
    $text['updated'] = "<i>" . $mInn->updated_at . "</i>\n";

    $toSend = "";

    foreach ($text as $key => $v) {
      $toSend .= $v;
    }

    return $toSend;

  }

  public static function formMemberInMessageText($inn){

    $text = "<b>memberIn:</b> ";

    //Check exists
    if(!Inn::memberInExists($inn)){
      $text .= "\nLoading...";
      return $text;
    }      

    $buffer = Meta::get($inn->metas, 'memberIn');
    $buffer = json_decode($buffer);
    if(is_array($buffer)){
      foreach ($buffer as $member) {
        $text .= "\n/" . $member;
      }      
    }

    $text .= "\n";
    //Date
    $text .= "<i>" . Meta::get($inn->metas, 'memberIn', 1)->updated_at . "</i>\n";

    
    return $text;
    
  }    

  public static function formStopsMessageText($inn){

    $text = "<b>Приостановлении:</b> ";

    //Check exists
    if(!Inn::stopsExists($inn)){
      $text .= "\nLoading...\n";
      return $text;
    }      

    $stops = Meta::get($inn->metas, 'stops');
    $text .= $stops == 'no' ? 'нет' : 'да'."($stops)";

    $text .= "\n";
    //Date
    $text .= "<i>" . Meta::get($inn->metas, 'stops', 1)->updated_at . "</i>\n";

    
    return $text;

  }

  public static function captchaSend($chatId){

    $captchas = Meta::where('metable_type','captcha')->get();

    $text = "Captchas \n";
    foreach ($captchas as $key => $v) {
      $text .= $v->name . ' - ' . $v->value;
      $text .= "\n";
    }

    $text .= "👾";

    Telegram::send($chatId, $text);

  }

  

}
