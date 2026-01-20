/**
 * Data2Rest JavaScript Client SDK
 * 
 * Official client for interacting with Data2Rest APIs.
 * Supports: v1/v2, Bulk Operations, Caching, and Query Builder.
 * 
 * @version 1.0.0
 * @license MIT
 */

class Data2RestClient {
    /**
     * @param {Object} config Configuration object
     * @param {string} config.baseUrl - Base URL of your API (e.g., 'https://api.example.com/api')
     * @param {string} config.apiKey - Your API Key
     * @param {string} [config.version='v2'] - API Version ('v1' or 'v2')
     */
    constructor(config) {
        this.baseUrl = config.baseUrl.replace(/\/$/, "");
        this.apiKey = config.apiKey;
        this.version = config.version || 'v2';
    }

    /**
     * Helper to build headers
     */
    _headers() {
        const headers = {
            'X-API-KEY': this.apiKey,
            'Content-Type': 'application/json'
        };
        if (this.version === 'v2') {
            headers['Accept'] = 'application/vnd.data2rest.v2+json';
        }
        return headers;
    }

    /**
     * Database Resource Accessor
     * @param {number} dbId Database ID
     * @returns {DatabaseResource}
     */
    database(dbId) {
        return new DatabaseResource(this, dbId);
    }
}

class DatabaseResource {
    constructor(client, dbId) {
        this.client = client;
        this.dbId = dbId;
    }

    /**
     * Table Resource Accessor
     * @param {string} tableName Table Name
     */
    table(tableName) {
        return new TableResource(this.client, this.dbId, tableName);
    }
}

class TableResource {
    constructor(client, dbId, tableName) {
        this.client = client;
        this.endpoint = `${client.baseUrl}/db/${dbId}/${tableName}`;
    }

    /**
     * Get records
     * @param {Object} params Query parameters (limit, offset, sort, filters)
     */
    async get(params = {}) {
        const query = new URLSearchParams(params).toString();
        const url = `${this.endpoint}?${query}`;

        const response = await fetch(url, { headers: this.client._headers() });
        return this._handleResponse(response);
    }

    /**
     * Get single record by ID
     * @param {number} id Record ID
     */
    async find(id) {
        const url = `${this.endpoint}/${id}`;
        const response = await fetch(url, { headers: this.client._headers() });
        return this._handleResponse(response);
    }

    /**
     * Create record
     * @param {Object} data Record data
     */
    async create(data) {
        const response = await fetch(this.endpoint, {
            method: 'POST',
            headers: this.client._headers(),
            body: JSON.stringify(data)
        });
        return this._handleResponse(response);
    }

    /**
     * Update record
     * @param {number} id Record ID
     * @param {Object} data Data to update
     */
    async update(id, data) {
        const url = `${this.endpoint}/${id}`;
        const response = await fetch(url, {
            method: 'PUT', // or PATCH
            headers: this.client._headers(),
            body: JSON.stringify(data)
        });
        return this._handleResponse(response);
    }

    /**
     * Delete record
     * @param {number} id Record ID
     */
    async delete(id) {
        const url = `${this.endpoint}/${id}`;
        const response = await fetch(url, {
            method: 'DELETE',
            headers: this.client._headers()
        });
        return this._handleResponse(response);
    }

    /**
     * Bulk Operations
     * @param {Array} operations List of operations
     */
    async bulk(operations) {
        const url = `${this.endpoint}/bulk`;
        const response = await fetch(url, {
            method: 'POST',
            headers: this.client._headers(),
            body: JSON.stringify({ operations })
        });
        return this._handleResponse(response);
    }

    async _handleResponse(response) {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'API Request Failed');
        }
        return data;
    }
}

if (typeof module !== 'undefined') {
    module.exports = Data2RestClient;
}
