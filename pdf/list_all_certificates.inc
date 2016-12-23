<?php
/*	function _list_all_certificates()
{
global $user;
$uid1 = $user->uid;

$query2 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE proposal_status=3 AND uid=".$user->uid);
$data2 = db_fetch_object($query2);
if($data2->id)
/*while($data2 = db_fetch_object($query2))
{*/
/*  if($data2->id)
{
$search_rows = array();
global $output;
$output = '';
$query3 = db_query("SELECT * FROM textbook_companion_preference WHERE approval_status=1 AND proposal_id=".$data2->id);

while ($search_data3 = db_fetch_object($query3))
{
$search_rows[] = array($search_data3->isbn,$search_data3->book,$search_data3->author,l('Download Certificate', 'certificate/generate_pdf/'.$search_data3->id));
}
if ($search_rows)
{
$search_header = array('ISBN', 'Book Name', 'Author', 'Download Certificates');
$output = theme_table($search_header, $search_rows);
return $output;
}
else
{
echo("Error");
return '';
}
}
else
{
$query3 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid=".$user->uid);
$data3 = db_fetch_object($query3);
if($data3)
{
drupal_set_message('<strong>Your book is still under Review!</strong>', 'status');	
return '';
}
else
{
drupal_set_message('<strong>You need to propose a book <a href="/proposal">Book Proposal</a></strong>', 'status');
return '';
}
}
//}
} */
function _list_all_certificates()
  {
    global $user;
    /*$query_id =db_query("SELECT id FROM textbook_companion_proposal WHERE proposal_status=3 AND uid=".$user->uid);
    $exist_id = db_fetch_object($query_id);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal', array(
        'id'
    ));
    $query->condition('proposal_status', 3);
    $query->condition('uid', $user->uid);
    $result = $query->execute();
    $exist_id = $result->fetchObject();
    if ($exist_id->id)
      {
        if ($exist_id->id < 3)
          {
            drupal_set_message('<strong>You need to propose a book <a href="/proposal">Book Proposal</a></strong>', 'status');
            return '';
          }
        else
          {
            $search_rows = array();
            global $output;
            $output = '';
            $query3 = db_query("SELECT prop.id,pref.isbn,pref.book,pref.author FROM textbook_companion_proposal as prop,textbook_companion_preference as pref WHERE prop.proposal_status=3 AND pref.approval_status=1 AND pref.proposal_id=prop.id AND prop.uid=:uid", array(
                ":uid" => $user->uid
            ));
            while ($search_data3 = $query3->fetchObject())
              {
                if ($search_data3->id)
                  {
                    $search_rows[] = array(
                        $search_data3->isbn,
                        $search_data3->book,
                        $search_data3->author,
                        l('Download Certificate', 'textbook-companion/certificate/generate-pdf/' . $search_data3->id)
                    );
                  }
              }
            if ($search_rows)
              {
                $search_header = array(
                    'ISBN',
                    'Book Name',
                    'Author',
                    'Download Certificates'
                );
                $output = theme('table', array(
                    'header' => $search_header,
                    'rows' => $search_rows
                ));
                return $output;
              }
            else
              {
                echo ("Error");
                return '';
              }
          }
      }
    else
      {
        drupal_set_message('<strong>You need to propose a book <a href="/proposal">Book Proposal</a></strong>', 'status');
        return '';
      }
  }
?>
