UploadedFile
============
helper class for CodeIgniter
----------------------------

Object wrapper for uploaded files that are available via 
the $_FILES array.

Provides accessors to each file's name, size, tmp_name, 
type and error code, as well as method for saving the file 
to a final location.

The static method *get_uploaded()* returns an array of 
UploadedFile objects. The array structure matches the way
the elements on the submitted form were named.

For example, if a form was submitted that contained 
these elements:
	
	<input type="file" name="logo"/>
	<input type="file" name="signature"/>
	<input type="file" name="photos[0]"/>
	<input type="file" name="photos[1]"/>
	<input type="file" name="photos[2]"/>
	
*UploadedFile::get_uploaded()* would return an array like this:

	array(
		'logo' => UploadedFile,
		'signature' => UploadedFile,
		'photos' => array(
			UploadedFile, 
			UploadedFile, 
			UploadedFile
		)
	);
	



Basic usage example:

	<?php
	
	$save_path = 'uploaded_files/';
	$files = UploadedFile::get_uploaded();
	if (!empty($files))
	{
		foreach ($files as $file)
		{
			try
			{
				$file->save_as($save_path . $file->get_name());
				$messages[] = $file->get_name() . ' was saved';
			}
			catch (UploadFileException $ex)
			{
				$messages[] = $ex->getMessage();
			}
		}
	}
	else
	{
		$messages[] = 'no files were uploaded';
	}
	?>

