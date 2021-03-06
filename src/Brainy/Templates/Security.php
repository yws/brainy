<?php
/**
 * Security API for Smarty
 *
 * @package    Brainy
 * @subpackage Security
 * @author     Uwe Tews
 */

namespace Box\Brainy\Templates;

/**
 * @todo getter and setter instead of public properties would allow cultivating an internal cache properly
 * @todo current implementation of isTrustedResourceDir() assumes that Brainy::$template_dir are immutable
 * @todo the cache is killed every time either of the variables change. That means that two distinct Smarty objects with differing
 * @todo $template_dir should NOT share the same Security instance as this would lead to (severe) performance penalty! how should this be handled?
 */
class Security
{
    /**
     * This is an array of trusted PHP functions.
     *
     * If empty all functions are allowed. If null, no functions are allowed.
     *
     * @var string[]|null
     */
    public $php_functions = array(
        'isset', 'empty',
        'count', 'sizeof',
        'in_array', 'is_array',
        'time',
        'nl2br',
    );
    /**
     * This is an array of trusted PHP modifiers.
     *
     * If empty all modifiers are allowed. If null, no modifiers are allowed.
     *
     * @var string[]|null
     */
    public $php_modifiers = array(
        'abs',
        'base64_encode',
        'ceil',
        'count',
        'floor',
        'htmlspecialchars',
        'implode',
        'json_encode',
        'ltrim',
        'md5',
        'number_format',
        'rtrim',
        'sha1',
        'split',
        'str_repeat',
        'urlencode',
    );
    /**
     * This is an array of allowed tags.
     *
     * If empty no restriction by allowed_tags.
     *
     * @var string[]
     */
    public $allowed_tags = array();
    /**
     * This is an array of disabled tags.
     *
     * If empty no restriction by disabled_tags.
     *
     * @var string[]
     */
    public $disabled_tags = array();
    /**
     * This is an array of allowed modifier plugins.
     *
     * If empty no restriction by allowed_modifiers.
     *
     * @var string[]
     */
    public $allowed_modifiers = array();
    /**
     * This is an array of disabled modifier plugins.
     *
     * If empty no restriction by disabled_modifiers.
     *
     * @var string[]
     */
    public $disabled_modifiers = array();
    /**
     * Cache for $resource_dir lookups
     *
     * @internal
     */
    protected $_resource_dir = null;
    /**
     * Cache for $template_dir lookups
     *
     * @internal
     */
    protected $_template_dir = null;
    /**
     * Cache for $secure_dir lookups
     *
     * @internal
     */
    protected $_secure_dir = null;
    /**
     * @internal
     */
    private $smarty = null;

    /**
     * Constructs a new security policy
     * @param \Box\Brainy\Brainy $smarty An instance of Brainy
     */
    public function __construct($smarty)
    {
        $this->smarty = $smarty;
    }

