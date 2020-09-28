<?php

namespace EWW\Dpf\Services\ImportExternalMetadata;

use EWW\Dpf\Domain\Model\RisWosMetadata;
use EWW\Dpf\Domain\Model\ExternalMetadata;

class RisWosFileImporter extends AbstractImporter implements FileImporter
{

    /**
     * @var array
     */
    protected $mandatoryErrors = [];

    /**
     * Returns the list of all publication types
     *
     * @return array
     */
    public static function types()
    {
        return [
            'J' => 'Journal',
            'B' => 'Book',
            'S' => 'Series',
            'P' => 'Patent'
        ];
    }

    /**
     * @param string $filePath
     * @param array $mandatoryFields
     * @return array
     */
    public function loadFile($filePath, $mandatoryFields, $contentOnly = false)
    {
        $results = [];
        $mandatoryErrors = [];
        $mandatoryFieldErrors = [];

        $risWosReader = new RisReader();

        if ($contentOnly) {
            $risWosEntries = $risWosReader->parseFile($filePath, $contentOnly);
        } else {
            $risWosEntries = $risWosReader->parseFile($filePath);
        }

        foreach ($risWosEntries as $index => $risWosItem) {

            foreach ($mandatoryFields as $mandatoryField) {
                if (
                !(
                    array_key_exists($mandatoryField, $risWosItem)
                    && $risWosItem[$mandatoryField]
                )
                ) {
                    $mandatoryFieldErrors[$mandatoryField] = $mandatoryField;
                    $mandatoryErrors[$index] = [
                        'index' => $index + 1,
                        'title' => $risWosItem['TI'],
                        'fields' => $mandatoryFieldErrors
                    ];
                }
            }

            if (!$mandatoryErrors[$index]) {
                /** @var RisWosMetadata $risWosMetadata */
                $risWosMetadata = $this->objectManager->get(RisWosMetadata::class);
                $risWosMetadata->setSource(get_class($this));
                $risWosMetadata->setFeUser($this->security->getUser()->getUid());
                $risWosMetadata->setData($risWosReader->risRecordToXML($risWosItem));
                $results[] = $risWosMetadata;
            }

        }
        $this->mandatoryErrors = $mandatoryErrors;

        return $results;
    }

    /**
     * @return array
     */
    public function getMandatoryErrors()
    {
        return $this->mandatoryErrors;
    }

    /**
     * @return bool
     */
    public function hasMandatoryErrors()
    {
        return !empty($this->mandatoryErrors);
    }

    /**
     * @return \EWW\Dpf\Domain\Model\TransformationFile|void
     */
    protected function getDefaultXsltTransformation()
    {
        /** @var \EWW\Dpf\Domain\Model\Client $client */
        $client = $this->clientRepository->findAll()->current();

        /** @var \EWW\Dpf\Domain\Model\TransformationFile $xsltTransformationFile */
        return $client->getRisWosTransformation()->current();
    }

    /**
     * @return string|void
     */
    protected function getDefaultXsltFilePath()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:dpf/Resources/Private/Xslt/riswos-default.xsl'
        );
    }

    /**
     * @return string|void
     */
    protected function getImporterName()
    {
        return 'riswos';
    }

}