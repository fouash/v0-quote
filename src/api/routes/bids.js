// src/api/routes/bids.js
const express = require('express');
const csrf = require('csurf');
const router = express.Router();
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

// PUT /api/bids/:id - Update a bid (CSRF protected, vendor only)
router.put('/:id', csrfProtection, requireRole('vendor'), asyncHandler(BidController.updateBid));

// POST /api/bids/:id/award - Award a bid (CSRF protected, buyer only)
router.post('/:id/award', csrfProtection, requireRole('buyer'), asyncHandler(BidController.awardBid));

// POST /api/bids/:id/retract - Retract a bid (CSRF protected, vendor only)
router.post('/:id/retract', csrfProtection, requireRole('vendor'), asyncHandler(BidController.retractBid));

module.exports = router;
