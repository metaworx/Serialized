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
 * @version 0.2.0
 * @package Serialized
 */

namespace Serialized;

use Serialized\ObjectNotation\Variables;

/**
 * Serialize Session Parser
 */
class SessionParser
    extends Parser
{

    public function setSession($session)
    {

        $this->setSerialized($session);
    }


    /**
     * parse for a serialized value at offset
     *
     * @param  \Serialized\Dumper|null  $returnFormat
     *
     * @return array array notation of serialized value
     */
    public function parseValue()
    {

        if ($this->pos === 0)
        {
            $class = $this->dumper::getTypeClass(Variables::TYPE_NAME);
            $value = new $class($this);
        }
        else
        {
            $class = $this->lookupVarType();

            $value = new $class($this);
        }

        return $value;
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    public function parseVariable($offset)
    {

        [$nameString, $nameLen] = $this->parseVariableName($offset);
        $this->expectChar('|', $offset + $nameLen);
        [$value, $len] = $this->parseValue($offset + $nameLen + 1);

        return [
            [
                [
                    TypeNames::of(self::TYPE_VARNAME),
                    $nameString,
                ],
                $value,
            ]
            ,
            $nameLen + 1 + $len,
        ];
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseVariableName(int $offset): array
    {

        $pattern = '([a-zA-Z0-9_\x7f-\xff]*)';
        $len     = $this->matchRegex($pattern, $offset);
        if (!$len)
        {
            throw new ParseException(
                sprintf('Invalid character sequence for variable name at offset %d.', $offset),
                $offset
            );
        }
        $value = substr($this->data, $offset, $len);

        return [$value, $len];
    }


    /**
     * @param $offset
     *
     * @return Variables
     * @throws \Serialized\ParseException
     */
    public function parseVariables()
    {

        return new Variables($this);
    }

}

