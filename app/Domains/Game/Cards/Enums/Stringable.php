<?php

namespace App\Domains\Game\Cards\Enums;

interface Stringable
{
    public static function get(int|string $value): string;
}
