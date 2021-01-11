<?php

namespace Serialized\ObjectNotation;

class StringValue
    extends AbstractValue
{

    // constants

    public const TYPE      = 24;
    public const TYPE_CHAR = 's';

    public const TYPE_MIMIMUM_LEN = 5;

    public const TYPE_NAME = 'string';

    // protected properties

    protected $nativeValidator = 'is_string';


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $value = $this->parseString();
        $this->assertTermination();

        $this->data = $value;
    }

    protected static function isLookingSerializedHelper( $data ): bool
    {

        if ( !parent::isLookingSerializedHelper( $data ) )
        {
            return false;
        }

        return ( '"' === $data[ 2 ] ) && ( '"' === substr( $data, -2, 1 ) );

    }

}