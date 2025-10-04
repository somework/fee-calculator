<?php

namespace SomeWork\MonetaryCalculator\Helpers;

class IdentifierNormalizer
{
    public static function normalize(mixed $identifier): string
    {
        if (is_string($identifier)) {
            return $identifier;
        }

        if (is_int($identifier)) {
            return (string) $identifier;
        }

        if (is_object($identifier)) {
            if (method_exists($identifier, '__toString')) {
                return (string) $identifier;
            }

            return sprintf(
                '%s@%s',
                $identifier::class,
                spl_object_id($identifier)
            );
        }

        if (is_null($identifier)) {
            return 'null';
        }

        if (is_resource($identifier)) {
            return sprintf(
                'resource@%s',
                get_resource_id($identifier)
            );
        }

        throw new \InvalidArgumentException('Unsupported identifier type: ' . gettype($identifier));
    }
}