<?php
/**
 * Serialized - PHP Library for Serialized Data
 *
 * Copyright (C) 2010-2011 Tom Klingenberg, some rights reserved
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program in a file called COPYING. If not, see
 * <http://www.gnu.org/licenses/> and please report back to the original
 * author.
 *
 * @author  Tom Klingenberg <http://lastflood.com/>
 * @version 0.2.5
 * @package Examples
 */

namespace Serialized;

require_once( __DIR__ . '/../../src/Serialized.php' );


class testClass
{

    //  public properties
    public $publicProperty = 'x';

    // protected properties
    protected $protectedProperty = 'y';

    // private properties
    private $privateProperty = 'z';

}


$x = new testClass();

$data = [
    'fooBar',
    'test'      => 'Test [string]',
    'null'      => null,
    'int'       => 5,
    'float1'    => 3.9,
    'float2'    => (float) 9,
    'true'      => true,
    'false'     => false,
    8.1         => 'float3',
    null        => 'null',
    ''          => 'empty string',
    'stdObj1'   => (object) [],
    'stdObj2'   => (object) [ 'hallo' => 'echo', ],
    'testClass' => $x,
];

$serialized = serialize( $data );

$arrayNotation  = Parser::Parse( $serialized );
$objectNotation = Parser::parseToObjectNotation( $serialized );

$serialized2 = $objectNotation->serialize();

echo "\n";
echo "$serialized\n";
echo "$serialized2\n";

$objectNotation->replace( "foo", "Neue" );
$serialized2 = $objectNotation->serialize();

echo "\n";
echo "$serialized\n";
echo "$serialized2\n";

$data2 = unserialize( $serialized2, true );

$serialized2 = serialize( $objectNotation );
echo "$serialized2\n";

//print_r( $arrayNotation );