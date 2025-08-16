-- Extended seed data for Quote Management features
-- Assumes base seeds are loaded

-- Map vendors to categories/subcategories
INSERT INTO user_categories (user_id, category_id, subcategory_id) VALUES
('v0000000-0000-0000-0000-000000000001', 1, 5),
('v0000000-0000-0000-0000-000000000002', 1, 6),
('v0000000-0000-0000-0000-000000000003', 1, 7),
('v0000000-0000-0000-0000-000000000004', 2, 8),
('v0000000-0000-0000-0000-000000000005', 2, 9),
('v0000000-0000-0000-0000-000000000006', 3, 10),
('v0000000-0000-0000-0000-000000000007', 3, 11),
('v0000000-0000-0000-0000-000000000008', 4, 12),
('v0000000-0000-0000-0000-000000000009', 4, 13),
('v0000000-0000-0000-0000-000000000010', 1, 6);

-- Favorites RFQs
INSERT INTO favorite_rfqs (user_id, rfq_id) VALUES
('b0000000-0000-0000-0000-000000000001', 2),
('b0000000-0000-0000-0000-000000000002', 1),
('v0000000-0000-0000-0000-000000000004', 3),
('v0000000-0000-0000-0000-000000000005', 6);

-- Favorites Users
INSERT INTO favorite_users (user_id, favorite_user_id) VALUES
('b0000000-0000-0000-0000-000000000001', 'v0000000-0000-0000-0000-000000000001'),
('b0000000-0000-0000-0000-000000000002', 'v0000000-0000-0000-0000-000000000004'),
('v0000000-0000-0000-0000-000000000001', 'b0000000-0000-0000-0000-000000000001');

-- Disputes
INSERT INTO disputes (rfq_id, bid_id, buyer_id, vendor_id, status, reason) VALUES
(11, 24, 'b0000000-0000-0000-0000-000000000001', 'v0000000-0000-0000-0000-000000000001', 'open', 'Disagreement on scope and timeline'),
(2, 4, 'b0000000-0000-0000-0000-000000000002', 'v0000000-0000-0000-0000-000000000004', 'resolved', 'Resolved by adjusting deliverables');

-- Referral Invites (one accepted)
INSERT INTO referral_invites (inviter_id, invitee_email, token, registered_user_id, accepted_at)
VALUES
('b0000000-0000-0000-0000-000000000001', 'newvendor@example.com', gen_random_uuid(), NULL, NULL),
('v0000000-0000-0000-0000-000000000004', 'buyerx@example.com', gen_random_uuid(), 'b0000000-0000-0000-0000-000000000010', now());

-- OTP samples (dev only; example, not for production)
INSERT INTO otps (email, code, purpose, expires_at, verified) VALUES
('devbuyer@example.com', '111111', 'registration', now() + interval '1 hour', false),
('devvendor@example.com', '222222', 'registration', now() + interval '1 hour', false);

-- Boost tags for trending (append extra tags to a subset of RFQs)
UPDATE rfqs SET tags = array_cat(COALESCE(tags, '{}'), '{"urgent","featured"}') WHERE id IN (1,2,3,4,5);
