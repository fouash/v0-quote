// src/api/routes/rfqs.js
const express = require('express');
const csrf = require('csurf');
const router = express.Router();
const RFQController = require('../../controllers/RFQController');
const BidController = require('../../controllers/BidController');
const { requireRole } = require('../../middleware/auth');

// CSRF protection for state-changing operations
const csrfProtection = csrf({ 
  cookie: {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'strict',
    path: '/',
    domain: process.env.COOKIE_DOMAIN || undefined
  }
});

// Error handling middleware
const asyncHandler = (fn) => (req, res, next) => {
    Promise.resolve(fn(req, res, next)).catch(next);
};

// --- RFQ Routes ---

// GET /api/rfq - Browse RFQs (no CSRF needed for GET)
router.get('/', asyncHandler(RFQController.browseRFQs));

// GET /api/rfq/:id - Get a single RFQ
router.get('/:id', asyncHandler(RFQController.getRFQById));

// POST /api/rfq - Create a new RFQ (CSRF protected, buyer only)
router.post('/', csrfProtection, requireRole('buyer'), asyncHandler(RFQController.createRFQ));

// PUT /api/rfq/:id - Update an RFQ (CSRF protected, buyer only)
router.put('/:id', csrfProtection, requireRole('buyer'), asyncHandler(RFQController.updateRFQ));

// POST /api/rfq/:id/close - Close an RFQ (CSRF protected, buyer only)
router.post('/:id/close', csrfProtection, requireRole('buyer'), asyncHandler(RFQController.closeRFQ));

// GET /api/rfq/:id/related - Get related RFQs
router.get('/:id/related', asyncHandler(RFQController.getRelatedRFQs));

// --- Nested Bid Routes ---

// POST /api/rfq/:rfqId/bids - Create a new bid for an RFQ (CSRF protected, vendor only)
router.post('/:rfqId/bids', csrfProtection, requireRole('vendor'), asyncHandler(BidController.createBid));

// GET /api/rfq/:rfqId/bids - List bids for an RFQ
router.get('/:rfqId/bids', asyncHandler(BidController.listBidsForRFQ));


module.exports = router;
