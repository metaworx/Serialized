<?php

namespace Serialized\ObjectNotation;

class ArrayValue
    extends AbstractValue
{

    // constants

    protected const DEFAULT_TERMINATION = null;
    public const    TYPE                = 41;
    public const    TYPE_CHAR           = 'a';
    public const    TYPE_NAME           = 'array';
    public const    TYPE_TERMINATION    = '}';

    // protected properties

    protected $nativeValidator = 'is_array';


    protected function loadValue( $data ): self
    {

        $this->assertNativeDataType( $data );

        array_walk(
            $data,
            function (
                &$val,
                $key,
                $class
            )
            {
                $val = new $class( $key, $val );

            },
            ArrayElement::class
        );

        $this->data = new  $data();
        $this->assertInternalDataType();

        return $this;
    }


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $value = $this->parseArray();

        $this->setDataArray( $value );
    }

}