<?php
use EWW\Dpf\Command\ResendNotificationCommand;
use EWW\Dpf\Command\ReplaceFileCommand;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

return [
    'dpf:resend-notification' => [
        'class' => ResendNotificationCommand::class,
    ],
    'dpf:replace-file' => [
        'class' => ReplaceFileCommand::class,
    ],
];
