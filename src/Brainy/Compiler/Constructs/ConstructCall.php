<?php

namespace Box\Brainy\Compiler\Constructs;

class ConstructCall extends BaseConstruct
{
    /**
     * @param  \Box\Brainy\Compiler\TemplateCompiler $compiler A compiler reference
     * @param  array|null                            $args     Arguments
     * @return mixed
     */
    public static function compileOpen(\Box\Brainy\Compiler\TemplateCompiler $compiler, $args)
    {

        $name = self::getRequiredArg($args, 'name');
        $assign = self::getOptionalArg($args, 'assign');

        $paramArray = self::flattenCompiledArray($args);
        $paramArray = self::exportArray($paramArray);

        $tmpVar = '$' . $compiler->getUniqueVarName();
        // Evaluate the function name dynamically at runtime
        $output = "$tmpVar = $name;\n";
        // Safety Dance
        $output .= "if (!array_key_exists($tmpVar, \$_smarty_tpl->tpl_vars['smarty']['functions'])) {\n";
        $output .= "  \$funcs = implode(', ', array_keys(\$_smarty_tpl->tpl_vars['smarty']['functions'])) ?: '<none>';\n";
        $output .= "  throw new \\Box\\Brainy\\Exceptions\\SmartyException('Call to undefined function \\'' . $tmpVar . '\\'. Defined functions: ' . \$funcs);\n";
        $output .= "}\n";

        if ($assign) {
            $output .= "ob_start();\n";
        }
        $output .= "\$_smarty_tpl->tpl_vars['smarty']['functions'][$tmpVar]($paramArray);\n";
        if ($assign) {
            $output .= "\$_smarty_tpl->setVariable($assign, ob_get_clean());\n";
        }

        return $output;
    }
}
