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

use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

class DocumentWorkflow
{
    const LOCAL_STATE_NONE          = 'NONE';
    const LOCAL_STATE_NEW           = 'NEW';
    const LOCAL_STATE_REGISTERED    = 'REGISTERED';
    const LOCAL_STATE_IN_PROGRESS   = 'IN_PROGRESS';
    const LOCAL_STATE_DISCARDED     = 'DISCARDED';
    const LOCAL_STATE_POSTPONED     = 'POSTPONED';

    const REMOTE_STATE_NONE         = 'NONE';
    const REMOTE_STATE_ACTIVE       = "ACTIVE";
    const REMOTE_STATE_INACTIVE     = "INACTIVE";
    const REMOTE_STATE_DELETED      = "DELETED";

    const STATE_NONE_NONE            = self::LOCAL_STATE_NONE.':'.self::REMOTE_STATE_NONE;

    // New
    const STATE_NEW_NONE             = self::LOCAL_STATE_NEW.':'.self::REMOTE_STATE_NONE;

    // Registered
    const STATE_REGISTERED_NONE      = self::LOCAL_STATE_REGISTERED.':'.self::REMOTE_STATE_NONE;

    // In progress
    const STATE_IN_PROGRESS_NONE     = self::LOCAL_STATE_IN_PROGRESS.':'.self::REMOTE_STATE_NONE;
    const STATE_IN_PROGRESS_ACTIVE   = self::LOCAL_STATE_IN_PROGRESS.":".self::REMOTE_STATE_ACTIVE;
    const STATE_IN_PROGRESS_INACTIVE = self::LOCAL_STATE_IN_PROGRESS.":".self::REMOTE_STATE_INACTIVE;
    const STATE_IN_PROGRESS_DELETED  = self::LOCAL_STATE_IN_PROGRESS.":".self::REMOTE_STATE_DELETED;

    // Active
    const STATE_NONE_ACTIVE          = self::LOCAL_STATE_NONE.':'.self::REMOTE_STATE_ACTIVE;

    // Postponed
    const STATE_POSTPONED_NONE       = self::LOCAL_STATE_POSTPONED.':'.self::REMOTE_STATE_NONE;
    const STATE_NONE_INACTIVE        = self::LOCAL_STATE_NONE.':'.self::REMOTE_STATE_INACTIVE;

    // Discarded
    const STATE_DISCARDED_NONE       = self::LOCAL_STATE_DISCARDED.':'.self::REMOTE_STATE_NONE;
    const STATE_NONE_DELETED         = self::LOCAL_STATE_NONE.':'.self::REMOTE_STATE_DELETED;

    const TRANSITION_CREATE              = "CREATE_TRANSITION";
    const TRANSITION_CREATE_REGISTER     = "CREATE_REGISTER_TRANSITION";
    const TRANSITION_REGISTER            = "REGISTER_TRANSITION";
    const TRANSITION_DISCARD             = "DISCARD_TRANSITION";
    const TRANSITION_POSTPONE            = "POSTPONE_TRANSITION";
    const TRANSITION_RELEASE_PUBLISH     = "RELEASE_PUBLISH_TRANSITION";
    const TRANSITION_RELEASE_ACTIVATE    = "RELEASE_ACTIVATE_TRANSITION";
    const TRANSITION_REMOTE_UPDATE       = "REMOTE_UPDATE_TRANSITION";
    const TRANSITION_IN_PROGRESS         = "IN_PROGRESS_TRANSITION";
    const TRANSITION_DELETE_LOCALLY      = "DELETE_LOCALLY_TRANSITION";
    const TRANSITION_DELETE_WORKING_COPY = "DELETE_WORKING_COPY_TRANSITION";
    const TRANSITION_DELETE_DISCARDED    = "DELETE_DISCARDED_TRANSITION";

    const ALIAS_STATE_NEW = "new";
    const ALIAS_STATE_REGISTERED = "registered";
    const ALIAS_STATE_POSTPONED = "postponed";
    const ALIAS_STATE_DISCARDED = "discarded";
    const ALIAS_STATE_IN_PROGRESS = "in_progress";
    const ALIAS_STATE_RELEASED = "released";

    const ALIAS_STATES = [
        self::ALIAS_STATE_NEW,
        self::ALIAS_STATE_REGISTERED,
        self::ALIAS_STATE_POSTPONED,
        self::ALIAS_STATE_DISCARDED,
        self::ALIAS_STATE_IN_PROGRESS,
        self::ALIAS_STATE_RELEASED
    ];

