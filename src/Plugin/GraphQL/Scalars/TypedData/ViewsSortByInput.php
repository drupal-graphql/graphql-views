<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Scalars\TypedData;

use Drupal\graphql\Plugin\GraphQL\Scalars\ScalarPluginBase;
use GraphQL\Utils\AST;

/**
 * Input types for view contextual filters.
 *
 * @GraphQLScalar(
 *   id = "views_sort_by_input",
 *   name = "ViewsSortByInput",
 *   type = "ViewsSortByInput",
 *   provider = "views"
 * )
 */
class ViewsSortByInput extends ScalarPluginBase {
  // @TODO: Untyped input is there because there is no option to create a InputType union. See discussion: https://github.com/graphql/graphql-js/issues/207 and https://github.com/facebook/graphql/issues/488.

  /**
   * {@inheritdoc}
   */
  public static function parseLiteral($node) {
    return AST::valueFromASTUntyped($node);
  }

}
