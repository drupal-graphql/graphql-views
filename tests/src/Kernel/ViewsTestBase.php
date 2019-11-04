<?php

namespace Drupal\Tests\graphql_views\Kernel;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\graphql_core\Kernel\GraphQLContentTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Base class for test views support in GraphQL.
 *
 * @group graphql_views
 */
abstract class ViewsTestBase extends GraphQLContentTestBase {
  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'filter',
    'text',
    'views',
    'taxonomy',
    'graphql_core',
    'graphql_views',
    'graphql_views_test',
  ];

  /**
   * A List of letters.
   *
   * @var string[]
   */
  protected $letters = ['A', 'B', 'C', 'A', 'B', 'C', 'A', 'B', 'C'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('view');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['node', 'filter', 'views', 'graphql_views_test']);
    $this->createEntityReferenceField('node', 'test', 'field_tags', 'Tags', 'taxonomy_term');

    Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ])->save();

    $terms = [];

    $terms['A'] = Term::create([
      'name' => 'Term A',
      'vid' => 'tags',
    ]);
    $terms['A']->save();

    $terms['B'] = Term::create([
      'name' => 'Term B',
      'vid' => 'tags',
    ]);
    $terms['B']->save();

    $terms['C'] = Term::create([
      'name' => 'Term C',
      'vid' => 'tags',
    ]);
    $terms['C']->save();

    foreach ($this->letters as $index => $letter) {
      $this->createNode([
        'title' => 'Node ' . $letter,
        'type' => 'test',
        'field_tags' => $terms[$letter],
      ])->save();
    }
  }

}
