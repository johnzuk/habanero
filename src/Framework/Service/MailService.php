<?php
namespace Habanero\Framework\Service;

use Habanero\Framework\Config\Config;

class MailService implements ServiceInterface
{
    /**
     * @param Config $config
     * @return \PHPMailer
     */
    public function getService(Config $config)
    {
        $mailerConfig = $config['mailer'];
        $mailer = new \PHPMailer();

        if ($mailerConfig['smtp']) {
            $mailer->isSMTP();
        }

        $mailer->Host = $mailerConfig['host'];
        $mailer->SMTPAuth = $mailerConfig['SMTPAuth'];
        $mailer->Username = $mailerConfig['username'];
        $mailer->Password = $mailerConfig['password'];
        $mailer->SMTPSecure = $mailerConfig['SMTPSecure'];
        $mailer->Port = $mailerConfig['port'];

        return $mailer;
    }
}