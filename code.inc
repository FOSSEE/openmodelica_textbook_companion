<?php
// $Id$
function upload_examples()
  {
    return drupal_get_form('upload_examples_form');
  }
function upload_examples_form($form, &$form_state)
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
        drupal_goto('');
      }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4)
      {
        switch ($proposal_data->proposal_status)
        {
            case 0:
                drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
                drupal_goto('');
                return;
                break;
            case 2:
                drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'error');
                drupal_goto('');
                return;
                break;
            case 3:
                drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
                drupal_goto('');
                return;
                break;
case 5:
      drupal_set_message(t('You have submitted your all codes.'), 'status');
      drupal_goto('');
      return;
            default:
                drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
                drupal_goto('');
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
        drupal_goto('');
        return;
      }
    $form['#attributes'] = array(
        'enctype' => "multipart/form-data"
    );
$form['book_details']['pref_id'] = array(
    '#type' => 'hidden',
    '#value' => $preference_data->id,    
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
    $options = array(
        '' => '(Select)'
    );
    for ($i = 1; $i <= 100; $i++)
      {
        $options[$i] = $i;
      }
    $form['number'] = array(
    '#type' => 'select',
    '#title' => t('Chapter No'),
    '#options' => $options,   
    '#multiple' => FALSE,
    '#size' => 1,
    '#required' => TRUE,  
    '#ajax' => array(
        'callback' => 'ajax_chapter_name_callback',
        ),
    );
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Title of the Chapter'),
    '#size' => 40,
    '#maxlength' => 255,
    '#required' => TRUE,    
    '#prefix' => '<div id="ajax-chapter-name-replace">',
    '#suffix' => '</div>',    
  );
    $form['example_number'] = array(
        '#type' => 'textfield',
        '#title' => t('Example No'),
        '#size' => 5,
        '#maxlength' => 10,
        '#description' => t("Example number should be separated by dots only.<br />Example: 1.1.a &nbsp;or&nbsp; 1.1.1"),
        '#required' => TRUE
    );
    $form['example_caption'] = array(
        '#type' => 'textfield',
        '#title' => t('Caption'),
        '#size' => 40,
        '#maxlength' => 255,
        '#description' => t('Example caption should contain only alphabets, numbers and spaces.'),
        '#required' => TRUE
    );
    $form['example_warning'] = array(
        '#type' => 'item',
        '#title' => t('You should upload all the files as extention ".'). variable_get('textbook_companion_source_extensions', '') . t('" main or source files, result files, executable file if any): '),
        '#prefix' => '<div style="color:red">',
        '#suffix' => '</div>'
    );
    $form['sourcefile'] = array(
        '#type' => 'fieldset',
        '#title' => t('Main or Source Files'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE
    );
    $form['sourcefile']['sourcefile1'] = array(
        '#type' => 'file',
        '#title' => t('Upload main or source file'),
        '#size' => 48,
        '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . variable_get('textbook_companion_source_extensions', '') . '</span>'
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'markup',
        '#value' => l(t('Cancel'), 'textbook-companion/code')
    );
    return $form;
  }
function upload_examples_form_validate($form, &$form_state)
  {
    if (!check_name($form_state['values']['name']))
        form_set_error('name', t('Title of the Chapter can contain only alphabets, numbers and spaces.'));
    if (!check_name($form_state['values']['example_caption']))
        form_set_error('example_caption', t('Example Caption can contain only alphabets, numbers and spaces.'));
    if (!check_chapter_number($form_state['values']['example_number']))
        form_set_error('example_number', t('Invalid Example Number. Example Number can contain only alphabets and numbers sepereated by dot.'));
    if (isset($_FILES['files']))
      {
        /* check if atleast one source or result file is uploaded */
        if (!($_FILES['files']['name']['sourcefile1']))
            form_set_error('sourcefile1', t('Please upload source file.'));
        /* check for valid filename extensions */
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
          {
            if ($file_name)
              {
                $allowed_extensions_str = variable_get('textbook_companion_source_extensions', '');
                $allowed_extensions = explode(',', $allowed_extensions_str);
                $temp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
                $temp_extension = end($temp_ext);
                //$temp_extension = substr($_FILES['files']['name'][$file_form_name], strripos($_FILES['files']['name'][$file_form_name], '.')); // get file name
                //var_dump($temp_extension); die;
                if (!in_array($temp_extension, $allowed_extensions))
                    form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
                if ($_FILES['files']['size'][$file_form_name] <= 0)
                    form_set_error($file_form_name, t('File size cannot be zero.'));
                /* check if valid file name
                if (!textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name]))
                form_set_error($file_form_name, t('Invalid file name specified. Only alphabets, numbers and underscore is allowed as a valid filename.'));*/
              }
          }
      }
  }
