// src/api/routes/auth.js
const express = require('express');
const router = express.Router();
const jwt = require('jsonwebtoken');
const db = require('../../config/db');

// Helpers
const isValidEmail = (email) => /[^@\s]+@[^@\s]+\.[^@\s]+/.test(email);
const genCode = () => Math.floor(100000 + Math.random() * 900000).toString(); // 6-digit
const minutesFromNow = (m) => new Date(Date.now() + m * 60 * 1000);
const slugify = (str) => (str || '')
  .toString()
  .toLowerCase()
  .trim()
  .replace(/[^a-z0-9]+/g, '-')
  .replace(/(^-|-$)+/g, '')
  .substring(0, 60) || `user-${Math.random().toString(36).slice(2, 8)}`;

// POST /api/auth/otp/request
// body: { email, purpose = 'registration' }
router.post('/otp/request', async (req, res) => {
  try {
    const { email, purpose = 'registration' } = req.body || {};
    if (!email || !isValidEmail(email)) {
      return res.status(400).json({ success: false, message: 'Valid email is required' });
    }
    if (!['registration', 'login', 'reset_password'].includes(purpose)) {
      return res.status(400).json({ success: false, message: 'Invalid purpose' });
    }

    const code = genCode();
    const expiresAt = minutesFromNow(10);

    await db.query(
      'INSERT INTO otps (email, code, purpose, expires_at) VALUES ($1, $2, $3, $4)',
      [email.toLowerCase(), code, purpose, expiresAt]
    );

    // TODO: Integrate nodemailer to send code by email
    console.log(`[OTP] ${purpose} code for ${email}: ${code} (expires at ${expiresAt.toISOString()})`);

    return res.status(201).json({ success: true, message: 'OTP sent to email' });
  } catch (err) {
    console.error('OTP request error:', err);
    return res.status(500).json({ success: false, message: 'Internal server error' });
  }
});

// POST /api/auth/otp/verify
// body: { email, code, purpose, name?, role? }
router.post('/otp/verify', async (req, res) => {
  const client = await db._pool?.connect?.() || null;
  try {
    const { email, code, purpose = 'registration', name, role } = req.body || {};
    if (!email || !isValidEmail(email) || !code) {
      return res.status(400).json({ success: false, message: 'Email and code are required' });
    }

    // Validate OTP
    const { rows: otpRows } = await db.query(
      `SELECT id FROM otps 
       WHERE email = $1 AND code = $2 AND purpose = $3 
         AND verified = false AND expires_at > now()
       ORDER BY id DESC LIMIT 1`,
      [email.toLowerCase(), code, purpose]
    );
    if (!otpRows[0]) {
      return res.status(400).json({ success: false, message: 'Invalid or expired OTP' });
    }
    const otpId = otpRows[0].id;

    // Start transaction for user creation and OTP update
    if (!client) {
      // Fallback: continue without explicit transaction
    } else {
      await client.query('BEGIN');
    }

    // Try to fetch existing user by email
    const existing = await db.query('SELECT id, role, name, email, slug FROM users WHERE email = $1', [email.toLowerCase()]);
    let user = existing.rows[0];

    if (!user) {
      // Create new user; default role buyer, allow vendor if requested
      const finalRole = (role === 'vendor' || role === 'buyer') ? role : 'buyer';
      const baseSlug = slugify(name || email.split('@')[0]);
      let uniqueSlug = baseSlug;
      let attempts = 0;
      // Ensure unique slug
      // eslint-disable-next-line no-constant-condition
      while (true) {
        const { rows } = await db.query('SELECT 1 FROM users WHERE slug = $1', [uniqueSlug]);
        if (!rows[0]) break;
        attempts += 1;
        uniqueSlug = `${baseSlug}-${(Math.random().toString(36).slice(2, 6))}`;
        if (attempts > 5) break;
      }

      const insert = await db.query(
        'INSERT INTO users (role, name, email, slug) VALUES ($1, $2, $3, $4) RETURNING id, role, name, email, slug',
        [finalRole, name || null, email.toLowerCase(), uniqueSlug]
      );
      user = insert.rows[0];
    }

    // Mark OTP as verified
    await db.query('UPDATE otps SET verified = true WHERE id = $1', [otpId]);

    // Issue JWT
    const secret = process.env.JWT_SECRET;
    if (!secret) {
      throw new Error('JWT_SECRET is not configured');
    }
    const token = jwt.sign({ id: user.id, role: user.role, email: user.email }, secret, { expiresIn: '7d' });

    if (client) {
      await client.query('COMMIT');
    }

    return res.status(200).json({ success: true, token, user });
  } catch (err) {
    if (client) {
      try { await client.query('ROLLBACK'); } catch (e) {}
    }
    console.error('OTP verify error:', err);
    return res.status(500).json({ success: false, message: 'Internal server error' });
  } finally {
    if (client) client.release();
  }
});

module.exports = router;
