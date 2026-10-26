<?php

namespace App\Support\Word;

/**
 * Image PNG transparente du filigrane (texte rouge pâle en diagonale), mise en cache dans storage/app.
 */
class WatermarkImage
{
    public static function path(string $text): string
    {
        $path = storage_path('app/watermark-'.md5($text).'.png');

        if (! is_file($path)) {
            self::draw($text, $path);
        }

        return $path;
    }

    private static function draw(string $text, string $path): void
    {
        $size = 1200;
        $font = resource_path('fonts/carlito/Carlito-Bold.ttf');

        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        // Rouge #E30613 à environ 12 % d'opacité (alpha GD : 0 opaque, 127 transparent).
        $color = imagecolorallocatealpha($image, 227, 6, 19, 112);

        $angle = 35;
        $fontSize = 170;
        $box = imagettfbbox($fontSize, $angle, $font, $text);
        $width = max($box[2], $box[4]) - min($box[0], $box[6]);
        $height = max($box[1], $box[3]) - min($box[5], $box[7]);
        $x = (int) (($size - $width) / 2 - min($box[0], $box[6]));
        $y = (int) (($size - $height) / 2 - min($box[5], $box[7]));

        imagettftext($image, $fontSize, $angle, $x, $y, $color, $font, $text);
        imagepng($image, $path);
        imagedestroy($image);
    }
}
