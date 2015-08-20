<?php
namespace EWW\Dpf\Services\Identifier;


class IdentifierUrn {
 
    /**
     * Standard prefix of the URN for DNB (Deutsche Nationalbibliothek).
     *
     * @var string
     */
    const NBN_URN_PREFIX = 'urn:nbn:de';
 
    /**
     * Holds generated dnb nbn urn string
     *
     * @var string
     */
    protected $nbnUrnString;
 
    /**
     * Use standard URN parts given to build an URN generator applicable to document identifiers.
     * A complete URN might look like "urn:nbn:de:bsz:14-qucosa-87650" having the component parts set to:
     *
     * $snid1    = bsz
     * $snid2    = 14
     * $niss     = qucosa
     *
     * The last part of the URN above "87650" consists of a document identifier "8765" and a check digit "0".
     *
     * @param string $snid1 First subnamespace identifier part of the URN.
     * @param string $snid2 Second subnamespace identifier part of the URN.
     * @param string $niss  (Optional) Namespace specific string part of the URN.
     * @throws InvalidArgumentException Thrown if at least one of the subnamespace parts contain other
     *                                  characters than characters.
     */
    public function __construct($snid1, $snid2, $niss = 'qucosa') {
 
        if (0 === preg_match('/^[a-zA-Z]+$/', $snid1)) {
            throw new \InvalidArgumentException('Used invalid first subnamespace identifier.');
        }
 
        if (0 === preg_match('/^[a-zA-Z0-9]+$/', $snid2)) {
            throw new \InvalidArgumentException('Used invalid second subnamespace identifier.');
        }
 
        if (0 === preg_match('/^[a-zA-Z0-9\-]+$/', $niss)) {
            throw new \InvalidArgumentException('Used invalid namespace specific string.');
        }
 
        $this->nbnUrnString = self::NBN_URN_PREFIX . ':' . $snid1 . ':' . $snid2 . '-' . $niss . '-';
    }
 
    /**
     * Generates complete URNs given a document identifier.
     *
     * @param integer $document_id Identifier of the Document
     * @throws InvalidArgumentException Thrown if the document identifier is not a number.
     * @return string The URN.
     */
    public function getUrn($document_id) {
 
        // regexp pattern for valid document id
      //  $id_pattern = '/^[1-9][0-9]*$/';
 
        // Check if document identifier is valid.
      //  if (0 === preg_match($id_pattern, $document_id)) {
      //      throw new \InvalidArgumentException('Used invalid arguments for document id.');
      //  }
 
        // calculate matching check digit
        $check_digit = self::getCheckDigit($document_id);
 
        // compose and return standard, snid1, snid2, niss, document id and check digit
        return $this->nbnUrnString . $document_id . $check_digit;
    }
 
    /**
     * Generates check digit for a given document identifer.
     *
     * @param integer $document_id ID of the Document
     * @throws InvalidArgumentException Thrown if the document identifier is not a number.
     * @return integer Check digit.
     */
    public function getCheckDigit($document_id) {
 
        // regexp pattern for valid document id
      //  $id_pattern = '/^[1-9][0-9]*$/';
 
        // Check if document identifier is valid.
      //  if (0 === preg_match($id_pattern, $document_id)) {
      //      throw new \InvalidArgumentException('Used invalid arguments for document id.');
      //  }
 
        // compose urn with document id
        $nbn = $this->nbnUrnString . $document_id;
 
        // Replace characters by numbers.
        $nbn_numbers = $this->replaceUrnChars($nbn);
 
        // identify string length
        $nbn_numbers_length = mb_strlen($nbn_numbers);
 
        // convert string to array of characters
        $nbn_numbers_array = preg_split('//', $nbn_numbers);
 
        // initialize sum
        $sum = 0;
 
        // calculate product sum
        for ($ii = 1; $ii <= $nbn_numbers_length; $ii++) {
            $sum = ($sum + ($nbn_numbers_array[$ii] * $ii));
        }
 
        // identify last digit
        $last_digit = $nbn_numbers_array[$nbn_numbers_length];
 
        // calculate quotient, round down
        $quotient = floor($sum/$last_digit);
 
        // convert to string
        $quotient = (string) $quotient;
 
        // identify last digit, which is the check digit
        $check_digit = ($quotient{mb_strlen($quotient)-1});
 
        // return check digit
        return $check_digit;
 
    }
 
 
    /**
     * Do a replacement of every character by a specific number according to DNB check digit allegation.
     *
     * @param string $urn A partial URN with the checkdigit missing.
     * @return string The given URN with all characters replaced by numbers.
     */
    private function replaceUrnChars($urn) {
        // However, the preg_replace function calls itself on the result of a previos run. In order to get
        // the replacement right, characters and numbers in the arrays below have got a specific order to make
        // it work. Be careful when changing those numbers! Tests may help ;)
 
        // convert to lower case
        $nbn = strtolower($urn);
 
        // array of characters to match
        $search_pattern = array('/9/', '/8/', '/7/', '/6/', '/5/', '/4/', '/3/', '/2/', '/1/', '/0/', '/a/', '/b/', '/c/',
                '/d/', '/e/', '/f/', '/g/', '/h/', '/i/', '/j/', '/k/', '/l/', '/m/', '/n/', '/o/', '/p/', '/q/', '/r/', '/s/',
                '/t/', '/u/', '/v/', '/w/', '/x/', '/y/', '/z/', '/-/', '/:/');
 
        // array of corresponding replacements, '9' will be temporarily replaced with placeholder '_' to prevent
        // replacement of '41' with '52'
        $replacements = array('_', 9, 8, 7, 6, 5, 4, 3, 2, 1, 18, 14, 19, 15, 16, 21, 22, 23, 24, 25, 42, 26, 27, 13, 28, 29,
        31, 12, 32, 33, 11, 34, 35, 36, 37, 38, 39, 17);
 
        // replace matching pattern in given nbn with corresponding replacement
        $nbn_numbers = preg_replace($search_pattern, $replacements, $nbn);
 
        // replace placeholder '_' with 41
        $nbn_numbers = preg_replace('/_/', 41, $nbn_numbers);
 
        return $nbn_numbers;
    }
 
}


?>
