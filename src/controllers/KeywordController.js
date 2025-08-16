// src/controllers/KeywordController.js
const RFQServiceExtended = require('../services/RFQServiceExtended');

const sanitizeForLog = (input) => {
    if (typeof input === 'string') {
        return input.replace(/[\r\n\t]/g, ' ').substring(0, 200);
    }
    return String(input).substring(0, 200);
};

const handleErrors = (err, res) => {
    if (err.message.includes('Invalid') || err.message.includes('must be')) {
        return res.status(400).json({ success: false, message: err.message });
    }
    if (err.message.includes('not found')) {
        return res.status(404).json({ success: false, message: err.message });
    }
    console.error('Unexpected error:', sanitizeForLog(err.message));
    res.status(500).json({ success: false, message: "An internal server error occurred." });
};

class KeywordController {
    /**
     * @description Search RFQs with keywords and filters
     * @route GET /api/rfq/search
     * @access Public
     */
    async searchRFQs(req, res) {
        try {
            const searchParams = {
                query: req.query.q || '',
                keywords: req.query.keywords ? req.query.keywords.split(',') : [],
                category_id: req.query.category_id && !isNaN(req.query.category_id) ? parseInt(req.query.category_id) : undefined,
                budget_min: req.query.budget_min && !isNaN(req.query.budget_min) ? parseFloat(req.query.budget_min) : undefined,
                budget_max: req.query.budget_max && !isNaN(req.query.budget_max) ? parseFloat(req.query.budget_max) : undefined,
                limit: req.query.limit ? Math.min(Math.max(parseInt(req.query.limit), 1), 100) : 20,
                offset: req.query.offset ? Math.max(parseInt(req.query.offset), 0) : 0
            };

            const results = await RFQServiceExtended.searchRFQs(searchParams);
            res.status(200).json({ 
                success: true, 
                message: "Search completed successfully", 
                data: results.data,
                meta: {
                    query: results.query,
                    keywords: results.keywords,
                    total: results.total,
                    limit: searchParams.limit,
                    offset: searchParams.offset
                }
            });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Get trending keywords
     * @route GET /api/keywords/trending
     * @access Public
     */
    async getTrendingKeywords(req, res) {
        try {
            const limit = req.query.limit ? Math.min(Math.max(parseInt(req.query.limit), 1), 50) : 10;
            const keywords = await RFQServiceExtended.getTrendingKeywords(limit);
            
            res.status(200).json({ 
                success: true, 
                message: "Trending keywords fetched successfully", 
                data: keywords 
            });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Get most searched keywords
     * @route GET /api/keywords/most-searched
     * @access Public
     */
    async getMostSearchedKeywords(req, res) {
        try {
            const limit = req.query.limit ? Math.min(Math.max(parseInt(req.query.limit), 1), 50) : 20;
            const keywords = await RFQServiceExtended.getMostSearchedKeywords(limit);
            
            res.status(200).json({ 
                success: true, 
                message: "Most searched keywords fetched successfully", 
                data: keywords 
            });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Get keyword suggestions
     * @route GET /api/keywords/suggestions
     * @access Public
     */
    async getKeywordSuggestions(req, res) {
        try {
            const partial = req.query.q || '';
            const limit = req.query.limit ? Math.min(Math.max(parseInt(req.query.limit), 1), 20) : 10;

            if (!partial || partial.trim().length < 2) {
                return res.status(200).json({ 
                    success: true, 
                    message: "Suggestions fetched successfully", 
                    data: [] 
                });
            }

            const suggestions = await RFQServiceExtended.getKeywordSuggestions(partial, limit);
            
            res.status(200).json({ 
                success: true, 
                message: "Keyword suggestions fetched successfully", 
                data: suggestions 
            });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Add keywords to an RFQ
     * @route POST /api/rfq/:id/keywords
     * @access Private (Buyer owner only)
     */
    async addKeywords(req, res) {
        try {
            const { id } = req.params;
            const { keywords } = req.body;

            if (!keywords || !Array.isArray(keywords) || keywords.length === 0) {
                return res.status(400).json({ 
                    success: false, 
                    message: "Keywords array is required" 
                });
            }

            const result = await RFQServiceExtended.addKeywords(id, keywords);
            res.status(200).json({ 
                success: true, 
                message: result.message 
            });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Remove keywords from an RFQ
     * @route DELETE /api/rfq/:id/keywords
     * @access Private (Buyer owner only)
     */
    async removeKeywords(req, res) {
        try {
            const { id } = req.params;
            const { keywords } = req.body;

            if (!keywords || !Array.isArray(keywords) || keywords.length === 0) {
                return res.status(400).json({ 
                    success: false, 
                    message: "Keywords array is required" 
                });
            }

            const result = await RFQServiceExtended.removeKeywords(id, keywords);
            res.status(200).json({ 
                success: true, 
                message: result.message 
            });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Get keywords for an RFQ
     * @route GET /api/rfq/:id/keywords
     * @access Public
     */
    async getKeywords(req, res) {
        try {
            const { id } = req.params;
            const keywords = await RFQServiceExtended.getKeywords(id);
            
            res.status(200).json({ 
                success: true, 
                message: "Keywords fetched successfully", 
                data: keywords 
            });
        } catch (error) {
            handleErrors(error, res);
        }
    }

    /**
     * @description Find RFQs by keywords
     * @route GET /api/rfq/by-keywords
     * @access Public
     */
    async findByKeywords(req, res) {
        try {
            const keywords = req.query.keywords ? req.query.keywords.split(',') : [];
            const limit = req.query.limit ? Math.min(Math.max(parseInt(req.query.limit), 1), 100) : 20;
            const offset = req.query.offset ? Math.max(parseInt(req.query.offset), 0) : 0;

            if (!keywords || keywords.length === 0) {
                return res.status(400).json({ 
                    success: false, 
                    message: "Keywords parameter is required" 
                });
            }

            const results = await RFQServiceExtended.findByKeywords(keywords, { limit, offset });
            
            res.status(200).json({ 
                success: true, 
                message: "RFQs fetched successfully", 
                data: results.data,
                meta: {
                    keywords: keywords,
                    total: results.total,
                    limit: limit,
                    offset: offset
                }
            });
        } catch (error) {
            handleErrors(error, res);
        }
    }
}

module.exports = new KeywordController();
