-- Populate Categories and Subcategories
-- This script populates the categories and subcategories tables with the comprehensive business categorization system

-- Insert main categories
INSERT INTO categories (name, description, icon, color, display_order, is_active, created_at, updated_at) VALUES
('Business Services', 'Professional business support and consulting services', 'briefcase', '#2563eb', 1, true, NOW(), NOW()),
('Construction & Engineering', 'Construction, engineering, and infrastructure services', 'hammer', '#dc2626', 2, true, NOW(), NOW()),
('Technology & IT', 'Information technology and digital services', 'computer', '#059669', 3, true, NOW(), NOW()),
('Industrial & Manufacturing', 'Manufacturing, production, and industrial services', 'cog', '#7c3aed', 4, true, NOW(), NOW()),
('Training & Development', 'Education, training, and professional development', 'academic-cap', '#ea580c', 5, true, NOW(), NOW()),
('Marketing & Media', 'Marketing, advertising, and media services', 'megaphone', '#db2777', 6, true, NOW(), NOW()),
('Logistics & Supply Chain', 'Transportation, logistics, and supply chain management', 'truck', '#0891b2', 7, true, NOW(), NOW()),
('Energy & Sustainability', 'Energy, environmental, and sustainability services', 'lightning-bolt', '#65a30d', 8, true, NOW(), NOW());

-- Insert subcategories for Business Services
INSERT INTO subcategories (category_id, name, description, keywords, typical_budget_min, typical_budget_max, typical_duration_days, complexity_level, display_order, is_active, created_at, updated_at) VALUES
((SELECT id FROM categories WHERE name = 'Business Services'), 'Management Consulting', 'Strategic planning, organizational development, and business process improvement', '["consulting", "strategy", "management", "planning", "optimization"]', 10000, 100000, 30, 'complex', 1, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Business Services'), 'Financial Advisory', 'Financial planning, investment advice, and accounting services', '["finance", "accounting", "investment", "advisory", "planning"]', 5000, 50000, 21, 'moderate', 2, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Business Services'), 'Legal Services', 'Corporate law, contract drafting, and legal consultation', '["legal", "law", "contracts", "compliance", "consultation"]', 3000, 75000, 14, 'complex', 3, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Business Services'), 'Human Resources', 'Recruitment, HR policies, and employee management', '["hr", "recruitment", "hiring", "policies", "management"]', 2000, 30000, 21, 'moderate', 4, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Business Services'), 'Business Registration', 'Company formation, licensing, and regulatory compliance', '["registration", "licensing", "compliance", "formation", "permits"]', 1000, 15000, 7, 'simple', 5, true, NOW(), NOW());

-- Insert subcategories for Construction & Engineering
INSERT INTO subcategories (category_id, name, description, keywords, typical_budget_min, typical_budget_max, typical_duration_days, complexity_level, display_order, is_active, created_at, updated_at) VALUES
((SELECT id FROM categories WHERE name = 'Construction & Engineering'), 'Civil Engineering', 'Infrastructure design, structural engineering, and project management', '["civil", "infrastructure", "structural", "engineering", "design"]', 50000, 500000, 90, 'expert_level', 1, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Construction & Engineering'), 'Architecture & Design', 'Architectural design, planning, and interior design services', '["architecture", "design", "planning", "interior", "blueprints"]', 20000, 200000, 60, 'complex', 2, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Construction & Engineering'), 'Project Management', 'Construction project coordination and management', '["project", "management", "coordination", "supervision", "planning"]', 15000, 100000, 120, 'complex', 3, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Construction & Engineering'), 'MEP Engineering', 'Mechanical, electrical, and plumbing engineering services', '["mep", "mechanical", "electrical", "plumbing", "hvac"]', 25000, 150000, 45, 'complex', 4, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Construction & Engineering'), 'Quality Control', 'Construction quality assurance and testing services', '["quality", "control", "testing", "assurance", "inspection"]', 5000, 50000, 30, 'moderate', 5, true, NOW(), NOW());

-- Insert subcategories for Technology & IT
INSERT INTO subcategories (category_id, name, description, keywords, typical_budget_min, typical_budget_max, typical_duration_days, complexity_level, display_order, is_active, created_at, updated_at) VALUES
((SELECT id FROM categories WHERE name = 'Technology & IT'), 'Software Development', 'Custom software, web applications, and mobile app development', '["software", "development", "programming", "applications", "coding"]', 10000, 200000, 60, 'complex', 1, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Technology & IT'), 'IT Infrastructure', 'Network setup, server management, and IT support services', '["infrastructure", "network", "servers", "support", "maintenance"]', 5000, 100000, 30, 'moderate', 2, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Technology & IT'), 'Cybersecurity', 'Security audits, penetration testing, and security consulting', '["cybersecurity", "security", "audits", "testing", "protection"]', 8000, 80000, 21, 'complex', 3, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Technology & IT'), 'Data Analytics', 'Business intelligence, data analysis, and reporting solutions', '["analytics", "data", "intelligence", "reporting", "insights"]', 7000, 75000, 45, 'complex', 4, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Technology & IT'), 'Digital Transformation', 'Process digitization, automation, and technology adoption', '["digital", "transformation", "automation", "digitization", "modernization"]', 15000, 150000, 90, 'expert_level', 5, true, NOW(), NOW());

