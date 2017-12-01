<?php
namespace EWW\Dpf\Controller;

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

/**
 * API to return METS dissemination and Attachments from Fedora.
 * Also renders METS XML for preview. Structure of the URIs totally
 * depend on proper RealURL configuration.
 *
 * Example:
 *
 * 1. METS from Fedora
 *   http://localhost/api/qucosa:1234/mets/
 *
 *   This always returns METS which is supplemented with additional information.
 *   The embedded MODS record is not the original MODS as it is stored in the
 *   repository datastream.
 *
 * 2. Attachment from Fedora
 *   http://localhost/api/qucosa:1234/attachment/ATT-0/
 *
 * 3. METS from Kitodo.Publication (this extension)
 *   http://localhost/api/3/preview/
 *
 * 4. DataCite from Kitodo.Publication (this extension)
 *
 * @author    Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author    Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author    Florian Rügamer <florian.ruegamer@slub-dresden.de>
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * GetFileController
 */
class GetFileController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository;

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @inject
     */
    protected $clientConfigurationManager;

    /*
     * Language database, based on Wikipedia
     * Source: https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     * array('ISO 639-1', 'ISO 639-2/T', '639-2/B', 'ISO 639-3', 'english language name', 'local language name(s)')
     *
     * @var array
     */
    protected $languages = array(
        array('ab', 'abk', 'abk', 'abk', 'Abkhaz', 'аҧсуа бызшәа, аҧсшәа'),
        array('aa', 'aar', 'aar', 'aar', 'Afar', 'Afaraf'),
        array('af', 'afr', 'afr', 'afr', 'Afrikaans', 'Afrikaans'),
        array('ak', 'aka', 'aka', 'aka', 'Akan', 'Akan'),
        array('sq', 'sqi', 'alb', 'sqi', 'Albanian', 'Shqip'),
        array('am', 'amh', 'amh', 'amh', 'Amharic', 'አማርኛ'),
        array('ar', 'ara', 'ara', 'ara', 'Arabic', 'العربية'),
        array('an', 'arg', 'arg', 'arg', 'Aragonese', 'aragonés'),
        array('hy', 'hye', 'arm', 'hye', 'Armenian', 'Հայերեն'),
        array('as', 'asm', 'asm', 'asm', 'Assamese', 'অসমীয়া'),
        array('av', 'ava', 'ava', 'ava', 'Avaric', 'авар мацӀ, магӀарул мацӀ'),
        array('ae', 'ave', 'ave', 'ave', 'Avestan', 'avesta'),
        array('ay', 'aym', 'aym', 'aym', 'Aymara', 'aymar aru'),
        array('az', 'aze', 'aze', 'aze', 'Azerbaijani', 'azərbaycan dili'),
        array('bm', 'bam', 'bam', 'bam', 'Bambara', 'bamanankan'),
        array('ba', 'bak', 'bak', 'bak', 'Bashkir', 'башҡорт теле'),
        array('eu', 'eus', 'baq', 'eus', 'Basque', 'euskara, euskera'),
        array('be', 'bel', 'bel', 'bel', 'Belarusian', 'беларуская мова'),
        array('bn', 'ben', 'ben', 'ben', 'Bengali, Bangla', 'বাংলা'),
        array('bh', 'bih', 'bih', '', 'Bihari', 'भोजपुरी'),
        array('bi', 'bis', 'bis', 'bis', 'Bislama', 'Bislama'),
        array('bs', 'bos', 'bos', 'bos', 'Bosnian', 'bosanski jezik'),
        array('br', 'bre', 'bre', 'bre', 'Breton', 'brezhoneg'),
        array('bg', 'bul', 'bul', 'bul', 'Bulgarian', 'български език'),
        array('my', 'mya', 'bur', 'mya', 'Burmese', 'ဗမာစာ'),
        array('ca', 'cat', 'cat', 'cat', 'Catalan', 'català'),
        array('ch', 'cha', 'cha', 'cha', 'Chamorro', 'Chamoru'),
        array('ce', 'che', 'che', 'che', 'Chechen', 'нохчийн мотт'),
        array('ny', 'nya', 'nya', 'nya', 'Chichewa, Chewa, Nyanja', 'chiCheŵa, chinyanja'),
        array('zh', 'zho', 'chi', 'zho', 'Chinese', '中文 (Zhōngwén), 汉语, 漢語'),
        array('cv', 'chv', 'chv', 'chv', 'Chuvash', 'чӑваш чӗлхи'),
        array('kw', 'cor', 'cor', 'cor', 'Cornish', 'Kernewek'),
        array('co', 'cos', 'cos', 'cos', 'Corsican', 'corsu, lingua corsa'),
        array('cr', 'cre', 'cre', 'cre', 'Cree', 'ᓀᐦᐃᔭᐍᐏᐣ'),
        array('hr', 'hrv', 'hrv', 'hrv', 'Croatian', 'hrvatski jezik'),
        array('cs', 'ces', 'cze', 'ces', 'Czech', 'čeština, český jazyk'),
        array('da', 'dan', 'dan', 'dan', 'Danish', 'dansk'),
        array('dv', 'div', 'div', 'div', 'Divehi, Dhivehi, Maldivian', 'ދިވެހި'),
        array('nl', 'nld', 'dut', 'nld', 'Dutch', 'Nederlands, Vlaams'),
        array('dz', 'dzo', 'dzo', 'dzo', 'Dzongkha', 'རྫོང་ཁ'),
        array('en', 'eng', 'eng', 'eng', 'English', 'English'),
        array('eo', 'epo', 'epo', 'epo', 'Esperanto', 'Esperanto'),
        array('et', 'est', 'est', 'est', 'Estonian', 'eesti, eesti keel'),
        array('ee', 'ewe', 'ewe', 'ewe', 'Ewe', 'Eʋegbe'),
        array('fo', 'fao', 'fao', 'fao', 'Faroese', 'føroyskt'),
        array('fj', 'fij', 'fij', 'fij', 'Fijian', 'vosa Vakaviti'),
        array('fi', 'fin', 'fin', 'fin', 'Finnish', 'suomi, suomen kieli'),
        array('fr', 'fra', 'fre', 'fra', 'French', 'français, langue française'),
        array('ff', 'ful', 'ful', 'ful', 'Fula, Fulah, Pulaar, Pular', 'Fulfulde, Pulaar, Pular'),
        array('gl', 'glg', 'glg', 'glg', 'Galician', 'galego'),
        array('ka', 'kat', 'geo', 'kat', 'Georgian', 'ქართული'),
        array('de', 'deu', 'ger', 'deu', 'German', 'Deutsch'),
        array('el', 'ell', 'gre', 'ell', 'Greek (modern)', 'ελληνικά'),
        array('gn', 'grn', 'grn', 'grn', 'Guaraní', 'Avañe\'ẽ'),
        array('gu', 'guj', 'guj', 'guj', 'Gujarati', 'ગુજરાતી'),
        array('ht', 'hat', 'hat', 'hat', 'Haitian, Haitian Creole', 'Kreyòl ayisyen'),
        array('ha', 'hau', 'hau', 'hau', 'Hausa', '(Hausa) هَوُسَ'),
        array('he', 'heb', 'heb', 'heb', 'Hebrew (modern)', 'עברית'),
        array('hz', 'her', 'her', 'her', 'Herero', 'Otjiherero'),
        array('hi', 'hin', 'hin', 'hin', 'Hindi', 'हिन्दी, हिंदी'),
        array('ho', 'hmo', 'hmo', 'hmo', 'Hiri Motu', 'Hiri Motu'),
        array('hu', 'hun', 'hun', 'hun', 'Hungarian', 'magyar'),
        array('ia', 'ina', 'ina', 'ina', 'Interlingua', 'Interlingua'),
        array('id', 'ind', 'ind', 'ind', 'Indonesian', 'Bahasa Indonesia'),
        array('ie', 'ile', 'ile', 'ile', 'Interlingue', 'Originally called Occidental; then Interlingue after WWII'),
        array('ga', 'gle', 'gle', 'gle', 'Irish', 'Gaeilge'),
        array('ig', 'ibo', 'ibo', 'ibo', 'Igbo', 'Asụsụ Igbo'),
        array('ik', 'ipk', 'ipk', 'ipk', 'Inupiaq', 'Iñupiaq, Iñupiatun'),
        array('io', 'ido', 'ido', 'ido', 'Ido', 'Ido'),
        array('is', 'isl', 'ice', 'isl', 'Icelandic', 'Íslenska'),
        array('it', 'ita', 'ita', 'ita', 'Italian', 'italiano'),
        array('iu', 'iku', 'iku', 'iku', 'Inuktitut', 'ᐃᓄᒃᑎᑐᑦ'),
        array('ja', 'jpn', 'jpn', 'jpn', 'Japanese', '日本語 (にほんご)'),
        array('jv', 'jav', 'jav', 'jav', 'Javanese', 'basa Jawa'),
        array('kl', 'kal', 'kal', 'kal', 'Kalaallisut, Greenlandic', 'kalaallisut, kalaallit oqaasii'),
        array('kn', 'kan', 'kan', 'kan', 'Kannada', 'ಕನ್ನಡ'),
        array('kr', 'kau', 'kau', 'kau', 'Kanuri', 'Kanuri'),
        array('ks', 'kas', 'kas', 'kas', 'Kashmiri', 'कश्मीरी, كشميري‎'),
        array('kk', 'kaz', 'kaz', 'kaz', 'Kazakh', 'қазақ тілі'),
        array('km', 'khm', 'khm', 'khm', 'Khmer', 'ខ្មែរ, ខេមរភាសា, ភាសាខ្មែរ'),
        array('ki', 'kik', 'kik', 'kik', 'Kikuyu, Gikuyu', 'Gĩkũyũ'),
        array('rw', 'kin', 'kin', 'kin', 'Kinyarwanda', 'Ikinyarwanda'),
        array('ky', 'kir', 'kir', 'kir', 'Kyrgyz', 'Кыргызча, Кыргыз тили'),
        array('kv', 'kom', 'kom', 'kom', 'Komi', 'коми кыв'),
        array('kg', 'kon', 'kon', 'kon', 'Kongo', 'Kikongo'),
        array('ko', 'kor', 'kor', 'kor', 'Korean', '한국어, 조선어'),
        array('ku', 'kur', 'kur', 'kur', 'Kurdish', 'Kurdî, كوردی‎'),
        array('kj', 'kua', 'kua', 'kua', 'Kwanyama, Kuanyama', 'Kuanyama'),
        array('la', 'lat', 'lat', 'lat', 'Latin', 'latine, lingua latina'),
        array('', '', '', 'lld', 'Ladin', 'ladin, lingua ladina'),
        array('lb', 'ltz', 'ltz', 'ltz', 'Luxembourgish, Letzeburgesch', 'Lëtzebuergesch'),
        array('lg', 'lug', 'lug', 'lug', 'Ganda', 'Luganda'),
        array('li', 'lim', 'lim', 'lim', 'Limburgish, Limburgan, Limburger', 'Limburgs'),
        array('ln', 'lin', 'lin', 'lin', 'Lingala', 'Lingála'),
        array('lo', 'lao', 'lao', 'lao', 'Lao', 'ພາສາລາວ'),
        array('lt', 'lit', 'lit', 'lit', 'Lithuanian', 'lietuvių kalba'),
        array('lu', 'lub', 'lub', 'lub', 'Luba-Katanga', 'Tshiluba'),
        array('lv', 'lav', 'lav', 'lav', 'Latvian', 'latviešu valoda'),
        array('gv', 'glv', 'glv', 'glv', 'Manx', 'Gaelg, Gailck'),
        array('mk', 'mkd', 'mac', 'mkd', 'Macedonian', 'македонски јазик'),
        array('mg', 'mlg', 'mlg', 'mlg', 'Malagasy', 'fiteny malagasy'),
        array('ms', 'msa', 'may', 'msa', 'Malay', 'bahasa Melayu, بهاس ملايو‎'),
        array('ml', 'mal', 'mal', 'mal', 'Malayalam', 'മലയാളം'),
        array('mt', 'mlt', 'mlt', 'mlt', 'Maltese', 'Malti'),
        array('mi', 'mri', 'mao', 'mri', 'Māori', 'te reo Māori'),
        array('mr', 'mar', 'mar', 'mar', 'Marathi (Marāṭhī)', 'मराठी'),
        array('mh', 'mah', 'mah', 'mah', 'Marshallese', 'Kajin M̧ajeļ'),
        array('mn', 'mon', 'mon', 'mon', 'Mongolian', 'монгол'),
        array('na', 'nau', 'nau', 'nau', 'Nauru', 'Ekakairũ Naoero'),
        array('nv', 'nav', 'nav', 'nav', 'Navajo, Navaho', 'Diné bizaad'),
        array('nd', 'nde', 'nde', 'nde', 'Northern Ndebele', 'isiNdebele'),
        array('ne', 'nep', 'nep', 'nep', 'Nepali', 'नेपाली'),
        array('ng', 'ndo', 'ndo', 'ndo', 'Ndonga', 'Owambo'),
        array('nb', 'nob', 'nob', 'nob', 'Norwegian Bokmål', 'Norsk bokmål'),
        array('nn', 'nno', 'nno', 'nno', 'Norwegian Nynorsk', 'Norsk nynorsk'),
        array('no', 'nor', 'nor', 'nor', 'Norwegian', 'Norsk'),
        array('ii', 'iii', 'iii', 'iii', 'Nuosu', 'ꆈꌠ꒿ Nuosuhxop'),
        array('nr', 'nbl', 'nbl', 'nbl', 'Southern Ndebele', 'isiNdebele'),
        array('oc', 'oci', 'oci', 'oci', 'Occitan', 'occitan, lenga d\'òc'),
        array('oj', 'oji', 'oji', 'oji', 'Ojibwe, Ojibwa', 'ᐊᓂᔑᓈᐯᒧᐎᓐ'),
        array('cu', 'chu', 'chu', 'chu', 'Old Church Slavonic, Church Slavonic, Old Bulgarian', 'ѩзыкъ словѣньскъ'),
        array('om', 'orm', 'orm', 'orm', 'Oromo', 'Afaan Oromoo'),
        array('or', 'ori', 'ori', 'ori', 'Oriya', 'ଓଡ଼ିଆ'),
        array('os', 'oss', 'oss', 'oss', 'Ossetian, Ossetic', 'ирон æвзаг'),
        array('pa', 'pan', 'pan', 'pan', 'Panjabi, Punjabi', 'ਪੰਜਾਬੀ, پنجابی‎'),
        array('pi', 'pli', 'pli', 'pli', 'Pāli', 'पाऴि'),
        array('fa', 'fas', 'per', 'fas', 'Persian (Farsi)', 'فارسی'),
        array('pl', 'pol', 'pol', 'pol', 'Polish', 'język polski, polszczyzna'),
        array('ps', 'pus', 'pus', 'pus', 'Pashto, Pushto', 'پښتو'),
        array('pt', 'por', 'por', 'por', 'Portuguese', 'português'),
        array('qu', 'que', 'que', 'que', 'Quechua', 'Runa Simi, Kichwa'),
        array('rm', 'roh', 'roh', 'roh', 'Romansh', 'rumantsch grischun'),
        array('rn', 'run', 'run', 'run', 'Kirundi', 'Ikirundi'),
        array('ro', 'ron', 'rum', 'ron', 'Romanian', 'limba română'),
        array('ru', 'rus', 'rus', 'rus', 'Russian', 'Русский'),
        array('sa', 'san', 'san', 'san', 'Sanskrit (Saṁskṛta)', 'संस्कृतम्'),
        array('sc', 'srd', 'srd', 'srd', 'Sardinian', 'sardu'),
        array('sd', 'snd', 'snd', 'snd', 'Sindhi', 'सिन्धी, سنڌي، سندھی‎'),
        array('se', 'sme', 'sme', 'sme', 'Northern Sami', 'Davvisámegiella'),
        array('sm', 'smo', 'smo', 'smo', 'Samoan', 'gagana fa\'a Samoa'),
        array('sg', 'sag', 'sag', 'sag', 'Sango', 'yângâ tî sängö'),
        array('sr', 'srp', 'srp', 'srp', 'Serbian', 'српски језик'),
        array('gd', 'gla', 'gla', 'gla', 'Scottish Gaelic, Gaelic', 'Gàidhlig'),
        array('sn', 'sna', 'sna', 'sna', 'Shona', 'chiShona'),
        array('si', 'sin', 'sin', 'sin', 'Sinhala, Sinhalese', 'සිංහල'),
        array('sk', 'slk', 'slo', 'slk', 'Slovak', 'slovenčina, slovenský jazyk'),
        array('sl', 'slv', 'slv', 'slv', 'Slovene', 'slovenski jezik, slovenščina'),
        array('so', 'som', 'som', 'som', 'Somali', 'Soomaaliga, af Soomaali'),
        array('st', 'sot', 'sot', 'sot', 'Southern Sotho', 'Sesotho'),
        array('es', 'spa', 'spa', 'spa', 'Spanish', 'español'),
        array('su', 'sun', 'sun', 'sun', 'Sundanese', 'Basa Sunda'),
        array('sw', 'swa', 'swa', 'swa', 'Swahili', 'Kiswahili'),
        array('ss', 'ssw', 'ssw', 'ssw', 'Swati', 'SiSwati'),
        array('sv', 'swe', 'swe', 'swe', 'Swedish', 'svenska'),
        array('ta', 'tam', 'tam', 'tam', 'Tamil', 'தமிழ்'),
        array('te', 'tel', 'tel', 'tel', 'Telugu', 'తెలుగు'),
        array('tg', 'tgk', 'tgk', 'tgk', 'Tajik', 'тоҷикӣ, toçikī, تاجیکی‎'),
        array('th', 'tha', 'tha', 'tha', 'Thai', 'ไทย'),
        array('ti', 'tir', 'tir', 'tir', 'Tigrinya', 'ትግርኛ'),
        array('bo', 'bod', 'tib', 'bod', 'Tibetan Standard, Tibetan, Central', 'བོད་ཡིག'),
        array('tk', 'tuk', 'tuk', 'tuk', 'Turkmen', 'Türkmen, Түркмен'),
        array('tl', 'tgl', 'tgl', 'tgl', 'Tagalog', 'Wikang Tagalog, ᜏᜒᜃᜅ᜔ ᜆᜄᜎᜓᜄ᜔'),
        array('tn', 'tsn', 'tsn', 'tsn', 'Tswana', 'Setswana'),
        array('to', 'ton', 'ton', 'ton', 'Tonga (Tonga Islands)', 'faka Tonga'),
        array('tr', 'tur', 'tur', 'tur', 'Turkish', 'Türkçe'),
        array('ts', 'tso', 'tso', 'tso', 'Tsonga', 'Xitsonga'),
        array('tt', 'tat', 'tat', 'tat', 'Tatar', 'татар теле, tatar tele'),
        array('tw', 'twi', 'twi', 'twi', 'Twi', 'Twi'),
        array('ty', 'tah', 'tah', 'tah', 'Tahitian', 'Reo Tahiti'),
        array('ug', 'uig', 'uig', 'uig', 'Uyghur', 'ئۇيغۇرچە‎, Uyghurche'),
        array('uk', 'ukr', 'ukr', 'ukr', 'Ukrainian', 'українська мова'),
        array('ur', 'urd', 'urd', 'urd', 'Urdu', 'اردو'),
        array('uz', 'uzb', 'uzb', 'uzb', 'Uzbek', 'Oʻzbek, Ўзбек, أۇزبېك‎'),
        array('ve', 'ven', 'ven', 'ven', 'Venda', 'Tshivenḓa'),
        array('vi', 'vie', 'vie', 'vie', 'Vietnamese', 'Việt Nam'),
        array('vo', 'vol', 'vol', 'vol', 'Volapük', 'Volapük'),
        array('wa', 'wln', 'wln', 'wln', 'Walloon', 'walon'),
        array('cy', 'cym', 'wel', 'cym', 'Welsh', 'Cymraeg'),
        array('wo', 'wol', 'wol', 'wol', 'Wolof', 'Wollof'),
        array('fy', 'fry', 'fry', 'fry', 'Western Frisian', 'Frysk'),
        array('xh', 'xho', 'xho', 'xho', 'Xhosa', 'isiXhosa'),
        array('yi', 'yid', 'yid', 'yid', 'Yiddish', 'ייִדיש'),
        array('yo', 'yor', 'yor', 'yor', 'Yoruba', 'Yorùbá'),
        array('za', 'zha', 'zha', 'zha', 'Zhuang, Chuang', 'Saɯ cueŋƅ, Saw cuengh'),
        array('zu', 'zul', 'zul', 'zul', 'Zulu', 'isiZulu'),
    );

    /*
     * doi prefixes
     *
     * @var array
     */
    protected $doiPrefix = array(
        'slub' => '10.25366',
        'ubl'  => '10.25367',
        'ubc'  => '10.25369',
    );

    public function attachmentAction()
    {

        $piVars = GeneralUtility::_GP('tx_dpf'); // get GET params from powermail

        $fedoraHost = $this->clientConfigurationManager->getFedoraHost();

        switch ($piVars['action']) {
            case 'mets':
                $path = rtrim('http://' . $fedoraHost,"/").'/fedora/objects/'.$piVars['qid'].'/methods/qucosa:SDef/getMETSDissemination?supplement=yes';
                break;

            case 'preview':

                $document = $this->documentRepository->findByUid($piVars['qid']);

                if ($document) {

                    $metsXml = $this->buildMetsXml($document);
                    $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                    return $metsXml;

                } else {

                    $this->response->setStatus(404);
                    return 'No such document';

                }

            case 'attachment':

                $qid = $piVars['qid'];

                $attachment = $piVars['attachment'];

                if (is_numeric($piVars['qid'])) {

                    // qid is local uid
                    $document = $this->documentRepository->findByUid($piVars['qid']);

                    $files = $document->getCurrentFileData();

                    foreach ($files['download'] as $id => $file) {

                        if ($file['id'] == $attachment) {

                            $path = $file['path'];

                            $contentType = $file['type'];

                            break;

                        }
                    }

                } else {

                    $path = rtrim('http://' . $fedoraHost, "/") . '/fedora/objects/' . $qid . '/datastreams/' . $attachment . '/content';

                }

                if (empty($path)) {
                    $this->response->setStatus(404);
                    return 'No file found';
                }

                break;

            case 'dataCite':

                $qid = $piVars['qid'];
                $source = explode(':', $qid);
                if($source[0] == 'qucosa') {

                    $path = rtrim('http://' . $fedoraHost,"/").'/fedora/objects/'.$piVars['qid'].'/methods/qucosa:SDef/getMETSDissemination?supplement=yes';
                    $metsXml = str_replace('&', '&amp;', file_get_contents($path));
                    $dataCiteXml = $this->buildDataCiteXml($metsXml);

                } elseif($document = $this->documentRepository->findByUid($piVars['qid'])) {

                    $metsXml = str_replace('&', '&amp;', $this->buildMetsXml($document));
                    $dataCiteXml = $this->buildDataCiteXml($metsXml);

                } else {

                    $this->response->setStatus(404);
                    return 'No such document';

                }
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadXML($dataCiteXml);
                $title = $dom->getElementsByTagName('title')[0];

                $filename = str_replace(' ', '_', $title->nodeValue);

                $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.DataCite.xml"');
                $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                return $dataCiteXml;

                break;

            default:

                $this->response->setStatus(404);

                return 'No such action';
        }

        // get remote header and set it before passtrough
        $headers = get_headers($path);

        if (FALSE === $headers) {
            $this->response->setStatus(500);
            return 'Error while fetching headers';
        }

        $contentDispFlag = false;
        $contentTypeFlag = false;

        foreach ($headers as $value) {

            if (FALSE !== stripos($value, 'Content-Disposition')) {
                header($value);
                $contentDispFlag = true;
                continue;
            }

            if (FALSE !== stripos($value, 'Content-Type')) {
                header($value);
                $contentTypeFlag = true;
                continue;
            }

            if (FALSE !== stripos($value, 'Content-Length')) {
                header($value);
                continue;
            }
        }

        if (!$contentDispFlag) {
            header('Content-Disposition: attachment');
        }

        if (!$contentTypeFlag) {
            header('Content-Type: ' . $contentType);
        }

        if ($stream = fopen($path, 'r')) {

            // close active session if any
            session_write_close();

            // stop output buffering
            ob_end_clean();

            fpassthru($stream);

            fclose($stream);

            // Hard exit PHP script to avoid sending TYPO3 framework HTTP artifacts
            exit;

        } else {
            $this->response->setStatus(500);
            return 'Error while opening stream';
        }

    }

    private function buildMetsXml($document)
    {

        $exporter = new \EWW\Dpf\Services\MetsExporter();
        $fileData = $document->getCurrentFileData();
        $exporter->setFileData($fileData);
        $exporter->setMods($document->getXmlData());
        $exporter->setSlubInfo($document->getSlubInfoData());

        if (empty($document->getObjectIdentifier())) {

            $exporter->setObjId($document->getUid());

        } else {

            $exporter->setObjId($document->getObjectIdentifier());

        }

        $exporter->buildMets();
        $metsXml = $exporter->getMetsData();

        return $metsXml;
    }

    private function buildDataCiteXml($metsXml)
    {

        $metsXml = simplexml_load_string($metsXml, NULL, NULL, "http://www.w3.org/2001/XMLSchema-instance");
        $metsXml->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
        $metsXml->registerXPathNamespace('slub', 'http://slub-dresden.de/');

        // doi
        $metsDoi = $metsXml->xpath("//mods:identifier[@type='qucosa:doi']");
        if(!empty($metsDoi)) {
            $dataCiteDoi = $metsDoi[0];
        } else {
            $ownerId = $this->clientConfigurationManager->getOwnerId();
            $dataCiteDoi = $this->doiPrefix[$ownerId] . '/' . date("Y") . ".xxxxx";
        }

        // creators
        $metsCreator = $metsXml->xpath("//mods:name[@type='personal']");
        $dataCiteCreator = array();
        foreach($metsCreator as $creator)
        {
            $creator->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
            $names       = array();
            $givenName   = $creator->xpath(".//mods:namePart[@type='given']");
            $familyName  = $creator->xpath(".//mods:namePart[@type='family']");
            $creatorName = $creator->xpath(".//mods:namePart[@type='displayForm']");
            if(empty($creatorName)) {
                if(!empty($givenName) && !empty($familyName)) {
                    $creatorName = "<creatorName>{$familyName[0]}, {$givenName[0]}</creatorName>";
                } else {
                    $creatorName = "";
                }
            } else {
                $creatorName = "<creatorName>{$creatorName[0]}</creatorName>";
            }
            $givenName  = (!empty($givenName)) ? "<givenName>{$givenName[0]}</givenName>" : "";
            $familyName = (!empty($familyName)) ? "<familyName>{$familyName[0]}</familyName>" : "";
            array_push($names, $creatorName, $givenName, $familyName);
            $names = implode("", $names);
            array_push($dataCiteCreator, "<creator>{$names}</creator>");
        };
        $dataCiteCreator = implode('', array_unique($dataCiteCreator));

        // title
        $metsTitle = $metsXml->xpath("//mods:titleInfo[@usage='primary']/mods:title");
        $dataCiteTitle = (!empty($metsTitle)) ? "<title>{$metsTitle[0]}</title>" : "";

        // subtitles
        $metsSubTitles = $metsXml->xpath("//mods:titleInfo[@usage='primary']/mods:subTitle");
        foreach($metsSubTitles as $title) {
            $dataCiteTitle .= (!empty($title)) ? "<title titleType=\"Subtitle\">{$title}</title>" : "";
        }

        // publisher
        $metsPublisher = $metsXml->xpath("//mods:name[@type='corporate'][@displayLabel='mapping-hack-other']/mods:namePart");
        $dataCitePublisher = (!empty($metsPublisher)) ? $metsPublisher[0] : "";

        // publication year
        $metsPublicationYear = $metsXml->xpath("//mods:originInfo[@eventType='publication']/mods:dateIssued");
        if(!empty($metsPublicationYear)) {
            $dataCitePublicationYear = $metsPublicationYear[0];
        } else {
            $metsPublicationYear = $metsXml->xpath("//mods:originInfo/mods:dateIssued");
            $dataCitePublicationYear = (!empty($metsPublicationYear)) ? $metsPublicationYear[0] : "";
        }
        if(strlen($dataCitePublicationYear) != 4) {
            $dataCitePublicationYear = substr($dataCitePublicationYear, 0, 4);
        }

        // subjects
        $metsSubjects = $metsXml->xpath("//mods:classification[@authority='z']");
        $dataCiteSubjects = '';
        foreach(GeneralUtility::trimExplode(',', $metsSubjects[0]) as $subject) {
            $dataCiteSubjects .= "<subject>{$subject}</subject>";
        }

        // language
        $metsLanguage = $metsXml->xpath("//mods:language/mods:languageTerm[@authority='iso639-2b'][@type='code']");
        foreach($this->languages as $language) {
            if($language[2] == $metsLanguage[0]) {
                $dataCiteLanguage = $language[0];
                break;
            }
        }

        // description
        $metsDescription = $metsXml->xpath("//mods:abstract[@type='summary']");
        $dataCiteDescription = (!empty($metsDescription)) ? "<description descriptionType=\"Abstract\">{$metsDescription[0]}</description>" : "";

        // resource type
        $slubResourceType = $metsXml->xpath("//slub:documentType");
        $dataCiteResourceType = (!empty($slubResourceType)) ? $slubResourceType[0] : "";

        $xml = simplexml_load_string(<<< XML
<?xml version="1.0" encoding="UTF-8"?>
<resource xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://datacite.org/schema/kernel-4" xsi:schemaLocation="http://datacite.org/schema/kernel-4 http://schema.datacite.org/meta/kernel-4/metadata.xsd">
    <identifier identifierType="DOI">{$dataCiteDoi}</identifier>
    <creators>{$dataCiteCreator}</creators>
    <titles>{$dataCiteTitle}</titles>
    <publisher>{$dataCitePublisher}</publisher>
    <publicationYear>{$dataCitePublicationYear}</publicationYear>
    <subjects>{$dataCiteSubjects}</subjects>
    <language>{$dataCiteLanguage}</language>
    <descriptions>{$dataCiteDescription}</descriptions>
    <resourceType resourceTypeGeneral="Text">{$dataCiteResourceType}</resourceType>
</resource>
XML
        );

        $dataCiteXml = new \DOMDocument('1.0', 'UTF-8');
        $dataCiteXml->preserveWhiteSpace = false;
        $dataCiteXml->formatOutput = true;
        $dataCiteXml->loadXML($xml->asXML());

        return($dataCiteXml->saveXML());
    }
}

