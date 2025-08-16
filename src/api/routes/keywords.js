// src/api/routes/keywords.js
const express = require('express');
const csrf = require('csurf');
const router = express.Router();
const KeywordController = require('../../controllers/KeywordController');

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

// --- Keyword Routes ---

// GET /api/keywords/trending - Get trending keywords
router.get('/trending', asyncHandler(KeywordController.getTrendingKeywords));

// GET /api/keywords/most-searched - Get most searched keywords
router.get('/most-searched', asyncHandler(KeywordController.getMostSearchedKeywords));

// GET /api/keywords/suggestions - Get keyword suggestions
router.get('/suggestions', asyncHandler(KeywordController.getKeywordSuggestions));

// --- RFQ keyword management routes ---

// GET /api/rfq/search - Search RFQs with keywords
router.get('/search', asyncHandler(KeywordController.searchRFQs));

// GET /api/rfq/by-keywords - Find RFQs by keywords
router.get('/by-keywords', asyncHandler(KeywordController.findByKeywords));

// GET /api/rfq/:id/keywords - Get keywords for an RFQ
router.get('/:id/keywords', asyncHandler(KeywordController.getKeywords));

// POST /api/rfq/:id/keywords - Add keywords to an RFQ (CSRF protected)
router.post('/:id/keywords', csrfProtection, asyncHandler(KeywordController.addKeywords));

// DELETE /api/rfq/:id/keywords - Remove keywords from an RFQ (CSRF protected)
router.delete('/:id/keywords', csrfProtection, asyncHandler(KeywordController.removeKeywords));

module.exports = router;
