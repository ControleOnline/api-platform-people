<?php

namespace ControleOnline\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AdsenseSiteChecker
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function check(string $domain): array
    {
        $baseUrl = $this->normalizeUrl($domain);
        $homepage = $this->request($baseUrl);
        $adsTxt = $this->request($baseUrl . '/ads.txt');
        $html = (string) ($homepage['body'] ?? '');
        $adsTxtBody = (string) ($adsTxt['body'] ?? '');
        $hasAdsenseCode = (bool) preg_match(
            '/(?:adsbygoogle|googlesyndication|ca-pub-[0-9]+)/i',
            $html
        );
        $adsTxtAuthorized = (bool) preg_match(
            '/^\s*google\.com\s*,\s*pub-[0-9]+\s*,\s*(?:DIRECT|RESELLER)\s*(?:,\s*[^\s]+)?\s*$/im',
            $adsTxtBody
        );

        return [
            'domain' => $domain,
            'checkedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'http' => [
                'status' => $homepage['status'],
                'reachable' => $homepage['status'] >= 200 && $homepage['status'] < 400,
            ],
            'adsenseCode' => [
                'found' => $hasAdsenseCode,
            ],
            'adsTxt' => [
                'status' => $adsTxt['status'],
                'reachable' => $adsTxt['status'] === 200,
                'authorized' => $adsTxtAuthorized,
            ],
            'issues' => array_values(array_filter([
                $homepage['status'] < 200 || $homepage['status'] >= 400 ? 'site_unreachable' : null,
                !$hasAdsenseCode ? 'adsense_code_not_found' : null,
                !$adsTxtAuthorized ? 'ads_txt_missing_or_unauthorized' : null,
            ])),
        ];
    }

    private function request(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'ControleOnline-AdSenseChecker/1.0',
                    'Accept' => 'text/html,text/plain,*/*',
                ],
                'max_duration' => 8,
                'timeout' => 6,
                'max_redirects' => 5,
            ]);

            return [
                'status' => $response->getStatusCode(),
                'body' => substr($response->getContent(false), 0, 2_000_000),
            ];
        } catch (\Throwable) {
            return ['status' => 0, 'body' => ''];
        }
    }

    private function normalizeUrl(string $domain): string
    {
        $value = trim($domain);
        if (!preg_match('/^https?:\/\//i', $value)) {
            $value = 'https://' . $value;
        }

        return rtrim($value, '/');
    }
}
