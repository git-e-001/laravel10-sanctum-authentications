<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;


trait LogMaker
{
    public function make_log(string $message, Exception $e, string $func = 'error')
    {
        Log::channel($func == 'error' ? 'stack' : 'slack')->{$func}($message, [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);
    }
}
