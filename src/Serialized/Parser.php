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

use UnexpectedValueException;

/**
 * Serialize Parser
 */
class Parser
    implements Value, ValueTypes
{
// protected properties
    /**
     * @var string serialized
     */
    protected $data = '';


    public function __construct($serialized = 'N;')
    {

        $this->setSerialized($serialized);
    }


    /**
     * get dump of a serialized array notation
     *
     * @param  string  $type    (optional) dumper type / format (Text, XML, Serialized)
     * @param  array   $config  (optional) dumper configuration
     *
     * @return string dump
     * @throws \Serialized\ParseException
     */
    public function getDump(
        $type = null,
        array $config = []
    ) {

        $parsed = $this->getParsed();
        $dumper = Dumper::factory($type, $config);

        return $dumper->getDump($parsed);
    }


    /**
     * @return array|mixed
     * @throws \Serialized\ParseException
     */
    public function getParsed()
    {

        [$value, $len] = $this->parseValue(0);
        $this->expectEof($len - 1);

        return $value;
    }


    public function getSerialized()
    {

        return $this->data;
    }


    /**
     * @return string datatype
     * @throws \Serialized\ParseException
     */
    public function getType()
    {

        $parsed = $this->getParsed();

        return $parsed[0];
    }


    public function setSerialized($serialized)
    {

        $this->data = (string)$serialized;
    }


    /**
     * print serialized array notation
     *
     * @param  string  $type    (optional) dumper type / format (Text, XML, Serialized)
     * @param  array   $config  (optional) dumper configuration
     *
     * @throws \Serialized\ParseException
     */
    public function dump(
        $type = null,
        array $config = []
    ) {

        $parsed = $this->getParsed();
        $dumper = Dumper::factory($type, $config);
        $dumper->dump($parsed);
    }


    /**
     * @param $charExpected
     * @param $offset
     *
     * @throws \Serialized\ParseException
     */
    protected function expectChar(
        $charExpected,
        $offset
    ) {

        if (!isset($this->data[$offset]))
        {
            throw new ParseException(
                sprintf(
                    'Unexpected EOF, expected Expected "%s". At offset #%d ("%s").',
                    $charExpected,
                    $offset,
                    $this->extract($offset)
                )
            );
        }
        $char = $this->data[$offset];
        if ($charExpected !== $char)
        {
            throw new ParseException(
                sprintf(
                    'Unexpected char "%s", expected "%s". At offset #%d ("%s").',
                    $char,
                    $charExpected,
                    $offset,
                    $this->extract($offset)
                )
            );
        }
    }


    /**
     * @param $offset
     *
     * @throws \Serialized\ParseException
     */
    protected function expectEof($offset)
    {

        $len = strlen($this->data);
        $end = ($offset + 1) === $len;
        if (!$end)
        {
            throw new ParseException(
                sprintf('Not EOF after offset #%d ("%s"). Length is %d.', $offset, $this->extract($offset), $len)
            );
        }
    }


    private function extract($offset)
    {

        $delta  = 12;
        $start  = max(0, $offset - $delta);
        $before = $offset - $start;
        $end    = min(strlen($this->data), $offset + $delta + 1);
        $after  = $end - $offset;
        $end    = $end - $after + 1;
        $build  = '';
        $build  .= ($before === $delta
            ? '...'
            : '');
        $build  .= substr($this->data, $start, $before);
        $build  .= isset($this->data[$offset])
            ? sprintf('[%s]', $this->data[$offset])
            : sprintf('<-- #%d', strlen($this->data) - 1);
        $build  .= substr($this->data, $end, $after);
        $build  .= ($after === $delta
            ? '...'
            : '');

        return $build;
    }


    private function invalidArrayKeyType($type)
    {

        return !in_array($type, ['int', 'string']);
    }


    /**
     * @param $offset
     *
     * @return array(int type, int byte length)
     */
    private function lookupVartype($offset)
    {

        $serialized = $this->data;
        $len        = strlen($serialized) - $offset;
        $error      = [self::TYPE_INVALID, 0];
        if ($len < 2)
        {
            return $error;
        }
        # NULL; fixed length: 2
        $token = $serialized[$offset];
        $test  = $serialized[$offset + 1];
        if ('N' === $token && ';' === $test)
        {
            return [self::TYPE_NULL, 0];
        }
        if (':' !== $test)
        {
            return $error;
        }
        if (false === strpos('abCdiOrRsS', $token))
        {
            return $error;
        }

        return [TypeChars::by($token), 2];
    }


    /**
     * @param  string  $pattern
     * @param  int     $offset
     *
     * @return int length in chars of match
     * @throws \Serialized\ParseException
     */
    protected function matchRegex(
        $pattern,
        $offset
    ) {

        $return  = 0;
        $subject = $this->data;
        if (!isset($subject[$offset]))
        {
            throw new ParseException(
                sprintf(
                    'Illegal offset #%d ("%s") for pattern, length is #%d.',
                    $offset,
                    $this->extract($offset),
                    strlen($subject)
                )
            );
        }
        $found = preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, $offset);
        if (false === $found)
        {
            // @codeCoverageIgnoreStart
            $error = preg_last_error();
            throw new UnexpectedValueException(
                sprintf('Regular expression ("%s") failed (Error-Code: %d).', $pattern, $error)
            );
            // @codeCoverageIgnoreEnd
        }
        $found
        && isset($matches[0][1])
        && $matches[0][1] === $offset
        && $return = strlen($matches[0][0]);

        return $return;
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseArrayValue($offset)
    {

        $offsetStart = $offset;
        $lenString   = $this->parseRegex('([+]?[0-9]+:{)', $offset);
        $lenMatch    = strlen($lenString);
        $lenLen      = (int)$lenString;
        $offset      += $lenMatch;
        $value       = [];
        for ($elementNumber = 0; $elementNumber < $lenLen; $elementNumber++)
        {
            [$keyHinted, $keyLength] = $this->parseValue($offset);
            [$keyTypeName] = $keyHinted;
            if ($this->invalidArrayKeyType($keyTypeName))
            {
                throw new ParseException(
                    sprintf(
                        'Invalid vartype %s (%d) for array key at offset #%d ("%s").',
                        $keyTypeName,
                        TypeNames::by($keyTypeName),
                        $offset,
                        $this->extract($offset)
                    )
                );
            }
            [$valueHinted, $valueLength] = $this->parseValue($offset += $keyLength);
            $offset  += $valueLength;
            $element = [
                $keyHinted,
                $valueHinted,
            ];
            $value[] = $element;
        }
        $this->expectChar('}', $offset);
        $len = $offset - $offsetStart + 1;

        return [$value, $len];
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseBoolValue($offset)
    {

        $char = $this->data[$offset];
        if ('0' !== $char && '1' !== $char)
        {
            throw new ParseException(
                sprintf('Unexpected char "%s" at offset %d. Expected "0" or "1".', $char, $offset)
            );
        }
        $this->expectChar(';', $offset + 1);
        $valueInt = (int)$char;
        $value    = (bool)$valueInt;

        return [$value, 2];
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseCustomValue($offset)
    {

        [$className, $classLen] = $this->parseStringValue($offset, ':');
        $dataLenLength = $this->matchRegex('([0-9]+(?=:))', $offset + $classLen);
        if (!$dataLenLength)
        {
            throw new ParseException(
                sprintf('Invalid character sequence for custom vartype at offset %d.', $offset + $classLen)
            );
        }
        $dataLengthString = substr($this->data, $offset + $classLen, $dataLenLength);
        $dataLength       = (int)$dataLengthString;
        $this->expectChar('{', $offset + $classLen + 1 + $dataLenLength);
        $this->expectChar('}', $offset + $classLen + 1 + $dataLenLength + 1 + $dataLength);
        $data    = $dataLength
            ? substr($this->data, $offset + $classLen + 1 + $dataLenLength + 1, $dataLength)
            : '';
        $value   = [
            [TypeNames::of(self::TYPE_CLASSNAME), $className],
            [TypeNames::of(self::TYPE_CUSTOMDATA), $data],
        ];
        $consume = $classLen + $dataLenLength + 2 + $dataLength + 1;

        return [$value, $consume];
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseFloatValue($offset)
    {

        $pattern
             = '((?:[-]?INF|[+-]?(?:(?:[0-9]+|(?:[0-9]*[\.][0-9]+)|(?:[0-9]+[\.][0-9]*))|(?:[0-9]+|(?:([0-9]*[\.][0-9]+)|(?:[0-9]+[\.][0-9]*)))[eE][+-]?[0-9]+));)';
        $len = $this->matchRegex($pattern, $offset);
        if (!$len)
        {
            throw new ParseException(sprintf('Invalid character sequence for float vartype at offset %d.', $offset));
        }
        $valueString = substr($this->data, $offset, $len - 1);
        $value       = unserialize("d:{$valueString};"); // using unserialize for INF and -INF.

        return [$value, $len];
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseIntValue($offset)
    {

        $len = $this->matchRegex('([-+]?[0-9]+)', $offset);
        if (!$len)
        {
            throw new ParseException(sprintf('Invalid character sequence for integer value at offset %d.', $offset));
        }
        $this->expectChar(';', $offset + $len);
        $valueString = substr($this->data, $offset, $len);
        $value       = (int)$valueString;

        return [$value, $len + 1];
    }


    /**
     * @param $offset
     *
     * @throws \Serialized\ParseException
     */
    private function parseInvalidValue($offset)
    {

        throw new ParseException(sprintf('Invalid ("%s") at offset %d.', $this->extract($offset), $offset));
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseNullValue($offset)
    {

        $this->expectChar('N', $offset);
        $this->expectChar(';', $offset + 1);

        return [null, 2];
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseObjectValue($offset)
    {

        $totalLen = 0;
        [$className, $len] = $this->parseStringValue($offset, ':');
        $totalLen += $len;
        [$classMembers, $len] = $this->parseArrayValue($offset + $len);
        foreach ($classMembers as $index => $member)
        {
            list(list($typeSpec)) = $member;
            if ('string' !== $typeSpec)
            {
                throw new ParseException(
                    sprintf(
                        'Unexpected type %s, expected string on offset #%d ("%s").',
                        $typeSpec,
                        $offset,
                        $this->extract($offset)
                    )
                );
            }
            $classMembers[$index][0][0] = TypeNames::of(self::TYPE_MEMBER);
        }
        $totalLen += $len;

        $count = count($classMembers);
        $value = [
            [TypeNames::of(self::TYPE_CLASSNAME), $className],
            [TypeNames::of(self::TYPE_MEMBERS), $classMembers],
        ];

        return [$value, $totalLen];
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseRecursionValue($offset)
    {

        $len = $this->matchRegex('([1-9]+[0-9]*)', $offset);
        if (!$len)
        {
            throw new ParseException(sprintf('Invalid character sequence for recursion index at offset %d.', $offset));
        }
        $this->expectChar(';', $offset + $len);
        $valueString = substr($this->data, $offset, $len);
        $value       = (int)$valueString;

        return [$value, $len + 1];
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseRecursionrefValue($offset)
    {

        return $this->parseRecursionValue($offset);
    }


    /**
     * @param  string  $pattern
     * @param  int     $offset
     *
     * @return string
     * @throws ParseException
     */
    private function parseRegex(
        $pattern,
        $offset
    ) {

        $match = $this->matchRegex($pattern, $offset);
        if (!$match)
        {
            throw new ParseException(
                sprintf(
                    'Invalid character sequence for %s at offset #%d ("%s").',
                    $pattern,
                    $offset,
                    $this->extract($offset)
                )
            );
        }

        return substr($this->data, $offset, $match);
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseStringEncodedValue($offset)
    {

        $len    = $this->parseRegex('([+]?[0-9]+:")', $offset);
        $lenLen = strlen($len);
        $lenInt = (int)$len;
        if ($offset + $lenLen + $lenInt > strlen($this->data))
        {
            throw new ParseException(
                sprintf(
                    'String length %d too large for data at offset #%d ("%s").',
                    $lenInt,
                    $offset,
                    $this->extract($offset)
                )
            );
        }
        $consume = 0;
        $string  = $this->unserializeString($offset + $lenLen, $lenInt, $consume);
        $this->expectChar('"', $offset + $lenLen + $consume);
        $this->expectChar(';', $offset + $lenLen + $consume + 1);

        return [$string, $lenLen + $consume + 2];
    }


    /**
     * @param          $offset
     * @param  string  $terminator
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseStringValue(
        $offset,
        $terminator = ';'
    ) {

        $len    = $this->parseRegex('([+]?[0-9]+:")', $offset);
        $lenLen = strlen($len);
        $lenInt = (int)$len;
        $this->expectChar('"', $offset + $lenLen + $lenInt);
        $this->expectChar($terminator, $offset + $lenLen + $lenInt + 1);
        $value = substr($this->data, $offset + $lenLen, $lenInt);

        return [$value, $lenLen + $lenInt + 2];
    }


    /**
     * parse for a serialized value at offset
     *
     * @param  int  $offset  byte offset
     *
     * @return array array notation of serialized value
     */
    public function parseValue($offset)
    {

        [$type, $consume] = $this->lookupVartype($offset);
        $typeName = TypeNames::of($type);
        $function = sprintf('parse%sValue', ucfirst($typeName));
        if (!is_callable([$this, $function]))
        {
            // @codeCoverageIgnoreStart
            throw new UnexpectedValueException(
                sprintf(
                    'Unable to parse vartype %s (%d) at offset %s. Parsing function %s is not callable',
                    $typeName,
                    $type,
                    $offset,
                    $function
                )
            );
            // @codeCoverageIgnoreEnd
        }
        [$value, $len] = $this->$function($offset + $consume);
        // parse encoded strings as strings (php forward compatibility)
        if ($type === self::TYPE_STRINGENCODED)
        {
            $typeName = TypeNames::of(self::TYPE_STRING);
        }
        $hinted = [$typeName, $value];

        return [$hinted, $len + $consume];
    }


    /**
     * @param $offset
     * @param $len
     * @param $consume
     *
     * @return string
     * @throws \Serialized\ParseException
     */
    private function unserializeString(
        $offset,
        $len,
        &$consume
    ) {

        $string  = '';
        $consume = 0;
        $subject = $this->data;
        $pos     = $offset;
        for ($i = 0; $i < $len; $i++)
        {
            if (!isset($subject[$pos]))
            {
                throw new ParseException(sprintf('Unexpected EOF at #%d ("%s")', $pos, $this->extract($pos)));
            }
            $char = $subject[$pos];
            if ($char === '\\')
            {
                $token = $this->parseRegex('([0-9a-fA-F]{2})', $pos + 1);
                $char  = chr(hexdec($token));
                $pos   += 2;
            }
            $string .= $char;
            $pos++;
        }
        $consume = $pos - $offset;

        return $string;
    }


    /**
     * parse string of serialized  data into array notation
     *
     * @param  string  $serialized
     *
     * @return array|false array notation, false on parse error
     */
    public static function parse($serialized)
    {

        $parser = new self($serialized);
        try
        {
            $result = $parser->getParsed();
        }
        catch (ParseException $e)
        {
            trigger_error(sprintf('Error parsing serialized string: %s', $e->getMessage()), E_USER_WARNING);
            $result = false;
        }

        return $result;
    }

}