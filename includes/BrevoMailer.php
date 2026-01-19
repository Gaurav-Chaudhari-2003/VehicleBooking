<?php

namespace includes;

require_once __DIR__ . '/../vendor/autoload.php';

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateSmtpEmail;
use Brevo\Client\Model\SendSmtpEmail;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class BrevoMailer
{
    private static function client(): TransactionalEmailsApi
    {
        $apiKey = $_ENV['BREVO_API_KEY'];

        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', $apiKey);

        return new TransactionalEmailsApi(null, $config);
    }

    /**
     * @throws ApiException
     */
    public static function send($to,$name,$subject,$html,$ics=null): CreateSmtpEmail
    {
        $email = [
            'subject'=>$subject,
            'sender'=>[
                'name'=>$_ENV['BREVO_SENDER_NAME'],
                'email'=>$_ENV['BREVO_SENDER_EMAIL']
            ],
            'to'=>[[ 'email'=>$to,'name'=>$name ]],
            'htmlContent'=>$html
        ];

        if ($ics) {
            $email['attachment'][] = [
                'content'=> base64_encode($ics),
                'name'=>'trip.ics'
            ];
        }

        $obj = new SendSmtpEmail($email);

        return self::client()->sendTransacEmail($obj);
    }

}
