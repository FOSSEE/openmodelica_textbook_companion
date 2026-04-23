<?php
//Used to generate forms dynamiclay
function _list_all_copyright_forms()
{
	$user = \Drupal::currentUser();
	$query_id = \Drupal::database()->query("SELECT id FROM textbook_companion_proposal WHERE proposal_status=3 AND uid= :uid", array(
		':uid' => $user->uid
	));
	$exist_id = $query_id->fetchObject();
	if ($exist_id->id)
	{
		if ($exist_id->id < 3)
		{
			\Drupal::messenger()->addStatus('<strong>You need to propose a <a href="http://dwsim.fossee.in/textbook-companion/proposal">Book Proposal</a></strong> or if you have already proposed then your book is under reviewing process');
			return '';
		} //$exist_id->id < 3
		else
		{
			$search_rows = array();
			global $output;
			$output = '';
			$query3 = \Drupal::database()->query("SELECT prop.id,pref.isbn,pref.book,pref.author FROM textbook_companion_proposal as prop,textbook_companion_preference as pref WHERE prop.proposal_status = 3 AND pref.approval_status =1 AND pref.proposal_id = prop.id AND prop.uid = :uid", array(
				':uid' => $user->uid
			));
			while ($search_data3 = $query3->fetchObject())
			{
				if ($search_data3->id)
				{
					// @FIXME
// l() expects a Url object, created from a route name or external URI.
// $search_rows[] = array(
// 						$search_data3->isbn,
// 						$search_data3->book,
// 						$search_data3->author,
// 						l('Download Copyright Form', 'Summer_Internship_Forms/copyright-form/generate_pdf/' . $search_data3->id),
// 						l('Download Undertaking Form', 'Summer_Internship_Forms/undertaking-form/generate_pdf/' . $search_data3->id)
// 					);

				} //$search_data3->id
			} //$search_data3 = $query3->fetchObject()
			if ($search_rows)
			{
				$search_header = array(
					'ISBN',
					'Book Name',
					'Author',
					'Download Copyright Form',
					'Download Undertaking Form'
				);
				// @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $output = theme('table', array(
// 					'header' => $search_header,
// 					'rows' => $search_rows
// 				));

				return $output;
			} //$search_rows
			else
			{
				echo ("Error");
				return '';
			}
		}
	} //$exist_id->id
	else
	{
		\Drupal::messenger()->addStatus('<strong>You need to propose a book <a href="http://dwsim.fossee.in/textbook-companion/proposal">Book Proposal</a></strong> or if you have already proposed then your book is under reviewing process');
		return '';
	}
}


