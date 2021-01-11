<?php

namespace Serialized\ObjectNotation;

trait NamedValueTrait
{
    use SimpleValueTrait;

//  public properties

    /** @var \Serialized\ObjectNotation\SimpleValueInterface */
    public $name;


    /**
     * @return mixed
     */
    public function getName()
    {

        return $this->name;
    }


    public function serialize(): ?string
    {

        return sprintf( '%s%s', $this->name->serialize(), $this->data->serialize() );
    }


    protected function serializeValue(): ?string
    {

        $name   = $this->name->serialize();
        $values = parent::serializeValue();

        return sprintf( '%s%s', $name, $values );
    }

}