import fs from 'fs';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const code = fs
	.readFileSync(path.join(__dirname, 'site-to-crm-workflow.sdk.ts'), 'utf8')
	.replace(/\r\n/g, '\n');
fs.writeFileSync(path.join(__dirname, 'vp.json'), JSON.stringify({ code }), 'utf8');