-- Insert subcategories for Industrial & Manufacturing
INSERT INTO subcategories (category_id, name, description, keywords, typical_budget_min, typical_budget_max, typical_duration_days, complexity_level, display_order, is_active, created_at, updated_at) VALUES
((SELECT id FROM categories WHERE name = 'Industrial & Manufacturing'), 'Process Engineering', 'Manufacturing process design and optimization', '["process", "engineering", "manufacturing", "optimization", "design"]', 30000, 300000, 75, 'expert_level', 1, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Industrial & Manufacturing'), 'Quality Assurance', 'Quality control systems and ISO certification support', '["quality", "assurance", "iso", "certification", "standards"]', 10000, 80000, 45, 'complex', 2, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Industrial & Manufacturing'), 'Equipment Maintenance', 'Industrial equipment servicing and maintenance programs', '["equipment", "maintenance", "servicing", "repair", "programs"]', 5000, 100000, 30, 'moderate', 3, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Industrial & Manufacturing'), 'Supply Chain Optimization', 'Manufacturing supply chain analysis and improvement', '["supply", "chain", "optimization", "logistics", "efficiency"]', 20000, 150000, 60, 'complex', 4, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Industrial & Manufacturing'), 'Automation Solutions', 'Industrial automation and robotics implementation', '["automation", "robotics", "implementation", "industrial", "solutions"]', 40000, 400000, 120, 'expert_level', 5, true, NOW(), NOW());

-- Insert subcategories for Training & Development
INSERT INTO subcategories (category_id, name, description, keywords, typical_budget_min, typical_budget_max, typical_duration_days, complexity_level, display_order, is_active, created_at, updated_at) VALUES
((SELECT id FROM categories WHERE name = 'Training & Development'), 'Corporate Training', 'Employee skill development and corporate training programs', '["corporate", "training", "skills", "development", "programs"]', 5000, 50000, 21, 'moderate', 1, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Training & Development'), 'Technical Certification', 'Professional certification and technical skill validation', '["certification", "technical", "professional", "validation", "skills"]', 2000, 20000, 14, 'moderate', 2, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Training & Development'), 'Leadership Development', 'Management and leadership skill enhancement programs', '["leadership", "management", "development", "enhancement", "skills"]', 8000, 60000, 30, 'complex', 3, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Training & Development'), 'Safety Training', 'Workplace safety and compliance training programs', '["safety", "training", "workplace", "compliance", "programs"]', 3000, 25000, 7, 'simple', 4, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Training & Development'), 'E-Learning Solutions', 'Online training platforms and digital learning content', '["elearning", "online", "digital", "platforms", "content"]', 10000, 80000, 45, 'complex', 5, true, NOW(), NOW());

-- Insert subcategories for Marketing & Media
INSERT INTO subcategories (category_id, name, description, keywords, typical_budget_min, typical_budget_max, typical_duration_days, complexity_level, display_order, is_active, created_at, updated_at) VALUES
((SELECT id FROM categories WHERE name = 'Marketing & Media'), 'Digital Marketing', 'Online marketing, SEO, social media, and digital advertising', '["digital", "marketing", "seo", "social", "advertising"]', 5000, 50000, 30, 'moderate', 1, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Marketing & Media'), 'Brand Development', 'Brand strategy, logo design, and brand identity creation', '["brand", "development", "strategy", "identity", "design"]', 8000, 60000, 45, 'complex', 2, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Marketing & Media'), 'Content Creation', 'Video production, photography, and content development', '["content", "creation", "video", "photography", "production"]', 3000, 40000, 21, 'moderate', 3, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Marketing & Media'), 'Public Relations', 'PR campaigns, media relations, and reputation management', '["public", "relations", "pr", "media", "reputation"]', 10000, 80000, 60, 'complex', 4, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Marketing & Media'), 'Market Research', 'Consumer research, market analysis, and competitive intelligence', '["market", "research", "analysis", "consumer", "intelligence"]', 7000, 50000, 30, 'moderate', 5, true, NOW(), NOW());

