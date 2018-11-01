<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\ContentFieldCount
 */

namespace Drupal\site_audit\Plugin\SiteAuditCheck;

use Drupal\site_audit\Plugin\SiteAuditCheckBase;
use Drupal\site_audit\Renderer\Html;

/**
 * Provides the ContentFieldCount Check.
 *
 * @SiteAuditCheck(
 *  id = "content_field_count",
 *  name = @Translation("Field counts"),
 *  description = @Translation("Total number of fields"),
 *  report = "content",
 *  weight = -2,
 * )
 */
class ContentFieldCount extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {
    return $this->t('There are no fields available!');
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    $table_rows = [];
    foreach ($this->registry->fields as $field_name => $type) {
      $table_rows[] = [
        $field_name,
        $type,
      ];
    }
    $headers = [
      $this->t('Name'),
      $this->t('Type'),
    ];
    return [
      '#theme' => 'table',
      '#class' => 'table-condensed',
      'headers' => $headers,
      'rows' => $table_rows,
      '#title' => $this->t('There are @count total fields.', [
        '@count' => count($this->registry->fields),
      ]),
    ];
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->t('There are @count total fields, which is higher than average', array(
      '@count' => count($this->registry->fields),
    ));
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {
    if ($this->getScore() == SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL) {
      return $this->t('Consider disabling the field module.');
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    if (!isset($this->registry->fields)) {
      // it hasn't been calculated yet, so do it now
      $map = \Drupal::entityManager()->getFieldMap();
      $this->registry->fields = array();
      $this->registry->default_fields = array(
        'body',
        'comment_body',
      );
      foreach ($map as $entity => $fields) {
        foreach ($fields as $field => $description) {
          if (preg_match('/^field\_/', $field) || in_array($field, $this->registry->default_fields)) {
            $this->registry->fields[$field] = $description['type'];
          }
        }
      }
    }

    if (count($this->registry->fields) == 0) {
      $this->abort;
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL;
    }
    elseif (count($this->registry->fields) > 75) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN;
    }
    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

}