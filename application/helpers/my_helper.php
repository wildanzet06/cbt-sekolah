<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

function tampil_media($file,$width="",$height="") {
	$ret = '';

	$pc_file = explode(".", $file ?? '');
	$eks = end($pc_file);

	$eks_video = array("mp4","flv","mpeg");
	$eks_audio = array("mp3","acc");
	$eks_image = array("jpeg","jpg","gif","bmp","png");


	if (!in_array($eks, $eks_video) && !in_array($eks, $eks_audio) && !in_array($eks, $eks_image)) {
		$ret .= '';
	} else {
		if (in_array($eks, $eks_video)) {
			if (is_file("./".$file)) {
				$ret .= '<p><video width="'.$width.'" height="'.$height.'" controls>
                <source src="'.base_url().$file.'" type="video/mp4">
                <source src="'.base_url().$file.'" type="application/octet-stream">Browser tidak support</video></p>';
			} else {
				$ret .= '';
			}
		} 

		if (in_array($eks, $eks_audio)) {
			if (is_file("./".$file)) {
				$ret .= '<p><audio width="'.$width.'" height="'.$height.'" controls>
				<source src="'.base_url().$file.'" type="audio/mpeg">
				<source src="'.base_url().$file.'" type="audio/wav">Browser tidak support</audio></p>';
			} else {
				$ret .= '';
			}
		}

		if (in_array($eks, $eks_image)) {
			if (is_file("./".$file)) {
				$ret .= '<img class="thumbnail w-100" src="'.base_url().$file.'" style="width: '.$width.'; height: '.$height.';">';
			} else {
				$ret .= '';
			}
		}
	}
	

	return $ret;
}

function make_thumb($src, $dest, $desired_width) {

    /* read the source image */
    $source_image = imagecreatefromjpeg($src);
    $width = imagesx($source_image);
    $height = imagesy($source_image);

    /* find the "desired height" of this thumbnail, relative to the desired width  */
    $desired_height = floor($height * ($desired_width / $width));

    /* create a new, "virtual" image */
    $virtual_image = imagecreatetruecolor($desired_width, $desired_height);

    /* copy source image at a resized size */
    imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

    /* create the physical thumbnail image to its destination */
    imagejpeg($virtual_image, $dest);
}

/* call it
$src="1494684586337H.jpg";
$dest="new.jpg";
$desired_width="200";
make_thumb($src, $dest, $desired_width);
*/
if (!function_exists('db_upsert')) {
    /**
     * INSERT ... ON DUPLICATE KEY UPDATE (pengganti REPLACE agar ID/audit tidak hilang).
     * Mengandalkan unique key yang sudah ada pada tabel.
     */
    function db_upsert($db, $table, $data)
    {
        if (!is_array($data) || count($data) === 0) {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $table)) {
            return false;
        }
        $kolom = array();
        $nilai = array();
        $update = array();
        foreach ($data as $k => $v) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $k)) {
                continue;
            }
            $kolom[] = '`' . $k . '`';
            $nilai[] = $db->escape($v);
            $update[] = '`' . $k . '` = VALUES(`' . $k . '`)';
        }
        if (count($kolom) === 0) {
            return false;
        }
        $sql = 'INSERT INTO `' . $table . '` (' . implode(',', $kolom) . ') VALUES ('
            . implode(',', $nilai) . ') ON DUPLICATE KEY UPDATE ' . implode(',', $update);
        return $db->query($sql);
    }
}
