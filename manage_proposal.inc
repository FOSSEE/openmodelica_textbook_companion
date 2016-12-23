<?php
function _proposal_pending()
{
    /* get pending proposals to be approved */
    $pending_rows = array();
    /*$pending_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 ORDER BY id DESC");*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
        $pending_rows[$pending_data->id] = array(
            date('d-m-Y', $pending_data->creation_date),
            l($pending_data->full_name, 'user/' . $pending_data->uid),
            date('d-m-Y', $pending_data->proposed_completion_date),
            l('Approve', 'textbook-companion/manage-proposal/approve/' . $pending_data->id) . ' | ' . l('Edit', 'textbook-companion/manage-proposal/edit/' . $pending_data->id)
        );
    }
    /* check if there are any pending proposals */
    if (!$pending_rows) {
        drupal_set_message(t('There are no pending proposals.'), 'status');
        return '';
    }
    $pending_header = array(
		'Date of Submission',
		'Contributor Name',
		'Proposed Date of Completion',
		'Action'
	);
    $output = theme('table', array(
        'header' => $pending_header,
        'rows' => $pending_rows
    ));
    return $output;
}
function _proposal_all()
{
    function _tbc_ext($status, $preference_id)
    {
        if ($status == "Approved") {
            //return " | " . l("ER", "tbc_external_review/add_book/" . $preference_id);
            return "";
        } else {
            return "";
        }
    }
    /* get pending proposals to be approved */
    $proposal_rows = array();
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} ORDER BY id DESC");*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
        /* get preference */
        /*$preference_q = db_query("SELECT * FROM textbook_companion_preference WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);   
        $preference_data = db_fetch_object($preference_q);*/
        $query = db_select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('proposal_id', $proposal_data->id);
        $query->condition('approval_status', 1);
        $query->range(0, 1);
        $preference_q = $query->execute();
        $preference_data = $preference_q->fetchObject();
        if (!$preference_data) {
            /* $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND pref_number = 1 LIMIT 1", $proposal_data->id);  
            $preference_data = db_fetch_object($preference_q);*/
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
        switch ($proposal_data->proposal_status) {
            case 0:
                $proposal_status = 'Pending';
                break;
            case 1:
                $proposal_status = 'Approved';
                break;
            case 2:
                $proposal_status = 'Dis-approved';
                break;
            case 3:
                $proposal_status = 'Completed';
                break;
            case 4:
                $proposal_status = 'External';
                break;
            case 5:
                $proposal_status = 'Submitted all codes';
                break;
            default:
                $proposal_status = 'Unknown';
                break;
        }
        if($proposal_data->proposed_completion_date != 0){
        $proposed_completion_date = date('d-m-Y', $proposal_data->proposed_completion_date);
        }
        else{
        $proposed_completion_date = "-----";
        }
        $proposal_rows[] = array(
            date('d-m-Y', $proposal_data->creation_date),
            "{$preference_data->book} <br> 
<em>by {$preference_data->author}</em>",
            l($proposal_data->full_name, 'user/' . $proposal_data->uid),
            date('d-m-Y', $proposal_data->completion_date),
            $proposed_completion_date,
            $proposal_status,
            l('Status', 'textbook-companion/manage-proposal/status/' . $proposal_data->id) . ' | ' . l('Edit', 'textbook-companion/manage-proposal/edit/' . $proposal_data->id) . _tbc_ext($proposal_status, $preference_data->id)
        );
    }
    /* check if there are any pending proposals */
    if (!$proposal_rows) {
        drupal_set_message(t('There are no proposals.'), 'status');
        return '';
    }
    $proposal_header = array(
        'Date of Submission',
        'Title of the Book',
        'Contributor Name',        
		'Actual Date of Completion',
		'Proposed Date of Completion',
        'Status',
        'Action'
    );
    $output = theme('table', array(
        'header' => $proposal_header,
        'rows' => $proposal_rows
    ));
    return $output;
}
function _category_all()
{
    /* get pending proposals to be approved */
    $preference_rows = array();
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 ORDER BY id DESC");*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approval_status', 1);
    $query->orderBy('id', 'DESC');
    $preference_q = $query->execute();
    while ($preference_data = $preference_q->fetchObject()) {
        switch ($preference_data->category) {
            case 0:
                $category_data = 'Not Selected';
                break;
            case 1:
                $category_data = 'Fluid Mechanics';
                break;
            case 2:
                $category_data = 'Control Theory & Control Systems';
                break;
            case 3:
                $category_data = 'Chemical Engineering';
                break;
            case 4:
                $category_data = 'Thermodynamics';
                break;
            case 5:
                $category_data = 'Mechanical Engineering';
                break;
            case 6:
                $category_data = 'Signal Processing';
                break;
            case 7:
                $category_data = 'Digital Communications';
                break;
            case 8:
                $category_data = 'Electrical Technology';
                break;
            case 9:
                $category_data = 'Mathematics & Pure Science';
                break;
            case 10:
                $category_data = 'Analog Electronics';
                break;
            case 11:
                $category_data = 'Digital Electronics';
                break;
            case 12:
                $category_data = 'Computer Programming';
                break;
            case 13:
                $category_data = 'Others';
                break;
            default:
                $category_data = 'Unknown';
                break;
        }
        $preference_rows[] = array(
            $preference_data->book . "<br> <i>by " . $preference_data->author . "</i>",
            $preference_data->isbn,
            $preference_data->publisher,
            $preference_data->edition,
            $preference_data->year,
            $category_data,
            l('Edit', 'textbook-companion/manage-proposal/category/edit/' . $preference_data->id)
        );
    }
    $preference_header = array(
        'Book',
        'ISBN',
        'Publisher',
        'Edition',
        'Year',
        'Category',
        'Status'
    );
    $output = theme('table', array(
        'header' => $preference_header,
        'rows' => $preference_rows
    ));
    return $output;
}
/******************************************************************************/
/************************** PROPOSAL APPROVAL FORM ****************************/
/******************************************************************************/
function proposal_approval_form($form, &$form_state)
{
    global $user;
    /* get current proposal */
    $proposal_id = arg(3);
    /*$result = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 and id = %d", $proposal_id);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    if ($result) {
        if ($row = $result->fetchObject()) {
            /* everything ok */
        } else {
            drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
            drupal_goto('textbook-companion/manage-proposal');
            return;
        }
    } else {
        drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
        drupal_goto('textbook-companion/manage-proposal');
        return;
    }
    $form['full_name'] = array(
        '#type' => 'item',
        '#markup' => l($row->full_name, 'user/' . $row->uid),
        '#title' => t('Contributor Name')
    );
    $form['email'] = array(
        '#type' => 'item',
        '#markup' => user_load($row->uid)->mail,
        '#title' => t('Email')
    );
    $form['mobile'] = array(
        '#type' => 'item',
        '#markup' => $row->mobile,
        '#title' => t('Mobile')
    );
    $form['how_project'] = array(
        '#type' => 'item',
        '#markup' => $row->how_project,
        '#title' => t('How did you come to know about this project')
    );
    $form['course'] = array(
        '#type' => 'item',
        '#markup' => $row->course,
        '#title' => t('Course')
    );
    $form['branch'] = array(
        '#type' => 'item',
        '#markup' => $row->branch,
        '#title' => t('Department/Branch')
    );
    $form['university'] = array(
        '#type' => 'item',
        '#markup' => $row->university,
        '#title' => t('University/Institute')
    );
    $form['city'] = array(
        '#type' => 'item',
        '#markup' => $row->city,
        '#title' => t('City/Village')
    );
    $form['pincode'] = array(
        '#type' => 'item',
        '#markup' => $row->pincode,
        '#title' => t('Pincode')
    );
    $form['state'] = array(
        '#type' => 'item',
        '#markup' => $row->state,
        '#title' => t('State')
    );
    $form['faculty'] = array(
        '#type' => 'hidden',
        '#markup' => $row->faculty,
        '#title' => t('College Teacher/Professor')
    );
    $form['reviewer'] = array(
        '#type' => 'hidden',
        '#markup' => $row->reviewer,
        '#title' => t('Reviewer')
    );
      if($row->proposed_completion_date != 0){
    $proposed_completion_date = date('d-m-Y', $row->proposed_completion_date);
        }
        else{
        $proposed_completion_date = "-----";
        }
        
     $form['proposed_completion_date'] = array(
		'#type' => 'item',
		'#markup' => $proposed_completion_date,
		
		'#title' => t('Proposed Date of Completion')
	);
	 if($row->completion_date != 0){
	$actual_completion_date = date('d-m-Y', $row->completion_date);
        }
        else{
        $actual_completion_date = "-----";
        }
    $form['completion_date'] = array(
        '#type' => 'item',
        '#markup' =>$actual_completion_date,
        '#title' => t('Actual Date of Completion')
    );
    $form['operating_system'] = array(
        '#type' => 'item',
        '#markup' => $row->operating_system,
        '#title' => t('Operating System')
    );
    $form['version'] = array(
        '#type' => 'item',
        '#markup' => $row->openmodelica_version,
        '#title' => t('OpenModelica Version')
    );
    $form['reference'] = array(
        '#type' => 'item',
        '#markup' => $row->reference,
        '#title' => t('References')
    );
    $form['reason'] = array(
        '#type' => 'item',
        '#markup' => $row->reason,
        '#title' => t('Reasons')
    );
    /* get book preference */
    $preference_rows = array();
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d ORDER BY pref_number ASC", $proposal_id);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('pref_number', 'ASC');
    $preference_q = $query->execute();
    while ($preference_data = $preference_q->fetchObject()) {
        $preference_rows[$preference_data->id] = $preference_data->book . ' (Written by ' . $preference_data->author . ')';
    }
    if ($row->proposal_type == 1) {
        $form['book_preference'] = array(
            '#type' => 'radios',
            '#options' => $preference_rows,
            '#title' => t('Book Preferences'),
            '#required' => TRUE
        );
    } else {
        $form['book_preference'] = array(
            '#type' => 'radios',
            '#title' => t('Book Preferences'),
            '#options' => $preference_rows,
            '#required' => TRUE
        );
    }
    if ($row->samplefilepath != "Not available") {
        $form['samplecode'] = array(
            '#type' => 'markup',
            '#markup' => l('Download Sample Code', 'textbook-companion/download/samplecode/' . $proposal_id) . "<br><br>"
        );
    }
    $form['disapprove'] = array(
        '#type' => 'checkbox',
        '#title' => t('Disapprove all the above book preferences')
    );
    $form['message'] = array(
        '#type' => 'textarea',
        '#title' => t('Reason for disapproval'),
        '#states' => array(
            'visible' => array(
                ':input[name="disapprove"]' => array(
                    'checked' => TRUE
                )
            ),
            'required' => array(
                ':input[name="disapprove"]' => array(
                    'checked' => TRUE
                )
            )
        )
    );
    $form['proposal_id'] = array(
        '#type' => 'hidden',
        '#value' => $proposal_id
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'markup',
        '#value' => l(t('Cancel'), 'textbook-companion/manage-proposal')
    );
    return $form;
}
function proposal_approval_form_validate($form, &$form_state)
{
    if ($form_state['values']['disapprove']) {
        if (strlen(trim($form_state['values']['message'])) <= 30) {
            form_set_error('message', t('Please mention the reason for disapproval in minimum 30 characters.'));
        }
    }
    return;
}
function proposal_approval_form_submit($form, &$form_state)
{
    global $user;
    /* get current proposal */
    $proposal_id = $form_state['values']['proposal_id'];
    /*$result = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 and id = %d", $proposal_id);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    if ($result) {
        if ($row = $result->fetchObject()) {
            /* everything ok */
        } else {
            drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
            drupal_goto('textbook-companion/manage-proposal');
            return;
        }
    } else {
        drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
        drupal_goto('textbook-companion/manage-proposal');
        return;
    }
    /* disapprove */
    if ($form_state['values']['disapprove']) {
        /*db_query("UPDATE {textbook_companion_proposal} SET approver_uid = %d, approval_date = %d, proposal_status = 2, message = '%s' WHERE id = %d", $user->uid, time(), $form_state['values']['message'], $proposal_id);*/
        $query = db_update('textbook_companion_proposal');
        $query->fields(array(
            'approver_uid' => $user->uid,
            'approval_date' => time(),
            'proposal_status' => 2,
            'completion_date' => '0',
            'message' => $form_state['values']['message']
        ));
        $query->condition('id', $proposal_id);
        $num_updated = $query->execute();
        /*db_query("UPDATE {textbook_companion_preference} SET approval_status = 2 WHERE proposal_id = %d", $proposal_id);*/
        $query = db_update('textbook_companion_preference');
        $query->fields(array(
            'approval_status' => 2
        ));
        $query->condition('proposal_id', $proposal_id);
        $num_updated = $query->execute();
        /* unlock all the aicte books */
        /*$query = "
        UPDATE textbook_companion_aicte
        SET status = 0, uid = 0, proposal_id = 0, preference_id = 0
        WHERE proposal_id = {$proposal_id}
        ";
        db_query($query);*/
        /*$query = db_update('textbook_companion_aicte');
        $query->fields(array(
        'status' => 0,
        'uid' => 0,
        'proposal_id' => 0,
        'preference_id' => 0,
        ));
        $query->condition('proposal_id', $proposal_id);
        $num_updated = $query->execute();*/
        /* sending email */
        $book_user = user_load($row->uid);
        $email_to = $book_user->mail;
        $from = variable_get('textbook_companion_from_email', '');
        $bcc = variable_get('textbook_companion_emails', '');
        $cc = variable_get('textbook_companion_cc_emails', '');
        $param['proposal_disapproved']['proposal_id'] = $proposal_id;
        $param['proposal_disapproved']['user_id'] = $row->uid;
        $param['proposal_disapproved']['headers'] = array(
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc
        );
        if (!drupal_mail('textbook_companion', 'proposal_disapproved', $email_to, language_default(), $param, $from, TRUE))
            drupal_set_message('Error sending email message.', 'error');
        drupal_set_message('Book proposal dis-approved. User has been notified of the dis-approval.', 'error');
        drupal_goto('textbook-companion/manage-proposal');
        return;
    }
    /* get book preference and set the status */
    $preference_id = $form_state['values']['book_preference'];
    /*db_query("UPDATE {textbook_companion_proposal} SET approver_uid = %d, approval_date = %d, proposal_status = 1 WHERE id = %d", $user->uid, time(), $proposal_id);*/
    $query = db_update('textbook_companion_proposal');
    $query->fields(array(
        'approver_uid' => $user->uid,
        'approval_date' => time(),
        'proposal_status' => 1
    ));
    $query->condition('id', $proposal_id);
    $num_updated = $query->execute();
    /*db_query("UPDATE {textbook_companion_preference} SET approval_status = 1 WHERE id = %d", $preference_id);*/
    $query = db_update('textbook_companion_preference');
    $query->fields(array(
        'approval_status' => 1
    ));
    $query->condition('id', $preference_id);
    $num_updated = $query->execute();
    /* unlock aicte books except the one which was approved out of 3 nos */
    /* $query = "
    UPDATE textbook_companion_aicte
    SET status = 0, uid = 0, proposal_id = 0, preference_id = 0
    WHERE proposal_id = {$proposal_id} AND preference_id != {$preference_id}
    ";
    db_query($query);*/
    /*$query = db_update('textbook_companion_aicte');
    $query->fields(array(
    'status' => 0,
    'uid' => 0,
    'proposal_id' => 0,
    'preference_id' => 0,
    ));
    $query->condition('proposal_id', '$proposal_id');
    $query->condition('preference_id', '$preference_id', '<>');
    $num_updated = $query->execute();*/
    /* sending email */
    $book_user = user_load($row->uid);
    $email_to = $book_user->mail;
    $from = variable_get('textbook_companion_from_email', '');
    $bcc = variable_get('textbook_companion_emails', '');
    $cc = variable_get('textbook_companion_cc_emails', '');
    $param['proposal_approved']['proposal_id'] = $proposal_id;
    $param['proposal_approved']['user_id'] = $row->uid;
    $param['proposal_approved']['headers'] = array(
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc
    );
    if (!drupal_mail('textbook_companion', 'proposal_approved', $email_to, language_default(), $param, $from, TRUE))
        drupal_set_message('Error sending email message.', 'error');
    drupal_set_message('Book proposal approved. User has been notified of the approval', 'status');
    drupal_goto('textbook-companion/manage-proposal');
    return;
}
/******************************************************************************/
/*************************** PROPOSAL STATUS FORM *****************************/
/******************************************************************************/
function proposal_status_form($from, $form_state)
{
    global $user;
    /* get current proposal */
    $proposal_id = arg(3);
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $proposal_id);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if (!$proposal_data = $proposal_q->fetchObject()) {
        drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
        drupal_goto('textbook-companion/manage-proposal');
        return;
    }
    $form['full_name'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->full_name,
        '#title' => t('Contributor Name')
    );
    $form['email'] = array(
        '#type' => 'item',
        '#markup' => user_load($proposal_data->uid)->mail,
        '#title' => t('Email')
    );
    $form['mobile'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->mobile,
        '#title' => t('Mobile')
    );
    $form['how_project'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->how_project,
        '#title' => t('How did you come to know about this project')
    );
    $form['course'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->course,
        '#title' => t('Course')
    );
    $form['branch'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->branch,
        '#title' => t('Department/Branch')
    );
    $form['university'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->university,
        '#title' => t('University/Institute')
    );
    $form['city'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->city,
        '#title' => t('City/Village')
    );
    $form['pincode'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->pincode,
        '#title' => t('Pincode')
    );
    $form['state'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->state,
        '#title' => t('State')
    );
    $form['faculty'] = array(
        '#type' => 'hidden',
        '#markup' => $proposal_data->faculty,
        '#title' => t('College Teacher/Professor')
    );
    $form['reviewer'] = array(
        '#type' => 'hidden',
        '#markup' => $proposal_data->reviewer,
        '#title' => t('Reviewer')
    );
    $form['completion_date'] = array(
        '#type' => 'item',
        '#markup' => date('d-m-Y', $proposal_data->completion_date),
        '#title' => t('Expected Date of Completion')
    );
    $form['operating_system'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->operating_system,
        '#title' => t('Operating System')
    );
    $form['version'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->openmodelica_version,
        '#title' => t('OpenModelica Version')
    );
    if ($proposal_data->proposal_type == 1) {
        $form['reason'] = array(
            '#type' => 'hidden',
            '#markup' => $proposal_data->reason,
            '#title' => t('Reason')
        );
        $form['reference'] = array(
            '#type' => 'hidden',
            '#markup' => $proposal_data->reference,
            '#title' => t('References')
        );
    }
    /* get book preference */
    $preference_html = '<ul>';
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d ORDER BY pref_number ASC", $proposal_id);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('pref_number', 'ASC');
    $preference_q = $query->execute();
    while ($preference_data = $preference_q->fetchObject()) {
        if ($preference_data->approval_status == 1)
            $preference_html .= '<li><strong>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')  - Approved Book</strong></li>';
        else
            $preference_html .= '<li>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')</li>';
    }
    $preference_html .= '</ul>';
    $form['book_preference'] = array(
        '#type' => 'item',
        '#markup' => $preference_html,
        '#title' => t('Book Preferences')
    );
    $proposal_status = '';
    switch ($proposal_data->proposal_status) {
        case 0:
            $proposal_status = t('Pending');
            break;
        case 1:
            $proposal_status = t('Approved');
            break;
        case 2:
            $proposal_status = t('Dis-approved');
            break;
        case 3:
            $proposal_status = t('Completed');
            break;
        case 4:
            $proposal_status = t('External');
            break;
        case 5:
            $proposal_status = t('Submitted all codes');
            break;
        default:
            $proposal_status = t('Unkown');
            break;
    }
    $form['proposal_status'] = array(
        '#type' => 'item',
        '#markup' => $proposal_status,
        '#title' => t('Proposal Status')
    );
    if ($proposal_data->proposal_status == 2) {
        $form['message'] = array(
            '#type' => 'item',
            '#markup' => $proposal_data->message,
            '#title' => t('Reason for disapproval')
        );
    }
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('pref_number', 'ASC');
    $preference_q_status = $query->execute()->fetchObject();
    if ($preference_q_status->submited_all_examples_code == 1) {
        $form['submit_all_code'] = array(
            '#type' => 'checkbox',
            '#title' => t('<strong>Enable Code Submission for user</strong>'),
            '#description' => t('Check if user has not submitted all the book examples.')
        );
        $form['completed'] = array(
            '#type' => 'hidden',
            '#value' => 0
        );
    }else if($preference_q_status->submited_all_examples_code == 2)
    {
    
    if (($proposal_data->proposal_status == 1 || $proposal_data->proposal_status == 4) || $proposal_data->proposal_status == 5) {
        $form['completed'] = array(
            '#type' => 'checkbox',
            '#title' => t('<strong>Completed</strong>'),
            '#description' => t('Check if user has completed all the book examples.')
        );
        $form['submit_all_code'] = array(
            '#type' => 'hidden',
            '#value' => 0
        );
    }}
    if ($proposal_data->proposal_status == 0) {
        $form['approve'] = array(
            '#type' => 'item',
            '#markup' => l('Click here', 'textbook-companion/manage-proposal/approve/' . $proposal_id),
            '#title' => t('Approve')
        );
        $form['completed'] = array(
            '#type' => 'hidden',
            '#value' => 0
        );
        $form['submit_all_code'] = array(
            '#type' => 'hidden',
            '#value' => 0
        );
    }
    $form['proposal_id'] = array(
        '#type' => 'hidden',
        '#value' => $proposal_id
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'item',
        '#markup' => l(t('Cancel'), 'textbook-companion/manage-proposal/all')
    );
    return $form;
}
function proposal_status_form_submit($form, &$form_state)
{
    global $user;
    /* get current proposal */
    $proposal_id = $form_state['values']['proposal_id'];
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $proposal_id);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if (!$proposal_data = $proposal_q->fetchObject()) {
        drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
        drupal_goto('textbook-companion/manage-proposal');
        return;
    }
    if ($form_state['values']['submit_all_code'] == 1) {
        /*db_query("UPDATE {textbook_companion_proposal} SET proposal_status = 3 WHERE id = %d", $proposal_id);*/
        $query = db_update('textbook_companion_preference');
        $query->fields(array(
            'submited_all_examples_code' => 0
        ));
        $query->condition('id', $proposal_id);
        $num_updated = $query->execute();
        /* sending email */
        $book_user = user_load($proposal_data->uid);
        $params['all_code_submitted_status_changed']['proposal_id'] = $proposal_id;
        $params['all_code_submitted_status_changed']['user_id'] = $proposal_data->uid;
        $email_to = $book_user->mail;
        if (!drupal_mail('textbook_companion', 'all_code_submitted_status_changed', $email_to, language_default(), $params, variable_get('textbook_companion_from_email', NULL), TRUE))
            drupal_set_message('Error sending email message.', 'error');
        drupal_set_message('User has been notified of that code submission interface is now available .', 'status');
        drupal_goto('textbook-companion/manage-proposal');
        return;
    } else if ($form_state['values']['completed'] == 1) { /* set the book status to completed */ 
        /*db_query("UPDATE {textbook_companion_proposal} SET proposal_status = 3 WHERE id = %d", $proposal_id);*/
        $query = db_update('textbook_companion_proposal');
        $query->fields(array(
            'proposal_status' => 3,
            'completion_date' => time()
        ));
        $query->condition('id', $proposal_id);
        $num_updated = $query->execute();
        CreateReadmeFileTextbookCompanion($proposal_id);
        /* sending email */
        $book_user = user_load($proposal_data->uid);
        $email_to = $book_user->mail;
        $from = variable_get('textbook_companion_from_email', '');
        $bcc = variable_get('textbook_companion_emails', '');
        $cc = variable_get('textbook_companion_cc_emails', '');
        $param['proposal_completed']['proposal_id'] = $proposal_id;
        $param['proposal_completed']['user_id'] = $proposal_data->uid;
        $param['proposal_completed']['headers'] = array(
            'From' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
            'Content-Transfer-Encoding' => '8Bit',
            'X-Mailer' => 'Drupal',
            'Cc' => $cc,
            'Bcc' => $bcc
        );
        if (!drupal_mail('textbook_companion', 'proposal_completed', $email_to, language_default(), $param, $from, TRUE))
            drupal_set_message('Error sending email message.', 'error');
        drupal_set_message('Congratulations! Book proposal has been marked as completed. User has been notified of the completion.', 'status');
    } else {
        drupal_set_message('Please select any one action.', 'error');
        drupal_goto('textbook-companion/manage-proposal/status/' . $proposal_id);
        return;
    }
    drupal_goto('textbook-companion/manage-proposal');
    return;
}
/******************************************************************************/
/**************************** PROPOSAL EDIT FORM ******************************/
/******************************************************************************/
function proposal_edit_form($form, &$form_state, $nonaicte_book)
{
    global $user;
    /* get current proposal */
    $proposal_id = arg(3);
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $proposal_id);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
        $proposal_data = $proposal_q->fetchObject();
        if (!$proposal_data) {
            drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
            drupal_goto('textbook-companion/manage-proposal');
            return;
        }
    } else {
        drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
        drupal_goto('textbook-companion/manage-proposal');
        return;
    }
    $user_data = user_load($proposal_data->uid);
    /* $preference1_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND pref_number = %d LIMIT 1", $proposal_id, 1);
    $preference1_data = db_fetch_object($preference1_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('pref_number', 1);
    $query->range(0, 1);
    $preference1_q = $query->execute();
    $preference1_data = $preference1_q->fetchObject();
    /********************************************************************/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('pref_number', 2);
    $query->range(0, 1);
    $preference2_q = $query->execute();
    $preference2_data = $preference2_q->fetchObject();
    /**************************************************************************/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('pref_number', 3);
    $query->range(0, 1);
    $preference3_q = $query->execute();
    $preference3_data = $preference3_q->fetchObject();
    /*************************************************************************/
    $form['full_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Full Name'),
        '#size' => 30,
        '#maxlength' => 50,
        '#required' => TRUE,
        '#default_value' => $proposal_data->full_name
    );
    $form['email_id'] = array(
        '#type' => 'textfield',
        '#title' => t('Email'),
        '#size' => 30,
        '#value' => $user_data->mail,
        '#disabled' => TRUE
    );
    $form['mobile'] = array(
        '#type' => 'textfield',
        '#title' => t('Mobile No.'),
        '#size' => 30,
        '#maxlength' => 15,
        '#required' => TRUE,
        '#default_value' => $proposal_data->mobile
    );
    $form['how_project'] = array(
        '#type' => 'select',
        '#title' => t('How did you come to know about this project'),
        '#options' => array(
            'OpenModelica Website' => 'OpenModelica Website',
            'Friend' => 'Friend',
            'Professor/Teacher' => 'Professor/Teacher',
            'Mailing List' => 'Mailing List',
            'Poster in my/other college' => 'Poster in my/other college',
            'Others' => 'Others'
        ),
        '#required' => TRUE,
        '#default_value' => $proposal_data->how_project
    );
    $form['course'] = array(
        '#type' => 'textfield',
        '#title' => t('Course'),
        '#size' => 30,
        '#maxlength' => 50,
        '#required' => TRUE,
        '#default_value' => $proposal_data->course
    );
    $form['branch'] = array(
        '#type' => 'select',
        '#title' => t('Department/Branch'),
        '#options' => _list_of_departments(),
        '#required' => TRUE,
        '#default_value' => $proposal_data->branch
    );
    $form['university'] = array(
        '#type' => 'textfield',
        '#title' => t('University/ Institute'),
        '#size' => 80,
        '#maxlength' => 200,
        '#required' => TRUE,
        '#attributes' => array(
            'placeholder' => 'Insert full name of your institute/ university.... '
        ),
        '#default_value' => $proposal_data->university
    );
    $form['country'] = array(
        '#type' => 'select',
        '#title' => t('Country'),
        '#options' => array(
            'India' => 'India',
            'Others' => 'Others'
        ),
        '#required' => TRUE,
        '#tree' => TRUE,
        '#validated' => TRUE,
        '#default_value' => $proposal_data->country
    );
    $form['other_country'] = array(
        '#type' => 'textfield',
        '#title' => t('Other than India'),
        '#size' => 100,
        '#attributes' => array(
            'placeholder' => t('Enter your country name')
        ),
        '#default_value' => $proposal_data->country,
        '#states' => array(
            'visible' => array(
                ':input[name="country"]' => array(
                    'value' => 'Others'
                )
            )
        )
    );
    $form['other_state'] = array(
        '#type' => 'textfield',
        '#title' => t('State other than India'),
        '#size' => 100,
        '#default_value' => $proposal_data->state,
        '#attributes' => array(
            'placeholder' => t('Enter your state/region name')
        ),
        '#states' => array(
            'visible' => array(
                ':input[name="country"]' => array(
                    'value' => 'Others'
                )
            )
        )
    );
    $form['other_city'] = array(
        '#type' => 'textfield',
        '#title' => t('City other than India'),
        '#size' => 100,
        '#default_value' => $proposal_data->city,
        '#attributes' => array(
            'placeholder' => t('Enter your city name')
        ),
        '#states' => array(
            'visible' => array(
                ':input[name="country"]' => array(
                    'value' => 'Others'
                )
            )
        )
    );
    $form['all_state'] = array(
        '#type' => 'select',
        '#title' => t('State'),
        '#selected' => array(
            '' => '-select-'
        ),
        '#options' => _list_of_states(),
        '#default_value' => $proposal_data->state,
        '#validated' => TRUE,
        '#states' => array(
            'visible' => array(
                ':input[name="country"]' => array(
                    'value' => 'India'
                )
            )
        )
    );
    $form['city'] = array(
        '#type' => 'select',
        '#title' => t('City'),
        '#default_value' => $proposal_data->city,
        '#options' => _list_of_cities(),
        '#states' => array(
            'visible' => array(
                ':input[name="country"]' => array(
                    'value' => 'India'
                )
            )
        )
    );
    $form['pincode'] = array(
        '#type' => 'textfield',
        '#title' => t('Pincode'),
        '#size' => 30,
        '#maxlength' => 6,
        '#required' => False,
        '#default_value' => $proposal_data->pincode,
        '#attributes' => array(
            'placeholder' => 'Enter pincode....'
        )
    );
    /***************************************************************************/
    $form['hr'] = array(
        '#type' => 'item',
        '#markup' => '<hr>'
    );
    $form['faculty'] = array(
        '#type' => 'hidden',
        '#title' => t('College Teacher/Professor'),
        '#size' => 30,
        '#maxlength' => 100,
        '#required' => TRUE,
        '#default_value' => $proposal_data->faculty
    );
    $form['reviewer'] = array(
        '#type' => 'hidden',
        '#title' => t('Reviewer'),
        '#size' => 30,
        '#maxlength' => 100,
        '#default_value' => $proposal_data->reviewer
    );
    $form['completion_date'] = array(
        '#type' => 'textfield',
        '#title' => t('Expected Date of Completion'),
        '#description' => t('Input date format should be DD-MM-YYYY. Eg: 23-03-2011'),
        '#size' => 10,
        '#maxlength' => 10,
        '#default_value' => date('d-m-Y', $proposal_data->completion_date)
    );
    $form['version'] = array(
        '#type' => 'textfield',
        '#title' => t('OpenModelica Version'),
        '#size' => 10,
        '#maxlength' => 20,
        '#default_value' => $proposal_data->openmodelica_version
    );
    $form['operating_system'] = array(
        '#type' => 'textfield',
        '#title' => t('Operating System'),
        '#size' => 30,
        '#maxlength' => 50,
        '#default_value' => $proposal_data->operating_system
    );
    $form['preference1'] = array(
        '#type' => 'fieldset',
        '#title' => t('Book Preference 1'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE
    );
    $form['preference1']['book1'] = array(
        '#type' => 'textfield',
        '#title' => t('Title of the book'),
        '#size' => 30,
        '#maxlength' => 100,
        '#required' => TRUE,
        '#default_value' => $preference1_data->book
    );
    $form['preference1']['author1'] = array(
        '#type' => 'textfield',
        '#title' => t('Author Name'),
        '#size' => 30,
        '#maxlength' => 100,
        '#required' => TRUE,
        '#default_value' => $preference1_data->author
    );
    $form['preference1']['isbn1'] = array(
        '#type' => 'textfield',
        '#title' => t('ISBN No'),
        '#size' => 30,
        '#maxlength' => 25,
        '#required' => TRUE,
        '#default_value' => $preference1_data->isbn
    );
    $form['preference1']['publisher1'] = array(
        '#type' => 'textfield',
        '#title' => t('Publisher & Place'),
        '#size' => 30,
        '#maxlength' => 50,
        '#required' => TRUE,
        '#default_value' => $preference1_data->publisher
    );
    $form['preference1']['edition1'] = array(
        '#type' => 'textfield',
        '#title' => t('Edition'),
        '#size' => 4,
        '#maxlength' => 2,
        '#required' => TRUE,
        '#default_value' => $preference1_data->edition
    );
    $form['preference1']['year1'] = array(
        '#type' => 'textfield',
        '#title' => t('Year of pulication'),
        '#size' => 4,
        '#maxlength' => 4,
        '#required' => TRUE,
        '#default_value' => $preference1_data->year
    );
    if ($preference2_data) {
        $form['preference2'] = array(
            '#type' => 'fieldset',
            '#title' => t('Book Preference 2'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE
        );
        $form['preference2']['book2'] = array(
            '#type' => 'textfield',
            '#title' => t('Title of the book'),
            '#size' => 30,
            '#maxlength' => 100,
            '#required' => TRUE,
            '#default_value' => $preference2_data->book
        );
        $form['preference2']['author2'] = array(
            '#type' => 'textfield',
            '#title' => t('Author Name'),
            '#size' => 30,
            '#maxlength' => 100,
            '#required' => TRUE,
            '#default_value' => $preference2_data->author
        );
        $form['preference2']['isbn2'] = array(
            '#type' => 'textfield',
            '#title' => t('ISBN No'),
            '#size' => 30,
            '#maxlength' => 25,
            '#required' => TRUE,
            '#default_value' => $preference2_data->isbn
        );
        $form['preference2']['publisher2'] = array(
            '#type' => 'textfield',
            '#title' => t('Publisher & Place'),
            '#size' => 30,
            '#maxlength' => 50,
            '#required' => TRUE,
            '#default_value' => $preference2_data->publisher
        );
        $form['preference2']['edition2'] = array(
            '#type' => 'textfield',
            '#title' => t('Edition'),
            '#size' => 4,
            '#maxlength' => 2,
            '#required' => TRUE,
            '#default_value' => $preference2_data->edition
        );
        $form['preference2']['year2'] = array(
            '#type' => 'textfield',
            '#title' => t('Year of pulication'),
            '#size' => 4,
            '#maxlength' => 4,
            '#required' => TRUE,
            '#default_value' => $preference2_data->year
        );
    }
    if ($preference3_data) {
        $form['preference3'] = array(
            '#type' => 'fieldset',
            '#title' => t('Book Preference 3'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE
        );
        $form['preference3']['book3'] = array(
            '#type' => 'textfield',
            '#title' => t('Title of the book'),
            '#size' => 30,
            '#maxlength' => 100,
            '#required' => TRUE,
            '#default_value' => $preference3_data->book
        );
        $form['preference3']['author3'] = array(
            '#type' => 'textfield',
            '#title' => t('Author Name'),
            '#size' => 30,
            '#maxlength' => 100,
            '#required' => TRUE,
            '#default_value' => $preference3_data->author
        );
        $form['preference3']['isbn3'] = array(
            '#type' => 'textfield',
            '#title' => t('ISBN No'),
            '#size' => 30,
            '#maxlength' => 25,
            '#required' => TRUE,
            '#default_value' => $preference3_data->isbn
        );
        $form['preference3']['publisher3'] = array(
            '#type' => 'textfield',
            '#title' => t('Publisher & Place'),
            '#size' => 30,
            '#maxlength' => 50,
            '#required' => TRUE,
            '#default_value' => $preference3_data->publisher
        );
        $form['preference3']['edition3'] = array(
            '#type' => 'textfield',
            '#title' => t('Edition'),
            '#size' => 4,
            '#maxlength' => 2,
            '#required' => TRUE,
            '#default_value' => $preference3_data->edition
        );
        $form['preference3']['year3'] = array(
            '#type' => 'textfield',
            '#title' => t('Year of pulication'),
            '#size' => 4,
            '#maxlength' => 4,
            '#required' => TRUE,
            '#default_value' => $preference3_data->year
        );
    }
    /* hidden fields */
    $form['hidden_preference_id1'] = array(
        '#type' => 'hidden',
        '#value' => $preference1_data->id
    );
    /* $form['hidden_preference_id2'] = array(
    '#type' => 'hidden',
    '#value' => $preference2_data->id
    );
    $form['hidden_preference_id3'] = array(
    '#type' => 'hidden',
    '#value' => $preference3_data->id
    );*/
    $form['hidden_proposal_id'] = array(
        '#type' => 'hidden',
        '#value' => $proposal_id
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'item',
        '#markup' => l(t('Cancel'), 'textbook-companion/manage-proposal')
    );
    return $form;
}
function proposal_edit_form_validate($form, &$form_state)
{
    if ($form_state['values']['book1'] && $form_state['values']['author1']) {
        $bk1 = trim($form_state['values']['book1']);
        $auth1 = trim($form_state['values']['author1']);
        if (_dir_name($bk1, $auth1, $form_state['values']['hidden_preference_id1']) != NULL) {
            $form_state['values']['dir_name1'] = _dir_name($bk1, $auth1, $form_state['values']['hidden_preference_id1']);
        }
    }
    /*if ($form_state['values']['book2'] && $form_state['values']['author2'])
    {
    $bk2 = trim($form_state['values']['book2']);
    $auth2 = trim($form_state['values']['author2']);
    
    if (_dir_name($bk2, $auth2, $form_state['values']['hidden_preference_id2']) != NULL)
    {
    $form_state['values']['dir_name2'] = _dir_name($bk2, $auth2, $form_state['values']['hidden_preference_id2']);
    }
    }
    if ($form_state['values']['book3'] && $form_state['values']['author3'])
    {
    $bk3 = trim($form_state['values']['book3']);
    $auth3 = trim($form_state['values']['author3']);
    
    if (_dir_name($bk3, $auth3, $form_state['values']['hidden_preference_id3']) != NULL)
    {
    $form_state['values']['dir_name3'] = _dir_name($bk3, $auth3, $form_state['values']['hidden_preference_id3']);
    }
    }
    /* mobile */
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state['values']['mobile']))
        form_set_error('mobile', t('Invalid mobile number'));
    /* date of completion */
    if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $form_state['values']['completion_date']))
        form_set_error('completion_date', t('Invalid expected date of completion'));
    list($d, $m, $y) = explode('-', $form_state['values']['completion_date']);
    $d = (int) $d;
    $m = (int) $m;
    $y = (int) $y;
    if (!checkdate($m, $d, $y))
        form_set_error('completion_date', t('Invalid expected date of completion'));
    //if (mktime(0, 0, 0, $m, $d, $y) <= time())
    //form_set_error('completion_date', t('Expected date of completion should be in future'));  
    /* edition */
    if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state['values']['edition1']))
        form_set_error('edition1', t('Invalid edition for Book Preference 1'));
    /* year of publication */
    if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state['values']['year1']))
        form_set_error('year1', t('Invalid year of pulication for Book Preference 1'));
    /* year of publication */
    $cur_year = date('Y');
    if ((int) $form_state['values']['year1'] > $cur_year)
        form_set_error('year1', t('Year of pulication should be not in the future for Book Preference 1'));
    /* isbn */
    if (!preg_match('/^[0-9\-xX]+$/', $form_state['values']['isbn1']))
        form_set_error('isbn1', t('Invalid ISBN for Book Preference 1'));
    if ($form_state['values']['version'] == 'olderversion') {
        if ($form_state['values']['older'] == '') {
            form_set_error('older', t('Please provide valid version'));
        }
    }
    return;
}
function proposal_edit_form_submit($form, &$form_state)
{
    /* completion date to timestamp */
    list($d, $m, $y) = explode('-', $form_state['values']['completion_date']);
    $completion_date_timestamp = mktime(0, 0, 0, $m, $d, $y);
    $proposal_id = $form_state['values']['hidden_proposal_id'];
    if ($form_state['values']['version'] == 'olderversion') {
        $form_state['values']['version'] = $form_state['values']['older'];
    }
    if ($form_state['values']['country'] == 'other') {
        $form_state['values']['country'] = $form_state['values']['other_country'];
        $form_state['values']['all_state'] = $form_state['values']['other_state'];
    }
    $query = db_update('textbook_companion_proposal');
    $query->fields(array(
        'full_name' => $form_state['values']['full_name'],
        'mobile' => $form_state['values']['mobile'],
        'how_project' => $form_state['values']['how_project'],
        'course' => $form_state['values']['course'],
        'branch' => $form_state['values']['branch'],
        'university' => $form_state['values']['university'],
        'city' => $form_state['values']['city'],
        'pincode' => $form_state['values']['pincode'],
        'state' => $form_state['values']['all_state'],
        'country' => $form_state['values']['country'],
        'faculty' => $form_state['values']['faculty'],
        'reviewer' => $form_state['values']['reviewer'],
        'completion_date' => $completion_date_timestamp,
        'operating_system' => $form_state['values']['operating_system'],
        'openmodelica_version' => $form_state['values']['version']
    ));
    $query->condition('id', $proposal_id);
    $num_updated = $query->execute();
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('pref_number', 1);
    $query->range(0, 1);
    $preference1_q = $query->execute();
    $preference1_data = $preference1_q->fetchObject();
    $preference1_id = $preference1_data->id;
    if ($preference1_data) {
        del_book_pdf($preference1_data->id);
        RenameDir($preference1_id, $form_state['values']['dir_name1']);
        $query = db_update('textbook_companion_preference');
        $query->fields(array(
            'book' => $form_state['values']['book1'],
            'author' => $form_state['values']['author1'],
            'isbn' => $form_state['values']['isbn1'],
            'publisher' => $form_state['values']['publisher1'],
            'edition' => $form_state['values']['edition1'],
            'year' => $form_state['values']['year1'],
            'directory_name' => $form_state['values']['dir_name1']
        ));
        $query->condition('id', $preference1_id);
        $num_updated = $query->execute();
    }
    /**************************************************************/
    /**$query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('pref_number', 2);
    $query->range(0, 1);
    $preference2_q = $query->execute();
    $preference2_data = $preference2_q->fetchObject();
    $preference2_id = $preference2_data->id;
    if ($preference2_data)
    {
    del_book_pdf($preference2_data->id);        
    RenameDir($preference2_id, $form_state['values']['dir_name2']);
    $query = db_update('textbook_companion_preference');
    $query->fields(array(
    'book' => $form_state['values']['book2'],
    'author' => $form_state['values']['author2'],
    'isbn' => $form_state['values']['isbn2'],
    'publisher' => $form_state['values']['publisher2'],
    'edition' => $form_state['values']['edition2'],
    'year' => $form_state['values']['year2'],
    'directory_name' => $form_state['values']['dir_name2']
    ));
    $query->condition('id', $preference2_id);
    $num_updated = $query->execute();
    }
    /*****************************************************************/
    /**$query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('pref_number', 3);
    $query->range(0, 1);
    $preference3_q = $query->execute();
    $preference3_data = $preference3_q->fetchObject();
    $preference3_id = $preference3_data->id;
    if ($preference3_data)
    {
    del_book_pdf($preference3_data->id);        
    RenameDir($preference3_id, $form_state['values']['dir_name3']);
    $query = db_update('textbook_companion_preference');
    $query->fields(array(
    'book' => $form_state['values']['book3'],
    'author' => $form_state['values']['author3'],
    'isbn' => $form_state['values']['isbn3'],
    'publisher' => $form_state['values']['publisher3'],
    'edition' => $form_state['values']['edition3'],
    'year' => $form_state['values']['year3'],
    'directory_name' => $form_state['values']['dir_name3']
    ));
    $query->condition('id', $preference3_id);
    $num_updated = $query->execute();
    }**/
    drupal_set_message(t('Proposal Updated'), 'status');
}
/******************************************************************************/
/**************************** CATEGORY EDIT FORM ******************************/
/******************************************************************************/
function category_edit_form($from, $form_state)
{
    /* get current proposal */
    $preference_id = arg(3);
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $result = $query->execute();
    if (!$preference_data) {
        drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
        drupal_goto('textbook-companion/manage-proposal/category');
        return;
    }
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
    $form['category'] = array(
        '#type' => 'select',
        '#title' => t('Category'),
        '#options' => _tbc_list_of_categories(),
        '#required' => TRUE,
        '#default_value' => $preference_data->category
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'markup',
        '#value' => l(t('Cancel'), 'textbook-companion/manage-proposal/category')
    );
    return $form;
}
function category_edit_form_submit($form, &$form_state)
{
    /* get current proposal */
    $preference_id = (int) arg(3);
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $preference_id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $preference_q = $query->execute();
    $preference_data = $preference_q->fetchObject();
    if (!$preference_data) {
        drupal_set_message(t('Invalid book selected. Please try again.'), 'error');
        drupal_goto('textbook-companion/manage-proposal/category');
        return;
    }
    /*db_query("UPDATE {textbook_companion_preference} SET category = %d WHERE id = %d", $form_state['values']['category'], $preference_data->id);*/
    $query = db_update('textbook_companion_preference');
    $query->fields(array(
        'category' => $form_state['values']['category']
    ));
    $query->condition('id', $preference_data->id);
    $num_updated = $query->execute();
    drupal_set_message(t('Book Category Updated'), 'status');
    drupal_goto('textbook-companion/manage-proposal/category');
}
/****************************************************************/
/*                     Data entry forms                         */
/****************************************************************/
function _data_entry_proposal_all()
{
    /* get pending proposals to be approved */
    $proposal_rows = array();
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 ORDER BY book ASC");*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approval_status', 1);
    $query->orderBy('book', 'ASC');
    $preference_q = $query->execute();
    $sno = 1;
    while ($preference_data = $preference_q->fetchObject()) {
        $proposal_rows[] = array(
            $sno++,
            $preference_data->book,
            $preference_data->author,
            $preference_data->isbn,
            l('Edit', 'textbook-companion/dataentry-edit/' . $preference_data->id)
        );
    }
    /* check if there are any pending proposals */
    if (!$proposal_rows) {
        drupal_set_message(t('There are no proposals.'), 'status');
        return '';
    }
    $proposal_header = array(
        'SNO',
        'Title of the Book',
        'Author',
        'ISBN',
        ''
    );
    $output = theme('table', array(
        'headers' => $proposal_header,
        'rows' => $proposal_rows
    ));
    return $output;
}
function dataentry_edit($id = NULL)
{
    if ($id) {
        return drupal_get_form('dataentry_edit_form', $id);
    } else {
        return 'Access denied';
    }
}
function dataentry_edit_form($form_state, $id)
{
    global $user;
    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $id);
    $preference_data = db_fetch_object($preference_q);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $id);
    $preference_q = $query->execute();
    $preference_data = $preference_q->fetchObject();
    $form['id'] = array(
        '#type' => 'hidden',
        '#required' => TRUE,
        '#value' => $id
    );
    $form['book'] = array(
        '#type' => 'textfield',
        '#title' => t('Title of the book'),
        '#size' => 30,
        '#maxlength' => 100,
        '#required' => TRUE,
        '#default_value' => $preference_data->book
    );
    $form['author'] = array(
        '#type' => 'textfield',
        '#title' => t('Author Name'),
        '#size' => 30,
        '#maxlength' => 100,
        '#required' => TRUE,
        '#default_value' => $preference_data->author
    );
    $form['isbn'] = array(
        '#type' => 'textfield',
        '#title' => t('ISBN No'),
        '#size' => 30,
        '#maxlength' => 25,
        '#required' => TRUE,
        '#attribute' => array(
            'readonly' => 'readonly'
        ),
        '#default_value' => $preference_data->isbn
    );
    $form['publisher'] = array(
        '#type' => 'textfield',
        '#title' => t('Publisher & Place'),
        '#size' => 30,
        '#maxlength' => 50,
        '#required' => TRUE,
        '#default_value' => $preference_data->publisher
    );
    $form['edition'] = array(
        '#type' => 'textfield',
        '#title' => t('Edition'),
        '#size' => 4,
        '#maxlength' => 2,
        '#required' => TRUE,
        '#default_value' => $preference_data->edition
    );
    $form['year'] = array(
        '#type' => 'textfield',
        '#title' => t('Year of pulication'),
        '#size' => 4,
        '#maxlength' => 4,
        '#required' => TRUE,
        '#default_value' => $preference_data->year
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    return $form;
}
function dataentry_edit_form_submit($form, &$form_state)
{
    /*db_query("UPDATE {textbook_companion_preference} SET book = '%s', author = '%s', isbn = '%s', publisher = '%s', edition = '%s', year = %d WHERE id = %d", $_POST['book'], $_POST['author'], $_POST['isbn'], $_POST['publisher'], $_POST['edition'], $_POST['year'], $_POST['id']);*/
    $query = db_update('textbook_companion_preference');
    $query->fields(array(
        'book' => $_POST['book'],
        'author' => $_POST['author'],
        'isbn' => $_POST['isbn'],
        'publisher' => $_POST['publisher'],
        'edition' => $_POST['edition'],
        'year' => $_POST['year']
    ));
    $query->condition('id', $_POST['id']);
    $num_updated = $query->execute();
    drupal_set_message('Book details updated successfully');
    drupal_goto('dataentry_book');
}
function _failed_all($preference_id = 0, $confirm = "")
{
    $page_content = "";
    if ($preference_id && $confirm == "yes") {
        /*$query = "
        SELECT *, pro.id as proposal_id FROM textbook_companion_proposal pro
        LEFT JOIN textbook_companion_preference pre ON pre.proposal_id = pro.id
        LEFT JOIN users usr ON usr.uid = pro.uid
        WHERE pre.id = {$preference_id}
        ";
        $result = db_query($query);
        $row = db_fetch_object($result);*/
        $query = db_select('textbook_companion_proposal', 'pro');
        $query->fields('*', array(
            ''
        ));
        $query->fields('pro', array(
            'id'
        ));
        $query->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
        $query->leftJoin('users', 'usr', 'usr.uid = pro.uid');
        $query->condition('pre.id', '$preference_id');
        $result = $query->execute();
        $row = $result->fetchObject();
        /* increment failed_reminder */
        /*$query = "
        UPDATE textbook_companion_proposal
        SET failed_reminder = failed_reminder + 1
        WHERE id = {$row->proposal_id}
        ";
        db_query($query);*/
        $query = db_update('textbook_companion_proposal');
        $query->fields(array(
            'failed_reminder' => 'failed_reminder + 1'
        ));
        $query->condition('id', '$row->proposal_id');
        $num_updated = $query->execute();
        /* sending mail */
        $to = $row->mail;
        $subject = "Failed to upload the TBC codes on time";
        $body = "
    <p>
      Dear {$row->name},<br><br>
      This is to inform you that you have failed to upload the TBC codes on time.<br>
      Please note that the time you have taken is way past the deadline as well.<br>
      Kindly upload the TBC codes on the interface within 5 days from now.<br>
      Failure to submit the same will result in disapproval of your work and cancellation of your internship.<br><br>
      Regards,<br>
      OpenModelica TBC Team,<br>
      FOSSEE.
    </p>
    ";
        $message = array(
            "to" => $to,
            "subject" => $subject,
            "body" => $body,
            "headers" => array(
                "From" => "contact-openmodelica@fossee.in",
                "Bcc" => "contact-openmodelica@fossee.in",
                "Content-Type" => "text/html; charset=UTF-8; format=flowed"
            )
        );
        drupal_mail_send($message);
        drupal_set_message("Reminder sent successfully.");
        drupal_goto("textbook-companion/manage-proposal/failed");
    } else if ($preference_id) {
        /*$query = "
        SELECT * FROM textbook_companion_preference pre
        LEFT JOIN textbook_companion_proposal pro ON pro.id = pre.proposal_id
        WHERE pre.id = {$preference_id}
        ";
        $result = db_query($query);
        $row = db_fetch_object($result);*/
        $query = db_select('textbook_companion_preference', 'pre');
        $query->fields('pre');
        $query->leftJoin('textbook_companion_proposal', 'pro', 'pro.id = pre.proposal_id');
        $query->condition('pre.id', $preference_id);
        $result = $query->execute();
        $row = $result->fetchObject();
        $page_content .= "Are you sure you want to notify?<br><br>";
        $page_content .= "Book: <b>{$row->book}</b><br>";
        $page_content .= "Author: <b>{$row->author}</b><br>";
        $page_content .= "Contributor: <b>{$row->full_name}</b><br>";
        $page_content .= "Expected Completion Date: <b>" . date("d-m-Y", $row->completion_date) . "</b><br><br>";
        $page_content .= l("Yes", "textbook-companion/manage-proposal/failed/{$preference_id}/yes") . " | ";
        $page_content .= l("Cancel", "textbook-companion/manage-proposal/failed");
    } else {
        /*$query = "
        SELECT * FROM textbook_companion_proposal pro
        LEFT JOIN textbook_companion_preference pre ON pre.proposal_id = pro.id
        LEFT JOIN users usr ON usr.uid = pro.uid
        WHERE pro.proposal_status = 1 AND pre.approval_status = 1 AND pro.completion_date < %d
        ORDER BY failed_reminder
        ";
        $result = db_query($query, time());*/
        $query = db_select('textbook_companion_proposal', 'pro');
        $query->fields('pro');
        $query->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
        $query->leftJoin('users', 'usr', 'usr.uid = pro.uid');
        $query->condition('pro.proposal_status', 1);
        $query->condition('pre.approval_status', 1);
        $query->condition('pro.completion_date', '%time()', '<');
        $query->orderBy('failed_reminder', 'ASC');
        $result = $query->execute();
        $headers = array(
            "Date of Submission",
            "Book",
            "Contributor Name",
            "Expected Completion Date",
            "Remainders",
            "Action"
        );
        $rows = array();
        while ($row = $result->fetchObject()) {
            $item = array(
                date("d-m-Y", $row->creation_date),
                "{$row->book}<br><i>by</i> {$row->author}",
                $row->name,
                date("d-m-Y", $row->completion_date),
                $row->failed_reminder,
                l("Remind", "textbook-companion/manage-proposal/failed/{$row->id}")
            );
            array_push($rows, $item);
        }
        $page_content .= theme('table', array(
            'header' => $headers,
            'rows' => $rows
        ));
    }
    return $page_content;
}
/***************************************************************************/
/*******Function to create readme file after book marked as completed*******/
/***************************************************************************/
function CreateReadmeFileTextbookCompanion($proposal_id)
{
    $result = db_query("
                       SELECT  tcc.id AS proposal_id,tcc.full_name, tcc.course, tcc.branch, tcc.university, tcp.id as pref_id, tcp.directory_name, tcp.* FROM textbook_companion_preference tcp JOIN textbook_companion_proposal tcc ON tcp.proposal_id= tcc.id WHERE  tcc.proposal_status = 3 AND tcp.approval_status = 1 AND tcc.id = :proposal_id
                       ", array(
        ":proposal_id" => $proposal_id
    ));
    $proposal_data = $result->fetchObject();
    $root_path = textbook_companion_path();
    $readme_file = fopen($root_path . $proposal_data->directory_name . "/README.txt", "w") or die('unable to open file');
    $txt = "";
    $txt .= "About The Contributor" . "\n\n";
    $txt .= "Contributed By: " . ucwords(strtolower($proposal_data->full_name)) . "\n";
    $txt .= "Course: " . ucwords(strtolower($proposal_data->course)) . "\n";
    $txt .= "Branch: " . ucwords(strtolower($proposal_data->branch)) . "\n";
    $txt .= "College/Institute/Organization: " . ucwords(strtolower($proposal_data->university)) . "\n\n";
    $txt .= "About The Book" . "\n\n";
    $txt .= "Book: " . ucwords(strtolower($proposal_data->book)) . "\n";
    $txt .= "Author: " . ucwords(strtolower($proposal_data->author)) . "\n";
    $txt .= "Publisher: " . ucwords(strtolower($proposal_data->publisher)) . "\n";
    $txt .= "Year Of Publication: " . $proposal_data->year . "\n";
    $txt .= "ISBN: " . $proposal_data->isbn . "\n";
    $txt .= "Edition: " . ucwords(strtolower($proposal_data->edition)) . "\n";
    $txt .= "\n" . "\n";
    $txt .= "Textbook Companion Project By FOSSEE, IIT Bombay" . "\n";
    fwrite($readme_file, $txt);
    fclose($readme_file);
    return;
}
function RenameDir($preference_id, $dir_name)
{
    $preference_id = $preference_id;
    $dir_name = $dir_name;
    $query = db_query("SELECT directory_name, proposal_id, id FROM textbook_companion_preference WHERE id = :preference_id", array(
        ':preference_id' => $preference_id
    ));
    $result = $query->fetchObject();
    if ($result != NULL) {
        $files = scandir(textbook_companion_path());
        $files_id_dir = textbook_companion_path() . $result->id;
        $file_dir = textbook_companion_path() . $result->directory_name;
        if (is_dir($file_dir)) {
            $new_directory_name = rename(textbook_companion_path() . $result->directory_name, textbook_companion_path() . $dir_name);
            CreateReadmeFileTextbookCompanion($result->proposal_id);
            return $new_directory_name;
        } else if (is_dir($files_id_dir)) {
            $new_directory_name = rename(textbook_companion_path() . $result->id, textbook_companion_path() . $dir_name);
            CreateReadmeFileTextbookCompanion($result->proposal_id);
            return $new_directory_name;
        } else {
            drupal_set_message('Can not rename the directory. If you are editing proposal before approving the proposal directory is not present because the code has been not uploaded yet. For more information please contact to administrator');
            return;
        }
    } else {
        drupal_set_message('Book names directory not present in databse');
        return;
    }
    return;
}
