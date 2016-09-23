<?php namespace ChrisCooper\SphinxConfReader;
class SphinxConfig
{
  /** @var string */
  private $file;

  public function __construct($file)
  {
    $this->file = $file;
  }

  public function handle()
  {
    return (new Lexer)->parse($this->file);
  }
}