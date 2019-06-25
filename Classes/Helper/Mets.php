<?php
namespace EWW\Dpf\Helper;

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

class Mets
{

    /**
     * mets
     *
     * @var \DOMDocument
     */
    protected $metsDom;

    public function __construct($metsXml)
    {
        $this->setMetsXml($metsXml);
    }

    public function setMetsXml($metsXml)
    {
        $metsDom = new \DOMDocument();
        $metsDom->loadXML($metsXml);
        $this->metsDom = $metsDom;
    }

    public function getMetsXml()
    {
        return $this->metsDom->saveXML();
    }

    public function getMetsXpath()
    {
        return new \DOMXPath($this->metsDom);
    }

    public function getMods()
    {
        $xpath = $this->getMetsXpath();

        $xpath->registerNamespace("mods", "http://www.loc.gov/mods/v3");
        $modsNodes = $xpath->query("/mets:mets/mets:dmdSec/mets:mdWrap/mets:xmlData/mods:mods");

        $modsXml = $this->metsDom->saveXML($modsNodes->item(0));

        $mods = new Mods($modsXml);

        return $mods;
    }

    public function getSlub()
    {
        $xpath = $this->getMetsXpath();

        $xpath->registerNamespace("slub", "http://slub-dresden.de/");
        $slubNodes = $xpath->query("/mets:mets/mets:amdSec/mets:techMD/mets:mdWrap/mets:xmlData/slub:info");

        $slubXml = $this->metsDom->saveXML($slubNodes->item(0));

        $slub = new Slub($slubXml);

        return $slub;
    }

    public function getState()
    {

        $xpath = $this->getMetsXpath();
        $xpath->registerNamespace("mets", "http://www.loc.gov/METS/");

        $dmdSec = $xpath->query("/mets:mets/mets:dmdSec");
        return $dmdSec->item(0)->getAttribute("STATUS");
    }

    public function getFiles()
    {
        $xpath = $this->getMetsXpath();

        $xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");

        $fileNodesOriginal = $xpath->query('/mets:mets/mets:fileSec/mets:fileGrp[@USE="ORIGINAL"]/mets:file');
        $fileNodesDownload = $xpath->query('/mets:mets/mets:fileSec/mets:fileGrp[@USE="DOWNLOAD"]/mets:file');
        $fileNodesDeleted = $xpath->query('/mets:mets/mets:fileSec/mets:fileGrp[@USE="DELETED"]/mets:file');

        $files = array();

        foreach ($fileNodesOriginal as $item) {

            $xlinkNS = "http://www.w3.org/1999/xlink";
            $mextNS  = "http://slub-dresden.de/mets";

            $flocat = $xpath->query('mets:FLocat', $item);
            if ($flocat->length > 0) {
                $href = $flocat->item(0)->getAttributeNS($xlinkNS, "href");
            }

            $files[] = array(
                'id'       => $item->getAttribute("ID"),
                'mimetype' => $item->getAttribute("MIMETYPE"),
                'href'     => $href,
                'title'    => $item->getAttributeNS($mextNS, "LABEL"),
                'archive'  => ($item->getAttribute("USE") == 'ARCHIVE'),
                'download' => false,
                'deleted'  => false,
            );
        }

        foreach ($fileNodesDownload as $item) {

            $xlinkNS = "http://www.w3.org/1999/xlink";
            $mextNS  = "http://slub-dresden.de/mets";

            $flocat = $xpath->query('mets:FLocat', $item);
            if ($flocat->length > 0) {
                $href = $flocat->item(0)->getAttributeNS($xlinkNS, "href");
            }

            $files[] = array(
                'id'       => $item->getAttribute("ID"),
                'mimetype' => $item->getAttribute("MIMETYPE"),
                'href'     => $href,
                'title'    => $item->getAttributeNS($mextNS, "LABEL"),
                'archive'  => ($item->getAttribute("USE") == 'ARCHIVE'),
                'download' => true,
                'deleted'  => false,
            );
        }

        foreach ($fileNodesDeleted as $item) {

            $xlinkNS = "http://www.w3.org/1999/xlink";
            $mextNS  = "http://slub-dresden.de/mets";

            $flocat = $xpath->query('mets:FLocat', $item);
            if ($flocat->length > 0) {
                $href = $flocat->item(0)->getAttributeNS($xlinkNS, "href");
            }

            $files[] = array(
                'id'       => $item->getAttribute("ID"),
                'mimetype' => $item->getAttribute("MIMETYPE"),
                'href'     => $href,
                'title'    => $item->getAttributeNS($mextNS, "LABEL"),
                'archive'  => ($item->getAttribute("USE") == 'ARCHIVE'),
                'download' => false,
                'deleted'  => true,
            );
        }

        return $files;

    }

}
