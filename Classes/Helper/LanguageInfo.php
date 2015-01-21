<?php
namespace EWW\Dpf\Helper;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Helper to get language information with ISO 639-2/B codes,
 * using the extension static_info_tables.
 */
class LanguageInfo {     

  /**
   * languageRepository
   *
   * @var \SJBR\StaticInfoTables\Domain\Repository\LanguageRepository
   * @inject
   */
  protected $languageRepository = NULL;
  
  protected $isoCodeA2ToIsoCodeA3 = array(
    'aa'=>'aar',
    'ab'=>'abk',
    'ae'=>'ave',
    'af'=>'afr',
    'ak'=>'aka',
    'am'=>'amh',
    'an'=>'arg',
    'ar'=>'ara',
    'as'=>'asm',
    'av'=>'ava',
    'ay'=>'aym',
    'az'=>'aze',
    'ba'=>'bak',
    'be'=>'bel',
    'bg'=>'bul',
    'bh'=>'bih',
    'bi'=>'bis',
    'bm'=>'bam',
    'bn'=>'ben',
    'bo'=>'tib',
    'br'=>'bre',
    'bs'=>'bos',
    'ca'=>'cat',
    'ce'=>'che',
    'ch'=>'cha',
    'co'=>'cos',
    'cr'=>'cre',
    'cs'=>'cze',
    'cu'=>'chu',
    'cv'=>'chv',
    'cy'=>'wel',
    'da'=>'dan',
    'de'=>'ger',
    'dv'=>'div',
    'dz'=>'dzo',
    'ee'=>'ewe',
    'el'=>'gre',
    'en'=>'eng',
    'eo'=>'epo',
    'es'=>'spa',
    'et'=>'est',
    'eu'=>'baq',
    'fa'=>'per',
    'ff'=>'ful',
    'fi'=>'fin',
    'fj'=>'fij',
    'fo'=>'fao',
    'fr'=>'fre',
    'fy'=>'fry',
    'ga'=>'gle',
    'gd'=>'gla',
    'gl'=>'glg',
    'gn'=>'grn',
    'gu'=>'guj',
    'gv'=>'glv',
    'ha'=>'hau',
    'he'=>'heb',
    'hi'=>'hin',
    'ho'=>'hmo',
    'hr'=>'hrv',
    'ht'=>'hat',
    'hu'=>'hun',
    'hy'=>'arm',
    'hz'=>'her',
    'ia'=>'ina',
    'id'=>'ind',
    'ie'=>'ile',
    'ig'=>'ibo',
    'ii'=>'iii',
    'ik'=>'ipk',
    'io'=>'ido',
    'is'=>'ice',
    'it'=>'ita',
    'iu'=>'iku',
    'ja'=>'jpn',
    'jv'=>'jav',
    'ka'=>'geo',
    'kg'=>'kon',
    'ki'=>'kik',
    'kj'=>'kua',
    'kk'=>'kaz',
    'kl'=>'kal',
    'km'=>'khm',
    'kn'=>'kan',
    'ko'=>'kor',
    'kr'=>'kau',
    'ks'=>'kas',
    'ku'=>'kur',
    'kv'=>'kom',
    'kw'=>'cor',
    'ky'=>'kir',
    'la'=>'lat',
    'lb'=>'ltz',
    'lg'=>'lug',
    'li'=>'lim',
    'ln'=>'lin',
    'lo'=>'lao',
    'lt'=>'lit',
    'lu'=>'lub',
    'lv'=>'lav',
    'mg'=>'mlg',
    'mh'=>'mah',
    'mi'=>'mao',
    'mk'=>'mac',
    'ml'=>'mal',
    'mn'=>'mon',
    'mo'=>'rum',
    'mr'=>'mar',
    'ms'=>'may',
    'mt'=>'mlt',
    'my'=>'bur',
    'na'=>'nau',
    'nb'=>'nob',
    'nd'=>'nde',
    'ne'=>'nep',
    'ng'=>'ndo',
    'nl'=>'dut',
    'nn'=>'nno',
    'no'=>'nor',
    'nr'=>'nbl',
    'nv'=>'nav',
    'ny'=>'nya',
    'oc'=>'oci',
    'oj'=>'oji',
    'om'=>'orm',
    'or'=>'ori',
    'os'=>'oss',
    'pa'=>'pan',
    'pi'=>'pli',
    'pl'=>'pol',
    'ps'=>'pus',
    'pt'=>'por',
    'qu'=>'que',
    'rm'=>'roh',
    'rn'=>'run',
    'ro'=>'rum',
    'ru'=>'rus',
    'rw'=>'kin',
    'sa'=>'san',
    'sc'=>'srd',
    'sd'=>'snd',
    'se'=>'sme',
    'sg'=>'sag',
    'si'=>'sin',
    'sk'=>'slo',
    'sl'=>'slv',
    'sm'=>'smo',
    'sn'=>'sna',
    'so'=>'som',
    'sq'=>'alb',
    'sr'=>'srp',
    'ss'=>'ssw',
    'st'=>'sot',
    'su'=>'sun',
    'sv'=>'swe',
    'sw'=>'swa',
    'ta'=>'tam',
    'te'=>'tel',
    'tg'=>'tgk',
    'th'=>'tha',
    'ti'=>'tir',
    'tk'=>'tuk',
    'tl'=>'tgl',
    'tn'=>'tsn',
    'to'=>'ton',
    'tr'=>'tur',
    'ts'=>'tso',
    'tt'=>'tat',
    'tw'=>'twi',
    'ty'=>'tah',
    'ug'=>'uig',
    'uk'=>'ukr',
    'ur'=>'urd',
    'uz'=>'uzb',
    've'=>'ven',
    'vi'=>'vie',
    'vo'=>'vol',
    'wa'=>'wln',
    'wo'=>'wol',
    'xh'=>'xho',
    'yi'=>'yid',
    'yo'=>'yor',
    'za'=>'zha',
    'zh'=>'chi',
    'zu'=>'zul',
  );

  public function getLanguages() {
     
    $languages = $this->languageRepository->findAll();
     
    foreach ( $languages as $language ) {
      
      $isoCodeA2 = strtolower($language->getIsoCodeA2());
      
      if (key_exists($isoCodeA2, $this->isoCodeA2ToIsoCodeA3)) {       
        $lang = new \EWW\Dpf\Domain\Model\Language;       
        $lang->setNameLocalized($language->getNameLocalized());
        $lang->setIsoCodeA3($this->isoCodeA2ToIsoCodeA3[$isoCodeA2]);
        $langArray[] = $lang;       
      }
    }
          
    return $langArray;
  }
  
  
}