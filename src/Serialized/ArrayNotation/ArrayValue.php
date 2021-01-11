<?php

namespace Serialized\ArrayNotation;

use Serialized\ObjectNotation\ArrayElement;

class ArrayValue
    extends \Serialized\ObjectNotation\ArrayValue
    implements \ArrayAccess
{
    use ArrayNotationTrait;

    public function __serialize(): array
    {

        $values = array_map(
            static function (ArrayElement $arrayValue)
            {

                return [
                    $arrayValue->name->__serialize(),
                    $arrayValue->data->__serialize(),
                ];
            },
            $this->data
        );

        return [
            $this::TYPE_NAME,
            array_values($values),
        ];

    }

}