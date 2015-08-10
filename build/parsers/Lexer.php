<?php
/**
 * Smarty Internal Plugin Templatelexer
 *
 * This is the lexer to break the template source into tokens
 * @package Brainy
 * @subpackage Compiler
 * @author Uwe Tews
 */

namespace Box\Brainy\Compiler;

class Lexer
{
    public $data;
    public $counter;
    public $token;
    public $value;
    public $node;
    public $line;
    public $taglineno;
    public $state = 1;
    private $heredoc_id_stack = array();
    public $yyTraceFILE;
    public $yyTracePrompt;
    public $state_name = array (1 => 'TEXT', 2 => 'SMARTY', 3 => 'LITERAL', 4 => 'DOUBLEQUOTEDSTRING', 5 => 'CHILDBODY');
    public $smarty_token_names = array (   // Text for parser error messages
        'IDENTITY'  => '===',
        'NONEIDENTITY'  => '!==',
        'EQUALS'  => '==',
        'NOTEQUALS' => '!=',
        'GREATEREQUAL' => '(>=,ge)',
        'LESSEQUAL' => '(<=,le)',
        'GREATERTHAN' => '(>,gt)',
        'LESSTHAN' => '(<,lt)',
        'MOD' => '(%,mod)',
        'NOT'     => '(!,not)',
        'LAND'    => '(&&,and)',
        'LOR'     => '(||,or)',
        'LXOR'      => 'xor',
        'OPENP'   => '(',
        'CLOSEP'  => ')',
        'OPENB'   => '[',
        'CLOSEB'  => ']',
        'PTR'     => '->',
        'APTR'    => '=>',
        'EQUAL'   => '=',
        'NUMBER'  => 'number',
        'UNIMATH' => '+" , "-',
        'MATH'    => '*" , "/" , "%',
        'INCDEC'  => '++" , "--',
        'SPACE'   => ' ',
        'DOLLAR'  => '$',
        'SEMICOLON' => ';',
        'COLON'   => ':',
        'AT'    => '@',
        'QUOTE'   => '"',
        'VERT'    => '|',
        'DOT'     => '.',
        'COMMA'   => '","',
        'ANDSYM'    => '"&"',
        'QMARK'   => '"?"',
        'ID'      => 'identifier',
        'TEXT'    => 'text',
        'LITERALSTART'  => 'Literal start',
        'LITERALEND'    => 'Literal end',
        'LDELSLASH' => 'closing tag',
        'SETSTRICT' => 'setstrict',
        'COMMENT' => 'comment',
        'AS' => 'as',
        'TO' => 'to',
    );

    public function __construct($data,$compiler)
    {
        $this->data = $data;
        $this->counter = 0;
        $this->line = 1;
        $this->smarty = $compiler->smarty;
        $this->compiler = $compiler;
        $this->ldel = preg_quote($this->smarty->left_delimiter,'/');
        $this->ldel_length = strlen($this->smarty->left_delimiter);
        $this->rdel = preg_quote($this->smarty->right_delimiter,'/');
        $this->rdel_length = strlen($this->smarty->right_delimiter);
        $this->smarty_token_names['LDEL'] =  $this->smarty->left_delimiter;
        $this->smarty_token_names['RDEL'] =  $this->smarty->right_delimiter;
        $this->mbstring_overload = ini_get('mbstring.func_overload') & 2;
    }

    public function PrintTrace()
    {
        $this->yyTraceFILE = fopen('php://output', 'w');
        $this->yyTracePrompt = '<br>';
    }

 
    private $_yy_state = 1;
    private $_yy_stack = array();

    public function yylex()
    {
        return $this->{'yylex' . $this->_yy_state}();
    }

    public function yypushstate($state)
    {
        if ($this->yyTraceFILE) {
             fprintf($this->yyTraceFILE, "%sState push %s\n", $this->yyTracePrompt, isset($this->state_name[$this->_yy_state]) ? $this->state_name[$this->_yy_state] : $this->_yy_state);
        }
        array_push($this->_yy_stack, $this->_yy_state);
        $this->_yy_state = $state;
        if ($this->yyTraceFILE) {
             fprintf($this->yyTraceFILE, "%snew State %s\n", $this->yyTracePrompt, isset($this->state_name[$this->_yy_state]) ? $this->state_name[$this->_yy_state] : $this->_yy_state);
        }
    }

