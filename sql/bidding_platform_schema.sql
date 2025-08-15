CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  role VARCHAR(10) NOT NULL,
  name VARCHAR(255),
  email VARCHAR(255) UNIQUE,
  password_hash VARCHAR(255),
  slug VARCHAR(255) UNIQUE,
  company_name VARCHAR(255),
  bio TEXT,
  phone VARCHAR(50),
  website VARCHAR(255),
  address JSONB,
  business_info JSONB,
  rating NUMERIC(2,1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT now(),
  updated_at TIMESTAMP DEFAULT now()
);

CREATE TABLE categories (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  parent_id INT REFERENCES categories(id),
  metadata JSONB,
  created_at TIMESTAMP DEFAULT now(),
  updated_at TIMESTAMP DEFAULT now()
);

CREATE TABLE rfqs (
  id BIGSERIAL PRIMARY KEY,
  title VARCHAR(500) NOT NULL,
  slug_title VARCHAR(500),
  description TEXT,
  buyer_id UUID REFERENCES users(id),
  category_id INT REFERENCES categories(id),
  subcategory_id INT REFERENCES categories(id),
  tags TEXT[],
  budget_min NUMERIC,
  budget_max NUMERIC,
  currency VARCHAR(3),
  location JSONB,
  status VARCHAR(20) DEFAULT 'open',
  is_public BOOLEAN DEFAULT TRUE,
  closing_at TIMESTAMP,
  attachments JSONB,
  created_at TIMESTAMP DEFAULT now(),
  updated_at TIMESTAMP DEFAULT now()
);

CREATE TABLE bids (
  id BIGSERIAL PRIMARY KEY,
  rfq_id BIGINT REFERENCES rfqs(id) ON DELETE CASCADE,
  vendor_id UUID REFERENCES users(id),
  amount NUMERIC,
  currency VARCHAR(3),
  delivery_days INT,
  message TEXT,
  attachments JSONB,
  status VARCHAR(20) DEFAULT 'submitted',
  awarded BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT now(),
  updated_at TIMESTAMP DEFAULT now()
);

CREATE TABLE conversations (
  id BIGSERIAL PRIMARY KEY,
  rfq_id BIGINT REFERENCES rfqs(id),
  buyer_id UUID REFERENCES users(id),
  vendor_id UUID REFERENCES users(id),
  created_at TIMESTAMP DEFAULT now(),
  updated_at TIMESTAMP DEFAULT now(),
  UNIQUE (rfq_id, buyer_id, vendor_id)
);

CREATE TABLE messages (
  id BIGSERIAL PRIMARY KEY,
  conversation_id BIGINT REFERENCES conversations(id) ON DELETE CASCADE,
  sender_id UUID REFERENCES users(id),
  content TEXT,
  attachments JSONB,
  read_by UUID[] DEFAULT '{}',
  created_at TIMESTAMP DEFAULT now(),
  updated_at TIMESTAMP DEFAULT now()
);

CREATE TABLE notifications (
  id BIGSERIAL PRIMARY KEY,
  user_id UUID REFERENCES users(id),
  type VARCHAR(50),
  payload JSONB,
  read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT now()
);
