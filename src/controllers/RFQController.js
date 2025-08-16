// src/controllers/RFQController.js
const RFQService = require('../services/RFQService');

const sanitizeForLog = (input) => {
    if (typeof input === 'string') {
        return input.replace(/[\r\n\t]/g, ' ').substring(0, 200);
    }
    return String(input).substring(0, 200);
};

const handleErrors = (err, res) => {
    if (err.message.includes('Invalid ID')) {
        return res.status(400).json({ success: false, message: err.message });
    }
    if (err.message.includes('not found')) {
        return res.status(404).json({ success: false, message: err.message });
    }
    if (err.message.includes('Unauthorized')) {
        return res.status(403).json({ success: false, message: err.message });
    }
    if (err.message.includes('required') || err.message.includes('must be')) {
        return res.status(400).json({ success: false, message: err.message });
    }
    console.error('Unexpected error:', sanitizeForLog(err.message));
    res.status(500).json({ success: false, message: "An internal server error occurred." });
};

const sanitizeInput = (input) => {
    if (typeof input === 'string') {
        return input
            .replace(/[<>"'&]/g, '')
            .replace(/javascript:/gi, '')
            .replace(/on\w+=/gi, '')
            .replace(/data:/gi, '')
            .trim()
            .substring(0, 1000);
    }
    return input;
};

class RFQController {
    /**
     * @description Browse and filter RFQs
     * @route GET /api/rfq
     * @access Public
     */
    async browseRFQs(req, res) {
        try {
            const validatedQuery = {
                limit: req.query.limit ? Math.min(Math.max(parseInt(req.query.limit), 1), 100) : 20,
                offset: req.query.offset ? Math.max(parseInt(req.query.offset), 0) : 0,
                category_id: req.query.category_id && !isNaN(req.query.category_id) ? parseInt(req.query.category_id) : undefined
            };
            const rfqs = await RFQService.find(validatedQuery);
            res.status(200).json({ success: true, message: "RFQs fetched successfully", data: rfqs });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Get a single RFQ by its ID
     * @route GET /api/rfq/:id
     * @access Public
     */
    async getRFQById(req, res) {
        try {
            const { id } = req.params;
            if (!id || isNaN(id)) {
                return res.status(400).json({ success: false, message: "Invalid RFQ ID" });
            }
            const rfq = await RFQService.findById(id);
            if (!rfq) {
                return res.status(404).json({ success: false, message: "RFQ not found" });
            }
            res.status(200).json({ success: true, message: "RFQ fetched successfully", data: rfq });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Create a new RFQ
     * @route POST /api/rfq
     * @access Private (Buyer only)
     * @middleware auth, roleGuard('buyer'), validateRFQ
     */
    async createRFQ(req, res) {
        try {
            if (!req.body.title || !req.body.description) {
                return res.status(400).json({ success: false, message: "Title and description are required" });
            }
            
            const rfqData = {
                buyer_id: req.user.id,
                title: sanitizeInput(req.body.title),
                description: sanitizeInput(req.body.description),
                category_id: req.body.category_id ? parseInt(req.body.category_id) : null,
                subcategory_id: req.body.subcategory_id ? parseInt(req.body.subcategory_id) : null,
                budget_min: req.body.budget_min ? parseFloat(req.body.budget_min) : null,
                budget_max: req.body.budget_max ? parseFloat(req.body.budget_max) : null,
                currency: req.body.currency ? String(req.body.currency).substring(0, 3) : 'USD'
            };
            
            const newRFQ = await RFQService.create(rfqData);
            res.status(201).json({ success: true, message: "RFQ created successfully", data: newRFQ });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Update an existing RFQ
     * @route PUT /api/rfq/:id
     * @access Private (Buyer owner only)
     * @middleware auth, roleGuard('buyer'), isRFQOwner
     */
    async updateRFQ(req, res) {
        try {
            const { id } = req.params;
            if (!id || isNaN(id)) {
                return res.status(400).json({ success: false, message: "Invalid RFQ ID" });
            }
            
            const updateData = {
                title: req.body.title ? sanitizeInput(req.body.title) : undefined,
                description: req.body.description ? sanitizeInput(req.body.description) : undefined,
                category_id: req.body.category_id ? parseInt(req.body.category_id) : undefined,
                subcategory_id: req.body.subcategory_id ? parseInt(req.body.subcategory_id) : undefined,
                budget_min: req.body.budget_min ? parseFloat(req.body.budget_min) : undefined,
                budget_max: req.body.budget_max ? parseFloat(req.body.budget_max) : undefined,
                currency: req.body.currency ? String(req.body.currency).substring(0, 3) : undefined
            };
            const userId = req.user.id;
            const updatedRFQ = await RFQService.update(id, updateData, userId);
            res.status(200).json({ success: true, message: "RFQ updated successfully", data: updatedRFQ });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Close an RFQ
     * @route POST /api/rfq/:id/close
     * @access Private (Buyer owner only)
     * @middleware auth, roleGuard('buyer'), isRFQOwner
     */
    async closeRFQ(req, res) {
        try {
            const { id } = req.params;
            if (!id || isNaN(id)) {
                return res.status(400).json({ success: false, message: "Invalid RFQ ID" });
            }
            const userId = req.user.id;
            const closedRFQ = await RFQService.close(id, userId);
            res.status(200).json({ success: true, message: "RFQ closed successfully", data: closedRFQ });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Get related RFQs
     * @route GET /api/rfq/:id/related
     * @access Public
     */
    async getRelatedRFQs(req, res) {
        try {
            const { id } = req.params;
            if (!id || isNaN(id)) {
                return res.status(400).json({ success: false, message: "Invalid RFQ ID" });
            }
            const relatedRFQs = await RFQService.findRelated(id);
            res.status(200).json({ success: true, message: "Related RFQs fetched", data: relatedRFQs });
        } catch (error) {
            handleErrors(error, res);
        }
    }
}

module.exports = new RFQController();