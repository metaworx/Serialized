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
 * @package Serialized
 */

namespace Serialized;

use Serialized\ObjectNotation\ArrayValue;
use Serialized\ObjectNotation\BoolValue;
use Serialized\ObjectNotation\ClassMembers;
use Serialized\ObjectNotation\ClassName;
use Serialized\ObjectNotation\CustomData;
use Serialized\ObjectNotation\CustomValue;
use Serialized\ObjectNotation\FloatValue;
use Serialized\ObjectNotation\IntValue;
use Serialized\ObjectNotation\InvalidValue;
use Serialized\ObjectNotation\MemberName;
use Serialized\ObjectNotation\NullValue;
use Serialized\ObjectNotation\ObjectValue;
use Serialized\ObjectNotation\Recursion;
use Serialized\ObjectNotation\Reference;
use Serialized\ObjectNotation\StringValue;
use Serialized\ObjectNotation\VariableName;
use Serialized\ObjectNotation\Variables;

interface ValueTypes
{

    // constants
    public const TYPE_ARRAY         = ArrayValue::TYPE;
    public const TYPE_BOOL          = BoolValue::TYPE;
    public const TYPE_CLASSNAME     = ClassName::TYPE;
    public const TYPE_CUSTOM        = CustomValue::TYPE;
    public const TYPE_CUSTOMDATA    = CustomData::TYPE;
    public const TYPE_FLOAT         = FloatValue::TYPE;

    /** @var \Serialized\ObjectNotation\AbstractValue[]  */
    public const TYPE_IDENTIFIERS
                                    = [
            // array
            ArrayValue::TYPE_CHAR    => ArrayValue::class,
            // bool
            BoolValue::TYPE_CHAR     => BoolValue::class,
            // custom object
            CustomValue::TYPE_CHAR   => CustomValue::class,
            // float
            FloatValue::TYPE_CHAR    => FloatValue::class,
            // integer
            IntValue::TYPE_CHAR      => IntValue::class,
            // null
            NullValue::TYPE_CHAR     => NullValue::class,
            // object
            ObjectValue::TYPE_CHAR   => ObjectValue::class,
            // string
            StringValue::TYPE_CHAR   => StringValue::class,
            self::TYPE_STRINGENCODED => 'S',
            // recursion
            Recursion::TYPE_CHAR     => Recursion::class,
            // reference
            Reference::TYPE_CHAR     => Reference::class,
        ];
    public const TYPE_INT           = InvalidValue::TYPE;
    public const TYPE_INVALID       = InvalidValue::TYPE; // collection
    public const TYPE_MEMBER        = MemberName::TYPE; // composite
    public const TYPE_MEMBERS       = ClassMembers::TYPE; // composite
    public const TYPE_NULL          = NullValue::TYPE;
    public const TYPE_OBJECT        = ObjectValue::TYPE;
    public const TYPE_RECURSION     = Recursion::TYPE; // collection
    public const TYPE_RECURSIONREF  = Reference::TYPE;
    public const TYPE_STRING        = StringValue::TYPE; // collection
    public const TYPE_STRINGENCODED = 25;
    public const TYPE_VARIABLES     = Variables::TYPE;
    public const TYPE_VARNAME       = VariableName::TYPE;

}
