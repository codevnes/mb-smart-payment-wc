# MB Smart Payment WC

Plugin WooCommerce tích hợp thanh toán tự động qua API ngân hàng MB (MBBank) với **giao diện admin hiện đại sử dụng Vue.js và Element Plus**.

## Tính năng chính

- ✅ **Thanh toán tự động**: Tự động xác nhận thanh toán qua API MBBank
- ✅ **QR Code**: Tạo mã QR VietQR cho khách hàng
- ✅ **Giao diện admin hiện đại**: Vue.js 3 + Element Plus UI components
- ✅ **Kiểm tra trạng thái**: Theo dõi kết nối MBBank real-time
- ✅ **Lịch sử giao dịch**: Xem và quản lý giao dịch đã khớp
- ✅ **Tự động refresh token**: Duy trì kết nối liên tục
- ✅ **Email thông báo**: Gửi thông tin thanh toán qua email

## Cấu trúc thư mục

```
mb-smart-payment-wc/
├── assets/                          # Tài nguyên frontend
│   ├── vue-modern.js                # Vue.js 3 components với Element Plus
│   ├── element-theme.css            # Custom Element Plus theme
│   ├── frontend.js                  # Frontend JavaScript cho checkout
│   └── mbbank.svg                   # Icon MBBank
├── includes/                        # Code chính
│   ├── class-mbspwc-admin.php       # Giao diện admin chính
│   ├── class-mbspwc-ajax.php        # Xử lý AJAX requests
│   ├── class-mbspwc-backend.php     # Kết nối Backend Node.js
│   ├── class-mbspwc-cron.php        # Tự động hóa (đã tối ưu)
│   ├── class-mbspwc-db.php          # Quản lý database
│   ├── class-mbspwc-gateway.php     # Payment Gateway (đã cải tiến)
│   ├── class-mbspwc-settings.php    # Cài đặt plugin
│   ├── class-mbspwc-transactions.php # Quản lý giao dịch
│   └── class-mbspwc-vietqr.php      # Tạo QR Code
├── mb-smart-payment-wc.php          # File plugin chính
└── uninstall.php                    # Cleanup khi gỡ plugin
```

## Cải tiến mới

### 🎨 Giao diện Admin (Vue.js + Element Plus)
- **Vue.js 3** với Composition API và reactive data
- **Element Plus** UI components chuyên nghiệp
- Dashboard hiện đại với animated statistics
- Professional form components với validation
- Toast notifications và modal dialogs
- Responsive design và dark mode support
- Smooth animations và transitions

### 🔧 Tính năng mới
- **Kiểm tra trạng thái**: Button kiểm tra kết nối MBBank
- **Tự động điền số TK**: Tự động lấy số tài khoản từ cài đặt
- **Hiển thị QR**: QR code hiển thị đầy đủ trên trang thank you
- **Email instructions**: Thông tin thanh toán trong email
- **Cải tiến cron**: Sử dụng backend thay vì mock API

### 🛡️ Bảo mật & Hiệu suất
- Kiểm tra duplicate transaction
- Validate payment method
- Auto refresh token
- Better error handling
- Cleanup khi uninstall

## Hướng dẫn sử dụng

### 1. Cài đặt
1. Upload plugin vào `/wp-content/plugins/`
2. Kích hoạt plugin trong WordPress Admin
3. Đảm bảo WooCommerce đã được cài đặt

### 2. Cấu hình
1. Vào **MBSP → Cài đặt**
2. Nhập thông tin:
   - Tên đăng nhập MBBank
   - Mật khẩu MBBank  
   - Số tài khoản
   - Tên chủ tài khoản

### 3. Đăng nhập MBBank
1. Vào **MBSP → Trạng thái**
2. Nhập thông tin đăng nhập
3. Kiểm tra trạng thái kết nối

### 4. Xem giao dịch
1. Vào **MBSP → Giao dịch**
2. Chọn khoảng thời gian
3. Nhấn "Tải giao dịch"

## Yêu cầu hệ thống

- **WordPress**: 6.0+
- **WooCommerce**: 5.0+
- **PHP**: 7.4+
- **Backend API**: Node.js server tại `localhost:3005`

## API Backend

Plugin kết nối với backend Node.js qua các endpoint:

```
POST /api/auth/login     # Đăng nhập MBBank
POST /api/auth/refresh   # Refresh token
POST /api/auth/logout    # Đăng xuất
GET  /api/auth/status    # Kiểm tra trạng thái
GET  /api/mbbank/transactions # Lấy giao dịch
```

## Database

Bảng `wp_mbspwc_transactions` lưu trữ:
- `id`: ID tự tăng
- `order_id`: ID đơn hàng WooCommerce
- `trans_id`: Mã giao dịch ngân hàng
- `amount`: Số tiền
- `status`: Trạng thái (matched, pending)
- `raw`: Dữ liệu giao dịch đầy đủ
- `created`: Thời gian tạo

## Luồng thanh toán

1. **Khách hàng đặt hàng** → Chọn "MBBank Smart Payment"
2. **Hệ thống tạo QR** → Hiển thị thông tin chuyển khoản
3. **Khách hàng chuyển khoản** → Theo thông tin QR
4. **Cron tự động kiểm tra** → Mỗi phút lấy giao dịch mới
5. **Khớp đơn hàng** → Dựa trên nội dung "ORDER-{ID}"
6. **Cập nhật trạng thái** → Đơn hàng thành "completed"

## Troubleshooting

### Không hiển thị QR
- Kiểm tra cài đặt số tài khoản
- Xem log trong WooCommerce → Status → Logs

### Không tự động xác nhận
- Kiểm tra cron WordPress: `wp cron event list`
- Xem trạng thái đăng nhập MBBank
- Kiểm tra backend API có chạy không

### Lỗi kết nối backend
- Đảm bảo backend chạy tại `localhost:3005`
- Kiểm tra firewall/proxy settings

## Changelog

### v1.1.0 (Optimized)
- ✅ Removed unused MBSPWC_API class
- ✅ Fixed QR display on thank you page
- ✅ Enhanced admin UI with modern styles
- ✅ Added status check functionality
- ✅ Auto-fill account number in transactions
- ✅ Improved cron with backend integration
- ✅ Better error handling and validation
- ✅ Added email instructions
- ✅ Enhanced security and performance
