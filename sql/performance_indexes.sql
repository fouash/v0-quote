-- Performance optimization indexes for Getlancer Quote Platform
-- These indexes will significantly improve query performance

-- User-related indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_role_id ON users(role_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_is_active ON users(is_active);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_is_email_confirmed ON users(is_email_confirmed);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_created_at ON users(created_at);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_country_id ON users(country_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_state_id ON users(state_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_city_id ON users(city_id);

-- Attachment indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_attachments_foreign_id ON attachments(foreign_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_attachments_class ON attachments(class);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_attachments_foreign_class ON attachments(foreign_id, class);

-- Transaction indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_to_user_id ON transactions(to_user_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_class ON transactions(class);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_foreign_id ON transactions(foreign_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_created_at ON transactions(created_at);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_payment_gateway_id ON transactions(payment_gateway_id);

-- Activity indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_activities_user_id ON activities(user_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_activities_other_user_id ON activities(other_user_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_activities_model_class ON activities(model_class);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_activities_model_id ON activities(model_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_activities_created_at ON activities(created_at);

-- OAuth indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_oauth_access_tokens_access_token ON oauth_access_tokens(access_token);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_oauth_access_tokens_user_id ON oauth_access_tokens(user_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_oauth_access_tokens_expires ON oauth_access_tokens(expires);

-- User login indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_user_logins_user_id ON user_logins(user_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_user_logins_ip_id ON user_logins(ip_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_user_logins_created_at ON user_logins(created_at);

-- Skills and relationships
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_skills_users_user_id ON skills_users(user_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_skills_users_skill_id ON skills_users(skill_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_skills_name ON skills(name);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_skills_is_active ON skills(is_active);

-- Location indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cities_country_id ON cities(country_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cities_state_id ON cities(state_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cities_is_active ON cities(is_active);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_states_country_id ON states(country_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_states_is_active ON states(is_active);

-- IP tracking indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ips_ip ON ips(ip);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ips_country_id ON ips(country_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ips_created_at ON ips(created_at);

-- Views tracking indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_views_user_id ON views(user_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_views_foreign_id ON views(foreign_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_views_class ON views(class);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_views_ip_id ON views(ip_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_views_created_at ON views(created_at);

-- Contact indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_contacts_email ON contacts(email);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_contacts_ip_id ON contacts(ip_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_contacts_created_at ON contacts(created_at);

-- Settings indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_settings_name ON settings(name);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_settings_setting_category_id ON settings(setting_category_id);

-- Email template indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_email_templates_name ON email_templates(name);

-- Page indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pages_slug ON pages(slug);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pages_is_active ON pages(is_active);

-- Form field indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_form_fields_class ON form_fields(class);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_form_fields_foreign_id ON form_fields(foreign_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_form_fields_is_active ON form_fields(is_active);

-- Provider indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_providers_name ON providers(name);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_providers_is_active ON providers(is_active);

-- Payment gateway indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_payment_gateways_name ON payment_gateways(name);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_payment_gateways_is_active ON payment_gateways(is_active);

-- Language indexes
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_languages_iso2 ON languages(iso2);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_languages_name ON languages(name);

-- Composite indexes for common query patterns
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_active_confirmed ON users(is_active, is_email_confirmed);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_user_created ON transactions(user_id, created_at);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_activities_user_created ON activities(user_id, created_at);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_attachments_foreign_class_created ON attachments(foreign_id, class, created_at);

-- Full-text search indexes (if using PostgreSQL)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_search ON users USING gin(to_tsvector('english', coalesce(first_name, '') || ' ' || coalesce(last_name, '') || ' ' || coalesce(username, '')));
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_skills_search ON skills USING gin(to_tsvector('english', name));

-- Partial indexes for better performance on filtered queries
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_active_only ON users(id) WHERE is_active = true;
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_confirmed_only ON users(id) WHERE is_email_confirmed = true;
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_transactions_recent ON transactions(created_at) WHERE created_at > NOW() - INTERVAL '1 year';

-- Analyze tables to update statistics after creating indexes
ANALYZE users;
ANALYZE attachments;
ANALYZE transactions;
ANALYZE activities;
ANALYZE oauth_access_tokens;
ANALYZE user_logins;
ANALYZE skills_users;
ANALYZE skills;
ANALYZE cities;
ANALYZE states;
ANALYZE ips;
ANALYZE views;
ANALYZE contacts;
ANALYZE settings;
ANALYZE email_templates;
ANALYZE pages;
ANALYZE form_fields;
ANALYZE providers;
ANALYZE payment_gateways;
ANALYZE languages;