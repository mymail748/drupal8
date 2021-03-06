<?php

/**
 * @file
 * Definition of Drupal\views_test_data\views\join\JoinTest.
 */

namespace Drupal\views_test_data\Plugin\views\join;

use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a join test plugin.
 *
 * @Plugin(
 *   id = "join_test",
 *   title = @Translation("Join test")
 * )
 */
class JoinTest extends JoinPluginBase {
  /**
   * A value which is used to build an additional join condition.
   *
   * @var int
   */
  protected $joinValue;

  /**
   * Returns the joinValue property.
   *
   * @return int
   */
  public function getJoinValue() {
    return $this->joinValue;
  }

  /**
   * Sets the joinValue property.
   *
   * @param int $join_value
   */
  public function setJoinValue($join_value) {
    $this->joinValue = $join_value;
  }


  /**
   * Overrides Drupal\views\Plugin\views\join\JoinPluginBase::buildJoin().
   */
  public function buildJoin($select_query, $table, $view_query) {
    // Add an additional hardcoded condition to the query.
    $this->extra = 'views_test_data.uid = ' . $this->getJoinValue();
    parent::buildJoin($select_query, $table, $view_query);
  }

}
