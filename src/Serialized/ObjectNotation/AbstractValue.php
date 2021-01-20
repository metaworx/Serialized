<?php

namespace Serialized\ObjectNotation;

use Serialized\ParseException;
use Serialized\Parser;
use Serialized\Value;
use Serialized\ValueTypes;

abstract class AbstractValue
    implements Value
{

    use SimpleValueTrait;

    // constants
    protected const DEFAULT_DELIMITER   = self::TYPE_DELIMITER;
    protected const DEFAULT_TERMINATION = self::TYPE_TERMINATION;

    public const TYPE      = -1;
    public const TYPE_CHAR = '';

    public const TYPE_DELIMITER = ':';

    const TYPE_MIMIMUM_LEN = 2;

    public const TYPE_NAME = '';

    public const TYPE_TERMINATION = ';';

    // protected properties

    /** @var Parser|null */
    protected $parser;

    /** @var int|null */
    protected $originalPos;

    /** @var callable|null */
    protected $nativeValidator;

    /** @var callable|null */
    protected $internalValidator;


    public function __construct( $data = null )
    {

        if ( $data instanceof Parser )
        {

            $this->setParser( $data )
                 ->parse()
            ;
        }
        else
        {

            $this->setData( $data );
        }

    }


    protected function getDelimiter( bool $return = true ): string
    {

        return $return
            ? static::DEFAULT_DELIMITER
            : '';
    }


    /**
     * @return int|null
     */
    public function getOriginalPos(): ?int
    {

        return $this->originalPos;
    }


    /**
     * @inheritDoc
     */
    public function getParsed()
    {
        // TODO: Implement getParsed() method.
    }


    /**
     * @inheritDoc
     * @deprecated Use serialize() instead
     */
    public function getSerialized(): string
    {

        static::_depricated( __METHOD__, __CLASS__ . '::serialize' );

        return $this->serialize();
    }


    protected function getTermination(): ?string
    {

        return static::DEFAULT_TERMINATION;
    }


    /**
     * @inheritDoc
     */
    public function getTypeChar(): ?string
    {

        return static::TYPE_CHAR;
    }


    /**
     * @inheritDoc
     */
    public function getTypeName(): string
    {

        return static::TYPE_NAME;
    }


    /**
     * @param  mixed  $data
     *
     * @return Value
     */
    public function setData( $data )
    {

        if ( !$data instanceof Value )
        {
            if ( static::isLookingSerialized( $data ) )
            {
                return $this->unserialize( $data );
            }

            return $this->loadValue( $data );
        }

        $this->data = $data;

        $this->assertInternalDataType();

        return $this;
    }


    /**
     * @throws \Serialized\ParseException
     */
    protected function setDataArray( array $value ): self
    {

        $this->data = [];

        array_walk(
            $value,
            function ( NamedValueInterface $element )
            {

                $this->data[ $element->name->data ] = $element;
            }
        );

        return $this;
    }


    public function setParser( ?Parser $parser ): Value
    {

        $this->parser = $parser;

        if ( $parser !== null )
        {
            $this->originalPos = $parser->getPos() + 1;
            $this->data        = null;
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function setSerialized( $serialized )
    {

        if ( $serialized instanceof Parser )
        {
            $this->setParser( $serialized );
        }
        else
        {
            $this->setParser( new Parser( $serialized ) );
        }

        return $this->parse();
    }


    public function __debugInfo()
    {

        return [
            'data'        => $this->data,
            'originalPos' => $this->originalPos,
        ];
    }


    public function __serialize(): array
    {

        return [
            'data'        => $this->data,
            'originalPos' => $this->originalPos,
        ];
    }


    public function __unserialize( array $data ): void
    {

        $this->data        = $data[ 'data' ];
        $this->originalPos = $data[ 'originalPos' ];
    }


    protected function asserDataType(
        $caller,
        $validator,
        &$data,
        $failGracefully
    ): ?Exception {

        $e = null;

        if ( null === ( $validator = $validator ?? $this->nativeValidator ) )
        {
            $e = new \RuntimeException(
                sprintf( 'Method %s needs to be implemented in %s', __METHOD__, static::class )
            );
        }

        elseif ( !$validator( $data ) )
        {
            $e = $this->throwInvalidData( $data );
        }

        if ( $e === null )
        {
            return null;
        }

        if ( $failGracefully )
        {
            return $e;
        }

        throw $e;

    }


    protected function assertInternalDataType(
        $failGracefully = false
    ): ?Exception {

        return $this->asserDataType(
            __METHOD__,
            $this->internalValidator ?? $this->nativeValidator,
            $this->data,
            $failGracefully
        );

    }


    protected function assertNativeDataType(
        $data,
        $failGracefully = false
    ): ?Exception {

        return $this->asserDataType(
            __METHOD__,
            $this->nativeValidator,
            $data,
            $failGracefully
        );

    }


    protected function assertParserData(
        string $string,
        ?string $errMessage = null
    ): string {

        if ( ( $x = $this->parser->read( strlen( $string ) ) ) !== $string )
        {
            throw new ParseException(
                sprintf(
                    "%s '%s' at pos #%d (got '%s')\n\n%s",
                    $errMessage ?? 'Missing string',
                    $string,
                    $this->parser->getPos(),
                    $x,
                    $this->parser->getProcessedString()
                )
            );
        }

        return $x;
    }


    protected function assertQuote()
    {

        $this->assertParserData( '"', "Missing quotation mark" );
    }


    protected function assertStreamType()
    {

        if ( !$this->parser instanceof Parser )
        {
            if ( $this instanceof NullValue )
            {
                return;
            }

            throw new \RuntimeException( 'No parser set' );
        }

        if ( static::TYPE_CHAR === null )
        {
            return;
        }

        $this->assertParserData( static::TYPE_CHAR, "Missing or invalid type declaration mark" );

        if ( $this instanceof NullValue )
        {
            $this->assertTermination();
        }
        else
        {
            $this->assertParserData( ':', "Missing or invalid type separation mark" );
        }

    }


    protected function assertTermination( $terminator = null ): string
    {

        return $this->assertParserData( $terminator ?? static::DEFAULT_TERMINATION, "Missing termination mark" );
    }


    protected function assertType()
    {

        if ( $this->data !== null )
        {
            $this->assertInternalDataType();
        }

        $this->assertStreamType();
    }


    protected function loadValue( $data ): self
    {

        $this->assertNativeDataType( $data );
        $this->data = $data;
        $this->assertInternalDataType();
    }


    /**
     * @throws \Serialized\ParseException
     */
    protected function parse(): self
    {

        $this->parser->addElement( $this );
        $this->assertStreamType();
        $this->parseValue();
        $this->assertInternalDataType();

        return $this;
    }


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseArray( $class = ArrayElement::class ): array
    {

        $value = [];

        $len = $this->parseLength();

        $this->assertParserData( '{', "Missing array opening mark" );

        for ( $i = 0 ; $i < $len ; $i++ )
        {
            $key = $this->parser->parseValue();
            $val = $this->parser->parseValue();

            $value[] = new $class( $key, $val );
        }

        $this->assertParserData( '}', "Missing array closing mark" );

        return $value;
    }


    /**
     * @param  string|string[]  $deliminator
     *
     * @return int
     * @throws \Serialized\ParseException
     */
    protected function parseInt(
        $deliminator = ';',
        &$deliminatorFound = null,
        ?int &$leadingZeros = 0
    ): int {

        $signAllowed  = true;
        $len          = '';
        $sign         = '';
        $leadingZeros = 0;

        if ( !$this->parser instanceof Parser )
        {
            throw new ParseException(
                sprintf( "Invalid data type" )
            );
        }

        while ( true )
        {
            $buffer = $this->parser->read();

            switch ( $buffer )
            {
            case '+':
            case '-':
                if ( $signAllowed )
                {
                    $sign = $buffer;
                    break;
                }

                throw new ParseException(
                    sprintf( "Unexpected '%s' while reading lengths at pos #%d", $buffer, $this->parser->getPos() )
                );

            case '0' :
                if ( $len === '' )
                {
                    $leadingZeros++;
                    break;
                }
            case '1' :
            case '2' :
            case '3' :
            case '4' :
            case '5' :
            case '6' :
            case '7' :
            case '8' :
            case '9' :
                $len .= $buffer;
                break;

            case $deliminator:
                $deliminatorFound = $buffer;
                break 2;

            default:
                if ( is_array( $deliminator ) && in_array( $buffer, $deliminator, true ) )
                {
                    $deliminatorFound = $buffer;
                    break 2;
                }

                throw new ParseException(
                    sprintf(
                        "Unexpected character '%s' while reading lengths at pos #%d",
                        $buffer,
                        $this->parser->getPos()
                    )
                );
            }

            $signAllowed = false;
        }

        if ( $len === '' && $leadingZeros-- )
        {
            return 0;
        }

        $int = filter_var(
            $len,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0,
                ],
            ]
        );

        if ( $int === false )
        {
            throw new ParseException(
                sprintf(
                    "Invalid integer '%s' while reading length at pos #%d",
                    $len,
                    $this->parser->getPos()
                )
            );
        }

        if ( $sign === '-' )
        {
            return -$int;
        }

        return $int;
    }


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseLength(): int
    {

        return $this->parseInt( ':' );
    }


    /**
     * @throws \Serialized\ParseException
     */
    protected function parseString(): string
    {

        $len = $this->parseLength();

        $this->assertQuote();
        $value = $this->parser->read( $len );
        $this->assertQuote();

        return $value;
    }


    abstract protected function parseValue();


    /**
     * @inheritDoc
     */
    public function serialize(): ?string
    {

        $type  = $this->getTypeChar();
        $delim = $this->getDelimiter( !empty( $type ) );
        $value = $this->serializeValue();
        $term  = $this->getTermination();

        return sprintf( "%s%s%s%s", $type, $delim, $value, $term );
    }


    protected function serializeValue(): ?string
    {

        if ( $this->data === null )
        {
            return null;
        }

        if ( is_bool( $this->data ) )
        {
            return sprintf( '%d', (int) $this->data );
        }

        if ( is_string( $this->data ) )
        {
            return sprintf( '%d:"%s"', strlen( $this->data ), $this->data );
        }

        if ( is_numeric( $this->data ) )
        {
            return sprintf( '%s', $this->data );
        }

        if ( is_array( $this->data ) )
        {
            $values = array_map(
                static function ( $value )
                {

                    if ( $value instanceof SimpleValueInterface )
                    {
                        return $value->serialize();
                    }
                },
                $this->data
            );

            return sprintf( '%d:{%s}', count( $values ), implode( '', $values ) );
        }

        return $this->data->serialize();
    }


    protected function throwInvalidData( &$data = null )
    {

        return new ParseException(
            sprintf(
                'Invalid data %s for %s%s',
                $data ?? $this->data,
                static::class,
                $this->parser
                    ? " at pos #" . $this->parser->getPos()
                    : ''
            )
        );
    }


    /**
     * @param  string  $serialized
     *
     * @return \Serialized\ObjectNotation\AbstractValue
     * @throws \Serialized\ParseException
     */
    public function unserialize( $serialized )
    {

        return $this->setSerialized( $serialized );
    }


    public static function __set_state( $an_array )
    {

        $data              = $an_array[ 'data' ] ?? null;
        $self              = new static( $data );
        $self->originalPos = $an_array[ 'originalPos' ] ?? null;

        return $self;
    }


    /**
     * @inheritDoc
     */
    protected static function _depricated(
        string $what,
        string $alternative
    ) {

        trigger_error( sprintf( "%s is deprecated. Please use %s instead.", $what, $alternative ), E_USER_DEPRECATED );
    }


    /**
     * Check whether string looks like serialized data, without parsing it. Only checks the outermost value type.
     *
     * @since 2.0.5
     *
     * @param  string  $data  Serialized data.
     * @param  string|string[]|null Accepted identifiers
     *
     * @return bool False if not a serialized string, true if it is.
     */
    public static function isLookingSerialized(
        $data,
        $dataType = null
    ): bool {

        // if it isn't a string, it isn't a serialized.
        if ( !is_string( $data ) )
        {
            return false;
        }

        $data = trim( $data );

        // at least two characters are required: identifier + termination
        if ( strlen( $data ) < self::TYPE_MIMIMUM_LEN )
        {
            return false;
        }

        // get identifier
        $identifier = $data[ 0 ];

        // get value class (if exists)
        $valueClass = ValueTypes::TYPE_IDENTIFIERS[ $identifier ] ?? null;

        // if no a valid identifier, there will be no value class
        if ( $valueClass === null || !class_exists( $valueClass ) )
        {
            return false;
        }

        // check if only a subset of identifiers are accepted
        if ( $dataType !== null )
        {
            if ( is_string( $dataType ) )
            {
                $dataType = str_split( $dataType );
            }

            $dataType = array_intersect( (array) $dataType, array_keys( ValueTypes::TYPE_IDENTIFIERS ) );

            // no valid identifier given
            if ( empty( $dataType ) )
            {
                return false;
            }

            // check if found identifier is among the ones accepted
            if ( !in_array( $identifier, $dataType, true ) )
            {
                return false;
            }
        }

        return $valueClass::isLookingSerializedHelper( $data );

    }


    /**
     * Check whether serialized data is of string type.
     *
     * @since 2.0.5
     *
     * @param  string  $data  Serialized data.
     *
     * @return bool False if not a serialized string, true if it is.
     */
    protected static function isLookingSerializedHelper(
        $data
    ): bool {

        // check if current value class defines a DELIMITER and if so, whether they match
        if ( static::TYPE_DELIMITER && $data[ 1 ] !== static::TYPE_DELIMITER )
        {
            return false;
        }

        // check if current value class defines a TERMINATOR and if so, whether they match
        if ( static::TYPE_TERMINATION && static::TYPE_TERMINATION !== substr( $data, -1 ) )
        {
            return false;
        }

        return true;

    }

}