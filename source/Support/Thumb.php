<?php


namespace Source\Support;


use CoffeeCode\Cropper\Cropper;

/**
 * Class Thumb
 * @package Source\Support
 */
class Thumb
{
    /** @var Cropper */
    private $cropper;

    /** @var string */
    private $uploads;

    /**
     * Thumb constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->cropper = new Cropper(CONF_IMAGE_CACHE, CONF_IMAGE_QUALITY['jpg'], CONF_IMAGE_QUALITY['png']);
        $this->uploads = CONF_UPLOAD_DIR;
    }

    /**METODO QUE REDIMENCIONA A IMAGEM
     * @param string $image
     * @param int $width
     * @param int|null $height
     * @return string
     */
    public function make(string $image, int $width, int $height = null): string
    {
        return $this->cropper->make("{$this->uploads}/{$image}", $width, $height);
    }

    /**METODO QUE LIMPA A PASTA, PELA IMAGE SE FOR PASSADA OU A PASTA INTEIRA
     * @param string|null $image
     */
    public function flush(string $image = null): void
    {
        if ($image) {
            $this->cropper->flush("{$this->uploads}/{$image}");
            return;
        }

        $this->cropper->flush();
        return;
    }

    /**METODO QUE RETORNA UM OBJETO CROPPER
     * @return Cropper
     */
    public function cropper(): Cropper
    {
        return $this->cropper;
    }
}