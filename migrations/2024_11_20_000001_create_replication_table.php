<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('replication', function (Blueprint $table) {
            $table->increments('id');
            $table->text('json_binlog')->nullable(false);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::table('replication')->insert([
            'id' => 1,
            'json_binlog' => '{"file": null, "position": null}',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('replication');
    }
};
