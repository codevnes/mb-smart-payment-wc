# MB Smart Payment WC

Plugin WooCommerce tích hợp thanh toán tự động qua API ngân hàng MB (MBBank).

## Cấu trúc thư mục

```
mb-smart-payment-wc/
├── assets/                 # Icon, hình ảnh
│   └── mbbank.svg
├── includes/               # Code chính (chia theo class)
│   ├── class-mbspwc-api.php        # Mock API MBBank (login, lấy giao dịch)
│   ├── class-mbspwc-cron.php       # Lịch cron kiểm tra giao dịch & khớp đơn
│   ├── class-mbspwc-db.php         # Tạo và thao tác bảng lưu giao dịch
│   ├── class-mbspwc-gateway.php    # Định nghĩa gateway WooCommerce
│   ├── class-mbspwc-settings.php   # Trang cài đặt trong wp-admin
│   └── class-mbspwc-vietqr.php     # Sinh QR trung gian qua VietQR
├── languages/              # File dịch (nếu có)
├── mb-smart-payment-wc.php # File plugin chính (bootstrap)
└── uninstall.php           # Xoá option, bảng khi gỡ plugin
```

## Lưu ý
- API MBBank và VietQR hiện chỉ mock. Thay thế code trong `class-mbspwc-api.php` và `class-mbspwc-vietqr.php` bằng implementation thật.
- Bảng `wp_mbspwc_transactions` (prefix tuỳ site) lưu toàn bộ giao dịch đã match đơn:
  * `order_id`, `trans_id`, `amount`, `status`, `raw`, `created`.
- Cron chạy mỗi phút (`wp-cron`) để lấy giao dịch, làm mới token, đối soát đơn.
- Trang cài đặt: WooCommerce → MB Smart Payment.
