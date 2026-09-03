import express from 'express';
import { spawn } from 'child_process';
import http from 'http';
import path from 'path';
import { createServer as createViteServer } from 'vite';

async function startServer() {
  const app = express();
  app.set('trust proxy', true);
  const PORT = 3000;
  const PHP_PORT = 8080;

  // Launch background PHP built-in server serving the project root
  let phpProcess: any = null;
  try {
    phpProcess = spawn('php', ['-S', `127.0.0.1:${PHP_PORT}`, '-t', '.'], {
      stdio: 'inherit',
    });
    phpProcess.on('error', (err: any) => {
      console.warn('PHP server spawn notice (falling back or initializing):', err.message);
    });
  } catch (e: any) {
    console.warn('Could not launch PHP server directly:', e.message);
  }

  const cleanup = () => {
    try {
      if (phpProcess) phpProcess.kill();
    } catch (e) {}
  };

  process.on('exit', cleanup);
  process.on('SIGTERM', () => {
    cleanup();
    process.exit(0);
  });
  process.on('SIGINT', () => {
    cleanup();
    process.exit(0);
  });

  // Proxy function to forward requests to the PHP application
  const proxyToPhp = (req: express.Request, res: express.Response) => {
    const options: http.RequestOptions = {
      hostname: '127.0.0.1',
      port: PHP_PORT,
      path: req.url,
      method: req.method,
      headers: {
        ...req.headers,
        host: req.headers.host || `localhost:${PORT}`,
        'x-forwarded-for': req.ip,
        'x-forwarded-proto': req.protocol,
      },
    };

    const proxyReq = http.request(options, (proxyRes) => {
      if (proxyRes.headers['set-cookie']) {
        res.setHeader('set-cookie', proxyRes.headers['set-cookie']);
      }
      for (const [key, value] of Object.entries(proxyRes.headers)) {
        if (key !== 'set-cookie' && value !== undefined) {
          res.setHeader(key, value);
        }
      }
      res.writeHead(proxyRes.statusCode || 200);
      proxyRes.pipe(res);
    });

    proxyReq.on('error', (err) => {
      console.error('PHP Proxy Error:', err);
      if (!res.headersSent) {
        res.status(502).send('PHP Gateway Error: ' + err.message);
      }
    });

    req.pipe(proxyReq);
  };

  // Redirect root to FMS introduction portal
  app.get('/', (req, res) => {
    res.redirect('/fms/index.php');
  });

  app.get('/fms', (req, res) => {
    res.redirect('/fms/index.php');
  });

  // Direct static assets handler for FMS assets
  app.use('/fms/assets', express.static(path.join(process.cwd(), 'fms/assets')));
  app.use('/assets', express.static(path.join(process.cwd(), 'fms/assets')));

  // Route all FMS requests and PHP scripts directly to PHP server
  app.use((req, res, next) => {
    if (
      req.path.startsWith('/fms') ||
      req.path.startsWith('/admin') ||
      req.path.startsWith('/faculty') ||
      req.path.endsWith('.php') ||
      req.url.includes('.php?')
    ) {
      return proxyToPhp(req, res);
    }
    next();
  });

  // Vite development middleware for any secondary frontend assets
  if (process.env.NODE_ENV !== 'production') {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), 'dist');
    app.use(express.static(distPath));
    app.get('*', (req, res) => {
      res.sendFile(path.join(distPath, 'index.html'));
    });
  }

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`FMS Server running on http://0.0.0.0:${PORT}`);
  });
}

startServer();
