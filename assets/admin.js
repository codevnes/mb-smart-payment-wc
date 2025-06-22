(function ($) {
    function req(action, data) {
        return $.post(mbsp_admin.ajax_url, $.extend({ action: 'mbsp_' + action, nonce: mbsp_admin.nonce }, data || {}));
    }

    $(function () {
        // Login form
        $('#mbsp-login-form').on('submit', function (e) {
            e.preventDefault();
            var user = $('#mb_user').val(), pass = $('#mb_pass').val();
            req('login', { user: user, pass: pass }).done(function () { location.reload(); }).fail(function (r) { alert(r.responseJSON?.data || 'Error'); });
        });
        // Logout button
        $('#mbsp-logout').on('click', function (e) { e.preventDefault(); req('logout').done(() => location.reload()); });
        // Status auto
        if ($('#mbsp-status').length) {
            req('status').done(function (res) { $('#mbsp-status').text(res.logged_in ? mbsp_admin.i18n.logged : mbsp_admin.i18n.not_logged); });
        }
        // Load transactions
        $('#mbsp-load-trans').on('click', function () {
            var from = $('#mbsp-from').val(), to = $('#mbsp-to').val(), acc = $('#mbsp-acc').val();
            $('#mbsp-trans-table tbody').html('<tr><td colspan="4">Loading...</td></tr>');
            req('transactions', { from: from, to: to, account: acc }).done(function (res) {
                var html = '';
                res.items.forEach(function (r) { html += '<tr><td>' + r.trans_id + '</td><td>' + r.amount + '</td><td>' + r.description + '</td><td>' + r.time + '</td></tr>'; });
                $('#mbsp-trans-table tbody').html(html || '<tr><td colspan="4">No data</td></tr>');
            }).fail(function (r) { alert(r.responseJSON?.data || 'Error'); });
        });
    });
})(jQuery);
