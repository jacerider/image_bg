<?php

namespace Drupal\image_bg\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the video field formatter.
 *
 * @FieldFormatter(
 *   id = "image_bg",
 *   label = @Translation("Image Background"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageBg extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['image_link']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $image_style = NULL;
    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      if ($image_style) {
        $image_url = $image_style->buildUrl($file->getFileUri());
      }
      else {
        $image_url = file_create_url($file->getFileUri());
      }

      $elements[] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'style' => [
            'background-image' => sprintf("background-image: url('%s');", $image_url),
          ],
        ],
        '#cache' => array(
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ),
      ];
    }

    if (count($elements) == 1) {
      // If we only have one image, we use the wrapping div.
      $elements = $elements[0];
      $elements[] = [];
    }
    return $elements;
  }

}
