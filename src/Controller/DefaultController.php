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
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Service\TelegramBot;

class DefaultController extends AbstractController
{
    public function index(Request $request)
    {
        file_put_contents(__DIR__ . '/request_dump', $request);
        return new Response('yes');
    }

    public function bot(Request $request)
    {
        file_put_contents(__DIR__ . '/request_dump', $request->getContent());
//        return new Response('bota');
//
//
//
        $config = parse_ini_file('/var/www/sticker2img.top/config/config.ini');
//        $config = parse_ini_file(__DIR__ . '/../../config/config.ini');


        $token = $config['telegram_api_token'];
        $log = new Logger('img_log');


        $telegramApi = new TelegramBot($token, $log);
//        $db = new Database([
//            'db_host' => $config['db_host'],
//            'db_name' => $config['db_name'],
//            'db_user' => $config['db_user'],
//            'db_password' => $config['db_password'],
//        ]);

        try {
            $log->pushHandler(new StreamHandler('./logs/img_log.log', 200));
        } catch (\Exception $exception) {
            error_log('logger exception');
        }

        $request = file_get_contents('php://input');
        $request = json_decode($request);

        $update = $request;
//        if (!$db->userExists($update->message->chat->id)) {
//            $db->saveUser([
//                'chat_id' => $update->message->chat->id,
//                'username' => $update->message->chat->username ?? null,
//                'type' => $update->message->chat->type ?? null
//            ]);
//
//            $telegramApi->sendMessage(7699150, 'New user @' . $update->message->chat->username ?? null);
//        }

        if (isset($update->message->text) && false !== strpos($update->message->text, 'start')) {
            $telegramApi->sendMessage($update->message->chat->id, 'Hi there! I\'m Sticker2Image bot. I\'ll help you to convert your stickers to PNG images. Just send me some sticker.');
            return true;
        }

        if (isset($update->message->sticker)) {
            try {
                $telegramApi->sendMessage($update->message->chat->id, 'I\'ve got your sticker');
                $telegramApi->sendMessage($update->message->chat->id, '...');
                $file = $telegramApi->getFile($update->message->sticker);
                $filePath = "https://api.telegram.org/file/bot$token/" . $file->file_path;

                $log->log(200, $update->message->chat->id);
                if (isset($update->message->chat->first_name)) {
                    $log->log(200, $update->message->chat->first_name);
                }
                if (isset($update->message->chat->last_name)) {
                    $log->log(200, $update->message->chat->last_name);
                }
                if (isset($update->message->chat->username)) {
                    $log->log(200, $update->message->chat->username);
                }
                $log->log(200, $update->message->sticker->set_name);
                $log->log(200, $update->message->sticker->file_id);
                $log->log(200, $file->file_path);
                $log->log(200, '==============');

//                $db->saveAction([
//                    'chat_id' => $update->message->chat->id,
//                    'set_name' => $update->message->sticker->set_name,
//                    'file_id' => $update->message->sticker->file_id,
//                    'file_path' => $file->file_path
//                ]);

                $fileName = './img_' . time() . mt_rand();
                $imgPathWebp = $fileName . '.webp';
                copy(
                    $filePath,
                    $imgPathWebp
                );
                $telegramApi->sendPhoto($update->message->chat->id, $imgPathWebp);
                unlink($imgPathWebp);

                return true;

            } catch (\Exception $exception) {
                $telegramApi->sendMessage($update->message->chat->id, 'Sorry, I am tired. Some server error. Try in a few minutes :\'( ');
                $log->log(404, '===============');
                $log->log(404, $exception->getCode());
                $log->log(404, $exception->getMessage());
                $log->log(404, '===============');
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
            return true;
        }

        if (isset($update->message, $update->message->chat->id)) {
            $telegramApi->sendMessage($update->message->chat->id, 'I understand only stickers');
        }

//
//
//
//


    }

}