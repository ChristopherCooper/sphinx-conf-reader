<?php namespace ChrisCooper\SphinxConfReader;

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

  private $regexs;
  private $lexer;
  private $factory;

  public function __construct()
  {
    $this->regexs = [
      '#[^\r\n]*' => self::T_COMMENT,
      '=' => self::T_EQUAL,
      ':' => self::T_SEPERATOR,
      '\h+' => self::T_HORI_WHITESPACE,
      '{' => self::T_CURLY_BRACER_OPEN,
      '}' => self::T_CURLY_BRACER_CLOSE,
      '[^\h{}:=\r\n]+' => self::T_TEXTBLOCK,
      '\r?\n' => self::T_LINEBREAK
    ];

    $this->factory = new UsingPregReplace(
      new LexerDataGenerator
    );

    $this->lexer = $this->factory->createLexer($this->regexs);
  }

  public function parse($filename)
  {
    if (!file_exists($filename)) {
      throw new \Exception('File not found "'.$filename.'"');
    }
    $tokens = $this->lex(file_get_contents($filename));
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
    $parsed = [];
    $config_reset = $config = [
      'type' => null,
      'argument' => null,
      'values' => []
    ];
    foreach ($tokens as $token) {
      $line_context = " (Line #".$token[1].")";
      switch ($token[0]) {
        // Text blocks are dependant on which node they are in
        case self::T_TEXTBLOCK:
          switch ($current_node) {
            case self::T_ROOT:
              $config['type'] = $token[2];
              $current_node = self::T_TYPE;
              break;
            case self::T_TYPE:
              $config_type_name = $token[2];
              break;
            case self::T_SEPERATOR:
              $config['argument'] = $token[2];
              $current_node = self::T_TYPE;
              break;
            case self::T_CURLY_BRACER_OPEN:
              $config_key = $token[2];
              break;
            case self::T_EQUAL:
              $config['values'][$config_key] = $token[2];
              $current_node = self::T_CURLY_BRACER_OPEN;
              break;
            default:
              throw new LexerSyntaxErrorException(
                'Syntax error, expected one of T_ROOT, T_TYPE, T_SEPERATOR, T_CURLY_BRACER_OPEN, T_EQUAL given '.
                $this->get_constant_name($current_node).$line_context
              );
              break;
          }
          break;

        // For these just set the current node to this
        case self::T_SEPERATOR:
          if ($current_node !== self::T_TYPE) {
            throw new LexerSyntaxErrorException(
              'Syntax error, expected T_TYPE, given '.$this->get_constant_name($current_node).$line_context
            );
          }
          $current_node = $token[0];
          break;
        case self::T_EQUAL:
          if ($current_node !== self::T_CURLY_BRACER_OPEN) {
            throw new LexerSyntaxErrorException(
              'Syntax error, expected T_CURLY_BRACER_OPEN, given '.$this->get_constant_name($current_node).$line_context
            );
          }
          $current_node = $token[0];
          break;
        case self::T_CURLY_BRACER_OPEN:
          if ($current_node !== self::T_TYPE) {
            throw new LexerSyntaxErrorException(
              'Syntax error, expected T_TYPE, given '.$this->get_constant_name($current_node).$line_context
            );
          }
          $current_node = $token[0];
          break;

        // Curly bracer closure implies we have a config set, save it to the list
        case self::T_CURLY_BRACER_CLOSE:
          if ($current_node !== self::T_CURLY_BRACER_OPEN) {
            throw new LexerSyntaxErrorException(
              'Syntax error, expected T_CURLY_BRACER_OPEN, given '.$this->get_constant_name($current_node).$line_context
            );
          }
          $current_node = self::T_ROOT;
          $parsed[$config_type_name] = $config;
          $config = $config_reset;
          break;
      }
    }

    if ($current_node !== self::T_ROOT) {
      throw new LexerSyntaxErrorException(
        'Syntax error, expected T_ROOT, given '.$this->get_constant_name($current_node).$line_context
      );
    }

    return $parsed;
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
