$pending_rows = array();
  $pending_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status = 0 ORDER BY id DESC");
  while ($pending_data = db_fetch_object($pending_q))
  {
    $pending_rows[$pending_data->id] = array(date('d-m-Y', $pending_data->creation_date), l($pending_data->full_name, 'user/' . $pending_data->uid), date('d-m-Y',  $pending_data->completion_date), l('Approve', 'manage_proposal/approve/' . $pending_data->id) . ' | ' . l('Edit', 'manage_proposal/edit/' . $pending_data->id));
  }



  /* check if there are any pending proposals */
  if (!$pending_rows)
  {
    drupal_set_message(t('There are no pending proposals.'), 'status');
    return '';
  }


  $pending_header = array('Date of Submission', 'Contributor Name', 'Date of Completion', 'Action');
  $output = theme_table($pending_header, $pending_rows);
  return $output;







global $user;
                $uid1 = $user->uid;

                $query2 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status=3 AND uid=".$user->uid);
                $data2 = db_fetch_object($query2);
                if($data2->id)
                {
                        $search_rows = array();
                        $output = '';
                        $query3 = db_query("SELECT * FROM textbook_companion_preference WHERE approval_status=1 AND proposal_id=".$data2->id);

                        while ($search_data3 = db_fetch_object($query3))
                        {
                              $search_rows[] = array($search_data3->id,$search_data3->proposal_id,$search_data3->book, l('Download Certificate', 'certificate/generate_pdf'.$search_data3->proposal_id));
                        }
                        if ($search_rows)
                        {
                                $search_header = array('Id', 'Proposal Id', 'Book Name', 'Download Certificates');
                                $output .= theme_table($search_header, $search_rows);
                        }
                        else
                        {
                                echo("Error");
                        }
                        return $output;
                }
                else
                {
                        echo("Book Still Under Review");
                };

