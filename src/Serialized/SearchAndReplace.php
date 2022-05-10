<?php

namespace Serialized;

use Serialized\ObjectNotation\AbstractValue;
use Serialized\ObjectNotation\SimpleValueInterface;

class SearchAndReplace
{

    // constants

    public const REPLACE_FLAG_AUTO                 = 2 ** 0;
    public const REPLACE_FLAG_DEFAULTS
                                                   = self::REPLACE_FLAG_AUTO
        + self::REPLACE_FLAG_PROCESS_VALUE
        + self::REPLACE_FLAG_PROCESS_STRING
        + self::REPLACE_FLAG_SINGLE_BYTE
        + self::REPLACE_FLAG_PROCESS_ARRAY_VALUES;
    public const REPLACE_FLAG_MULTI_BYTE           = 2 ** 4;
    public const REPLACE_FLAG_PROCESS_ARRAY_KEYS   = 2 ** 11;
    public const REPLACE_FLAG_PROCESS_ARRAY_VALUES = 2 ** 10;
    public const REPLACE_FLAG_PROCESS_FLOAT        = 2 ** 9;
    public const REPLACE_FLAG_PROCESS_INT          = 2 ** 8;
    public const REPLACE_FLAG_PROCESS_NAME         = 2 ** 5;
    public const REPLACE_FLAG_PROCESS_NUMERIC      = self::REPLACE_FLAG_PROCESS_INT + self::REPLACE_FLAG_PROCESS_FLOAT;
    public const REPLACE_FLAG_PROCESS_STRING       = 2 ** 7;
    public const REPLACE_FLAG_PROCESS_VALUE        = 2 ** 6;
    public const REPLACE_FLAG_REGEX                = 2 ** 2;
    public const REPLACE_FLAG_REGULAR              = 2 ** 1;
    public const REPLACE_FLAG_SINGLE_BYTE          = 2 ** 3;

//  public properties
    public        $search;

    public        $replace;

    public        $subject;

    public string $type;

    public ?int   $flags           = null;

    public ?array $callbacks       = null;

    public bool   $continue        = true;

    public int    $count           = 0;

    public bool   $searchPerformed = false;


    /**
     * SearchAndReplace constructor.
     *
     * @param              $search
     * @param              $replace
     * @param              $subject
     * @param  int|null    $flags
     * @param  array|null  $callbacks
     */
    public function __construct(
        &$search,
        &$replace,
        &$subject,
        ?int $flags,
        ?array &$callbacks
    ) {

        $this->search    =& $search;
        $this->replace   =& $replace;
        $this->subject   =& $subject;
        $this->callbacks =& $callbacks;
        $this->type      = gettype( $subject );

        $this->evaluateFlags( $flags );
    }


    public function getCallback(): ?callable
    {

        return $this->callbacks[ $this->type ] ?? $this->callbacks[0] ?? null;
    }


    public function getCloneFor( &$subject = null ): self
    {

        return new self( $this->search, $this->replace, $subject, $this->flags, $this->callbacks );
    }


    public function callBack( ...$additionalArguments ): int
    {

        $callback = $this->getCallback();

        if ( $callback )
        {
            $callback( $this, ...$additionalArguments );

            return $this->continue;
        }

        return true;
    }


    public function evaluateFlags( ?int $flags = null ): int
    {

        if ( $flags === null )
        {
            $flags = $this->flags ?? self::REPLACE_FLAG_DEFAULTS;
        }

        if ( $flags & self::REPLACE_FLAG_AUTO )
        {
            // remove the auto flag
            $flags &= ~self::REPLACE_FLAG_AUTO;

            // check if is regular or regex
            if ( ( $flags & self::REPLACE_FLAG_REGULAR ) === 0 && ( $flags & self::REPLACE_FLAG_REGEX ) === 0 )
            {
                // clear both
                $flags &= ~( self::REPLACE_FLAG_REGULAR | self::REPLACE_FLAG_REGEX );

                $regex = true;

                foreach ( (array) $this->search as $s )
                {
                    // check if search string can be evaluated as regex. If not, preg_match will return false;
                    if ( ! static::isPerlRegex( $s ) )
                    {
                        $regex = false;
                        break;
                    }
                }

                $flags |= $regex
                    ? self::REPLACE_FLAG_REGEX
                    : self::REPLACE_FLAG_REGULAR;
            }

            // check if is single or multibyte
            if ( ( $flags & self::REPLACE_FLAG_MULTI_BYTE ) === 0 && ( $flags & self::REPLACE_FLAG_SINGLE_BYTE ) === 0 )
            {
                // clear both
                $flags &= ~( self::REPLACE_FLAG_MULTI_BYTE | self::REPLACE_FLAG_SINGLE_BYTE );

                $flags |= function_exists( 'mb_split' ) && function_exists( 'mb_ereg_replace' )
                    ? self::REPLACE_FLAG_MULTI_BYTE
                    : self::REPLACE_FLAG_SINGLE_BYTE;
            }
        }

        return $this->flags = $flags;
    }


