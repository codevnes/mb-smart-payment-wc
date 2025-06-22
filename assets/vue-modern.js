/**
 * Modern Vue.js Admin Interface with Element Plus
 */

// Wait for Element Plus to load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Vue === 'undefined' || typeof ElementPlus === 'undefined') {
        console.error('Vue.js or Element Plus not loaded');
        return;
    }

    const { createApp, ref, reactive, computed, onMounted, watch } = Vue;
    const { ElMessage, ElMessageBox, ElNotification } = ElementPlus;

    // Main Admin App
    const AdminApp = {
        setup() {
            const activeTab = ref('status');
            const loading = ref(false);
            
            // Status data
            const statusData = reactive({
                logged_in: false,
                expires: 0,
                token_remaining: 0,
                user_info: {}
            });
            
            // Login form
            const loginForm = reactive({
                username: '',
                password: '',
                loading: false
            });
            
            // Settings form
            const settingsForm = reactive({
                user: '',
                pass: '',
                acc_no: '',
                acc_name: '',
                loading: false
            });
            
            // Transaction data
            const transactionData = reactive({
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
            });

            // Computed properties
            const statusClass = computed(() => statusData.logged_in ? 'success' : 'warning');
            const statusText = computed(() => statusData.logged_in ? 'Đã đăng nhập MBBank' : 'Chưa đăng nhập MBBank');
            
            // Methods
            const checkStatus = async () => {
                try {
                    const response = await apiRequest('status');
                    Object.assign(statusData, response);
                    updateTokenRemaining();
                } catch (error) {
                    console.error('Status check failed:', error);
                }
            };
            
            const login = async () => {
                if (!loginForm.username || !loginForm.password) {
                    ElMessage.warning('Vui lòng nhập đầy đủ thông tin');
                    return;
                }
                
                loginForm.loading = true;
                try {
                    const response = await apiRequest('login', {
                        user: loginForm.username,
                        pass: loginForm.password
                    });
                    
                    if (response.success) {
                        statusData.logged_in = true;
                        statusData.expires = response.expires;
                        updateTokenRemaining();
                        ElNotification.success({
                            title: 'Thành công',
                            message: 'Đăng nhập thành công!'
                        });
                        loginForm.username = '';
                        loginForm.password = '';
                    } else {
                        ElMessage.error(response.data || 'Đăng nhập thất bại');
                    }
                } catch (error) {
                    ElMessage.error('Lỗi kết nối');
                } finally {
                    loginForm.loading = false;
                }
            };
            
            const logout = async () => {
                try {
                    await ElMessageBox.confirm('Bạn có chắc chắn muốn đăng xuất?', 'Xác nhận', {
                        confirmButtonText: 'Đăng xuất',
                        cancelButtonText: 'Hủy',
                        type: 'warning'
                    });
                    
                    await apiRequest('logout');
                    statusData.logged_in = false;
                    statusData.expires = 0;
                    statusData.token_remaining = 0;
                    ElNotification.info({
                        title: 'Thông báo',
                        message: 'Đã đăng xuất'
                    });
                } catch (error) {
                    if (error !== 'cancel') {
                        ElMessage.error('Lỗi đăng xuất');
                    }
                }
            };
            
            const testConnection = async () => {
                loading.value = true;
                try {
                    const response = await apiRequest('test_connection');
                    if (response.success) {
                        ElNotification.success({
                            title: 'Thành công',
                            message: 'Kết nối thành công!'
                        });
                    } else {
                        ElMessage.error('Kết nối thất bại: ' + response.data);
                    }
                } catch (error) {
                    ElMessage.error('Lỗi kiểm tra kết nối');
                } finally {
                    loading.value = false;
                }
            };
            
            const saveSettings = async () => {
                settingsForm.loading = true;
                try {
                    const formData = new FormData();
                    formData.append('action', 'mbsp_save_settings');
                    formData.append('nonce', window.mbsp_admin?.nonce || '');
                    formData.append('settings', JSON.stringify(settingsForm));
                    
                    const response = await fetch(window.mbsp_admin?.ajax_url || '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        ElNotification.success({
                            title: 'Thành công',
                            message: 'Cài đặt đã được lưu!'
                        });
                    } else {
                        ElMessage.error('Lỗi lưu cài đặt');
                    }
                } catch (error) {
                    ElMessage.error('Lỗi kết nối');
                } finally {
                    settingsForm.loading = false;
                }
            };
            
            const loadTransactions = async () => {
                transactionData.loading = true;
                try {
                    const response = await apiRequest('transactions', transactionData.filters);
                    if (response.items && response.items.success && response.items.data) {
                        transactionData.apiTransactions = response.items.data;
                    } else {
                        transactionData.apiTransactions = [];
                    }
                    ElNotification.success({
                        title: 'Thành công',
                        message: 'Đã tải giao dịch'
                    });
                } catch (error) {
                    ElMessage.error('Lỗi tải giao dịch');
                } finally {
                    transactionData.loading = false;
                }
            };
            
            const loadSettings = async () => {
                try {
                    const response = await apiRequest('get_settings');
                    if (response.success) {
                        Object.assign(settingsForm, response.data);
                        transactionData.filters.account = response.data.acc_no || '';
                    }
                } catch (error) {
                    console.error('Failed to load settings:', error);
                }
            };
            
            const loadTransactionData = async () => {
                try {
                    const response = await apiRequest('get_transaction_data');
                    if (response.success) {
                        transactionData.stats = response.data.stats;
                        transactionData.orders = response.data.orders;
                    }
                } catch (error) {
                    console.error('Failed to load transaction data:', error);
                }
            };
            
            const updateTokenRemaining = () => {
                if (statusData.expires > 0) {
                    const remaining = Math.max(0, Math.floor((statusData.expires - Math.floor(Date.now() / 1000)) / 60));
                    statusData.token_remaining = remaining;
                }
            };
            
            const apiRequest = async (action, data = {}) => {
                const formData = new FormData();
                formData.append('action', 'mbsp_' + action);
                formData.append('nonce', window.mbsp_admin?.nonce || '');
                
                Object.keys(data).forEach(key => {
                    formData.append(key, data[key]);
                });
                
                const response = await fetch(window.mbsp_admin?.ajax_url || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                return await response.json();
            };
            
            const formatCurrency = (amount) => {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount);
            };
            
            const formatDate = (dateString) => {
                return new Date(dateString).toLocaleString('vi-VN');
            };
            
            const formatNumber = (number) => {
                return new Intl.NumberFormat('vi-VN').format(number);
            };

            // Lifecycle
            onMounted(() => {
                checkStatus();
                loadSettings();
                loadTransactionData();
                
                // Update token remaining every minute
                setInterval(updateTokenRemaining, 60000);
            });

            return {
                activeTab,
                loading,
                statusData,
                loginForm,
                settingsForm,
                transactionData,
                statusClass,
                statusText,
                checkStatus,
                login,
                logout,
                testConnection,
                saveSettings,
                loadTransactions,
                formatCurrency,
                formatDate,
                formatNumber
            };
        },
        
        template: `
            <div class="mbsp-modern-admin">
                <el-container>
                    <el-header class="mbsp-header">
                        <div class="mbsp-header-content">
                            <h1>MB Smart Payment</h1>
                            <el-tag :type="statusClass" size="large">
                                {{ statusText }}
                            </el-tag>
                        </div>
                    </el-header>
                    
                    <el-main>
                        <el-tabs v-model="activeTab" type="card" class="mbsp-tabs">
                            <el-tab-pane label="Trạng thái" name="status">
                                <status-panel 
                                    :status-data="statusData"
                                    :login-form="loginForm"
                                    :loading="loading"
                                    @login="login"
                                    @logout="logout"
                                    @check-status="checkStatus"
                                    @test-connection="testConnection"
                                />
                            </el-tab-pane>
                            
                            <el-tab-pane label="Cài đặt" name="settings">
                                <settings-panel 
                                    :form-data="settingsForm"
                                    @save="saveSettings"
                                />
                            </el-tab-pane>
                            
                            <el-tab-pane label="Giao dịch" name="transactions">
                                <transactions-panel 
                                    :transaction-data="transactionData"
                                    @load-transactions="loadTransactions"
                                    :format-currency="formatCurrency"
                                    :format-date="formatDate"
                                    :format-number="formatNumber"
                                />
                            </el-tab-pane>
                        </el-tabs>
                    </el-main>
                </el-container>
            </div>
        `
    };

    // Status Panel Component
    const StatusPanel = {
        props: ['statusData', 'loginForm', 'loading'],
        emits: ['login', 'logout', 'check-status', 'test-connection'],
        
        template: `
            <div class="mbsp-panel">
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-card class="mbsp-card">
                            <template #header>
                                <div class="card-header">
                                    <span>{{ statusData.logged_in ? 'Thông tin phiên đăng nhập' : 'Đăng nhập MBBank' }}</span>
                                </div>
                            </template>
                            
                            <!-- Login Form -->
                            <el-form v-if="!statusData.logged_in" @submit.prevent="$emit('login')" label-position="top">
                                <el-form-item label="Tên đăng nhập">
                                    <el-input 
                                        v-model="loginForm.username"
                                        placeholder="Nhập tên đăng nhập MBBank"
                                        :disabled="loginForm.loading"
                                        size="large"
                                    />
                                </el-form-item>
                                <el-form-item label="Mật khẩu">
                                    <el-input 
                                        v-model="loginForm.password"
                                        type="password"
                                        placeholder="Nhập mật khẩu"
                                        :disabled="loginForm.loading"
                                        size="large"
                                        show-password
                                    />
                                </el-form-item>
                                <el-form-item>
                                    <el-button 
                                        type="primary" 
                                        @click="$emit('login')"
                                        :loading="loginForm.loading"
                                        size="large"
                                        style="width: 100%"
                                    >
                                        {{ loginForm.loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
                                    </el-button>
                                </el-form-item>
                            </el-form>
                            
                            <!-- Session Info -->
                            <div v-else>
                                <el-descriptions :column="1" border>
                                    <el-descriptions-item label="Token hết hạn">
                                        {{ new Date(statusData.expires * 1000).toLocaleString() }}
                                    </el-descriptions-item>
                                    <el-descriptions-item label="Thời gian còn lại">
                                        <el-tag type="info">{{ statusData.token_remaining }} phút</el-tag>
                                    </el-descriptions-item>
                                </el-descriptions>
                                
                                <div style="margin-top: 20px;">
                                    <el-button @click="$emit('check-status')" size="large">
                                        Kiểm tra trạng thái
                                    </el-button>
                                    <el-button type="danger" @click="$emit('logout')" size="large">
                                        Đăng xuất
                                    </el-button>
                                </div>
                            </div>
                        </el-card>
                    </el-col>
                    
                    <el-col :span="12">
                        <el-card class="mbsp-card">
                            <template #header>
                                <div class="card-header">
                                    <span>Thông tin kết nối</span>
                                </div>
                            </template>
                            
                            <el-descriptions :column="1" border>
                                <el-descriptions-item label="Backend API">
                                    <code>{{ (typeof mbsp_admin !== 'undefined' && mbsp_admin.api_url) || 'https://api.mbbank.com.vn' }}</code>
                                </el-descriptions-item>
                            </el-descriptions>
                            
                            <div style="margin-top: 20px;">
                                <el-button 
                                    @click="$emit('test-connection')" 
                                    :loading="loading"
                                    size="large"
                                    style="width: 100%"
                                >
                                    {{ loading ? 'Đang kiểm tra...' : 'Kiểm tra kết nối Backend' }}
                                </el-button>
                            </div>
                        </el-card>
                    </el-col>
                </el-row>
            </div>
        `
    };

    // Settings Panel Component
    const SettingsPanel = {
        props: ['formData'],
        emits: ['save'],
        
        template: `
            <div class="mbsp-panel">
                <el-row :gutter="20">
                    <el-col :span="16">
                        <el-card class="mbsp-card">
                            <template #header>
                                <div class="card-header">
                                    <span>Thông tin tài khoản MBBank</span>
                                </div>
                            </template>
                            
                            <el-form label-position="top" @submit.prevent="$emit('save')">
                                <el-form-item label="Tên đăng nhập">
                                    <el-input 
                                        v-model="formData.user"
                                        placeholder="Nhập tên đăng nhập MBBank"
                                        :disabled="formData.loading"
                                        size="large"
                                    />
                                    <div class="form-description">Tên đăng nhập internet banking MBBank của bạn</div>
                                </el-form-item>
                                
                                <el-form-item label="Mật khẩu">
                                    <el-input 
                                        v-model="formData.pass"
                                        type="password"
                                        placeholder="Nhập mật khẩu"
                                        :disabled="formData.loading"
                                        size="large"
                                        show-password
                                    />
                                    <div class="form-description">Mật khẩu internet banking MBBank của bạn</div>
                                </el-form-item>
                                
                                <el-form-item label="Số tài khoản">
                                    <el-input 
                                        v-model="formData.acc_no"
                                        placeholder="Nhập số tài khoản nhận tiền"
                                        :disabled="formData.loading"
                                        size="large"
                                    />
                                    <div class="form-description">Số tài khoản MBBank để nhận thanh toán từ khách hàng</div>
                                </el-form-item>
                                
                                <el-form-item label="Tên chủ tài khoản">
                                    <el-input 
                                        v-model="formData.acc_name"
                                        placeholder="Nhập tên chủ tài khoản"
                                        :disabled="formData.loading"
                                        size="large"
                                    />
                                    <div class="form-description">Tên chủ tài khoản hiển thị trên QR code và thông tin thanh toán</div>
                                </el-form-item>
                                
                                <el-form-item>
                                    <el-button 
                                        type="primary" 
                                        @click="$emit('save')"
                                        :loading="formData.loading"
                                        size="large"
                                    >
                                        {{ formData.loading ? 'Đang lưu...' : 'Lưu cài đặt' }}
                                    </el-button>
                                </el-form-item>
                            </el-form>
                        </el-card>
                    </el-col>
                    
                    <el-col :span="8">
                        <el-card class="mbsp-card">
                            <template #header>
                                <div class="card-header">
                                    <span>Lưu ý bảo mật</span>
                                </div>
                            </template>
                            
                            <el-alert
                                title="Quan trọng"
                                type="warning"
                                :closable="false"
                                show-icon
                            >
                                <ul style="margin: 8px 0 0 20px;">
                                    <li>Thông tin đăng nhập được mã hóa và lưu trữ an toàn</li>
                                    <li>Chỉ sử dụng tài khoản có quyền truy cập hạn chế</li>
                                    <li>Thường xuyên kiểm tra và thay đổi mật khẩu</li>
                                    <li>Không chia sẻ thông tin đăng nhập với bên thứ ba</li>
                                </ul>
                            </el-alert>
                        </el-card>
                    </el-col>
                </el-row>
            </div>
        `
    };

    // Transactions Panel Component
    const TransactionsPanel = {
        props: ['transactionData', 'formatCurrency', 'formatDate', 'formatNumber'],
        emits: ['load-transactions'],
        
        setup(props) {
            const getStatusType = (status) => {
                const types = {
                    pending: 'warning',
                    completed: 'success',
                    failed: 'danger'
                };
                return types[status] || 'info';
            };
            
            const getStatusText = (status) => {
                const texts = {
                    pending: 'Chờ thanh toán',
                    completed: 'Đã thanh toán',
                    failed: 'Thất bại'
                };
                return texts[status] || status;
            };
            
            return {
                getStatusType,
                getStatusText
            };
        },
        
        template: `
            <div class="mbsp-panel">
                <!-- Statistics -->
                <el-row :gutter="20" style="margin-bottom: 20px;">
                    <el-col :span="6">
                        <el-card class="stat-card">
                            <el-statistic title="Tổng đơn hàng" :value="transactionData.stats.total_orders" />
                        </el-card>
                    </el-col>
                    <el-col :span="6">
                        <el-card class="stat-card">
                            <el-statistic title="Đã thanh toán" :value="transactionData.stats.completed_orders" />
                        </el-card>
                    </el-col>
                    <el-col :span="6">
                        <el-card class="stat-card">
                            <el-statistic title="Chờ thanh toán" :value="transactionData.stats.pending_orders" />
                        </el-card>
                    </el-col>
                    <el-col :span="6">
                        <el-card class="stat-card">
                            <el-statistic title="Tổng doanh thu" :value="formatNumber(transactionData.stats.total_amount)" suffix="VND" />
                        </el-card>
                    </el-col>
                </el-row>
                
                <!-- Filters -->
                <el-card style="margin-bottom: 20px;">
                    <el-row :gutter="20" align="bottom">
                        <el-col :span="6">
                            <el-form-item label="Từ ngày">
                                <el-date-picker 
                                    v-model="transactionData.filters.from"
                                    type="date"
                                    placeholder="Chọn ngày"
                                    style="width: 100%"
                                />
                            </el-form-item>
                        </el-col>
                        <el-col :span="6">
                            <el-form-item label="Đến ngày">
                                <el-date-picker 
                                    v-model="transactionData.filters.to"
                                    type="date"
                                    placeholder="Chọn ngày"
                                    style="width: 100%"
                                />
                            </el-form-item>
                        </el-col>
                        <el-col :span="6">
                            <el-form-item label="Số tài khoản">
                                <el-input 
                                    v-model="transactionData.filters.account"
                                    placeholder="Nhập số tài khoản"
                                />
                            </el-form-item>
                        </el-col>
                        <el-col :span="6">
                            <el-form-item>
                                <el-button 
                                    type="primary" 
                                    @click="$emit('load-transactions')"
                                    :loading="transactionData.loading"
                                    style="width: 100%"
                                >
                                    {{ transactionData.loading ? 'Đang tải...' : 'Tải giao dịch' }}
                                </el-button>
                            </el-form-item>
                        </el-col>
                    </el-row>
                </el-card>
                
                <!-- API Transactions -->
                <el-card style="margin-bottom: 20px;">
                    <template #header>
                        <div class="card-header">
                            <span>Giao dịch từ MBBank API</span>
                        </div>
                    </template>
                    
                    <el-table 
                        :data="transactionData.apiTransactions" 
                        style="width: 100%"
                        empty-text="Nhấn 'Tải giao dịch' để xem dữ liệu từ MBBank"
                    >
                        <el-table-column prop="refNo" label="Mã giao dịch" width="150" />
                        <el-table-column label="Số tiền" width="150">
                            <template #default="scope">
                                <span style="color: #67c23a; font-weight: bold;">
                                    {{ formatCurrency(scope.row.creditAmount || scope.row.debitAmount) }}
                                </span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="transactionDesc" label="Mô tả" />
                        <el-table-column label="Thời gian" width="180">
                            <template #default="scope">
                                {{ formatDate(scope.row.transactionDate) }}
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
                
                <!-- Orders -->
                <el-card>
                    <template #header>
                        <div class="card-header">
                            <span>Đơn hàng MB Smart Payment</span>
                        </div>
                    </template>
                    
                    <el-table 
                        :data="transactionData.orders" 
                        style="width: 100%"
                        empty-text="Các đơn hàng sử dụng MB Smart Payment sẽ hiển thị ở đây"
                    >
                        <el-table-column prop="id" label="ID" width="80" />
                        <el-table-column label="Đơn hàng" width="120">
                            <template #default="scope">
                                <el-link :href="'post.php?post=' + scope.row.order_id + '&action=edit'" type="primary">
                                    #{{ scope.row.order_id }}
                                </el-link>
                            </template>
                        </el-table-column>
                        <el-table-column label="Khách hàng" width="200">
                            <template #default="scope">
                                {{ scope.row.customer_name || scope.row.customer_email }}
                            </template>
                        </el-table-column>
                        <el-table-column label="Số tiền" width="150">
                            <template #default="scope">
                                <span style="color: #67c23a; font-weight: bold;">
                                    {{ formatCurrency(scope.row.amount) }}
                                </span>
                            </template>
                        </el-table-column>
                        <el-table-column label="Trạng thái" width="120">
                            <template #default="scope">
                                <el-tag :type="getStatusType(scope.row.status)">
                                    {{ getStatusText(scope.row.status) }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="trans_id" label="Mã GD" width="150">
                            <template #default="scope">
                                <code>{{ scope.row.trans_id || '-' }}</code>
                            </template>
                        </el-table-column>
                        <el-table-column label="Thời gian tạo" width="150">
                            <template #default="scope">
                                {{ formatDate(scope.row.created) }}
                            </template>
                        </el-table-column>
                        <el-table-column label="Cập nhật" width="150">
                            <template #default="scope">
                                {{ formatDate(scope.row.updated) }}
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
            </div>
        `
    };

    // Create and mount the app
    const app = createApp(AdminApp);
    app.use(ElementPlus);
    app.component('status-panel', StatusPanel);
    app.component('settings-panel', SettingsPanel);
    app.component('transactions-panel', TransactionsPanel);
    
    const adminContainer = document.getElementById('mbsp-vue-admin');
    if (adminContainer) {
        app.mount('#mbsp-vue-admin');
    }
});