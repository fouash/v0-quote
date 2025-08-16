// src/utils/logger.js
// Centralized logger to sanitize and standardize logs

const stripControl = (s) => String(s).replace(/[\r\n\t\0\x08\x0B\x0C\x1B]/g, ' ');
const truncate = (s, len = 500) => (s.length > len ? s.slice(0, len) + '…' : s);

const redact = (msg) => {
  // Redact common secret-bearing fields
  let out = String(msg);
  out = out.replace(/(authorization|set-cookie|x-csrf-token)\s*:\s*[^,\n]+/gi, '$1: [REDACTED]');
  out = out.replace(/(Bearer)\s+[A-Za-z0-9-_\.]+/g, '$1 [REDACTED]');
  return out;
};

const format = (level, args) => {
  try {
    const parts = Array.from(args).map((a) => {
      if (a instanceof Error) {
        const msg = a.stack || a.message || String(a);
        return truncate(stripControl(redact(msg)));
      }
      if (typeof a === 'object') {
        try {
          return truncate(stripControl(redact(JSON.stringify(a))));
        } catch {
          return truncate(stripControl(redact(String(a))));
        }
      }
      return truncate(stripControl(redact(String(a))));
    });
    const ts = new Date().toISOString();
    return `[${ts}] ${level.toUpperCase()}: ${parts.join(' ')}`;
  } catch (e) {
    return `[logger-error] ${level}: failed to format log`;
  }
};

const log = (...args) => console.log(format('info', args));
const info = (...args) => console.info(format('info', args));
const warn = (...args) => console.warn(format('warn', args));
const error = (...args) => console.error(format('error', args));

module.exports = { log, info, warn, error };
