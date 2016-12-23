<?php

function paper_submission_form($form_state, $proposal_id)
{
		global $user;
		$proposal_id = arg(2);
		
		/* get current proposal */

		/*$preference4_q = db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id=".$proposal_id);*/

		$query = db_select('textbook_companion_paper');
		$query->fields('textbook_companion_paper');
		$query->condition('proposal_id', $proposal_id);
		$preference4_q = $query->execute();

		$form1=0;
		$form2=0;
		$form3=0;
		$form4=0;
  		if($data = $preference4_q->fetchObject())
  		{
			$form1 = $data->internship_form;
			$form2 = $data->copyright_form;
			$form3 = $data->undertaking_form;
			$form4 = $data->reciept_form;
		}
		else
		{
			$query = "Insert into {textbook_companion_paper} (proposal_id) values (:proposal_id)";
			$args = array(
			":proposal_id"=>$proposal_id, 
				);
			$result = db_query($query, $args, array('return' => Database::RETURN_INSERT_ID));	
		}
		$form['proposal_id'] =array(
			'#type' => 'hidden',
			'#default_value' => $proposal_id,
		);
		$form['internshipform'] = array(
			'#type' => 'checkbox',
			'#title' => t('Recieved Internship Application'),
			'#description' => t('Check if the Internship Application has been recieved.'),
			'#default_value' => $form1,
		);
		$form['copyrighttransferform'] = array(
			'#type' => 'checkbox',
			'#title' => t('Recieved Copyright Transfer Form'),
			'#description' => t('Check if the Copyright Transfer Form has been recieved.'),
			'#default_value' => $form2,
		);
		$form['undertakingform'] = array(
			'#type' => 'checkbox',
			'#title' => t('Recieved Undertaking Form'),
			'#description' => t('Check if the Undertaking Form has been recieved.'),
			'#default_value' => $form3,
		);
		$form['recieptform'] = array(
			'#type' => 'checkbox',
			'#title' => t('Recieved Reciept Form'),
			'#description' => t('Check if the Reciept Form has been recieved.'),
			'#default_value' => $form4,
		);  
		$form['submit'] = array(
			'#type' => 'submit',
			'#value' => t('Send Email')
		);
		$form['cancel'] = array(
			'#type' => 'markup',
			'#value' => l(t('Cancel'), 'manage_proposal/all'),
		);
	return $form;
}

