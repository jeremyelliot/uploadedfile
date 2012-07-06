<?php
include 'uploadedfile_helper.php';

$messages = array();
$save_path = 'uploaded_files/';
$files = UploadedFile::get_uploaded();
if (!empty($files))
{
	$logo = $files['logo'];
	$doc = $files['document'];
	$photos = $files['photos'];

	// save logo file
	if ($logo->is_successful())
	{
		$logo->save_as($save_path . $logo->get_name());
		$messages[] = 'logo was saved (' . round($logo->get_size() / 1024) . ' kB)';
	}
	// save document file
	if ($doc->is_successful())
	{
		$doc->save_as($save_path . $doc->get_name());
		$messages[] = 'document was saved (' . $doc->get_name() . ', '
				. $doc->get_type() . ', ' . round($doc->get_size()) . ' kB)';
	}
	// save uploaded photos
	foreach ($photos as $file)
	{
		try
		{
			$file->save_as($save_path . $file->get_name());
			$message = $file->get_name() . ' was saved ('
					. round($file->get_size() / 1024) . ' kB)';
		}
		catch (UploadedFileException $ex)
		{
			$message = $ex->getMessage();
		}
		$messages[] = $message . '<pre>' . print_r($file, TRUE) . '</pre>';
	}
}
else
{
	$messages[] = 'no files were uploaded';
}
?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title>UploadedFile helper -- test form</title>
	</head>
	<body>
		<section>
			<ol>		
				<?php foreach ($messages as $message): ?>
					<li><?php echo $message ?></li>
				<?php endforeach; ?>
			</ol>
		</section>
		<form name="upload_form" method="POST" enctype="multipart/form-data" 
					action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<fieldset>
				<label for="logo">Logo</label>
				<input type="file" id="logo" name="logo" value="" />
				<label for="document">Document</label>
				<input type="file" id="document" name="document" value="" />
			</fieldset>
			<fieldset>
				<legend>Photos</legend>
				<input type="file" id="photos_0" name="photos[0]" value="" />
				<input type="file" id="photos_1" name="photos[1]" value="" />
				<input type="file" id="photos_2" name="photos[3]" value="" />
			</fieldset>
			<input type="submit" value="submit" name="submit" />
		</form>
	</body>
</html>
