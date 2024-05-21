<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class Firmware extends Migration
{
    public function up()
    {
        $capsule = new Capsule();
        $capsule::schema()->create('firmware', function (Blueprint $table) {
            $table->increments('id');
            $table->string('serial_number')->unique();
            $table->string('machine_model')->nullable();
            $table->string('boot_rom_version')->nullable();
            $table->string('boot_rom_latest')->nullable();
            $table->integer('boot_rom_outdated')->nullable();
            $table->string('hardware_model')->nullable();
            $table->string('ibridge_version')->nullable();
            $table->string('ibridge_latest')->nullable();
            $table->integer('ibridge_outdated')->nullable();

            $table->index('serial_number');
            $table->index('machine_model');
            $table->index('boot_rom_version');
            $table->index('boot_rom_latest');
            $table->index('boot_rom_outdated');
            $table->index('hardware_model');
            $table->index('ibridge_version');
            $table->index('ibridge_latest');
            $table->index('ibridge_outdated');
        });
    }

    public function down()
    {
        $capsule = new Capsule();
        $capsule::schema()->dropIfExists('firmware');
    }
}
