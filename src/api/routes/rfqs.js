// src/api/routes/rfqs.js
const express = require('express');
const router = express.Router();
const RFQController = require('../../controllers/RFQController');
const BidController = require('../../controllers/BidController');

// These would be your authentication and authorization middlewares
// const { auth, roleGuard } = require('../../middlewares/auth');

// --- RFQ Routes ---

// GET /api/rfq - Browse RFQs
router.get('/', RFQController.browseRFQs);

// GET /api/rfq/:id - Get a single RFQ
router.get('/:id', RFQController.getRFQById);

// POST /api/rfq - Create a new RFQ
// Example of protecting a route:
// router.post('/', auth, roleGuard('buyer'), RFQController.createRFQ);
router.post('/', RFQController.createRFQ);

// PUT /api/rfq/:id - Update an RFQ
router.put('/:id', RFQController.updateRFQ);

// POST /api/rfq/:id/close - Close an RFQ
router.post('/:id/close', RFQController.closeRFQ);

// GET /api/rfq/:id/related - Get related RFQs
router.get('/:id/related', RFQController.getRelatedRFQs);


// --- Nested Bid Routes ---

// POST /api/rfq/:rfqId/bids - Create a new bid for an RFQ
router.post('/:rfqId/bids', BidController.createBid);

// GET /api/rfq/:rfqId/bids - List bids for an RFQ
router.get('/:rfqId/bids', BidController.listBidsForRFQ);


module.exports = router;
