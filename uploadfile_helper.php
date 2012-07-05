<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Uploaded Files helper.
 * 
 * Object wrapper for uploaded files that are available via the $_FILES array.
 * Provides accessors to each file's name, size, tmp_name, type and error code,
 * as well as method for saving the file to a final location.
 * 
 * A basic usage is as follows:
 * 
 * 	$save_path = 'uploaded_files/';
 * 	$files = UploadFile::get_uploaded();
 * 
 * 	if (!empty($files))
 * 	{
 * 		foreach ($files as $file)
 * 		{
 * 			try
 * 			{
 * 				$file->save_as($save_path . $file->get_name());
 * 				$messages[] = $file->get_name() . ' was saved';
 * 			}
 * 			catch (UploadFileException $ex)
 * 			{
 * 				$messages[] = $ex->getMessage();
 * 			}
 * 		}
 * 	}
 * 	else
 * 	{
 * 		$messages[] = 'no files were uploaded';
 * 	}
 * 
 * 
 * @author Jeremy Elliot
 *
 * For upload error codes refer to 
 * http://www.php.net/manual/en/features.file-upload.errors.php
 */
class UploadFile {

	private $type; // MIME type as reported by browser
	private $size; // size of the uploaded file (bytes)
	private $error; // error code (0 == success) 
	private $tmp_name; // name of temporary file
	private $name; // name of file on client machine
	private $uid; // generated id, helps avoid filename conflicts when saving
	private $file_name = false; // set when file has been moved from temp dir

	/**
	 * Returns an array of UploadFiles created from the $_FILES array.
	 * 
	 * If a form was submitted that contained the following elements:
	 * 
	 * 		<input type="file" name="file_input1"/>
	 * 		<input type="file" name="file_input2"/>
	 * 		<input type="file" name="file_input3[0]"/>
	 * 		<input type="file" name="file_input3[1]"/>
	 * 		<input type="file" name="file_input3[2]"/>
	 * 
	 * 	get_uploaded() would return an array like this:
	 * 
	 * 		array(
	 * 			'file_input1' => UploadFile,
	 * 			'file_input2' => UploadFile,
	 * 			'file_input3' => array(
	 * 				UploadFile, 
	 * 				UploadFile, 
	 * 				UploadFile
	 * 			)
	 * 		);
	 * 
	 * @return array an array of UploadFile objects
	 */

	public static function get_uploaded()
	{
		$uploaded_files = array();
		foreach ($_FILES as $field_name => $properties)
		{
			if (is_array($properties['error']))
			{
				// multiple files for this field
				foreach ($properties['error'] as $key => $error)
				{
					$props = array(
							'error' => $error,
							'type' => $properties['type'][$key],
							'size' => $properties['size'][$key],
							'tmp_name' => $properties['tmp_name'][$key],
							'name' => $properties['name'][$key]
						);
					$uploaded_files[$field_name][$key] = new UploadFile($props);
				}
			}
			else
			{
				// single file for this form field
				$uploaded_files[$field_name] = new UploadFile($properties);
			}
		}
		return $uploaded_files;
	}

	/**
	 * Saves this uploaded file to a new location. An uploaded file can only 
	 * be saved once; after that it must be dealt with as a regular file.
	 * @param string $file_path full path of filename to save to.
	 */
	public function save_as($file_path)
	{
		if ($this->file_name)
		{
			throw new UploadFileException("cannot save_as: already saved");
		}
		if (!$this->is_successful())
		{
			throw new UploadFileException(
					"unable to save_as: upload error " . $this->error);
		}
		if (!move_uploaded_file($this->getTempName(), $file_path))
		{
			throw new UploadFileException("failed to save_as({$file_path})");
		}
		$this->file_name = $file_path;
	}

	/**
	 * creates a new UploadFile with the specified properties. 
	 * The properties array is in the same form as the properties 
	 * in $_FILES[<fieldname>].
	 * This means that an UploadFile object can be 
	 * constructed as in this example:
	 * $upFile = new UploadFile($_FILES['my_file']);
	 */
	public function __construct($properties = false)
	{
		if (is_array($properties))
		{
			$this->name = $properties['name'];
			$this->type = $properties['type'];
			$this->tmp_name = $properties['tmp_name'];
			$this->error = $properties['error'];
			$this->size = $properties['size'];
		}
	}

	/**
	 * Returns a human-readable string representation of the state of this object
	 * @return string state of this object
	 */
	public function toString()
	{
	  return
			"Type: " . $this->get_type() . " \n" .
			"Temp: " . $this->get_tmp_name() . " \n" .
			"Error: " . $this->get_error() . " \n" .
			"Size: " . $this->get_size() . " \n" .
			"Name: " . $this->get_name() . " \n" .
			"UID: " . $this->get_uid() . " \n";
	}

	/**
	 * Returns TRUE if this file was uploaded successfuly, otherwise FALSE
	 *	
	 * @return boolean uploaded successfuly
	 */
	public function is_successful()
	{
		return ($this->error === 0);
	}

	/**
	 * Generates a pseudo-random/unique identifier for this file. This UID
	 * can be used as a file name or part of a file name, to solve the problem 
	 * of filename conflicts when many files are stored together.
	 * @return void
	 */
	private function generate_uid()
	{
		$seed = $this->type . $this->tmp_name . $this->size . $this->name;
		$this->uid = md5($seed . date("Ymdhis") . mt_rand());
	}

	//Accessor methods	

	/**
	 * The mime type of the file, if the browser provided this information. 
	 * An example would be "image/gif". 
	 * This mime type is however not checked on the PHP side and therefore 
	 * don't take its value for granted.
	 * @return string mime type 
	 */
	public function get_type()
	{
		return $this->type;
	}

	/**
	 * Returns the size, in bytes, of the uploaded file.
	 * @return integer file size 
	 */
	public function get_size()
	{
		return $this->size;
	}

	/**
	 * Returns the error code associated with this file upload. 
	 * Error codes are defined in the php manual
	 * @link http://www.php.net/manual/en/features.file-upload.errors.php
	 * @return integer error code 
	 */
	public function get_error()
	{
		return $this->error;
	}

	/**
	 * Returns the temporary filename of the file in which the uploaded 
	 * file was stored on the server.
	 * @return string file name 
	 */
	public function get_tmp_name()
	{
		return $this->tmp_name;
	}

	/**
	 * Returns the original name of the file on the client machine.
	 * @return string original name 
	 */
	public function get_name()
	{
		return $this->name;
	}

	/**
	 * Returns a randomly-generated hash string that can be used to create
	 * a unique file name
	 * @return string random hash string
	 */
	public function get_uid()
	{
		if (empty($this->uid))
		{
			$this->generate_uid();
		}
		return $this->uid;
	}

	/**
	 * Returns the name by which this file was saved using save_as($file_path).
	 * If this file has not been saved, returns false.
	 * @return string|boolean file name or FALSE
	 */
	public function get_file_name()
	{
		return $this->file_name;
	}

}

class UploadFileException extends Exception {
	
}

