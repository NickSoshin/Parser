<?php

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Parser
{
    public const BYTES_IN_MEGABYTE = 1048576;

    private ?array $imagesData = [];

    private bool $status = false;

    private ?string $error = 'Data not retrieved';

    private ?float $totalSize = null;

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function __construct(HttpClientInterface $client, string $linkUrl)
    {
        $source = parse_url($linkUrl);

        if (
            !empty($source)
            &&
            is_array($source)
            &&
            !empty($source['scheme'])
            &&
            !empty($source['host'])
        ) {
            $baseUrl = $source['scheme'] . '://' . $source['host'];

            $response = $client->request('GET', $linkUrl);


            if ($response->getStatusCode() === 200) {
                try {
                    preg_match_all(
                        '/<img.*?src=["\'](.*?)["\'].*?>/i',
                        $response->getContent(),
                        $images,
                        PREG_SET_ORDER
                    );

                    if (!empty($images)) {
                        $this->prepareImagesData($client, $images, $baseUrl);
                    } else {
                        $this->error = 'Ошибка: Картинки не обнаружены';
                    }
                } catch (Exception $e) {
                    $this->error = 'Ошибка: запрос не завершен' . $e->getMessage();
                }
            }
        } else {
            $this->error = 'Ошибка: некорректная ссылка';
        }
    }

    private function prepareImagesData(HttpClientInterface $client, $images, string $baseUrl): void
    {
        $totalSize = 0;
        $imagesData = [];
        foreach ($images as $image) {
            $size = 0;

            if (!empty($image[1])) {
                if (str_starts_with($image[1], 'https://') || str_starts_with($image[1], 'http://')) {
                    $imageUrl = $image[1];
                } else {
                    $imageUrl = $baseUrl . $image[1];
                }

                $response = $client->request('GET', $imageUrl);
                if ($response->getStatusCode() === 200) {
                    $items = array_values(
                        array_filter($response->getInfo()['response_headers'], function ($item) {
                            return str_contains($item, 'Content-Length:');
                        })
                    );

                    if (!empty($items) && sizeof($items) === 1) {
                        $size = (int)trim(str_replace('Content-Length:', '', $items[0]));
                    } else {
                        $this->error = 'Ошибка(1): не удалось получить размер одного или нескольких файлов';
                    }
                } else {
                    $this->error = 'Ошибка(2): не удалось получить размер одного или нескольких файлов';
                }

                $imagesData[] = [
                    'url' => $imageUrl,
                    'size' => $size,
                ];
            }

            $totalSize += $size;
        }

        $this->totalSize = round($totalSize / self::BYTES_IN_MEGABYTE, 3);
        $this->imagesData = $imagesData;
        $this->status = true;
    }

    /**
     * @return array|null
     */
    public function getImagesData(): ?array
    {
        return $this->imagesData;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @return float|null
     */
    public function getTotalSize(): ?float
    {
        return $this->totalSize;
    }
}