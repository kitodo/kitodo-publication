<?php

namespace EWW\Dpf\Services\ImportExternalMetadata;

use Symfony\Component\Serializer\Encoder\XmlEncoder;
use EWW\Dpf\Domain\Model\BibTexMetadata;
use EWW\Dpf\Domain\Model\ExternalMetadata;

use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor;

class BibTexFileImporter extends AbstractImporter implements FileImporter
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
            'article',
            'book',
            'booklet',
            'inbook',
            'incollection',
            'inproceedings',
            'manual',
            'mastersthesis',
            'misc',
            'phdthesis',
            'proceedings',
            'techreport',
            'unpublished'
        ];
    }

    /**
     * @param string $file Can be a path to a file or a string with the file content
     * @param bool $contentOnly Determines if $file is a path or content as a string
     * @return array
     * @throws \ErrorException
     * @throws \RenanBr\BibTexParser\Exception\ParserException
     */
    protected function parseFile($file, $contentOnly = false)
    {
        //$data = json_decode($response->__toString(),true);
        //$encoder = new XmlEncoder();
        $listener = new Listener();
        $listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
        $parser = new Parser();
        $parser->addListener($listener);

        if ($contentOnly) {
            $parser->parseString($file);
        } else {
            $parser->parseFile($file);
        }

        $entries = $listener->export();

        foreach ($entries as $index => $fields) {
            foreach ($fields as $key => $field) {
                $field = str_replace("{\\\"o}", "ö", $field);
                $field = str_replace("{\\\"O}", "Ö", $field);
                $field = str_replace("{\\\"a}", "ä", $field);
                $field = str_replace("{\\\"A}", "Ä", $field);
                $field = str_replace("{\\\"u}", "ü", $field);
                $field = str_replace("{\\\"U}", "Ü", $field);
                $field = str_replace("\\textendash", "-", $field);
                $field = str_replace("\\textemdash", "—", $field);
                $field = str_replace("\\textquoteright", "'", $field);
                $field = str_replace("\\textquoteleft", "'", $field);
                $field = str_replace("{\\ss}", "ß", $field);
                $field = preg_replace("/{\s*[\\\]textunderscore\s*}/", '_', $field);
                $field = preg_replace("/{\s*[\\\]textquotedbl\s*}/", '"', $field);
                $entries[$index][$key] = preg_replace("/{\s*[\\\]&\s*}/", '&', $field);
            }
        }

        return $entries;
    }

    /**
     * @param string $filePath
     * @param string $mandatoryFieldSettings
     * @param bool $contentOnly Determines if $file is a path or content as a string
     * @return array
     */
    public function loadFile($filePath, $mandatoryFieldSettings, $contentOnly = false)
    {
        $results = [];
        $mandatoryErrors = [];
        $mandatoryFieldErrors = [];

        $mandatoryFields = array_map(
            'trim',
            explode(',', $mandatoryFieldSettings)
        );

        foreach ($mandatoryFields as $key => $value) {
            $orFields = array_map(
                'trim',
                explode('|', $value)
            );
            $mandatoryFields[$key] = $orFields;
        }

        if ($contentOnly) {
            $bibTexEntries = $this->parseFile($filePath, $contentOnly);
        } else {
            $bibTexEntries = $this->parseFile($filePath);
        }

        $encoder = new XmlEncoder();

        foreach ($bibTexEntries as $index => $bibTexItem) {

            $mandatoryFieldErrors = [];
            foreach ($mandatoryFields as $combinedMandatoryField) {

                $mandatoryOk = false;
                foreach ($combinedMandatoryField as $key => $value) {
                    $mandatoryOk = $mandatoryOk || (
                            array_key_exists($value, $bibTexItem)
                            && $bibTexItem[$value]
                        );
                }

                if (!$mandatoryOk) {
                    $mandatoryFieldErrors[implode('|', $combinedMandatoryField)] = implode(
                        '|',
                        $combinedMandatoryField
                    );
                    $mandatoryErrors[$index] = [
                        'index' => $index + 1,
                        'title' => $bibTexItem['title'],
                        'fields' => $mandatoryFieldErrors
                    ];
                }
            }

            if (!$mandatoryErrors[$index]) {

                $bibTexData = $bibTexItem;

                if (array_key_exists('author', $bibTexItem)) {
                    $bibTexData['author'] = $this->splitPersons($bibTexItem['author']);
                }

                if (array_key_exists('editor', $bibTexItem)) {
                    $bibTexData['editor'] = $this->splitPersons($bibTexItem['editor']);
                }

                /** @var BibTexMetadata $bibTexMetadata */
                $bibTexMetadata = $this->objectManager->get(BibTexMetadata::class);

                $bibTexMetadata->setSource(get_class($this));
                $bibTexMetadata->setFeUser($this->security->getUser()->getUid());
                $bibTexMetadata->setData($encoder->encode($bibTexData, 'xml'));

                $results[] = $bibTexMetadata;
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
        return $client->getBibTexTransformation()->current();
    }

    /**
     * @return string|void
     */
    protected function getDefaultXsltFilePath()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:dpf/Resources/Private/Xslt/bibtex-default.xsl'
        );
    }

    /**
     * @return string|void
     */
    protected function getImporterName()
    {
        return 'bibtex';
    }

    /**
     * @param string $persons
     */
    protected function splitPersons($persons)
    {
        $results = [];

        $persons = array_map('trim', explode(' and ', $persons));

        foreach ($persons as $person) {
            list($family, $given) = array_map('trim', explode(',', $person));
            $results[] = [
                'family' => $family,
                'given' => $given
            ];
        }

        return $results;
    }

}