/**
 * Vue.js Components for MB Smart Payment Admin
 */

// Main Admin App Component
const AdminApp = {
    data() {
        return {
            currentPage: 'status',
            loading: false,
            statusData: {
                logged_in: false,
                expires: 0,
                token_remaining: 0
            },
            loginForm: {
                username: '',
                password: '',
                loading: false
            },
            settingsForm: {
                user: '',
                pass: '',
                acc_no: '',
                acc_name: '',
                loading: false
            },
            transactionData: {
                stats: {
                    total_orders: 0,
                    completed_orders: 0,
                    pending_orders: 0,
                    total_amount: 0
                },
                filters: {
                    from: '',
                    to: '',
                    account: ''
                },
                apiTransactions: [],
                orders: [],
                loading: false
            }
        }
    },
    mounted() {
        this.initializeData();
        this.checkStatus();
    },
    methods: {
        initializeData() {
            // Load settings from server
            this.loadSettings();
            this.loadTransactionData();
        },
        
        async checkStatus() {
            try {
                const response = await this.apiRequest('status');
                this.statusData = response;
                this.updateTokenRemaining();
            } catch (error) {
                console.error('Status check failed:', error);
            }
        },
        
        async login() {
            this.loginForm.loading = true;
            try {
                const response = await this.apiRequest('login', {
                    user: this.loginForm.username,
                    pass: this.loginForm.password
                });
                
                if (response.success) {
                    this.statusData.logged_in = true;
                    this.statusData.expires = response.expires;
                    this.updateTokenRemaining();
                    this.showNotification('Đăng nhập thành công!', 'success');
                    this.loginForm.username = '';
                    this.loginForm.password = '';
                } else {
                    this.showNotification(response.data || 'Đăng nhập thất bại', 'error');
                }
            } catch (error) {
                this.showNotification('Lỗi kết nối', 'error');
            } finally {
                this.loginForm.loading = false;
            }
        },
        
        async logout() {
            try {
                await this.apiRequest('logout');
                this.statusData.logged_in = false;
                this.statusData.expires = 0;
                this.statusData.token_remaining = 0;
                this.showNotification('Đã đăng xuất', 'info');
            } catch (error) {
                this.showNotification('Lỗi đăng xuất', 'error');
            }
        },
        
        async testConnection() {
            this.loading = true;
            try {
                const response = await this.apiRequest('test_connection');
                if (response.success) {
                    this.showNotification('Kết nối thành công!', 'success');
                } else {
                    this.showNotification('Kết nối thất bại: ' + response.data, 'error');
                }
            } catch (error) {
                this.showNotification('Lỗi kiểm tra kết nối', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async saveSettings() {
            this.settingsForm.loading = true;
            try {
                const formData = new FormData();
                formData.append('action', 'mbsp_save_settings');
                formData.append('nonce', mbsp_admin.nonce);
                formData.append('settings', JSON.stringify(this.settingsForm));
                
                const response = await fetch(mbsp_admin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    this.showNotification('Cài đặt đã được lưu!', 'success');
                } else {
                    this.showNotification('Lỗi lưu cài đặt', 'error');
                }
            } catch (error) {
                this.showNotification('Lỗi kết nối', 'error');
            } finally {
                this.settingsForm.loading = false;
            }
        },
        
        async loadTransactions() {
            this.transactionData.loading = true;
            try {
                const response = await this.apiRequest('transactions', this.transactionData.filters);
                if (response.items && response.items.success && response.items.data) {
                    this.transactionData.apiTransactions = response.items.data;
                } else {
                    this.transactionData.apiTransactions = [];
                }
                this.showNotification('Đã tải giao dịch', 'success');
            } catch (error) {
                this.showNotification('Lỗi tải giao dịch', 'error');
            } finally {
                this.transactionData.loading = false;
            }
        },
        
        async loadSettings() {
            try {
                const response = await this.apiRequest('get_settings');
                if (response.success) {
                    Object.assign(this.settingsForm, response.data);
                    this.transactionData.filters.account = response.data.acc_no || '';
                }
            } catch (error) {
                console.error('Failed to load settings:', error);
            }
        },
        
        async loadTransactionData() {
            try {
                const response = await this.apiRequest('get_transaction_data');
                if (response.success) {
                    this.transactionData.stats = response.data.stats;
                    this.transactionData.orders = response.data.orders;
                }
            } catch (error) {
                console.error('Failed to load transaction data:', error);
            }
        },
        
        updateTokenRemaining() {
            if (this.statusData.expires > 0) {
                const remaining = Math.max(0, Math.floor((this.statusData.expires - Math.floor(Date.now() / 1000)) / 60));
                this.statusData.token_remaining = remaining;
            }
        },
        
        async apiRequest(action, data = {}) {
            const formData = new FormData();
            formData.append('action', 'mbsp_' + action);
            formData.append('nonce', mbsp_admin.nonce);
            
            Object.keys(data).forEach(key => {
                formData.append(key, data[key]);
            });
            
            const response = await fetch(mbsp_admin.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        },
        
        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `mbsp-notification mbsp-notification-${type}`;
            notification.innerHTML = `
                <div class="mbsp-notification-content">
                    <span class="mbsp-notification-icon">${this.getNotificationIcon(type)}</span>
                    <span class="mbsp-notification-message">${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Hide notification
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        },
        
        getNotificationIcon(type) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            return icons[type] || icons.info;
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        },
        
        formatDate(dateString) {
            return new Date(dateString).toLocaleString('vi-VN');
        },
        
        formatNumber(number) {
            return new Intl.NumberFormat('vi-VN').format(number);
        }
    },
    
    template: `
        <div class="mbsp-vue-admin">
            <!-- Navigation -->
            <nav class="mbsp-nav">
                <button 
                    v-for="(label, page) in {status: 'Trạng thái', settings: 'Cài đặt', transactions: 'Giao dịch'}"
                    :key="page"
                    @click="currentPage = page"
                    :class="['mbsp-nav-btn', {active: currentPage === page}]"
                >
                    {{ label }}
                </button>
            </nav>
            
            <!-- Status Page -->
            <div v-if="currentPage === 'status'" class="mbsp-page">
                <status-page 
                    :status-data="statusData"
                    :login-form="loginForm"
                    :loading="loading"
                    @login="login"
                    @logout="logout"
                    @check-status="checkStatus"
                    @test-connection="testConnection"
                />
            </div>
            
            <!-- Settings Page -->
            <div v-if="currentPage === 'settings'" class="mbsp-page">
                <settings-page 
                    :form-data="settingsForm"
                    @save="saveSettings"
                />
            </div>
            
            <!-- Transactions Page -->
            <div v-if="currentPage === 'transactions'" class="mbsp-page">
                <transactions-page 
                    :transaction-data="transactionData"
                    @load-transactions="loadTransactions"
                    @update-filters="(filters) => Object.assign(transactionData.filters, filters)"
                />
            </div>
        </div>
    `
};

// Status Page Component
const StatusPage = {
    props: ['statusData', 'loginForm', 'loading'],
    emits: ['login', 'logout', 'check-status', 'test-connection'],
    
    template: `
        <div class="mbsp-admin-wrap">
            <div class="mbsp-admin-header">
                <h1>Trạng thái kết nối MBBank</h1>
                <p class="subtitle">Quản lý kết nối và xác thực với hệ thống MBBank</p>
            </div>
            
            <div class="mbsp-admin-content">
                <!-- Status Indicator -->
                <div :class="['mbsp-status-indicator', statusData.logged_in ? 'logged-in' : 'logged-out']">
                    <span>{{ statusData.logged_in ? 'Đã đăng nhập MBBank' : 'Chưa đăng nhập MBBank' }}</span>
                </div>
                
                <div class="mbsp-grid">
                    <!-- Login/Session Card -->
                    <div class="mbsp-card">
                        <div class="mbsp-card-header">
                            <h2>{{ statusData.logged_in ? 'Thông tin phiên đăng nhập' : 'Đăng nhập MBBank' }}</h2>
                        </div>
                        <div class="mbsp-card-body">
                            <!-- Login Form -->
                            <form v-if="!statusData.logged_in" @submit.prevent="$emit('login')">
                                <div class="mbsp-form-group">
                                    <label class="mbsp-form-label">Tên đăng nhập</label>
                                    <input 
                                        v-model="loginForm.username"
                                        type="text" 
                                        class="mbsp-form-input" 
                                        placeholder="Nhập tên đăng nhập MBBank"
                                        required
                                        :disabled="loginForm.loading"
                                    >
                                </div>
                                <div class="mbsp-form-group">
                                    <label class="mbsp-form-label">Mật khẩu</label>
                                    <input 
                                        v-model="loginForm.password"
                                        type="password" 
                                        class="mbsp-form-input" 
                                        placeholder="Nhập mật khẩu"
                                        required
                                        :disabled="loginForm.loading"
                                    >
                                </div>
                                <button 
                                    type="submit" 
                                    :class="['mbsp-button', {loading: loginForm.loading}]"
                                    :disabled="loginForm.loading"
                                >
                                    {{ loginForm.loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
                                </button>
                            </form>
                            
                            <!-- Session Info -->
                            <div v-else>
                                <div class="mbsp-form-group">
                                    <label class="mbsp-form-label">Token hết hạn</label>
                                    <div style="font-family: monospace; font-size: 14px; color: #374151;">
                                        {{ new Date(statusData.expires * 1000).toLocaleString() }}
                                    </div>
                                    <div class="mbsp-form-description">
                                        Còn {{ statusData.token_remaining }} phút
                                    </div>
                                </div>
                                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                    <button 
                                        @click="$emit('check-status')" 
                                        class="mbsp-button secondary"
                                    >
                                        Kiểm tra trạng thái
                                    </button>
                                    <button 
                                        @click="$emit('logout')" 
                                        class="mbsp-button danger"
                                    >
                                        Đăng xuất
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Connection Info Card -->
                    <div class="mbsp-card">
                        <div class="mbsp-card-header">
                            <h2>Thông tin kết nối</h2>
                        </div>
                        <div class="mbsp-card-body">
                            <div class="mbsp-form-group">
                                <label class="mbsp-form-label">Backend API</label>
                                <div style="font-family: monospace; font-size: 14px; color: #374151; word-break: break-all;">
                                    {{ (typeof mbsp_admin !== 'undefined' && mbsp_admin.api_url) || 'https://api.mbbank.com.vn' }}
                                </div>
                            </div>
                            <button 
                                @click="$emit('test-connection')" 
                                :class="['mbsp-button', 'secondary', {loading: loading}]"
                                :disabled="loading"
                            >
                                {{ loading ? 'Đang kiểm tra...' : 'Kiểm tra kết nối Backend' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
};

// Settings Page Component
const SettingsPage = {
    props: ['formData'],
    emits: ['save'],
    
    template: `
        <div class="mbsp-admin-wrap">
            <div class="mbsp-admin-header">
                <h1>Cài đặt MB Smart Payment</h1>
                <p class="subtitle">Cấu hình thông tin tài khoản và API MBBank</p>
            </div>
            
            <div class="mbsp-admin-content">
                <form @submit.prevent="$emit('save')">
                    <div class="mbsp-grid">
                        <!-- Settings Card -->
                        <div class="mbsp-card mbsp-card-full">
                            <div class="mbsp-card-header">
                                <h2>Thông tin tài khoản MBBank</h2>
                            </div>
                            <div class="mbsp-card-body">
                                <div class="mbsp-form-group">
                                    <label class="mbsp-form-label">Tên đăng nhập</label>
                                    <input 
                                        v-model="formData.user"
                                        type="text" 
                                        class="mbsp-form-input" 
                                        placeholder="Nhập tên đăng nhập MBBank"
                                        :disabled="formData.loading"
                                    >
                                    <div class="mbsp-form-description">
                                        Tên đăng nhập internet banking MBBank của bạn
                                    </div>
                                </div>
                                
                                <div class="mbsp-form-group">
                                    <label class="mbsp-form-label">Mật khẩu</label>
                                    <input 
                                        v-model="formData.pass"
                                        type="password" 
                                        class="mbsp-form-input" 
                                        placeholder="Nhập mật khẩu"
                                        :disabled="formData.loading"
                                    >
                                    <div class="mbsp-form-description">
                                        Mật khẩu internet banking MBBank của bạn
                                    </div>
                                </div>
                                
                                <div class="mbsp-form-group">
                                    <label class="mbsp-form-label">Số tài khoản</label>
                                    <input 
                                        v-model="formData.acc_no"
                                        type="text" 
                                        class="mbsp-form-input" 
                                        placeholder="Nhập số tài khoản nhận tiền"
                                        :disabled="formData.loading"
                                    >
                                    <div class="mbsp-form-description">
                                        Số tài khoản MBBank để nhận thanh toán từ khách hàng
                                    </div>
                                </div>
                                
                                <div class="mbsp-form-group">
                                    <label class="mbsp-form-label">Tên chủ tài khoản</label>
                                    <input 
                                        v-model="formData.acc_name"
                                        type="text" 
                                        class="mbsp-form-input" 
                                        placeholder="Nhập tên chủ tài khoản"
                                        :disabled="formData.loading"
                                    >
                                    <div class="mbsp-form-description">
                                        Tên chủ tài khoản hiển thị trên QR code và thông tin thanh toán
                                    </div>
                                </div>
                                
                                <button 
                                    type="submit" 
                                    :class="['mbsp-button', {loading: formData.loading}]"
                                    :disabled="formData.loading"
                                >
                                    {{ formData.loading ? 'Đang lưu...' : 'Lưu cài đặt' }}
                                </button>
                            </div>
                        </div>
                        
                        <!-- Security Notice -->
                        <div class="mbsp-card mbsp-card-full">
                            <div class="mbsp-card-header">
                                <h2>Lưu ý bảo mật</h2>
                            </div>
                            <div class="mbsp-card-body">
                                <div class="mbsp-notice warning">
                                    <div class="mbsp-notice-content">
                                        <p><strong>Quan trọng:</strong></p>
                                        <ul style="margin: 8px 0 0 20px;">
                                            <li>Thông tin đăng nhập được mã hóa và lưu trữ an toàn</li>
                                            <li>Chỉ sử dụng tài khoản có quyền truy cập hạn chế</li>
                                            <li>Thường xuyên kiểm tra và thay đổi mật khẩu</li>
                                            <li>Không chia sẻ thông tin đăng nhập với bên thứ ba</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    `
};

// Transactions Page Component
const TransactionsPage = {
    props: ['transactionData'],
    emits: ['load-transactions', 'update-filters'],
    
    methods: {
        formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        },
        
        formatDate(dateString) {
            return new Date(dateString).toLocaleString('vi-VN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        formatNumber(number) {
            return new Intl.NumberFormat('vi-VN').format(number);
        },
        
        getStatusClass(status) {
            const classes = {
                pending: 'pending',
                completed: 'completed',
                failed: 'failed'
            };
            return classes[status] || 'pending';
        },
        
        getStatusText(status) {
            const texts = {
                pending: 'Chờ thanh toán',
                completed: 'Đã thanh toán',
                failed: 'Thất bại'
            };
            return texts[status] || status;
        }
    },
    
    template: `
        <div class="mbsp-admin-wrap">
            <div class="mbsp-admin-header">
                <h1>Lịch sử giao dịch MBBank</h1>
                <p class="subtitle">Theo dõi và quản lý các giao dịch thanh toán qua MBBank</p>
            </div>
            
            <div class="mbsp-admin-content">
                <!-- Statistics -->
                <div class="mbsp-stats-grid">
                    <div class="mbsp-stat-card">
                        <div class="mbsp-stat-value">{{ formatNumber(transactionData.stats.total_orders) }}</div>
                        <div class="mbsp-stat-label">Tổng đơn hàng</div>
                    </div>
                    <div class="mbsp-stat-card">
                        <div class="mbsp-stat-value">{{ formatNumber(transactionData.stats.completed_orders) }}</div>
                        <div class="mbsp-stat-label">Đã thanh toán</div>
                    </div>
                    <div class="mbsp-stat-card">
                        <div class="mbsp-stat-value">{{ formatNumber(transactionData.stats.pending_orders) }}</div>
                        <div class="mbsp-stat-label">Chờ thanh toán</div>
                    </div>
                    <div class="mbsp-stat-card">
                        <div class="mbsp-stat-value">{{ formatNumber(transactionData.stats.total_amount) }}</div>
                        <div class="mbsp-stat-label">Tổng doanh thu (VND)</div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="mbsp-filters">
                    <div class="mbsp-filters-grid">
                        <div class="mbsp-filter-group">
                            <label class="mbsp-filter-label">Từ ngày</label>
                            <input 
                                v-model="transactionData.filters.from"
                                type="date" 
                                class="mbsp-filter-input"
                                @change="$emit('update-filters', transactionData.filters)"
                            >
                        </div>
                        <div class="mbsp-filter-group">
                            <label class="mbsp-filter-label">Đến ngày</label>
                            <input 
                                v-model="transactionData.filters.to"
                                type="date" 
                                class="mbsp-filter-input"
                                @change="$emit('update-filters', transactionData.filters)"
                            >
                        </div>
                        <div class="mbsp-filter-group">
                            <label class="mbsp-filter-label">Số tài khoản</label>
                            <input 
                                v-model="transactionData.filters.account"
                                type="text" 
                                class="mbsp-filter-input" 
                                placeholder="Nhập số tài khoản"
                                @input="$emit('update-filters', transactionData.filters)"
                            >
                        </div>
                        <div class="mbsp-filter-group">
                            <label class="mbsp-filter-label">&nbsp;</label>
                            <button 
                                @click="$emit('load-transactions')" 
                                :class="['mbsp-button', {loading: transactionData.loading}]"
                                :disabled="transactionData.loading"
                            >
                                {{ transactionData.loading ? 'Đang tải...' : 'Tải giao dịch' }}
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="mbsp-grid">
                    <!-- API Transactions -->
                    <div class="mbsp-card mbsp-card-full">
                        <div class="mbsp-card-header">
                            <h2>Giao dịch từ MBBank API</h2>
                        </div>
                        <div class="mbsp-card-body" style="padding: 0;">
                            <div class="mbsp-table-container">
                                <table class="mbsp-table">
                                    <thead>
                                        <tr>
                                            <th>Mã giao dịch</th>
                                            <th>Số tiền</th>
                                            <th>Mô tả</th>
                                            <th>Thời gian</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="transactionData.apiTransactions.length === 0">
                                            <td colspan="4" style="text-align: center; padding: 40px;">
                                                <div class="mbsp-empty-state">
                                                    <div class="icon">📊</div>
                                                    <h3>Chưa có dữ liệu</h3>
                                                    <p>Nhấn "Tải giao dịch" để xem dữ liệu từ MBBank</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-for="transaction in transactionData.apiTransactions" :key="transaction.refNo">
                                            <td style="font-family: monospace; font-size: 13px;">{{ transaction.refNo }}</td>
                                            <td class="amount">{{ formatCurrency(transaction.creditAmount || transaction.debitAmount) }}</td>
                                            <td>{{ transaction.transactionDesc }}</td>
                                            <td style="font-size: 13px;">{{ formatDate(transaction.transactionDate) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orders -->
                    <div class="mbsp-card mbsp-card-full">
                        <div class="mbsp-card-header">
                            <h2>Đơn hàng MB Smart Payment</h2>
                        </div>
                        <div class="mbsp-card-body" style="padding: 0;">
                            <div class="mbsp-table-container">
                                <table class="mbsp-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Đơn hàng</th>
                                            <th>Khách hàng</th>
                                            <th>Số tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Mã GD</th>
                                            <th>Thời gian tạo</th>
                                            <th>Cập nhật</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="transactionData.orders.length === 0">
                                            <td colspan="8" style="text-align: center; padding: 40px;">
                                                <div class="mbsp-empty-state">
                                                    <div class="icon">🛒</div>
                                                    <h3>Chưa có đơn hàng nào</h3>
                                                    <p>Các đơn hàng sử dụng MB Smart Payment sẽ hiển thị ở đây</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-for="order in transactionData.orders" :key="order.id">
                                            <td>{{ order.id }}</td>
                                            <td>
                                                <a :href="'post.php?post=' + order.order_id + '&action=edit'" 
                                                   style="color: #667eea; text-decoration: none; font-weight: 600;">
                                                    #{{ order.order_id }}
                                                </a>
                                            </td>
                                            <td>{{ order.customer_name || order.customer_email }}</td>
                                            <td class="amount">{{ formatCurrency(order.amount) }}</td>
                                            <td>
                                                <span :class="['status', getStatusClass(order.status)]">
                                                    {{ getStatusText(order.status) }}
                                                </span>
                                            </td>
                                            <td style="font-family: monospace; font-size: 13px;">{{ order.trans_id || '-' }}</td>
                                            <td style="font-size: 13px;">{{ formatDate(order.created) }}</td>
                                            <td style="font-size: 13px;">{{ formatDate(order.updated) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
};

// Register components
if (typeof Vue !== 'undefined') {
    const { createApp } = Vue;
    
    // Create Vue app when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const adminContainer = document.getElementById('mbsp-vue-admin');
        if (adminContainer) {
            const app = createApp(AdminApp);
            app.component('status-page', StatusPage);
            app.component('settings-page', SettingsPage);
            app.component('transactions-page', TransactionsPage);
            app.mount('#mbsp-vue-admin');
        }
    });
}