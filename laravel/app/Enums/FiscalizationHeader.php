<?php

namespace App\Models\Fiscalization;

use Carbon\Carbon;
use Illuminate\Support\Str;

class FiscalizationHeader
{
    public string $uuid;
    public string $sentAt;

    public function __construct()
    {
        $this->uuid = (string) Str::uuid();
        $this->sentAt = Carbon::now()->toIso8601String();
    }
}

?>