// src/config/db.js
try {
    const { Pool } = require('pg');
    
    const pool = new Pool({
        user: process.env.DB_USER || 'postgres',
        host: process.env.DB_HOST || 'localhost',
        database: process.env.DB_NAME || 'getlancer_quote',
        password: process.env.DB_PASSWORD,
        port: Number(process.env.DB_PORT) || 5432,
    });
    
    module.exports = {
        query: (text, params) => pool.query(text, params),
        connect: () => pool.connect(),
        _pool: pool,
    };
} catch (error) {
    console.log('PostgreSQL not available, using mock database');
    module.exports = require('./db-mock');
}
