<?php

namespace Serialized\ObjectNotation;

class VariableName
    extends StringValue
{

    // constants

    protected const DEFAULT_TERMINATION = '|';
    public const    TYPE                = 202;
    public const    TYPE_CHAR           = null;
    public const    TYPE_NAME           = 'name';


    public function __construct($data = null)
    {

        $this->nativeValidator = static function ( $value): bool
        {

            return false !== preg_match('/([a-zA-Z0-9_\x7f-\xff]*)/', $value);

        };

        parent::__construct($data);
    }


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $value = '';

        while (($buffer = $this->parser->read()) !== self::DEFAULT_TERMINATION)
        {
            $value .= $buffer;

        }

        $this->data = $value;
    }

}