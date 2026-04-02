<?php
 
namespace Drupal\textbook_companion\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\user\Entity\User;


class TextBookCompanionGlobalFunction{
function ucname($string)
{
	$string = ucwords(strtolower($string));
	foreach (array(
		'-',
		'\''
	) as $delimiter)
	{
		if (strpos($string, $delimiter) !== false)
		{
			$string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
		} //strpos($string, $delimiter) !== false
	} //array( '-', '\'' ) as $delimiter
	return $string;
}
function _dir_name($book, $author, $pref_id)
{
	$database = \Drupal::database();
	if (!$pref_id)
	{
		$book_title = $this->ucname($book);
		$author = $this->ucname($author);
		$dir_name = $book_title . " " . "by" . " " . $author;
		$directory_name = str_replace("__", "_", str_replace(" ", "_", $dir_name));

// Check if the directory name already exists in the database.
$query = $database->select('textbook_companion_preference', 'p')
  ->fields('p', ['id'])
  ->condition('directory_name', $directory_name)
  ->condition('approval_status', 1);

// Use countQuery() to efficiently count the number of matching rows.
$count = $query->countQuery()->execute()->fetchField();

// If a matching record exists, set an error message.
if ($count > 0) {
  \Drupal::messenger()->addError(t('Book is already allotted. Please try another book or contact the administrator.'));
  return;
}
 //$result > 0
	} //!$pref_id
	else
	{
		$book_title = $this->ucname($book);
		$author = $this->ucname($author);
		$dir_name = $book_title . " " . "by" . " " . $author;
		$directory_name = str_replace("__", "_", str_replace(" ", "_", $dir_name));
		// Check if the directory name already exists for another record.
$query = $database->select('textbook_companion_preference', 'p')
  ->fields('p', ['id'])
  ->condition('directory_name', $directory_name)
  ->condition('id', $pref_id, '<>'); // Exclude the current record.

$count = $query->countQuery()->execute()->fetchField();

if ($count > 1) {
  \Drupal::messenger()->addError(t('Book is already present. Please try another book or contact the administrator.'));
  return;
}
 //$result > 1
	}

	return $directory_name;
}


function textbook_companion_samplecode_path()
{
	return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'openmodelica_uploads/tbc_sample_code/';
}


function textbook_companion_check_valid_filename($file_name)
{
	if (!preg_match('/^[0-9a-zA-Z\_\.]+$/', $file_name))
		return FALSE;
	else if (substr_count($file_name, ".") > 1)
		return FALSE;
	else
		return TRUE;
}
function check_name($name = '')
{
	if (!preg_match('/^[0-9a-zA-Z\ ]+$/', $name))
		return FALSE;
	else
		return TRUE;
}
function check_chapter_number($name = '')
{
	if (!preg_match('/^([0-9])+(\.([0-9a-zA-Z])+)+$/', $name))
		return FALSE;
	else
		return TRUE;
}
function textbook_companion_path()
{
	return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'openmodelica_uploads/tbc_uploads/';
}
function textbook_companion_temp_path()
{
	return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'openmodelica_uploads/';
}

function delete_file($file_id)
{
	$root_path = textbook_companion_path();
	/*$file_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE id = %d LIMIT 1", $file_id);*/
	$file_q = \Drupal::database()->query("SELECT * FROM textbook_companion_preference tcp JOIN textbook_companion_chapter tcc ON tcp.id = tcc.preference_id JOIN textbook_companion_example tce ON tcc.id=tce.chapter_id JOIN textbook_companion_example_files tcef on tce.id = tcef.example_id WHERE tcef.id = :example_id", array(
		':example_id' => $file_id
	));
	/*$query = db_select('textbook_companion_example_files');
	$query->fields('textbook_companion_example_files');
	$query->condition('id', $file_id);
	$query->range(0, 1);
	$file_q = $query->execute();*/
	$file_data = $file_q->fetchObject();
	if (!$file_data)
	{
		\Drupal::messenger()->addError('Invalid file specified.');
		return FALSE;
	} //!$file_data
	if (!file_exists($root_path . $file_data->directory_name . '/' . $file_data->filepath))
	{
		\Drupal::messenger()->addError(t('Error deleting !file. File does not exists.', array(
			'!file' => $file_data->filepath
		)));
		return FALSE;
	} //!file_exists($root_path . $file_data->directory_name . '/' . $file_data->filepath)
	/* removing example file */
	if (!unlink($root_path . $file_data->directory_name . '/' . $file_data->filepath))
	{
		\Drupal::messenger()->addError(t('Error deleting !file', array(
			'!file' => $file_data->filepath
		)));
		/* sending email to admins */
		$email_to = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
		$params['standard']['subject'] = "[ERROR] Error deleting file";
		$params['standard']['body'] = "Error deleting file by " . $user->uid . " at " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . " :
      file id : " . $file_id . "
      file path : " . $file_data->directory_name . '/' . $file_data->filepath;
		if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE))
			\Drupal::messenger()->addError('Error sending email message.');
		return FALSE;
	} //!unlink($root_path . $file_data->directory_name . '/' . $file_data->filepath)
	else
	{
		/* deleting example files database entries */
		/*db_query("DELETE FROM {textbook_companion_example_files} WHERE id = %d", $file_id);*/
		$query = \Drupal::database()->delete('textbook_companion_example_files');
		$query->condition('id', $file_id);
		$num_deleted = $query->execute();
		return TRUE;
	}
}
function delete_example($example_id)
{
	$user = \Drupal::currentUser();
	$root_path = textbook_companion_path();
	$status = TRUE;
	/*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $example_id);
	$example_data = db_fetch_object($example_q);*/
	$query = \Drupal::database()->select('textbook_companion_example');
	$query->fields('textbook_companion_example');
	$query->condition('id', $example_id);
	$example_q = $query->execute();
	$example_data = $example_q->fetchObject();
	if (!$example_data)
	{
		\Drupal::messenger()->addError(t('Invalid example.'));
		return FALSE;
	} //!$example_data
	/*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
	$chapter_data = db_fetch_object($chapter_q);*/
	$chapter_q = \Drupal::database()->query("SELECT tcp.id as pref_id, tcp.directory_name, tcc.*
FROM textbook_companion_preference tcp
JOIN textbook_companion_chapter tcc
ON tcp.id= tcc.preference_id
WHERE  tcc.id = :chapter_id", array(
		":chapter_id" => $example_data->chapter_id
	));
	/*$query = db_select('textbook_companion_chapter');
	$query->fields('textbook_companion_chapter');
	$query->condition('id', $example_data->chapter_id);
	$chapter_q = $query->execute();*/
	$chapter_data = $chapter_q->fetchObject();
	if (!$chapter_data)
	{
		\Drupal::messenger()->addError(t('Invalid example chapter.'));
		return FALSE;
	} //!$chapter_data
	/* deleting example files */
	/*$examples_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
	$query = \Drupal::database()->select('textbook_companion_example_files');
	$query->fields('textbook_companion_example_files');
	$query->condition('example_id', $example_id);
	$examples_files_q = $query->execute();
	while ($examples_files_data = $examples_files_q->fetchObject())
	{
		if (!file_exists($root_path . $chapter_data->directory_name . '/' . $examples_files_data->filepath))
		{
			$status = FALSE;
			//var_dump($root_path . $chapter_data->directory_name . '/' . $examples_files_data->filepath);
			die;
			\Drupal::messenger()->addError(t('Error deleting !file. File does not exists.', array(
				'!file' => $examples_files_data->filepath
			)));
			continue;
		} //!file_exists($root_path . $chapter_data->directory_name . '/' . $examples_files_data->filepath)
		/* removing example file */
		if (!unlink($root_path . $chapter_data->directory_name . '/' . $examples_files_data->filepath))
		{
			$status = FALSE;
			\Drupal::messenger()->addError(t('Error deleting !file', array(
				'!file' => $examples_files_data->filepath
			)));
			/* sending email to admins */
			$email_to = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
			$params['standard']['subject'] = "[ERROR] Error deleting example file";
			$params['standard']['body'] = "Error deleting example files by " . $user->uid . " at " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . " :
        example id : " . $example_id . "
        file id : " . $examples_files_data->id . "
        file path : " . $examples_files_data->filepath;
			// if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE))
			// 	\Drupal::messenger()->addError('Error sending email message.');
		} //!unlink($root_path . $chapter_data->directory_name . '/' . $examples_files_data->filepath)
		else
		{
			/* deleting example files database entries */
			/*db_query("DELETE FROM {textbook_companion_example_files} WHERE id = %d", $examples_files_data->id);*/
			$query = \Drupal::database()->delete('textbook_companion_example_files');
			$query->condition('id', $examples_files_data->id);
			$num_deleted = $query->execute();
		}
	} //$examples_files_data = $examples_files_q->fetchObject()
	if (!$status)
		return FALSE;
	/* removing example folder */
	$ex_path = $chapter_data->directory_name . '/' . 'CH' . $chapter_data->number . '/' . 'EX' . $example_data->number;
	$dir_path = $root_path . $ex_path;
	if (is_dir($dir_path))
	{
		if (!rmdir($dir_path))
		{
			\Drupal::messenger()->addError(t('Error deleting folder !folder', array(
				'!folder' => $dir_path
			)));
			/* sending email to admins */
			$email_to = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
			$params['standard']['subject'] = "[ERROR] Error deleting folder";
			$params['standard']['body'] = "Error deleting folder " . $dir_path . " by " . $user->uid . " at " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			// if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE))
			// 	\Drupal::messenger()->addError('Error sending email message.');
			return FALSE;
		} //!rmdir($dir_path)
	} //is_dir($dir_path)
	else
	{
		\Drupal::messenger()->addError(t('Cannot delete example folder. !folder does not exists.', array(
			'!folder' => $dir_path
		)));
		return FALSE;
	}
	/* deleting example dependency and exmaple database entries */
	/*db_query("DELETE FROM {textbook_companion_example_dependency} WHERE example_id = %d", $example_id);*/
	//$query = db_delete('textbook_companion_example_dependency');
	//$query->condition('example_id', $example_id);
	//$num_deleted = $query->execute();
	/*db_query("DELETE FROM {textbook_companion_example} WHERE id = %d", $example_id);*/
	$query = \Drupal::database()->delete('textbook_companion_example');
	$query->condition('id', $example_id);
	$num_deleted = $query->execute();
	return $status;
}
function delete_chapter($chapter_id)
{
	$status = TRUE;
	$root_path = textbook_companion_path();
	/*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
	$chapter_data = db_fetch_object($chapter_q);*/
	/*$query = db_select('textbook_companion_chapter');
	$query->fields('textbook_companion_chapter');
	$query->condition('id', $chapter_id);
	$chapter_q = $query->execute();
	$chapter_data = $chapter_q->fetchObject();*/
	$chapter_q = \Drupal::database()->query("SELECT tcp.id as pref_id, tcp.directory_name, tcc.*
FROM textbook_companion_preference tcp
JOIN textbook_companion_chapter tcc
ON tcp.id= tcc.preference_id
WHERE  tcc.id = :chapter_id", array(
		":chapter_id" => $chapter_id
	));
	$chapter_data = $chapter_q->fetchObject();
	if (!$chapter_data)
	{
		\Drupal::messenger()->addError('Invalid chapter.');
		return FALSE;
	} //!$chapter_data
	/* deleting examples */
	/*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d", $chapter_id);*/
	$query = \Drupal::database()->select('textbook_companion_example');
	$query->fields('textbook_companion_example');
	$query->condition('chapter_id', $chapter_id);
	$example_q = $query->execute();
	while ($example_data = $example_q->fetchObject())
	{
		if (!delete_example($example_data->id))
			$status = FALSE;
	} //$example_data = $example_q->fetchObject()
	if ($status)
	{
		$dir_path = $root_path . $chapter_data->directory_name . '/CH' . $chapter_data->number;
		if (is_dir($dir_path))
		{
			$res = rmdir($dir_path);
			if (!$res)
			{
				\Drupal::messenger()->addError(t('Error deleting chapter folder !folder', array(
					'!folder' => $dir_path
				)));
				/* sending email to admins */
				$email_to = \Drupal::config('textbook_companion.settings')->get('textbook_companion_emails');
				$params['standard']['subject'] = "[ERROR] Error deleting folder";
				$params['standard']['body'] = "Error deleting folder " . $dir_path;
				// if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, \Drupal::config('textbook_companion.settings')->get('textbook_companion_from_email'), TRUE))
				// 	\Drupal::messenger()->addError('Error sending email message.');
				// return FALSE;
			} //!$res
			else
			{
				/* deleting chapter details from database */
				/*db_query("DELETE FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);*/
				$query = \Drupal::database()->delete('textbook_companion_chapter');
				$query->condition('id', $chapter_id);
				$num_deleted = $query->execute();
				return TRUE;
			}
		} //is_dir($dir_path)
		else
		{
			\Drupal::messenger()->addError(t('Cannot delete chapter folder. !folder does not exists.', array(
				'!folder' => $dir_path
			)));
			return FALSE;
		}
	} //$status
	return FALSE;
}
function delete_book($book_id)
{
	$status = TRUE;
	$root_path = textbook_companion_path();
	/*$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $book_id);
	$preference_data = db_fetch_object($preference_q);*/
	$query = \Drupal::database()->select('textbook_companion_preference');
	$query->fields('textbook_companion_preference');
	$query->condition('id', $book_id);
	$preference_q = $query->execute();
	$preference_data = $preference_q->fetchObject();
	if (!$preference_data)
	{
		\Drupal::messenger()->addError('Invalid book.');
		return FALSE;
	} //!$preference_data
	/* delete chapters */
	/*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $preference_data->id);*/
	$query = \Drupal::database()->select('textbook_companion_chapter');
	$query->fields('textbook_companion_chapter');
	$query->condition('preference_id', $preference_data->id);
	$chapter_q = $query->execute();
	while ($chapter_data = $chapter_q->fetchObject())
	{
		if (!delete_chapter($chapter_data->id))
		{
			$status = FALSE;
		} //!delete_chapter($chapter_data->id)
	} //$chapter_data = $chapter_q->fetchObject()
	return $status;
}

