<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\EntityTranslationUITest.
 */

namespace Drupal\translation_entity\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityNG;
use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Tests the Entity Translation UI.
 */
abstract class EntityTranslationUITest extends EntityTranslationTestBase {

  /**
   * The id of the entity being translated.
   *
   * @var mixed
   */
  protected $entityId;

  /**
   * Whether the behavior of the language selector should be tested.
   *
   * @var boolean
   */
  protected $testLanguageSelector = TRUE;

  /**
   * Tests the basic translation UI.
   */
  function testTranslationUI() {
    $this->assertBasicTranslation();
    $this->assertOutdatedStatus();
    $this->assertPublishedStatus();
    $this->assertAuthoringInfo();
    $this->assertTranslationDeletion();
  }

  /**
   * Tests the basic translation workflow.
   */
  protected function assertBasicTranslation() {
    // Create a new test entity with original values in the default language.
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = $this->getNewEntityValues($default_langcode);
    $this->entityId = $this->createEntity($values[$default_langcode], $default_langcode);
    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    $this->assertTrue($entity, t('Entity found in the database.'));

    $translation = $this->getTranslation($entity, $default_langcode);
    foreach ($values[$default_langcode] as $property => $value) {
      $stored_value = $this->getValue($translation, $property, $default_langcode);
      $value = is_array($value) ? $value[0]['value'] : $value;
      $message = format_string('@property correctly stored in the default language.', array('@property' => $property));
      $this->assertEqual($stored_value, $value, $message);
    }

    // Add an entity translation.
    $langcode = 'it';
    $values[$langcode] = $this->getNewEntityValues($langcode);

    $base_path = $this->controller->getBasePath($entity);
    $path = $langcode . '/' . $base_path . '/translations/add/' . $default_langcode . '/' . $langcode;
    $this->drupalPost($path, $this->getEditValues($values, $langcode), $this->getFormSubmitAction());
    if ($this->testLanguageSelector) {
      $this->assertNoFieldByXPath('//select[@id="edit-langcode"]', NULL, 'Language selector correclty disabled on translations.');
    }
    $entity = entity_load($this->entityType, $this->entityId, TRUE);

    // Switch the source language.
    $langcode = 'fr';
    $source_langcode = 'it';
    $edit = array('source_langcode[source]' => $source_langcode);
    $path = $langcode . '/' . $base_path . '/translations/add/' . $default_langcode . '/' . $langcode;
    $this->drupalPost($path, $edit, t('Change'));
    $this->assertFieldByXPath("//input[@name=\"{$this->fieldName}[fr][0][value]\"]", $values[$source_langcode][$this->fieldName][0]['value'], 'Source language correctly switched.');

    // Add another translation and mark the other ones as outdated.
    $values[$langcode] = $this->getNewEntityValues($langcode);
    $edit = $this->getEditValues($values, $langcode) + array('translation_entity[retranslate]' => TRUE);
    $this->drupalPost($path, $edit, $this->getFormSubmitAction());
    $entity = entity_load($this->entityType, $this->entityId, TRUE);

    // Check that the entered values have been correctly stored.
    foreach ($values as $langcode => $property_values) {
      $translation = $this->getTranslation($entity, $langcode);
      foreach ($property_values as $property => $value) {
        $stored_value = $this->getValue($translation, $property, $langcode);
        $value = is_array($value) ? $value[0]['value'] : $value;
        $message = format_string('%property correctly stored with language %language.', array('%property' => $property, '%language' => $langcode));
        $this->assertEqual($stored_value, $value, $message);
      }
    }
  }

  /**
   * Tests up-to-date status tracking.
   */
  protected function assertOutdatedStatus() {
    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    $langcode = 'fr';
    $default_langcode = $this->langcodes[0];

    // Mark translations as outdated.
    $edit = array('translation_entity[retranslate]' => TRUE);
    $this->drupalPost($langcode . '/' . $this->controller->getEditPath($entity), $edit, $this->getFormSubmitAction());
    $entity = entity_load($this->entityType, $this->entityId, TRUE);

    // Check that every translation has the correct "outdated" status.
    foreach ($this->langcodes as $enabled_langcode) {
      $prefix = $enabled_langcode != $default_langcode ? $enabled_langcode . '/' : '';
      $path = $prefix . $this->controller->getEditPath($entity);
      $this->drupalGet($path);
      if ($enabled_langcode == $langcode) {
        $this->assertFieldByXPath('//input[@name="translation_entity[retranslate]"]', FALSE, 'The retranslate flag is not checked by default.');
      }
      else {
        $this->assertFieldByXPath('//input[@name="translation_entity[outdated]"]', TRUE, 'The translate flag is checked by default.');
        $edit = array('translation_entity[outdated]' => FALSE);
        $this->drupalPost($path, $edit, $this->getFormSubmitAction());
        $this->drupalGet($path);
        $this->assertFieldByXPath('//input[@name="translation_entity[retranslate]"]', FALSE, 'The retranslate flag is now shown.');
        $entity = entity_load($this->entityType, $this->entityId, TRUE);
        $this->assertFalse($entity->translation[$enabled_langcode]['outdated'], 'The "outdated" status has been correctly stored.');
      }
    }
  }

