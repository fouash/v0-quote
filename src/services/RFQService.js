// src/services/RFQService.js
const db = require('../config/db');

class RFQService {
    async find(query) {
        // Basic find all for now. Filtering and pagination would be added here.
        const { rows } = await db.query('SELECT * FROM rfqs ORDER BY created_at DESC', []);
        return rows;
    }

    async findById(id) {
        const { rows } = await db.query('SELECT * FROM rfqs WHERE id = $1', [id]);
        return rows[0];
    }

    async create(rfqData) {
        // In a real app, you would get the buyer_id from the authenticated user (e.g., req.user.id)
        const { title, description, buyer_id, category_id, subcategory_id, budget_min, budget_max, currency } = rfqData;
        const { rows } = await db.query(
            'INSERT INTO rfqs (title, description, buyer_id, category_id, subcategory_id, budget_min, budget_max, currency) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING *',
            [title, description, buyer_id, category_id, subcategory_id, budget_min, budget_max, currency]
        );
        return rows[0];
    }

    async update(id, rfqData, userId) {
        // Add logic to verify that the userId owns this RFQ before updating
        const { title, description, category_id, subcategory_id, budget_min, budget_max, currency } = rfqData;
        const { rows } = await db.query(
            'UPDATE rfqs SET title = $1, description = $2, category_id = $3, subcategory_id = $4, budget_min = $5, budget_max = $6, currency = $7, updated_at = now() WHERE id = $8 RETURNING *',
            [title, description, category_id, subcategory_id, budget_min, budget_max, currency, id]
        );
        return rows[0];
    }

    async close(id, userId) {
        // Add logic to verify that the userId owns this RFQ before closing
        const { rows } = await db.query(
            'UPDATE rfqs SET status = \'closed\', updated_at = now() WHERE id = $1 RETURNING *',
            [id]
        );
        return rows[0];
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
