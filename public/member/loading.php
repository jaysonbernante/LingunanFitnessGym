<?php
session_start();
require_once 'session_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signing you in...</title>
  <style>
    :root {
      color-scheme: dark;
      --accent: #f6c451;
      --accent-strong: #f4b400;
      --bg: #0f172a;
      --panel: rgba(15, 23, 42, 0.92);
      --text: #f8fafc;
      --muted: #cbd5e1;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #0f172a 0%, #111827 50%, #1f2937 100%);
      color: var(--text);
      font-family: Arial, Helvetica, sans-serif;
    }

    .loading-screen {
      width: min(92vw, 420px);
      padding: 36px 28px;
      border-radius: 24px;
      background: var(--panel);
      box-shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
      text-align: center;
      border: 1px solid rgba(246, 196, 81, 0.22);
    }

    .brand-mark {
      width: 112px;
      height: 112px;
      border-radius: 50%;
      margin: 0 auto 18px;
      display: grid;
      place-items: center;
      background: #21201f;
      box-shadow: inset 0 0 0 6px #21201f, 0 14px 34px #21201f;
      overflow: hidden;
      padding: 10px;
    }

    .brand-mark img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }

    .spinner {
      width: 74px;
      height: 74px;
      border: 5px solid rgba(255,255,255,0.16);
      border-top-color: var(--accent);
      border-radius: 50%;
      margin: 0 auto 18px;
      animation: spin 0.9s linear infinite;
    }

    .title {
      font-size: 1.3rem;
      font-weight: 700;
      margin: 0 0 8px;
    }

    .message {
      margin: 0;
      color: var(--muted);
      line-height: 1.6;
      font-size: 0.95rem;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="loading-screen" role="status" aria-live="polite">
    <div class="brand-mark">
      <img src="../../assets/image/LogoBkg.png" alt="Lingunan Fitness Gym logo">
    </div>
    <div class="spinner" aria-hidden="true"></div>
    <h1 class="title">Signing you in...</h1>
    <p class="message">Loading your dashboard. Please wait a moment.</p>
  </div>

  <script>
    window.setTimeout(function () {
      window.location.replace('dashboard.php');
    }, 1400);
  </script>
</body>
</html>
