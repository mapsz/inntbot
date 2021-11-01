<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Parse;

use App\Tasks\EgrulNalog;


class ParseController extends Controller{


  public function test(Request $request){

    $test = new EgrulNalog;

    // $test->run('1233213123');

    // dd($test->getError());
    // $test->run('7811715010');
    // $test->run('7814735871');
    // $test->run('780604379335');
    // $test->run('7814735871');

  }


}
