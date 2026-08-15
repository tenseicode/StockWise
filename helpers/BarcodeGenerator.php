<?php
/** Simple GD barcode renderer: draws a Code 128-like PNG from a string. */

class BarcodeGenerator
{
    private int $width;
    private int $height;
    private int $barHeight;

    public function __construct(int $width = 300, int $height = 80)
    {
        $this->width    = $width;
        $this->height   = $height;
        $this->barHeight = $height - 30;
    }

    /** Encode a value into a deterministic 1/0 bar pattern. */
    private function encode(string $value): array
    {
        $bits = [];
        // Start pattern.
        foreach (str_split('11010010000') as $b) {
            $bits[] = (int)$b;
        }
        $chars = str_split($value);
        foreach ($chars as $ch) {
            // A 5-char pseudo-encoding from the char's representation.
            $seed = md5($ch . '|' . ord($ch));
            $pattern = '';
            $nibbles = substr($seed, 0, 5);
            foreach (str_split($nibbles) as $n) {
                $bitsVal = (int)base_convert($n, 16, 2);
                $pattern .= str_pad(decbin($bitsVal), 4, '0', STR_PAD_LEFT);
            }
            foreach (str_split($pattern) as $b) {
                $bits[] = (int)$b;
            }
        }
        // Check + stop patterns.
        foreach (str_split('11101001100') as $b) {
            $bits[] = (int)$b;
        }
        return $bits;
    }

    /**
     * Render the barcode and return the PNG binary string.
     */
    public function render(string $value): string
    {
        $bits = $this->encode($value);

        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD extension is not loaded. Enable extension=gd in php.ini.');
        }

        $img = imagecreatetruecolor($this->width, $this->height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $this->width, $this->height, $white);

        $moduleCount = count($bits);
        $moduleWidth = $this->width / $moduleCount;
        $x = 0.0;
        foreach ($bits as $b) {
            if ($b === 1) {
                imagefilledrectangle(
                    $img,
                    (int)floor($x),
                    0,
                    (int)floor($x + $moduleWidth),
                    $this->barHeight,
                    $black
                );
            }
            $x += $moduleWidth;
        }

        $font = 5;
        $text = $value;
        $textW = imagefontwidth($font) * strlen($text);
        $textX = max(0, (int)(($this->width - $textW) / 2));
        imagestring($img, $font, $textX, $this->height - 20, $text, $black);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        return $png;
    }

    /**
     * Generate and save a barcode PNG file. Returns the filename on success.
     */
    public static function generate(string $value, string $directory): string
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new RuntimeException('Barcode directory not writable: ' . $directory);
            }
        }
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $value);
        $filename = $safe . '.png';
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $gen = new self();
        $png = $gen->render($value);
        file_put_contents($path, $png);
        return $filename;
    }
}
