/**
 * API Client for Alt Firma Takip Sistemi
 * Handles all HTTP requests to the backend REST API
 */

class API {
    constructor() {
        // API base URL - localhost ve production otomatik algıla
        this.baseURL = window.location.hostname === 'localhost'
            ? '/alt-firma-takip/backend/api'
            : '/backend/api';
        this.token = localStorage.getItem('token');
    }
    
    /**
     * Generic request method
     * @param {string} endpoint - API endpoint (e.g., '/auth/login')
     * @param {object} options - Fetch options
     * @returns {Promise<object>} - Response data
     */
    async request(endpoint, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        // Add Authorization header if token exists
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        const config = {
            ...options,
            headers
        };
        
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, config);
            const data = await response.json();
            
            // Handle 401 Unauthorized - token expired or invalid
            if (response.status === 401) {
                this.clearToken();
                localStorage.removeItem('user');
                window.location.replace('login.html');
                // Redirect başlayana kadar bekle, toast gösterilmesin
                await new Promise(() => {});
            }

            return data;
        } catch (error) {
            console.error('API request error:', error);
            throw new Error('Bağlantı hatası. Lütfen internet bağlantınızı kontrol edin.');
        }
    }
    
    /**
     * Set authentication token
     * @param {string} token - JWT token
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem('token', token);
    }
    
    /**
     * Clear authentication token
     */
    clearToken() {
        this.token = null;
        localStorage.removeItem('token');
    }
    
    // ==================== Authentication Methods ====================
    
    /**
     * Login with username and password
     * @param {string} username
     * @param {string} password
     * @returns {Promise<object>}
     */
    async login(username, password) {
        const response = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
        
        if (response.success && response.data && response.data.token) {
            this.setToken(response.data.token);
        }
        
        return response;
    }
    
    /**
     * Verify JWT token
     * @returns {Promise<object>}
     */
    async verifyToken() {
        return await this.request('/auth/verify', {
            method: 'POST'
        });
    }
    
    // ==================== Subcontractor Methods ====================
    
    /**
     * Get all subcontractors with balances
     * @returns {Promise<object>}
     */
    async getSubcontractors() {
        return await this.request('/subcontractors', {
            method: 'GET'
        });
    }
    
    /**
     * Get single subcontractor with jobs and payments
     * @param {number} id - Subcontractor ID
     * @returns {Promise<object>}
     */
    async getSubcontractor(id) {
        return await this.request(`/subcontractors/${id}`, {
            method: 'GET'
        });
    }
    
    /**
     * Create new subcontractor
     * @param {object} data - Subcontractor data
     * @returns {Promise<object>}
     */
    async createSubcontractor(data) {
        return await this.request('/subcontractors', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Update subcontractor
     * @param {number} id - Subcontractor ID
     * @param {object} data - Updated data
     * @returns {Promise<object>}
     */
    async updateSubcontractor(id, data) {
        return await this.request(`/subcontractors/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Toggle subcontractor status (aktif/pasif)
     * @param {number} id - Subcontractor ID
     * @returns {Promise<object>}
     */
    async toggleSubcontractorStatus(id) {
        return await this.request(`/subcontractors/${id}/status`, {
            method: 'PATCH'
        });
    }

    async deleteSubcontractor(id) {
        return await this.request(`/subcontractors/${id}`, { method: 'DELETE' });
    }
    
    // ==================== Job Methods ====================
    
    /**
     * Create new job
     * @param {object} data - Job data
     * @returns {Promise<object>}
     */
    async createJob(data) {
        return await this.request('/jobs', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Update job
     * @param {number} id - Job ID
     * @param {object} data - Updated data
     * @returns {Promise<object>}
     */
    async updateJob(id, data) {
        return await this.request(`/jobs/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Delete job
     * @param {number} id - Job ID
     * @returns {Promise<object>}
     */
    async deleteJob(id) {
        return await this.request(`/jobs/${id}`, {
            method: 'DELETE'
        });
    }

    async toggleJobTeslim(id) {
        return await this.request(`/jobs/${id}`, { method: 'PATCH' });
    }
    
    // ==================== Payment Methods ====================
    
    /**
     * Create new payment
     * @param {object} data - Payment data
     * @returns {Promise<object>}
     */
    async createPayment(data) {
        return await this.request('/payments', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Update payment
     * @param {number} id - Payment ID
     * @param {object} data - Updated data
     * @returns {Promise<object>}
     */
    async updatePayment(id, data) {
        return await this.request(`/payments/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Delete payment
     * @param {number} id - Payment ID
     * @returns {Promise<object>}
     */
    async deletePayment(id) {
        return await this.request(`/payments/${id}`, {
            method: 'DELETE'
        });
    }
    
    // ==================== Pending Request Methods ====================

    async getPendingRequests(altFirmaId) {
        return await this.request(`/pending?alt_firma_id=${altFirmaId}`, { method: 'GET' });
    }

    async createPendingRequest(data) {
        return await this.request('/pending', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async approvePendingRequest(id) {
        return await this.request(`/pending/${id}/approve`, { method: 'POST' });
    }

    async rejectPendingRequest(id) {
        return await this.request(`/pending/${id}/reject`, { method: 'POST' });
    }

    // ==================== Report Methods ====================
    
    /**
     * Get summary report for date range
     * @param {string} startDate - Start date (Y-m-d format)
     * @param {string} endDate - End date (Y-m-d format)
     * @returns {Promise<object>}
     */
    async getSummaryReport(startDate = null, endDate = null) {
        let endpoint = '/reports/summary';
        
        if (startDate && endDate) {
            endpoint += `?start_date=${startDate}&end_date=${endDate}`;
        }
        
        return await this.request(endpoint, {
            method: 'GET'
        });
    }
}

// Create global API instance
const api = new API();
