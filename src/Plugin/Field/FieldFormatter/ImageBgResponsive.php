<?php

namespace Drupal\image_bg\Plugin\Field\FieldFormatter;

use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the video field formatter.
 *
 * @FieldFormatter(
 *   id = "image_bg_responsive",
 *   label = @Translation("Image Responsive Background"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageBgResponsive extends ResponsiveImageFormatter {

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
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $field_name = $this->fieldDefinition->getName();
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $responsive_image_style_setting = $this->getSetting('responsive_image_style');

    // Collect cache tags to be added for each item in the field.
    $responsive_image_style = $this->responsiveImageStyleStorage->load($responsive_image_style_setting);
    $image_styles_to_load = [];
    $cache_tags = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    foreach ($files as $delta => $file) {
      $keys = [
        $entity_type,
        $entity->bundle(),
        $entity->id(),
        $this->viewMode,
        $field_name,
        $delta,
      ];
      $id = Html::cleanCssIdentifier(implode('-', $keys));

      $element = [
        '#type' => 'html_tag',
        '#tag' => 'div',
      ];
      $vars = [
        'uri' => $this->getUri($file),
        'responsive_image_style_id' => $responsive_image_style_setting,
      ];
      template_preprocess_responsive_image($vars);

      // Split each source into multiple rules.
      foreach (array_reverse($vars['sources']) as $source_i => $source) {
        $attr = $source->toArray();

        $srcset = explode(', ', $attr['srcset']);

        foreach ($srcset as $src_i => $src) {
          list($src, $res) = explode(' ', $src);

          $media = isset($attr['media']) ? $attr['media'] : '';

          // Add "retina" to media query if this is a 2x image.
          if ($res && $res === '2x') {
            $media = "$media and (-webkit-min-device-pixel-ratio: 2), $media and (min-resolution: 192dpi)";
          }

          // Correct a bug in template_preprocess_responsive_image which
          // generates an invalid media rule "screen (max-width)" when no
          // min-width is specified. If this bug gets fixed, this replacement
          // will deactivate.
          $media = str_replace('screen (max-width', 'screen and (max-width', $media);
          $style = sprintf('%s {', '.' . $id);
          $style .= sprintf("background-image: url('%s');", $src);
          $style .= '}';
          $element['#attached']['html_head'][] = [
            [
              '#tag' => 'style',
              '#attributes' => [
                'media' => $media,
              ],
              '#value' => $style,
            ],
            $id . implode('__', [$source_i, $src_i]),
          ];
        }
      }
      $element['#attributes']['class'][] = $id;
      $elements[] = $element;
    }

    if (count($elements) == 1) {
      // If we only have one image, we use the wrapping div.
      $elements = $elements[0];
      $elements[] = [];
    }
    return $elements;
  }

  /**
   * Returns the entity URI.
   */
  protected function getUri(EntityInterface $entity) {
    return $entity->getFileUri();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return \Drupal::moduleHandler()->moduleExists('responsive_image');
  }

}
