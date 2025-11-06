<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionSettingsForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class TextbookCompanionSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_settings_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Bcc) Notification emails'),
      '#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails'),
    ];
    $form['cc_emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Cc) Notification emails'),
      '#description' => t('Specify emails id for Cc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails'),
    ];
    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => t('Outgoing from email address'),
      '#description' => t('Email address to be display in the from field of all outgoing messages'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'),
    ];
    $form['extensions']['source'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed source file extensions'),
      '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('textbook_companion.settings')->get('textbook_companion_source_extensions'),
    ];
    $form['extensions']['sample_source'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed sample source file extensions'),
      '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('textbook_companion.settings')->get('textbook_companion_sample_source_extensions'),
    ];
    $options = [
      '1' => t('1'),
      '2' => t('2'),
      '3' => t('3'),
    ];
    $form['book_preference_options'] = [
      '#type' => 'radios',
      '#title' => t('Book Preferences'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => t('Set number book preference to be allowed'),
      '#default_value' => \Drupal::config('textbook_companion.settings')->get('textbook_companion_book_preferences'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('textbook_companion.settings')->set('textbook_companion_emails', $form_state->getValue(['emails']))->save();
    \Drupal::configFactory()->getEditable('textbook_companion.settings')->set('textbook_companion_cc_emails', $form_state->getValue(['cc_emails']))->save();
    \Drupal::configFactory()->getEditable('textbook_companion.settings')->set('textbook_companion_from_email', $form_state->getValue(['from_email']))->save();
    \Drupal::configFactory()->getEditable('textbook_companion.settings')->set('textbook_companion_source_extensions', $form_state->getValue(['source']))->save();
    \Drupal::configFactory()->getEditable('textbook_companion.settings')->set('textbook_companion_sample_source_extensions', $form_state->getValue(['sample_source']))->save();
    \Drupal::configFactory()->getEditable('textbook_companion.settings')->set('textbook_companion_book_preferences', $form_state->getValue(['book_preference_options']))->save();
    \Drupal::messenger()->addStatus(t('Settings updated'));
  }

}
?>