-- Insert subcategories for Logistics & Supply Chain
INSERT INTO subcategories (category_id, name, description, keywords, typical_budget_min, typical_budget_max, typical_duration_days, complexity_level, display_order, is_active, created_at, updated_at) VALUES
((SELECT id FROM categories WHERE name = 'Logistics & Supply Chain'), 'Transportation Services', 'Freight, shipping, and transportation management', '["transportation", "freight", "shipping", "logistics", "delivery"]', 2000, 100000, 7, 'simple', 1, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Logistics & Supply Chain'), 'Warehouse Management', 'Inventory management and warehouse optimization', '["warehouse", "inventory", "management", "storage", "optimization"]', 10000, 80000, 30, 'moderate', 2, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Logistics & Supply Chain'), 'Supply Chain Consulting', 'Supply chain strategy and optimization consulting', '["supply", "chain", "consulting", "strategy", "optimization"]', 15000, 120000, 60, 'complex', 3, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Logistics & Supply Chain'), 'Customs & Trade', 'Import/export documentation and customs clearance', '["customs", "trade", "import", "export", "clearance"]', 1000, 20000, 14, 'moderate', 4, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Logistics & Supply Chain'), 'Fleet Management', 'Vehicle fleet optimization and management services', '["fleet", "management", "vehicles", "optimization", "tracking"]', 8000, 60000, 45, 'moderate', 5, true, NOW(), NOW());

-- Insert subcategories for Energy & Sustainability
INSERT INTO subcategories (category_id, name, description, keywords, typical_budget_min, typical_budget_max, typical_duration_days, complexity_level, display_order, is_active, created_at, updated_at) VALUES
((SELECT id FROM categories WHERE name = 'Energy & Sustainability'), 'Renewable Energy', 'Solar, wind, and renewable energy system design and installation', '["renewable", "energy", "solar", "wind", "sustainable"]', 50000, 500000, 90, 'expert_level', 1, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Energy & Sustainability'), 'Energy Efficiency', 'Energy audits and efficiency improvement consulting', '["energy", "efficiency", "audits", "improvement", "conservation"]', 5000, 50000, 30, 'moderate', 2, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Energy & Sustainability'), 'Environmental Consulting', 'Environmental impact assessments and compliance', '["environmental", "consulting", "impact", "assessment", "compliance"]', 10000, 80000, 45, 'complex', 3, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Energy & Sustainability'), 'Waste Management', 'Waste reduction, recycling, and disposal solutions', '["waste", "management", "recycling", "disposal", "reduction"]', 8000, 60000, 30, 'moderate', 4, true, NOW(), NOW()),
((SELECT id FROM categories WHERE name = 'Energy & Sustainability'), 'Carbon Footprint', 'Carbon assessment, reduction strategies, and offset programs', '["carbon", "footprint", "assessment", "reduction", "offset"]', 12000, 70000, 60, 'complex', 5, true, NOW(), NOW());

-- Update category statistics
UPDATE categories SET 
    subcategory_count = (SELECT COUNT(*) FROM subcategories WHERE category_id = categories.id AND is_active = true),
    updated_at = NOW()
WHERE is_active = true;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_subcategories_category_active ON subcategories(category_id, is_active);
CREATE INDEX IF NOT EXISTS idx_subcategories_keywords ON subcategories USING GIN(keywords);
CREATE INDEX IF NOT EXISTS idx_categories_active_order ON categories(is_active, display_order);
CREATE INDEX IF NOT EXISTS idx_subcategories_active_order ON subcategories(is_active, display_order);
CREATE INDEX IF NOT EXISTS idx_subcategories_budget_range ON subcategories(typical_budget_min, typical_budget_max);
CREATE INDEX IF NOT EXISTS idx_subcategories_complexity ON subcategories(complexity_level);

-- Insert sample data for testing (optional)
-- This can be uncommented for development/testing purposes

/*
-- Sample organization categories (for testing)
INSERT INTO organization_categories (organization_id, category_id, expertise_level, years_experience, portfolio_items, certifications, is_primary, is_active, created_at, updated_at) VALUES
(1, (SELECT id FROM categories WHERE name = 'Technology & IT'), 'expert', 8, 25, '["AWS Certified", "Microsoft Azure"]', true, true, NOW(), NOW()),
(1, (SELECT id FROM categories WHERE name = 'Business Services'), 'intermediate', 5, 12, '["PMP", "Agile Certified"]', false, true, NOW(), NOW()),
(2, (SELECT id FROM categories WHERE name = 'Construction & Engineering'), 'specialist', 15, 50, '["PE License", "LEED Certified"]', true, true, NOW(), NOW());

-- Sample organization subcategories (for testing)
INSERT INTO organization_subcategories (organization_id, subcategory_id, expertise_level, years_experience, portfolio_items, min_project_value, max_project_value, typical_delivery_days, is_featured, pricing_model, availability_status, is_active, created_at, updated_at) VALUES
(1, (SELECT id FROM subcategories WHERE name = 'Software Development'), 'expert', 8, 25, 10000, 200000, 60, true, 'project_based', 'available', true, NOW(), NOW()),
(1, (SELECT id FROM subcategories WHERE name = 'Digital Transformation'), 'expert', 6, 15, 20000, 150000, 90, false, 'project_based', 'available', true, NOW(), NOW()),
(2, (SELECT id FROM subcategories WHERE name = 'Civil Engineering'), 'specialist', 15, 50, 50000, 500000, 120, true, 'project_based', 'busy', true, NOW(), NOW());
*/

COMMIT;