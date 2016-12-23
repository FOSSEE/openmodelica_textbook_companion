<?php 
function cheque_proposal_all()
{
  
  
  $form['#redirect'] = FALSE;
  $form['search_cheque'] = array(
      '#type' => 'textfield',
      '#title' => t('Search'),
      '#size' => 48,
  );
  $form['submit_cheque'] = array(
      '#type' => 'submit',
      '#value' => t('Search')
  );
  $form['cancel_cheque'] = array(
     '#type' => 'markup',
     '#value' => l(t('Cancel'), ''),
  );
  
  
  $count=20;
   /* get pending proposals to be approved */
  $proposal_rows = array();

  /*$proposal_q = "SELECT * FROM {textbook_companion_proposal} ORDER BY id DESC";*/
	$query = db_select('textbook_companion_proposal');
	$query->fields('textbook_companion_proposal');
	$query->orderBy('id', 'DESC');

  
  /*$pagerquery = pager_query($proposal_q, $count); */
	$pagerquery = $query->extend('PagerDefault')->limit($count)->execute();

  while ($proposal_data = $pagerquery->fetchObject())
  {
    /* get preference */

    /*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data = db_fetch_object($preference_q);*/

	$query = db_select('textbook_companion_preference');
	$query->fields('textbook_companion_preference');
	$query->condition('proposal_id', $proposal_data->id);
	$query->condition('approval_status', 1);
	$query->range(0, 1);
	$result = $query->execute();
	$preference_data =$result->fetchObject();

    /*$preference_q1 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
    $preference_data1 = db_fetch_object($preference_q1);*/

	$query = db_select('textbook_companion_proposal');
	$query->fields('textbook_companion_proposal');
	$query->condition('uid', $proposal_data->id);
	$query->condition('proposal_status', 1);
	$query->range(0, 1);
	$result = $query->execute();
	$preference_data1=$result->fetchObject();

    
    $proposal_status = '';
    switch ($proposal_data->proposal_status)
    {
    case 0: $proposal_status = 'Pending'; break;
    case 1: $proposal_status = 'Approved'; break;
    case 2: $proposal_status = 'Dis-approved'; break;
    case 3: $proposal_status = 'Completed'; break;
    default: $proposal_status = 'Unknown'; break;
    }
    $proposal_rows[] = array(date('d-m-Y', $proposal_data->creation_date), l($proposal_data->full_name, 'user/' . $proposal_data->uid), date('d-m-Y', $proposal_data->completion_date), l('Form Submission', 'manage_proposal/paper_submission/' . $proposal_data->id) . ' | ' . l('Cheque Details', 'cheque_contact/status/' . $proposal_data->id));
  }

  /* check if there are any pending proposals */
  if (!$proposal_rows)
  {
    drupal_set_message(t('There are no proposals.'), 'status');
    return '';
  }
 
  $proposal_header = array('Date of Submission', 'Contributor Name', 'Expected Date of Completion', 'Status');
  $output = theme('table',array('header' =>$proposal_header,'rows'=>$proposal_rows));
  return $output . theme_pager($count);
  }
  return $form;

?>
