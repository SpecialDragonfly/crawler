<?php
namespace Crawler\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class Crawler
{
    /**
     * @var Repository
     */
    private $repo;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $domain;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Crawler constructor.
     * @param LoggerInterface $logger
     * @param Client $httpClient
     * @param Repository $repo
     */
    public function __construct(LoggerInterface $logger, Client $httpClient, Repository $repo)
    {
        $this->repo = $repo;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Sets the domain to be crawled.
     * @param string $domain
     */
    public function setDomain(string $domain)
    {
        $this->domain = trim($domain);
        $this->logger->info("Setting domain to: ".$domain);
    }

    /**
     * Crawls a given path
     * @param string      $path   The path to check
     * @param string|null $parent The parent of this path so that links can be created
     * @throws CrawlerException
     */
    public function crawl(string $path, string $parent = null)
    {
        if (empty($this->domain)) {
            throw new CrawlerException("No domain specified");
        }

        $path = $this->validateMatch($this->domain, $path);
        if ($path === "") {
            return;
        }

        // Link the page to the parent
        $this->repo->link($this->domain, $path, $parent);

        // Get the links for the page
        $links = $this->parse($path);
        $this->logger->debug("Links: ".json_encode($links));

        // For each link, see whether we've already been to it
        foreach ($links as $link) {
            $this->logger->debug("Following link: ".$link);
            if ($this->repo->exists($this->domain, $link)) {
                $this->repo->link($this->domain, $link, $path);
                // Don't follow the link if we've already been to it.
                $this->logger->debug("Link ".$link." already exists");
            } else {
                $this->crawl($link, $path);
            }
        }
    }

    public function getLinks() : array
    {
        return $this->repo->getLinks($this->domain);
    }

    private function parse(string $url) : array
    {
        // If it's a relative route, add the domain on to the front.
        if (strpos($url, $this->domain) !== 0) {
            $url = $this->domain.$url;
        }
        $this->logger->debug("Attempting to query url: ".$url);
        $data = $this->httpClient->get($url);
        $matches = [];
        // Capture anything after an href that isn't an anchor tag
        preg_match_all('~href=[\'"](?!#)(.*?)[\'"]~', $data->getBody()->getContents(), $matches);

        $validMatches = [];
        if (count($matches) > 0) {
            foreach ($matches[1] as $match) {
                $validMatches[] = $this->validateMatch($url, $match);
            }
        }

        return array_unique(array_filter($validMatches));
    }

    private function validateMatch(string $parent, string $url) : string
    {
        // If we're dealing with a file, return early because we don't want to follow those files.
        $matches = [];
        // Regex: Find a forward slash, then anything up to the last dot. 2 - 4 letters, numbers of underscores, then a word boundary
        preg_match("~\/.*\.(\w{2,4})\b~", $url, $matches);
        $this->logger->debug("Validating ".$url." Found: ".print_r($matches, true));
        if (count($matches) > 0) {
            // We found a file extension
            $extension = array_pop($matches);
            $this->logger->debug("Extension was: ".$extension);
            if (!($extension == "html" || $extension == "htm")) {
                return "";
            }
        }

        // if the url starts with a / then it's off the domain, return it with domain:
        if (strpos($url, "/") === 0) {
            return $this->domain.$url;
        }

        // if the url starts with http or www then it's fully qualified, check that our domain is found at the start
        if (strpos($url, $this->domain) === 0) {
            return $url;
        }

        // If it doesn't start www or http, or a /, then assume it's a link relative to the page it was on.
        if (!(strpos($url, "www") === 0 || strpos($url, "http") === 0)) {
            $parts = explode("/", $parent);
            // if the last bit is a file of some sort:
            $end = end($parts);
            if (!empty($end)) {
                if (strpos($end, ".") !== false) {
                    array_pop($parts);
                }
            }
            return implode("/", $parts).$url; // Return the url with the link appended.
        }
        $this->logger->debug("Dropping ".$url);

        return "";
    }
}