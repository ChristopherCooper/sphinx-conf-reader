<?php namespace ChrisCooper\SphinxConfReader\Console;

use ChrisCooper\ApacheConfReader\ApacheConfig;
use ChrisCooper\ApacheConfReader\Lexer;
use ChrisCooper\ApacheConfReader\Nodes\Directory;
use ChrisCooper\ApacheConfReader\Nodes\VirtualHost;
use ChrisCooper\SphinxConfReader\Nodes\Node;
use ChrisCooper\SphinxConfReader\Nodes\Source;
use ChrisCooper\SphinxConfReader\SphinxConfig;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LexCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('lex')
      ->setDescription('Parse an apache conf file')
      ->addArgument(
        'file',
        InputArgument::REQUIRED,
        'Name of the file to lex.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $conf = (new SphinxConfig($input->getArgument('file')))->handle();

    dump($conf);

    $output->writeln("Done");
  }
}