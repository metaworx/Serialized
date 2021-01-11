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
use Serialized\ObjectNotation\StringValue;
use Serialized\ObjectNotation\VariableName;
use Serialized\ObjectNotation\Variables;

/**
 * Names of Value Types
 *
 * Concrete implementation of Value Type Names (represented as string)
 */
class TypeNames
    extends TypeMap
{
    // protected properties
    protected static $valType = 'name';
    protected static $map
                              = [
            self::TYPE_INVALID       => InvalidValue::TYPE_NAME,
            self::TYPE_BOOL          => BoolValue::TYPE_NAME,
            self::TYPE_FLOAT         => FloatValue::TYPE_NAME,
            self::TYPE_INT           => IntValue::TYPE_NAME,
            self::TYPE_NULL          => NullValue::TYPE_NAME,
            self::TYPE_RECURSION     => 'recursion',
            self::TYPE_RECURSIONREF  => 'recursionref',
            self::TYPE_ARRAY         => ArrayValue::TYPE_NAME,
            self::TYPE_OBJECT        => ObjectValue::TYPE_NAME,
            self::TYPE_STRING        => StringValue::TYPE_NAME,
            self::TYPE_STRINGENCODED => 'stringEncoded',
            self::TYPE_CLASSNAME     => ClassName::TYPE_NAME,
            self::TYPE_MEMBERS       => ClassMembers::TYPE_NAME,
            self::TYPE_MEMBER        => MemberName::TYPE_NAME,
            self::TYPE_VARIABLES     => Variables::TYPE_NAME,
            self::TYPE_VARNAME       => VariableName::TYPE_NAME,
            self::TYPE_CUSTOM        => CustomValue::TYPE_NAME,
            self::TYPE_CUSTOMDATA    => CustomData::TYPE_NAME,
        ];

}