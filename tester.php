<?php

require_once 'application/library/phpqrcode/qrlib.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImageConverter
 *
 * @author intelworx
 */
class ImageConverter {

    protected $name;
    protected $x, $y, $w, $h;
    protected $path;
    protected $version = 1;
    protected $is32Bit = false;
    public static $WHITE_COLOR_MAP = [
        'white' => 1,
        'black' => 0,
        'transparent' => 1,
    ];
    public static $BLACK_COLOR_MAP = [
        'white' => 0,
        'black' => 1,
        'transparent' => 0,
    ];

    const WHITE_MAP = 1;
    const BLACK_MAP = 2;

    protected $totalPixels = 0;
    protected $colorMap;
    protected $distinct = array();
    protected $image;

    public function __construct($imagePath, $map = self::WHITE_MAP) {
        if (!file_exists($imagePath)) {
            throw new Exception("Path was not found.");
        }

        //var_dump(realpath($imagePath));exit;
        $this->name = basename(realpath($imagePath));
        $this->x = 0;
        $this->y = 0;
        $imageInfo = getimagesize($imagePath);
        $this->w = $imageInfo[0];
        $this->h = $imageInfo[1];
        $this->path = $imagePath;
        $this->colorMap = $map === self::BLACK_MAP ? self::$BLACK_COLOR_MAP : self::$WHITE_COLOR_MAP;
        $this->image = imagecreatefromstring(file_get_contents($imagePath));
        $this->is32Bit = imageistruecolor($this->image);
        imagepalettetotruecolor($this->image);
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);
    }

    public function convertToPbi($outPath = null) {
//        if (!$outPath) {
//            $outPath = $this->name . ".pbi";
//        }
        $data = $this->pbiHeader() . $this->imageBits();
        return $outPath ? file_put_contents($outPath, $data) : $data;
    }

//    def row_size_bytes(self):
//        """
//        Return the length of the bitmap's row in bytes.
//
//        Row lengths are rounded up to the nearest word, padding up to
//        3 empty bytes per row.
//        """
//
//        row_size_padded_words = (self.w + 31) / 32
//        return row_size_padded_words * 4

    private function rowSizeBytes() {
        return floor((($this->w + 31) / 32)) * 4;
    }

//    
//        def info_flags(self):
//        """Returns the type and version of bitmap."""
//
//        return self.version << 12
    public function infoFlags() {
        return $this->version << 12;
    }

//    def pbi_header(self):
//        return struct.pack('<HHhhhh',
//                           self.row_size_bytes(),
//                           self.info_flags(),
//                           self.x,
//                           self.y,
//                           self.w,
//                           self.h)    

    public function pbiHeader() {
        return pack('vvssss', $this->rowSizeBytes(), $this->infoFlags(), $this->x, $this->y, $this->w, $this->h);
    }

//        def get_monochrome_value_for_pixel(pixel):
//            if pixel[3] < 127:
//                return self.color_map['transparent']
//            if ((pixel[0] + pixel[1] + pixel[2]) / 3) < 127:
//                return self.color_map['black']
//            return self.color_map['white']

    public function getMonochromeValueForPixel($pixel) {
        //$color = imagecolorsforindex($this->image, $pixel);

        $color = $this->int2rgba($pixel);
        if ($this->is32Bit && $color['a'] < 127) {
            return $this->colorMap['transparent'];
        }

        return (($color['r'] + $color['b'] + $color['g']) / 3) < 127 ?
                $this->colorMap['black'] : $this->colorMap['white'];
    }

    private function int2rgba($int) {
        $a = ($int >> 24) & 0xFF;
        $r = ($int >> 16) & 0xFF;
        $g = ($int >> 8) & 0xFF;
        $b = $int & 0xFF;
        return array('r' => $r, 'g' => $g, 'b' => $b, 'a' => $a);
    }

    function rgba2int($r, $g, $b, $a = 1) {
        return ($a << 24) + ($b << 16) + ($g << 8) + $r;
    }

    private function getPixelsToBitBlt($row, $xFrom, $xTo) {
        $word = 0;
        for ($column = $xFrom; $column < ($xTo); $column++) {
            $this->totalPixels++;
            $colorIdx = imagecolorat($this->image, $column, $row);
            if (!in_array($colorIdx, $this->distinct)) {
                $this->distinct[] = $colorIdx;
            }
            $shiftBy = $column - $xFrom;
            $word |= ($this->getMonochromeValueForPixel($colorIdx) << $shiftBy);
        }

        return pack('I', $word);
    }

    private function imageBits() {
        $output = [];
        $sizeOfWords = $this->rowSizeBytes() / 4;
        for ($row = $this->y; $row < ($this->y + $this->h); $row++) {
            //$yOffset $row * $this->
            //$xMax = ($)
            for ($columnWord = 0; $columnWord < $sizeOfWords; $columnWord++) {
                //pixels
                $xFrom = $this->x + $columnWord * 32;
                $xTo = $this->x + ($columnWord + 1) * 32;
                if ($xTo > $this->w) {
                    $xTo = $this->w;
                }
                $output[] = $this->getPixelsToBitBlt($row, $xFrom, $xTo);
            }
        }
        
        return join('', $output);
    }

    public function getTotalPixels() {
        return $this->totalPixels;
    }

}

if (!function_exists('imagepalettetotruecolor')) {

    function imagepalettetotruecolor(&$src) {
        if (imageistruecolor($src)) {
            return(true);
        }

        $dst = imagecreatetruecolor(imagesx($src), imagesy($src));

        imagecopy($dst, $src, 0, 0, 0, 0, imagesx($src), imagesy($src));
        imagedestroy($src);

        $src = $dst;

        return(true);
    }

}
//$op = "qr2.png";
//QRcode::png("http://r.orilogbon.me/l/1024", $op, QR_ECLEVEL_L, 4, 0);
//$imager = new ImageConverter($op);
//
//$imager->convertToPbi("qrtest.png.pbi");
////echo PHP_INT_MAX, PHP_EOL;
