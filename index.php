<?php
date_default_timezone_set('Asia/Shanghai');
if (isset($_GET['ajax']) || isset($_GET['lat'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    $lat = $_GET['lat'] ?? '';
    $lon = $_GET['lon'] ?? '';
    if (empty($lat) || empty($lon)) {
        echo json_encode(['success' => false, 'error' => '需要经纬度参数'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $api_url = 'https://cn.apihz.cn/api/tianqi/tqybjw5.php?id=YOUR_APIHZ_ID&key=YOUR_APIHZ_KEY&lat=' . $lat . '&lon=' . $lon;
    function getRawData($url) { $ch = curl_init(); curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => 'Mozilla/5.0']); $d = curl_exec($ch); if (curl_error($ch)) return ['error' => 'API请求失败: ' . curl_error($ch)]; curl_close($ch); return $d; }
    function repairJson($t) { $t = trim($t); if ($t[0] !== '{') { $p = strpos($t, '{'); if ($p !== false) $t = substr($t, $p); } $o = substr_count($t, '{'); $c = substr_count($t, '}'); if ($o > $c) $t .= str_repeat('}', $o - $c); $j = @json_decode($t, true); return (json_last_error() === JSON_ERROR_NONE) ? $j : false; }
    $raw = getRawData($api_url); if (is_array($raw) && isset($raw['error'])) { echo json_encode($raw, JSON_UNESCAPED_UNICODE); exit; }
    $fd = repairJson($raw);
    echo json_encode($fd ? ['success' => true, 'data' => $fd, 'server_time' => date('Y-m-d H:i:s')] : ['success' => false, 'error' => '数据解析失败'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#3b8ad9">
    <link rel="apple-touch-icon" href="/weather.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Weather">
    <title>天气</title>
    <link rel="icon" type="image/png" href="weather.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%; min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            overflow-x: hidden; -webkit-tap-highlight-color: transparent; -webkit-font-smoothing: antialiased;
            background: #3b8ad9;
        }

        /* ===== GPU加速 ===== */
        .sky-layer, .layer-base, .layer-precip, .layer-effects,
        .cloud-group, .cloud-body, .cloud-bubble,
        .raindrop, .snowflake, .hailstone,
        .star-dot, .sun-glow, .sun-wrapper,
        .lightning-flash, .fog-overlay, .dust-overlay, .wind-line {
            will-change: transform, opacity;
            transform: translateZ(0);
            backface-visibility: hidden;
        }

        .sky-layer { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 0; pointer-events: none; transition: all 2s ease; }
        .layer-base { z-index: 0; transition: background 2s ease; }
        .bg-clear { background: linear-gradient(180deg, #3b8ad9 0%, #5da3e8 25%, #87ceeb 50%, #b8dff0 80%, #fef9d7 100%); }
        .bg-cloudy { background: linear-gradient(180deg, #8e9eab 0%, #a8b8c8 30%, #c0cdd8 60%, #d5dde5 100%); }
        .bg-overcast { background: linear-gradient(180deg, #6b7b8d 0%, #8595a6 30%, #9eaab8 60%, #b8c2cc 100%); }
        .bg-rain-light { background: linear-gradient(180deg, #5a6d7e 0%, #6e8090 30%, #8495a2 60%, #9aa8b2 100%); }
        .bg-rain-heavy { background: linear-gradient(180deg, #3a4a5a 0%, #4d5d6d 30%, #607080 60%, #758492 100%); }
        .bg-rain-storm { background: linear-gradient(180deg, #1a2a3a 0%, #2d3d4d 25%, #405060 50%, #556574 80%); }
        .bg-snow-light { background: linear-gradient(180deg, #b8c6d4 0%, #c8d4e0 30%, #d8e0e8 60%, #e8ecf0 100%); }
        .bg-snow-heavy { background: linear-gradient(180deg, #a0b0c0 0%, #b0bec8 30%, #c0cad4 60%, #d0d6de 100%); }
        .bg-snow-storm { background: linear-gradient(180deg, #8898a8 0%, #98a6b4 30%, #a8b4c0 60%, #b8c2cc 100%); }
        .bg-thunderstorm { background: linear-gradient(180deg, #1a1a2e 0%, #25253a 20%, #303048 40%, #1a1a2e 60%, #25253a 80%); }
        .bg-fog { background: linear-gradient(180deg, #c8d6e5 0%, #d5dfe8 30%, #e0e7ed 60%, #eceff2 100%); }
        .bg-haze { background: linear-gradient(180deg, #b8a890 0%, #c8bfa8 30%, #d6d0c0 60%, #e3dfd5 100%); }
        .bg-dust { background: linear-gradient(180deg, #c4a87c 0%, #d4c098 30%, #e0d0b0 60%, #ece0c8 100%); }
        .bg-sandstorm { background: linear-gradient(180deg, #b8860b 0%, #c8960c 20%, #d4a520 50%, #e0c060 80%, #f0d878 100%); }
        .bg-night-clear { background: linear-gradient(180deg, #0a0a2e 0%, #12123a 20%, #1a1a4e 45%, #1a2a5e 70%, #152040 100%); }
        .bg-night-cloudy { background: linear-gradient(180deg, #0f1328 0%, #1a1f3a 30%, #252b48 60%, #303758 100%); }
        .bg-night-rain { background: linear-gradient(180deg, #0a0e1e 0%, #121830 30%, #1a2038 60%, #222a42 100%); }
        .bg-night-snow { background: linear-gradient(180deg, #1a2238 0%, #252e46 30%, #303a52 60%, #3c4660 100%); }
        .layer-precip { z-index: 2; display: none; }
        .layer-effects { z-index: 3; display: none; }
        .sun-wrapper { position: absolute; top: 8%; right: 15%; width: 280px; height: 280px; z-index: 1; pointer-events: none; }
        .sun-glow { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 100%; height: 100%; background: radial-gradient(circle, rgba(255,245,200,0.95) 0%, rgba(255,235,150,0.7) 8%, rgba(255,220,100,0.4) 18%, rgba(255,200,60,0.2) 30%, rgba(255,180,30,0.08) 50%, transparent 100%); border-radius: 50%; animation: sunPulse 4s ease-in-out infinite; }
        @keyframes sunPulse { 0%,100%{opacity:0.85;transform:translate(-50%,-50%)scale(1);} 50%{opacity:1;transform:translate(-50%,-50%)scale(1.08);} }
        .is-night .sun-wrapper { display: none; }
        .moon-wrapper { position: absolute; top: 10%; right: 18%; width: 80px; height: 80px; z-index: 1; display: none; }
        .is-night .moon-wrapper { display: block; }
        .moon-body { width: 100%; height: 100%; background: radial-gradient(circle at 40% 40%, #f5f5dc 0%, #e8e8d0 50%, #d4d4b8 100%); border-radius: 50%; box-shadow: 0 0 20px rgba(255,255,255,0.5), 0 0 60px rgba(255,255,255,0.2); }
        .stars-container { position: absolute; top: 0; left: 0; width: 100%; height: 55%; display: none; z-index: 0; }
        .is-night .stars-container { display: block; }
        .star-dot { position: absolute; background: #fff; border-radius: 50%; animation: starTwinkle 3s ease-in-out infinite; }
        @keyframes starTwinkle { 0%,100%{opacity:0.15;} 50%{opacity:0.9;} }
        .clouds-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; }
        .cloud-group { position: absolute; animation: cloudFloat linear infinite; }
        @keyframes cloudFloat { 0%{transform:translateX(-300px);} 100%{transform:translateX(calc(100vw + 300px));} }
        .cloud-body { position: absolute; background: rgba(255,255,255,0.85); border-radius: 60px; bottom: 0; }
        .cloud-bubble { position: absolute; background: inherit; border-radius: 50%; }
        .raindrop { position: absolute; width: 2px; background: linear-gradient(180deg, transparent, rgba(255,255,255,0.35), rgba(255,255,255,0.55)); border-radius: 0 0 2px 2px; animation: rainFall linear infinite; }
        @keyframes rainFall { 0%{transform:translateY(-10vh);opacity:0;} 5%{opacity:1;} 85%{opacity:1;} 100%{transform:translateY(105vh);opacity:0;} }
        .snowflake { position: absolute; background: rgba(255,255,255,0.8); border-radius: 50%; filter: blur(1px); animation: snowFall linear infinite; }
        @keyframes snowFall { 0%{transform:translateY(-5vh)translateX(0)rotate(0deg);opacity:0;} 10%{opacity:0.9;} 90%{opacity:0.7;} 100%{transform:translateY(105vh)translateX(80px)rotate(360deg);opacity:0;} }
        .hailstone { position: absolute; width: 8px; height: 8px; background: radial-gradient(circle at 40% 40%, rgba(255,255,255,0.9), rgba(200,220,240,0.7)); border-radius: 50%; animation: hailFall linear infinite; }
        @keyframes hailFall { 0%{transform:translateY(-10vh)rotate(0deg);opacity:0;} 5%{opacity:1;} 90%{opacity:1;} 100%{transform:translateY(105vh)rotate(720deg);opacity:0;} }
        .lightning-flash { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.15); pointer-events: none; opacity: 0; transition: opacity 0.1s ease-in, opacity 0.3s ease-out; }
        .fog-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(ellipse at 50% 50%, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0.1) 40%, transparent 70%); animation: fogDrift 8s ease-in-out infinite; }
        @keyframes fogDrift { 0%,100%{opacity:0.6;} 50%{opacity:0.9;} }
        .dust-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(ellipse at 50% 50%, rgba(210,180,140,0.4) 0%, rgba(210,180,140,0.15) 40%, transparent 70%); animation: dustDrift 6s ease-in-out infinite; }
        @keyframes dustDrift { 0%,100%{opacity:0.5;} 50%{opacity:0.8;} }
        .wind-line { position: absolute; top: 0; left: -100px; height: 2px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent); animation: windSweep linear infinite; pointer-events: none; }
        @keyframes windSweep { 0%{transform:translateX(-200px);opacity:0;} 10%{opacity:0.8;} 90%{opacity:0.8;} 100%{transform:translateX(calc(100vw + 200px));opacity:0;} }

        #app { position: relative; z-index: 10; width: 100%; max-width: 480px; margin: 0 auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        .header { text-align: center; padding: 30px 20px 20px; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.15); }
        .is-light-bg .header { color: #1a1a2e; text-shadow: 0 1px 4px rgba(255,255,255,0.3); }
        .header-location { font-size: 18px; font-weight: 500; opacity: 0.85; letter-spacing: 1.5px; margin-bottom: 6px; }
        .header-temp { font-size: 100px; font-weight: 200; line-height: 1; letter-spacing: -5px; margin: 6px 0; transition: opacity 0.4s ease; }
        .header-desc { font-size: 18px; font-weight: 400; opacity: 0.8; transition: opacity 0.4s ease; }
        .header-feel { font-size: 14px; opacity: 0.6; margin-top: 4px; }
        .header-hilo { display: flex; justify-content: center; gap: 20px; font-size: 18px; font-weight: 500; margin-top: 8px; }
        .header-tip { font-size: 14px; opacity: 0.7; margin-top: 8px; font-style: italic; }
        .smart-tips { display: flex; flex-direction: column; gap: 6px; padding: 0; }
        .smart-tip-item { text-align: center; padding: 10px 16px; border-radius: 14px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); font-size: 13px; font-weight: 500; }
        .is-night .smart-tip-item { background: rgba(255,255,255,0.06); }
        .is-light-bg .smart-tip-item { background: rgba(255,255,255,0.4); }
        .loading-indicator { text-align: center; padding: 10px 0 20px; display: none; }
        .loading-indicator.show { display: block; }
        .loading-dots { display: inline-flex; gap: 8px; }
        .loading-dots span { width: 10px; height: 10px; border-radius: 50%; background: rgba(255,255,255,0.7); animation: dotBounce 1.2s ease-in-out infinite; }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dotBounce { 0%, 80%, 100% { transform: scale(0.6); opacity: 0.35; } 40% { transform: scale(1); opacity: 1; } }
        .is-night .loading-dots span { background: rgba(255,255,255,0.55); }
        .is-light-bg .loading-dots span { background: rgba(0,0,0,0.3); }

        .content-card { border-radius: 24px; backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); background: rgba(255,255,255,0.22); border: 0.5px solid rgba(255,255,255,0.3); color: #1a1a2e; overflow: hidden; }
        .is-night .content-card { background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.1); color: #e8e8f0; }
        .is-light-bg .content-card { background: rgba(255,255,255,0.45); border-color: rgba(0,0,0,0.06); }
        .card-section { padding: 18px 20px; }
        .card-section + .card-section { border-top: 0.5px solid rgba(255,255,255,0.2); }
        .section-label { font-size: 12px; font-weight: 600; opacity: 0.5; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 12px; }
        .forecast5-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; scroll-snap-type: x mandatory; }
        .forecast5-row::-webkit-scrollbar { display: none; }
        .f5-cell { flex: 0 0 auto; width: 80px; text-align: center; padding: 10px 4px; scroll-snap-align: start; border-radius: 14px; background: rgba(255,255,255,0.2); }
        .is-night .f5-cell { background: rgba(255,255,255,0.05); }
        .is-light-bg .f5-cell { background: rgba(255,255,255,0.4); }
        .f5-cell .d1 { font-size: 12px; font-weight: 600; opacity: 0.7; }
        .f5-cell .d2 { font-size: 10px; opacity: 0.5; margin-bottom: 4px; }
        .f5-cell .icon { font-size: 22px; margin: 4px 0; }
        .f5-cell .hi { font-size: 14px; font-weight: 600; color: #ff6b6b; }
        .f5-cell .lo { font-size: 12px; opacity: 0.7; color: #74b9ff; }
        .bar-row { display: flex; align-items: center; gap: 8px; margin: 6px 0; }
        .bar-label { font-size: 13px; opacity: 0.7; width: 40px; text-align: right; }
        .bar-track { flex: 1; height: 8px; border-radius: 4px; background: rgba(255,255,255,0.2); overflow: hidden; }
        .is-night .bar-track { background: rgba(255,255,255,0.1); }
        .bar-fill { height: 100%; border-radius: 4px; transition: width 0.8s ease; }
        .bar-fill.hi { background: linear-gradient(90deg, #ff9500, #ff3b30); }
        .bar-fill.lo { background: linear-gradient(90deg, #5ac8fa, #3498db); }
        .bar-fill.hum { background: linear-gradient(90deg, #5ac8fa, #007aff); }
        .bar-fill.wind { background: linear-gradient(90deg, #34c759, #ffcc00); }
        .bar-val { font-size: 13px; font-weight: 600; width: 70px; }
        .env-scroll { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
        .env-scroll::-webkit-scrollbar { display: none; }
        .env-chip { flex: 0 0 auto; text-align: center; padding: 10px 14px; border-radius: 14px; background: rgba(255,255,255,0.25); min-width: 68px; }
        .is-night .env-chip { background: rgba(255,255,255,0.06); }
        .is-light-bg .env-chip { background: rgba(255,255,255,0.5); }
        .env-chip .ic { font-size: 18px; margin-bottom: 2px; }
        .env-chip .lb { font-size: 10px; opacity: 0.5; }
        .env-chip .vl { font-size: 16px; font-weight: 600; margin-top: 2px; }
        .life-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .life-tile { text-align: center; padding: 12px 6px; border-radius: 14px; background: rgba(255,255,255,0.2); }
        .is-night .life-tile { background: rgba(255,255,255,0.05); }
        .is-light-bg .life-tile { background: rgba(255,255,255,0.4); }
        .life-tile .lt-icon { font-size: 24px; margin-bottom: 4px; }
        .life-tile .lt-name { font-size: 12px; font-weight: 600; opacity: 0.7; }
        .life-tile .lt-val { font-size: 13px; font-weight: 600; margin-top: 3px; }
        .life-tile .lt-desc { font-size: 10px; opacity: 0.6; margin-top: 2px; }
        .tag-ok { color: #34c759; } .tag-mid { color: #ff9500; } .tag-bad { color: #ff3b30; }
        .is-night .tag-ok { color: #6ee78e; } .is-night .tag-mid { color: #ffd54f; } .is-night .tag-bad { color: #ff6e6e; }
        .sun-arc-wrap { position: relative; height: 80px; margin: 8px 0; }
        .sun-arc-wrap svg { width: 100%; height: 100%; }
        .alarm-strip { text-align: center; padding: 10px 16px; border-radius: 14px; font-size: 13px; font-weight: 600; }
        .alarm-strip.safe { background: rgba(52,199,89,0.15); color: #1a6b30; }
        .alarm-strip.warn { background: rgba(255,59,48,0.15); color: #8a1a15; }
        .is-night .alarm-strip.safe { color: #6ee78e; } .is-night .alarm-strip.warn { color: #ff6e6e; }
        .bottom-bar { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 4px 0; }
        .locate-btn { padding: 12px 28px; border-radius: 24px; border: none; background: rgba(255,255,255,0.18); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px); color: #fff; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.3s cubic-bezier(0.25,0.1,0.25,1); box-shadow: 0 4px 16px rgba(0,0,0,0.08), inset 0 1px 0 rgba(255,255,255,0.2); border: 0.5px solid rgba(255,255,255,0.25); }
        .locate-btn:active { background: rgba(255,255,255,0.3); transform: scale(0.96); }
        .is-light-bg .locate-btn { color: #1a1a2e; background: rgba(255,255,255,0.55); border-color: rgba(0,0,0,0.08); }
        .settings-glass-btn { width: 44px; height: 44px; border-radius: 22px; border: none; background: rgba(255,255,255,0.18); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px); color: #fff; font-size: 20px; cursor: pointer; transition: all 0.3s cubic-bezier(0.25,0.1,0.25,1); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(0,0,0,0.08), inset 0 1px 0 rgba(255,255,255,0.2); border: 0.5px solid rgba(255,255,255,0.25); }
        .settings-glass-btn:active { background: rgba(255,255,255,0.3); transform: scale(0.92); }
        .is-light-bg .settings-glass-btn { color: #1a1a2e; background: rgba(255,255,255,0.55); border-color: rgba(0,0,0,0.08); }
        .settings-glass-btn.open { background: rgba(255,255,255,0.28); }
        .settings-panel { overflow: hidden; max-height: 0; transition: max-height 0.4s cubic-bezier(0.25,0.1,0.25,1), padding 0.4s ease; background: rgba(255,255,255,0.12); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border-radius: 20px; border: 0.5px solid rgba(255,255,255,0.2); padding: 0 16px; margin-top: 4px; }
        .settings-panel.show { max-height: 300px; padding: 16px; }
        .is-night .settings-panel { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); }
        .is-light-bg .settings-panel { background: rgba(255,255,255,0.5); border-color: rgba(0,0,0,0.08); }
        .settings-section-label { font-size: 11px; font-weight: 600; opacity: 0.5; letter-spacing: 1px; text-align: center; margin: 8px 0 6px; }
        .settings-row { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        .settings-chip { padding: 8px 16px; border-radius: 20px; border: none; background: rgba(255,255,255,0.12); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); color: #fff; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.25s cubic-bezier(0.25,0.1,0.25,1); border: 0.5px solid rgba(255,255,255,0.15); }
        .settings-chip:active { background: rgba(255,255,255,0.25); transform: scale(0.95); }
        .settings-chip.active { background: rgba(255,255,255,0.32); border-color: rgba(255,255,255,0.5); font-weight: 600; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
        .is-light-bg .settings-chip { color: #1a1a2e; background: rgba(255,255,255,0.4); border-color: rgba(0,0,0,0.06); }
        .is-light-bg .settings-chip.active { background: rgba(255,255,255,0.7); border-color: rgba(0,0,0,0.2); }
        .error-toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 999; background: rgba(255,59,48,0.9); color: #fff; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 600; backdrop-filter: blur(10px); display: none; }
        @media (min-width: 768px) { #app { max-width: 520px; padding: 20px; } }
    </style>
</head>
<body>
    <div class="sky-layer layer-base" id="layer-base">
        <div class="sun-wrapper"><div class="sun-glow"></div></div>
        <div class="moon-wrapper"><div class="moon-body"></div></div>
        <div class="stars-container" id="stars-layer"></div>
        <div class="clouds-layer" id="clouds-layer"></div>
    </div>
    <div class="sky-layer layer-precip" id="layer-precip"></div>
    <div class="sky-layer layer-effects" id="layer-effects"></div>
    <div class="wind-lines" id="wind-layer"></div>

    <div id="app">
        <div class="header">
            <div class="header-location" id="loc">请允许定位...</div>
            <div class="header-temp" id="temp">--°</div>
            <div class="header-desc" id="desc">需要定位权限</div>
            <div class="header-feel" id="feel"></div>
            <div class="header-hilo">
                <span>H:<span id="hiV" style="color:#ff6b6b">--</span>°</span>
                <span>L:<span id="loV" style="color:#74b9ff">--</span>°</span>
            </div>
            <div class="header-tip" id="header-tip"></div>
            <div class="smart-tips" id="smart-tips"></div>
        </div>

        <div class="loading-indicator" id="loading-indicator">
            <div class="loading-dots">
                <span></span><span></span><span></span>
            </div>
        </div>

        <div class="content-card">
            <div class="card-section">
                <div class="section-label" id="lbl-forecast">5天预报</div>
                <div class="forecast5-row" id="forecast5-row"></div>
            </div>
            <div class="card-section">
                <div class="section-label" id="lbl-sun">日出日落</div>
                <div class="sun-arc-wrap" id="sun-arc"></div>
            </div>
            <div class="card-section">
                <div class="section-label" id="lbl-detail">详细指标</div>
                <div class="bar-row"><span class="bar-label" id="lbl-hi">高温</span><div class="bar-track"><div class="bar-fill hi" id="bar-hi" style="width:0%"></div></div><span class="bar-val" id="bar-hi-val">--</span></div>
                <div class="bar-row"><span class="bar-label" id="lbl-lo">低温</span><div class="bar-track"><div class="bar-fill lo" id="bar-lo" style="width:0%"></div></div><span class="bar-val" id="bar-lo-val">--</span></div>
                <div class="bar-row"><span class="bar-label" id="lbl-hum">湿度</span><div class="bar-track"><div class="bar-fill hum" id="bar-hum" style="width:0%"></div></div><span class="bar-val" id="bar-hum-val">--</span></div>
                <div class="bar-row"><span class="bar-label" id="lbl-wind">风力</span><div class="bar-track"><div class="bar-fill wind" id="bar-wind" style="width:0%"></div></div><span class="bar-val" id="bar-wind-val">--</span></div>
            </div>
            <div class="card-section">
                <div class="section-label" id="lbl-env">环境数据</div>
                <div class="env-scroll" id="env-scroll"></div>
            </div>
            <div class="card-section">
                <div class="section-label" id="lbl-life">生活指数</div>
                <div class="life-grid" id="life-grid"></div>
            </div>
        </div>
        <div class="alarm-strip safe" id="alarm-strip">✅ 无气象预警</div>

        <div class="bottom-bar">
            <button class="locate-btn" id="locate-btn" onclick="locateMe()">📍 刷新定位</button>
            <button class="settings-glass-btn" id="settings-btn" onclick="toggleSettings()">⚙️</button>
        </div>

        <div class="settings-panel" id="settings-panel">
            <div class="settings-section-label">温度单位</div>
            <div class="settings-row">
                <button class="settings-chip active" id="btn-c" onclick="setUnit('c')">°C 摄氏度</button>
                <button class="settings-chip" id="btn-f" onclick="setUnit('f')">°F 华氏度</button>
            </div>
            <div class="settings-section-label">语言 Language</div>
            <div class="settings-row">
                <button class="settings-chip active" id="btn-zh" onclick="setLang('zh')">中文</button>
                <button class="settings-chip" id="btn-en" onclick="setLang('en')">English</button>
                <button class="settings-chip" id="btn-ja" onclick="setLang('ja')">日本語</button>
                <button class="settings-chip" id="btn-ko" onclick="setLang('ko')">한국어</button>
                <button class="settings-chip" id="btn-ru" onclick="setLang('ru')">Русский</button>
            </div>
        </div>
    </div>
    <div class="error-toast" id="error-toast"></div>

    <script>
        function countryFlag(code) { if (!code || code.length !== 2) return ''; return String.fromCodePoint(code.charCodeAt(0) + 127397, code.charCodeAt(1) + 127397); }
        function translateWeather(w) {
            if (!w) return '';
            var map = i18n.weather_terms;
            if (!map) return w;
            if (map[w]) return map[w];
            var result = w;
            for (var key in map) { if (w.indexOf(key) !== -1 && key.length > 1) { result = result.replace(key, map[key]); } }
            if (/[\u4e00-\u9fa5]/.test(result) && currentLang !== 'zh') {
                var parts = w.split(/[,，、\s]+/);
                var translated = parts.map(function(p) { return map[p] || p; }).join(', ');
                if (!/[\u4e00-\u9fa5]/.test(translated)) return translated;
            }
            return result;
        }
        function getWindScaleText(windSpeed) {
            var ws = i18n.wind_scale;
            if (windSpeed < 1) return ws['微风'];
            if (windSpeed < 3) return ws['微风'];
            if (windSpeed < 6) return ws['和风'];
            if (windSpeed < 9) return ws['清风'];
            if (windSpeed < 12) return ws['强风'];
            if (windSpeed < 15) return ws['疾风'];
            if (windSpeed < 18) return ws['大风'];
            if (windSpeed < 21) return ws['烈风'];
            if (windSpeed < 25) return ws['狂风'];
            if (windSpeed < 30) return ws['暴风'];
            return ws['飓风'];
        }

        var I18N = {
            zh: {
                forecast: '5天预报', sunrise_sunset: '日出日落', detail: '详细指标', env: '环境数据', life: '生活指数',
                hi: '高温', lo: '低温', hum: '湿度', wind: '风力', feel: '体感', humidity: '湿度',
                pressure: '气压', visibility: '能见度', wind_speed: '风速', clouds: '云量',
                no_alarm: '无气象预警', locate: '刷新定位', loading: '加载中...', sun_rise: '日出', sun_set: '日落', pos: '定位中...',
                need_location: '我们需要定位权限才能获取天气，请允许定位后刷新页面',
                allow_location: '请在浏览器设置中允许定位后刷新页面',
                locating: '请允许定位...',
                no_geo: '请使用支持定位的浏览器',
                dressing: '穿衣', carwash: '洗车', sport: '运动', comfort: '舒适', cold: '感冒', uv: '紫外线', travel: '旅游', fishing: '钓鱼', morning: '晨练',
                hot: '炎热', warm: '较热', comf: '舒适', cool: '凉爽', cold2: '较冷', freezing: '寒冷',
                not_suit: '不适宜', suit: '适宜', not_very: '较不适宜', good: '一般', easy: '易发', rare: '少发', none: '无', strong: '强', mid: '中等', weak: '弱',
                desc_dress_hot: '短袖短裤', desc_dress_warm: '短袖+薄长裤', desc_dress_comf: '长袖+薄外套', desc_dress_cool: '毛衣+外套', desc_dress_cold: '棉衣+围巾', desc_dress_freezing: '厚羽绒服',
                desc_wash_no: '等晴天再洗', desc_wash_yes: '放心洗车',
                desc_sport_no: '风大不宜运动', desc_sport_cold: '太冷注意保暖', desc_sport_hot: '太热小心中暑', desc_sport_yes: '适合户外运动',
                desc_uv_no: '夜间无紫外线', desc_uv_yes: '涂防晒霜', desc_uv_mid: '无需防护',
                desc_travel_no: '带好雨具', desc_travel_yes: '适合出游',
                desc_fishing_wind: '风大鱼不咬钩', desc_fishing_yes: '可以试试',
                desc_morning_no: '风大不宜晨练', desc_morning_yes: '适合锻炼',
                weather_stable: '未来天气状况良好', rain_soon: '小时后可能降水', wind_strong: '风力较大，注意防风', dry_air: '空气干燥，多喝水',
                tip_cold1: '裹紧羽绒服，天冷别硬扛', tip_cold2: '围巾手套带上', tip_cool: '薄外套刚好', tip_comf: '舒适宜人，适合出门走走', tip_warm: '暖和，注意防晒', tip_hot: '炎热，小心中暑',
                tip_dry: '空气干燥，涂护手霜、多喝水', tip_wet: '湿度很大，注意防潮', tip_windy: '风有点大，帽子戴稳', tip_rain: '出门记得带伞', tip_snow: '下雪路滑，小心慢行',
                weather_terms: {
                    '晴':'晴','少云':'少云','晴，少云':'晴，少云','多云':'多云','阴':'阴','阴，多云':'阴，多云',
                    '小雨':'小雨','中雨':'中雨','大雨':'大雨','暴雨':'暴雨','大暴雨':'大暴雨','特大暴雨':'特大暴雨',
                    '阵雨':'阵雨','毛毛雨':'毛毛雨','雷阵雨':'雷阵雨','雷暴':'雷暴','雨夹雪':'雨夹雪','冻雨':'冻雨',
                    '小雪':'小雪','中雪':'中雪','大雪':'大雪','暴雪':'暴雪','阵雪':'阵雪',
                    '雾':'雾','轻雾':'轻雾','浓雾':'浓雾','霾':'霾','沙尘暴':'沙尘暴','扬沙':'扬沙','浮尘':'浮尘',
                    '冰雹':'冰雹','龙卷风':'龙卷风','台风':'台风','飓风':'飓风','霰':'霰','米雪':'米雪','冰粒':'冰粒','冰针':'冰针',
                    '沙尘':'沙尘','烟幕':'烟幕','火山灰':'火山灰','尘卷风':'尘卷风','热带风暴':'热带风暴',
                    '强台风':'强台风','超强台风':'超强台风','高温':'高温','酷热':'酷热','严寒':'严寒','闷热':'闷热',
                    '干热':'干热','湿冷':'湿冷','干冷':'干冷','凉爽':'凉爽',
                    '晴朗无云':'晴朗无云','晴间多云':'晴间多云','局部多云':'局部多云','阳光明媚':'阳光明媚',
                    '卷云':'卷云','卷积云':'卷积云','层云':'层云','层积云':'层积云','积云':'积云','淡积云':'淡积云','碎积云':'碎积云','积雨云':'积雨云','雨层云':'雨层云',
                    '彩虹':'彩虹','霓':'霓','极光':'极光','日晕':'日晕','月晕':'月晕','华':'华','宝光':'宝光','海市蜃楼':'海市蜃楼','朝霞':'朝霞','晚霞':'晚霞','蓝调时刻':'蓝调时刻',
                    '龙卷':'龙卷','尘卷':'尘卷','热带低压':'热带低压','温带气旋':'温带气旋','下击暴流':'下击暴流','飑线':'飑线'
                },
                wind_scale: {'微风':'微风','和风':'和风','清风':'清风','强风':'强风','疾风':'疾风','大风':'大风','烈风':'烈风','狂风':'狂风','暴风':'暴风','飓风':'飓风'}
            },
            en: {
                forecast: '5-Day Forecast', sunrise_sunset: 'Sunrise & Sunset', detail: 'Details', env: 'Environment', life: 'Life Index',
                hi: 'High', lo: 'Low', hum: 'Humidity', wind: 'Wind', feel: 'Feels', humidity: 'Humidity',
                pressure: 'Pressure', visibility: 'Visibility', wind_speed: 'Wind Speed', clouds: 'Clouds',
                no_alarm: 'No Alerts', locate: 'Relocate', loading: 'Loading...', sun_rise: 'Sunrise', sun_set: 'Sunset', pos: 'Locating...',
                need_location: 'We need location permission to get weather. Please allow and refresh.',
                allow_location: 'Please enable location in browser settings and refresh.',
                locating: 'Please allow location...',
                no_geo: 'Please use a browser that supports geolocation.',
                dressing: 'Dressing', carwash: 'Car Wash', sport: 'Sport', comfort: 'Comfort', cold: 'Cold', uv: 'UV', travel: 'Travel', fishing: 'Fishing', morning: 'Morning',
                hot: 'Hot', warm: 'Warm', comf: 'Comfortable', cool: 'Cool', cold2: 'Cold', freezing: 'Freezing',
                not_suit: 'No', suit: 'Yes', not_very: 'Not Ideal', good: 'Fair', easy: 'Prone', rare: 'Unlikely', none: 'None', strong: 'Strong', mid: 'Moderate', weak: 'Weak',
                desc_dress_hot: 'Shorts', desc_dress_warm: 'T-shirt', desc_dress_comf: 'Long sleeves', desc_dress_cool: 'Sweater', desc_dress_cold: 'Overcoat', desc_dress_freezing: 'Down jacket',
                desc_wash_no: 'Wait for sun', desc_wash_yes: 'Good for wash',
                desc_sport_no: 'Too windy', desc_sport_cold: 'Too cold', desc_sport_hot: 'Too hot', desc_sport_yes: 'Great for exercise',
                desc_uv_no: 'No UV', desc_uv_yes: 'Sunscreen', desc_uv_mid: 'No protection',
                desc_travel_no: 'Bring umbrella', desc_travel_yes: 'Good for trip',
                desc_fishing_wind: 'Too windy', desc_fishing_yes: 'Good for fishing',
                desc_morning_no: 'Too windy', desc_morning_yes: 'Good for run',
                weather_stable: 'Weather looks good', rain_soon: 'h rain possible', wind_strong: 'Strong wind', dry_air: 'Dry, drink water',
                tip_cold1: 'Freezing, down jacket', tip_cold2: 'Cold, gloves on', tip_cool: 'Light jacket', tip_comf: 'Pleasant, go walk', tip_warm: 'Warm, sunscreen', tip_hot: 'Hot, be careful',
                tip_dry: 'Dry, moisturize', tip_wet: 'Damp, beware', tip_windy: 'Windy, hold hat', tip_rain: 'Bring umbrella', tip_snow: 'Slippery, walk safe',
                weather_terms: {
                    '晴':'Clear','少云':'Few Clouds','晴，少云':'Clear, Few Clouds','多云':'Cloudy','阴':'Overcast','阴，多云':'Overcast, Cloudy',
                    '小雨':'Light Rain','中雨':'Moderate Rain','大雨':'Heavy Rain','暴雨':'Storm','大暴雨':'Heavy Storm','特大暴雨':'Extreme Rain',
                    '阵雨':'Showers','毛毛雨':'Drizzle','雷阵雨':'Thundershowers','雷暴':'Thunderstorm','雨夹雪':'Sleet','冻雨':'Freezing Rain',
                    '小雪':'Light Snow','中雪':'Moderate Snow','大雪':'Heavy Snow','暴雪':'Blizzard','阵雪':'Snow Showers',
                    '雾':'Fog','轻雾':'Mist','浓雾':'Dense Fog','霾':'Haze','沙尘暴':'Sandstorm','扬沙':'Dust','浮尘':'Dust',
                    '冰雹':'Hail','龙卷风':'Tornado','台风':'Typhoon','飓风':'Hurricane','霰':'Graupel','米雪':'Snow Grains','冰粒':'Ice Pellets','冰针':'Ice Needles',
                    '沙尘':'Sand/Dust','烟幕':'Smoke','火山灰':'Volcanic Ash','尘卷风':'Dust Devil','热带风暴':'Tropical Storm',
                    '强台风':'Severe Typhoon','超强台风':'Super Typhoon','高温':'Heat Wave','酷热':'Extreme Heat','严寒':'Severe Cold','闷热':'Muggy',
                    '干热':'Dry Heat','湿冷':'Damp Cold','干冷':'Dry Cold','凉爽':'Cool',
                    '晴朗无云':'Clear Sky','晴间多云':'Partly Cloudy','局部多云':'Scattered Clouds','阳光明媚':'Sunny',
                    '卷云':'Cirrus','卷积云':'Cirrocumulus','层云':'Stratus','层积云':'Stratocumulus','积云':'Cumulus','淡积云':'Cumulus Humilis','碎积云':'Fractocumulus','积雨云':'Cumulonimbus','雨层云':'Nimbostratus',
                    '彩虹':'Rainbow','霓':'Secondary Rainbow','极光':'Aurora','日晕':'Solar Halo','月晕':'Lunar Halo','华':'Corona','宝光':'Glory','海市蜃楼':'Mirage','朝霞':'Morning Glow','晚霞':'Afterglow','蓝调时刻':'Blue Hour',
                    '龙卷':'Tornado','尘卷':'Dust Devil','热带低压':'Tropical Depression','温带气旋':'Extratropical Cyclone','下击暴流':'Downburst','飑线':'Squall Line'
                },
                wind_scale: {'微风':'Light Breeze','和风':'Moderate Breeze','清风':'Fresh Breeze','强风':'Strong Breeze','疾风':'Near Gale','大风':'Gale','烈风':'Strong Gale','狂风':'Storm','暴风':'Violent Storm','飓风':'Hurricane'}
            },
            ja: {
                forecast: '5日間予報', sunrise_sunset: '日の出・日の入り', detail: '詳細', env: '環境', life: '生活指数',
                hi: '最高', lo: '最低', hum: '湿度', wind: '風力', feel: '体感', humidity: '湿度',
                pressure: '気圧', visibility: '視界', wind_speed: '風速', clouds: '雲量',
                no_alarm: '警報なし', locate: '更新', loading: '読込中...', sun_rise: '日の出', sun_set: '日の入り', pos: '位置取得中...',
                need_location: '天気を取得するには位置情報の許可が必要です。許可して更新してください。',
                allow_location: 'ブラウザ設定で位置情報を許可して更新してください。',
                locating: '位置情報を許可してください...',
                no_geo: '位置情報に対応したブラウザを使用してください。',
                dressing: '服装', carwash: '洗車', sport: '運動', comfort: '快適', cold: '風邪', uv: '紫外線', travel: '旅行', fishing: '釣り', morning: '朝練',
                hot: '暑い', warm: '暖かい', comf: '快適', cool: '涼しい', cold2: '寒い', freezing: '極寒',
                not_suit: '不適', suit: '適', not_very: 'やや不適', good: '普通', easy: '注意', rare: '低い', none: 'なし', strong: '強い', mid: '中程度', weak: '弱い',
                desc_dress_hot: '半袖', desc_dress_warm: 'Tシャツ', desc_dress_comf: '長袖', desc_dress_cool: 'セーター', desc_dress_cold: 'コート', desc_dress_freezing: 'ダウン',
                desc_wash_no: '晴れ待ち', desc_wash_yes: '洗車日和',
                desc_sport_no: '風強い', desc_sport_cold: '寒すぎ', desc_sport_hot: '暑すぎ', desc_sport_yes: '運動日和',
                desc_uv_no: '夜間', desc_uv_yes: '日焼け止め', desc_uv_mid: '対策不要',
                desc_travel_no: '傘持参', desc_travel_yes: '旅行日和',
                desc_fishing_wind: '風強', desc_fishing_yes: '釣り日和',
                desc_morning_no: '風強', desc_morning_yes: '朝練日和',
                weather_stable: '天気良好', rain_soon: '時間後雨', wind_strong: '強風注意', dry_air: '乾燥注意',
                tip_cold1: '極寒', tip_cold2: '手袋着用', tip_cool: '上着', tip_comf: '快適', tip_warm: '日焼け止め', tip_hot: '熱中症注意',
                tip_dry: '保湿', tip_wet: '多湿', tip_windy: '帽子注意', tip_rain: '傘', tip_snow: '滑注意',
                weather_terms: {
                    '晴':'晴れ','少云':'少雲','晴，少云':'晴れ、少雲','多云':'曇り','阴':'曇り','阴，多云':'曇り',
                    '小雨':'小雨','中雨':'中雨','大雨':'大雨','暴雨':'嵐','大暴雨':'大嵐','特大暴雨':'極大雨',
                    '阵雨':'にわか雨','毛毛雨':'霧雨','雷阵雨':'雷雨','雷暴':'雷嵐','雨夹雪':'みぞれ','冻雨':'着氷性の雨',
                    '小雪':'小雪','中雪':'中雪','大雪':'大雪','暴雪':'吹雪','阵雪':'にわか雪',
                    '雾':'霧','轻雾':'もや','浓雾':'濃霧','霾':'霞','沙尘暴':'砂嵐','扬沙':'砂塵','浮尘':'砂塵',
                    '冰雹':'雹','龙卷风':'竜巻','台风':'台風','飓风':'ハリケーン','霰':'霰','米雪':'細雪','冰粒':'氷粒','冰针':'氷針',
                    '沙尘':'砂塵','烟幕':'煙幕','火山灰':'火山灰','尘卷风':'塵旋風','热带风暴':'熱帯暴風',
                    '强台风':'強い台風','超强台风':'超強い台風','高温':'高温','酷热':'酷暑','严寒':'厳寒','闷热':'蒸し暑い',
                    '干热':'乾熱','湿冷':'湿寒','干冷':'乾寒','凉爽':'涼しい',
                    '晴朗无云':'快晴','晴间多云':'晴れ時々曇り','局部多云':'所により曇り','阳光明媚':'快晴',
                    '卷云':'巻雲','卷积云':'巻積雲','层云':'層雲','层积云':'層積雲','积云':'積雲','淡积云':'淡積雲','碎积云':'片積雲','积雨云':'積乱雲','雨层云':'乱層雲',
                    '彩虹':'虹','霓':'副虹','极光':'オーロラ','日晕':'日暈','月晕':'月暈','华':'光冠','宝光':'ブロッケン現象','海市蜃楼':'蜃気楼','朝霞':'朝焼け','晚霞':'夕焼け','蓝调时刻':'ブルーアワー',
                    '龙卷':'竜巻','尘卷':'塵旋風','热带低压':'熱帯低気圧','温带气旋':'温帯低気圧','下击暴流':'ダウンバースト','飑线':'スコールライン'
                },
                wind_scale: {'微风':'微風','和风':'和風','清风':'清風','强风':'強風','疾风':'疾風','大风':'大風','烈风':'烈風','狂风':'狂風','暴风':'暴風','飓风':'ハリケーン'}
            },
            ko: {
                forecast: '5일 예보', sunrise_sunset: '일출·일몰', detail: '상세', env: '환경', life: '생활지수',
                hi: '최고', lo: '최저', hum: '습도', wind: '풍력', feel: '체감', humidity: '습도',
                pressure: '기압', visibility: '시야', wind_speed: '풍속', clouds: '구름',
                no_alarm: '경보 없음', locate: '갱신', loading: '로딩...', sun_rise: '일출', sun_set: '일몰', pos: '위치 찾는 중...',
                need_location: '날씨를 가져오려면 위치 권한이 필요합니다. 허용 후 새로고침하세요.',
                allow_location: '브라우저 설정에서 위치를 허용하고 새로고침하세요.',
                locating: '위치를 허용해 주세요...',
                no_geo: '위치를 지원하는 브라우저를 사용하세요.',
                dressing: '옷차림', carwash: '세차', sport: '운동', comfort: '쾌적', cold: '감기', uv: '자외선', travel: '여행', fishing: '낚시', morning: '조깅',
                hot: '더움', warm: '따뜻', comf: '쾌적', cool: '서늘', cold2: '추움', freezing: '매우 추움',
                not_suit: '부적합', suit: '적합', not_very: '부적', good: '보통', easy: '주의', rare: '낮음', none: '없음', strong: '강함', mid: '중간', weak: '약함',
                desc_dress_hot: '반소매', desc_dress_warm: '반소매', desc_dress_comf: '긴소매', desc_dress_cool: '스웨터', desc_dress_cold: '코트', desc_dress_freezing: '패딩',
                desc_wash_no: '맑은 날', desc_wash_yes: '세차하기',
                desc_sport_no: '바람', desc_sport_cold: '추움', desc_sport_hot: '더움', desc_sport_yes: '운동하기',
                desc_uv_no: '야간', desc_uv_yes: '선크림', desc_uv_mid: '불필요',
                desc_travel_no: '우산', desc_travel_yes: '여행하기',
                desc_fishing_wind: '바람', desc_fishing_yes: '낚시하기',
                desc_morning_no: '바람', desc_morning_yes: '조깅하기',
                weather_stable: '날씨 양호', rain_soon: '시간 후 비', wind_strong: '강풍', dry_air: '건조',
                tip_cold1: '매우 추움', tip_cold2: '장갑', tip_cool: '가벼운 외투', tip_comf: '쾌적', tip_warm: '자외선', tip_hot: '열사병 주의',
                tip_dry: '보습', tip_wet: '습함', tip_windy: '바람', tip_rain: '우산', tip_snow: '미끄럼',
                weather_terms: {
                    '晴':'맑음','少云':'구름 적음','晴，少云':'맑음, 구름 적음','多云':'구름 많음','阴':'흐림','阴，多云':'흐림, 구름 많음',
                    '小雨':'약한 비','中雨':'보통 비','大雨':'큰 비','暴雨':'폭풍','大暴雨':'큰 폭풍','特大暴雨':'극한 폭우',
                    '阵雨':'소나기','毛毛雨':'이슬비','雷阵雨':'뇌우','雷暴':'뇌우','雨夹雪':'진눈깨비','冻雨':'얼음비',
                    '小雪':'약한 눈','中雪':'보통 눈','大雪':'큰 눈','暴雪':'눈폭풍','阵雪':'소낙눈',
                    '雾':'안개','轻雾':'옅은 안개','浓雾':'짙은 안개','霾':'연무','沙尘暴':'모래폭풍','扬沙':'먼지','浮尘':'먼지',
                    '冰雹':'우박','龙卷风':'토네이도','台风':'태풍','飓风':'허리케인','霰':'싸락눈','米雪':'가루눈','冰粒':'얼음알갱이','冰针':'얼음바늘',
                    '沙尘':'모래먼지','烟幕':'연막','火山灰':'화산재','尘卷风':'먼지회오리','热带风暴':'열대폭풍',
                    '强台风':'강한 태풍','超强台风':'초강력 태풍','高温':'고온','酷热':'혹서','严寒':'혹한','闷热':'무더위',
                    '干热':'건열','湿冷':'습냉','干冷':'건냉','凉爽':'서늘',
                    '晴朗无云':'청명','晴间多云':'구름 조금','局部多云':'부분적 구름','阳光明媚':'화창',
                    '卷云':'권운','卷积云':'권적운','层云':'층운','层积云':'층적운','积云':'적운','淡积云':'담적운','碎积云':'편적운','积雨云':'적란운','雨层云':'난층운',
                    '彩虹':'무지개','霓':'부무지개','极光':'오로라','日晕':'무리','月晕':'달무리','华':'코로나','宝光':'영광','海市蜃楼':'신기루','朝霞':'아침놀','晚霞':'저녁놀','蓝调时刻':'블루아워',
                    '龙卷':'토네이도','尘卷':'먼지회오리','热带低压':'열대저기압','温带气旋':'온대저기압','下击暴流':'하강기류','飑线':'스콜라인'
                },
                wind_scale: {'微风':'미풍','和风':'온화한 바람','清风':'맑은 바람','强风':'강한 바람','疾风':'질풍','大风':'대풍','烈风':'열풍','狂风':'광풍','暴风':'폭풍','飓风':'허리케인'}
            },
            ru: {
                forecast: '5 дней', sunrise_sunset: 'Рассвет и закат', detail: 'Подробно', env: 'Среда', life: 'Индекс',
                hi: 'Макс', lo: 'Мин', hum: 'Влажн', wind: 'Ветер', feel: 'Ощущ', humidity: 'Влажн',
                pressure: 'Давл', visibility: 'Видимость', wind_speed: 'Скорость', clouds: 'Облака',
                no_alarm: 'Нет тревог', locate: 'Обновить', loading: 'Загрузка...', sun_rise: 'Рассвет', sun_set: 'Закат', pos: 'Поиск...',
                need_location: 'Нам нужно разрешение на геолокацию для получения погоды. Разрешите и обновите.',
                allow_location: 'Включите геолокацию в настройках браузера и обновите.',
                locating: 'Разрешите геолокацию...',
                no_geo: 'Используйте браузер с поддержкой геолокации.',
                dressing: 'Одежда', carwash: 'Мойка', sport: 'Спорт', comfort: 'Комфорт', cold: 'Простуда', uv: 'УФ', travel: 'Поездки', fishing: 'Рыбалка', morning: 'Зарядка',
                hot: 'Жарко', warm: 'Тепло', comf: 'Комфорт', cool: 'Прохладно', cold2: 'Холодно', freezing: 'Мороз',
                not_suit: 'Нет', suit: 'Да', not_very: 'Не оч', good: 'Норм', easy: 'Риск', rare: 'Редко', none: 'Нет', strong: 'Сильн', mid: 'Средн', weak: 'Слабо',
                desc_dress_hot: 'Шорты', desc_dress_warm: 'Футболка', desc_dress_comf: 'Рубашка', desc_dress_cool: 'Свитер', desc_dress_cold: 'Пальто', desc_dress_freezing: 'Пуховик',
                desc_wash_no: 'Ждать', desc_wash_yes: 'Можно',
                desc_sport_no: 'Ветер', desc_sport_cold: 'Холод', desc_sport_hot: 'Жарко', desc_sport_yes: 'Отлично',
                desc_uv_no: 'Ночью', desc_uv_yes: 'Крем', desc_uv_mid: 'Без защиты',
                desc_travel_no: 'Зонт', desc_travel_yes: 'Хорошо',
                desc_fishing_wind: 'Ветер', desc_fishing_yes: 'Хорошо',
                desc_morning_no: 'Ветер', desc_morning_yes: 'Хорошо',
                weather_stable: 'Погода ок', rain_soon: 'ч дождь', wind_strong: 'Ветер', dry_air: 'Сухо',
                tip_cold1: 'Мороз', tip_cold2: 'Перчатки', tip_cool: 'Куртка', tip_comf: 'Прогулка', tip_warm: 'Крем', tip_hot: 'Жарко',
                tip_dry: 'Сухо', tip_wet: 'Влажно', tip_windy: 'Ветер', tip_rain: 'Зонт', tip_snow: 'Скользко',
                weather_terms: {
                    '晴':'Ясно','少云':'Малооблачно','晴，少云':'Ясно, малооблачно','多云':'Облачно','阴':'Пасмурно','阴，多云':'Пасмурно, облачно',
                    '小雨':'Легкий дождь','中雨':'Умеренный дождь','大雨':'Сильный дождь','暴雨':'Ливень','大暴雨':'Сильный ливень','特大暴雨':'Экстремальный ливень',
                    '阵雨':'Кратковременный дождь','毛毛雨':'Морось','雷阵雨':'Гроза с дождем','雷暴':'Гроза','雨夹雪':'Мокрый снег','冻雨':'Ледяной дождь',
                    '小雪':'Легкий снег','中雪':'Умеренный снег','大雪':'Сильный снег','暴雪':'Метель','阵雪':'Снегопад',
                    '雾':'Туман','轻雾':'Легкий туман','浓雾':'Густой туман','霾':'Мгла','沙尘暴':'Пыльная буря','扬沙':'Пыль','浮尘':'Пыль',
                    '冰雹':'Град','龙卷风':'Торнадо','台风':'Тайфун','飓风':'Ураган','霰':'Крупа','米雪':'Снежные зерна','冰粒':'Ледяные зерна','冰针':'Ледяные иглы',
                    '沙尘':'Пыль/песок','烟幕':'Дым','火山灰':'Вулканический пепел','尘卷风':'Пыльный вихрь','热带风暴':'Тропический шторм',
                    '强台风':'Сильный тайфун','超强台风':'Супер тайфун','高温':'Жара','酷热':'Сильная жара','严寒':'Сильный холод','闷热':'Душно',
                    '干热':'Сухая жара','湿冷':'Влажный холод','干冷':'Сухой холод','凉爽':'Прохладно',
                    '晴朗无云':'Безоблачно','晴间多云':'Переменная облачность','局部多云':'Местами облачно','阳光明媚':'Солнечно',
                    '卷云':'Перистые','卷积云':'Перисто-кучевые','层云':'Слоистые','层积云':'Слоисто-кучевые','积云':'Кучевые','淡积云':'Кучевые плоские','碎积云':'Разорванно-кучевые','积雨云':'Кучево-дождевые','雨层云':'Слоисто-дождевые',
                    '彩虹':'Радуга','霓':'Вторичная радуга','极光':'Аврора','日晕':'Гало','月晕':'Лунное гало','华':'Корона','宝光':'Глория','海市蜃楼':'Мираж','朝霞':'Утренняя заря','晚霞':'Вечерняя заря','蓝调时刻':'Синий час',
                    '龙卷':'Торнадо','尘卷':'Пыльный вихрь','热带低压':'Тропическая депрессия','温带气旋':'Внетропический циклон','下击暴流':'Нисходящий порыв','飑线':'Линия шквала'
                },
                wind_scale: {'微风':'Легкий бриз','和风':'Легкий ветер','清风':'Свежий ветер','强风':'Сильный ветер','疾风':'Штормовой ветер','大风':'Очень сильный ветер','烈风':'Жестокий ветер','狂风':'Ураганный ветер','暴风':'Шторм','飓风':'Ураган'}
            }
        };

        var currentUnit = 'c', currentLang = 'zh', i18n = I18N[currentLang];
        var currentLat = '', currentLon = '';
        var sunArcTimer = null, currentSunData = null;
        var isLoading = false;

        function showLoading() {
            isLoading = true;
            var indicator = document.getElementById('loading-indicator');
            var temp = document.getElementById('temp');
            var desc = document.getElementById('desc');
            if (indicator) indicator.classList.add('show');
            if (temp) { temp.style.opacity = '0.4'; }
            if (desc) { desc.style.opacity = '0.4'; desc.textContent = i18n.loading; }
        }

        function hideLoading() {
            isLoading = false;
            var indicator = document.getElementById('loading-indicator');
            var temp = document.getElementById('temp');
            var desc = document.getElementById('desc');
            if (indicator) indicator.classList.remove('show');
            if (temp) { temp.style.opacity = '1'; }
            if (desc) { desc.style.opacity = '0.8'; }
        }

        function setUnit(unit) {
            currentUnit = unit;
            localStorage.setItem('weatherUnit', unit);
            document.getElementById('btn-c').classList.toggle('active', unit==='c');
            document.getElementById('btn-f').classList.toggle('active', unit==='f');
            if (currentLat && currentLon) fetchWeather();
        }
        function setLang(lang) {
            currentLang = lang;
            i18n = I18N[lang];
            localStorage.setItem('weatherLang', lang);
            document.querySelectorAll('.settings-chip').forEach(function(b){b.classList.remove('active');});
            document.getElementById('btn-'+lang).classList.add('active');
            updateUILanguage();
            if (currentLat && currentLon) fetchWeather();
        }
        function updateUILanguage() {
            document.getElementById('lbl-forecast').textContent=i18n.forecast;
            document.getElementById('lbl-sun').textContent=i18n.sunrise_sunset;
            document.getElementById('lbl-detail').textContent=i18n.detail;
            document.getElementById('lbl-env').textContent=i18n.env;
            document.getElementById('lbl-life').textContent=i18n.life;
            document.getElementById('lbl-hi').textContent=i18n.hi;
            document.getElementById('lbl-lo').textContent=i18n.lo;
            document.getElementById('lbl-hum').textContent=i18n.hum;
            document.getElementById('lbl-wind').textContent=i18n.wind;
            document.getElementById('alarm-strip').textContent='✅ '+i18n.no_alarm;
            document.getElementById('locate-btn').textContent='📍 '+i18n.locate;
            if (!currentLat || !currentLon) {
                document.getElementById('loc').textContent=i18n.locating;
                document.getElementById('desc').textContent=i18n.need_location;
            }
        }
        function toggleSettings() {
            var p=document.getElementById('settings-panel');
            var b=document.getElementById('settings-btn');
            p.classList.toggle('show');
            b.classList.toggle('open');
        }
        function convertTemp(c) { return currentUnit==='f' ? c*9/5+32 : c; }
        function tempUnit() { return currentUnit==='f' ? '°F' : '°C'; }

        var WeatherEngine = {
            classify: function(w) {
                w = (w||'').toLowerCase();
                var r = { family: 'clear', bgClass: 'bg-clear', precipType: null, precipIntensity: 0, effects: [], isNight: false };
                var h = new Date().getHours(); r.isNight = (h < 6 || h >= 18);
                if (w.indexOf('tornado')>-1||w.indexOf('龙卷')>-1) { r.bgClass='bg-thunderstorm'; r.effects.push('wind-extreme'); if(!r.precipType){r.precipType='rain';r.precipIntensity=8;} }
                else if (w.indexOf('hurricane')>-1||w.indexOf('台风')>-1||w.indexOf('飓风')>-1) { r.bgClass='bg-rain-storm'; r.effects.push('wind-extreme'); r.precipType='rain'; r.precipIntensity=9; }
                else if (w.indexOf('hail')>-1||w.indexOf('冰雹')>-1) { r.precipType='hail'; r.precipIntensity=6; if(w.indexOf('thunder')>-1||w.indexOf('雷')>-1){r.effects.push('lightning');r.bgClass='bg-thunderstorm';} }
                else if (w.indexOf('blizzard')>-1||w.indexOf('暴雪')>-1) { r.precipType='snow'; r.precipIntensity=8; }
                else if (w.indexOf('heavy snow')>-1||w.indexOf('大雪')>-1) { r.precipType='snow'; r.precipIntensity=6; }
                else if (w.indexOf('snow')>-1||w.indexOf('雪')>-1) { r.precipType='snow'; r.precipIntensity=4; }
                else if (w.indexOf('light snow')>-1||w.indexOf('小雪')>-1) { r.precipType='snow'; r.precipIntensity=2; }
                else if (w.indexOf('sleet')>-1||w.indexOf('雨夹雪')>-1) { r.precipType='sleet'; r.precipIntensity=3; }
                else if (w.indexOf('freezing')>-1||w.indexOf('冻雨')>-1) { r.precipType='freezing-rain'; r.precipIntensity=3; }
                else if (w.indexOf('extreme rain')>-1||w.indexOf('特大暴雨')>-1) { r.precipType='rain'; r.precipIntensity=10; }
                else if (w.indexOf('heavy storm')>-1||w.indexOf('大暴雨')>-1) { r.precipType='rain'; r.precipIntensity=9; }
                else if (w.indexOf('storm')>-1||w.indexOf('暴雨')>-1) { r.precipType='rain'; r.precipIntensity=8; }
                else if (w.indexOf('heavy rain')>-1||w.indexOf('大雨')>-1) { r.precipType='rain'; r.precipIntensity=6; }
                else if (w.indexOf('moderate rain')>-1||w.indexOf('中雨')>-1) { r.precipType='rain'; r.precipIntensity=4; }
                else if (w.indexOf('light rain')>-1||w.indexOf('小雨')>-1) { r.precipType='rain'; r.precipIntensity=2; }
                else if (w.indexOf('drizzle')>-1||w.indexOf('毛毛雨')>-1) { r.precipType='drizzle'; r.precipIntensity=1; }
                else if (w.indexOf('shower')>-1||w.indexOf('阵雨')>-1) { r.precipType='rain'; r.precipIntensity=3; }
                else if (w.indexOf('thunder')>-1||w.indexOf('雷')>-1) { r.effects.push('lightning'); r.bgClass='bg-thunderstorm'; if(!r.precipType){r.precipType='rain';r.precipIntensity=Math.max(r.precipIntensity,5);} }
                else if (w.indexOf('fog')>-1||w.indexOf('霧')>-1||w.indexOf('雾')>-1) { r.effects.push('fog'); r.bgClass='bg-fog'; }
                else if (w.indexOf('mist')>-1||w.indexOf('轻雾')>-1) { r.effects.push('fog'); r.bgClass='bg-fog'; }
                else if (w.indexOf('haze')>-1||w.indexOf('霾')>-1) { r.effects.push('haze'); r.bgClass='bg-haze'; }
                else if (w.indexOf('sandstorm')>-1||w.indexOf('沙尘暴')>-1) { r.effects.push('sandstorm'); r.bgClass='bg-sandstorm'; }
                else if (w.indexOf('dust')>-1||w.indexOf('扬沙')>-1||w.indexOf('浮尘')>-1) { r.effects.push('dust'); r.bgClass='bg-dust'; }
                else if (w.indexOf('overcast')>-1||w.indexOf('阴')>-1) { r.bgClass='bg-overcast'; r.family='overcast'; }
                else if (w.indexOf('cloud')>-1||w.indexOf('多云')>-1) { r.bgClass='bg-cloudy'; r.family='cloudy'; }
                else if (w.indexOf('clear')>-1||w.indexOf('晴')>-1||w.indexOf('少云')>-1) { r.bgClass='bg-clear'; r.family='clear'; }
                if(r.precipType==='rain'||r.precipType==='drizzle'||r.precipType==='freezing-rain'){if(r.precipIntensity>=8)r.bgClass='bg-rain-storm';else if(r.precipIntensity>=5)r.bgClass='bg-rain-heavy';else r.bgClass='bg-rain-light';}
                if(r.precipType==='snow'||r.precipType==='graupel'){if(r.precipIntensity>=7)r.bgClass='bg-snow-storm';else if(r.precipIntensity>=4)r.bgClass='bg-snow-heavy';else r.bgClass='bg-snow-light';}
                if(r.effects.indexOf('lightning')>-1)r.bgClass='bg-thunderstorm';
                if(r.isNight){if(r.bgClass.indexOf('rain')>-1)r.bgClass='bg-night-rain';else if(r.bgClass.indexOf('snow')>-1)r.bgClass='bg-night-snow';else if(r.bgClass.indexOf('cloudy')>-1||r.bgClass.indexOf('overcast')>-1)r.bgClass='bg-night-cloudy';else r.bgClass='bg-night-clear';}
                return r;
            }
        };

        function renderSky(wi) {
            var body=document.body;body.className='';body.style.background=wi.isNight?'#0a0a2e':'#3b8ad9';
            if(wi.isNight)body.classList.add('is-night');if(wi.bgClass.indexOf('snow')>-1||wi.bgClass.indexOf('fog')>-1||wi.bgClass.indexOf('haze')>-1)body.classList.add('is-light-bg');
            document.getElementById('layer-base').className='sky-layer layer-base '+wi.bgClass;
            var starsEl=document.getElementById('stars-layer');starsEl.innerHTML='';if(wi.isNight)for(var i=0;i<60;i++){var s=document.createElement('div');s.className='star-dot';s.style.cssText='left:'+Math.random()*100+'%;top:'+Math.random()*50+'%;width:'+(1+Math.random()*2.5)+'px;height:'+s.style.width+';animation-delay:'+Math.random()*4+'s;';starsEl.appendChild(s);}
            var cloudsEl=document.getElementById('clouds-layer');cloudsEl.innerHTML='';var cc=wi.precipType?6:(wi.family==='overcast')?7:(wi.family==='cloudy')?5:2;
            for(var j=0;j<cc;j++){var group=document.createElement('div');group.className='cloud-group';var scale=0.4+Math.random()*1.6,w=Math.round(120*scale);group.style.cssText='top:'+(2+Math.random()*55)+'%;width:'+w+'px;height:'+Math.round(60*scale)+'px;animation-duration:'+(20+Math.random()*40)+'s;animation-delay:'+(-Math.random()*25)+'s;opacity:'+(0.35+Math.random()*0.45)+';';var bd=document.createElement('div');bd.className='cloud-body';bd.style.cssText='width:100%;height:55%;';group.appendChild(bd);[{w:w*0.55,l:-w*0.22,t:-w*0.15},{w:w*0.65,r:-w*0.28,t:-w*0.2},{w:w*0.5,l:w*0.2,t:-w*0.08}].forEach(function(p){var b=document.createElement('div');b.className='cloud-bubble';b.style.cssText='width:'+p.w+'px;height:'+p.w+'px;'+(p.l!==undefined?'left:'+p.l+'px;':'')+(p.r!==undefined?'right:'+p.r+'px;':'')+'top:'+p.t+'px;';group.appendChild(b);});cloudsEl.appendChild(group);}
            var precipEl=document.getElementById('layer-precip');precipEl.innerHTML='';precipEl.style.display=wi.precipType?'block':'none';
            if(wi.precipType){var intensity=wi.precipIntensity||1,count=Math.round(intensity*10),type=wi.precipType;for(var k=0;k<count;k++){var particle=document.createElement('div'),speed=0,h=0;switch(type){case'rain':particle.className='raindrop';h=12+intensity*4;speed=0.35+(10-intensity)*0.1;break;case'drizzle':particle.className='drizzle';h=6;speed=0.7;break;case'snow':particle.className='snowflake';h=4;speed=4+Math.random()*4;break;case'hail':particle.className='hailstone';h=8;speed=0.35;break;case'sleet':particle.className=Math.random()>0.5?'raindrop':'snowflake';h=Math.random()>0.5?14:4;speed=Math.random()>0.5?0.5:4;break;default:particle.className='raindrop';h=14;speed=0.5;}particle.style.cssText='left:'+Math.random()*100+'%;height:'+h+'px;animation-duration:'+speed+'s;animation-delay:'+(-Math.random()*speed)+'s;';if(type==='snow')particle.style.width=particle.style.height;precipEl.appendChild(particle);}}
            var effectsEl=document.getElementById('layer-effects');effectsEl.innerHTML='';effectsEl.style.display=wi.effects.length>0?'block':'none';
            if(wi.effects.indexOf('lightning')>-1){var flash=document.createElement('div');flash.className='lightning-flash';flash.id='lightning-flash';effectsEl.appendChild(flash);function randomLightning(){var el=document.getElementById('lightning-flash');if(!el)return;el.style.opacity='1';setTimeout(function(){if(el)el.style.opacity='0';},80+Math.random()*200);setTimeout(randomLightning,2000+Math.random()*8000);}setTimeout(randomLightning,1000+Math.random()*3000);}
            if(wi.effects.indexOf('fog')>-1){var fg=document.createElement('div');fg.className='fog-overlay';effectsEl.appendChild(fg);}
            if(wi.effects.indexOf('dust')>-1||wi.effects.indexOf('sandstorm')>-1){var du=document.createElement('div');du.className='dust-overlay';effectsEl.appendChild(du);}
            var windEl=document.getElementById('wind-layer');windEl.innerHTML='';windEl.style.display=wi.effects.indexOf('wind-extreme')>-1?'block':'none';if(windEl.style.display==='block')for(var ww=0;ww<20;ww++){var wl=document.createElement('div');wl.className='wind-line';wl.style.cssText='top:'+Math.random()*100+'%;width:'+(60+Math.random()*150)+'px;animation-duration:'+(0.5+Math.random()*1.5)+'s;';windEl.appendChild(wl);}
        }

        function getWeatherEmoji(c) { c=(c||'').toLowerCase(); if(c.indexOf('thunder')>-1||c.indexOf('雷')>-1)return'⛈️'; if(c.indexOf('snow')>-1||c.indexOf('雪')>-1)return'❄️'; if(c.indexOf('rain')>-1||c.indexOf('雨')>-1||c.indexOf('shower')>-1||c.indexOf('drizzle')>-1)return'🌧️'; if(c.indexOf('overcast')>-1||c.indexOf('阴')>-1)return'☁️'; if(c.indexOf('cloud')>-1||c.indexOf('多云')>-1)return'⛅'; if(c.indexOf('clear')>-1||c.indexOf('晴')>-1||c.indexOf('少云')>-1)return'☀️'; if(c.indexOf('fog')>-1||c.indexOf('雾')>-1||c.indexOf('mist')>-1)return'🌫️'; return'🌤️'; }

        function renderSunArc(sunriseUnix, sunsetUnix) {
            var sunriseDate = new Date(sunriseUnix * 1000);
            var sunsetDate = new Date(sunsetUnix * 1000);
            var sunrise = String(sunriseDate.getHours()).padStart(2,'0') + ':' + String(sunriseDate.getMinutes()).padStart(2,'0');
            var sunset = String(sunsetDate.getHours()).padStart(2,'0') + ':' + String(sunsetDate.getMinutes()).padStart(2,'0');
            var now = new Date(), nowMin = now.getHours()*60 + now.getMinutes();
            var srMin = sunriseDate.getHours()*60 + sunriseDate.getMinutes();
            var ssMin = sunsetDate.getHours()*60 + sunsetDate.getMinutes();
            var container = document.getElementById('sun-arc');
            if (!container) return;

            var w = 320, h = 80, padL = 40, padR = 40;
            var horizonY = 42, bottomY = horizonY + 50;
            var arcW = w - padL - padR;
            var srPct = srMin / 1440, noonPct = 0.5, ssPct = ssMin / 1440;
            var startX = padL, srX = padL + arcW * srPct, ssX = padL + arcW * ssPct, endX = w - padR;
            var noonX = padL + arcW * noonPct, noonY = horizonY - 80;

            var pct = nowMin / 1440;
            var sunX, sunY;

            if (pct <= srPct) {
                var t = pct / srPct;
                sunX = startX + t * (srX - startX);
                sunY = bottomY + t * (horizonY - bottomY);
            } else if (pct <= ssPct) {
                var t = (pct - srPct) / (ssPct - srPct);
                var mt = 1 - t;
                sunX = mt * mt * srX + 2 * mt * t * noonX + t * t * ssX;
                sunY = mt * mt * horizonY + 2 * mt * t * noonY + t * t * horizonY;
            } else {
                var t = (pct - ssPct) / (1 - ssPct);
                sunX = ssX + t * (endX - ssX);
                sunY = horizonY + t * (bottomY - horizonY);
            }

            var isDaytime = (nowMin >= srMin && nowMin < ssMin);
            var sunOpacity = isDaytime ? 0.9 : 0.35;
            var sunColor = isDaytime ? '#ffcc00' : '#8ab4f8';
            var isNight = document.body.classList.contains('is-night');
            var isLightBg = document.body.classList.contains('is-light-bg');
            var labelColor = isNight ? 'rgba(255,255,255,0.5)' : (isLightBg ? '#1a1a2e' : '#1a1a2e');

            var svg = '<svg viewBox="0 0 ' + w + ' ' + h + '" style="width:100%;height:100%;">';
            svg += '<line x1="' + startX + '" y1="' + horizonY + '" x2="' + endX + '" y2="' + horizonY + '" stroke="rgba(255,255,255,0.3)" stroke-width="1" stroke-dasharray="6,4"/>';
            svg += '<line x1="' + startX + '" y1="' + bottomY + '" x2="' + srX + '" y2="' + horizonY + '" stroke="rgba(255,255,255,0.3)" stroke-width="1.5" stroke-dasharray="5,4"/>';
            svg += '<path d="M' + srX + ',' + horizonY + ' Q' + noonX + ',' + noonY + ' ' + ssX + ',' + horizonY + '" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>';
            svg += '<line x1="' + ssX + '" y1="' + horizonY + '" x2="' + endX + '" y2="' + bottomY + '" stroke="rgba(255,255,255,0.3)" stroke-width="1.5" stroke-dasharray="5,4"/>';
            svg += '<circle cx="' + sunX + '" cy="' + sunY + '" r="7" fill="' + sunColor + '" opacity="' + sunOpacity + '"/>';
            svg += '<text x="' + srX + '" y="' + (h-2) + '" text-anchor="middle" font-size="10" fill="' + labelColor + '">' + i18n.sun_rise + ' ' + sunrise + '</text>';
            svg += '<text x="' + ssX + '" y="' + (h-2) + '" text-anchor="middle" font-size="10" fill="' + labelColor + '">' + i18n.sun_set + ' ' + sunset + '</text>';
            svg += '</svg>';
            container.innerHTML = svg;
        }

        function calculateLifeIndices(temp,humidity,weather,windSpeed){var hour=new Date().getHours(),isDay=(hour>=6&&hour<18);return[{icon:'👕',name:i18n.dressing,val:temp>=28?i18n.hot:temp>=24?i18n.warm:temp>=18?i18n.comf:temp>=10?i18n.cool:temp>=0?i18n.cold2:i18n.freezing,desc:temp>=28?i18n.desc_dress_hot:temp>=24?i18n.desc_dress_warm:temp>=18?i18n.desc_dress_comf:temp>=10?i18n.desc_dress_cool:temp>=0?i18n.desc_dress_cold:i18n.desc_dress_freezing},{icon:'🚗',name:i18n.carwash,val:(weather.indexOf('雨')>-1||weather.indexOf('rain')>-1||weather.indexOf('雪')>-1||weather.indexOf('snow')>-1)?i18n.not_suit:i18n.suit,desc:(weather.indexOf('雨')>-1||weather.indexOf('rain')>-1||weather.indexOf('雪')>-1||weather.indexOf('snow')>-1)?i18n.desc_wash_no:i18n.desc_wash_yes},{icon:'🏃',name:i18n.sport,val:(weather.indexOf('雨')>-1||weather.indexOf('rain')>-1||weather.indexOf('雪')>-1||weather.indexOf('snow')>-1||windSpeed>8)?i18n.not_suit:(temp<5||temp>30)?i18n.not_very:i18n.suit,desc:windSpeed>8?i18n.desc_sport_no:(temp<5?i18n.desc_sport_cold:temp>30?i18n.desc_sport_hot:i18n.desc_sport_yes)},{icon:'😊',name:i18n.comfort,val:Math.abs(temp-22)<4?i18n.comf:Math.abs(temp-22)<8?i18n.comf:i18n.good,desc:i18n.feel+temp.toFixed(0)+tempUnit()+'，'+i18n.humidity+humidity+'%'},{icon:'😷',name:i18n.cold,val:(temp<5&&humidity>70)?i18n.easy:i18n.rare,desc:temp<5?i18n.desc_sport_cold:'OK'},{icon:'☀️',name:i18n.uv,val:!isDay?i18n.none:weather.indexOf('晴')>-1||weather.indexOf('clear')>-1?i18n.strong:weather.indexOf('多云')>-1||weather.indexOf('cloud')>-1?i18n.mid:i18n.weak,desc:!isDay?i18n.desc_uv_no:weather.indexOf('晴')>-1||weather.indexOf('clear')>-1?i18n.desc_uv_yes:i18n.desc_uv_mid},{icon:'✈️',name:i18n.travel,val:(weather.indexOf('雨')>-1||weather.indexOf('rain')>-1||weather.indexOf('雪')>-1||weather.indexOf('snow')>-1)?i18n.not_suit:i18n.suit,desc:(weather.indexOf('雨')>-1||weather.indexOf('rain')>-1||weather.indexOf('雪')>-1||weather.indexOf('snow')>-1)?i18n.desc_travel_no:i18n.desc_travel_yes},{icon:'🎣',name:i18n.fishing,val:(weather.indexOf('晴')>-1&&temp>=15&&temp<=25&&windSpeed<5)?i18n.suit:i18n.good,desc:windSpeed>5?i18n.desc_fishing_wind:i18n.desc_fishing_yes},{icon:'🌅',name:i18n.morning,val:windSpeed<6?i18n.suit:i18n.not_suit,desc:windSpeed>6?i18n.desc_morning_no:i18n.desc_morning_yes}];}
        function getTagClass(val){return(val==='适宜'||val==='舒适'||val==='少发'||val==='弱'||val==='无'||val==='Suitable'||val==='Comfortable'||val==='Unlikely'||val==='Weak'||val==='None'||val==='適する'||val==='快適'||val==='なし'||val==='弱い'||val==='적합'||val==='쾌적'||val==='없음'||val==='약함'||val==='Да'||val==='Комфортно'||val==='Нет'||val==='Yes')?'tag-ok':(val==='不适宜'||val==='不舒适'||val==='易发'||val==='强'||val==='寒冷'||val==='Not Suitable'||val==='Prone'||val==='Strong'||val==='Freezing'||val==='不適'||val==='強い'||val==='부적합'||val==='강함'||val==='Нет'||val==='Сильн'||val==='Мороз'||val==='No')?'tag-bad':'tag-mid';}
        function showError(msg){var t=document.getElementById('error-toast');t.textContent=msg;t.style.display='block';setTimeout(function(){t.style.display='none';},4000);}

        function render(apiData){
            hideLoading();
            if(!apiData||!apiData.success||!apiData.data||!apiData.data.data){showError('数据异常');return;}
            var d=apiData.data,dataArr=d.data;if(!dataArr||dataArr.length===0){showError('无天气数据');return;}
            var current=dataArr[0];var tempC=(parseFloat(current.temp)||293)-273.15;var tempD=convertTemp(tempC);
            var humidity=parseFloat(current.humidity)||50;var windSpeed=parseFloat(current.speed)||2;
            var weatherText=current.weather||'晴';
            var weatherDisplay = translateWeather(weatherText);
            var pressure=parseFloat(current.pressure)||1013;var visibility=parseFloat(current.visibility)||10000;
            var flag=countryFlag(d.country||'');
            document.getElementById('loc').textContent=flag+' '+(d.name||i18n.pos);
            document.getElementById('temp').textContent=tempD.toFixed(0)+tempUnit();
            document.getElementById('desc').textContent=weatherDisplay;
            document.getElementById('feel').textContent=i18n.humidity+' '+humidity+'%';
            document.getElementById('header-tip').textContent=getFriendlyTip(tempC,humidity,weatherText,windSpeed);
            var wi=WeatherEngine.classify(weatherText);renderSky(wi);
            var temps=dataArr.map(function(x){return parseFloat(x.temp)-273.15;});var hiC=Math.max.apply(null,temps),loC=Math.min.apply(null,temps);
            var hiD=convertTemp(hiC),loD=convertTemp(loC);
            document.getElementById('hiV').textContent=hiD.toFixed(0)+tempUnit();document.getElementById('loV').textContent=loD.toFixed(0)+tempUnit();
            var tipsContainer=document.getElementById('smart-tips');tipsContainer.innerHTML='';var nextRain=-1;
            for(var i=0;i<Math.min(8,dataArr.length);i++){var w=dataArr[i].weather||'';if(nextRain===-1&&(w.indexOf('雨')>-1||w.indexOf('rain')>-1||w.indexOf('雪')>-1||w.indexOf('snow')>-1)){nextRain=i*3;}}
            if(nextRain>=0)tipsContainer.innerHTML+='<div class="smart-tip-item">🌧️ '+nextRain+i18n.rain_soon+'</div>';
            if(windSpeed>8)tipsContainer.innerHTML+='<div class="smart-tip-item">💨 '+i18n.wind_strong+'</div>';
            if(humidity<20)tipsContainer.innerHTML+='<div class="smart-tip-item">💧 '+i18n.dry_air+'</div>';
            if(tipsContainer.innerHTML==='')tipsContainer.innerHTML+='<div class="smart-tip-item">☀️ '+i18n.weather_stable+'</div>';
            document.getElementById('bar-hi').style.width=Math.max(0,Math.min(100,((hiC+10)/55)*100))+'%';document.getElementById('bar-hi-val').textContent=hiD.toFixed(0)+tempUnit();
            document.getElementById('bar-lo').style.width=Math.max(0,Math.min(100,((loC+20)/60)*100))+'%';document.getElementById('bar-lo-val').textContent=loD.toFixed(0)+tempUnit();
            document.getElementById('bar-hum').style.width=Math.min(100,humidity)+'%';document.getElementById('bar-hum-val').textContent=humidity+'%';
            var windScaleText = getWindScaleText(windSpeed);
            document.getElementById('bar-wind').style.width=Math.min(100,(windSpeed/12)*100)+'%';document.getElementById('bar-wind-val').textContent=windScaleText;
            if(d.sunrise&&d.sunset){currentSunData={sr:d.sunrise,ss:d.sunset};renderSunArc(d.sunrise,d.sunset);}
            if(sunArcTimer)clearInterval(sunArcTimer);if(currentSunData)sunArcTimer=setInterval(function(){renderSunArc(currentSunData.sr,currentSunData.ss);},30000);
            renderForecast5(dataArr);
            var envItems=[{ic:'💧',lb:i18n.humidity,vl:humidity+'%'},{ic:'📊',lb:i18n.pressure,vl:pressure+'hPa'},{ic:'👁️',lb:i18n.visibility,vl:(visibility/1000).toFixed(1)+'km'},{ic:'💨',lb:i18n.wind_speed,vl:windSpeed.toFixed(1)+'m/s'},{ic:'☁️',lb:i18n.clouds,vl:(current.clouds||0)+'%'},{ic:'🌡️',lb:i18n.feel,vl:tempD.toFixed(0)+tempUnit()}];
            var es=document.getElementById('env-scroll');es.innerHTML='';envItems.forEach(function(ei){es.innerHTML+='<div class="env-chip"><div class="ic">'+ei.ic+'</div><div class="lb">'+ei.lb+'</div><div class="vl">'+ei.vl+'</div></div>';});
            var indices=calculateLifeIndices(tempC,humidity,weatherText,windSpeed);var lg=document.getElementById('life-grid');lg.innerHTML='';
            indices.forEach(function(idx){lg.innerHTML+='<div class="life-tile"><div class="lt-icon">'+idx.icon+'</div><div class="lt-name">'+idx.name+'</div><div class="lt-val '+getTagClass(idx.val)+'">'+idx.val+'</div><div class="lt-desc">'+(idx.desc||'')+'</div></div>';});
            document.getElementById('alarm-strip').textContent='✅ '+i18n.no_alarm;document.getElementById('alarm-strip').className='alarm-strip safe';
        }

        function getFriendlyTip(temp,humidity,weather,windSpeed){var tips=[];if(temp<0)tips.push(i18n.tip_cold1);else if(temp<8)tips.push(i18n.tip_cold2);else if(temp<16)tips.push(i18n.tip_cool);else if(temp<24)tips.push(i18n.tip_comf);else if(temp<30)tips.push(i18n.tip_warm);else tips.push(i18n.tip_hot);if(humidity<20)tips.push(i18n.tip_dry);else if(humidity>85)tips.push(i18n.tip_wet);if(windSpeed>6)tips.push(i18n.tip_windy);if(weather.indexOf('雨')>-1||weather.indexOf('rain')>-1)tips.push(i18n.tip_rain);if(weather.indexOf('雪')>-1||weather.indexOf('snow')>-1)tips.push(i18n.tip_snow);return tips[Math.floor(Math.random()*tips.length)]||'';}

        function renderForecast5(dataArr){var container=document.getElementById('forecast5-row');if(!container)return;container.innerHTML='';var days={};dataArr.forEach(function(d){var date=new Date(d.dt*1000);var dayKey=date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0')+'-'+String(date.getDate()).padStart(2,'0');if(!days[dayKey]){days[dayKey]={highs:[],lows:[],weathers:[],date:date};}var t=parseFloat(d.temp)-273.15;days[dayKey].highs.push(convertTemp(t));days[dayKey].lows.push(convertTemp(t));days[dayKey].weathers.push(d.weather||'');});var dayKeys=Object.keys(days).slice(0,5);dayKeys.forEach(function(key){var dd=days[key];var hi=Math.max.apply(null,dd.highs).toFixed(0),lo=Math.min.apply(null,dd.lows).toFixed(0);var w=dd.weathers[Math.floor(dd.weathers.length/2)]||'晴';var wIcon=getWeatherEmoji(w);var weekdays=(currentLang==='zh')?['周日','周一','周二','周三','周四','周五','周六']:(currentLang==='ja')?['日曜','月曜','火曜','水曜','木曜','金曜','土曜']:(currentLang==='ko')?['일','월','화','수','목','금','토']:(currentLang==='ru')?['Вс','Пн','Вт','Ср','Чт','Пт','Сб']:['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];container.innerHTML+='<div class="f5-cell"><div class="d1">'+weekdays[dd.date.getDay()]+'</div><div class="d2">'+(dd.date.getMonth()+1)+'/'+dd.date.getDate()+'</div><div class="icon">'+wIcon+'</div><div class="hi">'+hi+tempUnit()+'</div><div class="lo">'+lo+tempUnit()+'</div></div>';});}

        function fetchWeather(){
            if (!currentLat || !currentLon) { showError(i18n.need_location); return; }
            showLoading();
            fetch('?ajax=1&lat='+currentLat+'&lon='+currentLon+'&t='+Date.now()).then(function(res){return res.json();}).then(function(data){render(data);}).catch(function(err){hideLoading();showError('网络请求失败');});
        }
        function locateMe(){
            if(!navigator.geolocation){showError(i18n.no_geo);return;}
            showLoading();
            navigator.geolocation.getCurrentPosition(function(pos){currentLat=pos.coords.latitude.toFixed(6);currentLon=pos.coords.longitude.toFixed(6);document.getElementById('loc').textContent=i18n.pos;fetchWeather();},function(){hideLoading();showError(i18n.allow_location);});
        }

        document.addEventListener('DOMContentLoaded',function(){
            var savedUnit=localStorage.getItem('weatherUnit');if(savedUnit){currentUnit=savedUnit;}setUnit(currentUnit);
            var savedLang=localStorage.getItem('weatherLang');if(savedLang&&I18N[savedLang]){currentLang=savedLang;i18n=I18N[currentLang];}
            document.querySelectorAll('.settings-chip').forEach(function(b){b.classList.remove('active');});
            var langBtn=document.getElementById('btn-'+currentLang);if(langBtn)langBtn.classList.add('active');
            updateUILanguage();
            if(navigator.geolocation){showLoading();navigator.geolocation.getCurrentPosition(function(pos){currentLat=pos.coords.latitude.toFixed(6);currentLon=pos.coords.longitude.toFixed(6);fetchWeather();},function(){hideLoading();showError(i18n.allow_location);});}else{showError(i18n.no_geo);}
            setInterval(function(){if(currentLat&&currentLon)fetchWeather();},10*60*1000);
            if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('/sw.js').then(function(r){console.log('SW注册成功:',r.scope);}).catch(function(e){console.log('SW注册失败:',e);});});}
        });
    </script>
</body>
</html>
