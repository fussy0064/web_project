// server.js - entry point. Plain Node.js http server, no web framework.
require('dotenv').config();
const http = require('http');
const url = require('url');

const { handleApi } = require('./src/router');
const { serveStatic } = require('./src/static');
const { loadSession } = require('./src/session');
const { sendJSON } = require('./src/http-utils');

const PORT = process.env.PORT || 3000;

const server = http.createServer(async (req, res) => {
  const parsed = url.parse(req.url, true);
  const pathname = parsed.pathname;

  // Basic CORS support (harmless for same-origin use, useful if the frontend
  // is ever opened from a different origin during development).
  const origin = req.headers.origin;
  if (origin) {
    res.setHeader('Access-Control-Allow-Origin', origin);
    res.setHeader('Access-Control-Allow-Credentials', 'true');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  }

  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }

  if (pathname.startsWith('/api/')) {
    loadSession(req, res);
    try {
      await handleApi(req, res, pathname, parsed.query);
    } catch (e) {
      console.error('Unhandled error:', e);
      if (!res.headersSent) sendJSON(res, 500, { message: 'Internal server error' });
    }
    return;
  }

  const served = serveStatic(req, res, pathname);
  if (!served) {
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('Not found');
  }
});

server.listen(PORT, () => {
  console.log(`Fussy Electronics server running at http://localhost:${PORT}`);
});
