<?php

namespace App\Form;

use Symfony\Component\Form\DataTransformerInterface;

class StringToArrayTransformer implements DataTransformerInterface
{
    public function transform($array): mixed
    {
        if (null === $array) {
            return '';
        }
        return implode(', ', $array); // Transforme array -> string
    }

    public function reverseTransform($string): mixed
    {
        if (!$string) {
            return [];
        }
        return array_map('trim', explode(',', $string)); // Transforme string -> array
    }
}