    /**
     * @throws \Serialized\ParseException
     * @throws \JsonException
     */
    public function replaceValue(): int
    {

        if ( ! $this->continue )
        {
            return $this->count;
        }

        if ( ! $this->callBack() )
        {
            return $this->count;
        }

        switch ( $this->type )
        {
        case 'NULL':
        case 'boolean':
        case 'resource':
        case 'resource (closed)':
            return 0;

        case 'string':
            if ( 0 === ( $this->flags & self::REPLACE_FLAG_PROCESS_STRING ) )
            {
                return 0;
            }

            $this->replaceValueString( $this );
            break;

        case 'integer':
            if ( 0 === ( $this->flags & self::REPLACE_FLAG_PROCESS_INT ) )
            {
                return 0;
            }

            $this->replaceValueString( $this );
            break;

        case 'double':
            # aka 'float':
            if ( 0 === ( $this->flags & self::REPLACE_FLAG_PROCESS_FLOAT ) )
            {
                return 0;
            }

            $this->replaceValueString( $this );
            break;

        case 'array':
            if ( 0 === ( $this->flags & ( self::REPLACE_FLAG_PROCESS_ARRAY_VALUES | self::REPLACE_FLAG_PROCESS_ARRAY_KEYS ) ) )
            {
                return 0;
            }

            $new = [];

            foreach ( $this->subject as $key => &$item )
            {
                if ( $this->flags & ( self::REPLACE_FLAG_PROCESS_ARRAY_VALUES ) )
                {
                    $this->count += $this->getCloneFor( $item )
                                         ->replaceValue();
                }

                if ( $this->flags & ( self::REPLACE_FLAG_PROCESS_ARRAY_KEYS ) )
                {
                    $this->count += $this->getCloneFor( $key )
                                         ->replaceValue();
                    $new[ $key ] = $item;
                }
            }

            if ( ! empty( $new ) )
            {
                $this->subject = $new;
            }
            break;

        case 'object':

            if ( $this->subject instanceof SimpleValueInterface )
            {
                return $this->subject->replace(
                    $this->search,
                    $this->replace,
                    $this->flags,
                    $this->callbacks
                );
            }

            $array      = get_object_vars( $this->subject );
            $properties = array_keys( $array );
            $translated = $properties;
            $values     = array_values( $array );

            unset ( $array );

            $this->count += $this->getCloneFor( $translated )
                                 ->replaceValue();
            $this->count += $this->getCloneFor( $values )
                                 ->replaceValue();

            $translated = array_combine( $properties, $translated );
            $properties = array_flip( $properties );

            foreach ( $translated as $oldProperty => $newProperty )
            {
                if ( $oldProperty !== $newProperty )
                {
                    unset( $this->subject->$oldProperty );
                }

                $this->subject->$newProperty = $values[ $properties[ $oldProperty ] ];
            }
            break;

        default:
            trigger_error(
                sprintf( 'Search and Replace not supported for type %s (in %s)', $this->type, __METHOD__ ),
                E_USER_WARNING
            );

            return 0;
        }

        $this->searchPerformed = true;

        $this->callBack();

        return $this->count;
    }


