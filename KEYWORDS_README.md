# Keywords Functionality for RFQ Platform

This document describes the new keywords functionality implemented for the getlancer v3 RFQ platform, including search, trending keywords, and most searched terms.

## Overview

The keywords functionality adds comprehensive search and keyword management capabilities to the RFQ platform, including:

1. **Full-text search** across RFQ titles, descriptions, and keywords
2. **Keyword management** for RFQs
3. **Trending keywords** based on usage frequency
4. **Most searched terms** tracking
5. **Keyword suggestions** with autocomplete

## Database Schema

### New Tables

#### `rfq_keywords`
- Stores keyword-RFQ relationships
- Fields: `id`, `rfq_id`, `keyword`, `weight`, `created_at`, `updated_at`

#### `search_queries`
- Tracks search terms for analytics
- Fields: `id`, `query_text`, `user_id`, `ip_address`, `results_count`, `created_at`

#### `keyword_trends`
- Stores trending keyword data
- Fields: `id`, `keyword`, `search_count`, `usage_count`, `trend_score`, `last_updated`

### Views

#### `trending_keywords`
- Shows keywords trending in the last 7 days
- Ordered by `trend_score`

#### `most_searched_keywords`
- Shows most frequently searched keywords
- Ordered by `search_count`

## API Endpoints

### Keyword Management

#### Get Trending Keywords
```
GET /api/keywords/trending?limit=10
```
Returns trending keywords based on recent usage.

#### Get Most Searched Keywords
```
GET /api/keywords/most-searched?limit=20
```
Returns most frequently searched keywords.

#### Get Keyword Suggestions
```
GET /api/keywords/suggestions?q=web&limit=10
```
Returns keyword suggestions based on partial input.

### RFQ Search

#### Search RFQs
```
GET /api/rfq/search?q=web development&keywords=react,node&category_id=1&limit=20&offset=0
```
Full-text search with keyword filtering.

#### Find RFQs by Keywords
```
GET /api/rfq/by-keywords?keywords=react,node&limit=20&offset=0
```
Find RFQs containing specific keywords.

### RFQ Keyword Management

#### Get RFQ Keywords
```
GET /api/rfq/:id/keywords
```
Get all keywords for a specific RFQ.

#### Add Keywords to RFQ
```
POST /api/rfq/:id/keywords
Content-Type: application/json

{
  "keywords": ["react", "node.js", "frontend"]
}
```

#### Remove Keywords from RFQ
```
DELETE /api/rfq/:id/keywords
Content-Type: application/json

{
  "keywords": ["react", "node.js"]
}
```

## Usage Examples

### Adding Keywords to RFQ
```javascript
// Client-side example
const response = await fetch('/api/rfq/123/keywords', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_TOKEN'
  },
  body: JSON.stringify({
    keywords: ['web development', 'react', 'node.js']
  })
});
```

### Searching RFQs
```javascript
// Search with keywords
const response = await fetch('/api/rfq/search?keywords=react,node&budget_min=1000');
const data = await response.json();
```

### Getting Trending Keywords
```javascript
const response = await fetch('/api/keywords/trending?limit=10');
const trending = await response.json();
```

## Database Setup

Run the SQL migration to set up the keywords schema:

```bash
psql -d your_database -f sql/keywords_schema.sql
```

## Integration with Existing RFQ Creation

When creating a new RFQ, you can now include keywords:

```javascript
// Extended RFQ creation with keywords
const rfqData = {
  title: "Web Development Project",
  description: "Need a full-stack web application",
  buyer_id: "user-uuid",
  category_id: 1,
  budget_min: 5000,
  budget_max: 10000,
  currency: "USD"
};

// Create RFQ first
const rfq = await RFQServiceExtended.create(rfqData);

// Then add keywords
await RFQServiceExtended.addKeywords(rfq.id, ["web development", "react", "node.js"]);
```

## Search Features

### Full-text Search
- Searches across RFQ titles and descriptions
- Uses PostgreSQL full-text search capabilities
- Returns relevance scores

### Keyword Filtering
- Filter RFQs by specific keywords
- Supports multiple keywords with AND logic
- Case-insensitive matching

### Budget and Category Filtering
- Combine keyword search with budget ranges
- Filter by category and subcategory
- Pagination support

## Analytics

### Trending Keywords
- Calculated based on search frequency and usage
- Updated in real-time via database triggers
- 7-day rolling window for trends

### Most Searched Terms
- Tracks all search queries
- Provides insights into user interests
- Can be used for content optimization

## Performance Optimizations

- Full-text search indexes on RFQ content
- GIN indexes for keyword arrays
- Trigram indexes for partial keyword matching
- Materialized views for trending data

## Security Considerations

- CSRF protection for state-changing operations
- Input sanitization for search queries
- Rate limiting on search endpoints
- SQL injection prevention via parameterized queries

## Frontend Integration

### Search Component Example
```javascript
// React component for keyword search
const KeywordSearch = () => {
  const [query, setQuery] = useState('');
  const [keywords, setKeywords] = useState([]);
  const [suggestions, setSuggestions] = useState([]);
  
  const handleSearch = async () => {
    const params = new URLSearchParams({
      q: query,
      keywords: keywords.join(','),
      limit: 20
    });
    
    const response = await fetch(`/api/rfq/search?${params}`);
    const results = await response.json();
    // Handle results
  };
  
  const handleKeywordInput = async (value) => {
    if (value.length > 1) {
      const response = await fetch(`/api/keywords/suggestions?q=${value}`);
      const suggestions = await response.json();
      setSuggestions(suggestions.data);
    }
  };
  
  return (
    <div>
      <input 
        type="text" 
        value={query} 
        onChange={(e) => setQuery(e.target.value)}
        placeholder="Search RFQs..."
      />
      <KeywordSelector 
        keywords={keywords} 
        suggestions={suggestions}
        onKeywordInput={handleKeywordInput}
      />
      <button onClick={handleSearch}>Search</button>
    </div>
  );
};
```

## Testing

### API Testing with curl
```bash
# Test trending keywords
curl http://localhost:3000/api/keywords/trending

# Test search
curl "http://localhost:3000/api/rfq/search?q=web&keywords=react,node"

# Test keyword suggestions
curl "http://localhost:3000/api/keywords/suggestions?q=web"
```

## Future Enhancements

- Machine learning-based keyword recommendations
- Semantic search capabilities
- Advanced filtering options
- Search analytics dashboard
- Keyword clustering and categorization
- Real-time search suggestions
- Search result personalization
