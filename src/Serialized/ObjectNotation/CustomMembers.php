<?php

namespace Serialized\ObjectNotation;

class CustomMembers
    extends
    ClassMembers
{

    // constants

    public const TYPE      = 104;
    public const TYPE_NAME = 'customMembers';


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseValue(): void
    {

        $this->length = $this->parseInt( ':' );

        $this->assertParserData( '{a:', "Missing or invalid opening mark" );

        $value = $this->parseArray();

        $this->assertParserData( '}', "Missing or invalid opening mark" );

        $this->setDataArray( $value );
    }


    public function serialize(): ?string
    {

        $array  = parent::serialize();
        $length = strlen( $array ) + 2;

        return sprintf( "%d:{%s}", $length, $array );
    }

}