// src/services/RFQService.js
const db = require('../config/db');

class RFQService {
    validateRFQData(data) {
        const { title, description, budget_min, budget_max } = data;
        if (!title || title.trim().length < 3) {
            throw new Error('Title must be at least 3 characters long');
        }
        if (!description || description.trim().length < 10) {
            throw new Error('Description must be at least 10 characters long');
        }
        if (budget_min && budget_max && budget_min > budget_max) {
            throw new Error('Minimum budget cannot be greater than maximum budget');
        }
    }

    async find(query = {}) {
        const { limit = 20, offset = 0, category_id } = query;
        const safeLimit = Math.min(Math.max(parseInt(limit) || 20, 1), 100);
        const safeOffset = Math.max(parseInt(offset) || 0, 0);
        
        let sql = 'SELECT * FROM rfqs';
        const params = [];
        
        if (category_id && !isNaN(category_id)) {
            sql += ' WHERE category_id = $1';
            params.push(parseInt(category_id));
        }
        
        sql += ` ORDER BY created_at DESC LIMIT $${params.length + 1} OFFSET $${params.length + 2}`;
        params.push(safeLimit, safeOffset);
        
        const { rows } = await db.query(sql, params);
        return rows;
    }

    async findById(id) {
        if (!id || isNaN(id)) {
            throw new Error('Invalid ID provided');
        }
        const { rows } = await db.query('SELECT * FROM rfqs WHERE id = $1', [id]);
        return rows.length > 0 ? rows[0] : null;
    }

    async create(rfqData) {
        this.validateRFQData(rfqData);
        const { title, description, buyer_id, category_id, subcategory_id, budget_min, budget_max, currency } = rfqData;
        
        if (!buyer_id) {
            throw new Error('Buyer ID is required');
        }
        
        try {
            const { rows } = await db.query(
                'INSERT INTO rfqs (title, description, buyer_id, category_id, subcategory_id, budget_min, budget_max, currency) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING *',
                [title, description, buyer_id, category_id, subcategory_id, budget_min, budget_max, currency]
            );
            return rows[0];
        } catch (error) {
            if (error.code === '23505') {
                throw new Error('Duplicate RFQ entry');
            }
            if (error.code === '23503') {
                throw new Error('Invalid category or buyer reference');
            }
            throw new Error('Database error: ' + error.message);
        }
    }

    async update(id, rfqData, userId) {
        if (!id || isNaN(id)) {
            throw new Error('Invalid ID provided');
        }
        
        this.validateRFQData(rfqData);
        const { title, description, category_id, subcategory_id, budget_min, budget_max, currency } = rfqData;
        
        try {
            const { rows } = await db.query(
                'UPDATE rfqs SET title = $1, description = $2, category_id = $3, subcategory_id = $4, budget_min = $5, budget_max = $6, currency = $7, updated_at = now() WHERE id = $8 AND buyer_id = $9 RETURNING *',
                [title, description, category_id, subcategory_id, budget_min, budget_max, currency, id, userId]
            );
            if (rows.length === 0) {
                throw new Error('RFQ not found or unauthorized');
            }
            return rows[0];
        } catch (error) {
            if (error.message.includes('not found')) {
                throw error;
            }
            throw new Error('Database error: ' + error.message);
        }
    }

    async close(id, userId) {
        if (!id || isNaN(id)) {
            throw new Error('Invalid ID provided');
        }
        
        try {
            const { rows } = await db.query(
                'UPDATE rfqs SET status = $1, updated_at = now() WHERE id = $2 AND buyer_id = $3 RETURNING *',
                ['closed', id, userId]
            );
            if (rows.length === 0) {
                throw new Error('RFQ not found or unauthorized');
            }
            return rows[0];
        } catch (error) {
            if (error.message.includes('not found')) {
                throw error;
            }
            throw new Error('Database error: ' + error.message);
        }
    }

    async findRelated(id) {
        if (!id || isNaN(id)) {
            throw new Error('Invalid ID provided');
        }
        
        try {
            const { rows } = await db.query(
                `SELECT r2.* FROM rfqs r1 
                 JOIN rfqs r2 ON r1.category_id = r2.category_id 
                 WHERE r1.id = $1 AND r2.id != $1 AND r2.status = 'open' 
                 ORDER BY r2.created_at DESC LIMIT 10`,
                [id]
            );
            return rows;
        } catch (error) {
            throw new Error('Database error: ' + error.message);
        }
    }
}

module.exports = RFQService;
