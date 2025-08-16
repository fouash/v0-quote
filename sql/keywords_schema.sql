-- Keywords functionality schema for RFQ platform
-- This adds support for keywords, search tracking, and trend analysis

-- Create rfq_keywords table for keyword-RFQ relationships
CREATE TABLE IF NOT EXISTS rfq_keywords (
    id BIGSERIAL PRIMARY KEY,
    rfq_id BIGINT REFERENCES rfqs(id) ON DELETE CASCADE,
    keyword VARCHAR(100) NOT NULL,
    weight INTEGER DEFAULT 1, -- For keyword importance scoring
    created_at TIMESTAMP DEFAULT now(),
    updated_at TIMESTAMP DEFAULT now(),
    UNIQUE(rfq_id, keyword)
);

-- Create search_queries table to track search terms
CREATE TABLE IF NOT EXISTS search_queries (
    id BIGSERIAL PRIMARY KEY,
    query_text VARCHAR(500) NOT NULL,
    user_id UUID REFERENCES users(id),
    ip_address INET,
    results_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT now()
);

-- Create keyword_trends table for trending analysis
CREATE TABLE IF NOT EXISTS keyword_trends (
    id BIGSERIAL PRIMARY KEY,
    keyword VARCHAR(100) NOT NULL,
    search_count INTEGER DEFAULT 1,
    usage_count INTEGER DEFAULT 0, -- Count in RFQs
    trend_score NUMERIC(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT now(),
    UNIQUE(keyword)
);

-- Create full-text search indexes
CREATE INDEX IF NOT EXISTS idx_rfqs_fulltext ON rfqs USING gin(to_tsvector('english', title || ' ' || description));
CREATE INDEX IF NOT EXISTS idx_rfqs_tags_gin ON rfqs USING gin(tags);
CREATE INDEX IF NOT EXISTS idx_rfqs_keywords_gin ON rfq_keywords USING gin(keyword gin_trgm_ops);
CREATE INDEX IF NOT EXISTS idx_search_queries_text ON search_queries USING gin(to_tsvector('english', query_text));
CREATE INDEX IF NOT EXISTS idx_keyword_trends_keyword ON keyword_trends(keyword);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_rfqs_created_at ON rfqs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_rfqs_status ON rfqs(status);
CREATE INDEX IF NOT EXISTS idx_rfqs_category_id ON rfqs(category_id);

-- Function to update keyword trends
CREATE OR REPLACE FUNCTION update_keyword_trends() 
RETURNS TRIGGER AS $$
BEGIN
    -- Update search count for search queries
    IF TG_TABLE_NAME = 'search_queries' THEN
        INSERT INTO keyword_trends (keyword, search_count) 
        VALUES (NEW.query_text, 1)
        ON CONFLICT (keyword) 
        DO UPDATE SET 
            search_count = keyword_trends.search_count + 1,
            trend_score = (keyword_trends.search_count + 1) * 0.7 + keyword_trends.usage_count * 0.3,
            last_updated = now();
    END IF;
    
    -- Update usage count for RFQ keywords
    IF TG_TABLE_NAME = 'rfq_keywords' THEN
        INSERT INTO keyword_trends (keyword, usage_count) 
        VALUES (NEW.keyword, 1)
        ON CONFLICT (keyword) 
        DO UPDATE SET 
            usage_count = keyword_trends.usage_count + 1,
            trend_score = keyword_trends.search_count * 0.7 + (keyword_trends.usage_count + 1) * 0.3,
            last_updated = now();
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Triggers for automatic trend updates
CREATE TRIGGER trigger_search_trends 
AFTER INSERT ON search_queries 
FOR EACH ROW EXECUTE FUNCTION update_keyword_trends();

CREATE TRIGGER trigger_keyword_usage 
AFTER INSERT ON rfq_keywords 
FOR EACH ROW EXECUTE FUNCTION update_keyword_trends();

-- View for trending keywords
CREATE OR REPLACE VIEW trending_keywords AS
SELECT 
    keyword,
    search_count,
    usage_count,
    trend_score,
    last_updated
FROM keyword_trends
WHERE last_updated > now() - interval '7 days'
ORDER BY trend_score DESC
LIMIT 20;

-- View for most searched terms
CREATE OR REPLACE VIEW most_searched_keywords AS
SELECT 
    keyword,
    search_count,
    usage_count,
    trend_score,
    last_updated
FROM keyword_trends
ORDER BY search_count DESC
LIMIT 50;
