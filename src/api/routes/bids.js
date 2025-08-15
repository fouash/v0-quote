// src/api/routes/bids.js
const express = require('express');
const router = express.Router();
const BidController = require('../../controllers/BidController');

// These would be your authentication and authorization middlewares
// const { auth, roleGuard, isBidOwner, canAwardBid } = require('../../middlewares/auth');

// PUT /api/bids/:id - Update a bid
// router.put('/:id', auth, roleGuard('vendor'), isBidOwner, BidController.updateBid);
router.put('/:id', BidController.updateBid);

// POST /api/bids/:id/award - Award a bid
// router.post('/:id/award', auth, roleGuard('buyer'), canAwardBid, BidController.awardBid);
router.post('/:id/award', BidController.awardBid);

// POST /api/bids/:id/retract - Retract a bid
// router.post('/:id/retract', auth, roleGuard('vendor'), isBidOwner, BidController.retractBid);
router.post('/:id/retract', BidController.retractBid);

module.exports = router;
