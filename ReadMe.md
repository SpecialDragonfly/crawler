# Naive Web Crawler

## Description
A very naive web crawler embodying "Think Big, Start Small".

A single threaded web crawler written in PHP 7.2, saving to an sqlite db (data.sqlite) and logging
to a flatfile (log.txt).

## To run

```
docker build -t crawler .

docker run --name=crawler crawler:latest

docker exec -it crawler php ./bin/run.php crawl <domain>
```

## Output
Output is a JSON string of all the links.

Expect output in the form:
```
[
    {
        link: <string>, 
        parent: <string>
    }
]
```

## Improvements

* Sort out docker exec command to be less ugly
* Switch to a MySQL DB
* Send links to a queue, have workers read from the queue to do the work. 
* Workers add further tasks to the queue
* When a worker adds links to the queue, make sure that those links don't already exist on the queue [1]
* Use Kibana/Graphana for logging
 
[1] This can be done by storing which links have been finished in a mysql table.
## Stuff
 