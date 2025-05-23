<?php

namespace App\Traits\Enum;

trait EnumTrait
{
    public static function cases(): array
    {
        return self::cases();
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function valuesWithoutOnClick(): array
    {
        return array_filter(self::values(), fn ($value) => $value !== 'onclick');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getAllNames(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function toArray(): array
    {
        $array = [];
        foreach (self::cases() as $case) {
            $array[$case->name] = $case->value;
        }

        return $array;
    }
}
