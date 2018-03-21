<?php

namespace Drupal\graphql_views\Plugin\GraphQL\Enums;

use Drupal\graphql\Plugin\GraphQL\Enums\EnumPluginBase;

/**
 * @GraphQLEnum(
 *   id = "view_sort_direction",
 *   name = "ViewSortDirection",
 *   provider = "views",
 * )
 */
class ViewSortDirection extends EnumPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function buildEnumValues($definition) {
    return [
      'ASC' => [
        'value' => 'ASC',
        'description' => 'Sort in ascending order.',
      ],
      'DESC' => [
        'value' => 'DESC',
        'description' => 'Sort in descending order.',
      ],
    ];
  }

}
