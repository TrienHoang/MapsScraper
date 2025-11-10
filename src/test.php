<?php

require_once 'src/GoogleMapsScraper.php';

$scraper = new GoogleMapsScraper();

// Test 1: Danh sách
// $data = $scraper->scrape('trung tâm thương mại Hà Nội', 10);
// print_r($data);

// // Test 2: Địa chỉ cụ thể
// $data = $scraper->scrape('bảo tàng hà nội');
// print_r($data);

// // Test 3: Không tìm thấy
// $data = $scraper->scrape('abc xyz không tồn tại');
// echo "Kết quả: " . count($data) . " bản ghi\n"; // → 0