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

        $binlogStatus = DB::selectOne('SHOW MASTER STATUS');
        $file = $binlogStatus['File'];
        $position = $binlogStatus['Position'];
        $replicationModel = new ReplicatorConfig();
        // @issue https://github.com/mobilestock/backend/issues/674
        $replicationModel->id = 1;
        // @issue https://github.com/mobilestock/backend/issues/639
        $replicationModel->json_binlog = [
            'file' => $file,
            'position' => $position,
        ];
        $replicationModel->save();
    }
};
