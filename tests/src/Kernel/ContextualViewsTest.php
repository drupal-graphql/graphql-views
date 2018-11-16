<?php

namespace Drupal\Tests\graphql_views\Kernel;

use GraphQL\Server\OperationParams;

/**
 * Test contextual views support in GraphQL.
 *
 * @group graphql_views
 */
class ContextualViewsTest extends ViewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'test2']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts() {
    return array_merge([
      'languages:language_content',
      'languages:language_interface',
      'user.permissions',
      'user.node_grants:view',
    ], parent::defaultCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheTags() {
    return array_merge([
      'config:field.storage.node.field_tags',
    ], parent::defaultCacheTags());
  }

  /**
   * Test if view contextual filters are set properly.
   */
  public function testContextualViewArgs() {
    $test2Node = $this->createNode(['type' => 'test2']);

    $this->graphQlProcessor()->processQuery(
      $this->getDefaultSchema(),
      OperationParams::create([
        'query' => $this->getQueryFromFile('contextual.gql'),
        'variables' => ['test2NodeId' => $test2Node->id()],
      ])
    );

    $this->assertEquals(drupal_static('graphql_views_test:view:args'), [
      'graphql_test:contextual_title_arg' => [
        0 => [NULL],
        1 => ['X'],
      ],
      'graphql_test:contextual_node' => [
        0 => [NULL],
        1 => ['X'],
        2 => ['1'],
        3 => ['X'],
        4 => ['1'],
        5 => ['X'],
      ],
      'graphql_test:contextual_nodetest' => [
        0 => [NULL],
        1 => ['X'],
        2 => ['1'],
        3 => ['X'],
      ],
      'graphql_test:contextual_node_and_nodetest' => [
        0 => [NULL, NULL],
        1 => ['X', 'X'],
        2 => [$test2Node->id(), NULL],
        3 => ['X', 'X'],
        4 => ['1', '1'],
        5 => ['X', 'X'],
      ],
    ]);
  }

  /**
   * Test if view fields are attached to correct types.
   */
  public function testContextualViewFields() {
    $schema = $this->introspect();

    $field = 'graphqlTestContextualTitleArgView';
    $this->assertArrayHasKey($field, $schema['types']['Query']['fields']);
    $this->assertArrayNotHasKey($field, $schema['types']['Node']['fields']);
    $this->assertArrayNotHasKey($field, $schema['types']['NodeTest']['fields']);

    $field = 'graphqlTestContextualNodeView';
    $this->assertArrayHasKey($field, $schema['types']['Query']['fields']);
    $this->assertArrayHasKey($field, $schema['types']['Node']['fields']);
    $this->assertArrayHasKey($field, $schema['types']['NodeTest']['fields']);

    $field = 'graphqlTestContextualNodetestView';
    $this->assertArrayHasKey($field, $schema['types']['Query']['fields']);
    $this->assertArrayNotHasKey($field, $schema['types']['Node']['fields']);
    $this->assertArrayHasKey($field, $schema['types']['NodeTest']['fields']);

    $field = 'graphqlTestContextualNodeAndNodetestView';
    $this->assertArrayHasKey($field, $schema['types']['Query']['fields']);
    $this->assertArrayHasKey($field, $schema['types']['Node']['fields']);
    $this->assertArrayHasKey($field, $schema['types']['NodeTest']['fields']);
  }

}
