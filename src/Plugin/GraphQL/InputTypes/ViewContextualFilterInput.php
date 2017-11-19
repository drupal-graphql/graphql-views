<?php

namespace Drupal\graphql_views\Plugin\GraphQL\InputTypes;

use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;

/**
 * Input types for view contextual filters.
 *
 * @GraphQLInputType(
 *   id = "view_contextual_filter_input",
 *   provider = "views",
 *   deriver = "Drupal\graphql_views\Plugin\Deriver\InputTypes\ViewContextualFilterInputDeriver"
 * )
 */
class ViewContextualFilterInput extends InputTypePluginBase {

}