function upload_examples_form_submit($form, &$form_state)
  {
    global $user;
    $root_path = textbook_companion_path();
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
        drupal_goto('');
      }
    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4)
      {
        switch ($proposal_data->proposal_status)
        {
            case 0:
                drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
                drupal_goto('');
                return;
                break;
            case 2:
                drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'error');
                drupal_goto('');
                return;
                break;
            case 3:
                drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook-companion/proposal') . '.'), 'status');
                drupal_goto('');
                return;
                break;
case 5:
      drupal_set_message(t('You have submmited your all codes'), 'status');
      drupal_goto('');
      return;
      break;
            default:
                drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
                drupal_goto('');
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
        drupal_goto('');
        return;
      }
    /************************ end approve book details **************************/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $result = $query->execute()->rowCount();
    if ($result > 1)
      {
        drupal_set_message(t('You cannot upload your code. This name of book directory alrady preasent in directory folder, please contact to administrator.'), 'error');
        return;
      }
    $proposal_directory = $preference_data->directory_name;
    $dest_path = $proposal_directory . '/';
    if (!is_dir($root_path . $dest_path)){   
        if(!mkdir($root_path . $dest_path))
        {
        drupal_set_message(t('You cannot upload your code. Error in creating directory'), 'error');
        }
     }   
    /* inserting chapter details */
    $chapter_id = 0;
    /*$chapter_result = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d AND number = %d", $preference_id, $form_state['values']['number']);*/
    $preference_id = $preference_data->id;
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_id);
    $query->condition('number', $form_state['values']['number']);
    $chapter_result = $query->execute();
    if (!$chapter_row = $chapter_result->fetchObject())
      {
        /*db_query("INSERT INTO {textbook_companion_chapter} (preference_id, number, name) VALUES (%d, '%s', '%s')",
        $preference_id,
        $form_state['values']['number'],
        $form_state['values']['name']
        );
        $chapter_id = db_last_insert_id('textbook_companion_chapter', 'id'); */
        $query = "INSERT INTO {textbook_companion_chapter} (preference_id, number, name) VALUES(:preference_id, :number, :name)";
        $args = array(
            ":preference_id" => $preference_id,
            ":number" => $form_state['values']['number'],
            ":name" => $form_state['values']['name']
        );
        $result = db_query($query, $args, array(
            'return' => Database::RETURN_INSERT_ID
        ));
        $chapter_id = $result;
      }
    else
      {
        $chapter_id = $chapter_row->id;
        /*db_query("UPDATE {textbook_companion_chapter} SET name = '%s' WHERE id = %d", $form_state['values']['name'], $chapter_id);*/
        $query = db_update('textbook_companion_chapter');
        $query->fields(array(
            'name' => $form_state['values']['name']
        ));
        $query->condition('id', $chapter_id);
        $num_updated = $query->execute();
      }
    /*  get example details - dont allow if already example present */
    /*$cur_example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND number = '%s'", $chapter_id, $form_state['values']['example_number']);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('number', $form_state['values']['example_number']);
    $cur_example_q = $query->execute();
    if ($cur_example_d = $cur_example_q->fetchObject())
      {
        if ($cur_example_d->approval_status == 1)
          {
            drupal_set_message(t("Example already approved. Cannot overwrite it."), 'error');
            drupal_goto('textbook-companion/code');
            return;
          }
        else if ($cur_example_d->approval_status == 0)
          {
            drupal_set_message(t("Example is under pending review. Delete the example and reupload it."), 'error');
            drupal_goto('textbook-companion/code');
            return;
          }
        else
          {
            drupal_set_message(t("Error uploading example. Please contact administrator."), 'error');
            drupal_goto('textbook-companion/code');
            return;
          }
      }
    /* creating directories */
    $dest_path .= 'CH' . $form_state['values']['number'] . '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $dest_path .= 'EX' . $form_state['values']['example_number'] . '/';
    if (!is_dir($root_path . $dest_path))
        mkdir($root_path . $dest_path);
    $filepath = 'CH' . $form_state['values']['number'] . '/' . 'EX' . $form_state['values']['example_number'] . '/';
    /* creating example database entry */
    /*db_query("INSERT INTO {textbook_companion_example} (chapter_id, number, caption, approval_status, timestamp) VALUES (%d, '%s', '%s', %d, %d)",
    $chapter_id,
    $form_state['values']['example_number'],
    $form_state['values']['example_caption'],
    0,
    time()
    );
    $example_id = db_last_insert_id('textbook_companion_example', 'id');*/
    $query = "INSERT INTO {textbook_companion_example} (chapter_id, number, caption, approval_date, approval_status, timestamp) VALUES (:chapter_id, :number, :caption, :approval_date,:approval_status, :timestamp)";
    $args = array(
        ":chapter_id" => $chapter_id,
        ":number" => $form_state['values']['example_number'],
        ":caption" => $form_state['values']['example_caption'],
        ":approval_date" => time(),
        ":approval_status" => 0,
        ":timestamp" => time()
    );
    $result = db_query($query, $args, array(
        'return' => Database::RETURN_INSERT_ID
    ));
    $example_id = $result;
    /* linking existing dependencies */
    /* foreach ($form_state['values']['existing_depfile']['dep_chapter_example_files'] as $row)
    {
    if ($row > 0)
    {
    /* insterting into database */
    /*db_query("INSERT INTO {textbook_companion_example_dependency} (example_id, dependency_id, approval_status, timestamp)
    VALUES (%d, %d, %d, %d)",
    $example_id,
    $row,
    0,
    time()
    );*/
    /*$query = "INSERT INTO {textbook_companion_example_dependency} (example_id, dependency_id, approval_status, timestamp)
    VALUES  (:example_id, :dependency_id, :approval_status, :timestamp)";
    $args = array(
    ":example_id"=>$example_id, 
    ":dependency_id"=>$row,
    ":approval_status"=> 0,
    ":timestamp"=>time(),
    );
    $result = db_query($query, $args, array('return' => Database::RETURN_INSERT_ID));
    }
    }*/
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
      {
        if ($file_name)
          {
            /* checking file type */
            $file_type = 'S';
            if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
              {
                drupal_set_message(t("Error uploading file. File !filename already exists.", array(
                    '!filename' => $_FILES['files']['name'][$file_form_name]
                )), 'error');
                return;
              }
            /* uploading file */
            if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
              {
                /* for uploaded files making an entry in the database */
                /*db_query("INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath, filemime, filesize, filetype, timestamp)
                VALUES (%d, '%s', '%s', '%s', %d, '%s', %d)",
                $example_id,
                $_FILES['files']['name'][$file_form_name],
                $dest_path . $_FILES['files']['name'][$file_form_name],
                $_FILES['files']['type'][$file_form_name],
                $_FILES['files']['size'][$file_form_name],
                $file_type,
                time()
                );*/
                $query = "INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath,filemime, filesize, filetype, timestamp)
          VALUES (:example_id, :filename ,:filepath,:filemime, :filesize, :filetype, :timestamp)";
                $args = array(
                    ":example_id" => $example_id,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $filepath . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => 'application/zip',
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time()
                );
                $result = db_query($query, $args, array(
                    'return' => Database::RETURN_INSERT_ID
                ));
                drupal_set_message($file_name . ' uploaded successfully.', 'status');
              }
            else
              {
                drupal_set_message('Error uploading file : ' . $dest_path . '/' . $file_name, 'error');
              }
          }
      }
  
    drupal_set_message('Example uploaded successfully.', 'status');
	/* sending email */
	$email_to = $user->mail;
	$from = variable_get('textbook_companion_from_email', '');
	$bcc = variable_get('textbook_companion_emails', '');
	$cc = variable_get('textbook_companion_cc_emails', '');
	$params['example_uploaded']['example_id'] = $example_id;
	$params['example_uploaded']['user_id'] = $user->uid;
	$params['example_uploaded']['headers'] = array(
		'From' => $from,
		'MIME-Version' => '1.0',
		'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
		'Content-Transfer-Encoding' => '8Bit',
		'X-Mailer' => 'Drupal',
		'Cc' => $cc,
		'Bcc' => $bcc
	);
	if (!drupal_mail('textbook_companion', 'example_uploaded', $email_to, language_default(), $params, $from, TRUE))
		drupal_set_message('Error sending email message.', 'error');
	drupal_goto('textbook-companion/code');
    
    
  }
