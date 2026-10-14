const fs = require('fs');
const path = require('path');

console.log('[patch-vercel-php] Scanning for vercel-php builder files...');

let patchCount = 0;

function patchFile(filePath) {
  try {
    const data = fs.readFileSync(filePath, 'utf8');
    if (data.includes('launcher.launcher')) {
      const patched = data.replace(launcher.launcher/g, 'launcher.js');
      fs.writeFileSync(filePath, patched, 'utf8');
      console.log('[patch-vercel-php] Patched: ' + filePath);
      patchCount++;
      return true;
    }
  } catch (err) {}
  return false;
}

function scanDir(dir, depth) {
  depth = depth || 0;
  if (depth > 12 || !fs.existsSync(dir)) return;
  try {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
      const fullPath = path.join(dir, entry.name);
      if (entry.isDirectory()) {
        if (!['proc', 'sys', 'dev'].includes(entry.name)) {
          scanDir(fullPath, depth + 1);
        }
      } else if (entry.isFile() && entry.name.endsWith('.js') && fullPath.includes('vercel-php')) {
        patchFile(fullPath);
      }
    }
  } catch (err) {}
}

const targets = ['/vercel', '/root', '/tmp', process.cwd()];
for (const target of targets) {
  scanDir(target);
}

console.log('[patch-vercel-php] Completed. Patched ' + patchCount + ' file(s).');
