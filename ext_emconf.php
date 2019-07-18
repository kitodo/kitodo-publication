<?php
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

/***************************************************************
 * Extension Manager/Repository config file for ext: "dpf"
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title'            => 'Qucosa Publication',
    'description'      => '',
    'category'         => 'plugin',
    'author'           => 'effective WEBWORK GmbH',
    'author_email'     => 'info@effective-webwork.de',
    'state'            => 'stable',
    'internal'         => '',
    'uploadfolder'     => '1',
    'createDirs'       => 'uploads/tx_dpf',
    'clearCacheOnLoad' => 0,
    'version'          => '2.0.0',
    'constraints'      => array(
        'depends'   => array(
            'typo3' => '8.7.0-8.7.99',
        ),
        'conflicts' => array(
        ),
        'suggests'  => array(
        ),
    ),
);
