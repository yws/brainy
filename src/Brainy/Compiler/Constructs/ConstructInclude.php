<?php

namespace Box\Brainy\Compiler\Constructs;

use \Box\Brainy\Brainy;
use \Box\Brainy\Exceptions\SmartyCompilerException;

class ConstructInclude extends BaseConstruct
{
    /**
     * Compiles the opening tag for a function
     * @param  \Box\Brainy\Compiler\TemplateCompiler $compiler A compiler reference
     * @param  array|null                            $args     Arguments
     * @return mixed
     */
    public static function compileOpen(\Box\Brainy\Compiler\TemplateCompiler $compiler, $args)
    {
        try {
            $file = self::getRequiredArg($args, 'file');
        } catch (SmartyCompilerException $e) {
            $compiler->assertIsNotStrict('Include shorthand is not allowed in strict mode. Use the file="" attribute instead.');
            if (!isset($args[0])) {
                throw $e;
            }
            $file = $args[0];
        }
        $file = (string) $file;
        $assign = self::getOptionalArg($args, 'assign');
        $compileID = self::getOptionalArg(
            $args,
            'compile_id',
            var_export($compiler->smarty->compile_id, true)
        );
        $scope = ConstructAssign::getScope($args, Brainy::SCOPE_LOCAL);

        if (!$assign) {
            return self::getDisplayCode($file, $compileID, $scope, $args);
        }

        $output = 'ob_start();';
        $output .= self::getDisplayCode($file, $compileID, $scope, $args);
        $output .= "\$_smarty_tpl->assign($assign, ob_get_clean(), $scope);\n";
        return $output;

    }

    /**
     * Gets the PHP code to execute the included template
     * @param  string      $templatePath
     * @param  string|null $compileID
     * @param  int         $scope
     * @return string
     */
    protected static function getDisplayCode($templatePath, $compileID, $scope, $data)
    {
        if ($templatePath instanceof \Box\Brainy\Compiler\Helpers\ParseTree) {
            $templatePath = $templatePath->toSmartyPHP();
        }

        $data = self::flattenCompiledArray($data);
        unset($data['assign']);
        unset($data['compile_id']);
        unset($data['file']);
        unset($data['inline']);
        unset($data['scope']);
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                unset($data[$key]);
            }
        }

        return '$_smarty_tpl->renderSubTemplate(' .
            $templatePath . ', ' .
            ($compileID ?: null) . ', ' .
            self::exportArray($data, true) . ', ' .
            var_export($scope, true) .
            ");\n";
    }
}
