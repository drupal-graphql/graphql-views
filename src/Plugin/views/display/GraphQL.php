<?php

/**
 * @file
 * Contains \Drupal\graphql\Plugin\views\display
 */

namespace Drupal\graphql_views\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\graphql\Utility\StringHelper;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a display plugin for GraphQL views.
 *
 * @ViewsDisplay(
 *   id = "graphql",
 *   title = @Translation("GraphQL"),
 *   help = @Translation("Creates a GraphQL entity list display."),
 *   admin = @Translation("GraphQL"),
 *   graphql_display = TRUE,
 *   returns_response = TRUE
 * )
 */
class GraphQL extends DisplayPluginBase {
  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAJAX.
   */
  protected $usesAJAX = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesPager.
   */
  protected $usesPager = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesMore.
   */
  protected $usesMore = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAreas.
   */
  protected $usesAreas = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'graphql';
  }

  /**
   * {@inheritdoc}
   */
  public function usesFields() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displaysExposed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Allow to attach the view to entity types / bundles.
    // Similar to the EVA module.
    $options['entity_type']['default'] = '';
    $options['bundles']['default'] = [];

    // Set the default plugins to 'graphql'.
    $options['style']['contains']['type']['default'] = 'graphql';
    $options['exposed_form']['contains']['type']['default'] = 'graphql';
    $options['row']['contains']['type']['default'] = 'graphql_entity';

    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['exposed_form'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    // Remove css/exposed form settings, as they are not used for the data display.
    unset($options['exposed_block']);
    unset($options['css_class']);

    $options['graphql_query_name'] = ['default' => ''];
    return $options;
  }

  /**
   * Get the user defined query name or the default one.
   *
   * @return string
   *   Query name.
   */
  public function getGraphQLQueryName() {
    return $this->getGraphQLName();
  }

  /**
   * Gets the result name.
   *
   * @return string
   *   Result name.
   */
  public function getGraphQLResultName() {
    return $this->getGraphQLName('result', TRUE);
  }

  /**
   * Gets the row name.
   *
   * @return string
   *   Row name.
   */
  public function getGraphQLRowName() {
    return $this->getGraphQLName('row', TRUE);
  }

  /**
   * Gets the filter input name..
   *
   * @return string
   *   Result name.
   */
  public function getGraphQLFilterInputName() {
    return $this->getGraphQLName('filter_input', TRUE);
  }

  /**
   * Gets the contextual filter input name.
   *
   * @return string
   *   Result name.
   */
  public function getGraphQLContextualFilterInputName() {
    return $this->getGraphQLName('contextual_filter_input', TRUE);
  }

  /**
   * Returns the formatted name.
   *
   * @param string|null $suffix
   *   Id suffix, eg. row, result.
   * @param bool $type
   *   Whether to use camel- or snake case. Uses camel case if TRUE. Defaults to
   *   FALSE.
   *
   * @return string The id.
   *   The id.
   */
  public function getGraphQLName($suffix = NULL, $type = FALSE) {
    $queryName = strip_tags($this->getOption('graphql_query_name'));

    if (empty($queryName)) {
      $viewId = $this->view->id();
      $displayId = $this->display['id'];
      $parts = [$viewId, $displayId, 'view', $suffix];
      return $type ? call_user_func_array([StringHelper::class, 'camelCase'], $parts) : call_user_func_array([StringHelper::class, 'propCase'], $parts);
    }

    $parts = array_filter([$queryName, $suffix]);
    return $type ? call_user_func_array([StringHelper::class, 'camelCase'], $parts) : call_user_func_array([StringHelper::class, 'propCase'], $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    unset($categories['title']);
    unset($categories['pager'], $categories['exposed'], $categories['access']);

    unset($options['show_admin_links'], $options['analyze-theme'], $options['link_display']);
    unset($options['show_admin_links'], $options['analyze-theme'], $options['link_display']);

    unset($options['title'], $options['access']);
    unset($options['exposed_block'], $options['css_class']);
    unset($options['query'], $options['group_by']);

    $categories['graphql'] = [
      'title' => $this->t('GraphQL'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    $options['graphql_query_name'] = [
      'category' => 'graphql',
      'title' => $this->t('Query name'),
      'value' => views_ui_truncate($this->getGraphQLQueryName(), 24),
    ];

    if ($entity_type = $this->getOption('entity_type')) {
      $entity_info = \Drupal::entityManager()->getDefinition($entity_type);
      $type_name = $entity_info->get('label');

      $bundle_names = [];
      $bundle_info = \Drupal::entityManager()->getBundleInfo($entity_type);
      foreach ($this->getOption('bundles') as $bundle) {
        $bundle_names[] = $bundle_info[$bundle]['label'];
      }
    }

    $options['entity_type'] = [
      'category' => 'graphql',
      'title' => $this->t('Entity type'),
      'value' => empty($type_name) ? $this->t('None') : $type_name,
    ];

    $options['bundles'] = [
      'category' => 'graphql',
      'title' => $this->t('Bundles'),
      'value' => empty($bundle_names) ? $this->t('All') : implode(', ', $bundle_names),
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'graphql_query_name':
        $form['#title'] .= $this->t('Query name');
        $form['graphql_query_name'] = [
          '#type' => 'textfield',
          '#description' => $this->t('This will be the graphQL query name.'),
          '#default_value' => $this->getGraphQLQueryName(),
        ];
        break;

      case 'entity_type':
        $entity_info = \Drupal::entityManager()->getDefinitions();
        $entity_names = [NULL => $this->t('None')];
        foreach ($entity_info as $type => $info) {
          // is this a content/front-facing entity?
          if ($info instanceof \Drupal\Core\Entity\ContentEntityType) {
            $entity_names[$type] = $info->get('label');
          }
        }

        $form['#title'] .= $this->t('Entity type');
        $form['entity_type'] = [
          '#type' => 'radios',
          '#required' => FALSE,
          '#title' => $this->t('Attach this display to the following entity type'),
          '#options' => $entity_names,
          '#default_value' => $this->getOption('entity_type'),
        ];
        break;

      case 'bundles':
        $options = [];
        $entity_type = $this->getOption('entity_type');
        foreach (\Drupal::entityManager()->getBundleInfo($entity_type) as $bundle => $info) {
          $options[$bundle] = $info['label'];
        }
        $form['#title'] .= $this->t('Bundles');
        $form['bundles'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Attach this display to the following bundles.  If no bundles are selected, the display will be attached to all.'),
          '#options' => $options,
          '#default_value' => $this->getOption('bundles'),
        ];
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'graphql_query_name':
        $this->setOption($section, $form_state->getValue($section));
        break;
      case 'entity_type':
        $new_entity = $form_state->getValue('entity_type');
        $old_entity = $this->getOption('entity_type');
        $this->setOption('entity_type', $new_entity);

        if ($new_entity != $old_entity) {
          // Each entity has its own list of bundles and view modes. If there's
          // only one on the new type, we can select it automatically. Otherwise
          // we need to wipe the options and start over.
          $new_entity_info = \Drupal::entityManager()->getDefinition($new_entity);
          $new_bundles_keys = \Drupal::entityManager()->getBundleInfo($new_entity);
          $new_bundles = array();
          if (count($new_bundles_keys) == 1) {
            $new_bundles[] = $new_bundles_keys[0];
          }
          $this->setOption('bundles', $new_bundles);
        }
        break;
      case 'bundles':
        $this->setOption('bundles', array_values(array_filter($form_state->getValue('bundles'))));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->view->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = (!empty($this->view->result) || $this->view->style_plugin->evenEmpty()) ? $this->view->style_plugin->render($this->view->result) : [];

    // Apply the cache metadata from the display plugin. This comes back as a
    // cache render array so we have to transform it back afterwards.
    $this->applyDisplayCacheabilityMetadata($this->view->element);

    return [
      'view' => $this->view,
      'rows' => $rows,
      'cache' => CacheableMetadata::createFromRenderArray($this->view->element),
    ];
  }
}
