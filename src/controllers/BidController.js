// src/controllers/BidController.js

const sanitizeForLog = (input) => {
    if (typeof input === 'string') {
        return input.replace(/[\r\n\t]/g, ' ').substring(0, 200);
    }
    return String(input).substring(0, 200);
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

class BidController {
    /**
     * @description Create a new bid for an RFQ
     * @route POST /api/rfq/:rfqId/bids
     * @access Private (Vendor only)
     * @middleware auth, roleGuard('vendor')
     */
    async createBid(req, res) {
        try {
            const { rfqId } = req.params;
            if (!rfqId || isNaN(rfqId)) {
                return res.status(400).json({ success: false, message: "Invalid RFQ ID" });
            }
            
            const { amount, description, delivery_time } = req.body;
            if (!amount || !description) {
                return res.status(400).json({ success: false, message: "Amount and description are required" });
            }
            
            const numAmount = parseFloat(amount);
            if (isNaN(numAmount) || numAmount <= 0) {
                return res.status(400).json({ success: false, message: "Amount must be a positive number" });
            }
            
            const bidData = {
                rfq_id: rfqId,
                vendor_id: req.user.id,
                amount: numAmount,
                description: sanitizeInput(description),
                delivery_time: delivery_time ? parseInt(delivery_time) : null
            };
            
            // TODO: Implement BidService.create(bidData)
            res.status(501).json({ success: false, message: "Bid creation not yet implemented" });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    async listBidsForRFQ(req, res) {
        try {
            const { rfqId } = req.params;
            if (!rfqId || isNaN(rfqId)) {
                return res.status(400).json({ success: false, message: "Invalid RFQ ID" });
            }
            
            // TODO: Implement BidService.findByRfq(rfqId, req.user)
            res.status(501).json({ success: false, message: "Bid listing not yet implemented" });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    async updateBid(req, res) {
        try {
            const { id } = req.params;
            if (!id || isNaN(id)) {
                return res.status(400).json({ success: false, message: "Invalid bid ID" });
            }
            
            const { amount, description, delivery_time } = req.body;
            if (!amount && !description && !delivery_time) {
                return res.status(400).json({ success: false, message: "At least one field must be provided for update" });
            }
            
            // TODO: Implement BidService.update(id, updateData, req.user.id)
            res.status(501).json({ success: false, message: "Bid update not yet implemented" });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    async awardBid(req, res) {
        try {
            const { id } = req.params;
            if (!id || isNaN(id)) {
                return res.status(400).json({ success: false, message: "Invalid bid ID" });
            }
            
            // TODO: Implement BidService.award(id, req.user.id)
            res.status(501).json({ success: false, message: "Bid awarding not yet implemented" });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    async retractBid(req, res) {
        try {
            const { id } = req.params;
            if (!id || isNaN(id)) {
                return res.status(400).json({ success: false, message: "Invalid bid ID" });
            }
            
            // TODO: Implement BidService.retract(id, req.user.id)
            res.status(501).json({ success: false, message: "Bid retraction not yet implemented" });
        } catch (error) {
            handleErrors(error, res);
        }
    }
}

module.exports = new BidController();
