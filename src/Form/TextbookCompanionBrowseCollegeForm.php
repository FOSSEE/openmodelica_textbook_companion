<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionBrowseCollegeForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class TextbookCompanionBrowseCollegeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_browse_college_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];
    ahah_helper_register($form, $form_state);
    if ($form_state->getStorage()) {
      $usage_default_value = '0';
    }
    else {
      $usage_default_value = $form_state->getStorage();
    }
    $form['college_info'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="college-info-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];
    $form['college_info']['college'] = [
      '#type' => 'select',
      '#title' => t('College Name'),
      '#options' => _list_of_colleges(),
      '#default_value' => $usage_default_value,
      '#ahah' => [
        'event' => 'change',
        'path' => ahah_helper_path([
          'college_info'
          ]),
        'wrapper' => 'college-info-wrapper',
      ],
    ];
    if ($usage_default_value != '0') {
      $form['college_info']['book_details'] = [
        '#type' => 'item',
        '#value' => _list_books_by_college($usage_default_value),
      ];
    }
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }

}
?>
