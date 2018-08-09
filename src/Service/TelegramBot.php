<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2/16/18
 * Time: 1:57 PM
 */

namespace App\Service;

//use GuzzleHttp\Client;
use Monolog\Logger;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class TelegramBot
 */
class TelegramBot
{
    /**
     * TelegramBot constructor.
     * @param string $token
     * @param Logger $log
     */
    public function __construct(string $token, Logger $log)
    {
        $this->token = $token;
//        file_put_contents('token.txt', $token);
        $this->log = $log;
    }

    /**
     * @const TELEGRAM_API_URL
     */
    const TELEGRAM_API_URL = 'https://api.telegram.org/bot';

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @param string $method
     * @param array $params
     * @return \stdClass
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function query(string $method, array $params = []): \stdClass
    {
        $response = new \stdClass();
        try {
            $url = self::TELEGRAM_API_URL . $this->token . '/' . $method;
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
//            file_put_contents('query_log.txt', $url);
            $client = new \GuzzleHttp\Client(['base_uri' => $url]);
            $result = $client->request('GET');

            $response = json_decode($result->getBody()->getContents());
        } catch (\Exception $exception) {
            return new JsonResponse(['error']);
//            file_put_contents('query_error_log.txt', $exception->getMessage());
        }

        return $response;
    }

//    /**
//     * @return stdClass
//     */
//    public function getUpdates(): object
//    {
//        $response = new stdClass();
//        try {
//            $response = $this->query('getUpdates', [
//                'offset' => $this->offset + 1
//            ]);
//            if (!empty($response->result)) {
//                $this->offset = $response->result[count($response->result) - 1]->update_id;
//            }
//
////            if()
//        } catch (GuzzleException $exception) {
//            $this->log->log(400, 'Guzzle sendMessage error');
//        }
//
//        return $response;
//    }

    /**
     * @param int $chatId
     * @param string $text
     * @return stdClass
     */
    public function sendMessage(int $chatId = 0, string $text): \stdClass
    {
        $response = new \stdClass();
        try {
            $response = $this->query('sendMessage', [
                'text' => $text,
                'chat_id' => $chatId
            ]);
        } catch (GuzzleException $exception) {
            $this->log->log(400, 'Guzzle sendMessage error');
        }

        return $response;
    }

    public function getFile($file): object
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
            'document' => new CURLFile(realpath($document)),
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