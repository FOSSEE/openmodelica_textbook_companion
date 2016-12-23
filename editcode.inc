<?php
// $Id$
/******************************************************************************/
/***************************** EDIT EXAMPLE ***********************************/
/******************************************************************************/
function upload_examples_edit_form($form, &$form_state)
  {
    global $user;
    $example_id = arg(3);
    /* get example details */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $example_q = $query->execute();
    $example_data = $example_q->fetchObject();
    if (!$example_q)
      {
        drupal_set_message(t("Invalid example selected."), 'error');
        drupal_goto('');
        return;
      }
    if ($example_data->approval_status != 0)
      {
        drupal_set_message(t("You cannot edit an example after it has been approved or dis-approved. Please contact site administrator if you want to edit this example."), 'error');
        drupal_goto('');
        return;
      }
    /* get examples files */
    $source_file = "";
    $source_id = 0;
    $result1_file = "";
    $result1_id = 0;
    $result2_file = "";
    $result2_id = 0;
    $xcos1_file = "";
    $xcos1_id = 0;
    $xcos2_file = "";
    $xcos2_id = 0;
    /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
    $query = db_select('textbook_companion_example_files');
    $query->fields('textbook_companion_example_files');
    $query->condition('example_id', $example_id);
    $example_files_q = $query->execute();
    while ($example_files_data = $example_files_q->fetchObject())
      {
        if ($example_files_data->filetype == "S")
          {
            $source_file = l($example_files_data->filename, 'textbook-companion/download/file/' . $example_files_data->id);
            $source_file_id = $example_files_data->id;
            //var_dump($source_file);die;
          }
      }
    /* get chapter details */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data)
      {
        drupal_set_message(t("Invalid chapter selected."), 'error');
        drupal_goto('');
        return;
      }
    /* get preference details */
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        drupal_set_message(t("Invalid book selected."), 'error');
        drupal_goto('');
        return;
      }
    if ($preference_data->approval_status != 1)
      {
        drupal_set_message(t("Cannot edit example. Either the book proposal has not been approved or it has been rejected."), 'error');
        drupal_goto('');
        return;
      }
    /* get proposal details */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
        drupal_set_message(t("Invalid proposal selected."), 'error');
        drupal_goto('');
        return;
      }
    if ($proposal_data->uid != $user->uid)
      {
        drupal_set_message(t("You do not have permissions to edit this example."), 'error');
        drupal_goto('');
        return;
      }
    $user_data = user_load($proposal_data->uid);
    $form['#redirect'] = 'textbook-companion/code';
    $form['#attributes'] = array(
        'enctype' => "multipart/form-data"
    );
    $form['book_details']['book'] = array(
        '#type' => 'item',
        '#markup' => $preference_data->book,
        '#title' => t('Title of the Book')
    );
    $form['contributor_name'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->full_name,
        '#title' => t('Contributor Name')
    );
    $form['number'] = array(
        '#type' => 'item',
        '#title' => t('Chapter No'),
        '#markup' => $chapter_data->number
    );
    $form['name'] = array(
        '#type' => 'item',
        '#title' => t('Title of the Chapter'),
        '#markup' => $chapter_data->name
    );
    $form['example_number'] = array(
        '#type' => 'item',
        '#title' => t('Example No'),
        '#markup' => $example_data->number
    );
    $form['example_caption'] = array(
        '#type' => 'textfield',
        '#title' => t('Caption'),
        '#size' => 40,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => $example_data->caption
    );
    $form['example_warning'] = array(
        '#type' => 'item',
        '#title' => t('You should upload all the files (main or source files, result files, executable file if any)'),
        '#prefix' => '<div style="color:red">',
        '#suffix' => '</div>'
    );
    $form['sourcefile'] = array(
        '#type' => 'fieldset',
        '#title' => t('Main or Source Files'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE
    );
    if ($source_file)
      {
        $form['sourcefile']['cur_source'] = array(
            '#type' => 'item',
            '#title' => t('Existing Main or Source File'),
            '#markup' => $source_file
        );
        $form['sourcefile']['cur_source_checkbox'] = array(
            '#type' => 'checkbox',
            '#title' => t('Delete Existing Main or Source File'),
            '#description' => 'Check to delete the existing Main or Source file.'
        );
        $form['sourcefile']['sourcefile1'] = array(
            '#type' => 'file',
            '#title' => t('Upload New Main or Source File'),
            '#size' => 48,
            '#description' => t("Upload new Main or Source file above if you want to replace the existing file. Leave blank if you want to keep using the existing file. <br />") . t('Allowed file extensions : ') . variable_get('textbook_companion_source_extensions', '')
        );
        $form['sourcefile']['cur_source_file_id'] = array(
            '#type' => 'hidden',
            '#value' => $source_file_id
        );
      }
    else
      {
        $form['sourcefile']['sourcefile1'] = array(
            '#type' => 'file',
            '#title' => t('Upload New Main or Source File'),
            '#size' => 48,
            '#description' => t('Allowed file extensions : ') . variable_get('textbook_companion_source_extensions', '')
        );
      }
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'item',
        '#markup' => l(t('Cancel'), 'textbook-companion/code')
    );
    return $form;
  }
