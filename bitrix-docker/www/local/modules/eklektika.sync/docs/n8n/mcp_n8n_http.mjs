/**
 * Call n8n MCP over HTTP (same endpoint as Cursor mcp.json eklektikaru-n8n-mcp).
 * Usage: node mcp_n8n_http.mjs validate|update
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const TOKEN =
  process.env.N8N_MCP_BEARER ||
  (() => {
    const p = process.env.CURSOR_MCP_JSON || 'C:/Users/Андрюша/.cursor/mcp.json';
    try {
      const j = JSON.parse(fs.readFileSync(p, 'utf8'));
      const h = j.mcpServers?.['eklektikaru-n8n-mcp']?.headers?.Authorization;
      if (h && h.startsWith('Bearer ')) return h.slice(7);
    } catch {
      /* ignore */
    }
    return '';
  })();

const base = process.env.N8N_MCP_URL || 'http://localhost:5678/mcp-server/http';

async function jsonRpc(id, method, params) {
  const res = await fetch(base, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json, text/event-stream',
      ...(TOKEN ? { Authorization: `Bearer ${TOKEN}` } : {}),
    },
    body: JSON.stringify({ jsonrpc: '2.0', id, method, params }),
  });
  const text = await res.text();
  let data;
  if (text.includes('event:') && text.includes('data:')) {
    const m = text.match(/data:\s*(\{[\s\S]*\})/);
    if (m) {
      try {
        data = JSON.parse(m[1]);
      } catch {
        /* fall through */
      }
    }
  }
  if (!data) {
    try {
      data = JSON.parse(text);
    } catch {
      console.error('Non-JSON response', res.status, text.slice(0, 800));
      process.exit(1);
    }
  }
  if (data.error) {
    console.error(JSON.stringify(data.error, null, 2));
    process.exit(1);
  }
  return data;
}

const cmd = process.argv[2] || 'validate';
const pack = JSON.parse(fs.readFileSync(path.join(__dirname, 'mcp_args_one_line.json'), 'utf8'));
const code = pack.code;

if (cmd === 'validate') {
  const r = await jsonRpc(1, 'tools/call', {
    name: 'validate_workflow',
    arguments: { code },
  });
  console.log(JSON.stringify(r, null, 2));
} else if (cmd === 'update') {
  const r = await jsonRpc(2, 'tools/call', {
    name: 'update_workflow',
    arguments: {
      workflowId: 'gGtsrfCPP9t3OyLj',
      code,
      description: 'Site to CRM: multihook registration + B24 REST proxy (from repo workflow-site-to-crm-multihook.sdk.ts)',
    },
  });
  console.log(JSON.stringify(r, null, 2));
} else {
  console.error('Usage: node mcp_n8n_http.mjs validate|update');
  process.exit(1);
}
