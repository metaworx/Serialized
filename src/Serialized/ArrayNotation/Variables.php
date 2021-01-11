<?php

namespace Serialized\ArrayNotation;

class Variables
    extends \Serialized\ObjectNotation\Variables
{
    use ArrayNotationTrait;

    // constants

    protected const ITEM_DATA_CLASS = VariableValue::class;


    public function __serialize(): array
    {

        $variables = array_map(
            static function (VariableValue $arrayValue)
            {

                return [
                    $arrayValue->name->__serialize(),
                    $arrayValue->data->__serialize(),
                ];
            },
            $this->data
        );

        return [$this::TYPE_NAME, array_values($variables),];
    }

}