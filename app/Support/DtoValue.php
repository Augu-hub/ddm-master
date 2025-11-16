<?php
namespace App\Support;

final class DtoValue
{
    public static function get($dto, $names, $default = null)
    {
        $candidates = is_array($names) ? $names : [$names];
        $expanded = [];
        foreach ($candidates as $name) {
            $expanded[] = $name;
            $expanded[] = self::snakeToCamel($name);
            $expanded[] = self::camelToSnake($name);
        }
        $candidates = array_values(array_unique(array_filter($expanded)));

        if (is_array($dto)) {
            foreach ($candidates as $key) if (array_key_exists($key, $dto)) return $dto[$key];
        }
        if ($dto instanceof \ArrayAccess) {
            foreach ($candidates as $key) if ($dto->offsetExists($key)) return $dto[$key];
        }
        if (is_object($dto) && method_exists($dto, 'toArray')) {
            $arr = $dto->toArray();
            if (is_array($arr)) foreach ($candidates as $key) if (array_key_exists($key, $arr)) return $arr[$key];
        }
        if (is_object($dto)) {
            foreach ($candidates as $key) {
                $g = 'get'.self::studly($key);
                $i = 'is'.self::studly($key);
                if (method_exists($dto, $g)) return $dto->{$g}();
                if (method_exists($dto, $i)) return $dto->{$i}();
            }
        }
        if (is_object($dto)) {
            foreach ($candidates as $key) if (isset($dto->{$key}) || property_exists($dto, $key)) return $dto->{$key};
        }
        return $default;
    }

    private static function snakeToCamel(string $v): string { return lcfirst(str_replace(' ', '', ucwords(str_replace(['-','_'],' ',$v)))); }
    private static function camelToSnake(string $v): string { return strtolower(preg_replace('/(?<!^)[A-Z]/','_$0',$v)); }
    private static function studly(string $v): string { return str_replace(' ', '', ucwords(str_replace(['-','_'],' ',$v))); }
}
