<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 8/5/18
 * Time: 8:26 PM
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\TelegramBot;

class DefaultController extends AbstractController
{
    public function index(Request $request)
    {
        file_put_contents(__DIR__ . '/request_dump', $request);
        return new Response('', 404);
    }

    public function bot(Request $request)
    {
        $request = $request->getContent();
        file_put_contents(__DIR__ . '/request_dump', $request->getContent());
        $config = parse_ini_file('/var/www/sticker2img.top/config/config.ini');
        $token = $config['telegram_api_token'];
        $telegramApi = new TelegramBot($token);
        $update = $request;

        if (isset($update->message->text) && false !== strpos($update->message->text, 'start')) {
            $telegramApi->sendMessage($update->message->chat->id, 'Hi there! I\'m Sticker2Image bot. I\'ll help you to convert your stickers to PNG images. Just send me some sticker.');
            return new Response('sent');
        }

        if (isset($update->message->sticker)) {
            try {
                $telegramApi->sendMessage($update->message->chat->id, 'I\'ve got your sticker');
                $telegramApi->sendMessage($update->message->chat->id, '...');
                $file = $telegramApi->getFile($update->message->sticker);
                $filePath = "https://api.telegram.org/file/bot$token/" . $file->file_path;
                $fileName = '/var/www/sticker2img.top/public/files/img_' . time() . mt_rand();
                $imgPathWebp = $fileName . '.webp';
                copy(
                    $filePath,
                    $imgPathWebp
                );
                $telegramApi->sendPhoto($update->message->chat->id, $imgPathWebp);
                unlink($imgPathWebp);

                return new Response('sent');

            } catch (\Exception $exception) {
                $telegramApi->sendMessage($update->message->chat->id, 'Sorry, I am tired. Some server error. Try in a few minutes :\'( ');
                return new Response('server_error');
            }
        }

        if (
            isset($update->message, $update->message->chat->username)
            && mb_strtolower($update->message->chat->username) === Dictionary::PAULMAKARON
            && false !== strpos($update->message->text, '/call_count')
        ) {
            $command = explode(' ', $update->message->text);
            $date = (isset($command[1]) && !empty($command[1])) ? $command[1] : (new \DateTime())->format('Y-m-d');
            exec("cat logs/img_log.log | grep === | grep {$date} | wc -l", $result);
            $telegramApi->sendMessage(7699150, 'Бот был использован ' . reset($result) . ' раз');
            return new Response('sent');
        }

        if (isset($update->message, $update->message->chat->id)) {
            $telegramApi->sendMessage($update->message->chat->id, 'I understand only stickers');
        }

        return new Response('sent', 200);
    }

}