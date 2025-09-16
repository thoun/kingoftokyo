<?php
declare(strict_types=1);

namespace Bga\GameFrameworkPrototype\Helpers;

/**
 * Array utility functions.
 */
class Arrays {
    /**
     * Filter an array using the predicate function, keys are not preserved (as opposed to PHP array_filter).
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool) $fn the filtering function
     * @return array<TValue> the filtered array.
     */
    public static function filter(array $array, callable $fn): array {
        return array_values(array_filter($array, $fn));
    }

    /**
     * Count items in an array matching the predicate function.
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool) $fn the filtering function
     * @return int the number of elements matching the filter function.
     */
    public static function count(array $array, callable $fn): int {
        return count(array_filter($array, $fn));
    }

    /**
     * Filter an array to remove duplicates, keys are not preserved (as opposed to PHP array_unique).
     * If a compare function is defined, it will compare each element using this function, that should return true if elements are considered identicals.
     * If the compare function is not defined, it will fallback to PHP array_unique behavior.
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param (callable(TValue $value1, TValue $value2): bool)|null $compareFn the compare function
     * @return array<TValue> the filtered array.
     */
    public static function unique(array $array, ?callable $compareFn = null): array {
        if ($compareFn !== null) {
            $result = $array;
            $index = 1;
            while ($index < count($result)) {
                $duplicateIndex = self::findKey($result, fn($otherValue) => $compareFn($otherValue, $result[$index]));
                if ($duplicateIndex !== null && $duplicateIndex < $index) {
                    array_splice($result, $index, 1);
                    $result = array_values($result);
                } else {
                    $index++;
                }
            }
            return $result;
        } else {
            return array_values(array_unique($array));
        }
    }

    /**
     * Returns the difference between two arrays, keys are not preserved (as opposed to PHP array_diff).
     * If a compare function is defined, it will compare each element using this function, that should return true if elements are considered identicals.
     * If the compare function is not defined, the elements will be compared using strict equality.
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param array<TKey, TValue> $remove The array of elements to ignore
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool)|null $compareFn the compare function
     * @return array<TValue> the filtered array.
     */
    public static function diff(array $array, array $remove, ?callable $compareFn = null): array {
        return self::filter($array, fn($value) => !self::some($remove, fn($removedValue) => ($compareFn !== null ? $compareFn($value, $removedValue) : $value === $removedValue)));
    }

    /**
     * Map each element using the map element function (same as array_map, but with more natural parameter order).
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool) $fn the mapping function
     * @return array the mapped array.
     */
    public static function map(array $array, callable $fn): array {
        return array_map($fn, $array);
    }

    /**
     * Returns the first element matching the predicate function.
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool)|null $fn the match function
     * @return TValue|null the first found value, or null if not found.
     */
    public static function find(array $array, callable $fn): mixed {
        foreach ($array as $value) {
            if($fn($value)) {
                return $value;
            }
        }
        return null;
    }
    
    /**
     * Returns the key (=index if it's not an associative array) of the first element matching the predicate function.
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool)|null $fn the match function
     * @return TKey|null the key of the first found value, or null if not found.
     */
    public static function findKey(array $array, callable $fn): mixed {
        foreach ($array as $key => $value) {
            if($fn($value)) {
                return $key;
            }
        }
        return null;
    }
    
    /**
     * Tells if at least one element in the array matches the predicate function.
     * Returns false if the array is empty.
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool) $fn the match function
     * @return bool if there is at least one element in the array that match the condition.
     */
    public static function some(array $array, callable $fn): bool {
        foreach ($array as $value) {
            if($fn($value)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Tells if all elements in the array matches de predication function.
     * Returns true if the array is empty.
     * 
     * @template TKey of int|string
     * @template TValue
     * @param array<TKey, TValue> $array The array to iterate over
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool) $fn the match function
     * @return bool if all elements in the array that match the condition.
     */
    public static function every(array $array, callable $fn): bool {
        foreach ($array as $value) {
            if(!$fn($value)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Tells if 2 arrays are identical.
     * If a compare function is defined, it will compare each element using this function, that should return true if elements are considered identicals.
     * If the compare function is not defined, the elements will be compared using strict equality.
     * 
     * @template TValue
     * @param array<TValue> $a1 The first array
     * @param array<TValue> $a1 The second array
     * @param (callable(TValue $value): bool)|(callable(TValue $value, TKey $key): bool)|null $fn the compare function
     * @return bool if the array are the same size, and each value is the same.
     */
    public static function identical(array $a1, array $a2, ?callable $compareFn = null): bool {
        if (count($a1) != count($a2)) {
            return false;
        }
        for ($i=0;$i<count($a1);$i++) {
            if ($compareFn !== null ? (!$compareFn($a1[$i], $a2[$i])) : ($a1[$i] !== $a2[$i])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Compute all possible permutations of an array.
     * Returns an array of permutations.
     * 
     * @param array<TValue> $array The array
     * @return array<array<TValue>> the permutations
     */
    public static function permutations(array $array): array {
        $result = [];
    
        $recurse = function($array, $start_i = 0) use (&$result, &$recurse) {
            if ($start_i === count($array)-1) {
                array_push($result, $array);
            }
    
            for ($i = $start_i; $i < count($array); $i++) {
                //Swap array value at $i and $start_i
                $t = $array[$i]; $array[$i] = $array[$start_i]; $array[$start_i] = $t;
    
                //Recurse
                $recurse($array, $start_i + 1);
    
                //Restore old order
                $t = $array[$i]; $array[$i] = $array[$start_i]; $array[$start_i] = $t;
            }
        };
    
        $recurse($array);
    
        return $result;
    }
}
