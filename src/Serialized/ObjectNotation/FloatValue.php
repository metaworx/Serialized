<?php

namespace Serialized\ObjectNotation;

class FloatValue
    extends AbstractValue
{

    // constants

    public const TYPE      = 23;
    public const TYPE_CHAR = 'd';
    public const TYPE_NAME = 'float';

    // protected properties

    protected $nativeValidator = 'is_float';


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $val = '';
        $val .= $this->parseNum( [ '.', ';' ], $delim );

        if ( $delim === '.' )
        {
            $val .= $delim;
            $i   = $this->parseNum( [ 'E', 'e', ';' ], $delim, $leadingZeros );
            $val .= str_repeat( '0', $leadingZeros );
            $val .= $i;
        }

        if ( $delim === 'E' || $delim === 'e' )
        {
            $val .= $delim;
            $val .= $this->parseNum();
        }

        $this->data = filter_var( $val, FILTER_VALIDATE_FLOAT );
    }

}