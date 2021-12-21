<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInnStopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inn_stops', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('inn_id')->unsigned();        
            $table->integer('number')->unsigned(); //Номер решения о приостановлении
            $table->date('date'); //Дата решения о приостановлении
            $table->char('kodosnov',5); //Код основания
            $table->char('infs',12); //Код налогового органа
            $table->char('bik',12); //БИК банка, в который направлено решение
            $table->datetime('dateInfo'); //Дата и время размещения информации в сервисе (Мск)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inn_stops');
    }
}
