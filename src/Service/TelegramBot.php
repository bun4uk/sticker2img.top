<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2/16/18
 * Time: 1:57 PM
 */

namespace App\Service;

use function Couchbase\defaultDecoder;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class TelegramBot
 */
class TelegramBot
{
    /**
     * TelegramBot constructor.
     * @param string $telegramApiToken
     */
    public function __construct(string $telegramApiToken)
    {

        print_r($telegramApiToken); die;

        $this->token = $telegramApiToken;
    }

    /**
     * @const TELEGRAM_API_URL
     */
    public const TELEGRAM_API_URL = 'https://api.telegram.org/bot';

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $token;

    /**
     * @param string $method
     * @param array $params
     * @return mixed|JsonResponse
     * @throws GuzzleException
     */
    protected function query(string $method, array $params = [])
    {
        try {
            $url = self::TELEGRAM_API_URL . $this->token . '/' . $method;
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            $client = new \GuzzleHttp\Client(['base_uri' => $url]);
            $result = $client->request('GET');

            $response = json_decode($result->getBody()->getContents());
        } catch (\Exception $exception) {
            return new JsonResponse(['error']);
        }

        return $response;
    }

    /**
     * @param int $chatId
     * @param string $text
     * @return \stdClass
     * @throws \Exception
     */
    public function sendMessage(int $chatId, string $text): \stdClass
    {
        $response = new \stdClass();
        try {
            $response = $this->query('sendMessage', [
                'text' => $text,
                'chat_id' => $chatId
            ]);
        } catch (GuzzleException $exception) {
            throw new  \Exception();
        }

        return $response;
    }

    /**
     * @param int $chatId
     * @return mixed|JsonResponse
     * @throws \Exception
     */
    public function sendKeyboard(int $chatId)
    {
        $replyMarkup = [
            'keyboard' => [
                [
                    'CallsCount'
                ]
            ]
        ];
        $encodedMarkup = json_encode($replyMarkup);
        $content = array(
            'chat_id' => $chatId,
            'reply_markup' => $encodedMarkup,
            'text' => ' '
        );

        try {
            $response = $this->query('sendMessage', $content);
        } catch (GuzzleException $exception) {
            throw new  \Exception();
        }

        return $response;
    }

    public function getFile($file): \stdClass
    {
        $response = $this->query('getFile', [
            'file_id' => $file->file_id
        ]);

        return $response->result;
    }

    /**
     * @param int $chatId
     * @param string $photo
     * @return mixed
     */
    public function sendPhoto(int $chatId, string $photo)
    {
        $url = self::TELEGRAM_API_URL . $this->token . '/sendPhoto?chat_id=' . $chatId;

        $post_fields = [
            'chat_id' => $chatId,
            'photo' => new \CURLFile(realpath($photo))
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type:multipart/form-data"
        ]);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

        return curl_exec($ch);
    }

    /**
     * @param int $chatId
     * @param string $document
     * @param string $caption
     * @return mixed
     */
    public function sendDocument(int $chatId, string $document, string $caption = '')
    {
        $url = self::TELEGRAM_API_URL . $this->token . '/sendDocument?chat_id=' . $chatId;

        $post_fields = [
            'chat_id' => $chatId,
            'document' => new \CURLFile(realpath($document)),
            'caption' => $caption
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type:multipart/form-data"
        ]);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

        return curl_exec($ch);
    }

}
