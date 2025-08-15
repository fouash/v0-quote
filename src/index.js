// src/index.js
const express = require('express');
const bodyParser = require('body-parser');

// Import routes
const rfqRoutes = require('./api/routes/rfqs');
const bidRoutes = require('./api/routes/bids');
// ... import other routes as they are created

const app = express();

// --- Middlewares ---
// In a real app, you'd also have CORS, logging, etc.
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// --- Routes ---
// Here we are mounting the route files.
// The API endpoints will be prefixed with /api
app.use('/api/rfq', rfqRoutes);
app.use('/api/bids', bidRoutes);

// A simple root endpoint to check if the server is running
app.get('/', (req, res) => {
    res.send('RFQ Bidding Platform API is running!');
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
