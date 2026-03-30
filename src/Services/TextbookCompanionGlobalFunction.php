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
}