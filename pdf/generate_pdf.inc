<?php
function generate_pdf()
  {
    require('fpdf/fpdf.php');
    global $user;
    $x = $user->uid;
    $proposal_id = arg(3);
    /*$query2 = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status=1 AND  proposal_id=".		$proposal_id);
    $data2 = db_fetch_object($query2);*/
    $query = db_select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('approval_status', 1);
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();
    $data2 = $result->fetchObject();
    /*$query3 = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id=".$proposal_id);
    $data3 = db_fetch_object($query3);*/
    $query = db_select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    $data3 = $result->fetchObject();
    //var_dump($data3->gender);
    $gender = array(
        'salutation' => 'Mr. /Ms.',
        'gender' => 'He/She'
    );
    if ($data3->gender)
      {
        if ($data3->gender == 'M')
          {
            $gender = array(
                'salutation' => 'Mr.',
                'gender' => 'He'
            );
          }
        else
          {
            $gender = array(
                'salutation' => 'Ms.',
                'gender' => 'She'
            );
          }
      }
    //die;
    /*if($data3->proposal_status == 3)
    {*/
    $pdf = new FPDF('L', 'mm', 'Letter');
    if (!$pdf)
      {
        echo "Error!";
      }
    $pdf->AddPage();
    $pdf->SetMargins(18, 1, 18);
    $pdf->Line(7.0, 7.0, 270.0, 7.0);
    $pdf->Line(7.0, 7.0, 7.0, 210.0);
    $pdf->Line(270.0, 210.0, 270.0, 7.0);
    $pdf->Line(7.0, 210.0, 270.0, 210.0);
    $path = drupal_get_path('module', 'textbook_companion');
    $pdf->Image($path . "/pdf/images/dwsim_logo.png", 10, 9, 0, 15);
    $pdf->Image($path . "/pdf/images/fossee.png", 228, 9, 0, 15);
    $pdf->SetFont('Arial', 'B', 25);
    $pdf->Ln(30);
    $pdf->Cell(240, 8, 'Certificate', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(240, 8, 'Textbook Companion', '0', '1', 'C');
    $pdf->Ln(20);
    //$pdf->Cell(240,8,'IIT Bombay', '0','1','C');
    $pdf->SetFont('Arial', '', 12);
    /*	if(strtolower($data3->branch)!="others")
    {
    $pdf->MultiCell(240,8,'This is to certify that '.$gender['salutation']." ".$data3->full_name.' from the Department of '.$data3->branch.', '.$data3->university.' has successfully completed Internship under Scilab Textbook Companion for a duration equivalent to six weeks. '.$gender['gender'].' has coded, in DWSIM, all the solved examples of the allotted textbook: '.$data2->book.' by '.$data2->author.'.', 0);
    }
    else
    {*/
    $pdf->MultiCell(240, 8, 'This is to certify that ' . $gender['salutation'] . " " . $data3->full_name . ' from ' . $data3->university . ' has successfully completed training under DWSIM Textbook Companion for a duration equivalent to six weeks. ' . $gender['gender'] . ' has coded, in DWSIM, all the solved examples of the allotted textbook: ' . $data2->book . ' by ' . $data2->author . '.', 0);
    //}
    $pdf->Cell(10, 10, 'The work done is available at ', '0', '0', 'L');
    $pdf->SetX(75);
    $pdf->SetFont('', 'U');
    $pdf->SetTextColor(0, 0, 255);
    $pdf->write(10, 'http://dwsim.fossee.in', 'http://dwsim.fossee.in');
    $pdf->SetFont('', '');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->write(10, '.', '.');
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(10, 10, 'This work was funded by the FOSSEE project, IIT Bombay (for more details visit', '0', '0', 'L');
    $pdf->SetX(170);
    $pdf->SetFont('', 'U');
    $pdf->SetTextColor(0, 0, 255);
    $pdf->write(10, 'http://fossee.in', 'http://fossee.in');
    $pdf->SetX(198);
    $pdf->SetFont('', '');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->write(10, ').');
    $pdf->SetY(-50);
    $pdf->SetX(209);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('', 'B');
    $pdf->Image($path . "/pdf/images/sign.png", 212, 151, 0, 15);
    //$pdf->SetX(206);
    $pdf->Cell(0, 7, 'Prof. Kannan M Moudgalya', 0, 1, 'L');
    $pdf->SetX(195);
    $pdf->Cell(0, 7, 'Principal Investigator - FOSSEE', 0, 1, 'L');
    $pdf->SetX(195);
    $pdf->Cell(0, 7, ' Dept. of Chemical Engineering', 0, 1, 'L');
    $pdf->SetX(216);
    $pdf->Cell(0, 7, 'IIT Bombay', 0, 1, 'L');
    $cur_date = date('jS F, Y');
    $pdf->SetY(180);
    $pdf->SetFont('', '');
    $pdf->Cell(200, 0, ' Date: ' . $cur_date . '', 0, 1, 'L');
    //$pdf->Cell(200,0,' Date: 28th August, 2013',0,1,'L');
    $pdf->Cell(200, 15, 'Email: contact-dwsim@sfossee.in', 0, 1, 'L');
    $name = $data3->full_name;
    $certificate_name = str_replace(' ', '_', $name);
    $pdf->Output($certificate_name . '_DWSIM_TBC_Certificate.pdf', 'D');
    /*}
    else
    {
    drupal_set_message('Your Book Is Still Under Review.', 'status');
    }*/
  }
?>
