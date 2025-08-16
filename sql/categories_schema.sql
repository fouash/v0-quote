-- Categories and Subcategories Schema for Organization Management
-- This extends the organization system with comprehensive business categorization

-- Categories table (main business sectors)
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(7) DEFAULT '#007bff',
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subcategories table (specific services within each sector)
CREATE TABLE IF NOT EXISTS subcategories (
    id SERIAL PRIMARY KEY,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    keywords TEXT, -- For search optimization
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(category_id, slug)
);

-- Organization categories (many-to-many relationship)
CREATE TABLE IF NOT EXISTS organization_categories (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    is_primary BOOLEAN DEFAULT false,
    expertise_level VARCHAR(50) DEFAULT 'intermediate', -- beginner, intermediate, expert, specialist
    years_experience INTEGER DEFAULT 0,
    certifications TEXT, -- JSON array of certifications
    portfolio_items INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(organization_id, category_id)
);

-- Organization subcategories (specific services offered)
CREATE TABLE IF NOT EXISTS organization_subcategories (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    subcategory_id INTEGER NOT NULL REFERENCES subcategories(id) ON DELETE CASCADE,
    expertise_level VARCHAR(50) DEFAULT 'intermediate',
    years_experience INTEGER DEFAULT 0,
    min_project_value DECIMAL(15,2) DEFAULT 0,
    max_project_value DECIMAL(15,2),
    typical_delivery_days INTEGER,
    certifications TEXT, -- JSON array of certifications
    description TEXT,
    is_featured BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(organization_id, subcategory_id)
);

