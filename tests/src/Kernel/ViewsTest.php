<?php

namespace Drupal\Tests\graphql_views\Kernel;

/**
 * Test views support in GraphQL.
 *
 * @group graphql_views
 */
class ViewsTest extends ViewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts(): array {
    return array_merge([
      'languages:language_content',
      'languages:language_interface',
      'user.permissions',
      'user.node_grants:view',
    ], parent::defaultCacheContexts());
  }

  /**
   * Test that the view returns both nodes.
   */
  public function testSimpleView() {
    $schema = <<<GQL
      type Query {
        graphqlTestSimpleView(page: Int, pageSize: Int): ViewResult
      }

      type ViewResult {
        results: [Node],
        count: Int
      }

      type Node {
        entityLabel: String!
      }
GQL;

    $this->setUpSchema($schema);

    $this->mockResolver('Query', 'graphqlTestSimpleView',
      $this->builder->produce('views')
        ->map('view_id', $this->builder->fromValue('graphql_test'))
        ->map('display_id', $this->builder->fromValue('simple'))
        ->map('page', $this->builder->fromArgument('page'))
        ->map('page_size', $this->builder->fromArgument('pageSize'))
    );

    $this->mockResolver('Node', 'entityLabel',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );

    $query = $this->getQueryFromFile('simple.gql');
    $this->assertResults($query, [], [
      'graphqlTestSimpleView' => [
        'results' => [
          [
            'entityLabel' => 'Node A',
          ], [
            'entityLabel' => 'Node B',
          ], [
            'entityLabel' => 'Node C',
          ],
        ],
      ],
    ], $this->defaultCacheMetaData()->addCacheTags([
      'config:views.view.graphql_test',
      'node:1',
      'node:2',
      'node:3',
      'node_list',
    ])->addCacheContexts(['user']));
  }

  /**
   * Test paging support.
   */
  public function testPagedView() {
    $schema = <<<GQL
      type Query {
        graphqlTestPagedView(page: Int, pageSize: Int): ViewResult
      }

      type ViewResult {
        results: [Node],
        count: Int
      }

      type Node {
        entityLabel: String!
      }
GQL;

    $this->setUpSchema($schema);

    $this->mockResolver('Query', 'graphqlTestPagedView',
      $this->builder->produce('views')
        ->map('view_id', $this->builder->fromValue('graphql_test'))
        ->map('display_id', $this->builder->fromValue('paged'))
        ->map('page', $this->builder->fromArgument('page'))
        ->map('page_size', $this->builder->fromArgument('pageSize'))
    );

    $this->mockResolver('Node', 'entityLabel',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );

    $query = $this->getQueryFromFile('paged.gql');
    $this->assertResults($query, [], [
      'page_one' => [
        'count' => count($this->letters),
        'results' => [
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
        ],
      ],
      'page_two' => [
        'count' => count($this->letters),
        'results' => [
          ['entityLabel' => 'Node C'],
          ['entityLabel' => 'Node A'],
        ],
      ],
      'page_three' => [
        'count' => count($this->letters),
        'results' => [
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node C'],
        ],
      ],
      'page_four' => [
        'count' => count($this->letters),
        'results' => [
          ['entityLabel' => 'Node C'],
        ],
      ],
    ], $this->defaultCacheMetaData()->addCacheTags([
      'config:views.view.graphql_test',
      'node:1',
      'node:2',
      'node:3',
      'node:4',
      'node:7',
      'node:8',
      'node:9',
      'node_list',
    ])->addCacheContexts(['user']));
  }

  /**
   * Test sorting behavior.
   */
  public function testSortedView() {
    $schema = <<<GQL
      type Query {
        graphqlTestSortedView(page: Int, pageSize: Int, sortBy: SortBy, sortDirection: SortDirection): ViewResult
      }

      enum SortDirection {
        ASC
        DESC
      }

      enum SortBy {
        TITLE
        NID
      }

      type ViewResult {
        results: [Node],
        count: Int
      }

      type Node {
        entityLabel: String!
      }
GQL;

    $this->setUpSchema($schema);

    $this->mockResolver('Query', 'graphqlTestSortedView',
      $this->builder->produce('views')
        ->map('view_id', $this->builder->fromValue('graphql_test'))
        ->map('display_id', $this->builder->fromValue('sorted'))
        ->map('sort_direction', $this->builder->fromArgument('sortDirection'))
        ->map('sort_by', $this->builder->compose(
            $this->builder->fromArgument('sortBy'),
            $this->builder->callback(function ($direction) {
              $map = ['TITLE' => 'title', 'NID' => 'nid'];
              return $map[$direction] ?? NULL;
            })
        ))
    );

    $this->mockResolver('Node', 'entityLabel',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );

    $query = $this->getQueryFromFile('sorted.gql');
    $this->assertResults($query, [], [
      'default' => [
        'results' => [
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node C'],
        ],
      ],
      'asc' => [
        'results' => [
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node A'],
        ],
      ],
      'desc' => [
        'results' => [
          ['entityLabel' => 'Node C'],
          ['entityLabel' => 'Node C'],
          ['entityLabel' => 'Node C'],
        ],
      ],
      'asc_nid' => [
        'results' => [
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node C'],
        ],
      ],
      'desc_nid' => [
        'results' => [
          ['entityLabel' => 'Node C'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node A'],
        ],
      ],
    ], $this->defaultCacheMetaData()->addCacheTags([
      'config:views.view.graphql_test',
      'node:1',
      'node:2',
      'node:3',
      'node:4',
      'node:6',
      'node:7',
      'node:8',
      'node:9',
      'node_list',
    ])->addCacheContexts(['user']));
  }

  /**
   * Test filter behavior.
   */
  public function testFilteredView() {
    $schema = <<<GQL
      type Query {
        graphqlTestFilteredView(filter: Filter): ViewResult
      }

      input Filter {
        TITLE: String
        NID: Int
      }

      type ViewResult {
        results: [Node],
        count: Int
      }

      type Node {
        entityLabel: String!
      }
GQL;
    $this->setUpSchema($schema);

    $this->mockResolver('Query', 'graphqlTestFilteredView',
      $this->builder->produce('views')
        ->map('view_id', $this->builder->fromValue('graphql_test'))
        ->map('display_id', $this->builder->fromValue('filtered'))
        ->map('filter', $this->builder->compose(
          $this->builder->fromArgument('filter'),
          $this->builder->callback(function ($filter) {
            $mapped = [];
            $map = ['TITLE' => 'title', 'NID' => 'nid'];
            foreach ($filter as $key => $value) {
              if (isset($map[$key])) {
                $mapped = [$map[$key] => $value];
              }
            }
            return $mapped;
          })
        ))
    );

    $this->mockResolver('Node', 'entityLabel',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );

    $query = <<<GQL
query {
  default:graphqlTestFilteredView (filter: {TITLE: "A"}) {
    results {
      entityLabel
    }
  }
}
GQL;

    $this->assertResults($query, [], [
      'default' => [
        'results' => [
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node A'],
        ],
      ],
    ], $this->defaultCacheMetaData()->addCacheTags([
      'config:views.view.graphql_test',
      'node:1',
      'node:4',
      'node:7',
      'node_list',
    ])->addCacheContexts(['user']));
  }

  /**
   * Test filter behavior.
   */
  public function testMultiValueFilteredView() {
    $schema = <<<GQL
      type Query {
        graphqlTestFilteredView(filter: Filter): ViewResult
      }

      input Filter {
        field_tags: [Int]
      }

      type ViewResult {
        results: [Node],
        count: Int
      }

      type Node {
        entityLabel: String!
      }
GQL;
    $this->setUpSchema($schema);

    $this->mockResolver('Query', 'graphqlTestFilteredView',
      $this->builder->produce('views')
        ->map('view_id', $this->builder->fromValue('graphql_test'))
        ->map('display_id', $this->builder->fromValue('filtered'))
        ->map('filter', $this->builder->compose(
          $this->builder->fromArgument('filter'),
          $this->builder->callback(function ($filter) {
            $mapped = [];
            $map = ['field_tags' => 'field_tags'];
            foreach ($filter as $key => $value) {
              if (isset($map[$key])) {
                $mapped = [$map[$key] => $value];
              }
            }
            return $mapped;
          })
        ))
    );

    $this->mockResolver('Node', 'entityLabel',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );

    $query = <<<GQL
query {
  multi:graphqlTestFilteredView (filter: {field_tags: [1, 2]}) {
    results {
      entityLabel
    }
  }
}
GQL;
    $this->assertResults($query, [], [
      'multi' => [
        'results' => [
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
        ],
      ],
    ], $this->defaultCacheMetaData()->addCacheTags([
      'config:views.view.graphql_test',
      'node:1',
      'node:2',
      'node:4',
      'node:5',
      'node:7',
      'node:8',
      'node_list',

    ])->addCacheContexts(['user']));
  }

  /**
   * Test complex filters.
   */
  public function testComplexFilteredView() {
    $schema = <<<GQL
      type Query {
        graphqlTestFilteredView(filter: Filter): ViewResult
      }

      input Filter {
        node_type: [String]
      }

      type ViewResult {
        results: [Node],
        count: Int
      }

      type Node {
        entityLabel: String!
      }
GQL;
    $this->setUpSchema($schema);

    $this->mockResolver('Query', 'graphqlTestFilteredView',
      $this->builder->produce('views')
        ->map('view_id', $this->builder->fromValue('graphql_test'))
        ->map('display_id', $this->builder->fromValue('filtered'))
        ->map('filter', $this->builder->compose(
          $this->builder->fromArgument('filter'),
          $this->builder->callback(function ($filter) {
            $mapped = [];
            $map = ['node_type' => 'node_type'];
            foreach ($filter as $key => $value) {
              if (isset($map[$key])) {
                $mapped = [$map[$key] => $value];
              }
            }
            return $mapped;
          })
        ))
    );

    $this->mockResolver('Node', 'entityLabel',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );

    $query = <<<GQL
query {
  complex:graphqlTestFilteredView(filter: {node_type:["test"]}) {
    results {
      entityLabel
    }
  }
}
GQL;
    $this->assertResults($query, [], [
      'complex' => [
        'results' => [
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node C'],
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node C'],
          ['entityLabel' => 'Node A'],
          ['entityLabel' => 'Node B'],
          ['entityLabel' => 'Node C'],
        ],
      ],
    ], $this->defaultCacheMetaData()->addCacheTags([
      'config:views.view.graphql_test',
      'node:1',
      'node:2',
      'node:3',
      'node:4',
      'node:5',
      'node:6',
      'node:7',
      'node:8',
      'node:9',
      'node_list',
    ])->addCacheContexts(['user']));
  }

  /**
   * Test the result type for views with a single-value bundle filter.
   */
  public function testSingleValueBundleFilterView() {
    $schema = <<<GQL
      type Query {
        graphqlBundleTestGraphql1View: ViewResult
      }

      type ViewResult {
        results: [NodeTest],
        count: Int
      }

      type NodeTest {
        entityLabel: String!
      }
GQL;
    $this->setUpSchema($schema);

    $this->mockResolver('Query', 'graphqlBundleTestGraphql1View',
      $this->builder->produce('views')
        ->map('view_id', $this->builder->fromValue('graphql_bundle_test'))
        ->map('display_id', $this->builder->fromValue('graphql_1'))
    );

    $this->mockResolver('Node', 'entityLabel',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );

    $query = $this->getQueryFromFile('single_bundle_filter.gql');
    $this->assertResults($query, [], [
      'withSingleBundleFilter' => [
        'results' => [
          0 => [
            '__typename' => 'NodeTest',
          ],
        ],
      ],
    ], $this->defaultCacheMetaData()->addCacheTags([
      'config:views.view.graphql_bundle_test',
      'node:1',
      'node_list',
    ]));
  }

}
