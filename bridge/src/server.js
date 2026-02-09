const http = require('http');
const net = require('net');
const fs = require('fs');
const path = require('path');
const os = require('os');

const VERSION = '0.1.0';
const DEFAULT_CONFIG_PATH = path.join(__dirname, '..', 'config.json');

const readConfig = () => {
  const configPath = process.env.BRIDGE_CONFIG || DEFAULT_CONFIG_PATH;
  if (!fs.existsSync(configPath)) {
    throw new Error(`Config non trovato: ${configPath}`);
  }
  const raw = fs.readFileSync(configPath, 'utf8');
  const cfg = JSON.parse(raw);
  cfg.api_key = cfg.api_key || '';
  cfg.host = cfg.host || '127.0.0.1';
  cfg.port = Number(cfg.port || 4789);
  cfg.command_delay_ms = Number(cfg.command_delay_ms || 120);
  cfg.terminator = cfg.terminator || '';
  cfg.devices = cfg.devices || {};
  return { config: cfg, configPath };
};

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const getLocalIPv4 = () => {
  const nets = os.networkInterfaces();
  for (const name of Object.keys(nets)) {
    for (const netInfo of nets[name] || []) {
      if (netInfo && netInfo.family === 'IPv4' && !netInfo.internal) {
        return netInfo.address;
      }
    }
  }
  return null;
};

const scanNetwork = async (port, timeoutMs = 250, concurrency = 40) => {
  const localIp = getLocalIPv4();
  if (!localIp) {
    return { subnet: null, hosts: [] };
  }
  const parts = localIp.split('.');
  if (parts.length !== 4) {
    return { subnet: null, hosts: [] };
  }
  const subnet = parts.slice(0, 3).join('.') + '.';
  const candidates = Array.from({ length: 254 }, (_, i) => subnet + String(i + 1));
  const found = [];

  let index = 0;
  const worker = () => new Promise((resolve) => {
    const next = () => {
      const ip = candidates[index++];
      if (!ip) {
        resolve();
        return;
      }
      const socket = new net.Socket();
      let done = false;
      const finish = (ok) => {
        if (done) {
          return;
        }
        done = true;
        socket.destroy();
        if (ok) {
          found.push(ip);
        }
        next();
      };
      socket.setTimeout(timeoutMs);
      socket.once('connect', () => finish(true));
      socket.once('timeout', () => finish(false));
      socket.once('error', () => finish(false));
      socket.connect(port, ip);
    };
    next();
  });

  const workers = Array.from({ length: concurrency }, worker);
  await Promise.all(workers);
  return { subnet, hosts: found };
};

const buildItemCommand = (item) => {
  const qty = item.qty != null ? String(item.qty) : '';
  const price = item.price_cents != null ? String(item.price_cents) + 'H' : '';
  const desc = item.desc ? `"${item.desc}"` : '';
  if (item.plu != null) {
    return `${qty ? qty + '*' : ''}${desc}${price}${item.plu}P`;
  }
  const dept = item.dept != null ? String(item.dept) : '1';
  return `${qty ? qty + '*' : ''}${desc}${price}${dept}R`;
};

const buildPaymentCommand = (payment) => {
  const tenderMap = {
    cash: '1',
    check: '2',
    card: '3',
    voucher: '4',
    credit: '5'
  };
  const tender = tenderMap[payment.type] || '1';
  if (payment.amount_cents != null) {
    return `${payment.amount_cents}H${tender}T`;
  }
  return `${tender}T`;
};

const sendCommands = (device, commands, config) => {
  return new Promise((resolve, reject) => {
    const socket = new net.Socket();
    let buffer = '';
    let errorSeen = false;

    const timeoutMs = Number(device.timeout_ms || 8000);
    socket.setTimeout(timeoutMs);

    socket.on('data', (data) => {
      for (const byte of data) {
        if (byte === 0x13) {
          errorSeen = true;
        }
      }
      buffer += data.toString('ascii');
    });

    socket.on('timeout', () => {
      socket.destroy(new Error('Timeout comunicazione stampante'));
    });

    socket.on('error', (err) => {
      reject(err);
    });

    socket.on('close', () => {
      if (errorSeen) {
        reject(new Error('Stampante ha risposto con XOFF (errore)'));
        return;
      }
      resolve({
        ok: true,
        raw: buffer.trim()
      });
    });

    socket.connect(device.port, device.host, async () => {
      try {
        const terminator = device.terminator != null ? device.terminator : config.terminator;
        for (const cmd of commands) {
          const payload = String(cmd) + terminator;
          socket.write(payload, 'ascii');
          await sleep(config.command_delay_ms);
        }
        socket.end();
      } catch (err) {
        socket.destroy(err);
      }
    });
  });
};

