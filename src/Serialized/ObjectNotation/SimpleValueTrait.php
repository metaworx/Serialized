<?php

namespace Serialized\ObjectNotation;

use Serialized\SearchAndReplace;

trait SimpleValueTrait
{

    //  public properties

    /** @var \Serialized\ObjectNotation\SimpleValueInterface */
    public $data;


    public function replace(
        $search,
        $replace,
        ?int $flags = null,
        ?array $callbacks = null
    ): int {

        if ( $search instanceof SearchAndReplace )
        {
            $searchAndReplace = $search;
        }
        else
        {
            $searchAndReplace = new SearchAndReplace( $search, $replace, $void, $flags, $callbacks );
        }

        if ( $this instanceof NamedValueInterface && ( $searchAndReplace->flags & SearchAndReplace::REPLACE_FLAG_PROCESS_NAME ) )
        {

            $searchAndReplace->count += $searchAndReplace->getCloneFor( $this->name )
                                                         ->replaceValue()
            ;
        }

        if ( $this instanceof SimpleValueInterface && ( $searchAndReplace->flags & SearchAndReplace::REPLACE_FLAG_PROCESS_VALUE ) )
        {
            $searchAndReplace->count += $searchAndReplace->getCloneFor( $this->data )
                                                         ->replaceValue()
            ;
        }

        return $searchAndReplace->count;
    }


    public function serialize(): ?string
    {

        return $this->data->serialize();
    }


    public function unserialize( $serialized )
    {
        // TODO: Implement unserialize() method.
    }

}