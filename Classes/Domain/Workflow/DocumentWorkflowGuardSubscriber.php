<?php
namespace EWW\Dpf\Domain\Workflow;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use EWW\Dpf\Security\AuthorizationChecker;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Exception\LogicException;

class DocumentWorkflowGuardSubscriber implements EventSubscriberInterface
{
    /**
     * authorizationChecker
     *
     * @var \EWW\Dpf\Security\AuthorizationChecker
     * @inject
     */
    protected $authorizationChecker = null;

    public function onTransitionRequest(GuardEvent $event)
    {   /*
        if ($this->authorizationChecker->isGranted($event->getTransition()->getName(), $event->getSubject())) {
            return;
        } else {
            $event->setBlocked('true');
        }
        */
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.guard' => 'onTransitionRequest',
            //'workflow.transition' => 'onTransition',
        ];
    }
}