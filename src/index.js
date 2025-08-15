// src/index.js
require('dotenv').config();
const express = require('express');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const cookieParser = require('cookie-parser');

// Import routes
const rfqRoutes = require('./api/routes/rfqs');
const bidRoutes = require('./api/routes/bids');

const app = express();

// --- Security Middlewares ---
app.use(helmet());

// Rate limiting
const limiter = rateLimit({
  windowMs: process.env.RATE_LIMIT_WINDOW_MS || 15 * 60 * 1000,
  max: process.env.RATE_LIMIT_MAX_REQUESTS || 100
});
app.use(limiter);

// --- Body Parsing ---
app.use(cookieParser());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// --- Authentication Middleware ---
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];
  
  if (!token) {
    return res.status(401).json({ error: 'Access token required' });
  }
  
  // In production, verify JWT token here
  // For now, mock user data
  req.user = { id: 1, role: 'user' };
  next();
};

// --- Routes ---
app.use('/api/rfq', authenticateToken, rfqRoutes);
app.use('/api/bids', authenticateToken, bidRoutes);

app.get('/', (req, res) => {
    res.send('RFQ Bidding Platform API is running!');
});

// --- Error Handling ---
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({ error: 'Something went wrong!' });
});

// --- Server ---
const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log(`Server is listening on port ${PORT}`);
    // In a real app, you would also connect to the database here.
    // e.g., connectToDatabase();
});

// For testing purposes, you might want to export the app
module.exports = app;
