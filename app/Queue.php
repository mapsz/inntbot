<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Telegram;
use App\Task;
use App\Meta;
use App\Tasks\EgrulNalog;
use App\Tasks\EgrulNalogStop;
use App\Tasks\ZaChestnyiBiznes;
use App\Tasks\RusProfile;
use Carbon\Carbon;
use App\Inn;

class Queue extends Model
{
  public static function addWork($work, $inn, $metas = []){

    switch ($work) {
      case 'innShow':
        $inns = $inn;
        if(!is_array($inns)) $inns = json_decode($inns);        
        if(is_int($inns)) $inns = [$inns];

        foreach ($inns as $vInn) {
          self::addWork('FullInnUpdate', $vInn);

          $task = new Task;
          $task->name = 'innShow';
          $task->status = 0;
          $task->save();
          $task->metas()->create(['name' => 'inn', 'value' => $vInn]);
          $task->metas()->create(['name' => 'chat_id', 'value' => $metas['chatId']]);
          $task->metas()->create(['name' => 'message_id', 'value' => $metas['messageId']]);

          $task = new Task;
          $task->name = 'memberInShow';
          $task->status = 0;
          $task->save();
          $task->metas()->create(['name' => 'inn', 'value' => $vInn]);
          $task->metas()->create(['name' => 'chat_id', 'value' => $metas['chatId']]);
          $task->metas()->create(['name' => 'message_id', 'value' => $metas['messageId']]);
          
          $task = new Task;
          $task->name = 'stopsShow';
          $task->status = 0;
          $task->save();
          $task->metas()->create(['name' => 'inn', 'value' => $vInn]);
          $task->metas()->create(['name' => 'chat_id', 'value' => $metas['chatId']]);
          $task->metas()->create(['name' => 'message_id', 'value' => $metas['messageId']]);
        }

      break;
      case 'FullInnUpdate':

        try {
          DB::beginTransaction();

          {//Egrul
            $task = new Task;
            $task->name = 'InnUpdate';
            $task->status = 0;
            $task->priority = 11;
            $task->save();
            $task->metas()->create(['name' => 'inn', 'value' => $inn]);
          }

          {//Members
            $task = new Task;
            $task->name = 'MemberInUpdate';
            $task->status = 0;
            $task->priority = 9;
            $task->save();
            $task->metas()->create(['name' => 'inn', 'value' => $inn]);
          }

          {//Stops
            $task = new Task;
            $task->name = 'StopsUpdate';
            $task->status = 0;
            $task->priority = 10;
            $task->save();
            $task->metas()->create(['name' => 'inn', 'value' => $inn]);
          }

          //Store to DB
          DB::commit();    
        } catch (Exception $e) {
          // Rollback from DB
          DB::rollback();
          die($e);
        }

      break;
      default:
        # code...
        break;
    }

    self::doActualTask();

  }

  public static function doActualTask(){
    $task = self::getActualTask();
    if(!$task) return;

    self::runTask($task);

    $task = Task::find($task->id);
    
    if($task->status == 1){
      self::doActualTask();
    }
  }

  public static function getActualTask(){

    $task = Task::with('metas')
    ->where('status', '<', '1')
    ->orderBy('status','DESC')
    ->orderBy('priority','DESC')
    ->orderBy('created_at','ASC')
    ->first();

    if(!$task){dump('no task'); return false;}

    return $task;

  }

