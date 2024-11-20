<?php

namespace MobileStock\LaravelReplicator\Interceptor;

use Illuminate\Support\Facades\App;

class InterceptorManager
{
    public static function applyInterceptor(
        array $interceptorFunction,
        array $data,
        string $nodePrimaryTable,
        string $nodePrimaryDatabase
    ): array {
        return App::call($interceptorFunction, [
            'data' => $data,
            'nodePrimaryTable' => $nodePrimaryTable,
            'nodePrimaryDatabase' => $nodePrimaryDatabase,
        ]);
    }
}
