<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('replicator_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->text('json_binlog')->nullable(false);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        $binlogStatus = DB::select('SHOW MASTER STATUS');

        $file = $binlogStatus[0]->File;
        $position = $binlogStatus[0]->Position;

        DB::table('replicator_configs')->insert([
            'id' => 1,
            'json_binlog' => json_encode(['file' => $file, 'position' => $position]),
        ]);
    }
};
