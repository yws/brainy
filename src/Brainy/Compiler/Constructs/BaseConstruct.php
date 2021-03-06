<?php

namespace Box\Brainy\Compiler\Constructs;

use \Box\Brainy\Exceptions\SmartyCompilerException;

abstract class BaseConstruct
{

    /**
     * Compiles the opening tag for a function
     * @param  \Box\Brainy\Compiler\TemplateCompiler $compiler A compiler reference
     * @param  array|null                            $args     Arguments
     * @return mixed
     */
    public static function compileOpen(\Box\Brainy\Compiler\TemplateCompiler $compiler, $args)
    {
        throw new \Exception('Not Implemented!');
    }

    /**
     * Returns whether an argument is present or not
     * @param  array|null $args The argument list
     * @param  string     $name The argument name
     * @return bool
     */
    public static function hasArg(array $args, $name)
    {
        if (isset($args[$name])) {
            return true;
        }
        foreach ($args as $arg) {
            if (!is_array($arg) || !isset($arg[$name])) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * Returns an argument from the args array
     * @param  array|null $args The argument list
     * @param  string     $name The argument name
     * @return mixed
     */
    public static function getRequiredArg(array $args, $name)
    {
        if (isset($args[$name])) {
            return $args[$name];
        }
        foreach ($args as $arg) {
            if (!is_array($arg) || !isset($arg[$name])) {
                continue;
            }
            return $arg[$name];
        }
        throw new SmartyCompilerException('Expected argument not found; missing "' . $name . '" attribute.');
    }

    /**
     * Returns an argument from the args array or a default
     * @param  array|null      $args    The argument list
     * @param  string          $name    The argument name
     * @param  mixed|null|void $default The default value
     * @return mixed
     */
    public static function getOptionalArg(array $args, $name, $default = null)
    {
        if (isset($args[$name])) {
            return $args[$name];
        }
        foreach ($args as $arg) {
            if (is_string($arg) && $arg === $name) {
                return true;
            }
            if ($arg instanceof \Box\Brainy\Compiler\Wrappers\StaticWrapper && (string) $arg === var_export($name, true)) {
                return true;
            }
            if (!is_array($arg) || !isset($arg[$name])) {
                continue;
            }
            return $arg[$name];
        }
        return $default;
    }

    /**
     * Push opening tag name on stack
     *
     * Optionally additional data can be saved on stack
     *
     * @param object $compiler compiler object
     * @param string $openTag  the opening tag's name
     * @param mixed  $data     optional data saved
     */
    public static function openTag($compiler, $openTag, $data = null)
    {
        array_push($compiler->_tag_stack, array($openTag, $data));
    }

    /**
     * Pop closing tag
     *
     * Raise an error if this stack-top doesn't match with expected opening tags
     *
     * @param  \Box\Brainy\Compiler\TemplateCompiler $compiler
     * @param  array|string                          $expectedTag the expected opening tag names
     * @return mixed        any type the opening tag's name or saved data
     */
    public static function closeTag($compiler, $expectedTag)
    {
        if (count($compiler->_tag_stack) === 0) {
            // wrong nesting of tags
            $compiler->trigger_template_error("unexpected closing tag", $compiler->lex->taglineno);
        }

        // get stacked info
        list($openTag, $data) = array_pop($compiler->_tag_stack);
        // open tag must match with the expected ones
        if (!in_array($openTag, (array) $expectedTag)) {
            // wrong nesting of tags
            $compiler->trigger_template_error("unclosed {$compiler->smarty->left_delimiter}" . $openTag . "{$compiler->smarty->right_delimiter} tag");
            return;
        }

        return is_null($data) ? $openTag : $data;
    }


    /**
     * Flattens an associative array of arrays into an associative array of strings
     * @param array $arr
     * @return array
     */
    protected static function flattenCompiledArray($arr)
    {
        $flattened = array();
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $innerKey => $innerVal) {
                    $flattened[$innerKey] = (string) $innerVal;
                }
            } else {
                $flattened[$key] = (string) $val;
            }
        }
        return $flattened;
    }


    /**
     * Collapse an array of strings into an array with pre-encoded values.
     * This is very similar to var_export($arr, true), but the values of the
     * associative array are not changed.
     * @param string[] $arr
     * @return string
     */
    protected static function exportArray($arr)
    {
        $out = 'array(';

        $first = true;
        foreach ($arr as $key => $value) {
            if (!$first) {
                $out .= ', ';
            }
            $first = false;
            $out .= var_export($key, true);
            $out .= ' => ';
            $out .= $value;
        }

        $out .= ')';
        return $out;
    }
}