function upload_examples_edit_form_validate($form, &$form_state)
  {
    if (!check_name($form_state['values']['example_caption']))
        form_set_error('example_caption', t('Example Caption can contain only alphabets, numbers and spaces.'));
    if (isset($_FILES['files']))
      {
        /* check for valid filename extensions */
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
          {
            if ($file_name)
              {
                /* checking file type */
                if (strstr($file_form_name, 'source'))
                    $file_type = 'S';
                else if (strstr($file_form_name, 'result'))
                    $file_type = 'R';
                else if (strstr($file_form_name, 'xcos'))
                    $file_type = 'X';
                else
                    $file_type = 'U';
                $allowed_extensions_str = '';
                switch ($file_type)
                {
                    case 'S':
                        $allowed_extensions_str = variable_get('textbook_companion_source_extensions', '');
                        break;
                    case 'R':
                        $allowed_extensions_str = variable_get('textbook_companion_result_extensions', '');
                        break;
                    case 'X':
                        $allowed_extensions_str = variable_get('textbook_companion_xcos_extensions', '');
                        break;
                }
                $allowed_extensions = explode(',', $allowed_extensions_str);
                $temp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
                $temp_extension = end($temp_ext);
                if (!in_array($temp_extension, $allowed_extensions))
                    form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
                if ($_FILES['files']['size'][$file_form_name] <= 0)
                    form_set_error($file_form_name, t('File size cannot be zero.'));
                /* check if valid file name */
                if (!textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name]))
                    form_set_error($file_form_name, t('Invalid file name specified. Only alphabets, numbers and underscore is allowed as a valid filename.'));
              }
          }
      }
    /* add javascript again for automatic book title, check if example uploaded, dependency selection effects */
    /*$chapter_name_js = " $(document).ready(function() {
    $('#edit-number').change(function() {
    $.get('" . base_path() . "textbook-companion/ajax/chapter-title/' + $('#edit-number').val() + '/' + " . $row->pre_id . ", function(data) {
    $('#edit-name').val(data);
    });
    });
    $('#edit-example-number').change(function() {
    $.get('" . base_path() . "textbook-companion/ajax/example-exists/' + $('#edit-number').val() + '/' + $('#edit-example-number').val(), function(data) {
    if (data) {
    alert(data);
    }
    });
    });
    $('#edit-existing-depfile-dep-book-title').change(function() {
    
    var dep_selected = ''; 
    /* showing and hiding relevant files */
    /*$('.form-checkboxes .option').hide();
    $('.form-checkboxes .option').each(function(index) {
    var activeClass = $('#edit-existing-depfile-dep-book-title').val();
    if ($(this).children().hasClass(activeClass)) {
    $(this).show();
    }
    if ($(this).children().attr('checked') == true) {
    dep_selected += $(this).children().next().text() + '<br />';
    }
    });
    /* showing list of already existing dependencies */
    /* $('#existing_depfile_selected').html(dep_selected);
    
    });
    
    $('.form-checkboxes .option').change(function() {
    $('#edit-existing-depfile-dep-book-title').trigger('change');
    });
    $('#edit-existing-depfile-dep-book-title').trigger('change');
    });";
    drupal_add_js($chapter_name_js, 'inline', 'header');*/
  }
