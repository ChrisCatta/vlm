<?php
    include_once ("config.php");
    include_once ("functions.php");

    $idraces = get_cgi_var("idraces", 0);
    $typethumb = get_cgi_var("type", "mini");
    // Récupération des dimensions (x et y) : valeurs mini par défaut = 250
    $image = $idraces;
    if ($typethumb == "tiny") {
        $thumb = DIRECTORY_TINYMAPS."/" . $image . ".png";    
        $new_x = 50;
    } else {
        $thumb = DIRECTORY_MINIMAPS."/" . $image . ".png";
        $new_x = 180;
    }
    $force = get_cgi_var('force', 'no');
    $original = getRacemap($idraces, $force);

    if ($original === False) {
        header("Cache-Control: no-cache"); // no cache for dummy answer
        die("No racemap with such id");
    }


    // Création et mise en cache de la miniature si elle n'existe pas ou est trop vieille
    if ( 
         ( ! file_exists($thumb) ) 
          ||  (filemtime($thumb) < filemtime($original) )
          ||  ($force == 'yes')
          ||  (filemtime($thumb) < filemtime(__FILE__) )
       ) {

        list($x, $y, $type, $attr) = getimagesize($original);
        $ratio = $x/$y;
        $new_y = $new_x/$ratio;

        switch(exif_imagetype($original)) {
            case IMAGETYPE_JPEG :
                $img_in  = imagecreatefromjpeg( $original ) or die("Cannot Initialize new GD image stream");
                break;
            case IMAGETYPE_PNG :
                $img_in  = imagecreatefrompng( $original ) or die("Cannot Initialize new GD image stream");
                break;
            default :
                die("Not JPG or PNG image file");
        }
        $img_out = imagecreatetruecolor($new_x, $new_y);

        imagecopyresampled($img_out, $img_in, 0, 0, 0, 0, imagesx($img_out), imagesy($img_out), imagesx($img_in), imagesy($img_in));

        // Sauvegarde de la miniature
        imagepng($img_out, $thumb) or die ("Cannot write thumbnail");

        // libération des ressources
        imagedestroy($img_in);
        imagedestroy($img_out);
    }

    // Envoi de la miniature
    header("Content-Type: image/png");
    header("Content-Length: " . filesize($thumb));
    header("Cache-Control: max-age=864000"); // default 10 days should be tunable.
    header("Content-Location: " . $thumb );
    // FIXME do we want to send a redirect, here ?

    readfile($thumb);
    exit(0); //To prevent bad spaces appended from php script
?>
