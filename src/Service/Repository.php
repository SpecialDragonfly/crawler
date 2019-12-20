<?php
namespace Crawler\Service;

use SQLite3;

class Repository
{
    /**
     * @var SQLite3
     */
    private $db;

    public function __construct(SQLite3 $db)
    {
        $this->db = $db;
    }

    public function link(string $domain, string $child, string $parent = null)
    {
        $statement = $this->db->prepare("INSERT INTO page_links (page, parent_page, domain) VALUES (:page, :parent_page, :domain)");
        $statement->bindParam(':page', $child);
        $statement->bindParam(':parent_page', $parent);
        $statement->bindParam(':domain', $domain);
        $statement->execute();
    }

    public function exists(string $domain, string $linkHash) : bool
    {
        $statement = $this->db->prepare("SELECT page FROM page_links WHERE page = :page AND domain = :domain");
        $statement->bindParam(':page', $linkHash);
        $statement->bindParam(':domain', $domain);
        $result = $statement->execute();
        $data = $result->fetchArray(SQLITE3_ASSOC);

        return $data !== false;
    }

    public function getLinks(string $domain) : array
    {
        $sql = <<<SQL
SELECT page, parent_page
FROM page_links
WHERE page_links.domain = :domain
ORDER BY page_links.parent_page
SQL;
        $statement = $this->db->prepare($sql);
        $statement->bindParam('domain', $domain);
        $results = $statement->execute();
        $data = [];
        while (($res = $results->fetchArray(SQLITE3_ASSOC)) !== false) {
            $data[] = $res;
        }

        return $data;
    }
}