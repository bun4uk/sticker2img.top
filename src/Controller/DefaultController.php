<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 8/5/18
 * Time: 8:26 PM
 */

namespace App\Controller;

use App\Entity\Action;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\TelegramBot;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DefaultController
 * @package App\Controller
 */
class DefaultController extends AbstractController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        throw new NotFoundHttpException();
    }

    /**
     * @param Request $request
     * @param Connection $connection
     * @return Response
     * @throws \Exception
     */
    public function answer(Request $request, Connection $connection): Response
    {
        $config = parse_ini_file('/var/www/sticker2img.top/config/config.ini');
        $token = $config['telegram_api_token'];
        $telegramApi = new TelegramBot($token);
        $update = json_decode($request->getContent());
//        if (!$update) {
//            throw new NotFoundHttpException();
//        }
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $entityManager = $this->getDoctrine()->getManager();
        $user = $userRepository->findOneBy(['chatId' => $update->message->chat->id]);

        if (isset($update->message->text) && false !== strpos($update->message->text, 'start')) {
            $telegramApi->sendMessage($update->message->chat->id, 'Hi there! I\'m Sticker2Image bot. I\'ll help you to convert your stickers to PNG images. Just send me some sticker.');
            return new Response('sent');
        }

        if ($update->message->chat->id === 7699150) {
            $telegramApi->sendKeyboard($update->message->chat->id);
        }
        if (isset($update->message->sticker)) {
            if (!$user) {
                $user = new User();
                $user->setChatId($update->message->chat->id);
                $user->setUsername($update->message->chat->username ?? null);
                $user->setFirstname($update->message->chat->first_name ?? null);
                $user->setLastname($update->message->chat->last_name ?? null);
                $user->setFirstLaunch(new \DateTime());

                $telegramApi->sendMessage(7699150, 'New user ' . $user->getFirstname() . ' ' . $user->getLastname() . ' @' . $user->getUsername());
            }
            $user->setLastAction(new \DateTime());
            $entityManager->persist($user);
            $entityManager->flush();
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

                $action = new Action();
                $action->setChatId($update->message->chat->id);
                $action->setSetName($update->message->sticker->set_name ?? null);
                $action->setFileId($update->message->sticker->file_id ?? null);
                $action->setFilePath($file->file_path);
                $entityManager->persist($action);
                $entityManager->flush();

                return new Response('sent');

            } catch (\Exception $exception) {
                $telegramApi->sendMessage($update->message->chat->id, 'Sorry, I am tired. Some server error. Try in a few minutes :\'( ');
                return new Response('server_error');
            }
        }

        if (
            isset($update->message, $update->message->chat->username)
            && $update->message->chat->id === 7699150
            && false !== strpos($update->message->text, 'CallsCount')
        ) {
            $dateFrom = date('Y-m-d') . ' 00:00:00';
            $dateTo = date('Y-m-d') . ' 23:59:59';
            $sql = "SELECT 
                  count(*) as count 
                FROM action a
                WHERE time >= '{$dateFrom}'
                AND time <= '{$dateTo}'";

            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $res = $stmt->fetch();
            $count = (string)(reset($res) ?? 0);
            $telegramApi->sendMessage(7699150, 'Бот был использован - ' . $count . ' раз(а)');

            return new Response('sent');
        }

        if (isset($update->message, $update->message->chat->id)) {
            $telegramApi->sendMessage($update->message->chat->id, 'I understand only stickers');
        }

        return new Response('sent', 200);
    }

}