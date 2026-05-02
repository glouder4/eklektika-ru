import fs from 'fs';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const code = fs
  .readFileSync(path.join(__dirname, 'workflow-site-to-crm-multihook.sdk.ts'), 'utf8')
  .replace(/\r\n/g, '\n');
const payload = { workflowId: 'gGtsrfCPP9t3OyLj', code };
fs.writeFileSync(path.join(__dirname, 'pack_update.json'), JSON.stringify(payload), 'utf8');
const validateArgs = { code };
fs.writeFileSync(path.join(__dirname, 'args_validate.json'), JSON.stringify(validateArgs), 'utf8');
fs.writeFileSync(path.join(__dirname, 'mcp_args_one_line.json'), JSON.stringify(validateArgs), 'utf8');
console.log('written pack_update.json + args_validate.json + mcp_args_one_line.json, code bytes:', Buffer.byteLength(code, 'utf8'));