function upload_examples_edit_form_submit($form, &$form_state)
  {
    global $user;
    $example_id = arg(3);
    /* get example details */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $example_q = $query->execute();
    $example_data = $example_q->fetchObject();
    if (!$example_q)
      {
        drupal_set_message(t("Invalid example selected."), 'error');
        drupal_goto('');
        return;
      }
    if ($example_data->approval_status != 0)
      {
        drupal_set_message(t("You cannot edit an example after it has been approved or dis-approved. Please contact site administrator if you want to edit this example."), 'error');
        drupal_goto('');
        return;
      }
    /* get chapter details */
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data)
      {
        drupal_set_message(t("Invalid chapter selected."), 'error');
        drupal_goto('');
        return;
      }
    /* get preference details */
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        drupal_set_message(t("Invalid book selected."), 'error');
        drupal_goto('');
        return;
      }
    if ($preference_data->approval_status != 1)
      {
        drupal_set_message(t("Cannot edit example. Either the book proposal has not been approved or it has been rejected."), 'error');
        drupal_goto('');
        return;
      }
    /* get proposal details */
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
        drupal_set_message(t("Invalid proposal selected."), 'error');
        drupal_goto('');
        return;
      }
    if ($proposal_data->uid != $user->uid)
      {
        drupal_set_message(t("You do not have permissions to edit this example."), 'error');
        drupal_goto('');
        return;
      }
    /* creating directories */
    $root_path = textbook_companion_path();
    $dest_path = $preference_data->directory_name . '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $dest_path .= 'CH' . $chapter_data->number . '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $dest_path .= 'EX' . $example_data->number . '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $filepath = 'CH' . $chapter_data->number . '/' . 'EX' . $example_data->number . '/';
    /* updating example caption */
    /*db_query("UPDATE {textbook_companion_example} SET caption = '%s' WHERE id = %d", $form_state['values']['example_caption'], $example_id);*/
    $query = db_update('textbook_companion_example');
    $query->fields(array(
        'caption' => $form_state['values']['example_caption']
    ));
    $query->condition('id', $example_id);
    $num_updated = $query->execute();
    /* handle source file */
    if (isset($form_state['values']['cur_source_file_id']))
      {
        $cur_file_id = $form_state['values']['cur_source_file_id'];
      }
    else
      {
        $cur_file_id = isset($form_state['values']['cur_source_file_id']);
      }
    //var_dump($cur_file_id);die;
    if ($cur_file_id > 0)
      {
        /*$file_q = db_query("SELECT * FROM  {textbook_companion_example_files} WHERE id = %d AND example_id = %d", $cur_file_id, $example_data->id);
        $file_data = db_fetch_object($file_q);*/
        //var_dump($cur_file_id. $example_data->id);die;
        $query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('id', $cur_file_id);
        $query->condition('example_id', $example_data->id);
        $result = $query->execute();
        $file_data = $result->fetchObject();
        if (!$file_data)
          {
            drupal_set_message("Error deleting example source file. File not present in database.", 'error');
            return;
          }
        if (($form_state['values']['cur_source_checkbox'] == 1) && (!$_FILES['files']['name']['sourcefile1']))
          {
            if (!delete_file($cur_file_id))
              {
                drupal_set_message("Error deleting example source file.", 'error');
                return;
              }
          }
      }
    if ($_FILES['files']['name']['sourcefile1'])
      {
        if ($cur_file_id > 0)
          {
            if (!delete_file($cur_file_id))
              {
                drupal_set_message("Error removing previous example source file.", 'error');
                return;
              }
          }
        if (file_exists($root_path . $dest_path . $_FILES['files']['name']['sourcefile1']))
          {
            drupal_set_message(t("Error uploading source file. File !filename already exists.", array(
                '!filename' => $_FILES['files']['name']['sourcefile1']
            )), 'error');
            return;
          }
        /* uploading file */
        if (move_uploaded_file($_FILES['files']['tmp_name']['sourcefile1'], $root_path . $dest_path . $_FILES['files']['name']['sourcefile1']))
          {
            /* for uploaded files making an entry in the database */
            /*db_query("INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath, filemime, filesize, filetype, timestamp)
            VALUES (%d, '%s', '%s', '%s', %d, '%s', %d)",
            $example_data->id,
            $_FILES['files']['name']['sourcefile1'],
            $dest_path . $_FILES['files']['name']['sourcefile1'],
            $_FILES['files']['type']['sourcefile1'],
            $_FILES['files']['size']['sourcefile1'],
            'S',
            time()
            );*/
            $query = "INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath, filemime, filesize, filetype, 		timestamp) VALUES (:example_id, :filename, :filepath, :filemime, :filesize, :filetype,:timestamp)";
            $args = array(
                ":example_id" => $example_data->id,
                ":filename" => $_FILES['files']['name']['sourcefile1'],
                ":filepath" => $filepath . $_FILES['files']['name']['sourcefile1'],
                ":filemime" => 'application/dwxml',
                ":filesize" => $_FILES['files']['size']['sourcefile1'],
                ":filetype" => 'S',
                ":timestamp" => time()
            );
            $result = db_query($query, $args, array(
                'return' => Database::RETURN_INSERT_ID
            ));
            drupal_set_message($_FILES['files']['name']['sourcefile1'] . ' uploaded successfully.', 'status');
          }
        else
          {
            drupal_set_message('Error uploading file : ' . $dest_path . '/' . $_FILES['files']['name']['sourcefile1'], 'error');
          }
      }
    /* sending email */
    $email_to = $user->mail;
    $from = variable_get('textbook_companion_from_email', '');
    $bcc = variable_get('textbook_companion_emails', '');
    $cc = variable_get('textbook_companion_cc_emails', '');
    $param['example_updated']['example_id'] = $example_id;
    $param['example_updated']['user_id'] = $user->uid;
    $param['example_updated']['headers'] = array(
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc
    );
    if (!drupal_mail('textbook_companion', 'example_updated', $email_to, language_default(), $param, $from, TRUE))
        drupal_set_message('Error sending email message.', 'error');
    drupal_set_message(t("Example successfully udpated."), 'status');
  }
