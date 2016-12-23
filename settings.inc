<?php
// $Id$
function textbook_companion_settings_form($form, &$form_state)
  {
    $form['emails'] = array(
        '#type' => 'textfield',
        '#title' => t('(Bcc) Notification emails'),
        '#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
        '#size' => 50,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => variable_get('textbook_companion_emails', '')
    );
    $form['cc_emails'] = array(
        '#type' => 'textfield',
        '#title' => t('(Cc) Notification emails'),
        '#description' => t('Specify emails id for Cc option of mail system with comma separated'),
        '#size' => 50,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => variable_get('textbook_companion_cc_emails', '')
    );
    $form['from_email'] = array(
        '#type' => 'textfield',
        '#title' => t('Outgoing from email address'),
        '#description' => t('Email address to be display in the from field of all outgoing messages'),
        '#size' => 50,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => variable_get('textbook_companion_from_email', '')
    );
    $form['extensions']['source'] = array(
        '#type' => 'textfield',
        '#title' => t('Allowed source file extensions'),
        '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
        '#size' => 50,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => variable_get('textbook_companion_source_extensions', '')
    );
    $form['extensions']['sample_source'] = array(
        '#type' => 'textfield',
        '#title' => t('Allowed sample source file extensions'),
        '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
        '#size' => 50,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => variable_get('textbook_companion_sample_source_extensions', '')
    );
    $options = array(
        '1' => t('1'),
        '2' => t('2'),
        '3' => t('3')
    );
    $form['book_preference_options'] = array(
        '#type' => 'radios',
        '#title' => t('Book Preferences'),
        '#options' => $options,
        '#required' => TRUE,
        '#description' => t('Set number book preference to be allowed'),
        '#default_value' => variable_get('textbook_companion_book_preferences', '')
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    return $form;
  }
function textbook_companion_settings_form_validate($form, &$form_state)
  {
    return;
  }
function textbook_companion_settings_form_submit($form, &$form_state)
  {
    variable_set('textbook_companion_emails', $form_state['values']['emails']);
    variable_set('textbook_companion_cc_emails', $form_state['values']['cc_emails']);
    variable_set('textbook_companion_from_email', $form_state['values']['from_email']);
    variable_set('textbook_companion_source_extensions', $form_state['values']['source']);
    variable_set('textbook_companion_sample_source_extensions', $form_state['values']['sample_source']);
    variable_set('textbook_companion_book_preferences', $form_state['values']['book_preference_options']);
    drupal_set_message(t('Settings updated'), 'status');
  }
