<?php namespace ChrisCooper\SphinxConfReader;

use ChrisCooper\SphinxConfReader\Nodes\Index;
use ChrisCooper\SphinxConfReader\Nodes\Node;
use ChrisCooper\SphinxConfReader\Nodes\Source;
use Phlexy\LexerFactory\Stateless\UsingPregReplace;
use Phlexy\LexerDataGenerator;

class Lexer
{
  const T_ROOT = -1;
  const T_COMMENT = 0;
  const T_TYPE = 1;
  const T_EQUAL = 2;
  const T_SEPERATOR = 3;
  const T_HORI_WHITESPACE = 4;
  const T_CURLY_BRACER_OPEN = 5;
  const T_CURLY_BRACER_CLOSE = 6;
  const T_TEXTBLOCK = 7;
  const T_LINEBREAK = 8;
  const T_NODE_OPEN = 10;
  const T_NODE_CLOSE = 11;
  const T_VARIABLE = 12;

  /** @var UsingPregReplace */
  protected $factory;

  /** @var string */
  protected $filename;

  /** @var \Phlexy\Lexer\Stateless\UsingPregReplace */
  public $lexer;

  protected $regexs = [];

  public function __construct()
  {
    $this->regexs = [
      '#[^\r\n]*' => self::T_COMMENT,
      '\h+' => self::T_HORI_WHITESPACE,
      '(source|index)\s+([^\:\s\#]+)(?:\s*\:\s*([^\s\#]+))?\s*\{' => self::T_NODE_OPEN,
      '}' => self::T_NODE_CLOSE,
      '([^\s\#]+)\h*\=\h*([^\s\#]+)' => self::T_VARIABLE,
      '\r?\n' => self::T_LINEBREAK
    ];

    $this->factory = new UsingPregReplace(new LexerDataGenerator);

    $this->lexer = $this->factory->createLexer($this->regexs);
  }

  public function parse($filename)
  {
    if (!file_exists($filename)) {
      throw new \Exception('File not found "'.$filename.'"');
    }
    $tokens = $this->lex(file_get_contents($filename));
    $this->filename = $filename;
    $conf = $this->parseTokens($tokens);

    return $conf;
  }

  public function lex($contents)
  {
    return $this->lexer->lex($contents);
  }

  public function parseTokens($tokens)
  {
    $current_node = self::T_ROOT;
    $config = [];

    /** @var Node|null $node */
    $node = null;

    $variable_key = $variable_value = null;

    foreach ($tokens as $token) {
      $line_context = " (Line ".$this->filename."#".$token[1].")";
      switch ($token[0]) {
        case self::T_NODE_OPEN:
          if (!in_array($current_node, [static::T_ROOT])) {
            throw new SyntaxErrorException(
              'Syntax error, expected T_ROOT'.
              $this->get_constant_name($current_node).$line_context
            );
          }
          switch ($token[3][1]) {
            case 'source':
              $node = new Source($token[3][2], $token[3][3]);
              break;
            case 'index':
              $node = new Index($token[3][2], isset($token[3][3]) ? $token[3][3] : null);
              break;
          }
          $current_node = static::T_NODE_OPEN;
          break;
        case self::T_NODE_CLOSE:
          if (!in_array($current_node, [static::T_NODE_OPEN])) {
            throw new SyntaxErrorException(
              'Syntax error, expected T_NODE_OPEN given '.
              $this->get_constant_name($current_node).$line_context
            );
          }

          $config[] = $node;

          $current_node = static::T_ROOT;
          unset($node);
          break;
        case static::T_VARIABLE:
          if (!in_array($current_node, [static::T_NODE_OPEN])) {
            throw new SyntaxErrorException(
              'Syntax error, expected T_NODE_OPEN given '.
              $this->get_constant_name($current_node).$line_context
            );
          }

          $variable_key = trim($token[3][1]);
          $variable_value = trim($token[3][2]);

          $current_node = static::T_VARIABLE;
          break;

        case static::T_LINEBREAK:
          switch ($current_node) {
            case static::T_VARIABLE:
              if ($variable_key === null || $variable_value === null) {
                throw new SyntaxErrorException(
                  'Syntax error, no variable key or value found '.$line_context
                );
              }
              if ($node === null) {
                throw new SyntaxErrorException(
                  'No node defined '.$line_context
                );
              }

              $node[$variable_key] = $variable_value;

              $variable_key = $variable_value = null;
              $current_node = static::T_NODE_OPEN;
              break;
          }
          break;
      }
    }

    if ($current_node !== self::T_ROOT) {
      throw new SyntaxErrorException(
        'Syntax error, expected T_ROOT, given '.$this->get_constant_name($current_node).$line_context
      );
    }

    return $config;
  }

  protected function get_constant_name($constant_value)
  {
    $this_class = new \ReflectionClass(__CLASS__);
    $constants = $this_class->getConstants();

    $const_name = null;
    foreach ($constants as $name => $value) {
      if ($value == $constant_value) {
        $const_name = $name;
        break;
      }
    }

    return $const_name;
  }
}
