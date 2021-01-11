<?php

namespace Serialized\ObjectNotation;

/**
 * @property \Serialized\ObjectNotation\ClassName    $name
 * @property \Serialized\ObjectNotation\ClassMembers $data
 */
class ObjectValue
    extends ArrayValue
    implements NamedValueInterface
{

    use NamedValueTrait
    {
        #NamedValueTrait::serialize as NamedValueTrait_serialize;
        #NamedValueTrait::unserialize as NamedValueTrait_unserialize;
        #SimpleValueTrait::serialize as SimpleValueTrait_serialize;
        #SimpleValueTrait::unserialize as SimpleValueTrait_unserialize;
    }

// constants

    protected const ITEM_DATA_CLASS  = ClassMembers::class;
    protected const ITEM_NAME_CLASS  = ClassName::class;
    public const    TYPE             = 42;
    public const    TYPE_CHAR        = 'O';
    public const    TYPE_NAME        = 'object';

// protected properties

    protected $nativeValidator = 'is_object';


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $class      = static::ITEM_NAME_CLASS;
        $this->name = new $class( $this->parser );

        $class      = static::ITEM_DATA_CLASS;
        $this->data = new $class( $this->parser );
    }


    public function serialize(): ?string
    {

        return parent::serialize();
    }

}