// db.js - MySQL connection pool (mysql2/promise)
const mysql = require('mysql2/promise');

const pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'electronics_db',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  dateStrings: true,
});

/**
 * Run a query and return rows.
 * @param {string} sql
 * @param {Array} params
 */
async function query(sql, params = []) {
  const [rows] = await pool.query(sql, params);
  return rows;
}

/**
 * Get a dedicated connection for transactions.
 */
async function getConnection() {
  return pool.getConnection();
}

module.exports = { pool, query, getConnection };
