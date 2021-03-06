<?php

/**
 * @file
 * Contains \Drupal\field_test\Type\ShapeItem.
 */

namespace Drupal\field_test\Type;

use Drupal\Core\Entity\Field\FieldItemBase;

/**
 * Defines the 'shape_field' entity field item.
 */
class ShapeItem extends FieldItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see ShapeItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['shape'] = array(
        'type' => 'string',
        'label' => t('Shape'),
      );
      static::$propertyDefinitions['color'] = array(
        'type' => 'string',
        'label' => t('Color'),
      );
    }
    return static::$propertyDefinitions;
  }

}
