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
}