/******************************************************************************/
/**************************** EDIT CHAPTER TITLE ******************************/
/******************************************************************************/
function edit_chapter_title_form($form, $form_state)
  {
    global $user;
    /************************ start approve book details ************************/
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
        drupal_set_message("Please submit a " . l('proposal', 'textbook-companion/proposal') . ".", 'error');
        drupal_goto('textbook-companion/code');
      }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4)
      {
        switch ($proposal_data->proposal_status)
        {
            case 0:
                drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
                drupal_goto('textbook-companion/code');
                return;
                break;
            case 2:
                drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');
                drupal_goto('textbook-companion/code');
                return;
                break;
            case 3:
                drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
                drupal_goto('textbook-companion/code');
                return;
                break;
            default:
                drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
                drupal_goto('textbook-companion/code');
                return;
                break;
        }
      }
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        drupal_set_message(t('Invalid Book Preference status. Please contact site administrator for further information.'), 'error');
        drupal_goto('textbook-companion/code');
        return;
      }
    /************************ end approve book details **************************/
    $chapter_id = arg(4);
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d", $chapter_id, $preference_data->id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data)
      {
        drupal_set_message(t('Invalid chapter.'), 'error');
        drupal_goto('textbook-companion/code');
        return;
      }
    $form['#redirect'] = 'textbook-companion/code';
    $form['book_details']['book'] = array(
        '#type' => 'item',
        '#markup' => $preference_data->book,
        '#title' => t('Title of the Book')
    );
    $form['contributor_name'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->full_name,
        '#title' => t('Contributor Name')
    );
    $form['number'] = array(
        '#type' => 'item',
        '#title' => t('Chapter No'),
        '#markup' => $chapter_data->number
    );
    $form['chapter_title'] = array(
        '#type' => 'textfield',
        '#title' => t('Title of the Chapter'),
        '#size' => 40,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => $chapter_data->name
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'markup',
        '#value' => l(t('Cancel'), 'textbook_companion/code')
    );
    return $form;
  }
function edit_chapter_title_form_validate($form, &$form_state)
  {
    if (!check_name($form_state['values']['chapter_title']))
        form_set_error('chapter_title', t('Title of the Chapter can contain only alphabets, numbers and spaces.'));
  }
function edit_chapter_title_form_submit($form, &$form_state)
  {
    global $user;
    /************************ start approve book details ************************/
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('uid', $user->uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
        drupal_set_message("Please submit a " . l('proposal', 'textbook-companion/proposal') . ".", 'error');
        drupal_goto('textbook-companion/code');
      }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4)
      {
        switch ($proposal_data->proposal_status)
        {
            case 0:
                drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
                drupal_goto('textbook-companion/code');
                return;
                break;
            case 2:
                drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');
                drupal_goto('textbook-companion/code');
                return;
                break;
            case 3:
                drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
                drupal_goto('textbook-companion/code');
                return;
                break;
            default:
                drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
                drupal_goto('textbook-companion/code');
                return;
                break;
        }
      }
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        drupal_set_message(t('Invalid Book Preference status. Please contact site administrator for further information.'), 'error');
        drupal_goto('textbook-companion/code');
        return;
      }
    /************************ end approve book details **************************/
    $chapter_id = arg(4);
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d AND preference_id = %d", $chapter_id, $preference_data->id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data)
      {
        drupal_set_message(t('Invalid chapter.'), 'error');
        drupal_goto('textbookcompanion/code');
        return;
      }
    /*db_query("UPDATE {textbook_companion_chapter} SET name = '%s' WHERE id = %d", $form_state['values']['chapter_title'], $chapter_id);*/
    $query = db_update('textbook_companion_chapter');
    $query->fields(array(
        'name' => $form_state['values']['chapter_title']
    ));
    $query->condition('id', $chapter_id);
    $num_updated = $query->execute();
    drupal_set_message(t('Title of the Chapter updated.'), 'status');
  }
/******************************************************************************/
/************************** GENERAL FUNCTIONS *********************************/
/******************************************************************************/
function _list_of_book_titles()
  {
    $book_titles = array(
        '0' => 'Please select...'
    );
    /*$book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 OR approval_status = 3 ORDER BY book ASC");*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $or = db_or();
    $or->condition('approval_status', 1);
    $or->condition('approval_status', 3);
    $query->condition($or);
    $query->orderBy('book', 'ASC');
    $book_titles_q = $query->execute();
    while ($book_titles_data = $book_titles_q->fetchObject())
      {
        $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
      }
    return $book_titles;
  }
