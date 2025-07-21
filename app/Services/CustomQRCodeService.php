<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PragmaRX\Google2FAQRCode\QRCode\QRCodeServiceContract;

class CustomQRCodeService implements QRCodeServiceContract
{
    protected $options = [];

    public function __construct()
    {
        // Настройки по умолчанию без белых границ
        $this->options = [
            'version'       => QRCode::VERSION_AUTO,
            'outputType'    => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel'      => QRCode::ECC_L,
            'addQuietzone'  => false,  // Убираем белые границы
            'quietzoneSize' => 0,      // Размер границ = 0
        ];
    }

    /**
     * Generates a QR code data url to display inline.
     *
     * @param string $string
     * @param int    $size
     * @param string $encoding Default to UTF-8
     *
     * @return string
     */
    public function getQRCodeInline($string, $size = null, $encoding = null)
    {
        $qrOptions = new QROptions($this->options);
        $qrcode = new QRCode($qrOptions);

        $svgString = $qrcode->render($string);

        // Проверяем, не является ли результат уже data URL
        if (strpos($svgString, 'data:image/svg+xml;base64,') === 0) {
            return $svgString;
        }

        // Если это чистый SVG, кодируем в base64
        return 'data:image/svg+xml;base64,' . base64_encode($svgString);
    }

    /**
     * Set custom options
     *
     * @param array $options
     * @return self
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }
}
