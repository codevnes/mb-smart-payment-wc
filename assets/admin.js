(function ($) {
    function req(action, data) {
        return $.post(mbsp_admin.ajax_url, $.extend({ action: 'mbsp_' + action, nonce: mbsp_admin.nonce }, data || {}));
    }

    function showLoading(element) {
        $(element).addClass('mbsp-loading').prop('disabled', true);
    }

    function hideLoading(element) {
        $(element).removeClass('mbsp-loading').prop('disabled', false);
    }

    function updateStatusIndicator(logged_in, expires) {
        var $indicator = $('#mbsp-status-indicator');
        var $text = $('#mbsp-status-text');
        
        if (logged_in) {
            $indicator.removeClass('logged-out').addClass('logged-in');
            $text.text(mbsp_admin.i18n.logged);
        } else {
            $indicator.removeClass('logged-in').addClass('logged-out');
            $text.text(mbsp_admin.i18n.not_logged);
        }
    }

    $(function () {
        // Login form
        $('#mbsp-login-form').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var user = $('#mb_user').val();
            var pass = $('#mb_pass').val();
            
            if (!user || !pass) {
                alert('Vui lòng nhập đầy đủ thông tin đăng nhập');
                return;
            }
            
            showLoading($btn);
            req('login', { user: user, pass: pass })
                .done(function () { 
                    location.reload(); 
                })
                .fail(function (r) { 
                    hideLoading($btn);
                    alert(r.responseJSON?.data || 'Đăng nhập thất bại'); 
                });
        });

        // Logout button
        $('#mbsp-logout').on('click', function (e) { 
            e.preventDefault(); 
            var $btn = $(this);
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                showLoading($btn);
                req('logout').done(() => location.reload());
            }
        });

        // Check status button
        $('#mbsp-check-status').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            showLoading($btn);
            
            req('status').done(function (res) {
                hideLoading($btn);
                updateStatusIndicator(res.logged_in, res.expires);
                
                if (res.logged_in) {
                    var remaining = Math.max(0, Math.floor((res.expires - Math.floor(Date.now() / 1000)) / 60));
                    alert('Trạng thái: Đã đăng nhập\nToken còn hiệu lực: ' + remaining + ' phút');
                } else {
                    alert('Trạng thái: Chưa đăng nhập hoặc token đã hết hạn');
                }
            }).fail(function (r) {
                hideLoading($btn);
                alert('Không thể kiểm tra trạng thái: ' + (r.responseJSON?.data || 'Lỗi kết nối'));
            });
        });

        // Auto check status on page load
        if ($('#mbsp-status-indicator').length) {
            req('status').done(function (res) {
                updateStatusIndicator(res.logged_in, res.expires);
            });
        }

        // Load transactions
        $('#mbsp-load-trans').on('click', function () {
            var $btn = $(this);
            var from = $('#mbsp-from').val();
            var to = $('#mbsp-to').val(); 
            var acc = $('#mbsp-acc').val();
            
            if (!acc) {
                alert('Vui lòng nhập số tài khoản');
                return;
            }
            
            showLoading($btn);
            $('#mbsp-trans-table tbody').html('<tr><td colspan="4"><div style="text-align:center;padding:20px;">Đang tải dữ liệu...</div></td></tr>');
            
            req('transactions', { from: from, to: to, account: acc })
                .done(function (res) {
                    hideLoading($btn);
                    var html = '';
                    
                    if (res.items && res.items.length > 0) {
                        res.items.forEach(function (r) { 
                            html += '<tr>';
                            html += '<td>' + (r.trans_id || r.transactionId || 'N/A') + '</td>';
                            html += '<td class="amount">' + (r.amount || r.creditAmount || '0') + '</td>';
                            html += '<td>' + (r.description || r.description || 'N/A') + '</td>';
                            html += '<td>' + (r.time || r.transactionDate || 'N/A') + '</td>';
                            html += '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="4" style="text-align:center;padding:20px;">Không có giao dịch nào trong khoảng thời gian này</td></tr>';
                    }
                    
                    $('#mbsp-trans-table tbody').html(html);
                })
                .fail(function (r) {
                    hideLoading($btn);
                    $('#mbsp-trans-table tbody').html('<tr><td colspan="4" style="text-align:center;padding:20px;color:#d63638;">Lỗi: ' + (r.responseJSON?.data || 'Không thể tải dữ liệu') + '</td></tr>');
                });
        });

        // Set default dates (last 7 days)
        var today = new Date();
        var lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
        
        $('#mbsp-to').val(today.toISOString().split('T')[0]);
        $('#mbsp-from').val(lastWeek.toISOString().split('T')[0]);
    });
})(jQuery);
