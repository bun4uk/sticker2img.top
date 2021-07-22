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
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use FilesystemIterator;
use Psr\Container\NotFoundExceptionInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
        echo '<!doctype html>
<html lang="en">
<head>
<meta author="vasili">
<title>Zdarova bandity!</title>
</head>
<body>
<h1>Zdarova bandity!</h1>
<p>Kak dela?</p>
<script async src="https://cdn.onthe.io/io.js/9FPVkP4NHVls"></script>
</body>
</html>
';
        die;
//        throw new NotFoundHttpException();
    }

    /**
     * @param Request $request
     * @param Connection $connection
     * @param TelegramBot $telegramApi
     * @return Response
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function answer(Request $request, Connection $connection, TelegramBot $telegramApi): Response
    {
        $update = json_decode($request->getContent());

        if (!property_exists($update, 'message')) {
            $telegramApi->sendMessage(7699150, "```{$request->getContent()}```");
            die;
        }

        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $entityManager = $this->getDoctrine()->getManager();
        $chatId = $update->message->chat->id > 0 ? $update->message->chat->id : $update->message->from->id;
        $user = $userRepository->findOneBy(['chatId' => $chatId]);
        if (isset($update->message->text) && false !== strpos($update->message->text, 'start')) {
            $telegramApi->sendMessage($chatId, 'Hi there! I\'m Sticker2Image bot. I\'ll help you to convert your stickers to PNG images. Just send me some sticker.');
            return new Response('sent');
        }

        if ($chatId === 7699150) {
            $telegramApi->sendKeyboard($chatId);
        }
        if (isset($update->message->sticker)) {
            if (!$user) {

                $user = new User();
                $user->setChatId($chatId);
                $user->setUsername($update->message->chat->username ?? $update->message->from->username ?? null);
                $user->setFirstname($update->message->chat->first_name ?? $update->message->from->first_name ?? null);
                $user->setLastname($update->message->chat->last_name ?? $update->message->from->last_name ?? null);
                $user->setFirstLaunch(new DateTime());

                $telegramApi->sendMessage(7699150, 'New user ' . $user->getFirstname() . ' ' . $user->getLastname() . ' @' . $user->getUsername());
            }
            $user->setLastAction(new DateTime());
            $entityManager->persist($user);
            $entityManager->flush();
            try {
                $telegramApi->sendMessage($chatId, 'I\'ve got your sticker');
                $telegramApi->sendMessage($chatId, '...');
                $file = $telegramApi->getFile($update->message->sticker);
                $filePath = "https://api.telegram.org/file/bot{$_SERVER['TELEGRAM_API_TOKEN']}/" . $file->file_path;
                $fileName = __DIR__ . '/../../public/files/img_' . time() . mt_rand();

                if ($update->message->sticker->is_animated) {
                    $telegramApi->sendMessage(7699150, "1");
                    $folder = __DIR__ . '/../../public/files/tgs/temp_folder_' . mt_rand();
                    if (!file_exists($folder) && !mkdir($folder, 0777, true) && !is_dir($folder)) {
                        throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
                    }
                    $telegramApi->sendMessage(7699150, "2");


                    $fileName = $folder . '/img_' . time() . mt_rand();

                    $imgPathTgs = $fileName . '.tgs';
                    copy(
                        $filePath,
                        $imgPathTgs
                    );
                    $telegramApi->sendMessage(7699150, "3");

                    exec('docker run --rm -v ' . $folder . '/:/source tgs-to-gif', $res);
                    $telegramApi->sendMessage(7699150, serialize($res));
                    $telegramApi->sendMessage(7699150, "4");

                    $telegramApi->sendDocument($chatId, $imgPathTgs . '.gif');
                    $telegramApi->sendMessage(7699150, "5");

                    $this->removeDir($folder);
                    $telegramApi->sendMessage(7699150, "6");

                } else {
                    $imgPathWebp = $fileName . '.webp';
                    copy(
                        $filePath,
                        $imgPathWebp
                    );
                    $telegramApi->sendPhoto($chatId, $imgPathWebp);
                    unlink($imgPathWebp);
                }

                $action = new Action();
                $action->setChatId($chatId);
                $action->setSetName($update->message->sticker->set_name ?? null);
                $action->setFileId($update->message->sticker->file_id ?? null);
                $action->setFilePath($file->file_path);
                $entityManager->persist($action);
                $entityManager->flush();

                return new Response('sent');

            } catch (Exception $exception) {
                $telegramApi->sendMessage($chatId, 'Sorry, I am tired. Some server error. Try in a few minutes :\'( ');
                $telegramApi->sendMessage(7699150, "```{$request->getContent()}```");

                return new Response('server_error');
            }
        }

        if (
            isset($update->message, $update->message->chat->username)
            && $chatId === 7699150
            && false !== strpos($update->message->text, 'CallsCount')
        ) {
            $dateFrom = date('Y-m-d') . ' 00:00:00';
            $dateTo = date('Y-m-d') . ' 23:59:59';
            $sql = "SELECT 
                  count(*) AS count 
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

        if (isset($update->message, $chatId)) {
            $telegramApi->sendMessage($chatId, 'I understand only stickers');
        }

        return new Response('sent', 200);
    }

    /**
     * @param $target
     */
    private function removeDir($target)
    {
        $directory = new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if (is_dir($file)) {
                rmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($target);
    }
}