function CreateReadmeFileTextbookCompanion($proposal_id) {
  // Get the database connection
  $database = Database::getConnection();
//var_dump($proposal_id);die;
  // Query to fetch proposal data
  $query = $database->select('textbook_companion_preference', 'tcp');
  $query->join('textbook_companion_proposal', 'tcc', 'tcp.proposal_id = tcc.id');
  $query->fields('tcp');
  $query->fields('tcc', ['full_name', 'course', 'branch', 'university']);
  $query->condition('tcc.proposal_status', 3);
  $query->condition('tcp.approval_status', 1);
  $query->condition('tcc.id', $proposal_id);

  $result = $query->execute();
  $proposal_data = $result->fetchObject();
//var_dump($proposal_data);die;
  if (!$proposal_data) {
    \Drupal::logger('textbook_companion')->error('No proposal data found for ID: @proposal_id', ['@proposal_id' => $proposal_id]);
    return;
  }

  // Get the root path for textbook companion files
  $root_path = textbook_companion_path();

  // Define the README file path
  $readme_path = $root_path . $proposal_data->directory_name . '/README.txt';
//var_dump($readme_path);die;
  // Create directory if it doesn't exist
  $directory_path = $root_path . '/' . $proposal_data->directory_name;
  if (!is_dir($directory_path)) {
    \Drupal::logger('textbook_companion')->error('Failed to create directory: @directory_path', ['@directory_path' => $directory_path]);
    return;
  }

  // Create and write to the README file
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

  // Write the content to the README file
  $file = file_put_contents($readme_path,$txt);
  if (!$file) {
    \Drupal::logger('textbook_companion')->error('Failed to create README file at: @readme_path', ['@readme_path' => $readme_path]);
    return;
  }
}
function RenameDir($preference_id, $dir_name) {
  // Use dependency injection for services like database, file system, and messenger.
  $database = \Drupal::database();
  $file_system = \Drupal::service('file_system');
  $messenger = \Drupal::messenger();

  // Fetch the record from the database.
  $query = $database->select('textbook_companion_preference', 'tcp')
    ->fields('tcp', ['directory_name', 'proposal_id', 'id'])
    ->condition('id', $preference_id)
    ->execute();

  $result = $query->fetchObject();

  if ($result) {
    $base_path = textbook_companion_path();
    $old_dir = $base_path . '/' . $result->directory_name;
    $old_id_dir = $base_path . '/' . $result->id;

    // Check if the directory exists and rename it.
    if (is_dir($old_dir)) {
      $new_dir = $base_path . '/' . $dir_name;
      if (rename($old_dir, $new_dir)) {
        $this->CreateReadmeFileTextbookCompanion($result->proposal_id);
        return $new_dir;
      } else {
        $messenger->addError(t('Failed to rename directory. Please check permissions or contact the administrator.'));
        return FALSE;
      }
    } elseif (is_dir($old_id_dir)) {
      $new_dir = $base_path . '/' . $dir_name;
      if (rename($old_id_dir, $new_dir)) {
        $this->CreateReadmeFileTextbookCompanion($result->proposal_id);
        return $new_dir;
      } else {
        $messenger->addError(t('Failed to rename directory. Please check permissions or contact the administrator.'));
        return FALSE;
      }
    } else {
      $messenger->addWarning(t('Cannot rename the directory. If you are editing a proposal before approval, the directory may not exist yet. Contact the administrator for assistance.'));
      return FALSE;
    }
  } else {
    $messenger->addError(t('Book names directory not found in the database.'));
    return FALSE;
  }
}
}