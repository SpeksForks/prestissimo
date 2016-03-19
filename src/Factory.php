<?php
/*
 * hirak/prestissimo
 * @author Hiraku NAKANO
 * @license MIT https://github.com/hirak/prestissimo
 */
namespace Hirak\Prestissimo;

use Composer\IO;
use Composer\Config as CConfig;

/**
 * cache manager for curl handler
 *
 * Singleton
 */
final class Factory
{
    /**
     * @param string $origin domain text
     * @param string $url
     * @param IO\IOInterface $io
     * @param CConfig $config
     * @param array $pluginConfig
     * @return Aspects\HttpGetRequest
     */
    public static function getHttpGetRequest($origin, $url, IO\IOInterface $io, CConfig $config, array $pluginConfig)
    {
        if (substr($origin, -10) === 'github.com') {
            $origin = 'github.com';
        }
        $requestClass = __NAMESPACE__ . '\Aspects\\' . self::getRequestClass($origin, $config) . 'Request';
        $request = new $requestClass($origin, $url, $io);
        $request->verbose = $pluginConfig['verbose'];
        if ($pluginConfig['insecure']) {
            $request->curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
        }
        if (!empty($pluginConfig['cainfo'])) {
            $request->curlOpts[CURLOPT_CAINFO] = $pluginConfig['cainfo'];
        }
        if (!empty($pluginConfig['userAgent'])) {
            $request->curlOpts[CURLOPT_USERAGENT] = $pluginConfig['userAgent'];
        }
        return $request;
    }

    private static function getRequestClass($origin, $config)
    {
        if (in_array($origin, $config->get('github-domains') ?: array())) {
            return 'GitHub';
        }
        if (in_array($origin, $config->get('gitlab-domains') ?: array())) {
            return 'GitLab';
        }
        return 'HttpGet';
    }

    /**
     * @return Aspects\JoinPoint
     */
    public static function getPreEvent(Aspects\HttpGetRequest $req)
    {
        $pre = new Aspects\JoinPoint('pre-download', $req);
        $pre->attach(new Aspects\AspectProxy);
        return $pre;
    }

    /**
     * @return Aspects\JoinPoint
     */
    public static function getPostEvent(Aspects\HttpGetRequest $req)
    {
        $post = new Aspects\JoinPoint('post-download', $req);
        return $post;
    }
}