/******************************************************************************/
/***************************** DELETE EXAMPLE *********************************/
/******************************************************************************/
function _upload_examples_delete()
  {
    global $user;
    $root_path = textbook_companion_path();
    $example_id = arg(3);
    //var_dump($example_id);die;
    /* check example */
    /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
    $example_data = db_fetch_object($example_q);*/
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('id', $example_id);
    $query->range(0, 1);
    $result = $query->execute();
    $example_data = $result->fetchObject();
    if (!$example_data)
      {
        drupal_set_message('Invalid example.', 'error');
        drupal_goto('textbook-companion/code');
        return;
      }
    if ($example_data->approval_status != 0)
      {
        drupal_set_message('You cannnot delete an example after it has been approved. Please contact site administrator if you want to delete this example.', 'error');
        drupal_goto('textbook-companion/code');
        return;
      }
    /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d LIMIT 1", $example_data->chapter_id);
    $chapter_data = db_fetch_object($chapter_q);*/
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $example_data->chapter_id);
    $query->range(0, 1);
    $result = $query->execute();
    $chapter_data = $result->fetchObject();
    if (!$chapter_data)
      {
        drupal_set_message('You do not have permission to delete this example.', 'error');
        drupal_goto('textbook-companion/code');
        return;
      }
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d LIMIT 1", $chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $chapter_data->preference_id);
    $query->range(0, 1);
    $result = $query->execute();
    $preference_data = $result->fetchObject();
    if (!$preference_data)
      {
        drupal_set_message('You do not have permission to delete this example.', 'error');
        drupal_goto('textbook-companion/code');
        return;
      }
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d AND uid = %d LIMIT 1", $preference_data->proposal_id, $user->uid);
    $proposal_data = db_fetch_object($proposal_q);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $preference_data->proposal_id);
    $query->condition('uid', $user->uid);
    $query->range(0, 1);
    $result = $query->execute();
    $proposal_data = $result->fetchObject();
    if (!$proposal_data)
      {
        drupal_set_message('You do not have permission to delete this example.', 'error');
        drupal_goto('textbook-companion/code');
        return;
      }
    /* deleting example files */
    if (delete_example($example_data->id))
      {
        drupal_set_message('Example deleted.', 'status');
        /* sending email */
      
        
        $email_to = $user->mail;
		$from = variable_get('textbook_companion_from_email', '');
		$bcc = variable_get('textbook_companion_emails', '');
		$cc = variable_get('textbook_companion_cc_emails', '');
		$params['example_deleted_user']['book_title'] = $preference_data->book;
        $params['example_deleted_user']['chapter_title'] = $chapter_data->name;
        $params['example_deleted_user']['example_number'] = $example_data->number;
        $params['example_deleted_user']['example_caption'] = $example_data->caption;
        $params['example_deleted_user']['user_id'] = $user->uid;
		$params['example_deleted_user']['headers'] = array(
		'From' => $from,
		'MIME-Version' => '1.0',
		'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
		'Content-Transfer-Encoding' => '8Bit',
		'X-Mailer' => 'Drupal',
		'Cc' => $cc,
		'Bcc' => $bcc
	);
        if (!drupal_mail('textbook_companion', 'example_deleted_user', $email_to, language_default(), $params, $from, TRUE))
            drupal_set_message('Error sending email message.', 'error');
      }
    else
      {
        drupal_set_message('Error deleting example.', 'status');
      }
    drupal_goto('textbook-companion/code');
    return;
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
function _list_of_book_dependency_files()
  {
    $book_dependency_files = array();
    $book_dependency_files_class = array();
    /*$book_dependency_files_q = db_query("SELECT * FROM {textbook_companion_dependency_files} ORDER BY filename ASC");*/
    $query = db_select('textbook_companion_dependency_files');
    $query->fields('textbook_companion_dependency_files');
    $query->orderBy('filename', 'ASC');
    $book_dependency_files_q = $query->execute();
    while ($book_dependency_files_data = $book_dependency_files_q->fetchObject())
      {
        $temp_caption = '';
        if ($book_dependency_files_data->caption)
            $temp_caption .= ' (' . $book_dependency_files_data->caption . ')';
        $book_dependency_files[$book_dependency_files_data->id] = l($book_dependency_files_data->filename . $temp_caption, 'download/dependency/' . $book_dependency_files_data->id, array(
            'attributes' => array(
                'class' => $book_dependency_files_data->preference_id
            )
        ));
        $book_dependency_files_class[$book_dependency_files_data->id] = $book_dependency_files_data->preference_id;
      }
    return array(
        $book_dependency_files,
        $book_dependency_files_class
    );
  }

function ajax_chapter_name_callback($form, $form_state){

  $pref_id = $form_state['values']['pref_id'];
  $chapter_number = $form_state['values']['number'];
  $query = "SELECT * FROM `textbook_companion_chapter` WHERE `preference_id` = :pref_id AND `number` = :number";
      $arg = array(':pref_id'=> $pref_id,':number' => $chapter_number);
      $chapter_name_data = db_query($query, $arg);
      
      if ($chapter_name_data->rowCount() > 0) { 

      //$form['name']['#default_value'] = $chapter_name_data->fetchObject()->name; 
      $form['name']['#value'] = $chapter_name_data->fetchObject()->name;    
      $form['name']['#readonly'] = TRUE;
      $form['name']['#attributes']['readonly'] = 'readonly';
      $commands[] = ajax_command_replace("#ajax-chapter-name-replace", drupal_render($form['name']));      
      
      }else{
      $form['name']['#value'] = ' ';
      $form['name']['#readonly'] = FALSE;
      //$form['name']['#attributes']['readonly'] = 'readonly';
      $commands[] = ajax_command_replace("#ajax-chapter-name-replace", drupal_render($form['name']));
    
    }
      return array('#type' => 'ajax', '#commands' => $commands);

}
