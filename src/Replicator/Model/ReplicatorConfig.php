<?php

namespace MobileStock\LaravelReplicator\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id;
 * @property string $json_binlog;
 */
class ReplicatorConfig extends Model
{
    public $timestamps = false;
    protected $fillable = ['json_binlog'];
}
