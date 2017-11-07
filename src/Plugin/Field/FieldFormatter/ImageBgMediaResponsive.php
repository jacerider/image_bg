<?php

namespace Drupal\image_bg\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the video field formatter.
 *
 * @FieldFormatter(
 *   id = "image_bg_media_responsive",
 *   label = @Translation("Image Responsive Background"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ImageBgMediaResponsive extends ImageBgResponsive {

  /**
   * Returns the entity URI.
   */
  protected function getUri(EntityInterface $entity) {
    return $entity->get('thumbnail')->entity->getFileUri();
  }

  /**
   * {@inheritdoc}
   *
   * This has to be overriden because FileFormatterBase expects $item to be
   * of type \Drupal\file\Plugin\Field\FieldType\FileItem and calls
   * isDisplayed() which is not in FieldItemInterface.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    return $target_type == 'media' && parent::isApplicable($field_definition);
  }

}
