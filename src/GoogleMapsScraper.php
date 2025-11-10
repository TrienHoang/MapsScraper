<?php
// src/GoogleMapsScraper.php

require_once __DIR__ . '/../vendor/autoload.php';
use Symfony\Component\Panther\Client;

class GoogleMapsScraper
{
    private $driverPath;
    private $client;

    public function __construct()
    {
        $this->driverPath =  __DIR__ . '/../chromedriver.exe';
        if (!file_exists($this->driverPath)) {
            throw new Exception("Không tìm thấy chromedriver.exe tại: " . $this->driverPath);
        }
    }

    /**
     * Scrape Google Maps và trả về mảng dữ liệu
     * @param string $query Tìm kiếm (vd: "cửa hàng tiện lợi Hà Nội")
     * @param int $maxResults Số kết quả tối đa
     * @return array Mảng dữ liệu: [place_id, name, address, website, phone, url, scraped_at]
     */
    public function scrape($query, $maxResults = 50)
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0',
        ];
        $ua = $userAgents[array_rand($userAgents)];

        $this->client = Client::createChromeClient($this->driverPath, [
            '--no-sandbox',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--start-maximized',
            '--window-size=1920,1080',
            '--user-agent=' . $ua,
            '--disable-blink-features=AutomationControlled',
            '--enable-features=NetworkService,NetworkServiceInProcess',
        ]);

        $data = [];

        try {
            $this->client->request('GET', 'https://www.google.com/maps');
            $this->random_sleep(3, 6);

            $this->client->waitFor('#searchboxinput', 15);
            $input = $this->client->getCrawler()->filter('#searchboxinput');
            $input->sendKeys($query . PHP_EOL);

            $this->random_sleep(5, 8);

            $crawler = $this->client->getCrawler();

            // === XỬ LÝ CẢ 2 TRƯỜNG HỢP ===
            $hasList = $crawler->filter('a.hfpxzc')->count() > 0;
            $isDetail = $crawler->filter('h1')->count() > 0 && $crawler->filter('button[data-item-id="address"]')->count() > 0;

            if ($isDetail && !$hasList) {
                // TRANG CHI TIẾT
                $data[] = $this->extractDetailPage($this->client, $crawler);
            } elseif ($hasList) {
                // DANH SÁCH
                $data = $this->extractListPage($this->client, $maxResults);
            }
            // else: Không tìm thấy → trả về mảng rỗng

        } catch (Exception $e) {
            // Không ném lỗi ra ngoài → chỉ trả về mảng rỗng
            error_log("GoogleMapsScraper Error: " . $e->getMessage());
        } finally {
            if ($this->client) {
                $this->client->quit();
            }
        }

        return $data;
    }

    private function extractDetailPage($client, $crawler)
    {
        $url = $client->getCurrentURL();
        return [
            'place_id'   => $this->extract_place_id($url) ?: 'single_result',
            'name'       => trim($crawler->filter('h1')->text()),
            'address'    => $crawler->filter('button[data-item-id="address"] .Io6YTe')->count() ? $crawler->filter('button[data-item-id="address"] .Io6YTe')->text() : '',
            'website'    => $crawler->filter('a[data-item-id="authority"]')->count() ? $crawler->filter('a[data-item-id="authority"]')->attr('href') : '',
            'phone'      => $crawler->filter('button[data-item-id*="phone"] .Io6YTe')->count() ? $crawler->filter('button[data-item-id*="phone"] .Io6YTe')->text() : '',
            'url'        => $url,
            'scraped_at' => date('Y-m-d H:i:s')
        ];
    }

    private function extractListPage($client, $max)
    {
        // Scroll load thêm
        for ($i = 0; $i < 6; $i++) {
            $client->executeScript('
                const results = document.querySelectorAll("a.hfpxzc");
                if (results.length > 0) {
                    const container = results[0].closest("div[jsaction]") || document.body;
                    container.scrollTop = container.scrollHeight;
                }
            ');
            $this->random_sleep(3, 7);
        }

        $crawler = $client->getCrawler();
        $links = $crawler->filter('a.hfpxzc');
        if ($links->count() > $max) $links = $links->slice(0, $max);

        $data = [];
        $links->each(function ($link, $idx) use ($client, &$data, $max) {
            if ($idx >= $max) return;
            $url = $link->attr('href');
            $placeId = $this->extract_place_id($url) ?: "item_$idx";

            try {
                $client->request('GET', $url);
                $client->waitFor('h1', 15);
                $this->random_sleep(2, 5);

                $c = $client->getCrawler();
                $name = $c->filter('h1')->count() ? trim($c->filter('h1')->text()) : '';
                if (!$name) return;

                $data[] = [
                    'place_id'   => $placeId,
                    'name'       => $name,
                    'address'    => $c->filter('button[data-item-id="address"] .Io6YTe')->count() ? $c->filter('button[data-item-id="address"] .Io6YTe')->text() : '',
                    'website'    => $c->filter('a[data-item-id="authority"]')->count() ? $c->filter('a[data-item-id="authority"]')->attr('href') : '',
                    'phone'      => $c->filter('button[data-item-id*="phone"] .Io6YTe')->count() ? $c->filter('button[data-item-id*="phone"] .Io6YTe')->text() : '',
                    'url'        => $url,
                    'scraped_at' => date('Y-m-d H:i:s')
                ];

                $client->back();
                $this->random_sleep(2, 6);

            } catch (Exception $e) {
                // Bỏ qua lỗi chi tiết
            }
        });

        return $data;
    }

    private function extract_place_id($url)
    {
        if (preg_match('/16s%2Fg%2F([a-zA-Z0-9]+)/', $url, $m)) return $m[1];
        if (preg_match('/0x[a-f0-9]+:0x[a-f0-9]+/', $url, $m)) return $m[0];
        return null;
    }

    private function random_sleep($min, $max)
    {
        $sec = rand($min, $max);
        sleep($sec);
    }
}