<?php namespace ChrisCooper\SphinxConfReader\Nodes;

class Index extends Node
{
  /** @var string */
  public $name;
  /** @var string|null */
  public $index;

  public function __construct($name, $index = null)
  {
    $this->name = $name;
    $this->index = $index;
  }
}