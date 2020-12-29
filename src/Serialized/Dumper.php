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

use Exception;
use InvalidArgumentException;
use stdClass;

/**
 * Serialize Dumper
 *
 * Abstract Dumper Class
 */
abstract class Dumper
    implements ValueTypes
{
// protected properties
    /**
     * configuration local store
     *
     * @var array
     */
    protected $config = [];

    /**
     * dumper state
     *
     * @var stdClass
     */
    protected $state;
// private properties
    /**
     * stack of states
     *
     * @var array
     */
    private $stack = [];


    public function __construct()
    {

        $this->stateInit();
    }


    /**
     * get dump as string
     *
     * @param  array  $parsed  serialized array notation data to be dumped.
     * @param  array  $config  (optional) dumper configuration
     *
     * @return string
     * @throws \Exception
     */
    public function getDump(
        array $parsed,
        array $config = []
    ) {

        if (count($parsed) != 2)
        {
            throw new InvalidArgumentException(
                sprintf('Parsed is expected to be an array of two values, array has %d values.', count($parsed))
            );
        }
        ob_start();
        try
        {
            $this->dump($parsed, $config);
        }
        catch (Exception $e)
        {
            $buffer                    = ob_get_clean();
            $e->serializedDumpFragment = $buffer;
            throw $e;
        }

        return ob_get_clean();
    }


    public function setConfig(array $config)
    {

        $this->config = $this->configMergeDeep($this->config, $config);
    }


    /**
     * config/ini (n-depth) array_merge
     *
     * config is an array without numerical keys and with an n-depth but
     *
     * if a non-array is to be set to an array, it will fail. the definition
     * is by default ($source), overwriters (sub or superset of $add) will get killed.
     *
     * @param  array  $source
     * @param  array  $add
     * @param  bool   $noticeUndefined
     *
     * @return array
     */
    protected function configMergeDeep(
        array $source,
        array $add,
        $noticeUndefined = true
    ) {

        static $base = '';
        foreach ($add as $key => $value)
        {
            $path = $base . '/' . $key;
            if (is_int($key))
            {
                continue;
            }
            if (true === is_array($value))
            {
                $value = $this->configMergeDeep([], $value, false); // merge with yourself, will trigger lot of errors
            }
            if (!array_key_exists($key, $source))
            {
                if ($noticeUndefined)
                {
                    trigger_error(sprintf('Configuration "%s" was not defined.', $path), E_USER_NOTICE);
                }
                $source[$key] = $value;
                continue;
            }
            if (!is_array($source[$key]) && !is_array($value))
            {
                $source[$key] = $value;
                continue;
            }
            if (!is_array($source[$key]) && is_array($value))
            {
                trigger_error(
                    sprintf('Can not merge array (key: "%s") into a non-array config entry.', $key),
                    E_USER_WARNING
                );
                continue;
            }
            if (is_array($source[$key]) && !is_array($value))
            {
                trigger_error(
                    sprintf('Can not overwrite existing array (key: "%s") with a value ("%s").', $key, $value),
                    E_USER_WARNING
                );
                continue;
            }
            [$save, $base] = [$base, $path];
            $source[$key] = $this->configMergeDeep($source[$key], $value);
            $base         = $save;
        }

        return $source;
    }


    /**
     * dump array notation
     *
     * @param  array  $parsed  serialized array notation data to be dumped.
     * @param  array  $config  (optional) dumper configuration
     */
    final public function dump(
        array $parsed,
        array $config = []
    ) {

        if (count($parsed) !== 2)
        {
            throw new InvalidArgumentException(
                sprintf('Parsed is expected to be an array of two values, array has %d values.', count($parsed))
            );
        }
        $config && $this->setConfig($config);
        $this->dumpConcrete($parsed);
    }


    abstract protected function dumpConcrete(array $parsed);


    /**
     * @param  string  $memberName
     *
     * @return array $name, $class, $access (0:public,1:protected,2:private)
     */
    protected function parseMemberName($memberName)
    {

        $name   = (string)$memberName;
        $class  = '';
        $access = 0;
        if ("\x00" === $name[0])
        {
            if ("\x00*\x00" === substr($name, 0, 3))
            {
                $name   = substr($name, 3);
                $access = 1;
            }
            elseif (false !== $pos = strpos($name, "\x00", 1))
            {
                $access = 2;
                $class  = substr($name, 1, $pos - 1);
                $name   = substr($name, $pos + 1);
            }
            else
            {
                // @codeCoverageIgnoreStart
                throw new InvalidArgumentException(sprintf('Invalid member-name: "%s".', $memberName));
            }    // @codeCoverageIgnoreEnd
        }

        return [$name, $class, $access];
    }


    private function stateInit()
    {

        $state        = new stdClass();
        $state->level = 0;
        $state->inset = '';
        $this->state  = $state;
    }


    /**
     * pop state from stack
     */
    protected function statePop()
    {

        $this->state = array_pop($this->stack);
    }


    /**
     * push the current state onto the stack
     */
    protected function statePush()
    {

        array_push($this->stack, clone $this->state);
        $this->state->level++;
    }


    /**
     * @param  null   $type
     * @param  array  $config
     *
     * @return \Serialized\Dumper\Concrete
     */
    public static function factory(
        $type = null,
        array $config = []
    ) {

        if (!is_string($type) && null !== $type)
        {
            throw new InvalidArgumentException(sprintf('Type expected string, %s given (%s).', gettype($type), $type));
        }
        null === $type && $type = 'text';
        $dumperClass = ucfirst(strtolower($type));
        if ($dumperClass === 'Xml')
        {
            $dumperClass = 'XML';
        } // Dumper\XML is all caps
        $class  = sprintf('%s\Dumper\%s', __NAMESPACE__, $dumperClass);
        $dumper = new $class();
        $config && $dumper->setConfig($config);

        return $dumper;
    }

}