  /**
   * Tests the translation publishing status.
   */
  protected function assertPublishedStatus() {
    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    $path = $this->controller->getEditPath($entity);

    // Unpublish translations.
    foreach ($this->langcodes as $index => $langcode) {
      if ($index > 0) {
        $edit = array('translation_entity[status]' => FALSE);
        $this->drupalPost($langcode . '/' . $path, $edit, $this->getFormSubmitAction());
        $entity = entity_load($this->entityType, $this->entityId, TRUE);
        $this->assertFalse($entity->translation[$langcode]['status'], 'The translation has been correctly unpublished.');
      }
    }

    // Check that the last published translation cannot be unpublished.
    $this->drupalGet($path);
    $this->assertFieldByXPath('//input[@name="translation_entity[status]" and @disabled="disabled"]', TRUE, 'The last translation is published and cannot be unpublished.');
  }

  /**
   * Tests the translation authoring information.
   */
  protected function assertAuthoringInfo() {
    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    $path = $this->controller->getEditPath($entity);
    $values = array();

    // Post different authoring information for each translation.
    foreach ($this->langcodes as $index => $langcode) {
      $user = $this->drupalCreateUser();
      $values[$langcode] = array(
        'uid' => $user->uid,
        'created' => REQUEST_TIME - mt_rand(0, 1000),
      );
      $edit = array(
        'translation_entity[name]' => $user->name,
        'translation_entity[created]' => format_date($values[$langcode]['created'], 'custom', 'Y-m-d H:i:s O'),
      );
      $prefix = $index > 0 ? $langcode . '/' : '';
      $this->drupalPost($prefix . $path, $edit, $this->getFormSubmitAction());
    }

    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    foreach ($this->langcodes as $langcode) {
      $this->assertEqual($entity->translation[$langcode]['uid'] == $values[$langcode]['uid'], 'Translation author correctly stored.');
      $this->assertEqual($entity->translation[$langcode]['created'] == $values[$langcode]['created'], 'Translation date correctly stored.');
    }

    // Try to post non valid values and check that they are rejected.
    $langcode = 'en';
    $edit = array(
      // User names have by default length 8.
      'translation_entity[name]' => $this->randomName(12),
      'translation_entity[created]' => $this->randomName(),
    );
    $this->drupalPost($path, $edit, $this->getFormSubmitAction());
    $this->assertTrue($this->xpath('//div[@id="messages"]//div[contains(@class, "error")]//ul'), 'Invalid values generate a list of form errors.');
    $this->assertEqual($entity->translation[$langcode]['uid'] == $values[$langcode]['uid'], 'Translation author correctly kept.');
    $this->assertEqual($entity->translation[$langcode]['created'] == $values[$langcode]['created'], 'Translation date correctly kept.');
  }

  /**
   * Tests translation deletion.
   */
  protected function assertTranslationDeletion() {
    // Confirm and delete a translation.
    $langcode = 'fr';
    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    $this->drupalPost($langcode . '/' . $this->controller->getEditPath($entity), array(), t('Delete translation'));
    $this->drupalPost(NULL, array(), t('Delete'));
    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    if ($this->assertTrue(is_object($entity), 'Entity found')) {
      $translations = $entity->getTranslationLanguages();
      $this->assertTrue(count($translations) == 2 && empty($translations[$langcode]), 'Translation successfully deleted.');
    }
  }

  /**
   * Returns an array of entity field values to be tested.
   */
  protected function getNewEntityValues($langcode) {
    return array($this->fieldName => array(array('value' => $this->randomName(16))));
  }

  /**
   * Returns an edit array containing the values to be posted.
   */
  protected function getEditValues($values, $langcode, $new = FALSE) {
    $edit = $values[$langcode];
    $langcode = $new ? LANGUAGE_NOT_SPECIFIED : $langcode;
    foreach ($values[$langcode] as $property => $value) {
      if (is_array($value)) {
        $edit["{$property}[$langcode][0][value]"] = $value[0]['value'];
        unset($edit[$property]);
      }
    }
    return $edit;
  }

  /**
   * Returns the form action value to be used to submit the entity form.
   */
  protected function getFormSubmitAction() {
    return t('Save');
  }

  /**
   * Returns the translation object to use to retrieve the translated values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested.
   * @param string $langcode
   *   The language code identifying the translation to be retrieved.
   *
   * @return \Drupal\Core\TypedData\TranslatableInterface
   *   The translation object to act on.
   */
  protected function getTranslation(EntityInterface $entity, $langcode) {
    return $entity instanceof EntityNG ? $entity->getTranslation($langcode, FALSE) : $entity;
  }

  /**
   * Returns the value for the specified property in the given language.
   *
   * @param \Drupal\Core\TypedData\TranslatableInterface $translation
   *   The translation object the property value should be retrieved from.
   * @param string $property
   *   The property name.
   * @param string $langcode
   *   The property value.
   *
   * @return
   *   The property value.
   */
  protected function getValue(ComplexDataInterface $translation, $property, $langcode) {
    $key = $property == 'user_id' ? 'target_id' : 'value';
    if (($translation instanceof EntityInterface) && !($translation instanceof EntityNG)) {
      return is_array($translation->$property) ? $translation->{$property}[$langcode][0][$key] : $translation->$property;
    }
    else {
      return $translation->get($property)->{$key};
    }
  }

}
