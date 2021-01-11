<?php

namespace Serialized\ObjectNotation;

class BoolValue
    extends AbstractValue
{

    // constants

    public const TYPE      = 21;
    public const TYPE_CHAR = 'b';
    public const TYPE_NAME = 'bool';

    // protected properties

    protected $nativeValidator = 'is_bool';



    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $val = $this->parseInt();

        $this->data = filter_var(
            $val,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0,
                    'max_range' => 1,
                ],
            ]
        );

        if ($this->data === false)
        {
            throw $this->throwInvalidData();
        }

        $this->data = (bool)$this->data;
    }

}