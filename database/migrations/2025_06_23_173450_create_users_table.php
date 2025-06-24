<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('username', 50);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->tinyInteger('role'); // 1 = Admin, 2 = Dosen, 3 = Mahasiswa
            $table->string('status_verifikasi')->default('Belum Terverifikasi');
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