function paper_submission_form_submit($form, &$form_state)
{

		/*$query ="UPDATE {textbook_companion_paper} SET internship_form = ".$form_state['values']['internshipform'].", copyright_form = ".$form_state['values']['copyrighttransferform'].", undertaking_form= ".$form_state['values']['undertakingform'].", reciept_form= ".$form_state['values']['recieptform']." WHERE proposal_id = ".$form_state['values']['proposal_id'];
		db_query($query);*/

	$query = db_update('textbook_companion_paper');
	$query->fields(array(
  	'internship_form' => $form_state[ values ][ internshipform ],
  	'copyright_form' => $form_state[ values ][ copyrighttransferform ],
  	'undertaking_form' => $form_state[ values ][ undertakingform ],
  	'reciept_form' => $form_state[ values ][ recieptform ],
	));
	$query->condition('proposal_id', $form_state['values']['proposal_id']);
	$num_updated = $query->execute();

		/************************************************
		Check For the Internship Form is checked or not
		************************************************/
		if ($form_state['values']['internshipform'] == 1)
		{
			/* sending email */
			$book_user = user_load($proposal_data->uid);
			$param['proposal_completed']['proposal_id'] = $proposal_id;
			$param['proposal_completed']['user_id'] = $proposal_data->uid;
			$email_to = $book_user->mail;
			if (!drupal_mail('textbook_companion', 'internship_form', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
				drupal_set_message('Error sending email message.', 'error');
			drupal_set_message('Internship Form for Book proposal has been recieved. User has been notified .', 'status');
		}
		else
		{
			if (!drupal_mail('textbook_companion', 'internship_form_not', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
			drupal_set_message('Error sending email message.', 'error');
		drupal_set_message('Internship Form for Book proposal has not been recieved. User has been notified .', 'status');
		}
  
		/************************************************
		Check For the Copyright Form is checked or not
		************************************************/

		if ($form_state['values']['copyrighttransferform'] == 1)
		{
			/* sending email */
			$book_user = user_load($proposal_data->uid);
			$param['proposal_completed']['proposal_id'] = $proposal_id;
			$param['proposal_completed']['user_id'] = $proposal_data->uid;
			$email_to = $book_user->mail;
			if (!drupal_mail('textbook_companion', 'copyrighttransfer_form', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
				drupal_set_message('Error sending email message.', 'error');
			drupal_set_message('Copyright Form for Book proposal has been recieved. User has been notified .', 'status');
		}
		else
		{
			if (!drupal_mail('textbook_companion', 'copyrighttransfer_form_not', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
				drupal_set_message('Error sending email message.', 'error');
			drupal_set_message('Copyright Transfer Form for Book proposal has not been recieved. User has been notified .', 'status');
		}
  
		/************************************************
		Check For the Undertaking Form is checked or not
		************************************************/

		if ($form_state['values']['undertakingform'] == 1)
		{
			/* sending email */
			$book_user = user_load($proposal_data->uid);
			$param['proposal_completed']['proposal_id'] = $proposal_id;
			$param['proposal_completed']['user_id'] = $proposal_data->uid;
			$email_to = $book_user->mail;
			if (!drupal_mail('textbook_companion', 'undertakingform_form', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
				drupal_set_message('Error sending email message.', 'error');
			drupal_set_message('Undertaking Form for Book proposal has been recieved. User has been notified .', 'status');
		}
		else
		{
			if (!drupal_mail('textbook_companion', 'undertakingform_form_not', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
				drupal_set_message('Error sending email message.', 'error');
			drupal_set_message('Undertaking Form for Book proposal has not been recieved. User has been notified .', 'status');
		}

drupal_set_message(t('Proposal Updated'), 'status');
}


/*function proposal_edit_form_validate($form, &$form_state)
{
		/* mobile */
		/*if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state['values']['mobile']))
			form_set_error('mobile', t('Invalid mobile number'));

		/* date of completion */
		/*if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $form_state['values']['completion_date']))
			form_set_error('completion_date', t('Invalid expected date of completion'));

		list($d, $m, $y) = explode('-', $form_state['values']['completion_date']);
		$d = (int)$d; $m = (int)$m; $y = (int)$y;
		
		if (!checkdate($m, $d, $y))
			form_set_error('completion_date', t('Invalid expected date of completion'));
		
		if (mktime(0, 0, 0, $m, $d, $y) <= time())
			form_set_error('completion_date', t('Expected date of completion should be in future'));  

		/* edition */
		/*if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state['values']['edition1']))
			form_set_error('edition1', t('Invalid edition for Book Preference 1'));

		if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state['values']['edition2']))
			form_set_error('edition2', t('Invalid edition for Book Preference 2'));
		if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state['values']['edition3']))
			form_set_error('edition3', t('Invalid edition for Book Preference 3'));

		/* year of publication */
		/*if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state['values']['year1']))
			form_set_error('year1', t('Invalid year of pulication for Book Preference 1'));

		if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state['values']['year2']))
			form_set_error('year2', t('Invalid year of pulication for Book Preference 2'));

		if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state['values']['year3']))
			form_set_error('year3', t('Invalid year of pulication for Book Preference 3'));

		/* year of publication */
		/*$cur_year = date('Y');
		if ((int)$form_state['values']['year1'] > $cur_year)
			form_set_error('year1', t('Year of pulication should be not in the future for Book Preference 1'));
		
		if ((int)$form_state['values']['year2'] > $cur_year)
			form_set_error('year2', t('Year of pulication should be not in the future for Book Preference 2'));
		
		if ((int)$form_state['values']['year3'] > $cur_year)
			form_set_error('year3', t('Year of pulication should be not in the future for Book Preference 3'));

		/* isbn */
		/*if (!preg_match('/^[0-9\-xX]+$/', $form_state['values']['isbn1']))
			form_set_error('isbn1', t('Invalid ISBN for Book Preference 1'));
		
		if (!preg_match('/^[0-9\-xX]+$/', $form_state['values']['isbn2']))
			form_set_error('isbn2', t('Invalid ISBN for Book Preference 2'));
		
		if (!preg_match('/^[0-9\-xX]+$/', $form_state['values']['isbn3']))
			form_set_error('isbn3', t('Invalid ISBN for Book Preference 3'));

return;
}*/


function cheque_contct_form()
{
	global $user;
        
	/*$preference4_q = db_query("SELECT id FROM {textbook_companion_proposal} WHERE uid=".$user->uid);*/

	$query = db_select('textbook_companion_proposal');
	$query->fields('id', array(''));
	$query->condition('uid', $user->uid);
	$result = $query->execute();
	$data = $result->fetchObject();

	$form1 = $data->id; 
	
	if($user->uid)
    {
		$form['#redirect'] = FALSE;
		
		$form['search'] = array(
			'#type' => 'textfield',
			'#title' => t('Search'),
			'#size' => 48,
		);
		
		$form['submit'] = array(
			'#type' => 'submit',
			'#value' => t('Search')
		);

		$form['cancel'] = array(
			'#type' => 'markup',
			'#value' => l(t('Cancel'), ''),
		);

		$form['submit2'] = array(
			'#type' => 'markup',
			'#value' => l(t('Generate Report'), 'cheque_contct/report'),
			'#attributes' => array('id' => 'perm_report'),
		);

		/*$search_q = db_query("SELECT * FROM textbook_companion_proposal p,textbook_companion_cheque c WHERE c.address_con = 'Submitted' AND (p.id = c.proposal_id)");*/
		$query = db_select('textbook_companion_proposal', 'p');
		$query->join('textbook_companion_cheque','c','p.id = c.proposal_id');
		$query->fields('p',array('textbook_companion_proposal'));
		$query->fields('c',array('textbook_companion_cheque'));
		$query->condition('c.address_con','Submitted');
		$result = $query->execute();
		
		while ($search_data = $result->fetchObject())
    	{
			$search_rows[] = array(l($search_data->full_name, 'cheque_contct/status/' . $search_data->proposal_id),$search_data->address_con,$search_data->cheque_no,$search_data->cheque_dispatch_date); 
		}
		if ($search_rows)
		{
			$search_header = array('Name Of The Student', 'Application Form Status', 'Cheque No', 'Cheque Clearance Date');
			$output .= theme('table',array('headers'=>$search_header, 'rows'=>$search_rows));
			$form['search_results'] = array(
				'#type' => 'item',
				'#title' => $_POST['search'] ,
				'#markup' => $output,
			);
		}
		else
		{
			$form['search_results'] = array(
				'#type' => 'item',
				'#title' => t('Search results for "') . $_POST['search'] . '"',
				'#markup' => 'No results found',
			);
		}
		if ($_POST)
	    {
			$output = '';
			$search_rows = array();
			$search_quert = '';

			/*$search_q = db_query("SELECT * FROM textbook_companion_proposal p,textbook_companion_cheque c WHERE c.address_con = 'Submitted' AND (p.id = c.proposal_id) AND (p.full_name LIKE '%%%s%%')", $_POST['search']);*/
			$query = db_select('textbook_companion_proposal', 'p');
			$query->join('textbook_companion_cheque','c','p.id = c.proposal_id');
			$query->fields('p',array('textbook_companion_proposal'));
			$query->fields('c',array('textbook_companion_cheque'));
			$query->condition('c.address_con', 'Submitted');
			$query->condition('p.full_name', '%%'.$_POST['search'].'%%', 'LIKE');
			$result = $query->execute();


			while ($search_data = $result->fetchObject())
			{
				$search_rows[] = array(l($search_data->full_name, 'cheque_contct/status/' . $search_data->proposal_id),$search_data->address_con,$search_data->cheque_no,$search_data->cheque_dispatch_date); 
    		}
			if ($search_rows)
    		{	
		    		$search_header = array('Name Of The Student', 'Application Form Status', 'Cheque No', 'Cheque Clearance Date');
		    		$output .= theme('table',array('headers'=>$search_header,'rows'=>$search_rows));
		    		$form['search_results'] = array(
						'#type' => 'item',
						'#title' => t('Search results for "') . $_POST['search'] . '"',
						'#markup' => $output,
					);
			}
			else
			{
					$form['search_results'] = array(
						'#type' => 'item',
						'#title' => t('Search results for "') . $_POST['search'] . '"',
						'#markup' => 'No results found',
					);
			}
  		}
  return $form;
	}
	else
	{
		/*$preference5_q = db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id=".$form1);
		$data1 = db_fetch_object($preference5_q);*/
		$query = db_select('textbook_companion_paper');
		$query->fields('textbook_companion_paper');
		$query->condition('proposal_id', $form1);
		$result = $query->execute();
		$data1=$result->fetchObject();

		$form2 = $data1->internship_form;
		$form3 = $data1->copyright_form;
		$form4 = $data1->undertaking_form;
		$form5 = $data1->reciept_form;

		/*$chq_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id=".$form1);
		$data_chq = db_fetch_object($chq_q);*/

		$query = db_select('textbook_companion_proposal');
		$query->fields('textbook_companion_proposal');
		$query->condition('id', $form1);
		$result = $query->execute();
		$data_chq=$result->fetchObject();

		$form9 = $data_chq->full_name;
		$form8 = $data->how_project;
		$form10 = $data_chq->mobile;
		$form11 = $data_chq->course;
		$form12 = $data_chq->branch;
		$form13 = $data_chq->university;
		if($form2&&$form3&&$form4&&$form5)
		{
			$form['full_name'] = array(
				'#type' => 'textfield',
				'#title' => t('Full Name'),
				'#size' => 30,
				'#maxlength' => 50,
				'#default_value' => $form9,
			);
			$form['mobile'] = array(
				'#type' => 'textfield',
				'#title' => t('Mobile No.'),
				'#size' => 30,
				'#maxlength' => 15,
				'#default_value' => $form10,
			);
			$form['how_project'] = array(
				'#type' => 'select',
				'#title' => t('How did you come to know about this project'),
				'#options' => array('eSim Website' => 'eSim Website',
					'Friend' => 'Friend',
					'Professor/Teacher' => 'Professor/Teacher',
					'Mailing List' => 'Mailing List',
					'Poster in my/other college' => 'Poster in my/other college',
					'Others' => 'Others'),
				'#default_value' => $form8,
			);
			$form['course'] = array(
				'#type' => 'textfield',
				'#title' => t('Course'),
				'#size' => 30,
				'#maxlength' => 50,
				'#default_value' => $form11,
			);
			$form['branch'] = array(
				'#type' => 'select',
				'#title' => t('Department/Branch'),
				'#options' => array('Electrical Engineering' => 'Electrical Engineering',
                        'Electronics Engineering' => 'Electronics Engineering',
                        'Computer Engineering' => 'Computer Engineering',
                        'Chemical Engineering' => 'Chemical Engineering',
                        'Instrumentation Engineering' => 'Instrumentation Engineering',
                        'Mechanical Engineering' => 'Mechanical Engineering',
                        'Civil Engineering' => 'Civil Engineering',
                        'Physics' => 'Physics',
                        'Mathematics' => 'Mathematics',
                        'Others' => 'Others'),
   				'#default_value' => $form12,
   			);

			$form['university'] = array(
				'#type' => 'textfield',
				'#title' => t('University/Institute'),
				'#size' => 30,
				'#maxlength' => 100,
				'#default_value' => $form13,
			);
			$form['addressforcheque'] = array(
				'#type' => 'textfield',
				'#title' => t('Address For Mailing Cheque'),
				//'#required' => TRUE,
				'#size' => 30,
				'#maxlength' => 100,
			);
			$form['submit'] = array(
				'#type' => 'submit',
				'#value' => t('Submit')
			);
			$form['cancel'] = array(
				'#type' => 'markup',
				'#value' => t('Cancel')
			);
		}
		if (!$form2)
		{ 
			drupal_set_message(t('Internship Form has not been recieved.'), 'error');
		}
		if(!$form3)
		{ 
			drupal_set_message(t('Copyright Form has not been recieved.'), 'error');
		}
		if(!$form4)
		{ 
			drupal_set_message(t('Undertaking Form has not been recieved.'), 'error');
		}
	return $form;
	}
}


function cheque_status_form($form_state, $proposal_id)
{
	global $user;

	/* get current proposal */
	$proposal_id = arg(2);

	/*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id =".$proposal_id);*/
		$query = db_select('textbook_companion_proposal');
		$query->fields('textbook_companion_proposal');
		$query->condition('id', $proposal_id);
		$proposal_q = $query->execute();

	/*$proposal_q1 = db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id =".$proposal_id);*/
		$query = db_select('textbook_companion_cheque');
		$query->fields('textbook_companion_cheque');
		$query->condition('proposal_id', $proposal_id);
		$proposal_q1 = $query->execute();


	$proposal_data1 = $proposal_q1->fetchObject();
	if (!$proposal_data = $proposal_q->fetchObject())
	{
		drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
		drupal_goto('manage_proposal');
		return;
	}
	$form['proposal_id'] =array(
		'#type' => 'hidden',
		'#default_value' => $proposal_id,
	);

	/*$empty = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = ".$proposal_id);*/
		$query = db_select('textbook_companion_proposal');
		$query->fields('textbook_companion_proposal');
		$query->condition('id', $proposal_id);
		$empty = $query->execute();

	if(!$empty)
	{
		/*$prop =db_query("insert into {textbook_companion_cheque} (proposal_id) values(%d)",$proposal_id);*/

		$query = "insert into {textbook_companion_cheque} (proposal_id) values (:proposal_id)";
		$args = array(
		":proposal_id"=>$proposal_id, 
			);
		$result = db_query($query, $args, array('return' => Database::RETURN_INSERT_ID));	
	}
	$form['candidate_detail'] = array(
		'#type' => 'fieldset',
		'#title' => t('Candidate Details'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
		'#attributes' => array('id' => 'candidate_detail'),
	);
	$form['candidate_detail']['full_name'] = array(
		'#type' => 'item',
		'#markup' => $proposal_data->full_name,
		'#title' => t('Contributor Name'),
	);
	$form['candidate_detail']['email'] = array(
		'#type' => 'item',
		'#markup' => user_load($proposal_data->uid)->mail,
		'#title' => t('Email'),
	);
	$form['candidate_detail']['mobile'] = array(
		'#type' => 'item',
		'#markup' => $proposal_data->mobile,
		'#title' => t('Mobile'),
	);
	$form['candidate_detail']['alt_mobile'] = array(
		'#type' => 'item',
		'#markup' => $proposal_data1->alt_mobno,
		'#title' => t('Alternate Mobile No.'),
	);
	/*$form_q=db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id =".$proposal_id);
	$form_data=db_fetch_object($form_q);*/

	$query = db_select('textbook_companion_paper');
	$query->fields('textbook_companion_paper');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$form_data=$result->fetchObject();
	
	/* get book preference */
	$preference_html = '<ul>';

	/*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d ORDER BY pref_number ASC", $proposal_id);*/
	$query = db_select('textbook_companion_preference');
	$query->fields('textbook_companion_preference');
	$query->condition('proposal_id', $proposal_id);
	$query->orderBy('pref_number', 'ASC');
	$preference_q = $query->execute();

	while ($preference_data = $preference_q->fetchObject())
	{
		if ($preference_data->approval_status == 1)
			$preference_html .= '<li><strong>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')  - Approved Book</strong></li>';
		else
			$preference_html .= '<li>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')</li>';
	}
	$preference_html .= '</ul>';
	$form['book_preference_f'] = array(
		'#type' => 'fieldset',
		'#title' => t('Book Preferences/Application Status'),
		'#collapsible' => FALSE,
		'#collapsed' => FALSE,
		'#attributes' => array('id' => 'book_preference_f'),
	);
	$form['book_preference_f']['book_preference'] = array(
		'#type' => 'item',
		'#markup' => $preference_html,
		'#title' => t('Book Preferences'),
	);

	/*$chq_q=db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id = %d", $proposal_id);
	$chq_data=db_fetch_object($chq_q);*/
	$query = db_select('textbook_companion_cheque');
	$query->fields('textbook_companion_cheque');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$chq_data=$result->fetchObject();

	$form_html .= '<ul>';
	if($form_data->internship_form)
	{
		$form_html .= '<li><strong>Internship Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Internship Application </strong> Form Not Submitted </li>';
	}
	if($form_data->copyright_form)
	{
		$form_html .= '<li><strong>Copyright Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Copyright Application</strong> Form Not Submitted </li>';
	}
	if($form_data->undertaking_form)
	{
		$form_html .= '<li><strong>Undertaking Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Undertaking Application</strong> Form Not Submitted </li>';
	}
	$form_html .= '</ul>';
	$form['book_preference_f']['formsubmit'] = array(
		'#type' => 'item',
		'#markup' => $form_html,
		'#title' => t('Application Form Status'),
	);
	$form['stu_cheque_details'] = array(
		'#type' => 'fieldset',
		'#title' => t('Student Cheque Details'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
		'#attributes' => array('id' => 'stu_cheque_details'),
	);
	$form['tea_cheque_details'] = array(
		'#type' => 'fieldset',
		'#title' => t('Teacher Cheque Details'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
		'#attributes' => array('id' => 'tea_cheque_details'),
	);
	$form['perm_cheque_address'] = array(
		'#type' => 'fieldset',
		'#title' => t('Permanent Address'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
		'#attributes' => array('id' => 'perm_cheque_address'),
	);
	$form['temp_cheque_address'] = array(
		'#type' => 'fieldset',
		'#title' => t('Temporary Address'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE,
		'#attributes' => array('id' => 'temp_cheque_address'),
	);
	$form['cheque_delivery'] = array(
		'#type' => 'fieldset',
		'#title' => t('Cheque Delivery'),
		'#collapsible' => FALSE,
		'#collapsed' => FALSE,
		'#attributes' => array('id' => 'cheque_delivery'),
	);
	$form['commentf'] = array(
		'#type' => 'fieldset',
		'#title' => t('Remark'),
		'#collapsible' => FALSE,
		'#collapsed' => FALSE,
		'#attributes' => array('id' => 'commentf'),
	);
	/*$chq4_q = db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id=".$proposal_id);*/
	$query = db_select('textbook_companion_cheque');
	$query->fields('textbook_companion_cheque');
	$query->condition('proposal_id', $proposal_id);
	$chq4_q = $query->execute();
	
	$chq1 = '';
	$chq2 = '';
	$chq3 = '';
	$chq4 = '';
	$chq5 = '';
	$chq6 = '';
	$chq7 = '';
	$chq8 = '';
	$chq9 = '';
	$chq10 = '';
	$chq11 = '';
	$chq12 = '';
	$chq13 = '';
	$chq14 = '';
	$chq15 = '';
	$chq16 = '';
	$chq17 = '';
	if($chqe = $chq4_q->fetchObject())
	{
		$chq1 = $chqe->cheque_no;
		$chq2 = $chqe->address;
		$chq3 = $chqe->cheque_amt;
		$chq4 = $chqe->cheque_sent;
		$chq5 = $chqe->cheque_cleared;
		$chq6 = $chqe->perm_chq_address2;
		$chq7 = $chqe->perm_city;
		$chq8 = $chqe->perm_state;
		$chq9 = $chqe->perm_pincode;
		$chq10 = $chqe->temp_chq_address;
		$chq11 = $chqe->temp_chq_address2;
		$chq12 = $chqe->temp_city;
		$chq13 = $chqe->temp_state;
		$chq14 = $chqe->temp_pincode;
		$chq15 = $chqe->commentf;
		$chq16 = $chqe->t_cheque_amt;
		$chq17 = $chqe->t_cheque_no;
		$form['stu_cheque_details']['cheque_no'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq1,
			'#title' => t('Cheque No'),
			'#size' => 54,
		);
		$form['tea_cheque_details']['cheque_no_t'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq17,
			'#title' => t('Cheque No'),
			'#size' => 54,
		);
		$form['perm_cheque_address']['chq_address'] = array(
			'#type' => 'textarea',
			'#default_value' => $chq2,
			'#title' => t('Address Street 1'),
		);
		$form['perm_cheque_address']['chq_address']['#attributes']['readonly'] = 'readonly';
		$form['perm_cheque_address']['perm_city'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq7,
			'#title' => t('City'),
			'#size' => 35,
		);
		$form['perm_cheque_address']['perm_city']['#attributes']['readonly'] = 'readonly';
		$form['perm_cheque_address']['perm_state'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq8,
			'#title' => t('State'),
			'#size' => 35,
		);
		$form['perm_cheque_address']['perm_state']['#attributes']['readonly'] = 'readonly';
		$form['perm_cheque_address']['perm_pincode'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq9,
			'#title' => t('Zip code'),
			'#size' => 35,
		);
		$form['perm_cheque_address']['perm_pincode']['#attributes']['readonly'] = 'readonly';
		$form['stu_cheque_details']['cheq_amt'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq3,
			'#title' => t('Cheque Amount'),
			'#size' => 54,
		);
		$form['tea_cheque_details']['cheq_amt_t'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq17,
			'#title' => t('Cheque Amount'),
			'#size' => 54,
		);
		$form['temp_cheque_address']['temp_chq_address'] = array(
			'#type' => 'textarea',
			'#default_value' => $chq10,
			'#title' => t('Address Street 1'),
		);
		$form['temp_cheque_address']['temp_chq_address']['#attributes']['readonly'] = 'readonly';
		$form['temp_cheque_address']['temp_city'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq12,
			'#title' => t('City'),
			'#size' => 35,
		);
		$form['temp_cheque_address']['temp_city']['#attributes']['readonly'] = 'readonly';
		$form['temp_cheque_address']['temp_state'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq13,
			'#title' => t('State'),
			'#size' => 35,
		);
		$form['temp_cheque_address']['temp_state']['#attributes']['readonly'] = 'readonly';
		$form['temp_cheque_address']['temp_pincode'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq14,
			'#title' => t('Zipcode'),
			'#size' => 35,
		);
		$form['temp_cheque_address']['temp_pincode']['#attributes']['readonly'] = 'readonly';
	}
	else
	{
		$form['stu_cheque_details']['cheque_no'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq1,
    			'#title' => t('Cheque No'),
		);
		$form['tea_cheque_details']['cheque_no_t'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq16,
			'#title' => t('Cheque No'),
		);
		$form['perm_cheque_address']['chq_address'] = array(
			'#type' => 'textarea',
			'#default_value' => $chq2,
			'#title' => t('Address Street 1'),
		);
		$form['perm_cheque_address']['perm_city'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq7,
			'#title' => t('City'),
			'#size' => 35,
		);
		$form['perm_cheque_address']['perm_state'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq8,
			'#title' => t('State'),
			'#size' => 35,
		);
		$form['perm_cheque_address']['perm_pincode'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq9,
			'#title' => t('Zip code'),
			'#size' => 35,
		);
		$form['perm_cheque_address']['same_address'] = array(
			'#type' => 'checkbox',
			'#title' => t('Same As Permanent Address'),
			'#attributes' => array('onclick' => 'copy_address()'),
		); 
		$form['stu_cheque_details']['cheq_amt'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq3,
			'#title' => t('Cheque Amount'),
		);
		$form['tea_cheque_details']['cheq_amt'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq17,
			'#title' => t('Cheque Amount'),
		);
		$form['temp_cheque_address']['temp_chq_address'] = array(
			'#type' => 'textarea',
			'#default_value' => $chq10,
			'#title' => t('Address Street 1'),
		);
		$form['temp_cheque_address']['temp_city'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq12,
			'#title' => t('City'),
			'#size' => 35,
		);
		$form['temp_cheque_address']['temp_state'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq13,
			'#title' => t('State'),
			'#size' => 35,
		);
		$form['temp_cheque_address']['temp_pincode'] = array(
			'#type' => 'textfield',
			'#default_value' => $chq14,
			'#title' => t('Zip code'),
			'#size' => 35,
		);
		$form['temp_cheque_address']['same_address'] = array(
			'#type' => 'checkbox',
			'#title' => t('Same As Permanent Address'),
		);
		$form['temp_cheque_address']['same_address'] = array(
			'#type' => 'checkbox',
			'#title' => t('Same As Permanent Address'),
			'#attributes' => array('onclick' => 'copy_address()'),
		); 
    }
    $form['cheque_delivery']['cheque_sent'] = array(
    	'#type' => 'checkbox',
	'#title' => t('Cheque Sent'),
	'#default_value' => $chq4,
	'#description' => t('Check if the Cheque has been sent to the user.'),
	'#attributes' => array('id' => 'cheque_sent'),
    );
    $form['cheque_delivery']['cheque_cleared'] = array(
	'#type' => 'checkbox',
	'#title' => t('Cheque Cleared'),
	'#default_value' => $chq5,
	'#description' => t('Check if the Cheque has been <strong>Realised</strong> to the User Account.'),
	'#attributes' => array('id' => 'cheque_cleared'),
    );
    $form['commentf']['comment_cheque'] = array(
	'#type' => 'textarea',
	'#size' => 35, 
	'#attributes' => array('id' => 'comment'),
	'#default_value' => $chq15,
    );
    $form['proposal_id'] = array(
	'#type' => 'hidden',
	'#value' => $proposal_id,
    );

    /*$preference1_p = db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id = %d ORDER BY id ASC", $proposal_id); */

	$query = db_select('textbook_companion_paper');
	$query->fields('textbook_companion_paper');
	$query->condition('proposal_id', $proposal_id);
	$query->orderBy('id', 'ASC');
	$preference1_p = $query->execute();

    if (!($proposal_data1 = $preference1_p->fetchObject()))
    {
    	drupal_set_message(t('Invalid proposal selected. Please try again.'), 'error');
    	drupal_goto('manage_proposal');
    	return;
    }
    $form['submit'] = array(
	'#type' => 'submit',
	'#value' => t('Submit')
    );
    $form['cancel'] = array(
	'#type' => 'markup',
	'#value' => l(t('Cancel'), 'manage_proposal/all'),
    );
  return $form;
}
/*function cheque_status_form_validate($form, &$form_state) 
{

		//var_dump(($form_state['values']['stu_cheque_details']['cheque_amt']));
		//die;
		if((!$form_state['values']['cheque_no'])||(!is_numeric($form_state['values']['cheque_no'])))
		{
			form_set_error('cheque_no', t('Invalid Student Cheque No. Please enter a valid cheque no.'));
			return;
		}
		if((!$form_state['values']['cheq_amt'])||(!is_numeric($form_state['values']['cheq_amt'])))
		{
			form_set_error('cheq_amt', t('Invalid Student Amount. Please enter a valid numeric amount.'));
			return;
		}
		if((!$form_state['values']['cheque_no_t'])||(!is_numeric($form_state['values']['cheque_no_t'])))
		{
			form_set_error('cheque_no_t', t('Invalid Teacher Cheque No. Please enter a valid cheque no.'));
			return;
		}
		if((!$form_state['values']['cheq_amt_t'])||(!is_numeric($form_state['values']['cheq_amt_t'])))
		{
			form_set_error('cheq_amt_t', t('Invalid Teacher Amount. Please enter a valid numeric amount.'));
			return;
		}
}*/
function cheque_status_form_submit($form, &$form_state)
{
	global $user;
	$proposal_id=arg(2);
	
	
	/*$query ="UPDATE {textbook_companion_cheque} SET 
	cheque_no = ".$form_state['values']['cheque_no'].",
	cheque_amt = ".$form_state['values']['cheq_amt'].",
	alt_mobno = '".$form_state['values']['mobileno2']."', 
	address = '".$form_state['values']['chq_address']."',  
	perm_city = '".$form_state['values']['perm_city']."', 
	perm_state = '".$form_state['values']['perm_state']."', 
	perm_pincode = '".$form_state['values']['perm_pincode']."', 
	temp_chq_address = '".$form_state['values']['temp_chq_address']."', 
	temp_city = '".$form_state['values']['temp_city']."', 
	temp_state = '".$form_state['values']['temp_state']."', 
	temp_pincode = '".$form_state['values']['temp_pincode']."',
	commentf = '".$form_state['values']['comment_cheque']."', 
	t_cheque_no = ".$form_state['values']['cheque_no_t'].",
	t_cheque_amt = ".$form_state['values']['cheq_amt_t']."
	WHERE proposal_id = ".$proposal_id;
	
	db_query($query);*/

	$query = db_update('textbook_companion_cheque');
	$query->fields(array(
  	'cheque_no' => $form_state[ values ][ cheque_no ],
  	'cheque_amt' => $form_state[ values ][ cheq_amt ],
  	'alt_mobno' => $form_state[ values ][ mobileno2 ],
  	'address' => $form_state[ values ][ chq_address ],
  	'perm_city' => $form_state[ values ][ perm_city ],
  	'perm_state' => $form_state[ values ][ perm_state ],
  	'perm_pincode' => $form_state[ values ][ perm_pincode ],
  	'temp_chq_address' => $form_state[ values ][ temp_chq_address ],
  	'temp_city' => $form_state[ values ][ temp_city ],
  	'temp_state' => $form_state[ values ][ temp_state ],
  	'temp_pincode' => $form_state[ values ][ temp_pincode ],
  	'commentf' => $form_state[ values ][ comment_cheque ],
  	't_cheque_no' => $form_state[ values ][ cheque_no_t ],
  	't_cheque_amt' => $form_state[ values ][ cheq_amt_t ],
	));
	$query->condition('proposal_id', $proposal_id);
	$num_updated = $query->execute();

	if ($form_state['values']['cheque_sent'] == 1)
	{
		/* sending email */
		/*$query ="UPDATE {textbook_companion_cheque} SET cheque_sent = ".$form_state['values']['cheque_sent']." WHERE proposal_id = ".$proposal_id;
		db_query($query);*/

		$query = db_update('textbook_companion_cheque');
		$query->fields(array(
  		'cheque_sent' => $form_state[ values ][ cheque_sent ],
		));
		$query->condition('proposal_id', $proposal_id);
		$num_updated = $query->execute();

		$book_user = user_load($proposal_data->uid);
		$param['proposal_completed']['proposal_id'] = $proposal_id;
		$param['proposal_completed']['user_id'] = $proposal_data->uid;
		$email_to = $book_user->mail;
		if (!drupal_mail('textbook_companion', 'cheque_sent', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
			drupal_set_message('Error sending email message.', 'error');
		drupal_set_message('Cheque for Book proposal has been Sent. User has been notified .', 'status');
	}


	if ($form_state['values']['cheque_cleared'] == 1)
	{
		/*$query ="UPDATE {textbook_companion_cheque} SET cheque_cleared = ".$form_state['values']['cheque_cleared']." WHERE proposal_id = ".$proposal_id;
		db_query($query);*/

		$query = db_update('textbook_companion_cheque');
		$query->fields(array(
  		'cheque_cleared' => $form_state[ values ][ cheque_cleared ],
		));
		$query->condition('proposal_id', $proposal_id);
		$num_updated = $query->execute();

		$curtime = MySQL_NOW();
		echo $curtime;
		drupal_set_message('Cheque Has Been Debited into User Account.', 'status');
		/*$queryc ="UPDATE {textbook_companion_cheque} SET cheque_dispatch_date = NOW() WHERE proposal_id = ".$form_state['values']['proposal_id']."";
		db_query($queryc);*/

		$query = db_update('textbook_companion_cheque');
		$query->fields(array(
  		'cheque_dispatch_date' => 'NOW',
		));
		$query->condition('proposal_id', $form_state['values']['proposal_id']);
		$num_updated = $query->execute();
	}

	/************************************************
	 Check For the Remark 
	************************************************/
	if ($form_state['values']['comment_cheque'])
	{
		/* sending email */
    		$book_user = user_load($proposal_data->uid);
		$param['proposal_completed']['proposal_id'] = $proposal_id;
		$param['proposal_completed']['user_id'] = $proposal_data->uid;
		$email_to = $book_user->mail;
		if (!drupal_mail('textbook_companion', 'remark', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
			drupal_set_message('Error sending email message.', 'error');
		drupal_set_message(t('Remark Updated. User has been notified'), 'status');
	}
  	else
	{
		if (!drupal_mail('textbook_companion', 'remark_not', $email_to , language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
			drupal_set_message('Error sending email message.', 'error');
       		drupal_set_message('No Remarks. User has been notified .', 'status');
	}
}
function MySQL_NOW(){ return date('Y-m-d'); }

function contact_details($form_state)
{
	global $user;
	if(!isset($_REQUEST['msg']))
		drupal_set_message('<strong>Caution</strong>:Please update Contact Detail carefully as this will be used for future reference during <strong>Payment</strong></li></ul>', 'error');

	$x = $user->uid;
        /*$query2 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid=".$x);
        $data2 = db_fetch_object($query2);*/

	$query = db_select('textbook_companion_proposal');
	$query->fields('textbook_companion_proposal');
	$query->condition('uid', $x);
	$result = $query->execute();
	$data2=$result->fetchObject();


	if(!$data2)
	{
		drupal_set_message('Fill Up The <a href="proposal">Book Proposal Form</a>', 'error');
		return '';
	}	
	/*$query3 = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status=1 AND proposal_id=".$data2->id);
	$data3 = db_fetch_object($query3);*/

	$query = db_select('textbook_companion_preference');
	$query->fields('textbook_companion_preference');
	$query->condition('approval_status', 1);
	$query->condition('proposal_id', $data2->id);
	$result = $query->execute();
	$data3=$result->fetchObject();
	
	if(!$data3->approval_status)
	{
		drupal_set_message('Book Proposal Has Not Been Accpeted .', 'error');
		return '';
	}
        
	$proposal_id = $data2->id;
	
	/*$comment_qx = db_query("SELECT * FROM textbook_companion_cheque c WHERE proposal_id =".$proposal_id);
	$commentv = db_fetch_object($comment_qx);*/

	$query = db_select('textbook_companion_cheque', 'c');
	$query->fields('c');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$commentv=$result->fetchObject();

	$form16 = $commentv->commentf;	
	$mob_no = $data2->mobile;
	$full_name = $data2->full_name;

	/*$query1 = db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id=".$proposal_id);*/
	$query = db_select('textbook_companion_cheque');
	$query->fields('textbook_companion_cheque');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();

	$form1 = 0;
	$form2 = 0;
	$form3 = 0;
	$form4 = 0;
	$form5 = 0;
	$form6 = 0;
	$form7 = 0;
	$form8 = 0;
	$form9 = 0;
	$form10 = 0;
	$form11 = 0;
	$form12 = 0;
	$form13 = 0;
	$form14 = 0;
	$form15 = 0;	
	
	if($data = $result->fetchObject())
	{
		$form1 = $data->address;
		$form8 = $data->alt_mobno;
	 	$form9 = $data->perm_city;
		$form10 = $data->perm_state;
		$form11 = $data->perm_pincode;
 		$form12 = $data->temp_chq_address;
		$form13 = $data->temp_city;
		$form14 = $data->temp_state;
 		$form15 = $data->temp_pincode;
  	}	
  	else
  	{
		/*db_query("insert into {textbook_companion_cheque} (proposal_id) values(%d)",$proposal_id);*/

		$query = "insert into {textbook_companion_cheque} (proposal_id) values (:proposal_id)";
		$args = array(
		":proposal_id"=>$proposal_id, 
			);
		$result = db_query($query, $args, array('return' => Database::RETURN_INSERT_ID));
	}
	$form['candidate_detail'] = array(
	     '#type' => 'fieldset',
             '#value' => $form_html,
	     '#title' => t('Candidate Detail'),
             '#attributes' => array('id' => 'candidate_detail'),
	);
	$form['proposal_id'] =array(
	    '#type' => 'hidden',
	    '#default_value' => $proposal_id,
  	);
	$form['candidate_detail']['fullname'] = array(
            '#type' => 'textfield',
	    '#title' => t('Full Name'),
	    '#size' => 48,
            '#default_value' => $full_name,
  	);
        $form['candidate_detail']['email'] = array(
            '#type' => 'textfield',
	    '#title' => t('Email'),
	    '#size' => 48,
            '#value' => $user->mail,
	    '#disabled' => TRUE,
  	);
	$form['candidate_detail']['mobileno1'] = array(
            '#type' => 'textfield',
	    '#title' => t('Mobile No'),
	    '#size' => 48,
            '#default_value' => $mob_no,
  	);
	
        $form['candidate_detail']['mobileno2'] = array(
            '#type' => 'textfield',
	    '#title' => t('Alternate Mobile No'),
	    '#size' => 48,
            '#default_value' => $form8,
  	);

	/*$chq_q=db_query("SELECT * FROM {textbook_companion_cheque} WHERE proposal_id=".$proposal_id);
        $chq_data=db_fetch_object($chq_q);*/

	$query = db_select('textbook_companion_cheque');
	$query->fields('textbook_companion_cheque');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$chq_data=$result->fetchObject();
 
 
        /*$q_form = db_query("SELECT * FROM {textbook_companion_paper} WHERE proposal_id=".$proposal_id);
        $q_data = db_fetch_object($q_form);*/

	$query = db_select('textbook_companion_paper');
	$query->fields('textbook_companion_paper');
	$query->condition('proposal_id', $proposal_id);
	$result = $query->execute();
	$q_data=$result->fetchObject();

  	$form_html .= '<ul>';
  	if($q_data->internship_form)
	{
		$form_html .= '<li><strong>Internship Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Internship Application </strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
	}
	if($q_data->copyright_form)
	{
		$form_html .= '<li><strong>Copyright Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Copyright Application</strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
	}
	if($q_data->undertaking_form)
	{
		$form_html .= '<li><strong>Undertaking Application </strong> Form Submitted</li>';
	}
	else
	{
		$form_html .= '<li><strong>Undertaking Application</strong> Form Not Submitted.<br>Please submit it as soon as possible.</li>';
	}
	$form_html .= '</ul>';
	$form['Application Status'] = array(
	     '#type' => 'fieldset',
             '#value' => $form_html,
	     '#title' => t('Application Form Status'),
             '#attributes' => array('id' => 'app_status'),
	);
        $form['perm_cheque_address'] = array(
    	    '#type' => 'fieldset',
            '#title' => t('Permanent Address'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
            '#attributes' => array('id' => 'perm_cheque_address'),
  	);
	$form['temp_cheque_address'] = array(
	    '#type' => 'fieldset',
            '#title' => t('Temporary Address'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
            '#attributes' => array('id' => 'temp_cheque_address'),
	);
  	$form['perm_cheque_address']['chq_address'] = array(
		'#type' => 'textarea',
		'#title' => t('Address'),
		'#size' => 35,
		'#default_value' => $form1,
	);
	$form['perm_cheque_address']['perm_city'] = array(
		'#type' => 'textfield',
		'#default_value' => $form9,
		'#title' => t('City'),
		'#size' => 35,
	);
	$form['perm_cheque_address']['perm_state'] = array(
		'#type' => 'textfield',
		'#default_value' => $form10,
		'#title' => t('State'),
		'#size' => 35,
	);
	$form['perm_cheque_address']['perm_pincode'] = array(
		'#type' => 'textfield',
		'#default_value' => $form11,
		'#title' => t('Zip code'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['temp_chq_address'] = array(
		'#type' => 'textarea',
		'#default_value' => $form12,
		'#title' => t('Address'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['temp_city'] = array(
		'#type' => 'textfield',
		'#default_value' => $form13,
		'#title' => t('City'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['temp_state'] = array(
		'#type' => 'textfield',
		'#default_value' => $form14,
		'#title' => t('State'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['temp_pincode'] = array(
		'#type' => 'textfield',
		'#default_value' => $form15,
		'#title' => t('Zip code'),
		'#size' => 35,
	);
	$form['temp_cheque_address']['same_address'] = array(
		'#type' => 'checkbox',
		'#title' => t('Same As Permanent Address'),
	);
	if($chq_data->commentf)
	{     
		$form['commentu'] = array(
	        	'#type' => 'fieldset',
  	        	'#title' => t('Remarks'),
 			'#collapsible' => FALSE,
 			'#collapsed' => FALSE,
                	'#attributes' => array('id' => 'comment_cheque'),
    		);
	        $form['commentu']['comment_cheque'] = array(
	        	'#type' => 'textarea',
			'#size' => 35,  
	        	'#default_value' => $form16,
		);
	}
	$form['commentu']['comment_cheque'] ['#attributes']['readonly'] = 'readonly';
	$form['submit'] = array(
    		'#type' => 'submit',
		'#value' => t('Update')
    	);
    	$form['cancel'] = array(
		'#type' => 'markup',
		'#value' => l(t('Cancel'), 'manage_proposal/all'),
	);
    return $form;       
}
function contact_details_submit($form, &$form_state)
{

	global $user;
	$x = $user->uid;

	/*$query2 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid=".$x);
	$data2 = db_fetch_object($query2);*/

	$query = db_select('textbook_companion_proposal');
	$query->fields('textbook_companion_proposal');
	$query->condition('uid', $x);
	$result = $query->execute();
	$data2=$result->fetchObject();


	/*$query ="UPDATE {textbook_companion_cheque} SET 
	alt_mobno = '".$form_state['values']['mobileno2']."' , 
	address = '".$form_state['values']['chq_address']."',  
	perm_city = '".$form_state['values']['perm_city']."', 
	perm_state = '".$form_state['values']['perm_state']."', 
	perm_pincode = '".$form_state['values']['perm_pincode']."', 
	temp_chq_address = '".$form_state['values']['temp_chq_address']."', 
	temp_city = '".$form_state['values']['temp_city']."', 
	temp_state = '".$form_state['values']['temp_state']."', 
	temp_pincode = '".$form_state['values']['temp_pincode']."' ,
	address_con = 'Submitted'
	WHERE proposal_id = ".$data2->id;
	db_query($query);*/

	$query = db_update('textbook_companion_cheque');
	$query->fields(array(
  	'alt_mobno' => $form_state[ values ][ mobileno2 ],
  	'address' => $form_state[ values ][ chq_address ],
  	'perm_city' => $form_state[ values ][ perm_city ],
  	'perm_state' => $form_state[ values ][ perm_state ],
  	'perm_pincode' => $form_state[ values ][ perm_pincode ],
  	'temp_chq_address' => $form_state[ values ][ temp_chq_address ],
  	'temp_city' => $form_state[ values ][ temp_city ],
  	'temp_state' => $form_state[ values ][ temp_state ],
  	'temp_pincode' => $form_state[ values ][ temp_pincode ],
  	'address_con' => 'Submitted',
	));
	$query->condition('proposal_id', $data2->id);
	$num_updated = $query->execute();

	drupal_set_message('Contact Details Has Been Updated.....!', 'status');
	drupal_goto('mycontact', array('msg' => 0 ), $fragment = NULL, $http_response_code = 302);
}
function copy_address()
{
    
    alert("Hello");
    $form['temp_cheque_address']['temp_chq_address'] = array(
    	'#type' => 'textarea',
    	'#default_value' => $form['values']['chq_address'],
    	'#title' => t('Address Street 1'),
    );
    
    $form['temp_cheque_address']['temp_city'] = array(
    	'#type' => 'textfield',
    	'#default_value' => $form['values']['perm_city'],
    	'#title' => t('City'),
    	'#size' => 35,
    );
    $form['temp_cheque_address']['temp_state'] = array(
    	'#type' => 'textfield',
    	'#default_value' => $form['values']['perm_state'],
    	'#title' => t('State'),
    	'#size' => 35,
    );
    $form['temp_cheque_address']['temp_pincode'] = array(
    	'#type' => 'textfield',
    	'#default_value' => $form['values']['perm_pincode'],
    	'#title' => t('Zip code'),
    	'#size' => 35,
    );
}

function cheque_report_form()
{
	/*$search_qx = db_query("SELECT * FROM textbook_companion_proposal p,textbook_companion_cheque c WHERE c.address_con = 'Submitted' AND (p.id = c.proposal_id)");*/

			$query = db_select('textbook_companion_proposal', 'p');
			$query->join('textbook_companion_cheque','c','p.id = c.proposal_id');
			$query->fields('p',array('textbook_companion_proposal'));
			$query->fields('c',array('textbook_companion_cheque'));
			$query->condition('c.address_con', 'Submitted');
			$search_qx = $query->execute();
	
	while ($search_datax = $search_qx->fetchObject())
	{
		$result = array($search_datax->full_name,$search_datax->address_con,$search_datax->cheque_no,$search_datax->cheque_dispatch_date); 
	}
	if (!$result) die('Couldn\'t fetch records');
	$num_fields = count($result);
	$headers = array();
	for ($i = 0; $i < $num_fields; $i++) 
	{
		$headers[] = mysql_field_name($result , $i);
	}

	$row = array();
	$fp = fopen('php://output', 'w');
	$search_header = array('Name Of The Student', 'Application Form Status', 'Cheque No', 'Cheque Clearance Date');
	fputcsv($fp, $search_header);
	if ($fp && $result)
	{
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="Report.csv"');
		header('Pragma: no-cache');
		header('Expires: 0');
		fputcsv($fp, $headers);
		while ($row = mysql_fetch_row($result)) 
		{
			fputcsv($fp, array_values($row));
		}
		die;
	}
}
?>
