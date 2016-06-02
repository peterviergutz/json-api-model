<?php

namespace OskarD\Tests\JsonApiModel;

use Art4\JsonApiClient\Utils\Helper;
use OskarD\Tests\App\Comment;
use OskarD\Tests\App\Article;
use OskarD\Tests\App\Person;
use OskarD\JsonApiModel\Factories\ApiModelFactory;

class ApiModelTest extends \PHPUnit_Framework_TestCase
{
    public function testEmpty()
    {
        $resource = '{
          "links": {
            "self": "http://example.com/articles"
          },
          "data": []
        }';

        $document = Helper::parse($resource);

        $apiModelFactory = new ApiModelFactory($document);
        $apiModelFactory->setMappedTypes([
            'articles' => Article::class,
        ]);

        $result = $apiModelFactory->build();

        $this->assertEmpty($result, "The returned result is not empty");
    }

    public function testSingle()
    {
        $resource = '{
          "links": {
            "self": "http://example.com/articles/1"
          },
          "data": {
            "type": "articles",
            "id": "1",
            "attributes": {
              "title": "JSON API paints my bikeshed!"
            },
            "relationships": {
              "author": {
                "links": {
                  "related": "http://example.com/articles/1/author"
                }
              }
            }
          }
        }';

        $document = Helper::parse($resource);

        $apiModelFactory = new ApiModelFactory($document);
        $apiModelFactory->setMappedTypes([
            'articles' => Article::class,
        ]);

        $result = $apiModelFactory->build();

        /** @var Article $article */
        $article1 = $result[0];

        $this->assertInstanceOf(Article::class, $article1,
            "The object is not an Article");

        $this->assertEquals(1, $article1->id);
        $this->assertEquals("JSON API paints my bikeshed!", $article1->title);
    }

    public function testCollection()
    {
        $resource = '{
          "links": {
            "self": "http://example.com/articles"
          },
          "data": [{
            "type": "articles",
            "id": "1",
            "attributes": {
              "title": "JSON API paints my bikeshed!"
            }
          }, {
            "type": "articles",
            "id": "2",
            "attributes": {
              "title": "Rails is Omakase"
            }
          }]
        }';

        $document = Helper::parse($resource);

        $apiModelFactory = new ApiModelFactory($document);
        $apiModelFactory->setMappedTypes([
            'articles' => Article::class,
        ]);

        $result = $apiModelFactory->build();

        /** @var Article $article */
        $article1 = $result[0];

        $this->assertInstanceOf(Article::class, $article1,
            "The object is not an Article");

        $this->assertEquals(1, $article1->id);
        $this->assertEquals("JSON API paints my bikeshed!", $article1->title);

        /** @var Article $article */
        $article2 = $result[1];

        $this->assertInstanceOf(Article::class, $article2,
            "The object is not an Article");

        $this->assertEquals(2, $article2->id);
        $this->assertEquals("Rails is Omakase", $article2->title);
    }

    public function testCompoundDocument()
    {
        $resource = '
        {
          "data": [{
            "type": "articles",
            "id": "1",
            "attributes": {
              "title": "JSON API paints my bikeshed!"
            },
            "links": {
              "self": "http://example.com/articles/1"
            },
            "relationships": {
              "author": {
                "links": {
                  "self": "http://example.com/articles/1/relationships/author",
                  "related": "http://example.com/articles/1/author"
                },
                "data": { "type": "people", "id": "9" }
              },
              "comments": {
                "links": {
                  "self": "http://example.com/articles/1/relationships/comments",
                  "related": "http://example.com/articles/1/comments"
                },
                "data": [
                  { "type": "comments", "id": "5" },
                  { "type": "comments", "id": "12" }
                ]
              }
            }
          }, {
            "type": "articles",
            "id": "2",
            "attributes": {
              "title": "Rails is Omakase"
            },
            "links": {
              "self": "http://example.com/articles/2"
            },
            "relationships": {
              "author": {
                "links": {
                  "self": "http://example.com/articles/2/relationships/author",
                  "related": "http://example.com/articles/2/author"
                },
                "data": { "type": "people", "id": "10" }
              },
              "comments": {
                "links": {
                  "self": "http://example.com/articles/2/relationships/comments",
                  "related": "http://example.com/articles/2/comments"
                },
                "data": [
                  { "type": "comments", "id": "15" },
                  { "type": "comments", "id": "23" }
                ]
              }
            }
          }],
          "included": [{
            "type": "people",
            "id": "9",
            "attributes": {
              "first-name": "Dan",
              "last-name": "Gebhardt",
              "twitter": "dgeb"
            },
            "links": {
              "self": "http://example.com/people/9"
            }
          }, {
            "type": "people",
            "id": "10",
            "attributes": {
              "first-name": "Glen",
              "last-name": "Fastswitch",
              "twitter": "fastglen"
            },
            "links": {
              "self": "http://example.com/people/10"
            }
          }, {
            "type": "comments",
            "id": "5",
            "attributes": {
              "body": "First!"
            },
            "relationships": {
              "author": {
                "data": { "type": "people", "id": "2" }
              }
            },
            "links": {
              "self": "http://example.com/comments/5"
            }
          }, {
            "type": "comments",
            "id": "12",
            "attributes": {
              "body": "I like XML better"
            },
            "relationships": {
              "author": {
                "data": { "type": "people", "id": "10" }
              }
            },
            "links": {
              "self": "http://example.com/comments/12"
            }
          }, {
            "type": "comments",
            "id": "15",
            "attributes": {
              "body": "I have a comment"
            },
            "relationships": {
              "author": {
                "data": { "type": "people", "id": "9" }
              }
            },
            "links": {
              "self": "http://example.com/comments/15"
            }
          }, {
            "type": "comments",
            "id": "23",
            "attributes": {
              "body": "I forgot to mention..."
            },
            "relationships": {
              "author": {
                "data": { "type": "people", "id": "9" }
              }
            },
            "links": {
              "self": "http://example.com/comments/23"
            }
          }]
        }';

        $document = Helper::parse($resource);

        $apiModelFactory = new ApiModelFactory($document);
        $apiModelFactory->setMappedTypes([
            'articles' => Article::class,
            'people'   => Person::class,
            'comments' => Comment::class,
        ]);

        $result = $apiModelFactory->build();

        /** @var Article $article */
        $article1 = $result[0];

        $this->assertInstanceOf(Article::class, $article1,
            "The object is not an Article");

        $this->assertEquals(1, $article1->id);
        $this->assertEquals("JSON API paints my bikeshed!", $article1->title);

        $this->assertCount(3, $article1->getRelationships(),
            "The object does not contain its relations");

        $relations = $article1->getRelationships();
        $this->assertInstanceOf(Person::class, $relations[0]);
        $this->assertInstanceOf(Comment::class, $relations[1]);
        $this->assertInstanceOf(Comment::class, $relations[2]);

        $this->assertEquals(9, $relations[0]->id, "The author's ID is wrong");

        /** @var Article $article */
        $article2 = $result[1];

        $this->assertInstanceOf(Article::class, $article2,
            "The object is not an Article");

        $this->assertEquals(2, $article2->id);
        $this->assertEquals("Rails is Omakase", $article2->title);

        $this->assertCount(3, $article2->getRelationships(),
            "The object does not contain its relations");

        $relations = $article2->getRelationships();
        $this->assertInstanceOf(Person::class, $relations[0]);
        $this->assertInstanceOf(Comment::class, $relations[1]);
        $this->assertInstanceOf(Comment::class, $relations[2]);

        $this->assertEquals(10, $relations[0]->id, "The author's ID is wrong");
    }
}