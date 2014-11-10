<?php namespace spec\ChrisCooper\SphinxConfReader;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use \ChrisCooper\SphinxConfReader\LexerSyntaxErrorException;

class LexerSpec extends ObjectBehavior
{
  function it_is_initializable()
  {
    $this->shouldHaveType('ChrisCooper\SphinxConfReader\Lexer');
  }

  function it_tokenizes_input()
  {
    $this
      ->lex(file_get_contents('stubs/sphinx-indexes.conf'))
      ->shouldReturn(unserialize(file_get_contents('stubs/sphinx-indexes.conf-tokens.srz')));
  }

  function it_parses_tokens()
  {
    $this
      ->parseTokens(unserialize(file_get_contents('stubs/sphinx-indexes.conf-tokens.srz')))
      ->shouldReturn(unserialize(file_get_contents('stubs/sphinx-indexes.conf-output.srz')));
  }

  function it_tokenizes_comment_only_file()
  {
    $this
      ->lex(file_get_contents('stubs/sphinx-indexes-empty.conf'))
      ->shouldReturn(unserialize(file_get_contents('stubs/sphinx-indexes-empty.conf-tokens.srz')));
  }

  function it_returns_nothing_for_comment_tokens()
  {
    $this
      ->parseTokens(unserialize(file_get_contents('stubs/sphinx-indexes-empty.conf-tokens.srz')))
      ->shouldReturn(unserialize(file_get_contents('stubs/sphinx-indexes-empty.conf-output.srz')));
  }

  function it_should_throw_file_not_found()
  {
    $this
      ->shouldThrow(new \Exception('File not found "stubs/syntax_errors/none-existent-file.conf"'))
      ->during('parse', ['stubs/syntax_errors/none-existent-file.conf']);
  }

  function it_should_throw_t_root_syntax_error()
  {
    $this
      ->shouldThrow(new LexerSyntaxErrorException('Syntax error, expected T_ROOT, given T_CURLY_BRACER_OPEN (Line #23)'))
      ->during('parse', ['stubs/syntax_errors/sphinx-indexes-t_root-syntax-error.conf']);
  }

  function it_should_throw_t_curly_bracer_open_syntax_error()
  {
    $this
      ->shouldThrow(new LexerSyntaxErrorException('Syntax error, expected T_CURLY_BRACER_OPEN, given T_ROOT (Line #12)'))
      ->during('parse', ['stubs/syntax_errors/sphinx-indexes-t_curly_bracer_open-syntax-error.conf']);
  }

}
