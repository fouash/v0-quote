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
        let sql = 'SELECT * FROM rfqs';
        const params = [];
        
        if (category_id) {
            sql += ' WHERE category_id = $1';
            params.push(category_id);
        }
        
        sql += ' ORDER BY created_at DESC LIMIT $' + (params.length + 1) + ' OFFSET $' + (params.length + 2);
        params.push(limit, offset);
        
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
        
        const { rows } = await db.query(
            'INSERT INTO rfqs (title, description, buyer_id, category_id, subcategory_id, budget_min, budget_max, currency) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING *',
            [title, description, buyer_id, category_id, subcategory_id, budget_min, budget_max, currency]
        );
        return rows[0];
    }

    async update(id, rfqData, userId) {
        if (!id || isNaN(id)) {
            throw new Error('Invalid ID provided');
        }
        
        // Verify ownership
        const existing = await this.findById(id);
        if (!existing) {
            throw new Error('RFQ not found');
        }
        if (existing.buyer_id !== userId) {
            throw new Error('Unauthorized: You can only update your own RFQs');
        }
        
        this.validateRFQData(rfqData);
        const { title, description, category_id, subcategory_id, budget_min, budget_max, currency } = rfqData;
        const { rows } = await db.query(
            'UPDATE rfqs SET title = $1, description = $2, category_id = $3, subcategory_id = $4, budget_min = $5, budget_max = $6, currency = $7, updated_at = now() WHERE id = $8 AND buyer_id = $9 RETURNING *',
            [title, description, category_id, subcategory_id, budget_min, budget_max, currency, id, userId]
        );
        return rows.length > 0 ? rows[0] : null;
    }

    async close(id, userId) {
        if (!id || isNaN(id)) {
            throw new Error('Invalid ID provided');
        }
        
        // Verify ownership
        const existing = await this.findById(id);
        if (!existing) {
            throw new Error('RFQ not found');
        }
        if (existing.buyer_id !== userId) {
            throw new Error('Unauthorized: You can only close your own RFQs');
        }
        
        const { rows } = await db.query(
            'UPDATE rfqs SET status = $1, updated_at = now() WHERE id = $2 AND buyer_id = $3 RETURNING *',
            ['closed', id, userId]
        );
        return rows.length > 0 ? rows[0] : null;
    }

    async findRelated(id) {
        // This is a simplified version of the related algorithm from the blueprint
        const rfq = await this.findById(id);
        if (!rfq) return [];

        const { rows } = await db.query(
            'SELECT * FROM rfqs WHERE category_id = $1 AND id != $2 AND status = \'open\' ORDER BY created_at DESC LIMIT 10',
            [rfq.category_id, id]
        );
        return rows;
    }
}

module.exports = new RFQService();