    const STATE_TO_ALIASSTATE_MAPPING = [
        DocumentWorkflow::STATE_NEW_NONE => DocumentWorkflow::ALIAS_STATE_NEW,
        DocumentWorkflow::STATE_REGISTERED_NONE => DocumentWorkflow::ALIAS_STATE_REGISTERED,
        DocumentWorkflow::STATE_POSTPONED_NONE => DocumentWorkflow::ALIAS_STATE_POSTPONED,
        DocumentWorkflow::STATE_DISCARDED_NONE => DocumentWorkflow::ALIAS_STATE_DISCARDED,
        DocumentWorkflow::STATE_IN_PROGRESS_NONE =>DocumentWorkflow::ALIAS_STATE_IN_PROGRESS,
        DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE => DocumentWorkflow::ALIAS_STATE_IN_PROGRESS,
        DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE => DocumentWorkflow::ALIAS_STATE_IN_PROGRESS,
        DocumentWorkflow::STATE_IN_PROGRESS_DELETED => DocumentWorkflow::ALIAS_STATE_IN_PROGRESS,
        DocumentWorkflow::STATE_NONE_ACTIVE => DocumentWorkflow::ALIAS_STATE_RELEASED,
        DocumentWorkflow::STATE_NONE_INACTIVE => DocumentWorkflow::ALIAS_STATE_POSTPONED,
        DocumentWorkflow::STATE_NONE_DELETED => DocumentWorkflow::ALIAS_STATE_DISCARDED
    ];

    const ALIASSTATE_TO_STATE_MAPPING = [
        DocumentWorkflow::ALIAS_STATE_NEW => [DocumentWorkflow::STATE_NEW_NONE],
        DocumentWorkflow::ALIAS_STATE_REGISTERED => [DocumentWorkflow::STATE_REGISTERED_NONE],
        DocumentWorkflow::ALIAS_STATE_POSTPONED => [
            DocumentWorkflow::STATE_POSTPONED_NONE,
            DocumentWorkflow::STATE_NONE_INACTIVE
        ],
        DocumentWorkflow::ALIAS_STATE_DISCARDED => [
            DocumentWorkflow::STATE_DISCARDED_NONE,
            DocumentWorkflow::STATE_NONE_DELETED
        ],
        DocumentWorkflow::ALIAS_STATE_IN_PROGRESS => [
            DocumentWorkflow::STATE_IN_PROGRESS_NONE,
            DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE,
            DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE,
            DocumentWorkflow::STATE_IN_PROGRESS_DELETED
        ],
        DocumentWorkflow::ALIAS_STATE_RELEASED => [DocumentWorkflow::STATE_NONE_ACTIVE]
    ];

    const PLACES = [
        self::STATE_NONE_NONE,
        self::STATE_NEW_NONE,
        self::STATE_REGISTERED_NONE,
        self::STATE_IN_PROGRESS_NONE,
        self::STATE_DISCARDED_NONE,
        self::STATE_NONE_DELETED,
        self::STATE_POSTPONED_NONE,
        self::STATE_NONE_INACTIVE,
        self::STATE_NONE_ACTIVE,
        self::STATE_IN_PROGRESS_ACTIVE,
        self::STATE_IN_PROGRESS_INACTIVE,
        self::STATE_IN_PROGRESS_DELETED
    ];

