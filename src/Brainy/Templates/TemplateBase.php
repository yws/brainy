<?php
/**
 * @package Brainy
 * @author Matt Basta
 * @author Uwe Tews
 */

namespace Box\Brainy\Templates;

use Box\Brainy\Brainy;
use Box\Brainy\Exceptions\SmartyException;


abstract class TemplateBase extends TemplateData
{
    /**
     * Template resource
     * @var string
     */
    public $template_resource = null;

    /**
     * Renders and returns a template.
     *
     * This returns the template output instead of displaying it.
     *
     * @param  string|void $template         the resource handle of the template file or template object
     * @param  mixed|void  $cache_id         no-op
     * @param  mixed|void  $compile_id       compile id to be used with this template
     * @param  object|void $parent           next higher level of Brainy variables
     * @param  bool|void   $display          noop
     * @param  bool|void   $merge_tpl_vars   if true parent template variables merged in to local scope
     * @return string rendered template output
     */
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true) {
        if ($template === null && $this instanceof Template) {
            $template = $this;
        }
        if ($cache_id !== null && is_object($cache_id)) {
            $parent = $cache_id;
            $cache_id = null;
        }
        if ($parent === null && ($this instanceof Brainy || is_string($template))) {
            $parent = $this;
        }
        // create template object if necessary
        $_template = ($template instanceof Template)
            ? $template
            : $this->smarty->createTemplate($template, $cache_id, $compile_id, $parent, false);

        // merge all variable scopes into template
        if ($merge_tpl_vars) {
            // save local variables
            $save_tpl_vars = $_template->tpl_vars;
            $ptr_array = array($_template);
            $ptr = $_template;
            while (isset($ptr->parent)) {
                $ptr_array[] = $ptr = $ptr->parent;
            }
            $ptr_array = array_reverse($ptr_array);
            $parent_ptr = reset($ptr_array);
            $tpl_vars = $parent_ptr->tpl_vars;
            while ($parent_ptr = next($ptr_array)) {
                if (!empty($parent_ptr->tpl_vars)) {
                    $tpl_vars = array_merge($tpl_vars, $parent_ptr->tpl_vars);
                }
            }
            if (!empty(Brainy::$global_tpl_vars)) {
                $tpl_vars = array_merge(Brainy::$global_tpl_vars, $tpl_vars);
            }
            $_template->tpl_vars = $tpl_vars;
        }

        // dummy local smarty variable
        $_template->tpl_vars['smarty'] = new Variable();

        // must reset merge template date
        $_template->smarty->merged_templates_func = array();
        // get rendered template
        // checks if template exists
        if (!$_template->source->exists) {
            $parent_resource = '';
            if ($_template->parent instanceof Template) {
                $parent_resource = " in '{$_template->parent->template_resource}'";
            }
            throw new SmartyException("Unable to load template {$_template->source->type} '{$_template->source->name}'{$parent_resource}");
        }

        // read from cache or render
        if ($_template->source->uncompiled) {
            try {
                ob_start();
                $_template->source->renderUncompiled($_template);
            } catch (Exception $e) {
                ob_get_clean();
                throw $e;
            }
        } elseif ($_template->source->recompiled) {
            $_smarty_tpl = $_template;
            $code = $_template->compiler->compileTemplate($_template);
            try {
                ob_start();
                eval('?>' . $code);  // The closing PHP bit accounts for the opening PHP tag at the top of the compiled file
                unset($code);
            } catch (Exception $e) {
                ob_get_clean();
                throw $e;
            }
        } else {
            $_smarty_tpl = $_template;
            if (!$_template->compiled->exists || ($_template->smarty->force_compile && !$_template->compiled->isCompiled)) {
                $_template->compileTemplateSource();
                require $_template->compiled->filepath;
                $_template->compiled->loaded = true;
                $_template->compiled->isCompiled = true;
            }
            if (!$_template->compiled->loaded) {
                require $_template->compiled->filepath;
                if ($_template->mustCompile()) {
                    // recompile and load again
                    $_template->compileTemplateSource();
                    require $_template->compiled->filepath;
                    $_template->compiled->isCompiled = true;
                }
                $_template->compiled->loaded = true;
            } else {
                $_template->decodeProperties($_template->compiled->_properties, false);
            }
            try {
                ob_start();
                if (empty($_template->properties['unifunc']) || !is_callable($_template->properties['unifunc'])) {
                    throw new SmartyException("Invalid compiled template for '{$_template->template_resource}'");
                }
                array_unshift($_template->_capture_stack, array());
                //
                // render compiled template
                //
                call_user_func($_template->properties['unifunc'], $_template);
                // any unclosed {capture} tags ?
                if (isset($_template->_capture_stack[0][0])) {
                    throw new SmartyException("Not matching {capture} open/close in \"{$this->template_resource}\"");
                }
                array_shift($_template->_capture_stack);
            } catch (Exception $e) {
                ob_get_clean();
                // if (isset($code)) echo $code;
                throw $e;
            }
        }
        $output = ob_get_clean();

        if (!$_template->source->recompiled && empty($_template->properties['file_dependency'][$_template->source->uid])) {
            $_template->properties['file_dependency'][$_template->source->uid] = array($_template->source->filepath, $_template->source->timestamp, $_template->source->type);
        }
        if ($_template->parent instanceof Template) {
            $_template->parent->properties['file_dependency'] = array_merge($_template->parent->properties['file_dependency'], $_template->properties['file_dependency']);
            foreach ($_template->required_plugins as $code => $tmp1) {
                foreach ($tmp1 as $name => $tmp) {
                    foreach ($tmp as $type => $data) {
                        $_template->parent->required_plugins[$code][$name][$type] = $data;
                    }
                }
            }
        }

        if (isset($this->smarty->autoload_filters['output']) || isset($this->smarty->registered_filters['output'])) {
            $output = \Box\Brainy\Runtime\FilterHandler::runFilter('output', $output, $_template);
        }

        if ($merge_tpl_vars) {
            // restore local variables
            $_template->tpl_vars = $save_tpl_vars;
        }

        return $output;
    }

    /**
     * Renders the template.
     *
     * This displays the contents of a template. To return the contents of a
     * template into a variable, use the fetch() method instead.
     *
     * As an optional second and third parameter, you can pass a cache ID and
     * compile ID.
     *
     * A fourth parameter can be passed which passes the parent scope that the
     * template should use.
     *
     * @param string $template   the resource handle of the template file or template object
     * @param mixed  $cache_id   no-op
     * @param mixed  $compile_id compile id to be used with this template
     * @param object $parent     next higher level of Brainy variables
     * @return void
     */
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
        echo $this->fetch($template, null, $compile_id, $parent);
    }

    /**
     * Returns whether the template is cached.
     *
     * Note that calling this method will load the template into memory.
     * Subsequent calls to fetch() or display() will not reload the template
     * file. Calling clearCache() may also have no effect if this method has
     * returned true.
     *
     * @param  string|object $template   the resource handle of the template file or template object
     * @param  mixed         $cache_id   cache id to be used with this template
     * @param  mixed         $compile_id compile id to be used with this template
     * @return boolean       The template's cache status
     * @deprecated This method is a source of confusion, as it is based on the in-memory template.
     * @deprecated Caching in Brainy should be transparent. There should be no logic around it.
     */
    public function isCached($template = null, $cache_id = null, $compile_id = null) {
        return false;
    }

    /**
     * Registers plugin to be used in templates
     *
     * @param  string                       $type       plugin type
     * @param  string                       $tag        name of template tag
     * @param  callable                     $callback   PHP callback to register
     * @param  boolean                      $cacheable  if true (default) this fuction is cachable
     * @param  array|null                   $cache_attr caching attributes if any
     * @return Smarty_Internal_TemplateBase Self-reference to facilitate chaining
     * @throws SmartyException              when the plugin tag is invalid
     */
    public function registerPlugin($type, $tag, $callback, $cacheable = true, $cache_attr = null) {
        if (isset($this->smarty->registered_plugins[$type][$tag])) {
            throw new SmartyException("Plugin tag \"{$tag}\" already registered");
        } elseif (!is_callable($callback)) {
            throw new SmartyException("Plugin \"{$tag}\" not callable");
        }

        $this->smarty->registered_plugins[$type][$tag] = array($callback, (bool) $cacheable, (array) $cache_attr);
        return $this;
    }

    /**
     * Unregister Plugin
     *
     * @param  string                       $type of plugin
     * @param  string                       $tag  name of plugin
     * @return Smarty_Internal_TemplateBase Self-reference to facilitate chaining
     */
    public function unregisterPlugin($type, $tag) {
        if (isset($this->smarty->registered_plugins[$type][$tag])) {
            unset($this->smarty->registered_plugins[$type][$tag]);
        }

        return $this;
    }

    /**
     * Registers a resource to fetch a template
     *
     * @param  string                       $type     name of resource type
     * @param  \Box\Brainy\Resources\Resource|\Box\Brainy\Resources\Resource[] $callback Instance of \Box\Brainy\Resources\Resource, or array of callbacks to handle resource (deprecated)
     * @return Smarty_Internal_TemplateBase Self-reference to facilitate chaining
     */
    public function registerResource($type, $callback) {
        $this->smarty->registered_resources[$type] = $callback instanceof \Box\Brainy\Resources\Resource ? $callback : array($callback, false);

        return $this;
    }

    /**
     * Unregisters a resource
     *
     * @param  string                       $type name of resource type
     * @return Smarty_Internal_TemplateBase Self-reference to facilitate chaining
     */
    public function unregisterResource($type) {
        if (isset($this->smarty->registered_resources[$type])) {
            unset($this->smarty->registered_resources[$type]);
        }

        return $this;
    }

    /**
     * Registers a filter function
     *
     * @param  string                       $type     filter type
     * @param  callback                     $callback
     * @return Smarty_Internal_TemplateBase Self-reference to facilitate chaining
     * @uses Brainy::FILTER_POST
     * @uses Brainy::FILTER_PRE
     * @uses Brainy::FILTER_OUTPUT
     * @uses Brainy::FILTER_VARIABLE
     */
    public function registerFilter($type, $callback) {
        $this->smarty->registered_filters[$type][$this->_get_filter_name($callback)] = $callback;

        return $this;
    }

    /**
     * Unregisters a filter function
     *
     * @param  string                       $type     filter type
     * @param  callback                     $callback
     * @return Smarty_Internal_TemplateBase Self-reference to facilitate chaining
     */
    public function unregisterFilter($type, $callback) {
        $name = $this->_get_filter_name($callback);
        if (isset($this->smarty->registered_filters[$type][$name])) {
            unset($this->smarty->registered_filters[$type][$name]);
        }

        return $this;
    }

    /**
     * Return internal filter name
     *
     * @param  callback $function_name
     * @return string   internal filter name
     * @internal
     */
    public function _get_filter_name($function_name) {
        if (!is_array($function_name)) {
            return $function_name;
        }

        $_class_name = is_object($function_name[0]) ? get_class($function_name[0]) : $function_name[0];
        return $_class_name . '_' . $function_name[1];
    }

    /**
     * Load a filter of specified type and name
     *
     * @param  string          $type filter type
     * @param  string          $name filter name
     * @return bool
     * @throws SmartyException if filter could not be loaded
     */
    public function loadFilter($type, $name) {
        $_plugin = "smarty_{$type}filter_{$name}";
        $_filter_name = $_plugin;
        if (!$this->smarty->loadPlugin($_plugin)) {
            throw new SmartyException("{$type}filter \"{$name}\" not callable");
        }
        if (class_exists($_plugin, false)) {
            $_plugin = array($_plugin, 'execute');
        }
        if (is_callable($_plugin)) {
            $this->smarty->registered_filters[$type][$_filter_name] = $_plugin;
            return true;
        }
        return false;
    }

    /**
     * unload a filter of specified type and name
     *
     * @param  string                       $type filter type
     * @param  string                       $name filter name
     * @return Smarty_Internal_TemplateBase Self-reference to facilitate chaining
     */
    public function unloadFilter($type, $name) {
        $_filter_name = "smarty_{$type}filter_{$name}";
        if (isset($this->smarty->registered_filters[$type][$_filter_name])) {
            unset($this->smarty->registered_filters[$type][$_filter_name]);
        }

        return $this;
    }

    /**
     * preg_replace callback to convert camelcase getter/setter to underscore property names
     *
     * @param  string $match match string
     * @return string replacemant
     */
    private function replaceCamelcase($match) {
        return "_" . strtolower($match[1]);
    }

    /**
     * Handle unknown class methods
     *
     * @param string $name unknown method-name
     * @param array  $args argument array
     * @ignore
     */
    public function __call($name, $args) {
        static $_prefixes = array('set' => true, 'get' => true);
        static $_resolved_property_name = array();
        static $_resolved_property_source = array();

        // method of Brainy object?
        if (method_exists($this->smarty, $name)) {
            return call_user_func_array(array($this->smarty, $name), $args);
        }
        // see if this is a set/get for a property
        $first3 = strtolower(substr($name, 0, 3));
        if (isset($_prefixes[$first3]) && isset($name[3]) && $name[3] !== '_') {
            if (isset($_resolved_property_name[$name])) {
                $property_name = $_resolved_property_name[$name];
            } else {
                // try to keep case correct for future PHP 6.0 case-sensitive class methods
                // lcfirst() not available < PHP 5.3.0, so improvise
                $property_name = strtolower(substr($name, 3, 1)) . substr($name, 4);
                // convert camel case to underscored name
                $property_name = preg_replace_callback('/([A-Z])/', array($this, 'replaceCamelcase'), $property_name);
                $_resolved_property_name[$name] = $property_name;
            }
            if (isset($_resolved_property_source[$property_name])) {
                $_is_this = $_resolved_property_source[$property_name];
            } else {
                $_is_this = null;
                if (property_exists($this, $property_name)) {
                    $_is_this = true;
                } elseif (property_exists($this->smarty, $property_name)) {
                    $_is_this = false;
                }
                $_resolved_property_source[$property_name] = $_is_this;
            }
            if ($_is_this) {
                if ($first3 == 'get') {
                    return $this->$property_name;
                } else {
                    return $this->$property_name = $args[0];
                }
            } elseif ($_is_this === false) {
                if ($first3 == 'get') {
                    return $this->smarty->$property_name;
                } else {
                    return $this->smarty->$property_name = $args[0];
                }
            }
            throw new SmartyException("property '$property_name' does not exist.");
        }

        if ($name == 'Brainy') {
            throw new SmartyException("PHP5 requires you to call __construct() instead of Brainy()");
        }
        // must be unknown
        throw new SmartyException("Call of unknown method '$name'.");
    }

}