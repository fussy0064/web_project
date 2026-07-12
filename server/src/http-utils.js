// http-utils.js - small helpers used instead of a web framework
const { randomUUID } = require('crypto');

/** Send a JSON response. */
function sendJSON(res, statusCode, data, extraHeaders = {}) {
  const body = JSON.stringify(data);
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
    ...extraHeaders,
  });
  res.end(body);
}

/** Read and parse a JSON request body. Resolves to {} if body is empty. */
function readJSONBody(req) {
  return new Promise((resolve, reject) => {
    let data = '';
    let size = 0;
    const MAX_SIZE = 10 * 1024 * 1024; // 10MB safety cap

    req.on('data', (chunk) => {
      size += chunk.length;
      if (size > MAX_SIZE) {
        reject(new Error('Payload too large'));
        req.destroy();
        return;
      }
      data += chunk;
    });

    req.on('end', () => {
      if (!data) return resolve({});
      try {
        resolve(JSON.parse(data));
      } catch (e) {
        reject(new Error('Invalid JSON input'));
      }
    });

    req.on('error', reject);
  });
}

/** Parse the Cookie header into an object. */
function parseCookies(req) {
  const header = req.headers.cookie;
  const cookies = {};
  if (!header) return cookies;
  header.split(';').forEach((pair) => {
    const idx = pair.indexOf('=');
    if (idx === -1) return;
    const key = pair.slice(0, idx).trim();
    const val = pair.slice(idx + 1).trim();
    cookies[key] = decodeURIComponent(val);
  });
  return cookies;
}

/** Build a Set-Cookie header string. */
function serializeCookie(name, value, options = {}) {
  let str = `${name}=${encodeURIComponent(value)}`;
  if (options.maxAge !== undefined) str += `; Max-Age=${options.maxAge}`;
  str += `; Path=${options.path || '/'}`;
  if (options.httpOnly !== false) str += '; HttpOnly';
  if (options.sameSite) str += `; SameSite=${options.sameSite}`;
  if (options.secure) str += '; Secure';
  return str;
}

function newId() {
  return randomUUID();
}

module.exports = {
  sendJSON,
  readJSONBody,
  parseCookies,
  serializeCookie,
  newId,
};
