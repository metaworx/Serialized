<?php

namespace Serialized\ArrayNotation;

use Serialized\ObjectNotation\ClassName as ObjectNotationClassName;

class ClassName
    extends StringValue
{

    // constants
    public const    DEFAULT_TERMINATION = ObjectNotationClassName::DEFAULT_TERMINATION;
    public const    TYPE                = ObjectNotationClassName::TYPE;
    public const    TYPE_CHAR           = ObjectNotationClassName::TYPE_CHAR;
    public const    TYPE_NAME           = ObjectNotationClassName::TYPE_NAME;

}