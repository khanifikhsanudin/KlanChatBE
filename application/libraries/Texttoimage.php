<?php

  function txttoimg($letter){
    $width = 100;
    $im = imagecreatetruecolor($width, $width);
    $white = imagecolorallocate($im, 248, 248, 248);
    $red = imagecolorallocate($im, 199, 13, 58);
    $green = imagecolorallocate($im, 82, 222, 151);
    $purple = imagecolorallocate($im, 56, 14, 127);
    $orange = imagecolorallocate($im, 236, 155, 59);

    $array = array($red, $orange, $green, $purple);
    $bg = $array[rand(0, count($array) - 1)];

    imagefilledrectangle($im, 0, 0, $width, $width, $bg);

    $text = $letter;
    $font = './assets/fonts/ubuntu.ttf';
    $fontSize = 50;
    $angle = 0;

    $dimensions = imagettfbbox($fontSize, $angle, $font, $text);
    $textWidth = abs($dimensions[4] - $dimensions[0]);
    $leftTextPos = ( $width - $textWidth ) / 2;

    imagettftext($im, 50, 0, $leftTextPos - 5, 70, $white, $font, $text);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    while (true) {
      $filename = uniqid('avatar', true).'.png';
      if (!file_exists('./assets/images/'.$filename)) break;
    }
    imagepng($im, './assets/images/'.$filename);
    imagedestroy($im);
    return base_url().'assets/images/'.$filename;
  }
?> 