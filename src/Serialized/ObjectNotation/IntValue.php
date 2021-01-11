<?php

namespace Serialized\ObjectNotation;

class IntValue
    extends AbstractValue
{

    // constants

    public const TYPE      = 22;
    public const TYPE_CHAR = 'i';
    public const TYPE_NAME = 'int';

    // protected properties

    protected $nativeValidator = 'is_int';


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $this->data = $this->parseInt();
    }

}