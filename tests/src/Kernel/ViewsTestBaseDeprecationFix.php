<?php

namespace Drupal\Tests\graphql_views\Kernel;

use Drupal\Tests\graphql_core\Kernel\GraphQLContentTestBase;

if (version_compare(\Drupal::VERSION, '8.4', '<')) {
  abstract class ViewsTestBaseDeprecationFix extends GraphQLContentTestBase {
    use \Drupal\taxonomy\Tests\TaxonomyTestTrait;
  }
} else {
  abstract class ViewsTestBaseDeprecationFix extends GraphQLContentTestBase {
    use \Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;
  }
}
