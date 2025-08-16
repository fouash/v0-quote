-- Schema extensions for Quote Management System features
-- Includes: favorites, disputes, referral invites, OTPs, vendor categories mapping, and helpful indexes

BEGIN;

-- Ensure pgcrypto is available for gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Map vendors to the categories/subcategories they serve
CREATE TABLE IF NOT EXISTS user_categories (
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  category_id INT REFERENCES categories(id) ON DELETE CASCADE,
  subcategory_id INT REFERENCES categories(id),
  PRIMARY KEY (user_id, category_id, subcategory_id)
);
CREATE INDEX IF NOT EXISTS idx_user_categories_user ON user_categories(user_id);
CREATE INDEX IF NOT EXISTS idx_user_categories_cat ON user_categories(category_id, subcategory_id);

-- Favorite RFQs by users (buyers or vendors)
CREATE TABLE IF NOT EXISTS favorite_rfqs (
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  rfq_id BIGINT REFERENCES rfqs(id) ON DELETE CASCADE,
  created_at TIMESTAMP DEFAULT now(),
  PRIMARY KEY (user_id, rfq_id)
);

-- Favorite users (e.g., vendors a buyer likes, or vice versa)
CREATE TABLE IF NOT EXISTS favorite_users (
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  favorite_user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  created_at TIMESTAMP DEFAULT now(),
  PRIMARY KEY (user_id, favorite_user_id),
  CONSTRAINT no_self_favorite CHECK (user_id <> favorite_user_id)
);

-- Dispute system for RFQs/Bids
CREATE TABLE IF NOT EXISTS disputes (
  id BIGSERIAL PRIMARY KEY,
  rfq_id BIGINT REFERENCES rfqs(id) ON DELETE CASCADE,
  bid_id BIGINT REFERENCES bids(id) ON DELETE SET NULL,
  buyer_id UUID REFERENCES users(id),
  vendor_id UUID REFERENCES users(id),
  status VARCHAR(20) DEFAULT 'open', -- open, resolved, rejected
  reason TEXT,
  resolution TEXT,
  created_at TIMESTAMP DEFAULT now(),
  updated_at TIMESTAMP DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_disputes_rfq ON disputes(rfq_id);
CREATE INDEX IF NOT EXISTS idx_disputes_vendor ON disputes(vendor_id);
CREATE INDEX IF NOT EXISTS idx_disputes_buyer ON disputes(buyer_id);

-- Referrals/Invitations tracking
CREATE TABLE IF NOT EXISTS referral_invites (
  id BIGSERIAL PRIMARY KEY,
  inviter_id UUID REFERENCES users(id) ON DELETE CASCADE,
  invitee_email VARCHAR(255) NOT NULL,
  token UUID DEFAULT gen_random_uuid() UNIQUE,
  registered_user_id UUID REFERENCES users(id),
  accepted_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_referral_invites_invitee_email ON referral_invites(invitee_email);
CREATE INDEX IF NOT EXISTS idx_referral_invites_inviter ON referral_invites(inviter_id);

-- OTPs for email verification/registration/login
CREATE TABLE IF NOT EXISTS otps (
  id BIGSERIAL PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  code VARCHAR(6) NOT NULL,
  purpose VARCHAR(30) NOT NULL, -- registration, login, reset_password
  expires_at TIMESTAMP NOT NULL,
  verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_otps_email ON otps(email);
CREATE INDEX IF NOT EXISTS idx_otps_email_code ON otps(email, code, verified);

-- Helpful index for tag keyword search and trending
CREATE INDEX IF NOT EXISTS idx_rfqs_tags_gin ON rfqs USING GIN (tags);

COMMIT;
