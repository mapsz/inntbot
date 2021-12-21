<?php

use Illuminate\Support\Facades\Route;

{//Queue
  Route::get('/add/work',         'QueueController@addWork');
  Route::get('/run/queued/task',  'QueueController@doActualTask');
}


{//Telegram

  Route::post('/telegram/hook',  function(){

    $input = file_get_contents('php://input');
    file_put_contents(storage_path() . '/data.txt', $input );
    $input = json_decode($input);

    {//Callback
      if(isset($input->callback_query)){
        App\Telegram::callback($input);
        exit;        
      }
    }

    //Check in
    if(!isset($input->message)) return 0;
    

    {//InMessage
      App\Telegram::inMessage($input);
    }


    

    
    

  })->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

  Route::get('/telegram/check',  function(){

    $body = file_get_contents('data.txt');

    $body = json_decode($body);

    // $chatId = $body->message->chat->id;
    // // dd($chatId);

    // // $inn = '1234567890';
    // // $inn = '12345678900';
    // // $inn = '123456789000';

    // $result = 0;
    // $result+=preg_match("~^[0-9]{10}$~",$inn);
    // $result+=preg_match("~^[0-9]{12}$~",$inn);


    dd($body);
    // dd($body->message->chat->id);
    dd($body->message->text);
  });

  Route::get('/telegram/set/hook',  function(){
    $link = App\Telegram::getUrl() . 'setWebHook?url=https://neosklad.ru/telegram/hook';
    $r = Http::get($link);
    dd((string) $r->getBody());
  });

}







Route::get('/test',  function(){

  dump('hi');

  $request = Illuminate\Support\Facades\Http::get('https://api.getproxylist.com/proxy');
  $response = (string) $request->getBody();
  $response = json_decode($response);
  $proxy = $response->ip . ':' . $response->port;

  //Client
  $client = new GuzzleHttp\Client([
    'base_uri' => 'http://kinozal.tv',
  ]);  

  $request = $client->request('GET', 'http://kinozal.tv', 
    [
      'proxy' => [
        'http'  => $proxy, // Use this proxy with "http"
        'https' => $proxy, // Use this proxy with "https",
      ]
    ]
  );

  $response = (string) $request->getBody();

  dd($response);

    // (new App\Tasks\ZaChestnyiBiznes())->parse(1);
});




Route::get('/', function () {
    return view('welcome');
});
