<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DesabilitaConsultaOcAntigoEHabilitaConsultaOcNovo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            UPDATE configuracoes.db_itensmenu 
            SET 
                libcliente = true
            WHERE funcao ilike 'com3_ordemdecompra001.php'
                AND libcliente = false
        ");

        DB::statement("
            UPDATE configuracoes.db_itensmenu 
            SET 
                libcliente = false
            WHERE funcao ilike 'emp3_ordemcompra001.php'
                AND libcliente = true
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("
            UPDATE configuracoes.db_itensmenu 
            SET 
                libcliente = false
            WHERE funcao ilike 'com3_ordemdecompra001.php'
                AND libcliente = true
        ");

        DB::statement("
            UPDATE configuracoes.db_itensmenu 
            SET 
                libcliente = true
            WHERE funcao ilike 'emp3_ordemcompra001.php'
                AND libcliente = false
        ");
    }
}
