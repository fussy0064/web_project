// routes/contact.js
const db = require('../db');
const { sendJSON, readJSONBody } = require('../http-utils');

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/** POST /api/contact */
async function submit(req, res) {
  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid JSON input' });
  }

  const { name, email, message } = body;

  if (!name) return sendJSON(res, 400, { message: 'Name is required' });
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return sendJSON(res, 400, { message: 'Valid email is required' });
  }
  if (!message) return sendJSON(res, 400, { message: 'Message is required' });

  const subject = body.subject || 'New Contact Request';

  try {
    const result = await db.query(
      'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)',
      [escapeHtml(name), escapeHtml(email), escapeHtml(subject), escapeHtml(message)]
    );
    sendJSON(res, 200, { message: 'Thank you! Your message has been sent successfully.', id: result.insertId });
  } catch (e) {
    sendJSON(res, 500, { message: 'Failed to store message: ' + e.message });
  }
}

module.exports = { submit };
