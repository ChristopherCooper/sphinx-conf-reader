<?php namespace ChrisCooper\SphinxConfReader\Nodes;

class Source extends Node
{
  /** @var string */
  public $name;
  /** @var string */
  public $query;

  public function __construct($name, $query)
  {
    $this->name = $name;
    $this->query = $query;
  }
}