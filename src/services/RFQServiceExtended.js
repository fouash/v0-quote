// src/services/RFQServiceExtended.js
const db = require('../config/db');
const RFQService = require('./RFQService');

class RFQServiceExtended extends RFQService {
    constructor() {
        super();
    }

    /**
     * Add keywords to an RFQ
     * @param {number} rfqId - RFQ ID
     * @param {string[]} keywords - Array of keywords to add
     * @returns {Promise<Object>} - Result of keyword addition
     */
    async addKeywords(rfqId, keywords) {
        if (!rfqId || isNaN(rfqId)) {
            throw new Error('Invalid RFQ ID provided');
        }
        
        if (!Array.isArray(keywords) || keywords.length === 0) {
            throw new Error('Keywords must be a non-empty array');
        }

        const client = await db.connect();
        
        try {
            await client.query('BEGIN');
            
            // Validate RFQ exists
            const rfqCheck = await client.query('SELECT id FROM rfqs WHERE id = $1', [rfqId]);
            if (rfqCheck.rows.length === 0) {
                throw new Error('RFQ not found');
            }

            // Insert keywords
            const insertPromises = keywords.map(keyword => {
                return client.query(
                    'INSERT INTO rfq_keywords (rfq_id, keyword) VALUES ($1, $2) ON CONFLICT (rfq_id, keyword) DO NOTHING',
                    [rfqId, keyword.trim().toLowerCase()]
                );
            });
            
            await Promise.all(insertPromises);
            await client.query('COMMIT');
            
            return { success: true, message: 'Keywords added successfully' };
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Remove keywords from an RFQ
     * @param {number} rfqId - RFQ ID
     * @param {string[]} keywords - Array of keywords to remove
     * @returns {Promise<Object>} - Result of keyword removal
     */
    async removeKeywords(rfqId, keywords) {
        if (!rfqId || isNaN(rfqId)) {
            throw new Error('Invalid RFQ ID provided');
        }
        
        if (!Array.isArray(keywords) || keywords.length === 0) {
            throw new Error('Keywords must be a non-empty array');
        }

        const client = await db.connect();
        
        try {
            await client.query('BEGIN');
            
            // Use parameterized query with ANY() to prevent SQL injection
            await client.query(
                'DELETE FROM rfq_keywords WHERE rfq_id = $1 AND keyword = ANY($2)',
                [rfqId, keywords.map(k => k.trim().toLowerCase())]
            );
            await client.query('COMMIT');
            
            return { success: true, message: 'Keywords removed successfully' };
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Get keywords for an RFQ
     * @param {number} rfqId - RFQ ID
     * @returns {Promise<string[]>} - Array of keywords
     */
    async getKeywords(rfqId) {
        if (!rfqId || isNaN(rfqId)) {
            throw new Error('Invalid RFQ ID provided');
        }
        
        const { rows } = await db.query(
            'SELECT keyword FROM rfq_keywords WHERE rfq_id = $1 ORDER BY keyword',
            [rfqId]
        );
        
        return rows.map(row => row.keyword);
    }

    /**
     * Search RFQs with keywords
     * @param {Object} searchParams - Search parameters
     * @returns {Promise<Object>} - Search results with metadata
     */
    async searchRFQs(searchParams = {}) {
        const {
            query = '',
            keywords = [],
            category_id,
            budget_min,
            budget_max,
            limit = 20,
            offset = 0
        } = searchParams;

        const client = await db.connect();
        
        try {
            let sql = `
                SELECT DISTINCT r.*, 
                       array_agg(DISTINCT k.keyword) FILTER (WHERE k.keyword IS NOT NULL) as keywords,
                       ts_rank(to_tsvector('english', r.title || ' ' || r.description), plainto_tsquery('english', $1)) as relevance
                FROM rfqs r
                LEFT JOIN rfq_keywords k ON r.id = k.rfq_id
                WHERE 1=1
            `;
            
            const params = [query];
            let paramIndex = 2;

            // Add full-text search
            if (query && query.trim()) {
                sql += ` AND to_tsvector('english', r.title || ' ' || r.description) @@ plainto_tsquery('english', $1)`;
            }

            // Add keyword search
            if (keywords && keywords.length > 0) {
                sql += ` AND k.keyword = ANY($${paramIndex})`;
                params.push(keywords.map(k => String(k).toLowerCase().substring(0, 50)));
                paramIndex++;
            }

            // Add category filter
            if (category_id && !isNaN(category_id)) {
                sql += ` AND r.category_id = $${paramIndex}`;
                params.push(parseInt(category_id));
                paramIndex++;
            }

            // Add budget filters
            if (budget_min && !isNaN(budget_min)) {
                sql += ` AND r.budget_min >= $${paramIndex}`;
                params.push(parseFloat(budget_min));
                paramIndex++;
            }

            if (budget_max && !isNaN(budget_max)) {
                sql += ` AND r.budget_max <= $${paramIndex}`;
                params.push(parseFloat(budget_max));
                paramIndex++;
            }

            // Add ordering and pagination with performance optimization
            sql += ` GROUP BY r.id ORDER BY relevance DESC, r.created_at DESC LIMIT $${paramIndex} OFFSET $${paramIndex + 1}`;
            const validLimit = Math.min(Math.max(parseInt(limit) || 20, 1), 50); // Reduced max limit
            const validOffset = Math.max(parseInt(offset) || 0, 0);
            
            // Prevent deep pagination for performance
            if (validOffset > 1000) {
                throw new Error('Pagination offset too large. Please use more specific filters.');
            }
            
            params.push(validLimit, validOffset);

            const { rows } = await client.query(sql, params);
            
            // Track search query
            if (query && query.trim()) {
                await this.trackSearchQuery(query.trim());
            }

            return {
                data: rows,
                total: rows.length,
                query: query,
                keywords: keywords
            };
        } catch (error) {
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Track search query
     * @param {string} queryText - Search query text
     * @returns {Promise<Object>} - Tracking result
     */
    async trackSearchQuery(queryText) {
        if (!queryText || queryText.trim().length < 2) {
            return { success: false, message: 'Query too short' };
        }

        try {
            await db.query(
                'INSERT INTO search_queries (query_text) VALUES ($1)',
                [queryText.trim().toLowerCase()]
            );
            
            // Update keyword trends
            const keywords = queryText.trim().toLowerCase().split(/\s+/);
            for (const keyword of keywords) {
                if (keyword.length > 2) {
                    await db.query(
                        `INSERT INTO keyword_trends (keyword, search_count) 
                         VALUES ($1, 1) 
                         ON CONFLICT (keyword) 
                         DO UPDATE SET 
                            search_count = keyword_trends.search_count + 1,
                            last_updated = now()`,
                        [keyword]
                    );
                }
            }
            
            return { success: true, message: 'Search query tracked' };
        } catch (error) {
            throw error;
        }
    }

    /**
     * Get trending keywords
     * @param {number} limit - Number of keywords to return
     * @returns {Promise<Object[]>} - Array of trending keywords
     */
    async getTrendingKeywords(limit = 10) {
        try {
            const { rows } = await db.query(
                `SELECT keyword, search_count, usage_count, trend_score 
                 FROM keyword_trends 
                 WHERE last_updated > now() - interval '7 days'
                 ORDER BY trend_score DESC, search_count DESC
                 LIMIT $1`,
                [Math.min(Math.max(parseInt(limit) || 10, 1), 50)]
            );
            
            return rows;
        } catch (error) {
            throw error;
        }
    }

    /**
     * Get most searched keywords
     * @param {number} limit - Number of keywords to return
     * @returns {Promise<Object[]>} - Array of most searched keywords
     */
    async getMostSearchedKeywords(limit = 20) {
        try {
            const { rows } = await db.query(
                `SELECT keyword, search_count, usage_count, trend_score 
                 FROM keyword_trends 
                 ORDER BY search_count DESC
                 LIMIT $1`,
                [Math.min(Math.max(parseInt(limit) || 20, 1), 50)]
            );
            
            return rows;
        } catch (error) {
            throw error;
        }
    }

    /**
     * Get keyword suggestions based on partial input
     * @param {string} partial - Partial keyword input
     * @param {number} limit - Number of suggestions to return
     * @returns {Promise<string[]>} - Array of keyword suggestions
     */
    async getKeywordSuggestions(partial, limit = 10) {
        if (!partial || partial.trim().length < 2) {
            return [];
        }

        try {
            const { rows } = await db.query(
                `SELECT DISTINCT keyword 
                 FROM keyword_trends 
                 WHERE keyword ILIKE $1 
                 ORDER BY search_count DESC, keyword ASC
                 LIMIT $2`,
                [`%${partial.trim().toLowerCase()}%`, Math.min(Math.max(parseInt(limit) || 10, 1), 20)]
            );
            
            return rows.map(row => row.keyword);
        } catch (error) {
            throw error;
        }
    }

    /**
     * Get RFQs with specific keywords
     * @param {string[]} keywords - Array of keywords to filter by
     * @param {Object} options - Additional filtering options
     * @returns {Promise<Object>} - RFQs with matching keywords
     */
    async findByKeywords(keywords, options = {}) {
        if (!Array.isArray(keywords) || keywords.length === 0) {
            return { data: [], total: 0 };
        }

        const { limit = 20, offset = 0 } = options;

        try {
            const sql = `
                SELECT DISTINCT r.*, 
                       array_agg(DISTINCT k.keyword) FILTER (WHERE k.keyword IS NOT NULL) as keywords
                FROM rfqs r
                INNER JOIN rfq_keywords k ON r.id = k.rfq_id
                WHERE k.keyword = ANY($1)
                GROUP BY r.id
                ORDER BY r.created_at DESC
                LIMIT $2 OFFSET $3
            `;
            
            const params = [keywords.map(k => String(k).toLowerCase().substring(0, 50)), 
                           Math.min(Math.max(parseInt(limit) || 20, 1), 100), 
                           Math.max(parseInt(offset) || 0, 0)];
            
            const { rows } = await db.query(sql, params);
            
            return {
                data: rows,
                total: rows.length
            };
        } catch (error) {
            throw error;
        }
    }
}

module.exports = new RFQServiceExtended();