    const TRANSITIONS = [
        self::TRANSITION_CREATE => [
            "from" => [self::STATE_NONE_NONE],
            "to" => self::STATE_NEW_NONE
        ],
        self::TRANSITION_CREATE_REGISTER => [
            "from" => [self::STATE_NONE_NONE],
            "to" => self::STATE_REGISTERED_NONE
        ],
        self::TRANSITION_REGISTER => [
            "from" => [self::STATE_NEW_NONE],
            "to" => self::STATE_REGISTERED_NONE
        ],
        self::TRANSITION_DISCARD => [
            "from" => [
                self::STATE_REGISTERED_NONE,
                self::STATE_IN_PROGRESS_NONE,
                self::STATE_POSTPONED_NONE,
                self::STATE_IN_PROGRESS_ACTIVE,
                self::STATE_IN_PROGRESS_INACTIVE,
                self::STATE_IN_PROGRESS_DELETED,
                self::STATE_NONE_ACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_DELETED
            ],
            "to" => [
                self::STATE_DISCARDED_NONE,
                self::STATE_DISCARDED_NONE,
                self::STATE_DISCARDED_NONE,
                self::STATE_NONE_DELETED,
                self::STATE_NONE_DELETED,
                self::STATE_NONE_DELETED,
                self::STATE_NONE_DELETED,
                self::STATE_NONE_DELETED,
                self::STATE_NONE_DELETED
            ]
        ],
        self::TRANSITION_POSTPONE => [
            "from" => [
                self::STATE_REGISTERED_NONE,
                self::STATE_IN_PROGRESS_NONE,
                self::STATE_DISCARDED_NONE,
                self::STATE_IN_PROGRESS_ACTIVE,
                self::STATE_IN_PROGRESS_DELETED,
                self::STATE_IN_PROGRESS_INACTIVE,
                self::STATE_NONE_ACTIVE,
                self::STATE_NONE_DELETED,
                self::STATE_NONE_INACTIVE
            ],
            "to" => [
                self::STATE_POSTPONED_NONE,
                self::STATE_POSTPONED_NONE,
                self::STATE_POSTPONED_NONE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_INACTIVE
            ]
        ],
        self::TRANSITION_RELEASE_PUBLISH => [
            "from" => [
                self::STATE_REGISTERED_NONE,
                self::STATE_IN_PROGRESS_NONE,
                self::STATE_DISCARDED_NONE,
                self::STATE_POSTPONED_NONE,
            ],
            "to" => self::STATE_NONE_ACTIVE
        ],
        self::TRANSITION_RELEASE_ACTIVATE => [
            "from" => [
                self::STATE_IN_PROGRESS_ACTIVE,
                self::STATE_IN_PROGRESS_INACTIVE,
                self::STATE_IN_PROGRESS_DELETED,
                self::STATE_NONE_ACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_DELETED
            ],
            "to" => self::STATE_NONE_ACTIVE
        ],
        self::TRANSITION_DELETE_LOCALLY => [
            "from" => [self::STATE_NEW_NONE],
            "to" => self::STATE_NONE_NONE
        ],
        self::TRANSITION_DELETE_DISCARDED => [
            "from" => [self::STATE_DISCARDED_NONE],
            "to" => self::STATE_NONE_NONE
        ],
        self::TRANSITION_DELETE_WORKING_COPY => [
            "from" => [self::STATE_IN_PROGRESS_ACTIVE, self::STATE_IN_PROGRESS_INACTIVE, self::STATE_IN_PROGRESS_DELETED],
            "to" => [self::STATE_NONE_ACTIVE, self::STATE_NONE_INACTIVE, self::STATE_NONE_DELETED]
        ],
        self::TRANSITION_REMOTE_UPDATE => [
            "from" => [
                self::STATE_IN_PROGRESS_ACTIVE,
                self::STATE_IN_PROGRESS_INACTIVE,
                self::STATE_IN_PROGRESS_DELETED,
                self::STATE_NONE_ACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_DELETED
            ],
            "to" => [
                self::STATE_NONE_ACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_DELETED,
                self::STATE_NONE_ACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_DELETED
            ]
        ],
        self::TRANSITION_IN_PROGRESS => [
            "from" => [
                self::STATE_REGISTERED_NONE,
                self::STATE_NONE_ACTIVE,
                self::STATE_NONE_INACTIVE,
                self::STATE_NONE_DELETED
            ],
            "to" => [
                self::STATE_IN_PROGRESS_NONE,
                self::STATE_IN_PROGRESS_ACTIVE,
                self::STATE_IN_PROGRESS_INACTIVE,
                self::STATE_IN_PROGRESS_DELETED
            ]
        ]
    ];

    public static function getWorkflow()
    {
        $definitionBuilder = new DefinitionBuilder();

        $definitionBuilder->addPlaces(self::PLACES);

        foreach (self::TRANSITIONS as $transitionName => $transition) {
            if (!empty($transition["from"]) && !empty($transition["to"])) {
                foreach ($transition["from"] as $key => $fromState) {
                    if (is_array($transition["to"])) {
                        $definitionBuilder->addTransition(
                            new Transition($transitionName, $fromState, $transition["to"][$key])
                        );
                    } else {
                        $definitionBuilder->addTransition(
                             new Transition($transitionName, $fromState, $transition["to"])
                        );
                    }
                }
            }
        }

        $definition = $definitionBuilder->build();

        $marking = new MethodMarkingStore(TRUE, 'state');

        return new Workflow($definition, $marking);
    }

    public static function constructState($localState, $remoteState) {
        return $localState . ':' . $remoteState;
    }

    public static function getAliasStateByLocalOrRepositoryState($state)
    {
        // FIXME: Information hiding. Future note: Fedora 3 implementation knowledge should be hidden in separate class module.

        // A,I and D are the states returned by a repository search.
        // The other states are the ones used in the document table.
        $aliasStateMapping = self::STATE_TO_ALIASSTATE_MAPPING;
        $aliasStateMapping["A"] = 'released';
        $aliasStateMapping["I"] = 'postponed';
        $aliasStateMapping["D"] = 'discarded';

        if (array_key_exists($state, $aliasStateMapping)) {
            return $aliasStateMapping[$state];
        }

        return '';
    }

}
