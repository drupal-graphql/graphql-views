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
  protected function defaultCacheContexts() {
    return array_merge([
      'user.permissions',
      'user.node_grants:view',
    ], parent::defaultCacheContexts());
  }

  /**
   * Test that the view returns both nodes.
   */
  public function testSimpleView() {
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
    $query = <<<GQL
query {
  default:graphqlTestFilteredView (filter: {title: "A"}) {
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
    $query = <<<GQL
query {
  multi:graphqlTestFilteredView (filter: {field_tags: ["1", "2"]}) {
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
    $query = <<<GQL
query {
  complex:graphqlTestFilteredView(filter: {node_type:{test:"test"}}) {
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
