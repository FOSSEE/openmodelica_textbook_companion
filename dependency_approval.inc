<?php
/******************************************************************************/
/**************************** DEPNDENCY APPROVAL ******************************/
/******************************************************************************/
function textbook_companion_dependency_approval_form($form_state)
  {
    // $form['#redirect'] = FALSE;
    // ahah_helper_register($form, $form_state);
    /* default value for ahah fields */
    // if (!isset($form_state['storage']['run']['dependency']))
    // {
    //   $dependency_default_value = 0;
    // } else {
    //   $dependency_default_value =  $form_state['storage']['run']['dependency'];
    // }
    $form['run'] = array(
        '#type' => 'fieldset',
        '#title' => t('Manage Dependency'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#prefix' => '<div id="run-wrapper">',
        '#suffix' => '</div>',
        '#tree' => TRUE
    );
    $form['run']['dependency'] = array(
        '#type' => 'select',
        '#title' => t('Dependency'),
        '#options' => _textbook_companion_list_of_dependencies(),
        '#default_value' => $dependency_default_value,
        '#tree' => TRUE
        // '#ahah' => array(
        //   'event'   => 'change',
        //   'effect' => 'none',
        //   'path'    => ahah_helper_path(array('run')),
        //   'wrapper' => 'run-wrapper',
        //   'progress' => array(
        //     'type' => 'throbber',
        //     'message' => t(''),
        //   ),
        // ),
    );
    // $example_list = array();
    /************ START OF $_POST **************/
    /*
    if ($_POST)
    {
    if ($dependency_default_value > 0)
    {
    $example_id_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE dependency_id = %d", $dependency_default_value);
    while ($example_id_data = db_fetch_object($example_id_q)) {
    $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $example_id_data->example_id);
    $example_data = db_fetch_object($example_q);
    $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);
    $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);
    $example_list[] = array($example_data->number, $chapter_data->number . ' . ' . $chapter_data->name, $preference_data->book);
    }
    $example_list_header = array('Code', 'Chapter', 'Book');
    $example = theme_table($example_list_header, $example_list);
    
    if ($example_list) {
    $form['run']['example_dependency'] = array(
    '#type'  => 'item',
    '#value' => $example,
    );
    $form['run']['example_dependency_message'] = array(
    '#type'  => 'item',
    '#value' => 'Please unlink the dependency from the above example before deleting it',
    );
    }
    }
    }
    */
    /* hidden form elements */
    // $form['run']['dependency_hidden'] = array(
    //   '#type'  => 'hidden',
    //   '#value' =>  $form_state['values']['run']['dependency'],
    // );
    // if (!$example_list && $dependency_default_value > 0)
    // {
    $form['run']['delete_dependency'] = array(
        '#type' => 'checkbox',
        '#title' => t('Delete Dependency')
    );
    $form['run']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    // }
    return $form;
  }
function textbook_companion_dependency_approval_form_submit($form, &$form_state)
  {
    $user = \Drupal::currentUser();
    $root_path = textbook_companion_path();
    if ($form_state['clicked_button']['#value'] == 'Submit')
      {
        if (\Drupal::currentUser()->hasPermission('bulk manage code'))
          {
            if ($form_state['values']['run']['delete_dependency'] == "1")
              {
                /*$example_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE dependency_id = %d", $form_state['values']['run']['dependency']);*/
                $query = \Drupal::database()->select('textbook_companion_example_dependency');
                $query->fields('textbook_companion_example_dependency');
                $query->condition('dependency_id', $form_state['values']['run']['dependency']);
                $example_q = $query->execute();
                if ($example_data = $example_q->fetchObject())
                  {
                    /*$example_id_q = db_query("SELECT * FROM {textbook_companion_example_dependency} WHERE dependency_id = %d", $form_state['values']['run']['dependency']);*/
                    $query = \Drupal::database()->select('textbook_companion_example_dependency');
                    $query->fields('textbook_companion_example_dependency');
                    $query->condition('dependency_id', $form_state['values']['run']['dependency']);
                    $example_id_q = $query->execute();
                    while ($example_id_data = $example_id_q->fetchObject())
                      {
                        /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $example_id_data->example_id);
                        $example_data = db_fetch_object($example_q);*/
                        $query = \Drupal::database()->select('textbook_companion_example');
                        $query->fields('textbook_companion_example');
                        $query->condition('id', $example_id_data->example_id);
                        $result = $query->execute();
                        $example_data = $result->fetchObject();
                        /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
                        $chapter_data = db_fetch_object($chapter_q);*/
                        $query = \Drupal::database()->select('textbook_companion_chapter');
                        $query->fields('textbook_companion_chapter');
                        $query->condition('id', $example_data->chapter_id);
                        $result = $query->execute();
                        $chapter_data = $result->fetchObject();
                        /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
                        $preference_data = db_fetch_object($preference_q);*/
                        $query = \Drupal::database()->select('textbook_companion_preference');
                        $query->fields('textbook_companion_preference');
                        $query->condition('id', $chapter_data->preference_id);
                        $result = $query->execute();
                        $preference_data = $result->fetchObject();
                        $example_list[] = array(
                            $preference_data->book,
                            $chapter_data->number,
                            $example_data->number
                        );
                      }
                    $example_list_header = array(
                        'Book',
                        'Chapter',
                        'Code'
                    );
                    // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $example = theme('table', array(
//                         'headers' => $example_list_header,
//                         'rows' => $example_list
//                     ));

                    \Drupal::messenger()->addError('Cannot delete dependency since it is linked with following examples. Delete these examples first before deleting the dependency file :' . $example);
                  }
                else
                  {
                    if (textbook_companion_delete_dependency($form_state['values']['run']['dependency']))
                      {
                        \Drupal::messenger()->addStatus('Dependency deleted');
                        /* email */
                        $email_subject = t('Dependency deleted');
                        $email_body = t('Dependency deleted : .') . $form_state['values']['run']['dependency'];
                        $email_to = $user->mail;
                        $param['standard']['subject'] = $email_subject;
                        $param['standard']['body'] = $email_body;
                        if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE))
                            \Drupal::messenger()->addError('Error sending email message.');
                      }
                  }
              }
          }
      }
  }
