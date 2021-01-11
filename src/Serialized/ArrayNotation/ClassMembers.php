<?php

namespace Serialized\ArrayNotation;

use Serialized\ObjectNotation\ArrayElement;
use Serialized\ObjectNotation\ClassMembers as ObjectNotationClassMembers;

class ClassMembers
    extends ArrayValue
{

    // constants

    public const TYPE      = ObjectNotationClassMembers::TYPE;
    public const TYPE_CHAR = ObjectNotationClassMembers::TYPE_CHAR;
    public const TYPE_NAME = ObjectNotationClassMembers::TYPE_NAME;

}