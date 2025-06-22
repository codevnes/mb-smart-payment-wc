(function($) {
    'use strict';

    // Copy to clipboard function
    window.copyToClipboard = function(text) {
        if (navigator.clipboard && window.isSecureContext) {
            // Use modern clipboard API
            navigator.clipboard.writeText(text).then(function() {
                showCopyNotification();
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyTextToClipboard(text);
        }
    };

    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showCopyNotification();
            }
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }

        document.body.removeChild(textArea);
    }

    function showCopyNotification() {
        var notification = $('#mbsp-copy-notification');
        if (notification.length) {
            notification.addClass('show');
            setTimeout(function() {
                notification.removeClass('show');
            }, 2000);
        }
    }

    function showLoading($btn) {
        var $text = $btn.find('.text');
        $text.html('<span class="spinner"></span> Đang kiểm tra...');
        $btn.prop('disabled', true);
    }

    function hideLoading($btn, originalText) {
        var $text = $btn.find('.text');
        $text.html(originalText || 'Kiểm tra thanh toán');
        $btn.prop('disabled', false);
    }

    function updatePaymentStatus(data) {
        var $statusIndicator = $('#mbsp-payment-status');
        if ($statusIndicator.length) {
            $statusIndicator
                .removeClass('mbsp-status-pending mbsp-status-completed mbsp-status-failed')
                .addClass(data.status_class)
                .text(data.status_text);
        }

        // If payment is completed, show success message
        if (data.is_paid) {
            setTimeout(function() {
                if (confirm('Thanh toán đã được xác nhận!\n\nBạn có muốn tải lại trang để xem cập nhật mới nhất?')) {
                    location.reload();
                }
            }, 1000);
        }
    }

    $(document).ready(function() {
        // Check payment button
        $('#mbsp-check-payment').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var orderId = $btn.data('order-id');
            var originalText = $btn.find('.text').html();
            
            if (!orderId) {
                alert('Không tìm thấy ID đơn hàng');
                return;
            }

            showLoading($btn);

            $.ajax({
                url: mbsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mbsp_check_payment',
                    order_id: orderId,
                    nonce: mbsp_ajax.nonce
                },
                timeout: 15000
            })
            .done(function(response) {
                hideLoading($btn, originalText);
                
                if (response.success && response.data) {
                    updatePaymentStatus(response.data);
                    
                    if (response.data.is_paid) {
                        // Payment completed
                        $btn.find('.text').html('Đã thanh toán');
                        $btn.removeClass('mbsp-btn-primary').addClass('mbsp-btn-secondary');
                    } else {
                        // Still pending
                        var message = 'Trạng thái: ' + response.data.status_text + '\n\n';
                        message += 'Số tiền: ' + response.data.order_total + ' VND\n';
                        message += 'Ngày đặt: ' + response.data.order_date;
                        alert(message);
                    }
                } else {
                    alert('Lỗi: ' + (response.data || 'Không thể kiểm tra trạng thái thanh toán'));
                }
            })
            .fail(function(xhr, status, error) {
                hideLoading($btn, originalText);
                
                var errorMsg = 'Lỗi kết nối';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                } else if (status === 'timeout') {
                    errorMsg = 'Hết thời gian chờ. Vui lòng thử lại.';
                }
                
                alert(errorMsg);
            });
        });

        // Auto-refresh payment status every 30 seconds if still pending
        var autoRefreshInterval;
        
        function startAutoRefresh() {
            var $statusIndicator = $('#mbsp-payment-status');
            var $checkBtn = $('#mbsp-check-payment');
            
            if ($statusIndicator.hasClass('mbsp-status-pending') && $checkBtn.length) {
                autoRefreshInterval = setInterval(function() {
                    $checkBtn.trigger('click');
                }, 30000); // 30 seconds
            }
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }

        // Start auto-refresh if payment is pending
        startAutoRefresh();

        // Stop auto-refresh when payment is completed
        $(document).on('payment-completed', function() {
            stopAutoRefresh();
        });

        // Stop auto-refresh when user leaves the page
        $(window).on('beforeunload', function() {
            stopAutoRefresh();
        });

        // Add hover effects for copy functionality
        $('.mbsp-payment-item .value').on('mouseenter', function() {
            $(this).attr('title', 'Nhấn để sao chép');
        });

        // Add visual feedback when copying
        $('.mbsp-payment-item .value').on('click', function() {
            var $this = $(this);
            var originalBg = $this.css('background-color');
            
            $this.css('background-color', 'rgba(76, 175, 80, 0.3)');
            setTimeout(function() {
                $this.css('background-color', originalBg);
            }, 300);
        });
    });

})(jQuery);