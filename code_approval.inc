<?php
// $Id$
function code_approval()
{
    /* get a list of unapproved chapters */
    $query = db_select('textbook_companion_example', 'e');
    $query->fields('c', array(
        'id',
        'number',
        'name',
        'preference_id'
    ));
    $query->addField('c', 'id', 'c_id');
    $query->addField('c', 'number', 'c_number');
    $query->addField('c', 'name', 'c_name');
    $query->addField('c', 'preference_id', 'c_preference_id');
    $query->innerJoin('textbook_companion_chapter', 'c', 'c.id = e.chapter_id');
    $query->condition('e.approval_status', 0);
    $pending_chapter_q = $query->execute();
    if (!$pending_chapter_q) {
        drupal_set_message(t('There are no pending code approvals.'), 'status');
        return '';
    }
    $rows = array();
    while ($row = $pending_chapter_q->fetchObject()) {
        /* get preference data */
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $row->c_preference_id);
        $result = $query->execute();
        $preference_data = $result->fetchObject();
        /* get proposal data */
        $query = db_select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();
        /* setting table row information */
        $rows[] = array(
            $preference_data->book,
            $row->c_number,
            $row->c_name,
            $proposal_data->full_name,
            l('Edit', 'textbook-companion/code-approval/approve/' . $row->c_id)
        );
    }
    /* check if there are any pending proposals */
    if (!$rows) {
        drupal_set_message(t('There are no pending proposals'), 'status');
        return '';
    }
    $header = array(
        'Title of the Book',
        'Chapter Number',
        'Title of the Chapter',
        'Contributor Name',
        'Actions'
    );
    $output = theme('table', array(
        'header' => $header,
        'rows' => $rows
    ));
    return $output;
}
function code_approval_form($form, &$form_state)
{
    /* get a list of unapproved chapters */
    $chapter_id = arg(3);
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('id', $chapter_id);
    $pending_chapter_q = $query->execute();
    if ($pending_chapter_data = $pending_chapter_q->fetchObject()) {
        /* get preference data */
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $pending_chapter_data->preference_id);
        $result = $query->execute();
        $preference_data = $result->fetchObject();
        /* get proposal data */
        $query = db_select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();
    } else {
        drupal_set_message(t('Invalid chapter selected.'), 'error');
        drupal_goto('textbook-companion/code-approval');
        return;
    }
    $form['#tree'] = TRUE;
    $form['contributor'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->full_name,
        '#title' => t('Contributor Name')
    );
    $form['book_details']['book'] = array(
        '#type' => 'item',
        '#markup' => $preference_data->book,
        '#title' => t('Title of the Book')
    );
    $form['book_details']['number'] = array(
        '#type' => 'item',
        '#markup' => $pending_chapter_data->number,
        '#title' => t('Chapter Number')
    );
    $form['book_details']['name'] = array(
        '#type' => 'item',
        '#markup' => $pending_chapter_data->name,
        '#title' => t('Title of the Chapter')
    );
    $form['book_details']['back_to_list'] = array(
        '#type' => 'item',
        '#markup' => l('Back to Code Approval List', 'textbook-companion/code-approval')
    );
    /* get example data */
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $example_q = $query->execute();
    while ($example_data = $example_q->fetchObject()) {
        $form['example_details'][$example_data->id] = array(
            '#type' => 'fieldset',
            '#collapsible' => FALSE,
            '#collapsed' => TRUE
        );
        $form['example_details'][$example_data->id]['example_number'] = array(
            '#type' => 'item',
            '#markup' => $example_data->number,
            '#title' => t('Example Number'),
        );
        $form['example_details'][$example_data->id]['example_caption'] = array(
            '#type' => 'item',
            '#markup' => $example_data->caption,
            '#title' => t('Example Caption')
        );
        $form['example_details'][$example_data->id]['download'] = array(
            '#type' => 'markup',
            '#markup' => l('Download Example', 'textbook-companion/download/example/' . $example_data->id)
        );
        $form['example_details'][$example_data->id]['approved'] = array(
            '#type' => 'radios',
            '#options' => array(
                'Approved',
                'Dis-approved'
            )
        );
        $form['example_details'][$example_data->id]['message'] = array(
            '#type' => 'textfield',
            '#title' => t('Reason for dis-approval')
        );
        $form['example_details'][$example_data->id]['example_id'] = array(
            '#type' => 'hidden',
            '#value' => $example_data->id
        );
        $form['example_details'][$example_data->id]['example_number_hidden'] = array(
            '#type' => 'hidden',
            '#value' => $example_data->number
        );
    }
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    return $form;
}
function code_approval_form_validate($form, &$form_state){
    foreach ($form_state['values']['example_details'] as $ex_id => $ex_data) {
    if ($ex_data['approved'] == "1"){
        if($ex_data['message'] == NULL || $ex_data['message'] == '')
        form_set_error($ex_id . '][message', t('Enter reason for disapproval for experiment no: ' . $ex_data['example_number_hidden']));
    }
    }
}
function code_approval_form_submit($form, &$form_state)
{
    global $user;
    foreach ($form_state['values']['example_details'] as $ex_id => $ex_data) {
        $query = db_select('textbook_companion_example');
        $query->fields('textbook_companion_example');
        $query->condition('id', $ex_data['example_id']);
        $query->range(0, 1);
        $result = $query->execute();
        $example_data = $result->fetchObject();
        $query = db_select('textbook_companion_chapter');
        $query->fields('textbook_companion_chapter');
        $query->condition('id', $example_data->chapter_id);
        $query->range(0, 1);
        $result = $query->execute();
        $chapter_data = $result->fetchObject();
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $chapter_data->preference_id);
        $query->range(0, 1);
        $result = $query->execute();
        $preference_data = $result->fetchObject();
        $query = db_select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $preference_data->proposal_id);
        $query->range(0, 1);
        $result = $query->execute();
        $proposal_data = $result->fetchObject();
        $user_data = user_load($proposal_data->uid);
        del_book_pdf($preference_data->id);
        if ($ex_data['approved'] == "0") {
            $query = db_update('textbook_companion_example');
            $query->fields(array(
                'approval_status' => 1,
                'approver_uid' => $user->uid,
                'approval_date' => time()
            ));
            $query->condition('id', $ex_data['example_id']);
            $num_updated = $query->execute();
            /* sending email */
            $email_to = $user_data->mail;
            $from = variable_get('textbook_companion_from_email', '');
            $bcc = variable_get('textbook_companion_emails', '');
            $cc = variable_get('textbook_companion_cc_emails', '');
            $param['example_approved']['example_id'] = $ex_data['example_id'];
            $param['example_approved']['user_id'] = $user_data->uid;
            $param['example_approved']['headers'] = array(
                'From' => $from,
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                'Content-Transfer-Encoding' => '8Bit',
                'X-Mailer' => 'Drupal',
                'Cc' => $cc,
                'Bcc' => $bcc
            );
            if (!drupal_mail('textbook_companion', 'example_approved', $email_to, language_default(), $param, $from, TRUE))
                drupal_set_message('Error sending email message.', 'error');
        } else if ($ex_data['approved'] == "1") {
            if (delete_example($ex_data['example_id'])) {
                /* sending email */
                $email_to = $user_data->mail;
                $from = variable_get('textbook_companion_from_email', '');
                $bcc = variable_get('textbook_companion_emails', '');
                $cc = variable_get('textbook_companion_cc_emails', '');
                $param['example_disapproved']['preference_id'] = $chapter_data->preference_id;
                $param['example_disapproved']['chapter_id'] = $example_data->chapter_id;
                $param['example_disapproved']['example_number'] = $example_data->number;
                $param['example_disapproved']['example_caption'] = $example_data->caption;
                $param['example_disapproved']['user_id'] = $user_data->uid;
                $param['example_disapproved']['message'] = $ex_data['message'];
                $param['example_disapproved']['headers'] = array(
                    'From' => $from,
                    'MIME-Version' => '1.0',
                    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                    'Content-Transfer-Encoding' => '8Bit',
                    'X-Mailer' => 'Drupal',
                    'Cc' => $cc,
                    'Bcc' => $bcc
                );
                if (!drupal_mail('textbook_companion', 'example_disapproved', $email_to, language_default(), $param, $from, TRUE))
                    drupal_set_message('Error sending email message.', 'error');
            } else {
                drupal_set_message('Error disapproving and deleting example. Please contact administrator.', 'error');
            }
        }
    }
    drupal_set_message('Updated successfully.', 'status');
    drupal_goto('textbook-companion/code-approval');
}
/********************************* BULK APPROVAL ******************************/
function bulk_approval_form($form, &$form_state)
{
    $options_first = _bulk_list_of_books();
    $options_two = _ajax_bulk_get_chapter_list();
    $selected = isset($form_state['values']['book']) ? $form_state['values']['book'] : key($options_first);
    $select_two = isset($form_state['values']['chapter']) ? $form_state['values']['chapter'] : key($options_two);
    $form['book'] = array(
        '#type' => 'select',
        '#title' => t('Title of the Book'),
        '#options' => _bulk_list_of_books(),
        '#default_value' => $selected,
        '#tree' => TRUE,
        '#ajax' => array(
            'callback' => 'ajax_bulk_chapter_list_callback'
        ),
        '#validated' => TRUE
    );
    $form['download_book'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_book"></div>'
    );
    $form['notes_book'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_book_notes"></div>'
    );
    $form['book_actions'] = array(
        '#type' => 'select',
        '#title' => t('Please select action for selected book'),
        '#options' => _bulk_list_book_actions(),
        //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
        '#prefix' => '<div id="ajax_selected_book_action" style="color:red;">',
        '#suffix' => '</div>',
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        ),
        '#validated' => TRUE
    );
    $form['chapter'] = array(
        '#type' => 'select',
        '#title' => t('Title of the Chapter'),
        '#options' => _ajax_bulk_get_chapter_list($selected),
        //'#default_value' => $chapter_default_value,
        '#prefix' => '<div id="ajax_select_chapter_list">',
        '#suffix' => '</div>',
        '#validated' => TRUE,
        '#tree' => TRUE,
        '#ajax' => array(
            'callback' => 'ajax_bulk_example_list_callback'
        ),
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        )
    );
    $form['download_chapter'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_download_chapter"></div>'
    );
    $form['chapter_actions'] = array(
        '#type' => 'select',
        '#title' => t('Please select action for selected chapter'),
        '#options' => _bulk_list_chapter_actions(),
        //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
        '#prefix' => '<div id="ajax_selected_chapter_action" style="color:red;">',
        '#suffix' => '</div>',
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        ),
        '#ajax' => array(
            'callback' => 'ajax_bulk_chapter_actions_callback'
        )
    );
    $form['example'] = array(
        '#type' => 'select',
        '#title' => t('Example No. (Caption)'),
        '#options' => _ajax_bulk_get_examples(),
        // '#default_value' => $example_default_value,       
        '#validated' => TRUE,
        '#prefix' => '<div id="ajax_selected_example">',
        '#suffix' => '</div>',
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        ),
        '#ajax' => array(
            'callback' => 'ajax_bulk_example_files_callback'
        )
    );
    $form['download_example'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_download_selected_example"></div>'
    );
    $form['edit_example'] = array(
        '#type' => 'item',
        '#markup' => '<div id="ajax_edit_selected_example"></div>'
    );
    $form['example_files'] = array(
        '#type' => 'item',
        '#markup' => '',
        '#prefix' => '<div id="ajax_example_files_list">',
        '#suffix' => '</div>'
    );
    $form['example_actions'] = array(
        '#type' => 'select',
        '#title' => t('Please select action for selected example'),
        '#options' => _bulk_list_example_actions(),
        //'#default_value' => isset($form_state['values']['lab_actions']) ? $form_state['values']['lab_actions'] : 0,
        '#prefix' => '<div id="ajax_selected_example_action" style="color:red;">',
        '#suffix' => '</div>',
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        )
    );
    $form['message'] = array(
        '#type' => 'textarea',
        '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
        '#states' => array(
            'visible' => array(
                array(
                    array(
                        ':input[name="book_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="chapter_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="example_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="book_actions"]' => array(
                            'value' => 4
                        )
                    )
                )
            ),
            'required' => array(
                array(
                    array(
                        ':input[name="book_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="chapter_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="example_actions"]' => array(
                            'value' => 3
                        )
                    ),
                    'or',
                    array(
                        ':input[name="book_actions"]' => array(
                            'value' => 4
                        )
                    )
                )
            )
        )
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
        '#states' => array(
            'invisible' => array(
                ':input[name="book"]' => array(
                    'value' => 0
                )
            )
        )
    );
    return $form;
}
function bulk_approval_form_submit($form, &$form_state)
{
    global $user;
    $root_path = textbook_companion_path();
    if ($form_state['clicked_button']['#value'] == 'Submit') {
        if ($form_state['values']['book'])
            del_book_pdf($form_state['values']['book']);
        if (user_access('bulk manage code')) {
            $query = db_select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('id', $form_state['values']['book']);
            $result = $query->execute();
            $pref_data = $result->fetchObject();
            $prop_id = $pref_data->proposal_id;
            $query = db_select('textbook_companion_proposal');
            $query->fields('textbook_companion_proposal');
            $query->condition('id', $prop_id);
            $user_query = $query->execute();
            $user_info = $user_query->fetchObject();
            $user_data = user_load($user_info->uid);
            if (($form_state['values']['book_actions'] == 1) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)) {
                /* approving entire book */
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $preference_data = $result->fetchObject();
                $query = db_select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $chapter_q = $query->execute();
                while ($chapter_data = $chapter_q->fetchObject()) {
                    $query = db_update('textbook_companion_example');
                    $query->fields(array(
                        'approval_status' => 1,
                        'approver_uid' => $user->uid
                    ));
                    $query->condition('chapter_id', $chapter_data->id);
                    $query->condition('approval_status', 0);
                    $num_updated = $query->execute();
                }
                $query = db_update('textbook_companion_preference');
                $query->fields(array(
                    'submited_all_examples_code' => 2
                ));
                $query->condition('id', $form_state['values']['book']);
                $num_updated = $query->execute();
                drupal_set_message(t('Approved Entire Book.'), 'status');
                /* email */
                $email_subject = t('[!site_name][Textbook Companion] Your uploaded Textbook Companion examples have been approved', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your all the uploaded examples for the book have been approved.

Title of the book : ' . $preference_data->book . '
Author name : ' . $preference_data->author . '
ISBN No. : ' . $preference_data->isbn . '
Publisher and Place : ' . $preference_data->publisher . '
Edition : ' . $preference_data->edition . '
Year of publication : ' . $preference_data->year . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } elseif (($form_state['values']['book_actions'] == 2) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)) {
                /* pending entire book */
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $preference_data = $result->fetchObject();
                $query = db_select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $chapter_q = $query->execute();
                while ($chapter_data = $chapter_q->fetchObject()) {
                    $query = db_update('textbook_companion_example');
                    $query->fields(array(
                        'approval_status' => 0
                    ));
                    $query->condition('chapter_id', $chapter_data->id);
                    $num_updated = $query->execute();
                }
                drupal_set_message(t('Pending Review Entire Book.'), 'status');
                /* email */
                $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as pending', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your all the uploaded examples for the book have been marked as pending to be reviewed. 
You will be able to see the examples after they have been approved by one of our reviewers.

Title of the book : ' . $preference_data->book . '
Author name : ' . $preference_data->author . '
ISBN No. : ' . $preference_data->isbn . '
Publisher and Place : ' . $preference_data->publisher . '
Edition : ' . $preference_data->edition . '
Year of publication : ' . $preference_data->year . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } elseif (($form_state['values']['book_actions'] == 3) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)) {
                if (strlen(trim($form_state['values']['message'])) <= 30) {
                    form_set_error('message', t(''));
                    drupal_set_message("Please mention the reason for disapproval. Minimum 30 character required", 'error');
                    return;
                }
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $preference_data = $result->fetchObject();
                if (!user_access('bulk delete code')) {
                    drupal_set_message(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Book.'), 'error');
                    return;
                }
                if (delete_book($form_state['values']['book'])) {
                    drupal_set_message(t('Dis-Approved and Deleted Entire Book.'), 'status');
                } else {
                    drupal_set_message(t('Error Dis-Approving and Deleting Entire Book.'), 'error');
                }
                /* email */
                /*$email_subject = t('Your uploaded examples have been marked as dis-approved');
                $email_body =array( t('Your all the uploaded examples for the whole book have been marked as dis-approved.
                
                Reason for dis-approval:
                
                ' . $form_state['values']['message']));*/
                $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as
				dis-approved', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your all the uploaded examples for the whole book have been marked as dis-approved.

Title of the book : ' . $preference_data->book . '
Author name : ' . $preference_data->author . '
ISBN No. : ' . $preference_data->isbn . '
Publisher and Place : ' . $preference_data->publisher . '
Edition : ' . $preference_data->edition . '
Year of publication : ' . $preference_data->year . '


Reason for dis-approval:' . $form_state['values']['message'] . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } elseif (($form_state['values']['book_actions'] == 4) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 0)) {
                if (strlen(trim($form_state['values']['message'])) <= 30) {
                    form_set_error('message', t(''));
                    drupal_set_message("Please mention the reason for disapproval/deletion. Minimum 30 character required", 'error');
                    return;
                }
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                if (!user_access('bulk delete code')) {
                    drupal_set_message(t('You do not have permission to Bulk Delete Entire Book Including Proposal.'), 'error');
                    return;
                }
                if (delete_book($form_state['values']['book'])) {
                    drupal_set_message(t('Dis-Approved and Deleted Entire Book examples.'), 'status');
                    $dir_path = $root_path . $form_state['values']['book'];
                    if (is_dir($dir_path)) {
                        $res = rmdir($dir_path);
                        if (!$res) {
                            drupal_set_message(t("Cannot delete Book directory : " . $dir_path . ". Please contact administrator."), 'error');
                            return;
                        }
                    } else {
                        drupal_set_message(t("Book directory not present : " . $dir_path . ". Skipping deleting book directory."), 'status');
                    }
                    /* deleting preference and proposal */
                    $query = db_select('textbook_companion_preference');
                    $query->fields('textbook_companion_preference');
                    $query->condition('id', $form_state['values']['book']);
                    $result = $query->execute();
                    $preference_data = $result->fetchObject();
                    $proposal_id = $preference_data->proposal_id;
                    $query = db_delete('textbook_companion_preference');
                    $query->condition('proposal_id', $proposal_id);
                    $num_deleted = $query->execute();
                    $query = db_delete('textbook_companion_proposal');
                    $query->condition('id', $proposal_id);
                    $num_deleted = $query->execute();
                    drupal_set_message(t('Deleted Book Proposal.'), 'status');
                    /* email */
                    $email_subject = t('[!site_name] Your uploaded Textbook Companion examples including the book proposal 					have been deleted', array(
                        '!site_name' => variable_get('site_name', '')
                    ));
                    $email_body = array(
                        0 => t('

Dear !user_name,

We regret to inform you that all the uploaded examples including the book with following details have been deleted permanently.


Title of the book : ' . $pref_data->book . '
Author name : ' . $pref_data->author . '
ISBN No. : ' . $pref_data->isbn . '
Publisher and Place : ' . $pref_data->publisher . '
Edition : ' . $pref_data->edition . '
Year of publication : ' . $pref_data->year . '

Reason for deletion:' . $form_state['values']['message'] . '


Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                            '!site_name' => variable_get('site_name', ''),
                            '!user_name' => $user_data->name
                        ))
                    );
                } else {
                    drupal_set_message(t('Error Dis-Approving and Deleting Entire Book.'), 'error');
                }
            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 1) && ($form_state['values']['example_actions'] == 0)) {
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = db_select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = db_update('textbook_companion_example');
                $query->fields(array(
                    'approval_status' => 1,
                    'approver_uid' => $user->uid
                ));
                $query->condition('chapter_id', $form_state['values']['chapter']);
                $query->condition('approval_status', 0);
                $num_updated = $query->execute();
                drupal_set_message(t('Approved Entire Chapter.'), 'status');
                /* email */
                $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been approved', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your all the uploaded examples for the chapter have been approved. 

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 2) && ($form_state['values']['example_actions'] == 0)) {
                /*db_query("UPDATE {textbook_companion_example} SET approval_status = 0 WHERE chapter_id = %d", $form_state['values']['chapter']);*/
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = db_select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = db_update('textbook_companion_example');
                $query->fields(array(
                    'approval_status' => 0
                ));
                $query->condition('chapter_id', $form_state['values']['chapter']);
                $num_updated = $query->execute();
                drupal_set_message(t('Entire Chapter marked as Pending Review.'), 'status');
                /* email */
                $email_subject = t('[!site_name] Your uploaded Textbook Companion examples have been marked as pending', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your all the uploaded examples for the chapter have been marked as pending to be reviewed.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 3) && ($form_state['values']['example_actions'] == 0)) {
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = db_select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                if (strlen(trim($form_state['values']['message'])) <= 30) {
                    form_set_error('message', t(''));
                    drupal_set_message("Please mention the reason for disapproval. Minimum 30 character required", 'error');
                    return;
                }
                if (!user_access('bulk delete code')) {
                    drupal_set_message(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Chapter.'), 'error');
                    return;
                }
                if (delete_chapter($form_state['values']['chapter'])) {
                    drupal_set_message(t('Dis-Approved and Deleted Entire Chapter.'), 'status');
                } else {
                    drupal_set_message(t('Error Dis-Approving and Deleting Entire Chapter.'), 'error');
                }
                /* email */
                $email_subject = t('[!site_name] Your uploaded Textbook Companion example have been marked as 					dis-approved', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your uploaded example for the entire chapter have been marked as dis-approved.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '


Reason for dis-approval:' . $form_state['values']['message'] . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 1)) {
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = db_select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = db_select('textbook_companion_example');
                $query->fields('textbook_companion_example');
                $query->condition('id', $form_state['values']['example']);
                $result = $query->execute();
                $examp_data = $result->fetchObject();
                $query = db_update('textbook_companion_example');
                $query->fields(array(
                    'approval_status' => 1,
                    'approver_uid' => $user->uid
                ));
                $query->condition('id', $form_state['values']['example']);
                $num_updated = $query->execute();
                drupal_set_message(t('Example approved.'), 'status');
                /* email */
                $email_subject = t('[!site_name] Your uploaded Textbook Companion example have been approved', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your example for OpenModelica Textbook Companion with the following details is approved.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '
Example number : ' . $examp_data->number . '
Caption : ' . $examp_data->caption . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 2)) {
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = db_select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = db_select('textbook_companion_example');
                $query->fields('textbook_companion_example');
                $query->condition('id', $form_state['values']['example']);
                $result = $query->execute();
                $examp_data = $result->fetchObject();
                $query = db_update('textbook_companion_example');
                $query->fields(array(
                    'approval_status' => 0
                ));
                $query->condition('id', $form_state['values']['example']);
                $num_updated = $query->execute();
                drupal_set_message(t('Example marked as Pending Review.'), 'status');
                /* email */
                $email_subject = t('[!site_name] Your uploaded Textbook Companion example has been marked as pending', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your uploaded example for OpenModelica Textbook Companion with the following details has been marked as pending to be reviewed.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '
Example number : ' . $examp_data->number . '
Caption : ' . $examp_data->caption . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } elseif (($form_state['values']['book_actions'] == 0) && ($form_state['values']['chapter_actions'] == 0) && ($form_state['values']['example_actions'] == 3)) {
                if (strlen(trim($form_state['values']['message'])) <= 30) {
                    form_set_error('message', t(''));
                    drupal_set_message("Please mention the reason for disapproval. Minimum 30 character required", 'error');
                    return;
                }
                $query = db_select('textbook_companion_preference');
                $query->fields('textbook_companion_preference');
                $query->condition('id', $form_state['values']['book']);
                $query->condition('approval_status', 1);
                $result = $query->execute();
                $pref_data = $result->fetchObject();
                $query = db_select('textbook_companion_chapter');
                $query->fields('textbook_companion_chapter');
                $query->condition('preference_id', $form_state['values']['book']);
                $query->condition('id', $form_state['values']['chapter']);
                $result = $query->execute();
                $chap_data = $result->fetchObject();
                $query = db_select('textbook_companion_example');
                $query->fields('textbook_companion_example');
                $query->condition('id', $form_state['values']['example']);
                $result = $query->execute();
                $examp_data = $result->fetchObject();
                if (delete_example($form_state['values']['example'])) {
                    drupal_set_message(t('Example Dis-Approved and Deleted.'), 'status');
                } else {
                    drupal_set_message(t('Error Dis-Approving and Deleting Example.'), 'error');
                }
                /* email */
                $email_subject = t('[!site_name] Your uploaded Textbook Companion example has been marked as 
				dis-approved', array(
                    '!site_name' => variable_get('site_name', '')
                ));
                $email_body = array(
                    0 => t('

Dear !user_name,

Your example for OpenModelica Textbook Companion has been marked as dis-approved and deleted.

Title of the book : ' . $pref_data->book . '
Title of the chapter : ' . $chap_data->name . '
Example number : ' . $examp_data->number . '
Caption : ' . $examp_data->caption . '

Reason for dis-approval:' . $form_state['values']['message'] . '

Best Wishes,

!site_name Team,
FOSSEE,IIT Bombay', array(
                        '!site_name' => variable_get('site_name', ''),
                        '!user_name' => $user_data->name
                    ))
                );
            } else {
                drupal_set_message(t('Please select only one action at a time'), 'error');
                return;
            }
            /****** sending email when everything done ******/
            if ($email_subject) {
                $email_to = $user_data->mail;
                $from = variable_get('textbook_companion_from_email', '');
                $bcc = variable_get('textbook_companion_emails', '');
                $cc = variable_get('textbook_companion_cc_emails', '');
                $param['standard']['subject'] = $email_subject;
                $param['standard']['body'] = $email_body;
                $param['standard']['headers'] = array(
                    'From' => $from,
                    'MIME-Version' => '1.0',
                    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                    'Content-Transfer-Encoding' => '8Bit',
                    'X-Mailer' => 'Drupal',
                    'Cc' => $cc,
                    'Bcc' => $bcc
                );
                if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, $from, TRUE))
                    drupal_set_message('Error sending email message.', 'error');
            }
        } else {
            drupal_set_message(t('You do not have permission to bulk manage code.'), 'error');
        }
    }
}
function _bulk_list_of_books()
{
    $book_titles = array(
        '0' => 'Please select...'
    );
    $query = db_select('textbook_companion_preference', 'pp');
    $query->join('textbook_companion_proposal', 'p', 'pp.proposal_id=p.id');
    $query->join('users', 'u', 'p.uid=u.uid');
    $query->fields('u', array(
        'name'
    ));
    $query->fields('pp', array(
        'id',
        'book',
        'author'
    ));
    $or = db_or();
    $or->condition('approval_status', 1);
    $or->condition('approval_status', 3);
    $query->condition($or);
    $query->orderBy('book', 'ASC');
    $book_titles_q = $query->execute();
    while ($book_titles_data = $book_titles_q->fetchObject()) {
        $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')' . ' (Proposed by ' . $book_titles_data->name . ')';
    }
    return $book_titles;
}
function _ajax_bulk_get_chapter_list($preference_id = 0)
{
    $book_chapters = array(
        '0' => 'Please select...'
    );
    $query = db_select('textbook_companion_chapter');
    $query->fields('textbook_companion_chapter');
    $query->condition('preference_id', $preference_id);
    $query->orderBy('number', 'ASC');
    $book_chapters_q = $query->execute();
    while ($book_chapters_data = $book_chapters_q->fetchObject()) {
        $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
    }
    return $book_chapters;
}
function _ajax_bulk_get_examples($chapter_id = 0)
{
    $book_examples = array(
        '0' => 'Please select...'
    );
    $query = db_select('textbook_companion_example');
    $query->fields('textbook_companion_example');
    $query->condition('chapter_id', $chapter_id);
    $book_examples_q = $query->execute();
    while ($book_examples_data = $book_examples_q->fetchObject()) {
        $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
    }
    return $book_examples;
}
function _bulk_list_book_actions()
{
    $book_actions = array(
        '0' => 'Please select...'
    );
    $book_actions[1] = 'Approve Entire Book';
    $book_actions[2] = 'Pending Review Entire Book';
    $book_actions[3] = 'Dis-Approve Entire Book (This will delete all the examples in the book)';
    $book_actions[4] = 'Delete Entire Book Including Proposal';
    return $book_actions;
}
function _bulk_list_chapter_actions()
{
    $chapter_actions = array(
        '0' => 'Please select...'
    );
    $chapter_actions[1] = 'Approve Entire Chapter';
    $chapter_actions[2] = 'Pending Review Entire Chapter';
    $chapter_actions[3] = 'Dis-Approve Entire Chapter (This will delete all the examples in the chapter)';
    return $chapter_actions;
}
function _bulk_list_example_actions()
{
    $example_actions = array(
        '0' => 'Please select...'
    );
    $example_actions[1] = 'Approve  Approve Example';
    $example_actions[2] = 'Pending Review Example';
    $example_actions[3] = 'Dis-approve Example (This will delete the example)';
    return $example_actions;
}
/****************************** Ajax Callback function ********************/
function ajax_bulk_chapter_list_callback($form, $form_state)
{
    $commands = array();
    $book_default_value = $form_state['values']['book'];
    if ($book_default_value > 0) {
        $commands[] = ajax_command_html('#ajax_selected_book', l('Download', 'textbook-companion/full-download/book/' . $book_default_value) . ' ' . t('(Download all the approved and unapproved examples of the entire book)'));
        $form['book_actions']['#options'] = _bulk_list_book_actions();
        $commands[] = ajax_command_replace('#ajax_selected_book_action', drupal_render($form['book_actions']));
        $form['chapter']['#options'] = _ajax_bulk_get_chapter_list($book_default_value);
        $commands[] = ajax_command_replace('#ajax_select_chapter_list', drupal_render($form['chapter']));
        $commands[] = ajax_command_html('#ajax_download_chapter', '');
        $form['chapter_actions']['#options'] = _bulk_list_book_actions();
        $commands[] = ajax_command_replace('#ajax_selected_chapter_action', drupal_render($form['chapter_actions']));
        $commands[] = ajax_command_html('#ajax_selected_chapter_action', '');
        $commands[] = ajax_command_html('#ajax_selected_example', '');
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', drupal_render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', drupal_render($form['example_files']));
    } else {
        $commands[] = ajax_command_html('#ajax_selected_book', '');
        $commands[] = ajax_command_html('#ajax_selected_book_pdf', '');
        $commands[] = ajax_command_html('#ajax_selected_book_regenerate_pdf', '');
        $commands[] = ajax_command_html('#ajax_selected_book_notes', '');
        $form['chapter']['#options'] = _ajax_bulk_get_chapter_list();
        $commands[] = ajax_command_replace('#ajax_select_chapter_list', drupal_render($form['chapter']));
        $commands[] = ajax_command_html('#ajax_select_chapter_list', '');
        $form['book_actions']['#options'] = _bulk_list_book_actions();
        $commands[] = ajax_command_replace('#ajax_selected_book_action', drupal_render($form['book_actions']));
        $commands[] = ajax_command_html('#ajax_selected_book_action', '');
        $form['chapter_actions']['#options'] = _bulk_list_chapter_actions();
        $commands[] = ajax_command_replace('#ajax_selected_chapter_action', drupal_render($form['chapter_actions']));
        $commands[] = ajax_command_html('#ajax_selected_chapter_action', '');
        $commands[] = ajax_command_html('#ajax_download_chapter', '');
        $commands[] = ajax_command_html('#ajax_selected_example', '');
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', drupal_render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', drupal_render($form['example_files']));
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_bulk_example_list_callback($form, $form_state)
{
    $commands = array();
    $chapter_default_value = $form_state['values']['chapter'];
    if ($chapter_default_value > 0) {
        $commands[] = ajax_command_html('#ajax_download_chapter', l('Download', 'textbook-companion/full-download/chapter/' . $chapter_default_value) . ' ' . t('(Download all the approved and unapproved examples of the entire chapter)'));
        $form['chapter_actions']['#options'] = _bulk_list_chapter_actions();
        $commands[] = ajax_command_replace('#ajax_selected_chapter_action', drupal_render($form['chapter_actions']));
        $form['example']['#options'] = _ajax_bulk_get_examples($chapter_default_value);
        $commands[] = ajax_command_replace('#ajax_selected_example', drupal_render($form['example']));
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', drupal_render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', drupal_render($form['example_files']));
    } else {
        $commands[] = ajax_command_html('#ajax_download_chapter', '');
        $form['chapter_actions']['#options'] = _bulk_list_chapter_actions();
        $commands[] = ajax_command_replace('#ajax_selected_chapter_action', drupal_render($form['chapter_actions']));
        $commands[] = ajax_command_html('#ajax_selected_chapter_action', '');
        $form['example']['#options'] = _ajax_bulk_get_examples();
        $commands[] = ajax_command_replace('#ajax_selected_example', drupal_render($form['example']));
        $commands[] = ajax_command_html('#ajax_selected_example', '');
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', drupal_render($form['example_files']));
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', drupal_render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_bulk_example_files_callback($form, $form_state)
{
    $commands = array();
    $example_list_default_value = $form_state['values']['example'];
    //var_dump($example_list_default_value);
    if ($example_list_default_value > 0) {
        $query = db_select('textbook_companion_example_files');
        $query->fields('textbook_companion_example_files');
        $query->condition('example_id', $example_list_default_value);
        $example_list_q = $query->execute();
        if ($example_list_q) {
            $example_files_rows = array();
            while ($example_list_data = $example_list_q->fetchObject()) {
                $example_file_type = '';
                switch ($example_list_data->filetype) {
                    case 'S':
                        $example_file_type = 'Source or Main file';
                        break;
                    case 'R':
                        $example_file_type = 'Result file';
                        break;
                    case 'X':
                        $example_file_type = 'xcos file';
                        break;
                    default:
                        $example_file_type = 'Unknown';
                        break;
                }
                $example_files_rows[] = array(
                    l($example_list_data->filename, 'textbook-companion/download/file/' . $example_list_data->id),
                    $example_file_type
                );
            }
            /* creating list of files table */
            $example_files_header = array(
                'Filename',
                'Type'
            );
            $example_files = theme('table', array(
                'header' => $example_files_header,
                'rows' => $example_files_rows
            ));
            $form['example_files']['#title'] = 'List of example files';
            $form['example_files']['#markup'] = $example_files;
            $commands[] = ajax_command_replace('#ajax_example_files_list', drupal_render($form['example_files']));
            $commands[] = ajax_command_html('#ajax_download_selected_example', l('Download Example', 'textbook-companion/download/example/' . $example_list_default_value));
            $commands[] = ajax_command_html('#ajax_edit_selected_example', l('Edit Example', 'code_approval/editcode/' . $example_list_default_value));
            $form['example_actions']['#options'] = _bulk_list_example_actions();
            $commands[] = ajax_command_replace('#ajax_selected_example_action', drupal_render($form['example_actions']));
        }
    } else {
        $commands[] = ajax_command_html('#ajax_download_selected_example', '');
        $commands[] = ajax_command_html('#ajax_edit_selected_example', '');
        $form['example_files']['#title'] = '';
        $form['example_files']['#markup'] = '';
        $commands[] = ajax_command_replace('#ajax_example_files_list', drupal_render($form['example_files']));
        $form['example_actions']['#options'] = _bulk_list_example_actions();
        $commands[] = ajax_command_replace('#ajax_selected_example_action', drupal_render($form['example_actions']));
        $commands[] = ajax_command_html('#ajax_selected_example_action', '');
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_bulk_chapter_actions_callback()
{
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function edit_code_submission()
{
    /* get pending proposals to be approved */
    $proposal_rows = array();
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 1);
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
        /* get preference */
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('proposal_id', $proposal_data->id);
        $query->condition('approval_status', 1);
        $query->condition('submited_all_examples_code', 1);
        $query->range(0, 1);
        $preference_q = $query->execute();
        $preference_data = $preference_q->fetchObject();
        if (!$preference_data) {
            $query = db_select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('proposal_id', $proposal_data->id);
            $query->condition('pref_number', 1);
            //$query->condition('approval_status', 0);        
            $query->range(0, 1);
            $preference_q = $query->execute();
            $preference_data = $preference_q->fetchObject();
        }
        $proposal_status = '';
        switch ($preference_data->submited_all_examples_code) {
            case 0:
                $proposal_status = 'Code Submission Pending';
                break;
            case 1:
                $proposal_status = 'Submitted code for all exmample for book';
                break;
        }
        $proposal_rows[] = array(
            "{$preference_data->book} <br> 
<em>by {$preference_data->author}</em>",
            l($proposal_data->full_name, 'user/' . $proposal_data->uid),
            $proposal_status,
            l('Edit', 'textbook-companion/code-approval/edit-code-submission/edit/' . $preference_data->id)
        );
    }
    /* check if there are any pending proposals */
    if (!$proposal_rows) {
        drupal_set_message(t('There are no proposals.'), 'status');
        return '';
    }
    $proposal_header = array(
        'Title of the Book',
        'Contributor Name',
        'Status',
        'Action'
    );
    $output = theme('table', array(
        'header' => $proposal_header,
        'rows' => $proposal_rows
    ));
    return $output;
}
function get_edit_code_submission($id = NULL)
{
    if ($id) {
        return drupal_get_form('edit_code_submission_form', $id);
    } else {
        return 'Access denied';
    }
}
function edit_code_submission_form($form, $form_state, $preference_id)
{
    /* get current proposal */
    $preference_id = arg(4);
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $preference_data = $query->execute()->fetchObject();
    if (!$preference_data) {
        drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
        drupal_goto('textbook-companion/code-approval/edit-code-submission');
        return;
    }
    $form = array();
    $form['book'] = array(
        '#type' => 'item',
        '#title' => t('Title of the book'),
        '#markup' => $preference_data->book
    );
    $form['author'] = array(
        '#type' => 'item',
        '#title' => t('Author Name'),
        '#markup' => $preference_data->author
    );
    $form['isbn'] = array(
        '#type' => 'item',
        '#title' => t('ISBN No'),
        '#markup' => $preference_data->isbn
    );
    $form['publisher'] = array(
        '#type' => 'item',
        '#title' => t('Publisher & Place'),
        '#markup' => $preference_data->publisher
    );
    $form['edition'] = array(
        '#type' => 'item',
        '#title' => t('Edition'),
        '#markup' => $preference_data->edition
    );
    $form['year'] = array(
        '#type' => 'item',
        '#title' => t('Year of pulication'),
        '#markup' => $preference_data->year
    );
    $form['all_example_submitted'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable code submission interface for user'),
        '#description' => 'Once you have submited this option user can upload more examples.',
        '#required' => TRUE
    );
    $form['hidden_preference_id'] = array(
        '#type' => 'hidden',
        '#value' => $preference_id
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancle'] = array(
        '#type' => 'item',
        '#markup' => l('Cancle', 'textbook-companion/code-approval/edit-code-submission')
    );
    return $form;
}
function edit_code_submission_form_validate($form, $form_state)
{
    if ($form_state['values']['all_example_submitted'] != 1) {
        form_set_error('all_example_submitted', t('Please check the field if you are intrested to submit the all uploaded examples for review!'));
    }
    return;
}
function edit_code_submission_form_submit($form, $form_state)
{
    global $user;
    if ($form_state['values']['all_example_submitted'] == 1) {
        if (db_query('UPDATE textbook_companion_preference SET submited_all_examples_code = 0 WHERE id = :preference_id', array(
            ':preference_id' => $form_state['values']['hidden_preference_id']
        ))) {
            $query = ("SELECT proposal_id FROM textbook_companion_preference WHERE id= :preference_id");
            $args = array(
                ":preference_id" => $form_state['values']['hidden_preference_id']
            );
            $proposal_data = db_query($query, $args);
            $proposal_data_result = $proposal_data->fetchObject();
            $proposal_query = db_select('textbook_companion_proposal');
            $proposal_query->fields('textbook_companion_proposal');
            $proposal_query->condition('proposal_status', 1);
            $proposal_query->condition('id', $proposal_data_result->proposal_id);
            $proposal_data_query = $proposal_query->execute()->fetchObject();
            /* sending email */
            $book_user = user_load($proposal_data_query->uid);
            $email_to = $book_user->mail;
            $from = variable_get('textbook_companion_from_email', '');
            $bcc = variable_get('textbook_companion_emails', '');
            $cc = variable_get('textbook_companion_cc_emails', '');
            $param['all_code_submitted_status_changed']['proposal_id'] = $proposal_data_result->proposal_id;
            $param['all_code_submitted_status_changed']['user_id'] = $user->uid;
            $param['all_code_submitted_status_changed']['headers'] = array(
                'From' => $from,
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                'Content-Transfer-Encoding' => '8Bit',
                'X-Mailer' => 'Drupal',
                'Cc' => $cc,
                'Bcc' => $bcc
            );
            if (!drupal_mail('textbook_companion', 'all_code_submitted_status_changed', $email_to, language_default(), $param, $from, TRUE))
                drupal_set_message('Error sending email message.', 'error');
            drupal_set_message('Enabled code submission interface for user');
            drupal_goto('textbook-companion/code-approval/edit-code-submission');
        }
    }
}
