<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ScalarType;

class Objects extends ScalarType
{
    public $name = 'Objects';

    public function serialize ($value)
    {
        return $value;
    }

    public function parseValue ($value)
    {
        return $value;
    }

    public function parseLiteral ($valueNode, array $variables = null)
    {
        return $valueNode->value;
    }
}