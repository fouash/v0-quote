# Category System Deployment Guide

## Overview

This guide provides comprehensive instructions for deploying the advanced category and organization matching system for the Getlancer Quote platform. The system includes 8 main business categories with 40 subcategories, intelligent organization matching, and capability-based quote routing.

## System Components

### Database Schema
- **Categories**: Main business categories (8 categories)
- **Subcategories**: Detailed service categories (40 subcategories)
- **Organization Categories**: Organization expertise in main categories
- **Organization Subcategories**: Detailed organization capabilities
- **Service Capabilities**: Specific skills and technologies
- **Category Matching Scores**: Pre-calculated matching scores
- **Quote Categories**: Quote categorization and requirements

### Backend Models
- [`Category.php`](../server/php/Slim/lib/Models/Category.php) - Main category model
- [`Subcategory.php`](../server/php/Slim/lib/Models/Subcategory.php) - Subcategory model
- [`OrganizationCategory.php`](../server/php/Slim/lib/Models/OrganizationCategory.php) - Organization-category relationships
- [`OrganizationSubcategory.php`](../server/php/Slim/lib/Models/OrganizationSubcategory.php) - Organization-subcategory specializations
- [`ServiceCapability.php`](../server/php/Slim/lib/Models/ServiceCapability.php) - Detailed capability tracking
- [`CategoryMatchingScore.php`](../server/php/Slim/lib/Models/CategoryMatchingScore.php) - Matching score management
- [`QuoteCategory.php`](../server/php/Slim/lib/Models/QuoteCategory.php) - Quote categorization
- [`QuoteMatchingEngine.php`](../server/php/Slim/lib/Models/QuoteMatchingEngine.php) - Intelligent matching algorithm

### API Layer
- [`CategoryAPI.php`](../server/php/Slim/lib/Models/CategoryAPI.php) - API endpoints
- [`categories.php`](../server/php/Slim/lib/routes/categories.php) - Route definitions

## Deployment Steps

### 1. Database Setup

#### Step 1.1: Create Database Schema
```bash
# Execute the category schema
psql -U your_username -d getlancer_db -f sql/categories_schema.sql
```

#### Step 1.2: Populate Categories and Subcategories
```bash
# Populate with business categories
psql -U your_username -d getlancer_db -f sql/populate_categories.sql
```

#### Step 1.3: Verify Database Setup
```sql
-- Check categories
SELECT id, name, subcategory_count FROM categories WHERE is_active = true ORDER BY display_order;

-- Check subcategories
SELECT c.name as category, s.name as subcategory, s.complexity_level 
FROM subcategories s 
JOIN categories c ON s.category_id = c.id 
WHERE s.is_active = true 
ORDER BY c.display_order, s.display_order;
```

### 2. Backend Integration

#### Step 2.1: Include Route Files
Add to your main routes file or bootstrap:
```php
// Include category routes
require_once __DIR__ . '/routes/categories.php';
```

#### Step 2.2: Update Composer Autoloader
```bash
composer dump-autoload
```

#### Step 2.3: Verify API Endpoints
Test the following endpoints:
- `GET /api/v1/categories` - List all categories
- `GET /api/v1/categories/{id}` - Get category details
- `GET /api/v1/categories/{id}/subcategories` - Get subcategories
- `GET /api/v1/categories/{id}/organizations` - Get organizations by category

### 3. Configuration

#### Step 3.1: Environment Variables
Add to your `.env` file:
```env
# Category System Configuration
CATEGORY_MATCHING_ENABLED=true
AUTO_MATCHING_ENABLED=true
MATCHING_SCORE_THRESHOLD=50
CATEGORY_CACHE_TTL=3600
```

#### Step 3.2: Cache Configuration
Configure Redis/Memcached for category caching:
```php
// In your cache configuration
$cache_config = [
    'categories' => ['ttl' => 3600],
    'matching_scores' => ['ttl' => 1800],
    'organization_capabilities' => ['ttl' => 900]
];
```

### 4. Data Migration (Existing Organizations)

#### Step 4.1: Migrate Existing Organization Data
```sql
-- Example migration for existing organizations
-- Adjust based on your current data structure

-- Migrate to Business Services category
INSERT INTO organization_categories (organization_id, category_id, expertise_level, years_experience, is_primary, is_active)
SELECT 
    id as organization_id,
    (SELECT id FROM categories WHERE name = 'Business Services') as category_id,
    'intermediate' as expertise_level,
    EXTRACT(YEAR FROM AGE(NOW(), created_at)) as years_experience,
    true as is_primary,
    true as is_active
FROM organizations 
WHERE organization_type = 'Supplier' 
AND is_active = true;
```

#### Step 4.2: Update Organization Profiles
Organizations should update their profiles to include:
- Category specializations
- Subcategory expertise levels
- Service capabilities
- Pricing models
- Delivery timelines

### 5. Matching Score Initialization

#### Step 5.1: Calculate Initial Scores
```php
// Run this via CLI or admin interface
php -r "
require_once 'vendor/autoload.php';
require_once 'server/php/Slim/lib/Models/QuoteMatchingEngine.php';
Models\QuoteMatchingEngine::updateMatchingScores();
echo 'Matching scores updated successfully';
"
```

