<?php
/** @noinspection UnknownInspectionInspection */

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

use Serialized\ArrayNotation\IntValue;
use Serialized\Dumper\ArrayNotation;
use Serialized\Dumper\Concrete;
use Serialized\Dumper\ObjectArrayNotation;
use Serialized\Dumper\ObjectNotation;
use Serialized\ObjectNotation\AbstractValue;
use Serialized\ObjectNotation\ArrayValue;
use Serialized\ObjectNotation\BoolValue;
use Serialized\ObjectNotation\FloatValue;
use Serialized\ObjectNotation\NullValue;
use Serialized\ObjectNotation\ObjectValue;
use Serialized\ObjectNotation\StringValue;
use UnexpectedValueException;

/**
 * Serialize Parser
 */
class Parser
    implements Value, ValueTypes
{

    // constants
    public const DEFAULT_RETURN_FORMAT = ArrayNotation::class;

    // protected properties

    /**
     * @var resource serialized
     */
    protected $data;

    protected $buffer   = [];

    protected $elements = [ null => [] ];

    /** @var \Serialized\Dumper */
    protected $dumper;

    protected $pos = 0;


    public function __construct(
        $serialized = 'N;',
        ?string $returnFormat = self::DEFAULT_RETURN_FORMAT
    ) {

        $this->setDumper( $returnFormat );

        if ( is_resource( $serialized ) || AbstractValue::isLookingSerialized( $serialized ) )
        {
            $this->setSerialized( $serialized );

            return;
        }

        $this->loadValue( $serialized );
    }


    protected function getBuffer(
        int $length = 1,
        int $offset = 0
    ): ?string {

        if ( $length < 0 )
        {
            throw new \InvalidArgumentException(
                sprintf( 'Argument 1 ($length) to %s cannot be negative!', __METHOD__ )
            );
        }

        if ( $offset < 0 )
        {
            throw new \InvalidArgumentException(
                sprintf( 'Argument 2 ($offset) to %s cannot be negative!', __METHOD__ )
            );
        }

        if ( ( $count = count( $this->buffer ) ) < $length + $offset && !$this->isEof( false ) )
        {
            $buffer = $this->readFromStream( $length - $count + $offset );

            array_splice( $this->buffer, $count, 0, str_split( $buffer ) );
        }

        $return = array_slice( $this->buffer, $offset, $length );

        if ( count( $return ) < $length )
        {
            return null;
        }

        return implode( '', $return );
    }


    /**
     * get dump of a serialized array notation
     *
     * @param  string  $type    (optional) dumper type / format (Text, XML, Serialized)
     * @param  array   $config  (optional) dumper configuration
     *
     * @return string dump
     * @throws \Serialized\ParseException
     * @throws \Exception
     */
    public function getDump(
        $type = null,
        array $config = []
    ): string {

        $parsed = $this->getParsed();
        $dumper = Dumper::factory( $type, $config );

        return $dumper->getDump( $parsed );
    }


    /**
     * @return \Serialized\Dumper
     */
    public function getDumper(): Dumper
    {

        return $this->dumper;
    }


    /**
     * @param  \Serialized\Dumper|string|null  $dumper
     *
     * @return Parser
     */
    public function setDumper( $dumper ): Parser
    {

        if ( $dumper instanceof Concrete )
        {
            $this->dumper = $dumper;

            return $this;
        }

        if ( $dumper === null )
        {
            $dumper = ObjectNotation::class;
        }

        if ( is_string( $dumper ) )
        {
            if ( $this->dumper !== null && $dumper === get_class( $this->dumper ) )
            {
                return $this;
            }

            $this->dumper = Dumper::factory( $dumper );

            return $this;
        }

        $type = gettype( $dumper );

        if ( 'object' === $type )
        {
            $type = get_class( $dumper );
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Invalid argument 1 ($dumper) for %s: %s given',
                __METHOD__,
                $type
            )
        );
    }


    public function getOriginalPos(): ?int
    {

        return null;
    }


    /**
     * @param  \Serialized\Dumper|null  $returnFormat
     *
     * @return \Serialized\Value|array|mixed
     * @throws \Serialized\ParseException
     */
    public function getParsed( ?string $returnFormat = self::DEFAULT_RETURN_FORMAT )
    {

        $this->setDumper( $returnFormat );

        if ( $this->data instanceof Value )
        {
            $value = $this->data;
        }
        else
        {

            $value = $this->parseValue();

            $this->expectEof();

            $this->data = $value;
        }

        $value = $this->dumper->dump( $value, [ 'dumpTo' => null ] );

        return $value;
    }


    /**
     * @return int
     */
    public function getPos(): int
    {

        return $this->pos;
    }


    public function getSerialized(): string
    {

        return $this->data;
    }


    /**
     * @return string datatype
     * @throws \Serialized\ParseException
     */
    public function getTypeName(): string
    {

        $parsed = $this->getParsed();

        return $parsed[ 0 ];
    }


    public function isEof( $buffered = true ): bool
    {

        if ( $buffered && count( $this->buffer ) !== 0 )
        {
            return false;
        }

        if ( feof( $this->data ) )
        {
            return true;
        }

        if ( ( $s = stream_get_meta_data( $this->data ) ) && array_key_exists( 'eof', $s ) && ( $s[ 'eof' ] ?? false ) )
        {
            return true;
        }

        $buffer = null;

        if ( $buffered && '' !== ( $buffer = fread( $this->data, 1 ) ) )
        {
            $this->buffer[] = $buffer;
        }

        return $buffer === '';
    }


    /**
     * @param  string|resource  $serialized
     *
     * @return $this
     */
    public function setSerialized( $serialized ): self
    {

        if ( is_string( $serialized ) )
        {
            /** @noinspection FopenBinaryUnsafeUsageInspection */
            $this->data = fopen( 'php://temp', 'r+' );
            fwrite( $this->data, $serialized );
            rewind( $this->data );
            $this->pos = 0;
        }
        elseif ( !is_resource( $serialized ) )
        {
            throw new \InvalidArgumentException( "Data must be String or Resource" );
        }
        elseif ( !in_array( stream_get_meta_data( $serialized )[ 'mode' ], [ 'r', 'r+', 'w+' ], true ) )
        {
            throw new \InvalidArgumentException( "Resource must be readable" );
        }
        else
        {
            $this->data = $serialized;
        };

        return $this;
    }


    public function addElement( Value $value ): int
    {

        if ( null !== ( $pos = $value->getOriginalPos() ) )
        {
            $this->elements[ $pos ] = $value;

            return $pos;
        }

        $this->elements[ null ][] = $value;

        return -count( $this->elements[ null ] );
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
        $dumper = Dumper::factory( $type, $config );

        return $dumper->dump( $parsed );
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
    ): void {

        if ( !isset( $this->data[ $offset ] ) )
        {
            throw new ParseException(
                sprintf(
                    'Unexpected EOF, expected Expected "%s". At offset #%d ("%s").',
                    $charExpected,
                    $offset,
                    $this->extract( $offset )
                )
            );
        }
        $char = $this->data[ $offset ];
        if ( $charExpected !== $char )
        {
            throw new ParseException(
                sprintf(
                    'Unexpected char "%s", expected "%s". At offset #%d ("%s").',
                    $char,
                    $charExpected,
                    $offset,
                    $this->extract( $offset )
                )
            );
        }
    }


    /**
     * @param $offset
     *
     * @throws \Serialized\ParseException
     */
    protected function expectEof( $offset = 0 ): void
    {

        $len = 0;

        if ( $offset )
        {
            $len = strlen( $this->read( $offset, false ) );
        }

        if ( $len !== $offset || !$this->isEof() )
        {
            throw new ParseException(
                sprintf( 'Not EOF after offset #%d ("%s"). Length is %d.', $offset, $this->read( 500, false ), $len )
            );
        }
    }


    private function extract( $offset ): string
    {

        $delta  = 12;
        $start  = max( 0, $offset - $delta );
        $before = $offset - $start;
        $end    = min( strlen( $this->data ), $offset + $delta + 1 );
        $after  = $end - $offset;
        $end    = $end - $after + 1;
        $build  = '';
        $build  .= ( $before === $delta
            ? '...'
            : '' );
        $build  .= substr( $this->data, $start, $before );
        $build  .= isset( $this->data[ $offset ] )
            ? sprintf( '[%s]', $this->data[ $offset ] )
            : sprintf( '<-- #%d', strlen( $this->data ) - 1 );
        $build  .= substr( $this->data, $end, $after );
        $build  .= ( $after === $delta
            ? '...'
            : '' );

        return $build;
    }


    private function invalidArrayKeyType( $type ): bool
    {

        return !in_array( $type, [ 'int', 'string' ] );
    }


    public function loadValue(
        $data,
        ?string $returnFormat = self::DEFAULT_RETURN_FORMAT,
        bool $failGracefully = true
    ): ?Value {

        $this->setDumper( $returnFormat );

        switch ( gettype( $data ) )
        {
        case 'NULL':
            $token = NullValue::TYPE_CHAR;
            break;

        case 'boolean':
            $token = BoolValue::TYPE_CHAR;
            break;

        case 'string':
            $token = StringValue::TYPE_CHAR;
            break;

        case 'integer':
            $token = IntValue::TYPE_CHAR;
            break;

        case 'double':
            # aka 'float':
            $token = FloatValue::TYPE_CHAR;
            break;

        case 'array':
            $token = ArrayValue::TYPE_CHAR;
            break;

        case 'object':
            $token = ObjectValue::TYPE_CHAR;
            break;

        case 'resource':
        case 'resource (closed)':
        default:
            trigger_error(
                sprintf( 'Loading value not supported for type %s (in %s)', $this->type, __METHOD__ ),
                E_USER_WARNING
            );

            return null;
        }

        $class = $this->dumper::getTypeClass( $token );

        $value = new $class( $data );

        return $this->data = $value;
    }


    /**
     * @param  \Serialized\Dumper  $dumper
     *
     * @return string|null Class name of the found value type
     */
    protected function lookupVarType( bool $failGracefully = false ): ?string
    {

        $token = $this->getBuffer();

        if ( null !== $token )
        {
            $test = $this->getBuffer( 1, 1 );

            switch ( $test )
            {
            case ':':
                break;

            case ';':
                if ( 'N' === $token )
                {
                    break;
                }

            default:
                if ( $failGracefully )
                {
                    return null;
                }

                throw new ParseException(
                    sprintf(
                        'Unexpected char "%s" following type declaration "%s" at offset %d. Expected ":" (or ";" for NULL).',
                        $test,
                        $token,
                        $this->pos - 1
                    )
                );
            }

        }

        if ( $token === null || null === ( $class = $this->dumper::getTypeClass( $token ) ) )
        {
            if ( $failGracefully )
            {
                return null;
            }

            throw new UnexpectedValueException(
                sprintf(
                    'Unable to parse var type %s at offset %d.',
                    $token ?? 'NULL',
                    $this->pos - 2
                )
            );
        }

        return $class;
    }


    /**
     * @param  string  $pattern
     * @param  int     $offset
     *
     * @return int length in chars of match
     * @throws \Serialized\ParseException
     */
    protected function matchRegex(
        string $pattern,
        $offset
    ): int {

        $return  = 0;
        $subject = $this->data;
        if ( !isset( $subject[ $offset ] ) )
        {
            throw new ParseException(
                sprintf(
                    'Illegal offset #%d ("%s") for pattern, length is #%d.',
                    $offset,
                    $this->extract( $offset ),
                    strlen( $subject )
                )
            );
        }
        $found = preg_match( $pattern, $subject, $matches, PREG_OFFSET_CAPTURE, $offset );
        if ( false === $found )
        {
            // @codeCoverageIgnoreStart
            $error = preg_last_error();
            throw new UnexpectedValueException(
                sprintf( 'Regular expression ("%s") failed (Error-Code: %d).', $pattern, $error )
            );
            // @codeCoverageIgnoreEnd
        }
        $found
        && isset( $matches[ 0 ][ 1 ] )
        && $matches[ 0 ][ 1 ] === $offset
        && $return = strlen( $matches[ 0 ][ 0 ] );

        return $return;
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseArrayValue( int $offset )
    {

        $offsetStart = $offset;
        $lenString   = $this->parseRegex( '([+]?[0-9]+:{)', $offset );
        $lenMatch    = strlen( $lenString );
        $lenLen      = (int) $lenString;
        $offset      += $lenMatch;
        $value       = [];
        for ( $elementNumber = 0 ; $elementNumber < $lenLen ; $elementNumber++ )
        {
            [ $keyHinted, $keyLength ] = $this->parseValue( $offset );
            [ $keyTypeName ] = $keyHinted;
            if ( $this->invalidArrayKeyType( $keyTypeName ) )
            {
                throw new ParseException(
                    sprintf(
                        'Invalid vartype %s (%d) for array key at offset #%d ("%s").',
                        $keyTypeName,
                        TypeNames::by( $keyTypeName ),
                        $offset,
                        $this->extract( $offset )
                    )
                );
            }
            [ $valueHinted, $valueLength ] = $this->parseValue( $offset += $keyLength );
            $offset  += $valueLength;
            $element = [
                $keyHinted,
                $valueHinted,
            ];
            $value[] = $element;
        }
        $this->expectChar( '}', $offset );
        $len = $offset - $offsetStart + 1;

        return [ $value, $len ];
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseBoolValue( int $offset )
    {

        $char = $this->data[ $offset ];
        if ( '0' !== $char && '1' !== $char )
        {
            throw new ParseException(
                sprintf( 'Unexpected char "%s" at offset %d. Expected "0" or "1".', $char, $offset )
            );
        }
        $this->expectChar( ';', $offset + 1 );
        $valueInt = (int) $char;
        $value    = (bool) $valueInt;

        return [ $value, 2 ];
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseCustomValue( int $offset )
    {

        [ $className, $classLen ] = $this->parseStringValue( $offset, ':' );
        $dataLenLength = $this->matchRegex( '([0-9]+(?=:))', $offset + $classLen );
        if ( !$dataLenLength )
        {
            throw new ParseException(
                sprintf( 'Invalid character sequence for custom vartype at offset %d.', $offset + $classLen )
            );
        }
        $dataLengthString = substr( $this->data, $offset + $classLen, $dataLenLength );
        $dataLength       = (int) $dataLengthString;
        $this->expectChar( '{', $offset + $classLen + 1 + $dataLenLength );
        $this->expectChar( '}', $offset + $classLen + 1 + $dataLenLength + 1 + $dataLength );
        $data    = $dataLength
            ? substr( $this->data, $offset + $classLen + 1 + $dataLenLength + 1, $dataLength )
            : '';
        $value   = [
            [ TypeNames::of( self::TYPE_CLASSNAME ), $className ],
            [ TypeNames::of( self::TYPE_CUSTOMDATA ), $data ],
        ];
        $consume = $classLen + $dataLenLength + 2 + $dataLength + 1;

        return [ $value, $consume ];
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseFloatValue( int $offset )
    {

        $pattern
             = '((?:[-]?INF|[+-]?(?:(?:[0-9]+|(?:[0-9]*[\.][0-9]+)|(?:[0-9]+[\.][0-9]*))|(?:[0-9]+|(?:([0-9]*[\.][0-9]+)|(?:[0-9]+[\.][0-9]*)))[eE][+-]?[0-9]+));)';
        $len = $this->matchRegex( $pattern, $offset );
        if ( !$len )
        {
            throw new ParseException(
                sprintf( 'Invalid character sequence for float vartype at offset %d.', $offset )
            );
        }
        $valueString = substr( $this->data, $offset, $len - 1 );
        $value       = unserialize( "d:{$valueString};" ); // using unserialize for INF and -INF.

        return [ $value, $len ];
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseIntValue( int $offset )
    {

        $len = $this->matchRegex( '([-+]?[0-9]+)', $offset );
        if ( !$len )
        {
            throw new ParseException(
                sprintf( 'Invalid character sequence for integer value at offset %d.', $offset )
            );
        }
        $this->expectChar( ';', $offset + $len );
        $valueString = substr( $this->data, $offset, $len );
        $value       = (int) $valueString;

        return [ $value, $len + 1 ];
    }


    /**
     * @param $offset
     *
     * @throws \Serialized\ParseException
     */
    private function parseInvalidValue( $offset )
    {

        throw new ParseException( sprintf( 'Invalid ("%s") at offset %d.', $this->extract( $offset ), $offset ) );
    }


    /**
     * @param $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseNullValue( $offset )
    {

        $this->expectChar( 'N', $offset );
        $this->expectChar( ';', $offset + 1 );

        return [ null, 2 ];
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseObjectValue( int $offset )
    {

        $totalLen = 0;
        [ $className, $len ] = $this->parseStringValue( $offset, ':' );
        $totalLen += $len;
        [ $classMembers, $len ] = $this->parseArrayValue( $offset + $len );
        foreach ( $classMembers as $index => $member )
        {
            list( list( $typeSpec ) ) = $member;
            if ( 'string' !== $typeSpec )
            {
                throw new ParseException(
                    sprintf(
                        'Unexpected type %s, expected string on offset #%d ("%s").',
                        $typeSpec,
                        $offset,
                        $this->extract( $offset )
                    )
                );
            }
            $classMembers[ $index ][ 0 ][ 0 ] = TypeNames::of( self::TYPE_MEMBER );
        }
        $totalLen += $len;

        $count = count( $classMembers );
        $value = [
            [ TypeNames::of( self::TYPE_CLASSNAME ), $className ],
            [ TypeNames::of( self::TYPE_MEMBERS ), $classMembers ],
        ];

        return [ $value, $totalLen ];
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseRecursionValue( int $offset ): array
    {

        $len = $this->matchRegex( '([1-9]+[0-9]*)', $offset );
        if ( !$len )
        {
            throw new ParseException(
                sprintf( 'Invalid character sequence for recursion index at offset %d.', $offset )
            );
        }
        $this->expectChar( ';', $offset + $len );
        $valueString = substr( $this->data, $offset, $len );
        $value       = (int) $valueString;

        return [ $value, $len + 1 ];
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseRecursionrefValue( int $offset )
    {

        return $this->parseRecursionValue( $offset );
    }


    /**
     * @param  string  $pattern
     * @param  int     $offset
     *
     * @return string
     * @throws \Serialized\ParseException
     */
    private function parseRegex(
        string $pattern,
        int $offset
    ): string {

        $match = $this->matchRegex( $pattern, $offset );
        if ( !$match )
        {
            throw new ParseException(
                sprintf(
                    'Invalid character sequence for %s at offset #%d ("%s").',
                    $pattern,
                    $offset,
                    $this->extract( $offset )
                )
            );
        }

        return substr( $this->data, $offset, $match );
    }


    /**
     * @param  int  $offset
     *
     * @return array
     * @throws \Serialized\ParseException
     */
    private function parseStringEncodedValue( int $offset )
    {

        $len    = $this->parseRegex( '([+]?[0-9]+:")', $offset );
        $lenLen = strlen( $len );
        $lenInt = (int) $len;
        if ( $offset + $lenLen + $lenInt > strlen( $this->data ) )
        {
            throw new ParseException(
                sprintf(
                    'String length %d too large for data at offset #%d ("%s").',
                    $lenInt,
                    $offset,
                    $this->extract( $offset )
                )
            );
        }
        $consume = 0;
        $string  = $this->unserializeString( $offset + $lenLen, $lenInt, $consume );
        $this->expectChar( '"', $offset + $lenLen + $consume );
        $this->expectChar( ';', $offset + $lenLen + $consume + 1 );

        return [ $string, $lenLen + $consume + 2 ];
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

        $class = $this->lookupVarType();

        return new $class( $this );
    }


    public function read(
        int $length = 1,
        $isLengthRequired = true
    ): string {

        if ( $length < 0 )
        {
            throw new ParseException( sprintf( 'Invalid length %d at #%d!', $length, $this->pos ) );
        }

        if ( $this->isEof() )
        {
            throw new ParseException( sprintf( 'Unexpected EOF at #%d!', $this->pos ) );
        }

        if ( $length === 0 )
        {
            return '';
        }

        $return    = array_splice( $this->buffer, 0, $length );
        $lenBuffer = count( $return );

        $this->pos += $lenBuffer;

        if ( $length - $lenBuffer === 0 )
        {

            return implode( '', $return );
        }

        try
        {
            $buffer = $this->readFromStream( $length - $lenBuffer );
        }
        catch ( ParseException $e )
        {
            // restore buffer
            $this->buffer =& $return;
            $this->pos    -= $lenBuffer;

            throw $e;
        }

        $lenRead = strlen( $buffer );

        if ( $isLengthRequired && $lenBuffer + $lenRead !== $length )
        {
            // append to buffer
            array_splice( $return, count( $return ), 0, str_split( $buffer ) );

            // restore buffer
            $this->buffer =& $return;
            $this->pos    -= $lenBuffer;

            throw new ParseException(
                sprintf(
                    'Failed to read %d characters at #%d! Only %d read: %s',
                    $length,
                    $this->pos,
                    $lenBuffer + $lenRead,
                    implode( '', $this->buffer )
                )
            );
        }

        $this->pos += $lenRead;

        return implode( '', $return ) . $buffer;
    }


    protected function readFromStream(
        int $length = 1
    ): string {

        $buffer = fread( $this->data, $length );

        if ( $buffer === false )
        {
            throw new ParseException( sprintf( 'Read failed at #%d!', $this->pos ) );
        }

        return $buffer;
    }


    public function replace(
        $search,
        $replace,
        ?int $flags = null,
        ?array $callbacks = null
    ): int {
        // TODO: Implement replace() method.
    }


    public function serialize()
    {

        return $this->getSerialized();
    }


    public function unserialize( $serialized )
    {

        return $this->setSerialized( $serialized );
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
        for ( $i = 0 ; $i < $len ; $i++ )
        {
            if ( !isset( $subject[ $pos ] ) )
            {
                throw new ParseException( sprintf( 'Unexpected EOF at #%d ("%s")', $pos, $this->extract( $pos ) ) );
            }
            $char = $subject[ $pos ];
            if ( $char === '\\' )
            {
                $token = $this->parseRegex( '([0-9a-fA-F]{2})', $pos + 1 );
                $char  = chr( hexdec( $token ) );
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
     * @param  string|resource          $serialized
     * @param  \Serialized\Dumper|null  $returnFormat
     *
     * @return array|false array notation, false on parse error
     * @throws \Serialized\ParseException
     */
    public static function Factory(
        $serialized,
        ?string $returnFormat = self::DEFAULT_RETURN_FORMAT,
        bool $failGracefully = true
    ) {

        return new self( $serialized, $returnFormat );
    }


    /**
     * parse string of serialized  data into array notation
     *
     * @param  string|resource          $serialized
     * @param  \Serialized\Dumper|null  $returnFormat
     *
     * @return array|false array notation, false on parse error
     * @throws \Serialized\ParseException
     */
    public static function parse(
        $serialized,
        ?string $returnFormat = self::DEFAULT_RETURN_FORMAT,
        bool $failGracefully = true
    ) {

        $parser = new self( $serialized, $returnFormat );

        try
        {
            $result = $parser->getParsed( $returnFormat );
        }
        catch ( ParseException $e )
        {
            if ( !$failGracefully )
            {
                throw $e;
            }

            trigger_error( sprintf( 'Error parsing serialized string: %s', $e->getMessage() ), E_USER_WARNING );
            $result = false;
        }

        return $result;
    }


    /**
     * parse string of serialized  data into array notation
     *
     * @param  string|resource  $serialized
     *
     * @return \Serialized\Value array notation
     * @throws \Serialized\ParseException
     * @noinspection PhpUnused
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function parseToArrayNotation( $serialized ): Value
    {

        return static::parse( $serialized, ArrayNotation::class, false );
    }


    /**
     * parse string of serialized  data into array notation
     *
     * @param  string|resource  $serialized
     *
     * @return \Serialized\Value array notation, false on parse error
     * @throws \Serialized\ParseException
     * @noinspection PhpUnused
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function parseToObjectArrayNotation( $serialized ): Value
    {

        return static::parse( $serialized, ObjectArrayNotation::class, false );
    }


    /**
     * parse string of serialized  data into array notation
     *
     * @param  string|resource  $serialized
     *
     * @return \Serialized\Value array notation, false on parse error
     * @throws \Serialized\ParseException
     * @noinspection PhpUnused
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function parseToObjectNotation( $serialized ): Value
    {

        return static::parse( $serialized, ObjectNotation::class, false );
    }

}