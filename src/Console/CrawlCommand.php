<?php
namespace Crawler\Console;

use Crawler\Service\Crawler;
use Crawler\Service\CrawlerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends Command
{
    /**
     * @var Crawler
     */
    private $crawler;

    public function __construct(Crawler $crawler)
    {
        parent::__construct();
        $this->crawler = $crawler;
    }

    protected function configure()
    {
        $this->setName('crawl');
        $this->setDescription("Crawls the specified domain");
        $this->addArgument('domain', InputArgument::REQUIRED);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws CrawlerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->crawler->setDomain($input->getArgument('domain'));
        $this->crawler->crawl('/');
        $output->write(json_encode($this->crawler->getLinks()));
        return 0;
    }
}