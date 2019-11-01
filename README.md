# GraphQL Views for Drupal

[![Build Status](https://img.shields.io/travis/drupal-graphql/graphql-views.svg)](https://travis-ci.org/drupal-graphql/graphql-views)
[![Code Coverage](https://img.shields.io/codecov/c/github/drupal-graphql/graphql-views.svg)](https://codecov.io/gh/drupal-graphql/graphql-views)
[![Code Quality](https://img.shields.io/scrutinizer/g/drupal-graphql/graphql-views.svg)](https://scrutinizer-ci.com/g/drupal-graphql/graphql-views/?branch=8.x-1.x)

[Drupal GraphQL]: https://github.com/drupal-graphql/graphql

With `graphql_views` enabled a `GraphQL` views display can be added to any view in the system.

Results can be sorted, filtered based on content fields, and relationships can be added. There is also the option to return either the full entities, just a selection of fields, or even search results taken straight from a search server.

Any `GraphQL` views display will provide a field that will adapt to the views configuration:

- The fields name will be composed of the views and displays machine names or configured manually.
- If the view is configured with pagination, the field will accept pager arguments and return the result list and count field instead of the entity list directly.
- Any exposed filters will be added to the `filters` input type that can be used to pass filter values into the view.
- Any contextual filters will be added to the `contextual_filters` input type.
- If a contextual filters validation criteria match an existing GraphQL type, the field will be added to this type too, and the value will be populated from the current result context.
    
Read more on:
- https://www.amazeelabs.com/en/blog/graphql-drupalers-part-4-fetching-entities
- https://www.amazeelabs.com/en/blog/drupal-graphql-batteries-included

Please also refer to the main [Drupal GraphQL] module for further information.
