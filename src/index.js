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
app.use(helmet());

// Rate limiting
const limiter = rateLimit({
  windowMs: process.env.RATE_LIMIT_WINDOW_MS || 15 * 60 * 1000,
  max: process.env.RATE_LIMIT_MAX_REQUESTS || 100
});
app.use(limiter);

// --- Body Parsing ---
app.use(cookieParser());
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: true, limit: '1mb' }));

// --- CORS (development-friendly) ---
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
  if (req.method === 'OPTIONS') {
    return res.sendStatus(204);
  }
  next();
});

// --- Authentication Middleware ---
const jwt = require('jsonwebtoken');

const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];
  
  if (!token) {
    return res.status(401).json({ error: 'Access token required' });
  }
  
  try {
    const jwtSecret = process.env.JWT_SECRET;
    if (!jwtSecret) {
      throw new Error('JWT_SECRET not configured');
    }
    
    const decoded = jwt.verify(token, jwtSecret);
    req.user = decoded;
    next();
  } catch (error) {
    return res.status(403).json({ error: 'Invalid or expired token' });
  }
};

// --- Routes ---
app.use('/api/auth', authRoutes);
app.use('/api/rfq', authenticateToken, rfqRoutes);
app.use('/api/bids', authenticateToken, bidRoutes);
app.use('/api/keywords', keywordRoutes);

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
