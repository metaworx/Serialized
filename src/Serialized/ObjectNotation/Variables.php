<?php

namespace Serialized\ObjectNotation;

class Variables
    extends ArrayValue
{

    // constants

    protected const ITEM_DATA_CLASS = VariableValue::class;
    public const    TYPE            = 201;
    public const    TYPE_CHAR       = null;
    public const    TYPE_NAME       = 'variables';


    protected function parseValue(): void
    {

        $sessionVariables = [];

        while (!$this->parser->isEof())
        {
            $class              = static::ITEM_DATA_CLASS;
            $sessionVariables[] = new $class($this->parser);
        }

        $this->setDataArray($sessionVariables);
    }

}