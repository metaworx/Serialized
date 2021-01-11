<?php

namespace Serialized\ObjectNotation;

/**
 * @property  \Serialized\ObjectNotation\VariableName $name
 */
class VariableValue
    extends ObjectValue
{

    // constants

    public const    TYPE            = 42;
    public const    TYPE_CHAR       = null;
    public const    TYPE_NAME       = 'variable';

    // protected properties

    protected $nativeValidator = 'is_object';


    /**
     * @return \Serialized\ObjectNotation\VariableName
     */
    public function getName(): VariableName
    {

        return $this->name;
    }


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $class      = $this->parser->getDumper()::getTypeClass(VariableName::TYPE_NAME);
        $this->name = new $class($this->parser);
        $this->data = $this->parser->parseValue();
    }

}