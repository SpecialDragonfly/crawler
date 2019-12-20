<?php

use Crawler\Service\Repository;

class RepositoryTest extends PHPUnit\Framework\TestCase
{
    private $db;
    private $repo;

    public function setUp(): void
    {
        $this->db = new SQLite3(':memory:');
        $this->db->exec("CREATE TABLE IF NOT EXISTS page_links (page TEXT, parent_page TEXT, domain TEXT)");
        $this->repo = new Repository($this->db);
        parent::setUp();
    }

    public function tearDown(): void
    {
        $this->db->exec("DELETE FROM page_links");
        $this->db->close();
        parent::tearDown();
    }

    public function testLink()
    {
        $this->repo->link("mydomain", "child1", "parent1");
        $results = $this->db->query("SELECT * FROM page_links");
        $expected = ['page' => 'child1', 'parent_page' => 'parent1', 'domain' => "mydomain"];
        $this->assertEquals($expected, $results->fetchArray(SQLITE3_ASSOC));
    }

    public function testExistsReturnsTrue()
    {
        $this->repo->link("domain", "child1", "parent1");
        $result = $this->repo->exists("domain", "child1");
        $this->assertTrue($result);
    }

    public function testExistsReturnsFalse()
    {
        $result = $this->repo->exists("domain", "child2");
        $this->assertFalse($result);
    }

    public function testGetLinks()
    {
        $this->repo->link("mydomain", "child1", "parent1");
        $this->repo->link("myotherdomain", "child2", "parent2");
        $data = $this->repo->getLinks("myotherdomain");
        $expected = [
            [
                'page' => 'child2',
                'parent_page' => 'parent2'
            ]
        ];
        $this->assertEquals($expected, $data);
    }
}