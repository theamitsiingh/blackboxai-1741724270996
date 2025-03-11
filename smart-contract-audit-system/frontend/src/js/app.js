// API endpoints
const API_BASE_URL = '/smart-contract-audit-system/backend/api';
const API_ENDPOINTS = {
    auth: {
        login: `${API_BASE_URL}/auth/login`,
        register: `${API_BASE_URL}/auth/register`,
        forgotPassword: `${API_BASE_URL}/auth/forgot-password`
    },
    audits: {
        create: `${API_BASE_URL}/audits/create`,
        list: `${API_BASE_URL}/audits/list`,
        get: (id) => `${API_BASE_URL}/audits/${id}`,
        update: (id) => `${API_BASE_URL}/audits/${id}`,
        stats: `${API_BASE_URL}/audits/stats`,
        search: `${API_BASE_URL}/audits/search`
    },
    reports: {
        create: `${API_BASE_URL}/reports/create`,
        list: `${API_BASE_URL}/reports/list`,
        get: (id) => `${API_BASE_URL}/reports/${id}`,
        update: (id) => `${API_BASE_URL}/reports/${id}`,
        stats: `${API_BASE_URL}/reports/stats`
    },
    compliance: {
        list: `${API_BASE_URL}/compliance/list`,
        get: (id) => `${API_BASE_URL}/compliance/${id}`,
        check: (auditId) => `${API_BASE_URL}/compliance/check/${auditId}`
    }
};

// Auth Service
const AuthService = {
    getToken() {
        return localStorage.getItem('token');
    },

    getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    },

    isAuthenticated() {
        return !!this.getToken();
    },

    isAdmin() {
        const user = this.getUser();
        return user && user.role === 'admin';
    },

    logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    },

    checkAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = 'login.html';
            return false;
        }
        return true;
    }
};

// API Service
const ApiService = {
    async request(endpoint, options = {}) {
        const token = AuthService.getToken();
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {})
            }
        };

        try {
            const response = await fetch(endpoint, { ...defaultOptions, ...options });
            const data = await response.json();

            if (!response.ok) {
                // Handle unauthorized access
                if (response.status === 401) {
                    AuthService.logout();
                }
                throw new Error(data.message || 'API request failed');
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    },

    get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};

// UI Utilities
const UIUtils = {
    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            element.disabled = true;
        }
    },

    hideLoading(elementId, originalText) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = originalText;
            element.disabled = false;
        }
    },

    showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 rounded-md p-4 ${
            type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
        }`;
        alertDiv.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
            </div>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
    },

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    updateNavigation() {
        const user = AuthService.getUser();
        if (user) {
            // Update user menu
            const userMenu = document.querySelector('#user-menu-button');
            if (userMenu) {
                userMenu.innerHTML = `
                    <span class="text-sm font-medium text-gray-700">${user.name}</span>
                    <i class="fas fa-chevron-down ml-2"></i>
                `;
            }

            // Show/hide admin-only elements
            const adminElements = document.querySelectorAll('[data-admin-only]');
            adminElements.forEach(element => {
                element.style.display = user.role === 'admin' ? '' : 'none';
            });
        }
    }
};

// Event Handlers
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication for protected pages
    if (!window.location.pathname.includes('login.html') && 
        !window.location.pathname.includes('register.html')) {
        AuthService.checkAuth();
    }

    // Update navigation
    UIUtils.updateNavigation();

    // Setup logout handler
    const logoutButton = document.querySelector('[data-logout]');
    if (logoutButton) {
        logoutButton.addEventListener('click', (e) => {
            e.preventDefault();
            AuthService.logout();
        });
    }

    // Setup user menu toggle
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    }
});

// Export services for use in other scripts
window.AuthService = AuthService;
window.ApiService = ApiService;
window.UIUtils = UIUtils;
window.API_ENDPOINTS = API_ENDPOINTS;
