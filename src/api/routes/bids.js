// src/api/routes/bids.js
const express = require('express');
const csrf = require('csurf');
const router = express.Router();
const BidController = require('../../controllers/BidController');

// CSRF protection for state-changing operations
const csrfProtection = csrf({ 
  cookie: {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'strict',
    path: '/'
  }
});

// Error handling middleware
const asyncHandler = (fn) => (req, res, next) => {
    Promise.resolve(fn(req, res, next)).catch(next);
};

// PUT /api/bids/:id - Update a bid (CSRF protected)
router.put('/:id', csrfProtection, asyncHandler(BidController.updateBid));

// POST /api/bids/:id/award - Award a bid (CSRF protected)
router.post('/:id/award', csrfProtection, asyncHandler(BidController.awardBid));

// POST /api/bids/:id/retract - Retract a bid (CSRF protected)
router.post('/:id/retract', csrfProtection, asyncHandler(BidController.retractBid));

module.exports = router;