const parseBody = (req) => {
  return new Promise((resolve, reject) => {
    let data = '';
    req.on('data', (chunk) => {
      data += chunk;
    });
    req.on('end', () => {
      if (!data) {
        resolve({});
        return;
      }
      try {
        resolve(JSON.parse(data));
      } catch (err) {
        reject(new Error('JSON non valido'));
      }
    });
  });
};

const sendJson = (res, status, payload) => {
  const body = JSON.stringify(payload);
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'Content-Type, X-Bridge-Key'
  });
  res.end(body);
};

const createServer = () => {
  const { config, configPath } = readConfig();

  const isLocalRequest = (req) => {
    const addr = req.socket && req.socket.remoteAddress ? req.socket.remoteAddress : '';
    return addr === '127.0.0.1' || addr === '::1' || addr === '::ffff:127.0.0.1';
  };

  const sendHtml = (res, status, body) => {
    res.writeHead(status, {
      'Content-Type': 'text/html; charset=utf-8'
    });
    res.end(body);
  };

  const buildUiPage = () => {
    return `<!doctype html>
<html lang="it">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bridge CUSTOM RT</title>
    <style>
      body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 24px; }
      .card { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
      h1 { margin-top: 0; font-size: 20px; }
      label { display: block; font-weight: 600; margin: 12px 0 6px; }
      input { width: 100%; padding: 10px 12px; border: 1px solid #d0d0d0; border-radius: 6px; }
      .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
      .actions { margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap; }
      .scan-results { margin-top: 12px; }
      .scan-results button { margin: 6px 6px 0 0; }
      button { padding: 10px 14px; border: none; border-radius: 6px; cursor: pointer; }
      .primary { background: #2563eb; color: #fff; }
      .secondary { background: #e5e7eb; color: #111; }
      .status { margin-top: 12px; font-size: 14px; }
      .muted { color: #6b7280; font-size: 13px; }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Bridge CUSTOM RT</h1>
      <p class="muted">Configurazione locale per stampante fiscale.</p>
      <form id="bridge-form">
        <label for="api_key">API key</label>
        <input id="api_key" name="api_key" type="text" />
        <div class="row">
          <div>
            <label for="device_id">Device ID</label>
            <input id="device_id" name="device_id" type="text" />
          </div>
          <div>
            <label for="device_host">IP stampante</label>
            <input id="device_host" name="device_host" type="text" placeholder="192.168.1.50" />
          </div>
        </div>
        <div class="row">
          <div>
            <label for="device_port">Porta TCP</label>
            <input id="device_port" name="device_port" type="number" value="9100" min="1" />
          </div>
          <div>
            <label for="command_delay_ms">Delay comandi (ms)</label>
            <input id="command_delay_ms" name="command_delay_ms" type="number" value="120" min="0" />
          </div>
        </div>
        <div class="row">
          <div>
            <label for="terminator">Terminatore</label>
            <input id="terminator" name="terminator" type="text" placeholder="(vuoto)" />
          </div>
          <div>
            <label for="device_terminator">Terminatore device</label>
            <input id="device_terminator" name="device_terminator" type="text" placeholder="(vuoto)" />
          </div>
        </div>
        <div class="actions">
          <button class="primary" type="submit">Salva configurazione</button>
          <button class="secondary" type="button" id="reload">Ricarica</button>
          <button class="secondary" type="button" id="scan">Cerca stampante</button>
        </div>
        <div class="status" id="status"></div>
        <div class="scan-results" id="scan-results"></div>
      </form>
    </div>
    <script>
      const statusEl = document.getElementById('status');
      const form = document.getElementById('bridge-form');
      const reloadBtn = document.getElementById('reload');
      const scanBtn = document.getElementById('scan');
      const scanResults = document.getElementById('scan-results');

      const setStatus = (message, ok) => {
        statusEl.textContent = message || '';
        statusEl.style.color = ok ? '#16a34a' : '#dc2626';
      };

      const loadConfig = () => {
        fetch('/config')
          .then(r => r.json())
          .then(data => {
            document.getElementById('api_key').value = data.api_key || '';
            document.getElementById('device_id').value = data.device_id || '';
            document.getElementById('device_host').value = data.device_host || '';
            document.getElementById('device_port').value = data.device_port || 9100;
            document.getElementById('command_delay_ms').value = data.command_delay_ms || 120;
            document.getElementById('terminator').value = data.terminator || '';
            document.getElementById('device_terminator').value = data.device_terminator || '';
            setStatus('Configurazione caricata.', true);
          })
          .catch(() => setStatus('Errore caricamento configurazione.', false));
      };

      form.addEventListener('submit', (event) => {
        event.preventDefault();
        const payload = {
          api_key: document.getElementById('api_key').value.trim(),
          device_id: document.getElementById('device_id').value.trim(),
          device_host: document.getElementById('device_host').value.trim(),
          device_port: parseInt(document.getElementById('device_port').value || '0', 10),
          command_delay_ms: parseInt(document.getElementById('command_delay_ms').value || '0', 10),
          terminator: document.getElementById('terminator').value || '',
          device_terminator: document.getElementById('device_terminator').value || ''
        };
        fetch('/config', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        })
          .then(r => r.json())
          .then(data => {
            if (!data || !data.ok) {
              throw new Error('Errore');
            }
            setStatus('Configurazione salvata.', true);
          })
          .catch(() => setStatus('Errore salvataggio configurazione.', false));
      });

      reloadBtn.addEventListener('click', loadConfig);

      scanBtn.addEventListener('click', () => {
        const port = parseInt(document.getElementById('device_port').value || '9100', 10);
        scanResults.textContent = '';
        setStatus('Scansione rete in corso...', true);
        fetch('/scan?port=' + String(port))
          .then(r => r.json())
          .then(data => {
            const hosts = Array.isArray(data.hosts) ? data.hosts : [];
            if (hosts.length === 0) {
              setStatus('Nessuna stampante trovata sulla rete.', false);
              return;
            }
            setStatus('Stampanti trovate: ' + hosts.length, true);
            scanResults.textContent = '';
            hosts.forEach(host => {
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.textContent = host;
              btn.addEventListener('click', () => {
                document.getElementById('device_host').value = host;
              });
              scanResults.appendChild(btn);
            });
          })
          .catch(() => setStatus('Errore scansione rete.', false));
      });
      loadConfig();
    </script>
  </body>
</html>`;
  };

  const server = http.createServer(async (req, res) => {
    if (req.method === 'OPTIONS') {
      res.writeHead(204, {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'Content-Type, X-Bridge-Key',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS'
      });
      res.end();
      return;
    }

    if (req.url === '/ui' && req.method === 'GET') {
      sendHtml(res, 200, buildUiPage());
      return;
    }

    if (req.url === '/config' && req.method === 'GET') {
      if (!isLocalRequest(req)) {
        sendJson(res, 403, { error: 'forbidden' });
        return;
      }
      const deviceIds = Object.keys(config.devices);
      const deviceId = deviceIds.length > 0 ? deviceIds[0] : '';
      const device = deviceId ? config.devices[deviceId] : null;
      sendJson(res, 200, {
        api_key: config.api_key,
        device_id: deviceId,
        device_host: device ? device.host : '',
        device_port: device ? device.port : 9100,
        device_terminator: device ? (device.terminator || '') : '',
        command_delay_ms: config.command_delay_ms,
        terminator: config.terminator || ''
      });
      return;
    }

    if (req.url === '/config' && req.method === 'POST') {
      if (!isLocalRequest(req)) {
        sendJson(res, 403, { error: 'forbidden' });
        return;
      }
      try {
        const body = await parseBody(req);
        const apiKey = String(body.api_key || '').trim();
        const deviceId = String(body.device_id || '').trim() || 'cassa_1';
        const deviceHost = String(body.device_host || '').trim();
        const devicePort = Number(body.device_port || 0);
        if (!deviceHost || !devicePort) {
          sendJson(res, 400, { error: 'invalid_payload' });
          return;
        }

        config.api_key = apiKey;
        config.command_delay_ms = Number(body.command_delay_ms || config.command_delay_ms || 120);
        config.terminator = body.terminator != null ? String(body.terminator) : '';
        config.devices = {
          [deviceId]: {
            host: deviceHost,
            port: devicePort,
            terminator: body.device_terminator != null ? String(body.device_terminator) : ''
          }
        };

        fs.writeFileSync(configPath, JSON.stringify(config, null, 2) + '\n', 'utf8');
        sendJson(res, 200, { ok: true });
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    if (req.url && req.url.startsWith('/scan') && req.method === 'GET') {
      if (!isLocalRequest(req)) {
        sendJson(res, 403, { error: 'forbidden' });
        return;
      }
      try {
        const parsed = new URL(req.url, 'http://localhost');
        const port = Number(parsed.searchParams.get('port') || 9100);
        const timeoutMs = Number(parsed.searchParams.get('timeout') || 250);
        const result = await scanNetwork(port, timeoutMs);
        sendJson(res, 200, { port, subnet: result.subnet, hosts: result.hosts });
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    if (req.url === '/health' && req.method === 'GET') {
      sendJson(res, 200, { status: 'ok', version: VERSION });
      return;
    }

    if (req.url === '/devices' && req.method === 'GET') {
      sendJson(res, 200, { devices: Object.keys(config.devices) });
      return;
    }

    if (config.api_key) {
      const apiKey = req.headers['x-bridge-key'];
      if (apiKey !== config.api_key) {
        sendJson(res, 401, { error: 'unauthorized' });
        return;
      }
    }

    if (req.url === '/send' && req.method === 'POST') {
      try {
        const body = await parseBody(req);
        const deviceId = body.device_id;
        const commands = Array.isArray(body.commands) ? body.commands : [];
        if (!deviceId || !config.devices[deviceId]) {
          sendJson(res, 400, { error: 'device_id_invalid' });
          return;
        }
        if (commands.length === 0) {
          sendJson(res, 400, { error: 'commands_empty' });
          return;
        }
        const device = config.devices[deviceId];
        const result = await sendCommands(device, commands, config);
        sendJson(res, 200, result);
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    if (req.url === '/receipt' && req.method === 'POST') {
      try {
        const body = await parseBody(req);
        const deviceId = body.device_id;
        const items = Array.isArray(body.items) ? body.items : [];
        const payment = body.payment || { type: 'cash' };
        const operator = body.operator;
        const includeSubtotal = body.include_subtotal !== false;
        const customerTaxCode = body.customer_tax_code;

        if (!deviceId || !config.devices[deviceId]) {
          sendJson(res, 400, { error: 'device_id_invalid' });
          return;
        }
        if (items.length === 0) {
          sendJson(res, 400, { error: 'items_empty' });
          return;
        }

        const commands = [];
        if (operator != null) {
          commands.push(`${operator}O`);
        }
        if (customerTaxCode) {
          commands.push(`"${customerTaxCode}"@39F`);
        }
        for (const item of items) {
          commands.push(buildItemCommand(item));
        }
        if (includeSubtotal) {
          commands.push('=');
        }
        commands.push(buildPaymentCommand(payment));

        const device = config.devices[deviceId];
        const result = await sendCommands(device, commands, config);
        sendJson(res, 200, result);
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    if (req.url === '/void' && req.method === 'POST') {
      try {
        const body = await parseBody(req);
        const deviceId = body.device_id;
        if (!deviceId || !config.devices[deviceId]) {
          sendJson(res, 400, { error: 'device_id_invalid' });
          return;
        }
        const device = config.devices[deviceId];
        const result = await sendCommands(device, ['25F'], config);
        sendJson(res, 200, result);
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    if (req.url === '/z-close' && req.method === 'POST') {
      try {
        const body = await parseBody(req);
        const deviceId = body.device_id;
        if (!deviceId || !config.devices[deviceId]) {
          sendJson(res, 400, { error: 'device_id_invalid' });
          return;
        }
        const device = config.devices[deviceId];
        const result = await sendCommands(device, ['z1Fc'], config);
        sendJson(res, 200, result);
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    if (req.url === '/x-report' && req.method === 'POST') {
      try {
        const body = await parseBody(req);
        const deviceId = body.device_id;
        if (!deviceId || !config.devices[deviceId]) {
          sendJson(res, 400, { error: 'device_id_invalid' });
          return;
        }
        const device = config.devices[deviceId];
        const result = await sendCommands(device, ['x1Fc'], config);
        sendJson(res, 200, result);
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    if (req.url === '/status' && req.method === 'POST') {
      try {
        const body = await parseBody(req);
        const deviceId = body.device_id;
        if (!deviceId || !config.devices[deviceId]) {
          sendJson(res, 400, { error: 'device_id_invalid' });
          return;
        }
        const device = config.devices[deviceId];
        const result = await sendCommands(device, ['89F'], config);
        sendJson(res, 200, result);
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    if (req.url === '/drawer' && req.method === 'POST') {
      try {
        const body = await parseBody(req);
        const deviceId = body.device_id;
        if (!deviceId || !config.devices[deviceId]) {
          sendJson(res, 400, { error: 'device_id_invalid' });
          return;
        }
        const device = config.devices[deviceId];
        const result = await sendCommands(device, ['a'], config);
        sendJson(res, 200, result);
      } catch (err) {
        sendJson(res, 500, { error: err.message });
      }
      return;
    }

    sendJson(res, 404, { error: 'not_found' });
  });

  server.listen(config.port, config.host, () => {
    console.log(`CUSTOM RT bridge in ascolto su http://${config.host}:${config.port}`);
  });
};

try {
  createServer();
} catch (err) {
  console.error(err.message);
  process.exit(1);
}
