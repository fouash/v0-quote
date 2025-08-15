// src/api/routes/rfqs.js
const express = require('express');
const csrf = require('csurf');
const router = express.Router();
const RFQController = require('../../controllers/RFQController');
const BidController = require('../../controllers/BidController');

// CSRF protection for state-changing operations
const csrfProtection = csrf({ cookie: true });

// Error handling middleware
const asyncHandler = (fn) => (req, res, next) => {
    Promise.resolve(fn(req, res, next)).catch(next);
};

// --- RFQ Routes ---

// GET /api/rfq - Browse RFQs (no CSRF needed for GET)
router.get('/', asyncHandler(RFQController.browseRFQs));

// GET /api/rfq/:id - Get a single RFQ
router.get('/:id', asyncHandler(RFQController.getRFQById));

// POST /api/rfq - Create a new RFQ (CSRF protected)
router.post('/', csrfProtection, asyncHandler(RFQController.createRFQ));

// PUT /api/rfq/:id - Update an RFQ (CSRF protected)
router.put('/:id', csrfProtection, asyncHandler(RFQController.updateRFQ));

// POST /api/rfq/:id/close - Close an RFQ (CSRF protected)
router.post('/:id/close', csrfProtection, asyncHandler(RFQController.closeRFQ));

// GET /api/rfq/:id/related - Get related RFQs
router.get('/:id/related', asyncHandler(RFQController.getRelatedRFQs));

// --- Nested Bid Routes ---

// POST /api/rfq/:rfqId/bids - Create a new bid for an RFQ (CSRF protected)
router.post('/:rfqId/bids', csrfProtection, asyncHandler(BidController.createBid));

// GET /api/rfq/:rfqId/bids - List bids for an RFQ
router.get('/:rfqId/bids', asyncHandler(BidController.listBidsForRFQ));


module.exports = router;
