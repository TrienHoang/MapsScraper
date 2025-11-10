# Google Maps Scraper (PHP + Panther)

**Scrape Google Maps → Trả về mảng dữ liệu**  
Không lưu file, không echo, không lỗi nghiêm trọng – Dễ tích hợp vào bất kỳ dự án nào.

---

## 1. Yêu cầu hệ thống

| Yêu cầu | Phiên bản |
|--------|---------|
| PHP | `>= 8.1` |
| Composer | `2.x` |
| Google Chrome | `>= 131` |
| RAM | `>= 4GB` |
| Hệ điều hành | Windows / Linux / macOS |

---

## 2. Cài đặt

### Bước 1: Clone dự án

```bash
git clone https://github.com/TrienHoang/MapsScraper.git
cd mapsgg/maps-scraper
```

### Bước 2: Cài dependencies


```bash
composer install
```

Nếu lỗi symfony/panther, chạy:

``` bash
composer require --dev symfony/panther
```

### Bước 3: Tải ChromeDriver

1. Truy cập: https://googlechromelabs.github.io/chrome-for-testing/

2. Tìm phiên bản Chrome của bạn → Tải file:

        Windows: chromedriver.exe
        Linux/macOS: chromedriver

3. Đặt vào: .../maps-scraper/chromedriver.exe

### Bước 4: Cách dùng

Tạo file: 

        test.php

Nhúng file:

        require_once 'src/GoogleMapsScraper.php';
 
Gọi hàm: 

        GoogleMapsScraper();

Chạy dự án: 

        php test.php


### Các lưu ý

| Lưu ý | Mô tả |
|--------|---------|
| Giới hạn 50 kết quả/lần | Dễ bị Google phát hiện |
| Delay tự nhiên 3-7 giây | Giống hành vi của người thật |
| Không chạy 24/7 | Không nên chạy liên tục vì dễ bị Block |
| Hệ điều hành | Windows / Linux / macOS |