function _textbook_companion_list_of_dependencies()
  {
    $dependencies = array(
        '0' => 'Please select...'
    );
    /*$dependency_q = db_query("SELECT * FROM {textbook_companion_dependency_files} ORDER BY filename ASC");*/
    $query = \Drupal::database()->select('textbook_companion_dependency_files');
    $query->fields('textbook_companion_dependency_files');
    $query->orderBy('filename', 'ASC');
    $dependency_q = $query->execute();
    while ($dependency_data = $dependency_q->fetchObject())
      {
        $dependencies[$dependency_data->id] = $dependency_data->filename . ' (' . $dependency_data->filepath . ')';
      }
    return $dependencies;
  }
function textbook_companion_delete_dependency($dependency_id)
  {
    $user = \Drupal::currentUser();
    $root_path = textbook_companion_path();
    $status = TRUE;
    /*$dependency_files_q = db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE id = %d", $dependency_id);
    $dependency_files_data = db_fetch_object($dependency_files_q);*/
    $query = \Drupal::database()->select('textbook_companion_dependency_files');
    $query->fields('textbook_companion_dependency_files');
    $query->condition('id', $dependency_id);
    $result = $query->execute();
    $dependency_files_data = $result->fetchObject();
    if (!$dependency_files_data)
      {
        \Drupal::messenger()->addError(t('Invalid dependency.'));
        return FALSE;
      }
    if (!file_exists($root_path . $dependency_files_data->filepath))
      {
        \Drupal::messenger()->addError(t('Error deleting !file. File does not exists.', array(
            '!file' => $dependency_files_data->filepath
        )));
        return FALSE;
      }
    /* removing dependency file */
    if (!unlink($root_path . $dependency_files_data->filepath))
      {
        $status = FALSE;
        \Drupal::messenger()->addError(t('Error deleting !file', array(
            '!file' => $dependency_files_data->filepath
        )));
        /* sending email to admins */
        $email_to = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
        $param['standard']['subject'] = "[ERROR] Error deleting dependency file";
        $param['standard']['body'] = "Error deleting dependency files by " . $user->uid . " at " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . " :
    dependency id : " . $dependency_id . "
    file id : " . $dependency_files_data->id . "
    file path : " . $dependency_files_data->filepath;
        if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE))
            \Drupal::messenger()->addError('Error sending email message.');
      }
    else
      {
        /* deleting dependency files database entries */
        /*db_query("DELETE FROM {textbook_companion_dependency_files} WHERE id = %d", $dependency_id);*/
        $query = \Drupal::database()->delete('textbook_companion_dependency_files');
        $query->condition('id', $dependency_id);
        $num_deleted = $query->execute();
      }
    return $status;
  }