    /**
     * @throws \Serialized\ParseException
     * @throws \JsonException
     */
    public function replaceValueString(): int
    {

        if ( AbstractValue::isLookingSerialized( $this->subject ) )
        {
            $count = 0;

            try
            {
                $unserialized = Parser::parseToObjectNotation( $this->subject );

                if ( $count = $unserialized->replace(
                    $this->search,
                    $this->replace,
                    $this->flags,
                    $this->callbacks
                ) )
                {
                    $this->subject = $unserialized->serialize();
                }
            }
            catch ( ParseException $e )
            {
                $e->subject = $this->subject;
                throw $e;
            }

            return $this->count += $count;
        }

        try
        {
            $decoded = json_decode( $this->subject, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );
        }
        catch ( \JsonException $exception )
        {
            unset( $exception );
            $decoded = null;
        }

        if ( ( is_array( $decoded ) || is_object( $decoded ) ) && json_last_error() === JSON_ERROR_NONE )
        {
            if ( empty( $decoded ) )
            {
                return 0;
            }

            $count += $this->getCloneFor( $decoded )
                           ->replaceValue();

            $this->subject = json_encode( $decoded, JSON_NUMERIC_CHECK + JSON_THROW_ON_ERROR );

            return $this->count += $count;
        }

        unset( $decoded );

        if ( $this->flags & self::REPLACE_FLAG_REGULAR )
        {
            $temp = str_replace( $this->search, $this->replace, $this->subject, $this->count );
        }

        elseif ( $this->flags & self::REPLACE_FLAG_MULTI_BYTE )
        {
            // Normalize $search and $replace, so they are both arrays of the same length
            $searches     = array_values( (array) $this->search );
            $replacements = array_values( (array) $this->replace );

            if ( ( $s = count( $searches ) ) !== ( $r = count( $replacements ) ) )
            {
                if ( $s < $r )
                {
                    array_splice( $replacements, $s, $r );
                }
                else
                {
                    array_splice( $replacements, $r, 0, array_fill( 0, $r - $s, '' ) );
                }
            }

            $replacements = array_combine( $searches, $replacements );
            $temp         = $this->subject;

            unset( $searches, $s, $r );

            $this->count = 0;

            foreach ( $replacements as $s => $r )
            {
                //$parts = \mb_split( $s, $temp );
                //if ( $parts === false || count( $parts ) < 2 )
                //{
                //    continue;
                //}
                $this->count += count( $parts ) - 1;
                $temp        = \mb_ereg_replace( $s, $r, $temp, 'z' );
            }

            $this->subject = $temp;
        }

        elseif ( $this->flags & self::REPLACE_FLAG_REGEX )
        {
            $temp = preg_replace(
                $this->search,
                $this->replace,
                $this->subject,
                - 1,
                $this->count
            );
        }

        switch ( $this->type )
        {
        case 'string':
            $this->subject = $temp;

            return $this->count;

        case 'int':
        case 'integer':
            $this->subject = (integer) $temp;

            return $this->count;

        case 'float':
            $this->subject = (float) $temp;

            return $this->count;

        case 'double':
            $this->subject = (double) $temp;

            return $this->count;

        }

        throw new \InvalidArgumentException();
    }


    public static function isPerlRegex( $regex ): bool
    {

        // check if search string can be evaluated as regex. If not, preg_match will return false;
        return false !== @preg_match( $regex, '' );

    }


    public static function regexNormalizeWhitespace( ?string $regex ): ?string
    {

        if ( ! preg_match( '/^(?<delim>.)(?<regex>.+)\1(?<flags>[gmixXsuUAJD]*)$/s', $regex, $matches ) )
        {
            return $regex;
        }

        $delim = $matches['delim'];
        $regex = $matches['regex'];
        $flags = str_split( $matches['flags'] ?? '' );

        if ( ! in_array( 'x', $flags, true ) )
        {
            return $regex;
        }

        $replace = [
            // non-capturing and named parenthesis
            '@\s*(?<!\\\\)#.*\n@s' => '',
            '@(?<!\\\\|\[)\s@'     => '',
        ];

        $regex = preg_replace( array_keys( $replace ), array_values( $replace ), $regex );

        return $regex;
    }


    public static function regexPerlToMysql(
        $regex,
        $connection = null
    ): ?string {

        if ( ! preg_match( '/^(?<delim>.)(?<regex>.+)\1(?<flags>[gmixXsuUAJD]*)$/s', $regex, $matches ) )
        {
            return null;
        }

        $delim = $matches['delim'];
        $regex = $matches['regex'];
        $flags = str_split( $matches['flags'] ?? '' );

        $replace = [
            "\\$delim" => $delim,
            '\b'       => '',
        ];

        $regex = str_replace( array_keys( $replace ), array_values( $replace ), $regex );

        $replace = [
            // non-capturing and named parenthesis
            '@(?<!\\\\)\(\?(\:|P?[<\']\w+[>\'])@' => '(',
            '@\\(\\?<![^)]+\\)@'                  => '',
        ];

        if ( in_array( 'x', $flags, true ) )
        {
            $replace['@(?<!\\\\|\[)\s@'] = '';
        }

        $regex = preg_replace( array_keys( $replace ), array_values( $replace ), $regex );

        while ( $connection !== null )
        {

            $sql = "SELECT '' REGEXP '$regex';";

            if ( $connection instanceof \PDO )
            {
                try
                {
                    $connection->query( $sql );
                }
                catch ( \Exception $e )
                {
                    return null;
                }

                if ( ! $connection->errorCode() == 0 )

                {
                    return $regex;
                }

                return null;
            }

            if ( $connection instanceof \mysqli )
            {
                try
                {
                    $connection->query( $sql );
                }
                catch ( \Exception $e )
                {
                    return null;
                }

                return $regex;
            }

            if ( ! is_resource( $connection ) )
            {
                return null;
            }

            try
            {
                /** @noinspection PhpParamsInspection */
                mysqli_query( $connection, $sql );
            }
            catch ( \Exception $e )
            {
                return null;
            }

            $connection = null;
        }

        return $regex;
    }

}