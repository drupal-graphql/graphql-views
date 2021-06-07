<?php

namespace Drupal\graphql_views\Plugin\views\row;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which displays entities as raw data.
 *
 * @ViewsRow(
 *   id = "graphql_entity",
 *   title = @Translation("Entity"),
 *   help = @Translation("Use entities as row data."),
 *   display_types = {"graphql"}
 * )
 */
class GraphQLEntityRow extends RowPluginBase {

  use EntityTranslationRenderTrait {
    getEntityTranslationRenderer as getEntityTranslationRendererBase;
  }

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $plugin = parent::create($container, $configuration, $pluginId, $pluginDefinition);
    $plugin->setLanguageManager($container->get('language_manager'));
    $plugin->setEntityTypeManager($container->get('entity_type.manager'));
    $plugin->setEntityRepository($container->get('entity.repository'));
    return $plugin;
  }

  /**
   * Set the language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  protected function setLanguageManager(LanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
  }

  /**
   * Set the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  protected function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Set the entity repository.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  protected function setEntityRepository(EntityRepositoryInterface $entityRepository) {
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    if ($entity = $this->getEntityFromRow($row)) {
      return $this->view->getBaseEntityType() ? $this->getEntityTranslation($entity, $row) : $entity;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTranslationRenderer() {
    if ($this->view->getBaseEntityType()) {
      return $this->getEntityTranslationRendererBase();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    if ($entityType = $this->view->getBaseEntityType()) {
      return $entityType->id();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityRepository() {
    return $this->entityRepository;
  }

  /**
   * Retrieves the entity object from a result row.
   *
   * @param \Drupal\Views\ResultRow $row
   *   The views result row object.
   *
   * @return null|\Drupal\Core\Entity\EntityInterface
   *   The extracted entity object or NULL if it could not be retrieved.
   */
  protected function getEntityFromRow(ResultRow $row) {
    if (isset($row->_entity) && $row->_entity instanceof EntityInterface) {
      return $row->_entity;
    }

    if (isset($row->_object) && $row->_object instanceof EntityAdapter) {
      return $row->_object->getValue();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    if ($this->view->getBaseEntityType()) {
      $this->getEntityTranslationRenderer()->query($this->view->getQuery());
    }
  }

}
