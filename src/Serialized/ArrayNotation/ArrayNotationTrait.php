<?php

namespace Serialized\ArrayNotation;

trait ArrayNotationTrait
{
    public function __serialize(): array
    {

        return [$this::TYPE_NAME, $this->data,];

    }


    public function offsetExists($offset): bool
    {

        if ($offset < 0)
        {
            return false;
        }

        if ($offset > 1)
        {
            return false;
        }

        return true;
    }


    public function offsetGet($offset)
    {

        /** @var \Serialized\Value $this */

        if ($offset === 0)
        {
            return $this::TYPE_NAME;
        }

        if ($offset === 1)
        {
            return $this->data;
        }

        throw new \InvalidArgumentException(sprintf('Illegal offset "%s" for %s', $offset, $this->class));
    }


    public function offsetSet(
        $offset,
        $value
    ) {
    }


    public function offsetUnset($offset)
    {
    }


    public function toArray()
    {

        return $this->__serialize();

    }

}