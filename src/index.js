// src/index.js
require('dotenv').config();
const express = require('express');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const cookieParser = require('cookie-parser');

// Import routes
const rfqRoutes = require('./api/routes/rfqs');
const bidRoutes = require('./api/routes/bids');
const authRoutes = require('./api/routes/auth');
const keywordRoutes = require('./api/routes/keywords');
const http = require('http');

const app = express();

// --- Security Middlewares ---
const { helmetConfig, rateLimitConfig } = require('./config/security');
app.use(helmetConfig);
app.use(rateLimitConfig);

// --- Body Parsing ---
app.use(cookieParser());
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: true, limit: '1mb' }));

// --- CORS (secure configuration) ---
const cors = require('cors');
const { corsConfig } = require('./config/security');
app.use(cors(corsConfig));

// --- Authentication Middleware ---
const { authenticateToken, requireRole, optionalAuth } = require('./middleware/auth');

if (!process.env.JWT_SECRET) {
  console.error('JWT_SECRET environment variable is required');
  process.exit(1);
}

// --- Routes ---
app.use('/api/auth', authRoutes);
app.use('/api/rfq', optionalAuth, rfqRoutes);
app.use('/api/bids', authenticateToken, bidRoutes);
app.use('/api/keywords', optionalAuth, keywordRoutes);

app.get('/', (req, res) => {
    res.send('RFQ Bidding Platform API is running!');
});

// --- Error Handling ---
app.use((err, req, res, next) => {
  const sanitizedError = err.stack ? err.stack.replace(/[\r\n\t]/g, ' ').substring(0, 500) : 'Unknown error';
  console.error('Server error:', sanitizedError);
  res.status(500).json({ error: 'Something went wrong!' });
});

// --- Server ---
const PORT = process.env.PORT || 3000;
const server = http.createServer(app);

// Optional WebSocket (Socket.IO) setup
try {
  const { Server } = require('socket.io');
  const io = new Server(server, {
    cors: { origin: '*', methods: ['GET','POST','PUT','PATCH','DELETE','OPTIONS'] }
  });
  app.locals.io = io;
  io.on('connection', (socket) => {
    const { userId } = socket.handshake.query || {};
    if (userId) {
      socket.join(`user:${userId}`);
    }
  });
  console.log('WebSocket initialized');
} catch (e) {
  console.warn('Socket.IO not installed; skipping WebSocket setup');
}

server.listen(PORT, () => {
  console.log(`Server is listening on port ${PORT}`);
  // In a real app, you would also connect to the database here.
  // e.g., connectToDatabase();
});

// For testing purposes, you might want to export the app
module.exports = app;
