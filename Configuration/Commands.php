<?php
use EWW\Dpf\Command\ResendNotificationCommand;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

return [
    'dpf:resend-notification' => [
        'class' => ResendNotificationCommand::class,
    ],
];
