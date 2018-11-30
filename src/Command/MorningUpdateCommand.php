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
    /**
     * @var TelegramBot
     */
    private $telegramApi;

    /**
     * MorningUpdateCommand constructor.
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->telegramApi = $this->getContainer()->get('App\Service\TelegramBot');
    }

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
//        $config = parse_ini_file('/var/www/sticker2img.top/config/config.ini');
//        $token = $config['telegram_api_token'];
//        $telegramApi = new TelegramBot($token);
        $em = $this->getContainer()->get('doctrine');
        $conn = $em->getConnection();
        $date = (new \DateTime('YESTERDAY'))->format('Y-m-d');
        $dateFrom = $date . ' 00:00:00';
        $dateTo = $date . ' 23:59:59';
        $sql = "SELECT 
                  count(*) as count 
                FROM action a
                WHERE time >= '{$dateFrom}'
                AND time <= '{$dateTo}'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->fetch();
        $count = (string)(reset($res) ?? 0);
        $output->writeln($count);
        $this->telegramApi->sendMessage(7699150, 'Вчера бот был использован - ' . $count . ' раз(а)');
    }
}
