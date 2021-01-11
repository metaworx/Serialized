<?php

namespace Serialized\ObjectNotation;

/**
 * @property $data
 */
interface SimpleValueInterface
    extends \Serializable
{


    public function replace(
        $search,
        $replace,
        ?int $flags = null,
        ?array $callbacks = null
    ): int;

}