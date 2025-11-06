<?php
function book_proposal_form($form, &$form_state)
{
	$user = \Drupal::currentUser();
	$page_content = "";
	if (!$user->uid)
	{
		\Drupal::messenger()->addError('It is mandatory to login on this website to access the proposal form');
		drupal_goto('');
		return;
	} //!$user->uid
	/* check if user has already submitted a proposal */
	/*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);*/
	$query = \Drupal::database()->select('textbook_companion_proposal');
	$query->fields('textbook_companion_proposal');
	$query->condition('uid', $user->uid);
	$query->orderBy('id', 'DESC');
	$query->range(0, 1);
	$proposal_q = $query->execute();
	if ($proposal_q)
	{
		if ($proposal_data = $proposal_q->fetchObject())
		{
			switch ($proposal_data->proposal_status)
			{
				case 0:
					\Drupal::messenger()->addStatus(t('We have already received your proposal. We will get back to you soon.'));
					drupal_goto('');
					return;
					break;
				case 1:
					// @FIXME
// l() expects a Url object, created from a route name or external URI.
// drupal_set_message(t('Your proposal has been approved. Please go to ' . l('Code Submission', 'textbook-companion/code') . ' to upload your code'), 'status');

					drupal_goto('');
					return;
					break;
				case 2:
					\Drupal::messenger()->addError(t('Your proposal has been dis-approved. Please create another proposal below.'));
					break;
				case 3:
					\Drupal::messenger()->addStatus(t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
					break;
				default:
					\Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
					drupal_goto('');
					return;
					break;
			} //$proposal_data->proposal_status
		} //$proposal_data = $proposal_q->fetchObject()
	}
	$form = array();
	$form['imp_notice'] = array(
		'#type' => 'item',
		'#markup' => '<font color="red"><b>Please fill up this form carefully as the details entered here will be exactly written in the Textbook Companion</b></font>'
	);
	$form['full_name'] = array(
		'#type' => 'textfield',
		'#title' => t('Full Name'),
		'#size' => 30,
		'#maxlength' => 50,
		'#required' => TRUE
	);
	$form['email_id'] = array(
		'#type' => 'textfield',
		'#title' => t('Email'),
		'#size' => 30,
		'#value' => $user->mail,
		'#disabled' => TRUE
	);
	$form['mobile'] = array(
		'#type' => 'textfield',
		'#title' => t('Mobile No.'),
		'#size' => 30,
		'#maxlength' => 15,
		'#required' => TRUE
	);
	$form['gender'] = array(
		'#type' => 'radios',
		'#title' => t('Gender'),
		'#options' => array(
			'M' => 'Male',
			'F' => 'Female'
		),
		'#required' => TRUE
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
		'#required' => TRUE
	);
	$form['course'] = array(
		'#type' => 'textfield',
		'#title' => t('Course'),
		'#size' => 30,
		'#maxlength' => 50,
		'#required' => TRUE
	);
	$form['branch'] = array(
		'#type' => 'select',
		'#title' => t('Department/Branch'),
		'#options' => _list_of_departments(),
		'#required' => TRUE
	);
	$form['university'] = array(
		'#type' => 'textfield',
		'#title' => t('University/ Institute'),
		'#size' => 80,
		'#maxlength' => 200,
		'#required' => TRUE,
		'#attributes' => array(
			'placeholder' => 'Insert full name of your institute/ university.... '
		)
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
		'#validated' => TRUE
	);
	$form['other_country'] = array(
		'#type' => 'textfield',
		'#title' => t('Other than India'),
		'#size' => 100,
		'#attributes' => array(
			'placeholder' => t('Enter your country name')
		),
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
		'#required' => TRUE,
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
		'#value' => 'None',
		'#title' => t('College Teacher/Professor'),
		'#size' => 30,
		'#maxlength' => 50,
		'#required' => TRUE
	);
	$form['faculty_email'] = array(
		'#type' => 'hidden',
		'#value' => 'None',
		'#title' => t('Teacher/Professor Email Id'),
		'#value' => '@email.com',
		'#size' => 30,
		'#maxlength' => 50
	);
	$form['reviewer'] = array(
		'#type' => 'hidden',
		'#value' => 'OpenModelica TBC Team',
		'#title' => t('Reviewer'),
		'#size' => 30,
		'#maxlength' => 50
	);
	$form['version'] = array(
		'#type' => 'select',
		'#title' => t('Version'),
		'#options' => _list_of_software_version(),
		'#required' => TRUE
	);
	$form['other_version'] = array(
		'#type' => 'textfield',
		'#size' => 30,
		'#maxlength' => 50,
		//'#required' => TRUE,
		'#description' => t('Specify the Older version used'),
		'#states' => array(
			'visible' => array(
				':input[name="version"]' => array(
					'value' => 'Other version'
				)
			)
		)
	);
	$form['completion_date'] = array(
		'#type' => 'textfield',
		'#title' => t('Expected Date of Completion'),
		'#description' => t('Input date format should be DD-MM-YYYY. Eg: 23-03-2011'),
		'#size' => 10,
		'#maxlength' => 10
	);
	$form['operating_system'] = array(
		'#type' => 'textfield',
		'#title' => t('Operating System'),
		'#required' => TRUE,
		'#size' => 30,
		'#maxlength' => 50
	);
	$reason = array(
		'Used in more than one University' => t('Used in more than one University'),
		'The book has multiple editions' => t('The book has multiple editions'),
		'Extremely useful' => t('Extremely useful'),
		'Other reason' => t('Any other reason state below')
	);
	$form['reason'] = array(
		'#type' => 'checkboxes',
		'#title' => t('Reasons'),
		'#options' => $reason,
		'#required' => TRUE
	);
	$form['other_reason'] = array(
		'#type' => 'textarea',
		'#size' => 300,
		'#maxlength' => 300,
		'#description'=>t('<span style="color:red;">Maximum character limit is 255 characters</span>'),
		'#states' => array(
			'visible' => array(
				':input[name="reason[Other reason]"]' => array(
					'checked' => TRUE
				)
			)
		)
		//'#required' => FALSE,
	);
	$form['proposal_type'] = array(
		'#type' => 'hidden',
		'#default_value' => '1',
		'#required' => FALSE
	);
	$form['reference'] = array(
		'#type' => 'textarea',
		'#title' => t('Reference'),
		'#required' => TRUE,
		'#size' => 500,
		'#maxlength' => 500,
		'#attributes' => array(
			'placeholder' => 'Links of the syllabus must be provided....'
		)
	);
	$form['form_type'] = array(
		'#type' => 'hidden',
		'#value' => 1
	);
	$form['preference1'] = array(
		'#type' => 'fieldset',
		'#title' => t('Book Preference'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE
	);
	$form['preference1']['book1'] = array(
		'#type' => 'textfield',
		'#title' => t('Title of the book'),
		'#size' => 30,
		'#maxlength' => 100,
		'#required' => TRUE
	);
	$form['preference1']['author1'] = array(
		'#type' => 'textfield',
		'#title' => t('Author Name'),
		'#size' => 30,
		'#maxlength' => 100,
		'#required' => TRUE
		//'#value' => $row1->author,
		//'#disabled' => ($row1->author?TRUE:FALSE),
	);
	$form['preference1']['isbn1'] = array(
		'#type' => 'textfield',
		'#title' => t('ISBN No'),
		'#size' => 30,
		'#maxlength' => 25,
		'#required' => TRUE
		// '#value' => $row1->isbn,
		// '#disabled' => ($row1->isbn?TRUE:FALSE),
	);
	$form['preference1']['publisher1'] = array(
		'#type' => 'textfield',
		'#title' => t('Publisher & Place'),
		'#size' => 30,
		'#maxlength' => 50,
		'#required' => TRUE
		//'#value' => $row1->publisher,
	);
	$form['preference1']['edition1'] = array(
		'#type' => 'textfield',
		'#title' => t('Edition'),
		'#size' => 4,
		'#maxlength' => 2,
		'#required' => TRUE
		//'#value' => $row1->edition,
	);
	$form['preference1']['year1'] = array(
		'#type' => 'textfield',
		'#title' => t('Year of publication'),
		'#size' => 4,
		'#maxlength' => 4,
		'#required' => TRUE
		//'#value' => $row1->year,
	);
	$form['samplefile'] = array(
		'#type' => 'fieldset',
		'#title' => t('Sample Source Files'),
		'#collapsible' => FALSE,
		'#collapsed' => FALSE
	);
	$form['samplefile']['samplefile1'] = array(
		'#type' => 'file',
		'#title' => t('Upload sample source file'),
		'#size' => 48,
		'#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('textbook_companion.settings')->get('textbook_companion_sample_source_extensions') . '</span>'
	);
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit')
	);
	$form['dir_name'] = array(
		'#type' => 'hidden',
		'#value' => 'None'
	);
	/* #value fix for #default_value bug drupal6  
	foreach(array("preference1", "preference2", "preference3") as $preference) {
	foreach($form[$preference] as $key => $value) {
	if(!$form[$preference][$key]["#value"]) {
	unset($form[$preference][$key]["#value"]);
	}
	}
	}*/
	return $form;
}
function book_proposal_form_validate($form, &$form_state)
{
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
	if (mktime(0, 0, 0, $m, $d, $y) <= time())
		form_set_error('completion_date', t('Expected date of completion should be in future'));
	/* edition */
	$cur_year = date('Y');
	if (!preg_match('/^[A-Za-z]/', $form_state['values']['book1']))
		form_set_error('book1', t('Invalid book name for Book Preference 1'));
	if (!preg_match('/^[A-Za-z]/', $form_state['values']['author1']))
		if (!preg_match('/^[0-9\-xX]+$/', $form_state['values']['isbn1']))
			form_set_error('isbn1', t('Invalid ISBN for Book Preference 1'));
	if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state['values']['edition1']))
		form_set_error('edition1', t('Invalid edition for Book Preference 1'));
	if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state['values']['year1']))
		form_set_error('year1', t('Invalid year of pulication for Book Preference 1'));
	if ((int) $form_state['values']['year1'] > $cur_year)
		form_set_error('year1', t('Year of pulication should be not in the future for Book Preference 1'));
	if ($form_state['values']['book1'] && $form_state['values']['author1'])
	{
		$bk1 = trim($form_state['values']['book1']);
		$auth1 = trim($form_state['values']['author1']);
		//var_dump(_dir_name($bk1, $auth1))
		$pref_id = NULL;
		if (_dir_name($bk1, $auth1, $pref_id) != NULL)
		{
			$form_state['values']['dir_name1'] = _dir_name($bk1, $auth1, $pref_id);
		} //_dir_name($bk1, $auth1, $pref_id) != NULL
	} //$form_state['values']['book1'] && $form_state['values']['author1']

	if (isset($form_state['values']['reason']))
	{
		$my_reason = $form_state['values']['other_reason'];
		if($my_reason>255)
		{
			form_set_error('other_reason', t('Maximum limit is 255 characters'));
		}
	}
	if ($form_state['values']['version'] == 'Other version')
	{
		if ($form_state['values']['other_version'] == '')
		{
			form_set_error('other_version', t('Please provide valid version'));
		} //$form_state['values']['other_version'] == ''
	} //$form_state['values']['version'] == 'Other version'
	if (isset($_FILES['files']))
	{
		/* check if atleast one source or result file is uploaded */
		if (!($_FILES['files']['name']['samplefile1']))
			form_set_error('samplefile1', t('Please upload sample code main or source file.'));
		/* check for valid filename extensions */
		foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
		{
			if ($file_name)
			{
				/* checking file type */
				if (strstr($file_form_name, 'sample'))
					$file_type = 'S';
				else
					$file_type = 'U';
				$allowed_extensions_str = '';
				switch ($file_type)
				{
					case 'S':
						$allowed_extensions_str = \Drupal::config('textbook_companion.settings')->get('textbook_companion_sample_source_extensions');
						break;
				} //$file_type
				$allowed_extensions = explode(',', $allowed_extensions_str);
				$fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
				$temp_extension = end($fnames);
				if (!in_array($temp_extension, $allowed_extensions))
					form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
				if ($_FILES['files']['size'][$file_form_name] <= 0)
					form_set_error($file_form_name, t('File size cannot be zero.'));
				/* check if valid file name */
				if (!textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name]))
					form_set_error($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
			} //$file_name
		} //$_FILES['files']['name'] as $file_form_name => $file_name
	} //isset($_FILES['files'])
	if ($form_state['values']['version'] == 'Other version')
	{
		if ($form_state['values']['other_version'] == '')
		{
			form_set_error('other_version', t('Please provide valid version'));
		} //$form_state['values']['other_version'] == ''
	} //$form_state['values']['version'] == 'Other version'
	return;
}
function book_proposal_form_submit($form, &$form_state, $directory_name = NULL)
{
	$user = \Drupal::currentUser();
	$root_path = textbook_companion_samplecode_path();
	// @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $selections = variable_get("aicte_" . $user->uid, "");

	if (!$user->uid)
	{
		\Drupal::messenger()->addError('It is mandatory to login on this website to access the proposal form');
		return;
	} //!$user->uid
	/* completion date to timestamp */
	list($d, $m, $y) = explode('-', $form_state['values']['completion_date']);
	$completion_date_timestamp = mktime(0, 0, 0, $m, $d, $y);
	$openmodelica_version = $form_state['values']['version'];
	if ($form_state['values']['version'] == 'Other version')
	{
		$form_state['values']['version'] = $form_state['values']['other_version'];
	} //$form_state['values']['version'] == 'Other version'
	if ($form_state['values']['country'] == 'other')
	{
		$form_state['values']['country'] = trim($form_state['values']['other_country']);
		$form_state['values']['all_state'] = trim($form_state['values']['other_state']);
	} //$form_state['values']['country'] == 'other'
	if (isset($_POST['reason']))
	{
		if (!($form_state['values']['other_reason']))
		{
			$my_reason = implode(", ", $_POST['reason']);
		} //!($form_state['values']['other_reason'])
		else
		{
			$my_reason = implode(", ", $_POST['reason']);
			$my_reason = $my_reason . "-" . " " . $form_state['values']['other_reason'];
		}
		$form_state['values']['reason'] = $my_reason;
	} //isset($_POST['reason'])
	$query = "INSERT INTO {textbook_companion_proposal}
    (uid, approver_uid, full_name, mobile, gender, how_project, course, branch, university,city,pincode,state,country, faculty, reviewer,reference, completion_date, creation_date, approval_date, proposal_status, openmodelica_version, operating_system, teacher_email,reason,samplefilepath,proposal_type,proposed_completion_date) VALUES (:uid, :approver_uid, :full_name, :mobile, :gender, :how_project, :course, :branch, :university, :city,:pincode,:state,:country, :faculty, :reviewer,:reference, :completion_date,
 :creation_date, :approval_date, :proposal_status, :openmodelica_version, :operating_system,
:teacher_email,:reason,:samplefilepath,:proposal_type, :proposed_completion_date)";
	$args = array(
		":uid" => $user->uid,
		":approver_uid" => 0,
		":full_name" => trim(ucwords(strtolower($form_state['values']['full_name']))),
		":mobile" => trim($form_state['values']['mobile']),
		":gender" => $form_state['values']['gender'],
		":how_project" => $form_state['values']['how_project'],
		":course" => trim($form_state['values']['course']),
		":branch" => $form_state['values']['branch'],
		":university" => trim($form_state['values']['university']),
		":city" => trim($form_state['values']['city']),
		":pincode" => $form_state['values']['pincode'],
		":state" => trim($form_state['values']['all_state']),
		":country" => $form_state['values']['country'],
		":faculty" => ucwords(strtolower($form_state['values']['faculty'])),
		":reviewer" => ucwords(strtolower($form_state['values']['reviewer'])),
		":reference" => trim($form_state['values']['reference']),
		":completion_date" => $completion_date_timestamp,
		":creation_date" => time(),
		":approval_date" => 0,
		":proposal_status" => 0,
		":openmodelica_version" => trim($form_state['values']['version']),
		":operating_system" => trim($form_state['values']['operating_system']),
		":teacher_email" => $form_state['values']['faculty_email'],
		":reason" => $form_state['values']['reason'],
		":samplefilepath" => "",
		":proposal_type" => 0,
		":proposed_completion_date" => $completion_date_timestamp
	);
	$result = \Drupal::database()->query($query, $args, $query);
	$dest_path = $result . '/';
	if (!is_dir($root_path . $dest_path))
		mkdir($root_path . $dest_path);
	/* uploading files */
	foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
	{
		if ($file_name)
		{
			/* checking file type */
			$file_type = 'S';
			if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
			{
				// drupal_set_message(t("Error uploading file. File !filename already exists.", array('!filename' => $_FILES['files']['name'][$file_form_name])), 'error');
				unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
			} //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			/* uploading file */
			if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
			{
				$query = "UPDATE {textbook_companion_proposal} SET samplefilepath = :samplefilepath WHERE id = :id";
				$args = array(
					":samplefilepath" => $dest_path . $_FILES['files']['name'][$file_form_name],
					":id" => $result
				);
				$updateresult = \Drupal::database()->query($query, $args);
				\Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
			} //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			else
			{
				\Drupal::messenger()->addError('Error uploading file : ' . $dest_path . '/' . $file_name);
			}
		} //$file_name
	} //$_FILES['files']['name'] as $file_form_name => $file_name
	if (!$result)
	{
		\Drupal::messenger()->addError(t('Error receiving your proposal. Please try again.'));
		return;
	} //!$result
	/* proposal id */
	$proposal_id = $result;
	/* inserting first book preference */
	if ($form_state['values']['book1'])
	{
		/*$result = db_query("INSERT INTO {textbook_companion_preference}
		(proposal_id, pref_number, book, author, isbn, publisher, edition, year, category, approval_status) VALUES
		(%d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, %d)",
		$proposal_id,
		1,
		ucwords(strtolower($form_state['values']['book1'])),
		ucwords(strtolower($form_state['values']['author1'])),
		$form_state['values']['isbn1'],
		ucwords(strtolower($form_state['values']['publisher1'])),
		$form_state['values']['edition1'],
		$form_state['values']['year1'],
		0,
		0
		);*/
		$bk1 = trim($form_state['values']['book1']);
		$auth1 = trim($form_state['values']['author1']);
		$pref_id = NULL;
		$directory_name = _dir_name($bk1, $auth1, $pref_id);
		$query = "INSERT INTO {textbook_companion_preference}
      (proposal_id, pref_number, book, author, isbn, publisher, edition, year, category, approval_status, directory_name) VALUES (:proposal_id, :pref_number, :book, :author, :isbn, :publisher, :edition, :year, :category, :approval_status, :directory_name)
	";
		$args = array(
			":proposal_id" => $proposal_id,
			":pref_number" => 1,
			":book" => trim(ucwords(strtolower($form_state['values']['book1']))),
			":author" => trim(ucwords(strtolower($form_state['values']['author1']))),
			":isbn" => trim($form_state['values']['isbn1']),
			":publisher" => trim(ucwords(strtolower($form_state['values']['publisher1']))),
			":edition" => trim($form_state['values']['edition1']),
			":year" => trim($form_state['values']['year1']),
			":category" => 0,
			":approval_status" => 0,
			":directory_name" => $form_state['values']['dir_name1']
		);
		$result = \Drupal::database()->query($query, $args, $query);
		if (!$result)
		{
			\Drupal::messenger()->addError(t('Error receiving your first book preference.'));
		} //!$result
	} //$form_state['values']['book1']
	
	/* sending email */
	$email_to = $user->mail;
	$from = \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email');
	$bcc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
	$cc = \Drupal::config('textbook_companion.settings')->get('textbook_companion_cc_emails');
	$params['proposal_received']['proposal_id'] = $proposal_id;
	$params['proposal_received']['user_id'] = $user->uid;
	$params['proposal_received']['headers'] = array(
		'From' => $from,
		'MIME-Version' => '1.0',
		'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
		'Content-Transfer-Encoding' => '8Bit',
		'X-Mailer' => 'Drupal',
		'Cc' => $cc,
		'Bcc' => $bcc
	);
	if (!drupal_mail('textbook_companion', 'proposal_received', $email_to, language_default(), $params, $from, TRUE))
		\Drupal::messenger()->addError('Error sending email message.');
	\Drupal::messenger()->addStatus(t('We have received you book proposal. We will get back to you soon.'));
	drupal_goto('');
}