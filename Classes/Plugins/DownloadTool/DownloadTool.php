<?php
namespace EWW\Dpf\Plugins\DownloadTool;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'DPF: DownloadTool' for the 'dlf / dpf' extension.
 *
 * @author    Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package    TYPO3
 * @subpackage    tx_dpf
 * @access    public
 */
class DownloadTool extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugins/DownloadTool.php';

    /**
     * The main method of the PlugIn
     *
     * @access    public
     *
     * @param    string        $content: The PlugIn content
     * @param    array        $conf: The PlugIn configuration
     *
     * @return    string        The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);

        // get the tx_dpf.settings too
        // Flexform wins over TS
        $dpfTSconfig = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.'];
        if (is_array($dpfTSconfig['settings.'])) {
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($dpfTSconfig['settings.'], $this->conf, true, false);
            $this->conf = $dpfTSconfig['settings.'];
        }

        // Load current document.
        $this->loadDocument();
        if ($this->doc === null || empty($this->conf['fileGrpDownload'])) {
            // Quit without doing anything if required variables are not set.
            return $content;
        }

        // Load template file.
        if (!empty($this->conf['templateFile'])) {
            $this->template = $this->templateService->getSubpart(file_get_contents($this->conf['templateFile']), '###TEMPLATE###');
        } else {
            $this->template = $this->templateService->getSubpart(file_get_contents($GLOBALS['TSFE']->tmpl->getFileName('EXT:dpf/Classes/Plugins/DownloadTool/template.tmpl')), '###TEMPLATE###');
        }

        $subpartArray['downloads'] = $this->templateService->getSubpart($this->template, '###DOWNLOADS###');
        // Show all PDF documents in download filegroup
        $attachments = $this->getAttachments();
        // Get VG-Wort-Url
        $vgwort = $this->getVGWortUrl();
        $content = '';
        if (is_array($attachments)) {
            foreach ($attachments as $id => $file) {
                $conf = array(
                    'useCacheHash'     => 0,
                    'parameter'        => $this->conf['apiPid'] . ' - piwik_download',
                    'additionalParams' => '&tx_dpf_getfile[qid]=' . $this->doc->recordId . '&tx_dpf_getfile[action]=attachment' . '&tx_dpf_getfile[attachment]=' . $file['ID'],
                    'forceAbsoluteUrl' => true,
                );
                $title = $file['LABEL'] ? $file['LABEL'] : $file['ID'];
                $markerArray['###FILE###'] = $this->cObj->typoLink($title, $conf);

                if(!empty($vgwort)) {
                    $markerArray['###VGWORT###'] = "<div class='div_vgwpixel' data-url='" . $vgwort . "'></div>";
                } else {
                    $markerArray['###VGWORT###'] = "";
                }
                $content .= $this->templateService->substituteMarkerArray($subpartArray['downloads'], $markerArray);
            }
        }
        return $this->templateService->substituteSubpart($this->template, '###DOWNLOADS###', $content, true);
    }

    /**
     * Get PDF document list

     * @return array of attachments
     */
    protected function getAttachments()
    {
        // Get pdf documents
        $xPath = 'mets:fileSec/mets:fileGrp[@USE="' . $this->conf['fileGrpDownload'] . '"]/mets:file';
        $files = $this->doc->mets->xpath($xPath);
        if (!is_array($files)) {
            return array();
        }
        foreach ($files as $key => $file) {
            $singleFile = array();
            foreach ($file->attributes('mext', 1) as $attribute => $value) {
                $singleFile[$attribute] = $value;
            }
            foreach ($file->attributes() as $attribute => $value) {
                $singleFile[$attribute] = $value;
            }
            $attachments[(string) $singleFile['ID']] = $singleFile;
        }
        if (is_array($attachments) && count($attachments) > 1) {
            ksort($attachments);
        }
        return $attachments;
    }

    protected function getVGWortUrl()
    {
        // Get VG-Wort-OpenKey for document
        $this->doc->mets->registerXPathNamespace("slub", 'http://slub-dresden.de/');
        $xPath = '//slub:info/slub:vgwortOpenKey';
        $vgwortOpenKey = $this->doc->mets->xpath($xPath)[0];

        if (!empty($vgwortOpenKey) or $vgwortOpenKey != FALSE ) {
            if (GeneralUtility::getIndpEnv('TYPO3_SSL')) {
                $vgwortserver = 'https://ssl-vg03.met.vgwort.de/na/';
            } else {
                $vgwortserver = 'http://vg08.met.vgwort.de/na/';
            }
            return $vgworturl = $vgwortserver . $vgwortOpenKey;
        }
        return FALSE;
    }
}
