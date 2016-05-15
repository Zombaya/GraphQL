<?php
/*
* This file is a part of graphql-youshido project.
*
* @author Alexandr Viniychuk <a@viniychuk.com>
* created: 11/28/15 2:02 AM
*/

namespace Youshido\Tests;

use Youshido\GraphQL\Schema;
use Youshido\GraphQL\Type\Object\ObjectType;
use Youshido\GraphQL\Processor;
use Youshido\GraphQL\Type\TypeMap;
use Youshido\GraphQL\Validator\ResolveValidator\ResolveValidator;
use Youshido\Tests\DataProvider\TestObjectType;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param $query
     * @param $expectedResponse
     *
     * @dataProvider predefinedSchemaProvider
     */
    public function testPredefinedQueries($query, $expectedResponse)
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name'        => 'TestSchema',
                'description' => 'Root of TestSchema'
            ])
        ]);
        $schema->addQuery('latest',
            new ObjectType(
                [
                    'name'    => 'latest',
                    'args'    => [
                        'id' => ['type' => TypeMap::TYPE_INT]
                    ],
                    'fields'  => [
                        'id'   => ['type' => TypeMap::TYPE_INT],
                        'name' => ['type' => TypeMap::TYPE_STRING]
                    ],
                    'resolve' => function () {
                        return [
                            'id'   => 1,
                            'name' => 'Alex'
                        ];
                    }
                ]),
            [
                'description'       => 'latest description',
                'deprecationReason' => 'for test',
                'isDeprecated'      => true,
            ]
        );

        $processor = new Processor();

        $processor->setSchema($schema);

        $processor->processRequest($query);
        $responseData = $processor->getResponseData();

        $this->assertEquals($responseData, $expectedResponse);
    }

    public function predefinedSchemaProvider()
    {
        return [
            [
                '{ __type { name } }',
                [
                    'errors' => [['message' => 'Require "name" arguments to query "__type"']]
                ]
            ],
            [
                '{ __type (name: "__Type") { name } }',
                [
                    'data' => [
                        '__type' => ['name' => '__Type']
                    ]
                ]
            ],
            [
                '{
                    __schema {
                        types {
                            name,
                            fields {
                                name
                            }
                        }
                    }
                }',
                [
                    'data' => [
                        '__schema' => [
                            'types' => [
                                ['name' => 'TestSchema', 'fields' => [['name' => 'latest']]],
                                ['name' => 'Int', 'fields' => null],
                                ['name' => 'latest', 'fields' => [['name' => 'id'], ['name' => 'name']]],
                                ['name' => 'String', 'fields' => null],
                                ['name' => '__Schema', 'fields' => [['name' => 'queryType'], ['name' => 'mutationType'], ['name' => 'subscriptionType'], ['name' => 'types'], ['name' => 'directives']]],
                                ['name' => '__Type', 'fields' => [['name' => 'name'], ['name' => 'kind'], ['name' => 'description'], ['name' => 'ofType'], ['name' => 'inputFields'], ['name' => 'enumValues'], ['name' => 'fields'], ['name' => 'interfaces'], ['name' => 'possibleTypes']]],
                                ['name' => '__InputValue', 'fields' => [['name' => 'name'], ['name' => 'description'], ['name' => 'type'], ['name' => 'defaultValue'],]],
                                ['name' => '__EnumValue', 'fields' => [['name' => 'name'], ['name' => 'description'], ['name' => 'deprecationReason'], ['name' => 'isDeprecated'],]],
                                ['name' => 'Boolean', 'fields' => null],
                                ['name' => '__Field', 'fields' => [['name' => 'name'], ['name' => 'description'], ['name' => 'isDeprecated'], ['name' => 'deprecationReason'], ['name' => 'type'], ['name' => 'args']]],
                                ['name' => '__Subscription', 'fields' => [['name' => 'name']]],
                                ['name' => '__Directive', 'fields' => [['name' => 'name'], ['name' => 'description'], ['name' => 'args'], ['name' => 'onOperation'], ['name' => 'onFragment'], ['name' => 'onField']]],
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                  test : __schema {
                    queryType {
                      kind,
                      name,
                      fields {
                        name,
                        isDeprecated,
                        deprecationReason,
                        description,
                        type {
                          name
                        }
                      }
                    }
                  }
                }',
                ['data' => [
                    'test' => [
                        'queryType' => [
                            'name'   => 'TestSchema',
                            'kind'   => 'OBJECT',
                            'fields' => [
                                ['name' => 'latest', 'isDeprecated' => true, 'deprecationReason' => 'for test', 'description' => 'latest description', 'type' => ['name' => 'latest']]
                            ]
                        ]
                    ]
                ]]
            ],
            [
                '{
                  __schema {
                    queryType {
                      kind,
                      name,
                      description,
                      interfaces {
                        name
                      },
                      possibleTypes {
                        name
                      },
                      inputFields {
                        name
                      },
                      ofType{
                        name
                      }
                    }
                  }
                }',
                ['data' => [
                    '__schema' => [
                        'queryType' => [
                            'kind'          => 'OBJECT',
                            'name'          => 'TestSchema',
                            'description'   => 'Root of TestSchema',
                            'interfaces'    => [],
                            'possibleTypes' => null,
                            'inputFields'   => null,
                            'ofType'        => null
                        ]
                    ]
                ]]
            ]
        ];
    }

}
