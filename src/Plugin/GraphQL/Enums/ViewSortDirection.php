<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Enums;

use Drupal\graphql\Plugin\GraphQL\Enums\EnumPluginBase;

/**
 * @GraphQLEnum(
 *   id = "view_sort_direction",
 *   name = "ViewSortDirection",
 *   provider = "views",
 *   values = {
 *     "ASC" = "asc",
 *     "DESC" = "desc"
 *   }
 * )
 */
class ViewSortDirection extends EnumPluginBase {

}
