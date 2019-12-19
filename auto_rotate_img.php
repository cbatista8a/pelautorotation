/**
 * Auto rotates an image file based on exif data from camera
 * Based on https://github.com/pel/pel Library
 * note that images already at normal orientation are skipped (when exif data Orientation = 1)
 * @Autor   Carlos Batista
 * @email   cbatista8a@gmail.com
 * @param $original_file
 * @return array
 * @throws PelException
 * @throws \lsolesen\pel\PelInvalidArgumentException
 * @throws \lsolesen\pel\PelInvalidDataException
 * @throws \lsolesen\pel\PelJpegInvalidMarkerException
 */
 
 /**
	* Load the required files. One would normally just require the
	* PelJpeg.php file for dealing with JPEG images
	*/

use lsolesen\pel\Pel;
use lsolesen\pel\PelDataWindow;
use lsolesen\pel\PelException;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelTiff;
 
function autoRotateImage($original_file){

  $mimetype = [

	'IMAGETYPE_GIF' => IMAGETYPE_GIF,
	'IMAGETYPE_JPEG' => IMAGETYPE_JPEG,
	'IMAGETYPE_PNG' => IMAGETYPE_PNG,
	'IMAGETYPE_SWF' => IMAGETYPE_SWF,
	'IMAGETYPE_PSD' => IMAGETYPE_PSD,
	'IMAGETYPE_BMP' => IMAGETYPE_BMP,
	'IMAGETYPE_TIFF_II' => IMAGETYPE_TIFF_II,
	'IMAGETYPE_TIFF_MM' => IMAGETYPE_TIFF_MM,
	'IMAGETYPE_JPC' => IMAGETYPE_JPC,
	'IMAGETYPE_JP2' => IMAGETYPE_JP2,
	'IMAGETYPE_JPX' => IMAGETYPE_JPX,
	'IMAGETYPE_JB2' => IMAGETYPE_JB2,
	'IMAGETYPE_SWC' => IMAGETYPE_SWC,
	'IMAGETYPE_IFF' => IMAGETYPE_IFF,
	'IMAGETYPE_WBMP' => IMAGETYPE_WBMP,
	'IMAGETYPE_XBM' => IMAGETYPE_XBM,
	'IMAGETYPE_ICO' => IMAGETYPE_ICO,
	'IMAGETYPE_WEBP' => IMAGETYPE_WEBP,

];   // Imagetype Constants Doc Manual php


   $response = array(
			"status" => "OK",
			"message" => "",
			"error_code" => 0,
			"salva_cloud" => TRUE,
			"original_orientation" => null,
			"finally_orientation" => null,

		);




		$exif_orientation = FALSE;

		if(file_exists($original_file) && is_readable($original_file)){

			
			$type_image = exif_imagetype($original_file);

			if (!in_array($type_image,$mimetype)){

				$response['status']= "OK";
				$response['message']= "Il File caricato non è una immagine. ";
				$response['error_code']= 0;
				$response['salva_cloud']= TRUE;

				return $response;
			}

			$exif_orientation = read_exif_data($original_file)['Orientation'];  // Read Orientation

		}

		if ($exif_orientation != 1 && $exif_orientation != false){

			Pel::setDebug(false);
			Pel::setStrictParsing(false);
			Pel::setJPEGQuality(92);



			$data = new PelDataWindow(file_get_contents($original_file));
			$jpeg = new PelJpeg();
			$jpg_rotated = new PelJpeg();

			try{

				$source_jpg = imagecreatefromstring(file_get_contents($original_file)); // Read img from string file


			}
			catch (Exeption $e){


				$response['status']= "ERRORE";
				$response['message']= "Errore nel processo di lettura dell'immagine. ".$e->getMessage();
				$response['error_code']= 1;
				$response['salva_cloud']= FALSE;
				$response['original_orientation']= $exif_orientation;
				$response['finally_orientation']= $exif_orientation;


				return $response;

			}

			if (PelJpeg::isValid($data)) {
				$jpeg->load($data);
				$exif = $jpeg->getExif();

				$tiff = $exif->getTiff();
				$ifd = $tiff->getIfd();
				$entry_orientation = $ifd->getEntry(PelTag::ORIENTATION);


				if ( $exif_orientation=='3'  or $exif_orientation=='6' or $exif_orientation=='8'){

					$new_angle[3] = 180;
					$new_angle[6] = 270;
					$new_angle[8] = 90;


					try{

						$rotate = imagerotate($source_jpg, $new_angle[$exif_orientation], 0);  //Img Rotation

						$jpg_rotated->load(new PelDataWindow($rotate));
					}
					catch (Exeption $e){

						imagedestroy($source_jpg);
						imagedestroy($rotate);

						$response['status']= "ERRORE";
						$response['message']= "Errore nel processo di rotazione dell'immagine. ".$e->getMessage();
						$response['error_code']= 1;
						$response['salva_cloud']= FALSE;
						$response['original_orientation']= $exif_orientation;
						$response['finally_orientation']= $exif_orientation;

						return $response;
					}

					// Now Add a PelIfd object with one or more
					// PelEntry objects to $jpeg... Finally save $jpeg to file:

					$entry_orientation->setValue(1);                // Update Orientation Value
					$ifd->addEntry($entry_orientation);
					$tiff->setIfd($ifd);
					$exif->setTiff($tiff);
					$jpg_rotated->setExif($exif);                         // Update Object exif
					$response['original_orientation']= $exif_orientation;
					$response['finally_orientation']= 1;

				}


			} elseif (PelTiff::isValid($data)) {
				$jpeg = new PelTiff();
				$jpeg->load($data);

				$ifd = $jpeg->getIfd();
				$entry_orientation = $ifd->getEntry(PelTag::ORIENTATION);


				if ($exif_orientation=='3'  or $exif_orientation=='6' or $exif_orientation=='8'){

					$new_angle[3] = 180;
					$new_angle[6] = -90;
					$new_angle[8] = 90;


					try{

						$rotate = imagerotate($source_jpg, $new_angle[$exif_orientation], 0);   //Img Rotation

						$jpg_rotated->load(new PelDataWindow($rotate));
					}
					catch (Exeption $e){

						imagedestroy($source_jpg);
						imagedestroy($rotate);

						$response['status']= "ERRORE";
						$response['message']= "Errore nel processo di rotazione dell'immagine. ".$e->getMessage();
						$response['error_code']= 1;
						$response['salva_cloud']= FALSE;
						$response['original_orientation']= $exif_orientation;
						$response['finally_orientation']= $exif_orientation;

						return $response;
					}


					// Now Add a PelIfd object with one or more
					// PelEntry objects to $jpeg... Finally save $jpeg to file:

					$entry_orientation->setValue(1);                  // Update Orientation Value
					$ifd->addEntry($entry_orientation);
					$jpg_rotated->setIfd($ifd);                             // Update Object exif
					$response['original_orientation']= $exif_orientation;
					$response['finally_orientation']= 1;



				}


			} else {

				imagedestroy($source_jpg);

				$response['status']= "ERRORE";
				$response['message']= "Formato immagine non riconosciuto per i primi 16 byte!";
				$response['error_code']= 1;
				$response['salva_cloud']= FALSE;
				$response['original_orientation']= $exif_orientation;
				$response['finally_orientation']= $exif_orientation;

				return $response;
			}


			try{

				$jpg_rotated->saveFile($original_file);
				imagedestroy($source_jpg);
				imagedestroy($rotate);

				$response['status']= "OK";
				$response['message']= "Il processo di rotazione dell'immagine ha avuto esito positivo.";
				$response['error_code']= 0;
				$response['salva_cloud']= TRUE;



				return $response;

			}catch (Exception $e){

				imagedestroy($source_jpg);
				imagedestroy($rotate);
				$response['status']= "ERRORE";
				$response['message']= "Errore di scrittura del file. Non è stato salvato. ".$e->getMessage();
				$response['error_code']= 1;
				$response['salva_cloud']= FALSE;


				return $response;
			}
		}else{

			$response['status']= ($exif_orientation == 1 || $exif_orientation == null) ? "OK" : "ERRORE";
			$response['message']= ($exif_orientation == 1 || $exif_orientation == null) ? "File già Ottimizzato o non contiene dati Exif per la gestione." : "Il file non esiste o non si può leggere.";
			$response['error_code']= ($exif_orientation == 1 || $exif_orientation == null) ? 0 : 1;
			$response['salva_cloud']= ($exif_orientation == 1 || $exif_orientation == null) ? TRUE : FALSE;
			$response['original_orientation']= $exif_orientation;
			$response['finally_orientation']= $exif_orientation;

			return $response;
		}

		return $response;


}
