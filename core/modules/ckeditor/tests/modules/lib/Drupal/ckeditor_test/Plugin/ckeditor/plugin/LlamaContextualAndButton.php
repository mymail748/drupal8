<?php

/**
 * @file
 * Contains \Drupal\ckeditor_test\Plugin\ckeditor\plugin\LlamaContextualAndButton.
 */

namespace Drupal\ckeditor_test\Plugin\ckeditor\plugin;

use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\Plugin\Core\Entity\Editor;

/**
 * Defines a "LlamaContextualAndbutton" plugin, with a contextually OR toolbar
 * builder-enabled "llama" feature.
 *
 * @Plugin(
 *   id = "llama_contextual_and_button",
 *   label = @Translation("Contextual Llama With Button"),
 *   module = "ckeditor_test"
 * )
 */
class LlamaContextualAndButton extends Llama implements CKEditorPluginContextualInterface, CKEditorPluginButtonsInterface, CKEditorPluginConfigurableInterface {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginContextualInterface::isEnabled().
   */
  function isEnabled(Editor $editor) {
    // Automatically enable this plugin if the Strike button is enabled.
    foreach ($editor->settings['toolbar']['buttons'] as $row) {
      if (in_array('Strike', $row)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginButtonsInterface::getButtons().
   */
  function getButtons() {
    return array(
      'Llama' => array(
        'label' => t('Insert Llama'),
      ),
    );
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  function getFile() {
    return drupal_get_path('module', 'ckeditor_test') . '/js/llama_contextual_and_button.js';
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginConfigurableInterface::settingsForm().
   */
  function settingsForm(array $form, array &$form_state, Editor $editor) {
    // Defaults.
    $config = array('ultra_llama_mode' => FALSE);
    if (isset($editor->settings['plugins']['llama_contextual_and_button'])) {
      $config = $editor->settings['plugins']['llama_contextual_and_button'];
    }

    $form['ultra_llama_mode'] = array(
      '#title' => t('Ultra llama mode'),
      '#type' => 'checkbox',
      '#default_value' => $config['ultra_llama_mode'],
    );

    return $form;
  }

}
