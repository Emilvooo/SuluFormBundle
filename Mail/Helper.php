<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\FormBundle\Mail;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Helper implements HelperInterface
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var string
     */
    protected $toMail;

    /**
     * @var string
     */
    protected $fromMail;

    /**
     * @var string|null
     */
    protected $sender;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swift_Mailer $mailer
     * @param string $fromMail
     * @param string $toMail
     * @param string|null $sender
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Swift_Mailer $mailer,
        $fromMail,
        $toMail,
        $sender = null,
        $logger = null
    ) {
        $this->mailer = $mailer;
        $this->toMail = $toMail;
        $this->fromMail = $fromMail;
        $this->sender = $sender;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function sendMail(
        $subject,
        $body,
        $toMail = null,
        $fromMail = null,
        $html = true,
        $replyTo = null,
        $attachments = [],
        $ccMail = [],
        $bccMail = [],
        $plainText = null
    ) {
        $message = new \Swift_Message(
            $subject,
            $body
        );

        if (!$toMail) {
            $toMail = $this->toMail;
        }

        if (!$fromMail) {
            $fromMail = $this->fromMail;
        }

        if ($html) {
            $message->setContentType('text/html');
        }

        $message->setFrom($fromMail);
        $message->setTo($toMail);

        if ($this->sender) {
            $message->setSender($this->sender);
        }

        // Add attachments to the Swift Message
        if (count($attachments) > 0) {
            foreach ($attachments as $file) {
                if ($file instanceof \SplFileInfo) {
                    $path = $file->getPathname();
                    $name = $file->getFilename();

                    // if uploadedfile get original name
                    if ($file instanceof UploadedFile) {
                        $name = $file->getClientOriginalName();
                    }

                    $message->attach(\Swift_Attachment::fromPath($path)->setFilename($name));
                }
            }
        }

        if (null != $replyTo) {
            $message->setReplyTo($replyTo);
        }

        $message->setCc($ccMail);
        $message->setBcc($bccMail);
        if ($plainText != null) {
            $message->addPart($plainText, 'text/plain');
        }

        $this->logger->info(sprintf(
            'Try register mail from SuluFormBundle: ' . PHP_EOL .
            '   From: %s' . PHP_EOL .
            '   To: %s' . PHP_EOL .
            '   Reply to: %s' . PHP_EOL .
            '   Subject: %s' . PHP_EOL .
            '   CC: %s' . PHP_EOL .
            '   BCC: %s' . PHP_EOL .
            '   Plain text: %s' . PHP_EOL,
            is_string($fromMail) ? $fromMail : serialize($fromMail),
            is_string($toMail) ? $toMail : serialize($toMail),
            is_string($replyTo) ? $replyTo : serialize($toMail),
            is_string($subject) ? $subject : serialize($subject),
            is_string($ccMail) ? $ccMail : serialize($ccMail),
            is_string($bccMail) ? $bccMail : serialize($bccMail),
            is_string($plainText) ? $plainText : serialize($plainText)
        ));

        return $this->mailer->send($message);
    }
}
