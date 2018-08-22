<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 8/22/18
 * Time: 6:25 PM
 */

namespace App\Command;

use App\Entity\Action;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\TelegramBot;

class MorningUpdateCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:morning-update')
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates a new user.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command shows actions count for a previous day');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = parse_ini_file('/var/www/sticker2img.top/config/config.ini');
        $token = $config['telegram_api_token'];
        $telegramApi = new TelegramBot($token);
        $em = $this->getContainer()->get('doctrine');
        $conn = $em->getConnection();
        $sql = 'SELECT count(*) from action a';
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $em = $this->getContainer()->get('doctrine');
        $conn = $em->getConnection();
        $date = new \DateTime();
        $dateFrom = $date->setTime(0, 0, 0)->format('Y-m-d h:i:s');
        $dateTo = $date->setTime(23, 59, 59)->format('Y-m-d h:i:s');
        $sql = "SELECT 
                  count(*) as count 
                FROM action a
                WHERE time >= '{$dateFrom}'
                AND time <= '{$dateTo}'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->fetchAll();
        $count = $res['count'] ? $res['count'] : 0;
        $telegramApi->sendMessage(7699150, 'Вчера бот был использован - ' . $count . 'раз(а)');
        $output->writeln($res['count']);
    }
}