  public static function runTask($task){

    //Get task
    if(!isset($task->id)){ 
      if(!$task) return 0;
      $task = Task::with('metas')->find($task);
    }

    {//Log
      $log = new Log;
      $log->name = $task->name;
      $log->task_id = $task->id;
      $log->save();
    }

    dump('task - ' . $task->id . " " .  $task->name);

    switch ($task->name) {
      case 'InnUpdate':

        {//Get inn
          $inn = Meta::get($task->metas, 'inn');
          $mInn = Inn::getByInn($inn);
        }

        $run = true;        

        //Check up to date
        if($run && Inn::isUpToDate($inn, 30)){
          dump('up to date!');
          $log->result = 'success';
          $task->status = 1;
          break;
        }    
        
        {//Update
          $eTask = new EgrulNalog($inn);
          $run = $eTask->run();
          if(!$run){
            $log->result = $eTask->getError();
          }            
          dump('update - ');
          dump($run);
        }

        //Check exists
        if($run && !Inn::_exists($inn)){
          dump('!not exists');
          $log->result = '!not exists';
          $run = false;
        }

        //Check up to date
        if($run && !Inn::isUpToDate($inn)){
          dump('!not up to date');
          $log->result = '!not up to date';
          $run = false;
        }        

        {//Log
          {//task
            $task->status = $run ? 1 : $task->status-1;       
          }
          //Log
          if(!$run){            
            $log->status = 0;
          }
        }

      break;
      case 'MemberInUpdate':

        {//Get inn
          dump('Get inn');
          $inn = Meta::get($task->metas, 'inn');
          dump($inn);
          $mInn = Inn::where('inn', $inn)->first();
          //Make Inn
          if(!$mInn) Inn::DBUpdate($inn);
        }

        //Track error
        if(!$mInn){
          //task
          $task->status--;
          $log->status = 0;
          $log->result = 'no inn';
          break;
        }

        
        {//Member In task
          dump('Member in');
          {//Recent updated
            $update = true;
            $memberIn = Meta::where('metable_type', 'App\Inn')->where('name', 'memberIn')->where('metable_id', $mInn->id)->orderBy('updated_at','DESC')->first();
            if($memberIn && Carbon::now()->diffInMinutes($memberIn->updated_at) < 60*24){
              dump('Recent updated');
              $update = false;
            }  
          }
          if($update){
            $inTask = $mInn->isEntity ? new ZaChestnyiBiznes($inn) : new RusProfile($inn);
            $run = $inTask->run();
          }else{
            $run = true;
          }
        }


        dump('run - ' . $run);

        {//Log          
          {//task
            $task->status = $run ? 1 : $task->status-1;       
          }
          //Log
          if(!$run){
            $log->status = 0;
            $log->result = $inTask->getError();
          }
        }


      break;
      case 'StopsUpdate':

        dump('Stops Update');

        {//Get inn
          dump('Get inn');
          $inn = Meta::get($task->metas, 'inn');
          dump($inn);
          $mInn = Inn::where('inn', $inn)->first();
          //Make Inn
          if(!$mInn) Inn::DBUpdate($inn);
        }

        //Track error
        if(!$mInn){
          //task
          $task->status--;
          $log->status = 0;
          $log->result = 'no inn';
          break;
        }

        
        {//Recent updated
          $update = true;
          $stops = Meta::where('metable_type', 'App\Inn')->where('name', 'stops')->where('metable_id', $mInn->id)->orderBy('updated_at','DESC')->first();
          if($stops && Carbon::now()->diffInMinutes($stops->updated_at) < 60*24){
            dump('Recent updated');
            $update = false;
          }  
        }

        //Update
        if($update){
          $EgrulNalogStop = new EgrulNalogStop($inn);
          $run = $EgrulNalogStop->run();
        }else{
          $run = true;
        }

        dump('run - ' . $run);

        {//Log          
          {//task
            $task->status = $run ? 1 : $task->status-1;       
          }
          //Log
          if(!$run){
            $log->status = 0;
            $log->result = $EgrulNalogStop->getError();
          }
        }


      break;
      case 'innShow':

        $do = true;

        {//Get inn
          $inn = Meta::get($task->metas, 'inn');
          $inn = Inn::getByInn($inn);
        }

        dump('inn - '. $inn);

        //Check exists
        if($do && !Inn::_exists($inn)){
          dump('!not exists');
          $do = false;
        }

        //Check up to date
        if($do && !Inn::isUpToDate($inn)){
          dump('!not up to date');
          $do = false;
        }

        //Do
        $r = false;
        if($do) $r = Telegram::innSend(Meta::get($task->metas, 'chat_id'), Meta::get($task->metas, 'message_id'), Meta::get($task->metas, 'inn'));
        
        {//Log
          {//task
            if($r) $task->status = 1;
            else $task->status--;
          }
        }

      break;
      case 'memberInShow':

        $do = true;

        {//Get inn
          $inn = Meta::get($task->metas, 'inn');
          $inn = Inn::getByInn($inn);
        }

        dump('inn - '. $inn);

        //Check exists
        if($do && !Inn::memberInExists($inn)){
          dump('!not exists');
          $do = false;
        }

        //Check up to date
        if($do && !Inn::memberInIsToDate($inn)){
          dump('!not up to date');
          $do = false;
        }

        //Do
        $r = false;
        if($do) $r = Telegram::innSend(Meta::get($task->metas, 'chat_id'), Meta::get($task->metas, 'message_id'), Meta::get($task->metas, 'inn'));
        
        {//Log
          {//task
            if($r) $task->status = 1;
            else $task->status--;
          }
        }

      break;
      case 'stopsShow':

        $do = true;

        {//Get inn
          $inn = Meta::get($task->metas, 'inn');
          $inn = Inn::getByInn($inn);
        }

        dump('inn - '. $inn);

        //Check exists
        if($do && !Inn::stopsExists($inn)){
          dump('!not exists');
          $do = false;
        }

        //Check up to date
        if($do && !Inn::stopsIsToDate($inn)){
          dump('!not up to date');
          $do = false;
        }

        //Do
        $r = false;
        if($do) $r = Telegram::innSend(Meta::get($task->metas, 'chat_id'), Meta::get($task->metas, 'message_id'), Meta::get($task->metas, 'inn'));
        
        {//Log
          {//task
            if($r) $task->status = 1;
            else $task->status--;
          }
        }

      break;
      default:
        {//Log          
          //task
          $task->status--;
          $log->status = 0;
          $log->result = 'bad task';
        }
      break;
    }

    {//Log result
      //Task
      $task->save();

      $log->status = $log->status === null ? 1          : $log->status;
      $log->result = $log->result === null ? 'success'  : $log->result;
      $log->save();
    }

    dump($log);

    return true;
  }


  
}