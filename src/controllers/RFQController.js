// src/controllers/RFQController.js

const RFQService = require('../services/RFQService');

// A simple error handler for demonstration
const handleErrors = (err, res) => {
    console.error(err);
    res.status(500).json({ success: false, message: "An internal server error occurred." });
};

class RFQController {
    /**
     * @description Browse and filter RFQs
     * @route GET /api/rfq
     * @access Public
     */
    async browseRFQs(req, res) {
        try {
            const rfqs = await RFQService.find(req.query);
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
            const rfqData = req.body;
            // In a real app, the user's ID would be available from the auth middleware
            // For now, we'll assume it's passed in the body or hardcoded for testing
            // e.g., rfqData.buyer_id = req.user.id;
            
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
            const updateData = req.body;
            // const userId = req.user.id; // from auth middleware
            const updatedRFQ = await RFQService.update(id, updateData /*, userId */);
            if (!updatedRFQ) {
                return res.status(404).json({ success: false, message: "RFQ not found or user not authorized" });
            }
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
            // const userId = req.user.id; // from auth middleware
            const closedRFQ = await RFQService.close(id /*, userId */);
            if (!closedRFQ) {
                return res.status(404).json({ success: false, message: "RFQ not found or user not authorized" });
            }
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
            const relatedRFQs = await RFQService.findRelated(id);
            res.status(200).json({ success: true, message: "Related RFQs fetched", data: relatedRFQs });
        } catch (error) {
            handleErrors(error, res);
        }
    }
}

module.exports = new RFQController();