// upload.js - multipart/form-data parsing for product image uploads
const path = require('path');
const fs = require('fs');
const crypto = require('crypto');
const Busboy = require('busboy');

const UPLOAD_DIR = path.join(__dirname, '..', '..', 'public', 'uploads');

if (!fs.existsSync(UPLOAD_DIR)) {
  fs.mkdirSync(UPLOAD_DIR, { recursive: true });
}

const ALLOWED_EXTENSIONS = new Set(['.jpg', '.jpeg', '.png', '.gif', '.webp']);

/**
 * Parses a multipart/form-data request.
 * Resolves to { fields: {...}, imagePath: '/uploads/xyz.jpg' | null }
 */
function parseMultipart(req) {
  return new Promise((resolve, reject) => {
    let busboy;
    try {
      busboy = Busboy({ headers: req.headers, limits: { fileSize: 8 * 1024 * 1024 } });
    } catch (e) {
      return reject(new Error('Invalid multipart request'));
    }

    const fields = {};
    let imagePath = null;
    let fileError = null;

    busboy.on('field', (name, value) => {
      fields[name] = value;
    });

    busboy.on('file', (name, file, info) => {
      const { filename } = info;
      if (!filename) {
        file.resume();
        return;
      }
      const ext = path.extname(filename).toLowerCase();
      if (!ALLOWED_EXTENSIONS.has(ext)) {
        fileError = 'Unsupported image type';
        file.resume();
        return;
      }
      const safeName = `${Date.now()}-${crypto.randomBytes(6).toString('hex')}${ext}`;
      const destPath = path.join(UPLOAD_DIR, safeName);
      const writeStream = fs.createWriteStream(destPath);
      file.pipe(writeStream);
      imagePath = `/uploads/${safeName}`;
    });

    busboy.on('error', (err) => reject(err));

    busboy.on('finish', () => {
      if (fileError) return reject(new Error(fileError));
      resolve({ fields, imagePath });
    });

    req.pipe(busboy);
  });
}

module.exports = { parseMultipart, UPLOAD_DIR };