-- Service capabilities (detailed capabilities within subcategories)
CREATE TABLE IF NOT EXISTS service_capabilities (
    id SERIAL PRIMARY KEY,
    subcategory_id INTEGER NOT NULL REFERENCES subcategories(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Organization capabilities (specific capabilities an organization offers)
CREATE TABLE IF NOT EXISTS organization_capabilities (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    capability_id INTEGER NOT NULL REFERENCES service_capabilities(id) ON DELETE CASCADE,
    proficiency_level VARCHAR(50) DEFAULT 'intermediate', -- basic, intermediate, advanced, expert
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(organization_id, capability_id)
);

-- Quote categories (for categorizing quote requests)
CREATE TABLE IF NOT EXISTS quote_categories (
    id SERIAL PRIMARY KEY,
    quote_id INTEGER NOT NULL, -- References quotes table
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    subcategory_id INTEGER REFERENCES subcategories(id) ON DELETE SET NULL,
    is_primary BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Category matching scores (for intelligent quote routing)
CREATE TABLE IF NOT EXISTS category_matching_scores (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    subcategory_id INTEGER REFERENCES subcategories(id) ON DELETE SET NULL,
    score DECIMAL(5,2) DEFAULT 0, -- 0-100 matching score
    factors TEXT, -- JSON object with scoring factors
    last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(organization_id, category_id, subcategory_id)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_categories_active ON categories(is_active, sort_order);
CREATE INDEX IF NOT EXISTS idx_categories_slug ON categories(slug);
CREATE INDEX IF NOT EXISTS idx_subcategories_category ON subcategories(category_id, is_active);
CREATE INDEX IF NOT EXISTS idx_subcategories_slug ON subcategories(category_id, slug);
CREATE INDEX IF NOT EXISTS idx_org_categories_org ON organization_categories(organization_id);
CREATE INDEX IF NOT EXISTS idx_org_categories_cat ON organization_categories(category_id);
CREATE INDEX IF NOT EXISTS idx_org_categories_primary ON organization_categories(organization_id, is_primary);
CREATE INDEX IF NOT EXISTS idx_org_subcategories_org ON organization_subcategories(organization_id);
CREATE INDEX IF NOT EXISTS idx_org_subcategories_sub ON organization_subcategories(subcategory_id);
CREATE INDEX IF NOT EXISTS idx_org_subcategories_featured ON organization_subcategories(subcategory_id, is_featured);
CREATE INDEX IF NOT EXISTS idx_org_capabilities_org ON organization_capabilities(organization_id);
CREATE INDEX IF NOT EXISTS idx_quote_categories_quote ON quote_categories(quote_id);
CREATE INDEX IF NOT EXISTS idx_quote_categories_cat ON quote_categories(category_id, subcategory_id);
CREATE INDEX IF NOT EXISTS idx_matching_scores_org ON category_matching_scores(organization_id);
CREATE INDEX IF NOT EXISTS idx_matching_scores_score ON category_matching_scores(score DESC);

-- Insert main categories
INSERT INTO categories (name, slug, description, icon, color, sort_order) VALUES
('Business Services', 'business-services', 'Professional business consulting, accounting, legal, and HR services', 'fa-briefcase', '#007bff', 1),
('Construction & Engineering', 'construction-engineering', 'Construction, renovation, MEP services, and engineering consulting', 'fa-building', '#28a745', 2),
('Technology & IT', 'technology-it', 'Software development, IT infrastructure, cybersecurity, and digital solutions', 'fa-laptop', '#17a2b8', 3),
('Industrial & Manufacturing', 'industrial-manufacturing', 'Machinery supply, fabrication, maintenance, and raw materials', 'fa-industry', '#ffc107', 4),
('Training & Development', 'training-development', 'Corporate training, technical skills, safety training, and professional development', 'fa-graduation-cap', '#6f42c1', 5),
('Marketing & Media', 'marketing-media', 'Digital marketing, branding, content creation, and advertising services', 'fa-bullhorn', '#e83e8c', 6),
('Logistics & Supply Chain', 'logistics-supply-chain', 'Freight, warehousing, customs clearance, and fleet management', 'fa-truck', '#fd7e14', 7),
('Energy & Sustainability', 'energy-sustainability', 'Renewable energy, efficiency solutions, waste management, and environmental consulting', 'fa-leaf', '#20c997', 8);

-- Insert subcategories for Business Services
INSERT INTO subcategories (category_id, name, slug, description, keywords, sort_order) VALUES
(1, 'Consulting & Strategy', 'consulting-strategy', 'Business consulting, feasibility studies, strategic planning', 'business consulting, strategy, feasibility study, strategic planning, management consulting', 1),
(1, 'Accounting & Finance', 'accounting-finance', 'Bookkeeping, tax services, financial advisory', 'accounting, bookkeeping, tax services, financial advisory, audit, CFO services', 2),
(1, 'Legal & Compliance', 'legal-compliance', 'Legal consulting, contracts, regulatory compliance', 'legal consulting, contracts, compliance, regulatory, legal advisory, corporate law', 3),
(1, 'HR & Recruitment', 'hr-recruitment', 'Talent acquisition, HR outsourcing, training programs', 'HR, recruitment, talent acquisition, human resources, staffing, payroll', 4),
(1, 'Outsourcing Services', 'outsourcing-services', 'Business process outsourcing, call centers, virtual assistants', 'BPO, outsourcing, call center, virtual assistant, back office, data entry', 5);

-- Insert subcategories for Construction & Engineering
INSERT INTO subcategories (category_id, name, slug, description, keywords, sort_order) VALUES
(2, 'General Contracting', 'general-contracting', 'Building construction, civil works, turnkey projects', 'construction, general contractor, civil works, building, turnkey projects', 1),
(2, 'Renovation & Fit-Out', 'renovation-fitout', 'Office renovation, commercial fit-outs, interior works', 'renovation, fit-out, interior design, office renovation, commercial interiors', 2),
(2, 'MEP Services', 'mep-services', 'Mechanical, electrical, plumbing installation & maintenance', 'MEP, mechanical, electrical, plumbing, HVAC, installation, maintenance', 3),
(2, 'Engineering Consulting', 'engineering-consulting', 'Structural, civil, and architectural design', 'engineering consulting, structural design, civil engineering, architectural design', 4),
(2, 'Smart Building Solutions', 'smart-building', 'Building automation, energy management systems', 'smart building, building automation, BMS, energy management, IoT', 5);

-- Insert subcategories for Technology & IT
INSERT INTO subcategories (category_id, name, slug, description, keywords, sort_order) VALUES
(3, 'Software Development', 'software-development', 'Web, mobile, ERP, and custom applications', 'software development, web development, mobile apps, ERP, custom software', 1),
(3, 'IT Infrastructure', 'it-infrastructure', 'Networking, servers, cloud services', 'IT infrastructure, networking, servers, cloud services, system administration', 2),
(3, 'Cybersecurity', 'cybersecurity', 'Penetration testing, compliance, managed security services', 'cybersecurity, penetration testing, security audit, managed security, compliance', 3),
(3, 'E-commerce Solutions', 'ecommerce-solutions', 'Marketplace platforms, payment integrations', 'e-commerce, online store, marketplace, payment gateway, digital commerce', 4),
(3, 'AI & Data Solutions', 'ai-data-solutions', 'Data analytics, AI automation, machine learning services', 'AI, artificial intelligence, data analytics, machine learning, automation', 5);

-- Insert subcategories for Industrial & Manufacturing
INSERT INTO subcategories (category_id, name, slug, description, keywords, sort_order) VALUES
(4, 'Machinery Supply', 'machinery-supply', 'Industrial equipment, spare parts', 'machinery, industrial equipment, spare parts, manufacturing equipment', 1),
(4, 'Fabrication & Welding', 'fabrication-welding', 'Metal works, structural steel, custom fabrication', 'fabrication, welding, metal works, structural steel, custom manufacturing', 2),
(4, 'Industrial Maintenance', 'industrial-maintenance', 'Preventive maintenance, repair services', 'industrial maintenance, preventive maintenance, repair services, equipment maintenance', 3),
(4, 'Packaging Solutions', 'packaging-solutions', 'Product packaging, labeling, and logistics packaging', 'packaging, labeling, product packaging, logistics packaging, packaging design', 4),
(4, 'Raw Materials', 'raw-materials', 'Steel, aluminum, plastics, chemicals', 'raw materials, steel, aluminum, plastics, chemicals, industrial materials', 5);

-- Insert subcategories for Training & Development
INSERT INTO subcategories (category_id, name, slug, description, keywords, sort_order) VALUES
(5, 'Corporate Training', 'corporate-training', 'Leadership, soft skills, project management', 'corporate training, leadership training, soft skills, project management, professional development', 1),
(5, 'Technical Training', 'technical-training', 'Industrial safety, engineering, IT certifications', 'technical training, industrial safety, engineering training, IT certification, skills development', 2),
(5, 'Health & Safety Training', 'health-safety-training', 'OSHA, NEBOSH, firefighting, first aid', 'health safety training, OSHA, NEBOSH, firefighting, first aid, safety certification', 3),
(5, 'Digital Skills Training', 'digital-skills-training', 'AI, cloud computing, data analysis', 'digital skills, AI training, cloud computing, data analysis, digital transformation', 4),
(5, 'Vocational Training', 'vocational-training', 'Construction skills, automotive, welding', 'vocational training, construction skills, automotive training, welding certification', 5);

-- Insert subcategories for Marketing & Media
INSERT INTO subcategories (category_id, name, slug, description, keywords, sort_order) VALUES
(6, 'Digital Marketing', 'digital-marketing', 'SEO, PPC, social media marketing', 'digital marketing, SEO, PPC, social media marketing, online marketing', 1),
(6, 'Branding & Design', 'branding-design', 'Brand identity, corporate rebranding', 'branding, brand identity, logo design, corporate branding, graphic design', 2),
(6, 'Content Creation', 'content-creation', 'Photography, videography, copywriting', 'content creation, photography, videography, copywriting, content marketing', 3),
(6, 'Event Management', 'event-management', 'Exhibitions, conferences, corporate events', 'event management, exhibitions, conferences, corporate events, event planning', 4),
(6, 'Advertising Services', 'advertising-services', 'Billboard, print, and media buying', 'advertising, billboard, print advertising, media buying, outdoor advertising', 5);

-- Insert subcategories for Logistics & Supply Chain
INSERT INTO subcategories (category_id, name, slug, description, keywords, sort_order) VALUES
(7, 'Freight & Shipping', 'freight-shipping', 'Sea, air, and land transport', 'freight, shipping, sea freight, air freight, land transport, logistics', 1),
(7, 'Warehousing Solutions', 'warehousing-solutions', 'Storage, inventory management', 'warehousing, storage, inventory management, warehouse management, distribution', 2),
(7, 'Customs Clearance', 'customs-clearance', 'Import/export documentation', 'customs clearance, import export, customs documentation, trade compliance', 3),
(7, 'Cold Chain Logistics', 'cold-chain-logistics', 'Refrigerated transport for food & pharma', 'cold chain, refrigerated transport, temperature controlled, food logistics, pharma logistics', 4),
(7, 'Fleet Management', 'fleet-management', 'Vehicle leasing, GPS tracking', 'fleet management, vehicle leasing, GPS tracking, fleet optimization, vehicle maintenance', 5);

-- Insert subcategories for Energy & Sustainability
INSERT INTO subcategories (category_id, name, slug, description, keywords, sort_order) VALUES
(8, 'Renewable Energy', 'renewable-energy', 'Solar panels, wind energy solutions', 'renewable energy, solar panels, wind energy, solar installation, green energy', 1),
(8, 'Energy Efficiency', 'energy-efficiency', 'LED retrofitting, energy audits', 'energy efficiency, LED retrofitting, energy audit, energy optimization, energy saving', 2),
(8, 'Waste Management', 'waste-management', 'Recycling, hazardous waste disposal', 'waste management, recycling, hazardous waste, waste disposal, environmental services', 3),
(8, 'Water Treatment', 'water-treatment', 'Desalination, filtration systems', 'water treatment, desalination, water filtration, water purification, water systems', 4),
(8, 'Environmental Consulting', 'environmental-consulting', 'ESG reporting, compliance audits', 'environmental consulting, ESG reporting, environmental compliance, sustainability consulting', 5);

-- Insert sample service capabilities
INSERT INTO service_capabilities (subcategory_id, name, description) VALUES
-- Business Services capabilities
(1, 'Market Research', 'Comprehensive market analysis and research services'),
(1, 'Business Plan Development', 'Professional business plan creation and review'),
(1, 'Process Optimization', 'Business process analysis and optimization'),
(2, 'Tax Preparation', 'Individual and corporate tax preparation services'),
(2, 'Financial Auditing', 'Independent financial audit services'),
(2, 'CFO Services', 'Part-time or interim CFO services'),
-- Technology capabilities
(11, 'Web Development', 'Custom website and web application development'),
(11, 'Mobile App Development', 'iOS and Android mobile application development'),
(11, 'Database Design', 'Database architecture and optimization'),
(13, 'Vulnerability Assessment', 'Security vulnerability testing and assessment'),
(13, 'Security Compliance', 'Regulatory compliance and security frameworks'),
-- Construction capabilities
(6, 'Project Management', 'Construction project management and coordination'),
(6, 'Quality Control', 'Construction quality assurance and control'),
(8, 'HVAC Installation', 'Heating, ventilation, and air conditioning systems'),
(8, 'Electrical Installation', 'Commercial and industrial electrical systems');

-- Update timestamps trigger
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_categories_updated_at BEFORE UPDATE ON categories FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_subcategories_updated_at BEFORE UPDATE ON subcategories FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Comments for documentation
COMMENT ON TABLE categories IS 'Main business categories/sectors';
COMMENT ON TABLE subcategories IS 'Specific services within each business category';
COMMENT ON TABLE organization_categories IS 'Categories that organizations operate in';
COMMENT ON TABLE organization_subcategories IS 'Specific services offered by organizations';
COMMENT ON TABLE service_capabilities IS 'Detailed capabilities within subcategories';
COMMENT ON TABLE organization_capabilities IS 'Specific capabilities an organization offers';
COMMENT ON TABLE quote_categories IS 'Categories assigned to quote requests';
COMMENT ON TABLE category_matching_scores IS 'AI-powered matching scores for quote routing';