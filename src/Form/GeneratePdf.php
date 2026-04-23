<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\GeneratePdf.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class GeneratePdf extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_pdf';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $mpath = drupal_get_path('module', 'textbook_companion');
    require($mpath . '/pdf/fpdf/fpdf.php');
    require($mpath . '/pdf/phpqrcode/qrlib.php');
    $user = \Drupal::currentUser();
    $x = $user->uid;
    $proposal_id = arg(3);
    $query2 = \Drupal::database()->query("SELECT * FROM {textbook_companion_preference} WHERE approval_status=1 AND  proposal_id= :prop_id", [
      ':prop_id' => $proposal_id
      ]);
    $data2 = $query2->fetchObject();
    $query3 = \Drupal::database()->query("SELECT * FROM {textbook_companion_proposal} WHERE id= :prop_id AND proposal_status =3", [
      ':prop_id' => $proposal_id
      ]);
    $data3 = $query3->fetchObject();
    if ($data3) {
      if ($data3->uid != $x) {
        \Drupal::messenger()->addError('Certificate is not available');
        return;
      }
    }
    $query4 = \Drupal::database()->query("SELECT COUNT( tce.id ) AS example_count FROM textbook_companion_example tce
						LEFT JOIN textbook_companion_chapter tcc ON tce.chapter_id = tcc.id
						LEFT JOIN textbook_companion_preference tcpe ON tcc.preference_id = tcpe.id
						LEFT JOIN textbook_companion_proposal tcpo ON tcpe.proposal_id = tcpo.id
						WHERE tcpo.proposal_status =3 AND tce.approval_status =1 AND tce.approval_status=1 AND tcpo.id = :prop_id", [
      ':prop_id' => $proposal_id
      ]);
    $data4 = $query4->fetchObject();
    if ($data4->example_count == 0) {
      \Drupal::messenger()->addError('Certificate is not available');
      return;
    }
    $number_of_example = $data4->example_count;
    $gender = [
      'salutation' => 'Mr. /Ms.',
      'gender' => 'He/She',
    ];
    if ($data3->gender) {
      if ($data3->gender == 'M') {
        $gender = [
          'salutation' => 'Mr.',
          'gender' => 'He',
        ];
      } //$data3->gender == 'M'
      else {
        $gender = [
          'salutation' => 'Ms.',
          'gender' => 'She',
        ];
      }
    } //$data3->gender
    $pdf = new FPDF('L', 'mm', 'Letter');
    if (!$pdf) {
      echo "Error!";
    } //!$pdf
    $pdf->AddPage();
    $image_bg = $mpath . "/pdf/images/bg.png";
    $pdf->Image($image_bg, 0, 0, $pdf->w, $pdf->h);
    //$pdf->Rect(5, 5, 267, 207, 'D');
    $pdf->SetMargins(18, 1, 18);
    //$pdf->Line(7.0, 7.0, 270.0, 7.0);
    //$pdf->Line(7.0, 7.0, 7.0, 210.0);
    //$pdf->Line(270.0, 210.0, 270.0, 7.0);
    //$pdf->Line(7.0, 210.0, 270.0, 210.0);
    $path = drupal_get_path('module', 'textbook_companion');
    $image1 = $mpath . "/pdf/images/om_logo.png";
    $pdf->Ln(15);
    //$pdf->Cell(200, 8, $pdf->Image($image1, 105, 15, 0, 28), 0, 1, 'C');
    $pdf->Ln(20);
    $pdf->SetFont('Arial', 'BI', 25);
    $pdf->SetTextColor(96, 69, 96);
    $pdf->Cell(240, 8, 'Certificate of Participation', '0', 1, 'C');
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'BI', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(240, 8, 'This is to certify that', '0', '1', 'C');
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'BI', 25);
    $pdf->SetTextColor(96, 69, 96);
    $pdf->Cell(240, 8, $data3->full_name, '0', '1', 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'I', 12);
    if (strtolower($data3->branch) != "others") {
      $pdf->SetTextColor(0, 0, 0);
      //$pdf->Cell(240, 8, 'from ' . $data3->university . ' has successfully', '0', '1', 'C');
      $pdf->MultiCell(240, 8, 'from ' . $data3->university . ' has successfully', '0', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'completed an OpenModelica Textbook Companion', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'by coding ' . $number_of_example . ' solved examples from the', '0', '1', 'C');
      $pdf->Ln(0);
      //$pdf->Cell(240, 8, 'Book: ' . $data2->book . ', Author: ' . $data2->author . '.', '0', '1', 'C');
      $pdf->MultiCell(240, 8, 'book: ' . $data2->book . ', author(s) ' . $data2->author . '.', '0', 'C');
      $pdf->Ln(0);
    } //strtolower($data3->branch) != "others"
    else {
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(240, 8, 'from ' . $data3->university . ' has successfully', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'completed an OpenModelica Textbook Companion', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'by coding ' . $number_of_example . ' solved examples from the', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'book: ' . $data2->book . ', author(s) ' . $data2->author . '.', '0', '1', 'C');
      $pdf->Ln(0);
    }
    $proposal_get_id = 0;
    $UniqueString = "";
    $tempDir = $path . "/pdf/temp_prcode/";
    $query = \Drupal::database()->select('textbook_companion_qr_code');
    $query->fields('textbook_companion_qr_code');
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();
    $data = $result->fetchObject();
    $DBString = $data->qr_code;
    $proposal_get_id = $data->proposal_id;
    if ($DBString == "" || $DBString == "null") {
      $UniqueString = generateRandomString();
      $query = "
				INSERT INTO textbook_companion_qr_code
				(proposal_id,qr_code)
				VALUES
				(:proposal_id,:qr_code)
				";
      $args = [
        ":proposal_id" => $proposal_id,
        ":qr_code" => $UniqueString,
      ];
      $result = \Drupal::database()->query($query, $args, $query);
    } //$DBString == "" || $DBString == "null"
    else {
      $UniqueString = $DBString;
    }
    $codeContents = "https://om.fossee.in/textbook-companion/certificate/verify/" . $UniqueString;
    $fileName = 'generated_qrcode.png';
    $pngAbsoluteFilePath = $tempDir . $fileName;
    $urlRelativeFilePath = $path . "/pdf/temp_prcode/" . $fileName;
    QRcode::png($codeContents, $pngAbsoluteFilePath);
    $pdf->Cell(240, 4, '', '0', '1', 'C');
    $pdf->SetX(95);
    $pdf->write(0, 'The work done is available at ');
    $pdf->SetFont('', 'U');
    $pdf->SetTextColor(96, 69, 96);
    $pdf->write(0, 'https://om.fossee.in', 'http://om.fossee.in');
    $pdf->SetFont('', '');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->write(0, '.', '.');
    $pdf->Ln(5);
    $pdf->SetX(198);
    $pdf->SetFont('', '');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(-85);
    $pdf->SetX(200);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('', '');
    $sign = $path . "/pdf/images/sign.png";
    $pdf->Image($sign, $pdf->GetX() - 20, $pdf->GetY() + 15, 75, 0);
    /*$pdf->Cell(0, 8, 'Prof. Kannan M. Moudgalya', 0, 1, 'L');
	$pdf->SetX(199);
	$pdf->SetFont('Arial', '', 10);
	$pdf->Cell(0, 7, 'Co - Principal Investigator - FOSSEE', 0, 1, 'L');
	$pdf->SetX(190);
	$pdf->Cell(0, 7, ' Dept. of Chemical Engineering, IIT Bombay.', 0, 1, 'L');*/
    $pdf->SetX(29);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetY(-58);
    $pdf->SetX(28);
    $pdf->Cell(0, 2, $UniqueString, 0, 0, 'C');
    $pdf->SetX(29);
    $pdf->SetY(-50);
    $image4 = $path . "/pdf/images/verify_content.png";
    //$pdf->Image($image4, $pdf->GetX(), $pdf->GetY(), 20, 0);
    $pdf->SetY(-50);
    $pdf->SetX(80);
    $image3 = $path . "/pdf/images/mhrd.png";
    $image2 = $path . "/pdf/images/fossee.png";
    $pdf->Image($image2, $pdf->GetX() - 60, $pdf->GetY() + 7, 40, 0);
    $pdf->Image($pngAbsoluteFilePath, $pdf->GetX() + 50, $pdf->GetY() - 5, 30, 0);
    $pdf->Image($image3, $pdf->GetX() + 120, $pdf->GetY() + 3, 40, 0);
    $pdf->Image($image4, $pdf->GetX() - 15, $pdf->GetY() + 28, 150, 0);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(0, 0, 0);
    $filename = str_replace(' ', '-', $data3->full_name) . '-OM-Textbook-Certificate.pdf';
    $file = $path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
    $pdf->Output($file, 'F');
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Description: File Transfer");
    header("Content-Length: " . filesize($file));
    flush();
    $fp = fopen($file, "r");
    while (!feof($fp)) {
      echo fread($fp, 65536);
      flush();
    } //!feof($fp)
    fclose($fp);
    unlink($file);
    //drupal_goto('certificate');
    return;
  }
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }

}
?>