#### Step 5.2: Set Up Automated Score Updates
Add to your cron jobs:
```bash
# Update matching scores daily at 2 AM
0 2 * * * /usr/bin/php /path/to/your/app/scripts/update_matching_scores.php
```

### 6. Testing and Validation

#### Step 6.1: API Testing
```bash
# Test category listing
curl -X GET "http://your-domain/api/v1/categories?include_subcategories=true"

# Test organization matching
curl -X POST "http://your-domain/api/v1/matching/find" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 1,
    "subcategory_id": 1,
    "budget": 50000,
    "deadline": "2024-03-01"
  }'
```

#### Step 6.2: Database Validation
```sql
-- Verify category relationships
SELECT 
    c.name as category,
    COUNT(oc.id) as organization_count,
    AVG(cms.score) as avg_score
FROM categories c
LEFT JOIN organization_categories oc ON c.id = oc.category_id
LEFT JOIN category_matching_scores cms ON c.id = cms.category_id
WHERE c.is_active = true
GROUP BY c.id, c.name
ORDER BY c.display_order;
```

## Business Categories Overview

### 1. Business Services
- Management Consulting
- Financial Advisory
- Legal Services
- Human Resources
- Business Registration

### 2. Construction & Engineering
- Civil Engineering
- Architecture & Design
- Project Management
- MEP Engineering
- Quality Control

### 3. Technology & IT
- Software Development
- IT Infrastructure
- Cybersecurity
- Data Analytics
- Digital Transformation

### 4. Industrial & Manufacturing
- Process Engineering
- Quality Assurance
- Equipment Maintenance
- Supply Chain Optimization
- Automation Solutions

### 5. Training & Development
- Corporate Training
- Technical Certification
- Leadership Development
- Safety Training
- E-Learning Solutions

### 6. Marketing & Media
- Digital Marketing
- Brand Development
- Content Creation
- Public Relations
- Market Research

### 7. Logistics & Supply Chain
- Transportation Services
- Warehouse Management
- Supply Chain Consulting
- Customs & Trade
- Fleet Management

### 8. Energy & Sustainability
- Renewable Energy
- Energy Efficiency
- Environmental Consulting
- Waste Management
- Carbon Footprint

## Matching Algorithm Features

### Scoring Factors
1. **Category Expertise (30 points)**: Organization's expertise level in the category
2. **Budget Compatibility (20 points)**: Alignment with organization's pricing range
3. **Timeline Compatibility (15 points)**: Ability to meet project deadlines
4. **Location Proximity (10 points)**: Geographic proximity or remote capability
5. **Organization Quality (15 points)**: Verification status, experience, team size
6. **Requirements Matching (10 points)**: Specific skill and certification matches

### Matching Process
1. Filter organizations by category/subcategory
2. Apply budget and timeline constraints
3. Calculate comprehensive matching scores
4. Rank organizations by score
5. Return top matches with reasoning

## Maintenance and Monitoring

### Regular Tasks
1. **Weekly**: Review matching score accuracy
2. **Monthly**: Update category statistics
3. **Quarterly**: Analyze category performance and usage
4. **Annually**: Review and update category structure

### Monitoring Metrics
- Category usage distribution
- Average matching scores by category
- Organization distribution across categories
- Quote-to-match conversion rates

### Performance Optimization
- Index optimization for large datasets
- Caching strategies for frequently accessed data
- Background processing for score calculations
- Database query optimization

## Troubleshooting

### Common Issues

#### Low Matching Scores
- **Cause**: Insufficient organization data or overly strict requirements
- **Solution**: Encourage organizations to complete profiles, adjust matching thresholds

#### Slow API Response
- **Cause**: Complex matching calculations or large datasets
- **Solution**: Implement caching, optimize database queries, use background processing

#### Inaccurate Matches
- **Cause**: Outdated matching scores or incomplete organization profiles
- **Solution**: Regular score updates, profile validation requirements

### Debug Commands
```bash
# Check category data integrity
php scripts/validate_categories.php

# Recalculate matching scores
php scripts/update_matching_scores.php

# Generate category statistics
php scripts/category_stats.php
```

## Security Considerations

### Access Control
- Category management requires admin privileges
- Organization profile updates require organization membership
- Matching data is publicly accessible but rate-limited

### Data Validation
- All category assignments require validation
- Budget and timeline data is sanitized
- File uploads for capabilities are security-scanned

### Privacy
- Organization capabilities are public for matching
- Detailed pricing may be hidden until contact
- Personal information remains protected

## Support and Documentation

### API Documentation
- Complete API documentation available at `/api/docs`
- Interactive API explorer for testing endpoints
- Example requests and responses provided

### Developer Resources
- Model documentation with relationship diagrams
- Database schema documentation
- Matching algorithm explanation

### User Guides
- Organization profile setup guide
- Category selection best practices
- Matching optimization tips

## Conclusion

The category system provides a comprehensive foundation for intelligent organization matching and quote routing. Regular maintenance and monitoring ensure optimal performance and accurate matching results.

For technical support or questions, contact the development team or refer to the API documentation.