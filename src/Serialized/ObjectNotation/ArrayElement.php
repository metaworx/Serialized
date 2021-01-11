<?php

namespace Serialized\ObjectNotation;

/**
 * @property \Serialized\ObjectNotation\IntValue|\Serialized\ObjectNotation\StringValue $name
 * @property  \Serialized\Value                                                         $data
 */
class ArrayElement
    implements SimpleValueInterface, NamedValueInterface
{
    use NamedValueTrait;

    /**
     * ArrayElement constructor.
     *
     * @param  \Serialized\ObjectNotation\IntValue|\Serialized\ObjectNotation\StringValue  $key
     * @param  \Serialized\Value                                                           $value
     */
    public function __construct(
        $key,
        \Serialized\Value $value
    ) {

        $this->name = $key;
        $this->data = $value;
    }


}