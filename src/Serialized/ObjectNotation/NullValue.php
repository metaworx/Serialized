<?php

namespace Serialized\ObjectNotation;

class NullValue
    extends AbstractValue
{

    // constants

    protected const DEFAULT_DELIMITER = '';
    public const    TYPE              = 11;
    public const    TYPE_CHAR         = 'N';
    public const    TYPE_NAME         = 'null';

    // protected properties

    protected $nativeValidator = 'is_null';


    protected function assertType()
    {

        if ($this->data !== null)
        {
            throw new \InvalidArgumentException('Null value can only be null.');
        }

        parent::assertType();
    }


    protected function parseValue()
    {
    }

}