// src/config/db.js
const { Pool } = require('pg');
const logger = require('../utils/logger');

const pool = new Pool({
    user: process.env.DB_USER || 'postgres',
    host: process.env.DB_HOST || 'localhost',
    database: process.env.DB_NAME || 'getlancer_quote',
    password: process.env.DB_PASSWORD,
    port: Number(process.env.DB_PORT) || 5432,
});

pool.on('error', (err) => {
    logger.error('Database pool error:', err.message);
});

pool.on('connect', () => {
    logger.info('Database connected successfully');
});

module.exports = {
    query: (text, params) => pool.query(text, params),
    getPool: () => pool,
    _pool: pool,
};
