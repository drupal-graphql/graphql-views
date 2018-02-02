<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Enums;

use Drupal\graphql\Plugin\GraphQL\Enums\EnumPluginBase;

/**
 * @GraphQLEnum(
 *   id = "view_sort_by",
 *   provider = "views",
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\Enums\ViewSortByDeriver"
 * )
 */
class ViewSortBy extends EnumPluginBase {

}
