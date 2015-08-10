<?php

namespace Box\Brainy\Compiler\Helpers;


class DoubleQuotedContent extends ParseTree
{
    /**
     * Create parse tree buffer with string content
     *
     * @param object $parser parser object
     * @param string $data   string section
     */
    public function __construct($parser, $data) {
        $this->parser = $parser;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function to_inline_data() {
        return $this->data;
    }

    /**
     * Return content as double quoted string
     *
     * @return string doubled quoted string
     */
    public function to_smarty_php() {
        return '"' . $this->data . '"';
    }

    /**
     * @return bool
     */
    public function can_combine_inline_data() {
        return true;
    }

}