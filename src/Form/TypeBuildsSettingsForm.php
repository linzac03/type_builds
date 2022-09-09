<?php

namespace Drupal\type_builds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TypeBuildsSettingsForm extends ConfigFormBase {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  public function getEditableConfigNames() {
    return ['type_builds.settings'];
  }

  public function getFormId() {
    return 'type_builds_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('type_builds.settings');
    // Switch to temporarily disable runtime builds
    $form['no_build'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable runtime builds'),
      '#default_value' => $config->get('no_build')
    ];

    // Get content types and set to options
    $contentTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $typeOptions = [];
    foreach($contentTypes as $type) {
      $typeOptions[$type->id()] = $type->label();
    }
    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#options' => $typeOptions,
      '#default_value' => $config->get('content_types'),
      '#description' => 'Select the types that on update should trigger a build',
      '#title' => 'Content Types'
    ];

    //Get frontend environments and set to options
    $frontendEnvironments = $this->entityTypeManager->getStorage('frontend_environment')
      ->loadByProperties(['deployment_strategy' => 'manual']);

    $envOptions = [];
    foreach($frontendEnvironments as $environment) {
      $envOptions[$environment->id()] = $environment->label(); 
    }
    $form['frontend_type_environments'] = [
      '#type' => 'checkboxes',
      '#options' => $envOptions,
      '#default_value' => $config->get('frontend_type_environments'),
      '#description' => $this->t('Select the environments to be built when specific types are updated'),
      '#title' => $this->t('Frontend Environments')
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('type_builds.settings');
    $config->set('no_build', $form_state->getValue('no_build'));
    $config->set('content_types', $form_state->getValue('content_types'));
    $config->set('frontend_type_environments', $form_state->getValue('frontend_type_environments'));
    $config->save();
  }
}