    public function yypopstate()
    {
       if ($this->yyTraceFILE) {
             fprintf($this->yyTraceFILE, "%sState pop %s\n", $this->yyTracePrompt,  isset($this->state_name[$this->_yy_state]) ? $this->state_name[$this->_yy_state] : $this->_yy_state);
        }
       $this->_yy_state = array_pop($this->_yy_stack);
        if ($this->yyTraceFILE) {
             fprintf($this->yyTraceFILE, "%snew State %s\n", $this->yyTracePrompt, isset($this->state_name[$this->_yy_state]) ? $this->state_name[$this->_yy_state] : $this->_yy_state);
        }

    }

    public function yybegin($state)
    {
       $this->_yy_state = $state;
        if ($this->yyTraceFILE) {
             fprintf($this->yyTraceFILE, "%sState set %s\n", $this->yyTracePrompt, isset($this->state_name[$this->_yy_state]) ? $this->state_name[$this->_yy_state] : $this->_yy_state);
        }
    }


 
    public function yylex1()
    {
        $tokenMap = array (
              1 => 0,
              2 => 0,
              3 => 1,
              5 => 0,
              6 => 0,
              7 => 0,
              8 => 1,
              10 => 0,
              11 => 0,
              12 => 0,
              13 => 0,
              14 => 0,
              15 => 0,
              16 => 0,
            );
        if ($this->counter >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/\G(\\{\\})|\G(".$this->ldel."\\*\\s*set strict\\s*\\*".$this->rdel.")|\G(".$this->ldel."\\*([\S\s]*?)\\*".$this->rdel.")|\G(".$this->ldel."\\s*strip\\s*".$this->rdel.")|\G(".$this->ldel."\\s*\/strip\\s*".$this->rdel.")|\G(".$this->ldel."\\s*literal\\s*".$this->rdel.")|\G(".$this->ldel."\\s*(if|elseif|else if|while)\\s+)|\G(".$this->ldel."\\s*for\\s+)|\G(".$this->ldel."\\s*foreach(?![^\s]))|\G(".$this->ldel."\\s*setfilter\\s+)|\G(".$this->ldel."\\s*\/)|\G(".$this->ldel."\\s*)|\G(\\s*".$this->rdel.")|\G([\S\s])/iS";

        do {
            if (preg_match($yy_global_pattern,$this->data, $yymatches, null, $this->counter)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched' .
                        ' an empty string.  Input "' . substr($this->data,
                        $this->counter, 5) . '... state TEXT');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice($yysubmatches, $this->token + 1,
                        $tokenMap[$this->token]);
                } else {
                    $yysubmatches = array();
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r1_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    // accept this token
                    return true;
                } elseif ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                } elseif ($r === false) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    if ($this->counter >= ($this->mbstring_overload ? mb_strlen($this->data,'latin1'): strlen($this->data))) {
                        return false; // end of input
                    }
                    // skip this token
                    continue;
                }            } else {
                throw new Exception('Unexpected input at line' . $this->line .
                    ': ' . $this->data[$this->counter]);
            }
            break;
        } while (true);

    } // end function


    const TEXT = 1;
    function yy_r1_1($yy_subpatterns)
    {

   $this->token = Parser::TP_TEXT;
     }
    function yy_r1_2($yy_subpatterns)
    {

   $this->token = Parser::TP_SETSTRICT;
     }
    function yy_r1_3($yy_subpatterns)
    {

   $this->token = Parser::TP_COMMENT;
     }
    function yy_r1_5($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false)  {
     $this->token = Parser::TP_TEXT;
   } else {
     $this->token = Parser::TP_STRIPON;
   }
     }
    function yy_r1_6($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
     $this->token = Parser::TP_STRIPOFF;
   }
     }
    function yy_r1_7($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
     $this->token = Parser::TP_LITERALSTART;
     $this->yypushstate(self::LITERAL);
    }
     }
    function yy_r1_8($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELIF;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r1_10($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELFOR;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r1_11($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELFOREACH;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r1_12($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELSETFILTER;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r1_13($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
     $this->token = Parser::TP_TEXT;
   } else {
     $this->token = Parser::TP_LDELSLASH;
     $this->yypushstate(self::SMARTY);
     $this->taglineno = $this->line;
   }
     }
    function yy_r1_14($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
     $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDEL;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r1_15($yy_subpatterns)
    {

   $this->token = Parser::TP_TEXT;
     }
    function yy_r1_16($yy_subpatterns)
    {

   if ($this->mbstring_overload) {
     $to = mb_strlen($this->data,'latin1');
   } else {
     $to = strlen($this->data);
   }
   preg_match("/{$this->ldel}/",$this->data,$match,PREG_OFFSET_CAPTURE,$this->counter);
   if (isset($match[0][1])) {
     $to = $match[0][1];
   }
   if ($this->mbstring_overload) {
     $this->value = mb_substr($this->data,$this->counter,$to-$this->counter,'latin1');
   } else {
     $this->value = substr($this->data,$this->counter,$to-$this->counter);
   }
   $this->token = Parser::TP_TEXT;
     }

 
    public function yylex2()
    {
        $tokenMap = array (
              1 => 0,
              2 => 0,
              3 => 1,
              5 => 0,
              6 => 0,
              7 => 0,
              8 => 0,
              9 => 0,
              10 => 0,
              11 => 0,
              12 => 0,
              13 => 0,
              14 => 1,
              16 => 1,
              18 => 1,
              20 => 0,
              21 => 0,
              22 => 0,
              23 => 0,
              24 => 0,
              25 => 0,
              26 => 0,
              27 => 0,
              28 => 0,
              29 => 0,
              30 => 0,
              31 => 0,
              32 => 0,
              33 => 0,
              34 => 0,
              35 => 0,
              36 => 0,
              37 => 3,
              41 => 0,
              42 => 0,
              43 => 0,
              44 => 0,
              45 => 0,
              46 => 0,
              47 => 0,
              48 => 0,
              49 => 1,
              51 => 1,
              53 => 0,
              54 => 0,
              55 => 0,
              56 => 0,
              57 => 0,
              58 => 0,
              59 => 0,
              60 => 0,
              61 => 0,
              62 => 0,
              63 => 0,
              64 => 0,
              65 => 1,
              67 => 0,
              68 => 0,
              69 => 0,
              70 => 0,
              71 => 0,
            );
        if ($this->counter >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/\G(\")|\G('[^'\\\\]*(?:\\\\.[^'\\\\]*)*')|\G([$]smarty\\.block\\.(child|parent))|\G(\\$)|\G(\\s*".$this->rdel.")|\G(\\s+is\\s+in\\s+)|\G(\\s+as\\s+)|\G(\\s+to\\s+)|\G(\\s+step\\s+)|\G(\\s*===\\s*)|\G(\\s*!==\\s*)|\G(\\s*==\\s*|\\s+eq\\s+)|\G(\\s*!=\\s*|\\s*<>\\s*|\\s+(ne|neq)\\s+)|\G(\\s*>=\\s*|\\s+(ge|gte)\\s+)|\G(\\s*<=\\s*|\\s+(le|lte)\\s+)|\G(\\s*>\\s*|\\s+gt\\s+)|\G(\\s*<\\s*|\\s+lt\\s+)|\G(\\s+mod\\s+)|\G(!\\s*|not\\s+)|\G(\\s*&&\\s*|\\s*and\\s+)|\G(\\s*\\|\\|\\s*|\\s*or\\s+)|\G(\\s*xor\\s+)|\G(\\s+is\\s+odd\\s+by\\s+)|\G(\\s+is\\s+not\\s+odd\\s+by\\s+)|\G(\\s+is\\s+odd)|\G(\\s+is\\s+not\\s+odd)|\G(\\s+is\\s+even\\s+by\\s+)|\G(\\s+is\\s+not\\s+even\\s+by\\s+)|\G(\\s+is\\s+even)|\G(\\s+is\\s+not\\s+even)|\G(\\s+is\\s+div\\s+by\\s+)|\G(\\s+is\\s+not\\s+div\\s+by\\s+)|\G(\\((int(eger)?|bool(ean)?|float|double|real|string|binary|array|object)\\)\\s*)|\G(\\s*\\(\\s*)|\G(\\s*\\))|\G(\\[\\s*)|\G(\\s*\\])|\G(\\s*->\\s*)|\G(\\s*=>\\s*)|\G(\\s*=\\s*)|\G(\\+\\+|--)|\G(\\s*(\\+|-)\\s*)|\G(\\s*(\\*|\/|%)\\s*)|\G(@)|\G(\\s+[0-9]*[a-zA-Z_][a-zA-Z0-9_\-:]*\\s*=\\s*)|\G([0-9]*[a-zA-Z_]\\w*)|\G(\\d+)|\G(\\|)|\G(\\.)|\G(\\s*,\\s*)|\G(\\s*;)|\G(\\s*:\\s*)|\G(\\s*&\\s*)|\G(\\s*\\?\\s*)|\G(\\s+)|\G(".$this->ldel."\\s*(if|elseif|else if|while)\\s+)|\G(".$this->ldel."\\s*for\\s+)|\G(".$this->ldel."\\s*foreach(?![^\s]))|\G(".$this->ldel."\\s*\/)|\G(".$this->ldel."\\s*)|\G([\S\s])/iS";

        do {
            if (preg_match($yy_global_pattern,$this->data, $yymatches, null, $this->counter)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched' .
                        ' an empty string.  Input "' . substr($this->data,
                        $this->counter, 5) . '... state SMARTY');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice($yysubmatches, $this->token + 1,
                        $tokenMap[$this->token]);
                } else {
                    $yysubmatches = array();
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r2_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    // accept this token
                    return true;
                } elseif ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                } elseif ($r === false) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    if ($this->counter >= ($this->mbstring_overload ? mb_strlen($this->data,'latin1'): strlen($this->data))) {
                        return false; // end of input
                    }
                    // skip this token
                    continue;
                }            } else {
                throw new Exception('Unexpected input at line' . $this->line .
                    ': ' . $this->data[$this->counter]);
            }
            break;
        } while (true);

    } // end function


    const SMARTY = 2;
    function yy_r2_1($yy_subpatterns)
    {

   $this->token = Parser::TP_QUOTE;
   $this->yypushstate(self::DOUBLEQUOTEDSTRING);
     }
    function yy_r2_2($yy_subpatterns)
    {

   $this->token = Parser::TP_SINGLEQUOTESTRING;
     }
    function yy_r2_3($yy_subpatterns)
    {

      $this->token = Parser::TP_SMARTYBLOCKCHILDPARENT;
      $this->taglineno = $this->line;
     }
    function yy_r2_5($yy_subpatterns)
    {

   $this->token = Parser::TP_DOLLAR;
     }
    function yy_r2_6($yy_subpatterns)
    {

   $this->token = Parser::TP_RDEL;
   $this->yypopstate();
     }
    function yy_r2_7($yy_subpatterns)
    {

   $this->token = Parser::TP_ISIN;
     }
    function yy_r2_8($yy_subpatterns)
    {

   $this->token = Parser::TP_AS;
     }
    function yy_r2_9($yy_subpatterns)
    {

   $this->token = Parser::TP_TO;
     }
    function yy_r2_10($yy_subpatterns)
    {

   $this->token = Parser::TP_STEP;
     }
    function yy_r2_11($yy_subpatterns)
    {

   $this->token = Parser::TP_IDENTITY;
     }
    function yy_r2_12($yy_subpatterns)
    {

   $this->token = Parser::TP_NONEIDENTITY;
     }
    function yy_r2_13($yy_subpatterns)
    {

   $this->token = Parser::TP_EQUALS;
     }
    function yy_r2_14($yy_subpatterns)
    {

   $this->token = Parser::TP_NOTEQUALS;
     }
    function yy_r2_16($yy_subpatterns)
    {

   $this->token = Parser::TP_GREATEREQUAL;
     }
    function yy_r2_18($yy_subpatterns)
    {

   $this->token = Parser::TP_LESSEQUAL;
     }
    function yy_r2_20($yy_subpatterns)
    {

   $this->token = Parser::TP_GREATERTHAN;
     }
    function yy_r2_21($yy_subpatterns)
    {

   $this->token = Parser::TP_LESSTHAN;
     }
    function yy_r2_22($yy_subpatterns)
    {

   $this->token = Parser::TP_MOD;
     }
    function yy_r2_23($yy_subpatterns)
    {

   $this->token = Parser::TP_NOT;
     }
    function yy_r2_24($yy_subpatterns)
    {

   $this->token = Parser::TP_LAND;
     }
    function yy_r2_25($yy_subpatterns)
    {

   $this->token = Parser::TP_LOR;
     }
    function yy_r2_26($yy_subpatterns)
    {

   $this->token = Parser::TP_LXOR;
     }
    function yy_r2_27($yy_subpatterns)
    {

   $this->token = Parser::TP_ISODDBY;
     }
    function yy_r2_28($yy_subpatterns)
    {

   $this->token = Parser::TP_ISNOTODDBY;
     }
    function yy_r2_29($yy_subpatterns)
    {

   $this->token = Parser::TP_ISODD;
     }
    function yy_r2_30($yy_subpatterns)
    {

   $this->token = Parser::TP_ISNOTODD;
     }
    function yy_r2_31($yy_subpatterns)
    {

   $this->token = Parser::TP_ISEVENBY;
     }
    function yy_r2_32($yy_subpatterns)
    {

   $this->token = Parser::TP_ISNOTEVENBY;
     }
    function yy_r2_33($yy_subpatterns)
    {

   $this->token = Parser::TP_ISEVEN;
     }
    function yy_r2_34($yy_subpatterns)
    {

   $this->token = Parser::TP_ISNOTEVEN;
     }
    function yy_r2_35($yy_subpatterns)
    {

   $this->token = Parser::TP_ISDIVBY;
     }
    function yy_r2_36($yy_subpatterns)
    {

   $this->token = Parser::TP_ISNOTDIVBY;
     }
    function yy_r2_37($yy_subpatterns)
    {

   $this->token = Parser::TP_TYPECAST;
     }
    function yy_r2_41($yy_subpatterns)
    {

   $this->token = Parser::TP_OPENP;
     }
    function yy_r2_42($yy_subpatterns)
    {

   $this->token = Parser::TP_CLOSEP;
     }
    function yy_r2_43($yy_subpatterns)
    {

   $this->token = Parser::TP_OPENB;
     }
    function yy_r2_44($yy_subpatterns)
    {

   $this->token = Parser::TP_CLOSEB;
     }
    function yy_r2_45($yy_subpatterns)
    {

   $this->token = Parser::TP_PTR;
     }
    function yy_r2_46($yy_subpatterns)
    {

   $this->token = Parser::TP_APTR;
     }
    function yy_r2_47($yy_subpatterns)
    {

   $this->token = Parser::TP_EQUAL;
     }
    function yy_r2_48($yy_subpatterns)
    {

   $this->token = Parser::TP_INCDEC;
     }
    function yy_r2_49($yy_subpatterns)
    {

   $this->token = Parser::TP_UNIMATH;
     }
    function yy_r2_51($yy_subpatterns)
    {

   $this->token = Parser::TP_MATH;
     }
    function yy_r2_53($yy_subpatterns)
    {

   $this->token = Parser::TP_AT;
     }
    function yy_r2_54($yy_subpatterns)
    {

   // resolve conflicts with shorttag and right_delimiter starting with '='
   if (substr($this->data, $this->counter + strlen($this->value) - 1, $this->rdel_length) == $this->smarty->right_delimiter) {
      preg_match("/\s+/",$this->value,$match);
      $this->value = $match[0];
      $this->token = Parser::TP_SPACE;
   } else {
      $this->token = Parser::TP_ATTR;
   }
     }
    function yy_r2_55($yy_subpatterns)
    {

   $this->token = Parser::TP_ID;
     }
    function yy_r2_56($yy_subpatterns)
    {

   $this->token = Parser::TP_INTEGER;
     }
    function yy_r2_57($yy_subpatterns)
    {

   $this->token = Parser::TP_VERT;
     }
    function yy_r2_58($yy_subpatterns)
    {

   $this->token = Parser::TP_DOT;
     }
    function yy_r2_59($yy_subpatterns)
    {

   $this->token = Parser::TP_COMMA;
     }
    function yy_r2_60($yy_subpatterns)
    {

   $this->token = Parser::TP_SEMICOLON;
     }
    function yy_r2_61($yy_subpatterns)
    {

   $this->token = Parser::TP_COLON;
     }
    function yy_r2_62($yy_subpatterns)
    {

   $this->token = Parser::TP_ANDSYM;
     }
    function yy_r2_63($yy_subpatterns)
    {

   $this->token = Parser::TP_QMARK;
     }
    function yy_r2_64($yy_subpatterns)
    {

   $this->token = Parser::TP_SPACE;
     }
    function yy_r2_65($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELIF;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r2_67($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELFOR;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r2_68($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELFOREACH;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r2_69($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
     $this->token = Parser::TP_TEXT;
   } else {
     $this->token = Parser::TP_LDELSLASH;
     $this->yypushstate(self::SMARTY);
     $this->taglineno = $this->line;
   }
     }
    function yy_r2_70($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
     $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDEL;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r2_71($yy_subpatterns)
    {

   $this->token = Parser::TP_TEXT;
     }


 
    public function yylex3()
    {
        $tokenMap = array (
              1 => 0,
              2 => 0,
              3 => 0,
            );
        if ($this->counter >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/\G(".$this->ldel."\\s*literal\\s*".$this->rdel.")|\G(".$this->ldel."\\s*\/literal\\s*".$this->rdel.")|\G([\S\s])/iS";

        do {
            if (preg_match($yy_global_pattern,$this->data, $yymatches, null, $this->counter)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched' .
                        ' an empty string.  Input "' . substr($this->data,
                        $this->counter, 5) . '... state LITERAL');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice($yysubmatches, $this->token + 1,
                        $tokenMap[$this->token]);
                } else {
                    $yysubmatches = array();
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r3_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    // accept this token
                    return true;
                } elseif ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                } elseif ($r === false) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    if ($this->counter >= ($this->mbstring_overload ? mb_strlen($this->data,'latin1'): strlen($this->data))) {
                        return false; // end of input
                    }
                    // skip this token
                    continue;
                }            } else {
                throw new Exception('Unexpected input at line' . $this->line .
                    ': ' . $this->data[$this->counter]);
            }
            break;
        } while (true);

    } // end function


    const LITERAL = 3;
    function yy_r3_1($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
     $this->token = Parser::TP_LITERALSTART;
     $this->yypushstate(self::LITERAL);
   }
     }
    function yy_r3_2($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
     $this->token = Parser::TP_LITERALEND;
     $this->yypopstate();
   }
     }
    function yy_r3_3($yy_subpatterns)
    {

   if ($this->mbstring_overload) {
     $to = mb_strlen($this->data,'latin1');
   } else {
     $to = strlen($this->data);
   }
   preg_match("/{$this->ldel}\/?literal{$this->rdel}/",$this->data,$match,PREG_OFFSET_CAPTURE,$this->counter);
   if (isset($match[0][1])) {
     $to = $match[0][1];
   } else {
     $this->compiler->trigger_template_error ("missing or misspelled literal closing tag");
   }
   if ($this->mbstring_overload) {
     $this->value = mb_substr($this->data,$this->counter,$to-$this->counter,'latin1');
   } else {
     $this->value = substr($this->data,$this->counter,$to-$this->counter);
   }
   $this->token = Parser::TP_LITERAL;
     }

 
    public function yylex4()
    {
        $tokenMap = array (
              1 => 1,
              3 => 0,
              4 => 0,
              5 => 0,
              6 => 0,
              7 => 0,
              8 => 0,
              9 => 0,
              10 => 3,
              14 => 0,
            );
        if ($this->counter >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/\G(".$this->ldel."\\s*(if|elseif|else if|while)\\s+)|\G(".$this->ldel."\\s*for\\s+)|\G(".$this->ldel."\\s*foreach(?![^\s]))|\G(".$this->ldel."\\s*\/)|\G(".$this->ldel."\\s*)|\G(\")|\G(\\$[0-9]*[a-zA-Z_]\\w*)|\G(\\$)|\G(([^\"\\\\]*?)((?:\\\\.[^\"\\\\]*?)*?)(?=(".$this->ldel."|\\$|\")))|\G([\S\s])/iS";

        do {
            if (preg_match($yy_global_pattern,$this->data, $yymatches, null, $this->counter)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched' .
                        ' an empty string.  Input "' . substr($this->data,
                        $this->counter, 5) . '... state DOUBLEQUOTEDSTRING');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice($yysubmatches, $this->token + 1,
                        $tokenMap[$this->token]);
                } else {
                    $yysubmatches = array();
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r4_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    // accept this token
                    return true;
                } elseif ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                } elseif ($r === false) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    if ($this->counter >= ($this->mbstring_overload ? mb_strlen($this->data,'latin1'): strlen($this->data))) {
                        return false; // end of input
                    }
                    // skip this token
                    continue;
                }            } else {
                throw new Exception('Unexpected input at line' . $this->line .
                    ': ' . $this->data[$this->counter]);
            }
            break;
        } while (true);

    } // end function


    const DOUBLEQUOTEDSTRING = 4;
    function yy_r4_1($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELIF;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r4_3($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELFOR;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r4_4($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDELFOREACH;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r4_5($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
     $this->token = Parser::TP_TEXT;
   } else {
     $this->token = Parser::TP_LDELSLASH;
     $this->yypushstate(self::SMARTY);
     $this->taglineno = $this->line;
   }
     }
    function yy_r4_6($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
     $this->token = Parser::TP_TEXT;
   } else {
      $this->token = Parser::TP_LDEL;
      $this->yypushstate(self::SMARTY);
      $this->taglineno = $this->line;
   }
     }
    function yy_r4_7($yy_subpatterns)
    {

   $this->token = Parser::TP_QUOTE;
   $this->yypopstate();
     }
    function yy_r4_8($yy_subpatterns)
    {

   $this->token = Parser::TP_DOLLARID;
     }
    function yy_r4_9($yy_subpatterns)
    {

   $this->token = Parser::TP_TEXT;
     }
    function yy_r4_10($yy_subpatterns)
    {

   $this->token = Parser::TP_TEXT;
     }
    function yy_r4_14($yy_subpatterns)
    {

   if ($this->mbstring_overload) {
     $to = mb_strlen($this->data,'latin1');
   } else {
     $to = strlen($this->data);
   }
   if ($this->mbstring_overload) {
     $this->value = mb_substr($this->data,$this->counter,$to-$this->counter,'latin1');
   } else {
     $this->value = substr($this->data,$this->counter,$to-$this->counter);
   }
   $this->token = Parser::TP_TEXT;
     }

 
    public function yylex5()
    {
        $tokenMap = array (
              1 => 0,
              2 => 0,
              3 => 0,
              4 => 0,
            );
        if ($this->counter >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/\G(".$this->ldel."\\s*strip\\s*".$this->rdel.")|\G(".$this->ldel."\\s*\/strip\\s*".$this->rdel.")|\G(".$this->ldel."\\s*block)|\G([\S\s])/iS";

        do {
            if (preg_match($yy_global_pattern,$this->data, $yymatches, null, $this->counter)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched' .
                        ' an empty string.  Input "' . substr($this->data,
                        $this->counter, 5) . '... state CHILDBODY');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice($yysubmatches, $this->token + 1,
                        $tokenMap[$this->token]);
                } else {
                    $yysubmatches = array();
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r5_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    // accept this token
                    return true;
                } elseif ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                } elseif ($r === false) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    if ($this->counter >= ($this->mbstring_overload ? mb_strlen($this->data,'latin1'): strlen($this->data))) {
                        return false; // end of input
                    }
                    // skip this token
                    continue;
                }            } else {
                throw new Exception('Unexpected input at line' . $this->line .
                    ': ' . $this->data[$this->counter]);
            }
            break;
        } while (true);

    } // end function


    const CHILDBODY = 5;
    function yy_r5_1($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      return false;
   } else {
     $this->token = Parser::TP_STRIPON;
   }
     }
    function yy_r5_2($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      return false;
   } else {
     $this->token = Parser::TP_STRIPOFF;
   }
     }
    function yy_r5_3($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      return false;
   } else {
     $this->yypopstate();
     return true;
   }
     }
    function yy_r5_4($yy_subpatterns)
    {

   if ($this->mbstring_overload) {
     $to = mb_strlen($this->data,'latin1');
   } else {
     $to = strlen($this->data);
   }
   preg_match("/".$this->ldel."\s*((\/)?strip\s*".$this->rdel."|block\s+)/",$this->data,$match,PREG_OFFSET_CAPTURE,$this->counter);
   if (isset($match[0][1])) {
     $to = $match[0][1];
   }
   if ($this->mbstring_overload) {
     $this->value = mb_substr($this->data,$this->counter,$to-$this->counter,'latin1');
   } else {
     $this->value = substr($this->data,$this->counter,$to-$this->counter);
   }
   return false;
     }

 
    public function yylex6()
    {
        $tokenMap = array (
              1 => 0,
              2 => 0,
              3 => 0,
              4 => 1,
              6 => 0,
            );
        if ($this->counter >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/\G(".$this->ldel."\\s*literal\\s*".$this->rdel.")|\G(".$this->ldel."\\s*block)|\G(".$this->ldel."\\s*\/block)|\G(".$this->ldel."\\s*[$]smarty\\.block\\.(child|parent))|\G([\S\s])/iS";

        do {
            if (preg_match($yy_global_pattern,$this->data, $yymatches, null, $this->counter)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched' .
                        ' an empty string.  Input "' . substr($this->data,
                        $this->counter, 5) . '... state CHILDBLOCK');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice($yysubmatches, $this->token + 1,
                        $tokenMap[$this->token]);
                } else {
                    $yysubmatches = array();
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r6_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    // accept this token
                    return true;
                } elseif ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                } elseif ($r === false) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    if ($this->counter >= ($this->mbstring_overload ? mb_strlen($this->data,'latin1'): strlen($this->data))) {
                        return false; // end of input
                    }
                    // skip this token
                    continue;
                }            } else {
                throw new Exception('Unexpected input at line' . $this->line .
                    ': ' . $this->data[$this->counter]);
            }
            break;
        } while (true);

    } // end function


    const CHILDBLOCK = 6;
    function yy_r6_1($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_BLOCKSOURCE;
   } else {
     $this->token = Parser::TP_BLOCKSOURCE;
     $this->yypushstate(self::CHILDLITERAL);
    }
     }
    function yy_r6_2($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_BLOCKSOURCE;
   } else {
     $this->yypopstate();
     return true;
   }
     }
    function yy_r6_3($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_BLOCKSOURCE;
   } else {
     $this->yypopstate();
     return true;
   }
     }
    function yy_r6_4($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_BLOCKSOURCE;
   } else {
     $this->yypopstate();
     return true;
   }
     }
    function yy_r6_6($yy_subpatterns)
    {

   if ($this->mbstring_overload) {
     $to = mb_strlen($this->data,'latin1');
   } else {
     $to = strlen($this->data);
   }
   preg_match("/".$this->ldel."\s*(literal\s*".$this->rdel."|(\/)?block(\s|".$this->rdel.")|[\$]smarty\.block\.(child|parent))/",$this->data,$match,PREG_OFFSET_CAPTURE,$this->counter);
   if (isset($match[0][1])) {
     $to = $match[0][1];
   }
   if ($this->mbstring_overload) {
     $this->value = mb_substr($this->data,$this->counter,$to-$this->counter,'latin1');
   } else {
     $this->value = substr($this->data,$this->counter,$to-$this->counter);
   }
   $this->token = Parser::TP_BLOCKSOURCE;
     }

 
    public function yylex7()
    {
        $tokenMap = array (
              1 => 0,
              2 => 0,
              3 => 0,
            );
        if ($this->counter >= strlen($this->data)) {
            return false; // end of input
        }
        $yy_global_pattern = "/\G(".$this->ldel."\\s*literal\\s*".$this->rdel.")|\G(".$this->ldel."\\s*\/literal\\s*".$this->rdel.")|\G([\S\s])/iS";

        do {
            if (preg_match($yy_global_pattern,$this->data, $yymatches, null, $this->counter)) {
                $yysubmatches = $yymatches;
                $yymatches = array_filter($yymatches, 'strlen'); // remove empty sub-patterns
                if (!count($yymatches)) {
                    throw new Exception('Error: lexing failed because a rule matched' .
                        ' an empty string.  Input "' . substr($this->data,
                        $this->counter, 5) . '... state CHILDLITERAL');
                }
                next($yymatches); // skip global match
                $this->token = key($yymatches); // token number
                if ($tokenMap[$this->token]) {
                    // extract sub-patterns for passing to lex function
                    $yysubmatches = array_slice($yysubmatches, $this->token + 1,
                        $tokenMap[$this->token]);
                } else {
                    $yysubmatches = array();
                }
                $this->value = current($yymatches); // token value
                $r = $this->{'yy_r7_' . $this->token}($yysubmatches);
                if ($r === null) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    // accept this token
                    return true;
                } elseif ($r === true) {
                    // we have changed state
                    // process this token in the new state
                    return $this->yylex();
                } elseif ($r === false) {
                    $this->counter += strlen($this->value);
                    $this->line += substr_count($this->value, "\n");
                    if ($this->counter >= ($this->mbstring_overload ? mb_strlen($this->data,'latin1'): strlen($this->data))) {
                        return false; // end of input
                    }
                    // skip this token
                    continue;
                }            } else {
                throw new Exception('Unexpected input at line' . $this->line .
                    ': ' . $this->data[$this->counter]);
            }
            break;
        } while (true);

    } // end function


    const CHILDLITERAL = 7;
    function yy_r7_1($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_BLOCKSOURCE;
   } else {
     $this->token = Parser::TP_BLOCKSOURCE;
     $this->yypushstate(self::CHILDLITERAL);
   }
     }
    function yy_r7_2($yy_subpatterns)
    {

   if ($this->smarty->auto_literal && isset($this->value[$this->ldel_length]) ? strpos(" \n\t\r", $this->value[$this->ldel_length]) !== false : false) {
      $this->token = Parser::TP_BLOCKSOURCE;
   } else {
     $this->token = Parser::TP_BLOCKSOURCE;
     $this->yypopstate();
   }
     }
    function yy_r7_3($yy_subpatterns)
    {

   if ($this->mbstring_overload) {
     $to = mb_strlen($this->data,'latin1');
   } else {
     $to = strlen($this->data);
   }
   preg_match("/{$this->ldel}\/?literal\s*{$this->rdel}/",$this->data,$match,PREG_OFFSET_CAPTURE,$this->counter);
   if (isset($match[0][1])) {
     $to = $match[0][1];
   } else {
     $this->compiler->trigger_template_error ("Missing or misspelled literal closing tag");
   }
   if ($this->mbstring_overload) {
     $this->value = mb_substr($this->data,$this->counter,$to-$this->counter,'latin1');
   } else {
     $this->value = substr($this->data,$this->counter,$to-$this->counter);
   }
   $this->token = Parser::TP_BLOCKSOURCE;
     }

 }