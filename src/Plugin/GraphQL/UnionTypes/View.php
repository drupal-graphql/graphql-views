<?php

namespace Drupal\graphql_views\Plugin\GraphQL\UnionTypes;

use Drupal\graphql\Plugin\GraphQL\Unions\UnionTypePluginBase;

/**
 * @GraphQLUnionType(
 *   id = "view",
 *   name = "View",
 *   provider = "views",
 *   description = @Translation("Common view interface containing generic view properties.")
 * )
 */
class View extends UnionTypePluginBase {

}
