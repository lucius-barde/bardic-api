<?php
class Media{
	function __construct($db) {
		$this->db = $db;
	}
	

	public function generateThumbnail($img, $twidth = 300, $theight = 300, $quality = 90){

		$urlParts = pathinfo($img);

		$thumbDir = $urlParts['dirname'].'/_thumb/'.$twidth.'x'.$theight;
		$path = $thumbDir.'/'.$urlParts['filename'].'.'.$urlParts['extension'];
		if(file_exists($path)){
			return $path;
		}
		if(file_exists($img)){
			
			if(mime_content_type($img) == 'image/jpeg'){
				$imgSrc = imagecreatefromjpeg($img); //TODO: why returns null
			}
			elseif(mime_content_type($img) == 'image/png'){
				$imgSrc = imagecreatefrompng($img);
			}


			//rotate according to exif data
			if(function_exists('exif_read_data')){
				$exif = exif_read_data($img);
				$ort = $exif['Orientation'];
				switch($ort)
				{
					case 3: // 180 rotate left
						$imgSrc = imagerotate($imgSrc, 180, 0);
						break;
					case 6: // 90 rotate right
						$imgSrc = imagerotate($imgSrc, -90, 0);
						break;
					case 8:    // 90 rotate left
						$imgSrc = imagerotate($imgSrc, 90, 0); 
						break;
				}
			}

			$w = imagesx($imgSrc);
			$h = imagesy($imgSrc);
			
			//calculate new width + height
			if(isset($twidth) && !isset($theight)){
				$new_w=$twidth;
				$new_h=$new_w * ($h/$w);
			} elseif (isset($theight) && !isset($twidth)) {
				$new_h=$theight;
				$new_w=$new_h * ($w/$h);
			} else {
				$new_w=isset($twidth)?$twidth:560;
				$new_h=isset($theight)?$theight:560;
				if(($w/$h) > ($new_w/$new_h)){
					$new_h=$new_w*($h/$w);
				} else {
					$new_w=$new_h*($w/$h);    
				}
			} 
			$im2 = ImageCreateTrueColor($new_w, $new_h);
			imagecopyResampled($im2, $imgSrc, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
			


			if(!is_dir($thumbDir)){
				mkdir($urlParts['dirname'].'/_thumb');
				mkdir($thumbDir); 
			}

			if(mime_content_type($img) == 'image/jpeg'){
					imagejpeg($im2, $path, $quality);
				return $path;
			}

			elseif(mime_content_type($img) == 'image/png'){
				imagepng($im2, $path);
				return $path;
			}
			
			 
		}
		return false;
	}     
	
	public function getMedia($subdir){ //get the media list for a directory inside /uploads. Can be used synchronously or in AJAX.
		
		$dir = ABSDIR.'/uploads'.$subdir;

		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(!$subdir && $file == ".." || $file == "."){
						continue;
					}
					$stat = stat(ABSDIR.'/uploads'.$subdir.'/'.$file);
					$size = $stat[7];
					$mtime = $stat[9];
					$humanreadablesize = $size . ' B';
					$humanreadablesize = ($size > 1000 ? (ceil(100 * $size/1000)/100). ' KB' : $humanreadablesize);
					$humanreadablesize = ($size > 1000000 ? (ceil(100* $size/1000000)/100).' MB' : $humanreadablesize);
					$humanreadablesize = ($size > 1000000000 ? (ceil(100* $size/1000000000)/100).' GB' : $humanreadablesize);
				
					$media[] = [
						'name'=>$file,
						'type'=>mime_content_type($dir.'/'.$file),
						'size'=>$size, //does not work
						'hrsize'=>$humanreadablesize, //does not work
						'modified'=>date('Y-m-d H:i:s',$mtime)
					];
				}
				
				closedir($dh);
			}
		}
		
		sort($media);
		return $media;
	}

	public function moveUploadedFile($directory, $uploadedFile)
	{
		$validate = new Validator($this->db);
		$uploadedFileName = $validate->toFileName($uploadedFile->getClientFilename());
		$extension = pathinfo($uploadedFileName, PATHINFO_EXTENSION);
		
		
		$allowed = ["doc","docx","epub","flac","jfif","jpeg","jpg","mp3","ods","odt","ogg","pdf","png","ppt","pptx","svg","webp","xls","xlsx"]; //api controller, media model, dropzone, must be identical
		if(!in_array(strtolower($extension), $allowed)) {
			return false;
		}
		
		$basename = $uploadedFileName;

		
		if(!is_dir($directory)){
			mkdir($directory, 0777, true);
		}
		try{
			$uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $basename);
			return ['status'=>'success', 'statusText'=>'uploaded_file_ok'];
		}catch(Exception $e){
			return ['status'=>'error', 'statusText'=>'uploaded_file_fail', 'uploadedFile'=>$uploadedFile, 'moveTo'=>$directory . DIRECTORY_SEPARATOR . $filename];
		}
	}
	
	public function moveMedia($from, $to){  //"from" and "to" should be relative paths inside /uploads.
		$from = ABSDIR.'/uploads'.$from;
		$to = ABSDIR.'/uploads'.$to;
		
		rename($from,$to);
		return ['status'=>'success','statusText'=>'probably_ok__debug_not_enabled','to'=>$to];
		//there were some debug tests made with is_file() etc. to return error messages but it was buggy,
		//and returned false positives sometimes. So currently no error is sent back in case of error.
	}

	public function deleteMedia($src){
		$file = html_entity_decode(ABSDIR.'/uploads'.$src);
		if(is_file($file)){
			unlink($file);
			return true;
		}else{
			return false;
		}
	}
}