    /**
     * Check if PHP function is trusted.
     *
     * @param  string $function_name The name of the PHP function
     * @param  object $compiler      compiler object
     * @return boolean                 true if function is trusted
     * @throws SmartyCompilerException if php function is not trusted
     */
    public function isTrustedPhpFunction($function_name, $compiler)
    {
        if (isset($this->php_functions)
            && (empty($this->php_functions)
            || in_array($function_name, $this->php_functions))
        ) {
            return true;
        }

        $compiler->trigger_template_error("PHP function '{$function_name}' not allowed by security setting");

        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if PHP modifier is trusted.
     *
     * @param  string $modifier_name The name of the PHP function
     * @param  object $compiler      compiler object
     * @return boolean                 true if modifier is trusted
     * @throws SmartyCompilerException if modifier is not trusted
     */
    public function isTrustedPhpModifier($modifier_name, $compiler)
    {
        if (isset($this->php_modifiers) && (empty($this->php_modifiers) || in_array($modifier_name, $this->php_modifiers))) {
            return true;
        }

        $compiler->trigger_template_error("modifier '{$modifier_name}' not allowed by security setting");

        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if tag is trusted.
     *
     * @param  string $tag_name The name of the tag
     * @param  object $compiler compiler object
     * @return boolean                 true if tag is trusted
     * @throws SmartyCompilerException if modifier is not trusted
     */
    public function isTrustedTag($tag_name, $compiler)
    {
        // check for internal always required tags
        if (in_array($tag_name, array('assign', 'call', 'private_block_plugin', 'private_function_plugin', 'private_registered_function', 'private_registered_block', 'private_special_variable', 'private_print_expression', 'private_modifier'))) {
            return true;
        }
        // check security settings
        if (empty($this->allowed_tags)) {
            if (empty($this->disabled_tags) || !in_array($tag_name, $this->disabled_tags)) {
                return true;
            }
            $compiler->trigger_template_error("tag '{$tag_name}' disabled by security setting", $compiler->lex->taglineno);
        } elseif (in_array($tag_name, $this->allowed_tags) && !in_array($tag_name, $this->disabled_tags)) {
            return true;
        }
        $compiler->trigger_template_error("tag '{$tag_name}' not allowed by security setting", $compiler->lex->taglineno);

        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if modifier plugin is trusted.
     *
     * @param  string $modifier_name The name of the modifier
     * @param  object $compiler      compiler object
     * @return boolean                 true if tag is trusted
     * @throws SmartyCompilerException if modifier is not trusted
     */
    public function isTrustedModifier($modifier_name, $compiler)
    {
        // check for internal always allowed modifier
        if (in_array($modifier_name, array('default'))) {
            return true;
        }
        // check security settings
        if (empty($this->allowed_modifiers)) {
            if (empty($this->disabled_modifiers) || !in_array($modifier_name, $this->disabled_modifiers)) {
                return true;
            }
            $compiler->trigger_template_error("modifier '{$modifier_name}' disabled by security setting", $compiler->lex->taglineno);
        } elseif (in_array($modifier_name, $this->allowed_modifiers) && !in_array($modifier_name, $this->disabled_modifiers)) {
            return true;
        }
        $compiler->trigger_template_error("modifier '{$modifier_name}' not allowed by security setting", $compiler->lex->taglineno);

        return false; // should not, but who knows what happens to the compiler in the future?
    }

    /**
     * Check if directory of file resource is trusted.
     *
     * @param  string $filepath The file path to test
     * @return boolean         true if directory is trusted
     * @throws SmartyException if directory is not trusted
     */
    public function isTrustedResourceDir($filepath)
    {
        $_template = false;
        $_secure = false;

        $_template_dir = $this->smarty->getTemplateDir();

        // check if index is outdated
        if ((!$this->_template_dir || $this->_template_dir !== $_template_dir)
            || (!empty($this->secure_dir) && (!$this->_secure_dir || $this->_secure_dir !== $this->secure_dir))
        ) {
            $this->_resource_dir = array();
            $_template = true;
            $_secure = !empty($this->secure_dir);
        }

        // rebuild template dir index
        if ($_template) {
            $this->_template_dir = $_template_dir;
            foreach ($_template_dir as $directory) {
                $directory = realpath($directory);
                $this->_resource_dir[$directory] = true;
            }
        }

        // rebuild secure dir index
        if ($_secure) {
            $this->_secure_dir = $this->secure_dir;
            foreach ((array) $this->secure_dir as $directory) {
                $directory = realpath($directory);
                $this->_resource_dir[$directory] = true;
            }
        }

        $_filepath = realpath($filepath);
        $directory = dirname($_filepath);
        $_directory = array();
        while (true) {
            // remember the directory to add it to _resource_dir in case we're successful
            $_directory[$directory] = true;
            // test if the directory is trusted
            if (isset($this->_resource_dir[$directory])) {
                // merge sub directories of current $directory into _resource_dir to speed up subsequent lookups
                foreach ($_directory as $k => $v) {
                    $this->_resource_dir[$k] = $v;
                }

                return true;
            }
            // abort if we've reached root
            if (($pos = strrpos($directory, DIRECTORY_SEPARATOR)) === false || !isset($directory[1])) {
                break;
            }
            // bubble up one level
            $directory = substr($directory, 0, $pos);
        }

        // give up
        throw new \Box\Brainy\Exceptions\SmartyException("directory '{$_filepath}' not allowed by security setting");
    }
}
