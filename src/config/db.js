// src/config/db.js
const { Pool } = require('pg');

// IMPORTANT: Replace these with your actual database credentials
// In a real application, these should be stored securely in environment variables
const pool = new Pool({
    user: 'postgres', // your database user
    host: 'localhost',
    database: 'getlancer_quote', // your database name
    password: 'password', // your database password
    port: 5432,
});

module.exports = {
    query: (text, params) => pool.query(text, params),
};
