<?php

namespace Serialized\ArrayNotation;

class ObjectValue
    extends \Serialized\ObjectNotation\ObjectValue
{

    use ArrayNotationTrait;

    // constants
    protected const ITEM_DATA_CLASS = ClassMembers::class;
    protected const ITEM_NAME_CLASS = ClassName::class;


    public function __serialize(): array
    {

        return [
            $this::TYPE_NAME,
            [
                $this->name->__serialize(),
                    $this->data->__serialize(),
            ],
        ];

    }

}