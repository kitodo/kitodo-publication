<?php
namespace EWW\Dpf\Services;

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
 * Fetches METS XML for a Fedora PID via Redis cache or live Fedora SDef.
 *
 * Extracted from GetFileController::metsAction() to allow direct PHP calls
 * without routing through the HTTP self-loop.
 */
class MetsService
{
    /**
     * @var string
     */
    private $fedoraHost;

    /**
     * @var string
     */
    private $redisHost;

    /**
     * @var int
     */
    private $redisPort;

    /**
     * @var int
     */
    private $redisDb;

    /**
     * @var float
     */
    private $redisTimeout;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var int
     */
    private $fedoraTimeout;

    /**
     * @param array $settings Keys: fedoraHost, redisHost, redisPort, redisDatabase,
     *                        redisConnectTimeout, metsCacheTtl — same as GetFileController settings.
     */
    public function __construct(array $settings)
    {
        $this->fedoraHost = isset($settings['fedoraHost']) ? trim($settings['fedoraHost']) : '';

        $this->redisHost = (isset($settings['redisHost']) && $settings['redisHost'] !== '')
            ? $settings['redisHost'] : '127.0.0.1';

        $this->redisPort = (isset($settings['redisPort']) && (int)$settings['redisPort'] > 0)
            ? (int)$settings['redisPort'] : 6379;

        $this->redisDb = isset($settings['redisDatabase'])
            ? (int)$settings['redisDatabase'] : 4;

        $this->redisTimeout = (isset($settings['redisConnectTimeout']) && $settings['redisConnectTimeout'] !== '')
            ? (float)$settings['redisConnectTimeout'] : 1.0;

        $this->ttl = (isset($settings['metsCacheTtl']) && (int)$settings['metsCacheTtl'] > 0)
            ? (int)$settings['metsCacheTtl'] : 86400;

        $this->fedoraTimeout = (isset($settings['fedoraFetchTimeout']) && (int)$settings['fedoraFetchTimeout'] > 0)
            ? (int)$settings['fedoraFetchTimeout'] : 90;
    }

    /**
     * Return METS XML for the given Fedora PID, or null on failure.
     *
     * Checks Redis db4 first (key "mets:{pid}"); on miss fetches from
     * Fedora SDef with supplement=yes and writes back to cache.
     *
     * @param string $pid Fedora PID, e.g. "qucosa:12345"
     * @return string|null
     */
    public function getXml(string $pid)
    {
        $cacheKey = 'mets:' . $pid;

        try {
            $redis = new \Redis();
            if ($redis->connect($this->redisHost, $this->redisPort, $this->redisTimeout)) {
                $redis->select($this->redisDb);
                $cached = $redis->get($cacheKey);
                if ($cached !== false) {
                    return $cached;
                }
            }
        } catch (\Throwable $e) {
            $redis = null;
        }

        $fedoraUrl = rtrim('http://' . $this->fedoraHost, '/')
            . '/fedora/objects/' . rawurlencode($pid)
            . '/methods/qucosa:SDef/getMETSDissemination?supplement=yes';

        $ctx = stream_context_create(['http' => ['timeout' => $this->fedoraTimeout]]);
        $xml = @file_get_contents($fedoraUrl, false, $ctx);
        if ($xml === false) {
            return null;
        }

        try {
            if ($redis instanceof \Redis && $redis->isConnected()) {
                $redis->set($cacheKey, $xml, $this->ttl);
            }
        } catch (\Throwable $e) {
            // cache write failure is non-fatal
        }

        return $xml;
    }

    /**
     * Read plugin settings from TYPO3 globals for use in static contexts.
     *
     * Priority: TypoScript plugin.tx_dpf.settings.* → extConf dpf fallback.
     *
     * @return array
     */
    public static function readSettings(): array
    {
        $settings = [];

        if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.']['settings.'])
            && is_array($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.']['settings.'])
        ) {
            $settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dpf.']['settings.'];
        }

        if (empty($settings['fedoraHost'])
            && isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf'])
        ) {
            $extConf = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
            if (is_array($extConf) && !empty($extConf['fedoraHost'])) {
                $settings['fedoraHost'] = $extConf['fedoraHost'];
            }
        }

        return $settings;
    }
}
