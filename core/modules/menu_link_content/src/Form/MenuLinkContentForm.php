<?php

namespace Drupal\menu_link_content\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to add/update content menu links.
 *
 * @internal
 */
class MenuLinkContentForm extends ContentEntityForm {

  /**
   * The content menu link.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected $entity;

  /**
   * Constructs a MenuLinkContentForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menuParentSelector
   *   The menu parent form selector service.
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    protected MenuParentFormSelectorInterface $menuParentSelector,
    protected PathValidatorInterface $pathValidator,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('menu.parent_form_selector'),
      $container->get('path.validator'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $parent_id = $this->entity->getParentId() ?: $this->getRequest()->query->get('parent');
    $default = $this->entity->getMenuName() . ':' . $parent_id;
    $id = $this->entity->isNew() ? '' : $this->entity->getPluginId();
    $menu_id = $this->entity->getMenuName();
    $menu = $this->entityTypeManager->getStorage('menu')->load($menu_id);
    if ($menu instanceof MenuInterface && $this->entity->isNew()) {
      $form['menu_parent'] = $this->menuParentSelector->parentSelectElement($default, $id, [
        $menu_id => $menu->label(),
      ]);
    }
    else {
      $form['menu_parent'] = $this->menuParentSelector->parentSelectElement($default, $id);
    }
    $form['menu_parent']['#weight'] = 10;
    $form['menu_parent']['#title'] = $this->t('Parent link');
    $form['menu_parent']['#description'] = $this->t('The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.');
    $form['menu_parent']['#attributes']['class'][] = 'menu-title-select';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#button_type'] = 'primary';
    $element['delete']['#access'] = $this->entity->access('delete');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    [$menu_name, $parent] = explode(':', $form_state->getValue('menu_parent'), 2);

    $entity->parent->value = $parent;
    $entity->menu_name->value = $menu_name;
    $entity->enabled->value = (!$form_state->isValueEmpty(['enabled', 'value']));
    $entity->expanded->value = (!$form_state->isValueEmpty(['expanded', 'value']));

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // The entity is rebuilt in parent::submit().
    $menu_link = $this->entity;
    $menu_link->save();

    $this->messenger()->addStatus($this->t('The menu link has been saved.'));

    $form_state->setRedirectUrl($menu_link->toUrl('canonical'));
  }

}
