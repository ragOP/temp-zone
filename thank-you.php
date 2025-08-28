<?php
/* ===== ОБРАБОТКА ЛОГИРОВАНИЯ КЛИКОВ ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['qs_listings'])) {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);

  if (isset($data['action']) && $data['action'] === 'log_offer_click') {
    // Логируем клик по офферу
    logData([
      'type' => 'offer_click',
      'title' => $data['title'] ?? 'unknown',
      'url' => $data['url'] ?? 'unknown',
      'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
      'user_agent' => $data['user_agent'] ?? 'unknown',
      'referer' => $data['referer'] ?? 'unknown',
      'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ], 'offer_click');

    header('Content-Type: application/json');
    echo json_encode(['status' => 'logged']);
    exit;
  }
}

/* ===== INLINE JSON PROXY (ставить самым первым) ===== */
if (isset($_GET['qs_listings'])) {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
  }
  ini_set('display_errors', '0');
  header('Content-Type: application/json; charset=utf-8');
  if (function_exists('ob_get_length') && ob_get_length()) {
    ob_clean();
  }

  $raw = file_get_contents('php://input');
  $in = json_decode($raw, true);
  if (!is_array($in)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body', 'raw' => $raw]);
    exit;
  }
  $val = fn($k, $d = '') => (isset($in[$k]) ? $in[$k] : $d);

  $zip       = trim($val('zip', ''));
  $insured   = (int)$val('insured', 0);
  $homeowner = (int)$val('homeowner', 0);
  $carrier   = trim($val('carrier', ''));
  $subid3    = trim($val('subid3', ''));
  $env       = ($val('env', 'prod') === 'stage') ? 'stage' : 'prod';

  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
  } else {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  }
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $uri  = $_SERVER['REQUEST_URI'] ?? '/';
  $ref  = $_SERVER['HTTP_REFERER'] ?? ("https://{$host}{$uri}");

  $payload = [
    'tracking' => [
      'ni_ad_client' => '704447',
      'testing' => ($env === 'stage') ? 1 : 0,
      'ni_zc' => (string)$zip,
      'ip' => $ip,
      'ua' => $ua,
      'ni_ref' => $ref
    ],
    'contact' => ['zip' => (string)$zip],
    'household' => ['homeowner' => $homeowner],
    'current_insurance' => ['currently_insured' => $insured, 'carrier' => $carrier],
    'subid3' => $subid3,
  ];

  // Логируем входящий запрос
  logData([
    'type' => 'api_request',
    'method' => $_SERVER['REQUEST_METHOD'],
    'ip' => $ip,
    'user_agent' => $ua,
    'payload' => $payload,
    'endpoint' => $endpoint,
    'subid3_received' => $subid3
  ], 'api_incoming');
  $endpoint = ($env === 'stage')
    ? 'https://nextinsure.quinstage.com/listingdisplay/listings'
    : 'https://www.nextinsure.com/listingdisplay/listings';

  $ch = curl_init($endpoint);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 25,
  ]);
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);

  if ($resp === false) {
    // Логируем ошибку CURL
    logData([
      'type' => 'api_error',
      'error' => 'curl_failed',
      'detail' => $err,
      'payload' => $payload
    ], 'api_error');

    http_response_code(502);
    echo json_encode(['error' => 'Upstream request failed', 'detail' => $err, 'sent' => $payload]);
    exit;
  }
  if ($code < 200 || $code >= 300) {
    // Логируем HTTP ошибку
    logData([
      'type' => 'api_error',
      'error' => 'http_error',
      'status' => $code,
      'response' => $resp,
      'payload' => $payload
    ], 'api_error');

    http_response_code($code);
    echo json_encode(['error' => 'Upstream HTTP error', 'status' => $code, 'body' => $resp, 'sent' => $payload]);
    exit;
  }

  // Логируем успешный ответ
  logData([
    'type' => 'api_success',
    'status' => $code,
    'response_length' => strlen($resp),
    'payload' => $payload
  ], 'api_success');

  echo $resp;
  exit;
}
/* ===== /INLINE JSON PROXY ===== */
?>
<?php
// Функция для логирования данных
function logData($data, $type = 'general')
{
  $logDir = 'log';
  if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
  }

  $date = date('Y-m-d');
  $timestamp = date('Y-m-d H:i:s');
  $logFile = $logDir . '/' . $date . '.log';

  $logEntry = "[{$timestamp}] [{$type}] " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

  file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Функция для получения параметров из URL
function getUrlParameter($name)
{
  return isset($_GET[$name]) ? $_GET[$name] : '';
}

// Получаем ответы пользователя и макросы из URL
$insuredAnswer = getUrlParameter('insured') ?: 'N';
$insuranceCarrier = getUrlParameter('insurance_carrier') ?: '';

// Определяем subid3 на основе ответа о страховщике
if ($insuredAnswer === 'Yes' && $insuranceCarrier) {
  $subid3 = $insuranceCarrier;
} else {
  $subid3 = '';
}
$homeownerAnswer = getUrlParameter('homeowner') ?: 'No';
$subid = getUrlParameter('subid') ?: '';
$fbclid = getUrlParameter('fbclid') ?: '';
$mksite = getUrlParameter('mksite') ?: '709';
$mkcampaign = getUrlParameter('mkcampaign') ?: '309838';
$pixel = getUrlParameter('pixel') ?: '123456789';
$phone = getUrlParameter('phone') ?: '1-855-235-1844';
$utm_campaign = getUrlParameter('utm_campaign') ?: '';
$utm_source = getUrlParameter('utm_source') ?: '';
$utm_placement = getUrlParameter('utm_placement') ?: '';
$campaign_id = getUrlParameter('campaign_id') ?: '';
$adset_id = getUrlParameter('adset_id') ?: '';
$ad_id = getUrlParameter('ad_id') ?: '';
$adset_name = getUrlParameter('adset_name') ?: '';
$offerlink   = getUrlParameter('offerlink') ?: ''; // ✅ Added offerlink here

// Макросы, полученные из URL параметров
$macros = [
  'mksite' => $mksite,
  'mkcampaign' => $mkcampaign,
  'subid' => $subid,
  'subid3' => isset($_GET['subid3']) && $_GET['subid3'] !== '' ? $_GET['subid3'] : '',
  'pixel' => $pixel,
  'phone' => $phone,
  'fbclid' => $fbclid,
  'insured_answer' => $insuredAnswer,
  'insurance_carrier' => $insuranceCarrier,
  'homeowner_answer' => $homeownerAnswer,
  'utm_campaign' => $utm_campaign,
  'utm_source' => $utm_source,
  'utm_placement' => $utm_placement,
  'campaign_id' => $campaign_id,
  'adset_id' => $adset_id,
  'ad_id' => $ad_id,
  'adset_name' => $adset_name,
  'offerlink' => isset($_GET['offerlink']) && $_GET['offerlink'] !== '' ? $_GET['offerlink'] : '' // ✅ Added offerlink here

];

// Логируем данные пользователя
logData([
  'type' => 'user_data',
  'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
  'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
  'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
  'macros' => $macros,
  'url_params' => $_GET
], 'user_visit');
?>
<!DOCTYPE html>

<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Landing page para servicio Pay Per Call relacionado con deudas en España">
  <title>Thank You - Cut Up to 40% on Your Auto Insurance </title>
  <!-- Bootstrap 5 CSS -->
  <link href="assets/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="assets/bootstrap-icons.css" rel="stylesheet">
  <!-- Option 1: Include in HTML -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
  <link href="assets/styles.css" rel="stylesheet">
  <!-- FF Pro Universal Tag -->
  <script>
    // Lumetric tracking - отключен из-за недоступности домена
    window.flux = {
      track: function() {
        // Пустая функция для предотвращения ошибок
        console.log('Lumetric tracking disabled');
      }
    };
  </script>
  <!-- FF Pro View Event -->
  <script>
    if (typeof flux !== 'undefined') {
      flux.track("view")
    }
  </script>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=AW-16674691485"></script>
  <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', 'AW-16674691485');
  </script>

  <meta name="referrer" content="no-referrer-when-downgrade">
  <!-- Facebook Pixel Code -->
  <script>
    ! function(f, b, e, v, n, t, s) {
      if (f.fbq) return;
      n = f.fbq = function() {
        n.callMethod ?
          n.callMethod.apply(n, arguments) : n.queue.push(arguments)
      };
      if (!f._fbq) f._fbq = n;
      n.push = n;
      n.loaded = !0;
      n.version = '2.0';
      n.queue = [];
      t = b.createElement(e);
      t.async = !0;
      t.src = v;
      s = b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t, s)
    }(window, document, 'script',
      'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo $macros['pixel']; ?>');
    fbq('track', 'PageView');
  </script>
  <noscript><img height="1" width="1" style="display:none"
      src="https://www.facebook.com/tr?id=<?php echo $macros['pixel']; ?>&ev=PageView&noscript=1" /></noscript>
  <!-- End Facebook Pixel Code -->
  <script src="assets/script/jquery.min.js"></script>

  <!-- Start of Marketcall Code -->
  <script>
    (function(w, d, s, o, f, js, fjs) {
      w[o] = w[o] || function() {
        (w[o].q = w[o].q || []).push(arguments)
      };
      js = d.createElement(s), fjs = d.getElementsByTagName(s)[0];
      js.id = o;
      js.src = f;
      js.async = 1;
      fjs.parentNode.insertBefore(js, fjs);
    }(window, document, 'script', 'mcc', 'https://marketcall.com/js/mc-calltracking.js'));
    mcc('init', {
      site: <?php echo $macros['mksite']; ?>,
      serviceBaseUrl: '//www.marketcall.com'
    });
    mcc('requestTrackingNumber', {
      campaign: "<?php echo $macros['mkcampaign']; ?>",
      selector: [{
        type: "dom",
        value: "a[href^='tel:']"
      }],
      mask: "(xxx) xxx-xxxx",
      subid: "<?php echo $macros['subid']; ?>",
      subid1: "<?php echo $macros['homeowner_answer']; ?>",
      subid2: "<?php echo $macros['insured_answer']; ?>",
      subid3: "<?php echo $macros['subid3']; ?>"
    });
  </script>
  <!-- End Marketcall Code -->



  <!-- Hotjar Tracking Code for Auto insurance / Facebook / Quiz / v5 -->
  <script>
    (function(h, o, t, j, a, r) {
      h.hj = h.hj || function() {
        (h.hj.q = h.hj.q || []).push(arguments)
      };
      h._hjSettings = {
        hjid: 6444832,
        hjsv: 6
      };
      a = o.getElementsByTagName('head')[0];
      r = o.createElement('script');
      r.async = 1;
      r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv;
      a.appendChild(r);
    })(window, document, 'https://static.hotjar.com/c/hotjar-', '.js?sv=');
  </script>

  <style type="text/css">
    @font-face {
      font-family: 'Atlassian Sans';
      font-style: normal;
      font-weight: 400 653;
      font-display: swap;
      src: local('AtlassianSans'), local('Atlassian Sans Text'), url('chrome-extension://liecbddmkiiihnedobmlmillhodjkdmb/fonts/AtlassianSans-latin.woff2') format('woff2');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }
  </style>
  <!-- Google Tag Manager -->
  <script>
    (function(w, d, s, l, i) {
      w[l] = w[l] || [];
      w[l].push({
        'gtm.start': new Date().getTime(),
        event: 'gtm.js'
      });
      var f = d.getElementsByTagName(s)[0],
        j = d.createElement(s),
        dl = l != 'dataLayer' ? '&l=' + l : '';
      j.async = true;
      j.src =
        'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
      f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-PTNJ8J8L');
  </script>
  <!-- End Google Tag Manager -->


</head>

<body><!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PTNJ8J8L"
      height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  <div class="text-center py-2 mb-0 rounded-0"
    style="background-color: #FFD814; color: black; font-weight: bold; border: none; font-size: clamp(12px, 2vw, 18px);">
    <div class="container d-flex align-items-center justify-content-center">
      <!-- 5 estrellas verdes con borde blanco -->
      <div class="me-2" style="display: inline-flex; gap: 3px;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="#28a745" stroke="#ffffff" stroke-width="1"
          xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z">
          </path>
        </svg>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="#28a745" stroke="#ffffff" stroke-width="1"
          xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z">
          </path>
        </svg>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="#28a745" stroke="#ffffff" stroke-width="1"
          xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z">
          </path>
        </svg>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="#28a745" stroke="#ffffff" stroke-width="1"
          xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z">
          </path>
        </svg>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="#28a745" stroke="#ffffff" stroke-width="1"
          xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z">
          </path>
        </svg>
      </div>
      <span class="me-1"
        style="color: #000; font-weight: 500; font-size: 0.90rem;">Helped 17,250,579 Americans</span> <span
        class="me-1" style="font-size: 0.9rem;"></span>
    </div>
  </div>
  <div class="container" style="max-width: 800px;">
    <!-- Thank You Section -->
    <section id="thank-you" class="container my-2">
      <div class="text-center mb-3">
        <!--<h1 class="fw-bold text-black" style="margin-top: 10px;">Pay As Low as <span class="text-danger">$39/month</span>
          On Your Auto Insurance With Full Coverage</h1>
        <hr class="my-1">
        <p class="text-center" style="font-size: 1.2rem;">This is your chance to qualify! Take advantage of this
          <strong>New Safe Driver Incentive Program</strong> while it's available. <span class="text-primary">
            <strong>Act now!</strong> </span>
        </p>
        <p> <strong>Take this quiz for free - It will only take 2 minutes.</strong> <br>
          <strong style="color: red;">Last spots available.</strong>
        </p>
      </div>-->
      <div class="cards" style="margin-top: 2%;">
        <!-- Loading Block 1 -->
        <div id="loading1" class="loading boxme">
          <h4><i class='bi bi-check-circle text-success fs-3'></i> Validating your answers...</h4>
        </div>
        <!-- Loading Block 2 -->
        <div id="loading2" class="loading boxme">
          <h4><i class='bi bi-search text-primary fs-3'></i> Analyzing options...</h4>
        </div>
        <!-- Loading Block 3 -->
        <div id="loading3" class="loading boxme">
          <h4><i class='bi bi-shield-check text-info fs-3'></i> Confirming eligibility...</h4>
        </div>
        <!-- Thank You Block -->
        <div id="thank-you-block" class="boxme">
          <h5 style="color: #198754; font-size: 24px;">✅ You’re Almost Done!</h5>
          <p style="font-size: 18px; font-weight: 600;"> We’ve found 3 insurance quotes that fit your selection and you’re eligible for up to <span class="text-danger"> 40% OFF</span>.</b> </p>
          <p style="font-size: 18px;"> Call now to get your quote!</p>
          <!-- C2C Button-->
          <a class="button __tc_dni_phone card_submit" id="callBtn"
            onclick="fbq('track', 'ClickButton', {eventID: '<?php echo $macros['subid']; ?>'})" href="tel:<?php echo $macros['phone']; ?>"
            style="text-decoration: none">
            <div style="
              position: absolute;
              width: 65px;
              right: 0;
              margin-top: -68px;
              margin-right: 14px;
            " class="next-button"> </div>
            Call now: <?php echo $macros['phone']; ?>
          </a>
          <!--<p style="color: #dc3545; font-weight: bold; font-size: 18px;">Attention! Agents are only available for a few
            minutes.</p>-->
          <!--<p style="font-size: 18px;">Click to claim your discount before the program ends:</p>-->
          <p style="font-size: 14px;">Your place is reserved by <span class="countdown"> 2:33</span><br>Limited availability. Application deadline is coming soon. Your application number is: SOL-8828</p>
          
          
         ─────────────
          <p style=" font-size: 18px;">Want to get your quote online instead?</p>
          <!-- C2C Button-->
          
        
          <?php
            ob_start(); // Start output buffering
            
            // Get values from GET parameters
            $insured = isset($_GET['insured']) ? $_GET['insured'] : 'No';
            $subid = isset($_GET['subid']) ? $_GET['subid'] : '123';
            $carrier = isset($_GET['insurance_carrier']) ? $_GET['insurance_carrier'] : '';
            $homeowner = isset($_GET['homeowner']) ? $_GET['homeowner'] : 'No';
            $offerlink = isset($_GET['offerlink']) && !empty($_GET['offerlink']) ? $_GET['offerlink'] : 'https://trkmcl.com/5v4we7l4xk/274m5egeng';
            
            // If not insured, set carrier to 'N'
            if (strtolower($insured) !== 'yes') {
                $carrier = 'N';
            }
            
            // Normalize carrier parameter to lowercase, trimmed
            $carrier_normalized = strtolower(trim($carrier));
            
            // Encode parameters
            $insured_url = urlencode($insured);
            $carrier_url = urlencode($carrier);
            $homeowner_url = urlencode($homeowner);
            $subid_url = urlencode($subid);
            
            // Build redirect URL with dynamic offerlink or default
            $redirect_url = rtrim($offerlink, '/') . "/?subid={$subid_url}&subid1={$homeowner_url}&subid2={$insured_url}&subid3={$carrier_url}";
            
            // Redirect immediately if carrier is Allstate
            if ($carrier_normalized === 'allstate') {
                header("Location: $redirect_url");
                exit();
            }
            
            ob_end_flush(); // Send output buffer and end buffering
            
            // Continue rest of code...
            ?>

          
          
          

          <?php
            // Get values from GET parameters
            $insured = isset($_GET['insured']) ? $_GET['insured'] : 'No';
            $subid = isset($_GET['subid']) ? $_GET['subid'] : '123';
            $carrier = isset($_GET['insurance_carrier']) ? $_GET['insurance_carrier'] : '';
            $homeowner = isset($_GET['homeowner']) ? $_GET['homeowner'] : 'No';
          
            // If not insured, set carrier to 'N'
            if (strtolower($insured) !== 'yes') {
              $carrier = 'N';
            }
            

          
            // URL encode values for safety in URLs
            $insured_url = urlencode($insured);
            $carrier_url = urlencode($carrier);
            $homeowner_url = urlencode($homeowner);
            $subid = urlencode($subid);
            
            
            $base_url = isset($_GET['offerlink']) && $_GET['offerlink'] !== '' 
              ? $_GET['offerlink'] 
              : 'https://trkmcl.com/5v4we7l4xk/274m5egeng';
          

            // Compose your URL
            $url = $base_url . "?subid=$subid&subid1=$homeowner_url&subid2=$insured_url&subid3=$carrier_url";
          ?>
          
          
          <a class="button __tc_dni_phone card_submit" id="callBtn"
             onclick="fbq('track', 'ClickButton', {eventID: '<?php echo $macros['subid']; ?>'})"
             
             
             href="<?php echo $url; ?>"
             
             
             style="width:80%; text-decoration: none; color: white !important; padding: 10px 20px; display: inline-block; border-radius: 6px;">
             Click here to get your online quote
          </a>
        


          
          <p style="font-size: 18px; font-style: italic; "><italic>Answer 2 more questions to get online estimate from top 10 carriers.</italic></p>
          
          
          
          <!-- <div id="qs-widget"></div>-->
          
          
          <style>
            :root {
              --qs-bg: #ffffff;
              --qs-text: #1f2937;
              --qs-sub: #6b7280;
              --qs-border: #e5e7eb;
              --qs-pill: #3b82f6;
              --qs-cta: #ffffff;
              --qs-cta-bg: #3b82f6;
              --qs-cta-bg2: #2563eb;
              --qs-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
              --qs-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
              --qs-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            .qs-wrap {
              font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
              max-width: 900px;
              margin: 32px auto;
              color: var(--qs-text);
              padding: 0 16px;
            }

            .qs-header {
              text-align: center;
              margin: 0 0 32px 0;
              color: var(--qs-sub);
              font-size: 20px;
              font-weight: 500;
              position: relative;
            }

            .qs-header::after {
              content: '';
              position: absolute;
              bottom: -8px;
              left: 50%;
              transform: translateX(-50%);
              width: 60px;
              height: 3px;
              background: var(--qs-gradient);
              border-radius: 2px;
            }

            .qs-header b {
              color: var(--qs-text);
              font-weight: 700;
            }

            .qs-list {
              display: grid;
              gap: 24px;
              grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            }

            .qs-card {
              background: var(--qs-bg);
              border: 1px solid var(--qs-border);
              border-radius: 20px;
              padding: 24px;
              box-shadow: var(--qs-shadow);
              transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
              position: relative;
              overflow: hidden;
            }

            .qs-card::before {
              content: '';
              position: absolute;
              top: 0;
              left: 0;
              right: 0;
              height: 4px;
              background: var(--qs-gradient);
              opacity: 0;
              transition: opacity 0.3s ease;
            }

            .qs-card:hover {
              transform: translateY(-8px);
              box-shadow: var(--qs-shadow-hover);
              border-color: #d1d5db;
            }

            .qs-card:hover::before {
              opacity: 1;
            }

            .qs-top {
              display: grid;
              grid-template-columns: 80px 1fr auto;
              gap: 20px;
              align-items: center;
              margin-bottom: 20px;
            }

            .qs-logo {
              width: 80px;
              height: 80px;
              border-radius: 16px;
              background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
              display: flex;
              align-items: center;
              justify-content: center;
              overflow: hidden;
              border: 2px solid #f1f5f9;
              box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
              transition: all 0.3s ease;
            }

            .qs-card:hover .qs-logo {
              transform: scale(1.05);
              box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
            }

            .qs-logo img {
              width: 100%;
              height: 100%;
              object-fit: cover;
              display: block;
            }

            .qs-logo .qs-fallback {
              font-weight: 800;
              font-size: 24px;
              color: #64748b;
              text-transform: uppercase;
              letter-spacing: -0.5px;
              display: flex;
              align-items: center;
              justify-content: center;
              width: 100%;
              height: 100%;
              background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
              border-radius: 16px;
            }

            .qs-title {
              font-size: 20px;
              font-weight: 700;
              line-height: 1.3;
              margin: 0;
              color: var(--qs-text);
              letter-spacing: -0.025em;
            }



            .qs-desc {
              margin: 0 0 24px 0;
              color: var(--qs-sub);
              line-height: 1.6;
              font-size: 15px;
            }

            .qs-desc ul {
              margin: 12px 0 0 24px;
              padding: 0;
            }

            .qs-desc li {
              margin-bottom: 8px;
              position: relative;
            }

            .qs-desc li::marker {
              color: #3b82f6;
              font-weight: 600;
            }

            .qs-cta {
              display: inline-flex;
              align-items: center;
              justify-content: center;
              padding: 14px 28px;
              border-radius: 14px;
              font-weight: 700;
              font-size: 16px;
              text-decoration: none;
              color: var(--qs-cta);
              background: var(--qs-gradient);
              border: 0;
              box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.4);
              transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
              position: relative;
              overflow: hidden;
            }

            .qs-cta::before {
              content: '';
              position: absolute;
              top: 0;
              left: -100%;
              width: 100%;
              height: 100%;
              background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
              transition: left 0.5s;
            }

            .qs-cta:hover {
              transform: translateY(-2px);
              box-shadow: 0 8px 25px 0 rgba(59, 130, 246, 0.5);
            }

            .qs-cta:hover::before {
              left: 100%;
            }

            .qs-cta:active {
              transform: translateY(0);
            }

            .qs-muted {
              color: var(--qs-sub);
            }

            .qs-skeleton {
              border: 2px dashed #d1d5db;
              border-radius: 16px;
              padding: 40px 20px;
              text-align: center;
              color: var(--qs-sub);
              background: #f9fafb;
              font-size: 16px;
              font-weight: 500;
            }

            .qs-skeleton::before {
              content: '⏳';
              font-size: 24px;
              display: block;
              margin-bottom: 12px;
            }

            @media (max-width: 768px) {
              .qs-wrap {
                margin: 20px auto;
                padding: 0 12px;
              }

              .qs-list {
                grid-template-columns: 1fr;
                gap: 20px;
              }

              .qs-card {
                padding: 20px;
              }

              .qs-top {
                grid-template-columns: 70px 1fr;
                gap: 16px;
              }

              .qs-logo {
                width: 70px;
                height: 70px;
              }

              .qs-title {
                font-size: 18px;
              }



              .qs-header {
                font-size: 18px;
                margin-bottom: 24px;
              }
            }

            @media (max-width: 480px) {
              .qs-card {
                padding: 16px;
              }

              .qs-top {
                grid-template-columns: 60px 1fr;
                gap: 12px;
              }

              .qs-logo {
                width: 60px;
                height: 60px;
              }

              .qs-title {
                font-size: 16px;
              }

              .qs-cta {
                padding: 12px 24px;
                font-size: 15px;
                width: 100%;
                justify-content: center;
              }
            }
          </style>
          <script>
            (function() {
              const IPAPI_KEY = 'TeX6XTHx7VOUrrP'; // ip-api Pro

              // Тестовая функция для проверки загрузки картинок
              function testImageLoading() {
                const testImg = new Image();
                testImg.onload = function() {
                  console.log('[QuinstreetWidget] Test image loaded successfully');
                };
                testImg.onerror = function() {
                  console.warn('[QuinstreetWidget] Test image failed to load - possible CORS issue');
                };
                testImg.src = 'https://via.placeholder.com/100x100/3b82f6/ffffff?text=Test';
              }

              // Запускаем тест при инициализации
              testImageLoading();

              function ynTo01(v) {
                if (v == null) return 0;
                const s = String(v).trim().toLowerCase();
                return (s === 'yes' || s === 'y' || s === '1' || s === 'true') ? 1 : 0;
              }

              async function getUserIP() {
                console.log('[QuinstreetWidget] Get user IP…');
                try {
                  const r = await fetch('https://api.ipify.org?format=json', {
                    cache: 'no-store'
                  });
                  const j = await r.json();
                  console.log('[QuinstreetWidget] IP:', j.ip);
                  return j.ip || '';
                } catch (e) {
                  console.error('[QuinstreetWidget] IP error:', e);
                  return '';
                }
              }

              async function getZipFromIpApi(ip) {
                console.log('[QuinstreetWidget] Resolve ZIP via ip-api…', ip || '(no IP)');
                try {
                  const u = ip ?
                    `https://pro.ip-api.com/json/${encodeURIComponent(ip)}?fields=status,zip,city,regionName&key=${IPAPI_KEY}` :
                    `https://pro.ip-api.com/json/?fields=status,zip,city,regionName&key=${IPAPI_KEY}`;
                  const r = await fetch(u, {
                    cache: 'no-store'
                  });
                  const j = await r.json();
                  if (j.status === 'success') {
                    console.log(`[QuinstreetWidget] ZIP ${j.zip}, ${j.city}, ${j.regionName}`);
                    return j.zip || '';
                  }
                  console.warn('[QuinstreetWidget] ip-api error:', j.message);
                } catch (e) {
                  console.error('[QuinstreetWidget] ip-api failed:', e);
                }
                return '';
              }

              function samePageProxyUrl() {
                const url = new URL(window.location.href);
                url.searchParams.set('qs_listings', '1');
                return url.href;
              }

              async function fetchListingsViaSamePage(payload) {
                const url = samePageProxyUrl();
                console.log('[QuinstreetWidget] POST ->', url, payload);
                const resp = await fetch(url, {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify(payload),
                  credentials: 'same-origin'
                });
                const ct = resp.headers.get('content-type') || '';
                const text = await resp.text();
                console.log('[QuinstreetWidget] Proxy status:', resp.status, '| content-type:', ct);
                if (!ct.includes('application/json')) {
                  console.error('[QuinstreetWidget] Non-JSON (first 200):\n', text.slice(0, 200));
                  throw new Error('Proxy returned non-JSON');
                }
                if (!resp.ok) throw new Error('Proxy HTTP ' + resp.status + ' ' + text);
                return JSON.parse(text);
              }

              // Вытаскиваем первую картинку из description и возвращаем {logoSrc, descriptionHtmlClean}
              function extractLogo(descHtml) {
                console.log('[QuinstreetWidget] extractLogo input:', descHtml);

                const tmp = document.createElement('div');
                tmp.innerHTML = descHtml || '';

                // Ищем все возможные варианты картинок
                const img = tmp.querySelector('img');
                let src = '';

                if (img) {
                  // Пробуем разные атрибуты для src
                  src = img.getAttribute('src') ||
                    img.getAttribute('data-src') ||
                    img.getAttribute('data-original') ||
                    img.getAttribute('data-lazy-src') ||
                    '';

                  console.log('[QuinstreetWidget] Found image:', {
                    src: src,
                    alt: img.getAttribute('alt'),
                    width: img.getAttribute('width'),
                    height: img.getAttribute('height')
                  });

                  // Удаляем картинку из описания
                  img.remove();
                } else {
                  console.log('[QuinstreetWidget] No image found in description');
                }

                const result = {
                  logoSrc: src,
                  descriptionHtmlClean: tmp.innerHTML.trim()
                };

                console.log('[QuinstreetWidget] extractLogo result:', result);
                return result;
              }

              function normalize(data) {
                const arr = data?.response?.listingset?.listing || [];
                console.log('[QuinstreetWidget] Offers:', arr.length);

                return arr.map(l => {
                  console.log('[QuinstreetWidget] Processing listing:', {
                    title: l.title,
                    displayname: l.displayname,
                    company: l.company,
                    description: l.description ? l.description.substring(0, 200) + '...' : 'No description'
                  });

                  const {
                    logoSrc,
                    descriptionHtmlClean
                  } = extractLogo(l.description || '');

                  const title = l.title || l.displayname || l.company || 'Offer';
                  const fallback = (title || '').trim().charAt(0).toUpperCase() || 'A';

                  const result = {
                    title,
                    cpc: l.cpc,
                    clickurl: l.clickurl,
                    logo: logoSrc || '',
                    fallbackLetter: fallback,
                    descriptionHtml: descriptionHtmlClean
                  };

                  console.log('[QuinstreetWidget] Normalized listing:', result);
                  return result;
                });
              }

              function render(node, zip, items) {
                node.innerHTML = `
                  <div class="qs-wrap">
                    <div class="qs-header">Found <b>${items.length}</b> offers for ZIP <b>${zip||'-'}</b></div>
                    <div class="qs-list">
                      ${items.length? items.map(it=>`
                        <article class="qs-card">
                          <div class="qs-top">
                            <div class="qs-logo">
                              ${it.logo ? `<img src="${it.logo}" alt="${it.title}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" onload="this.nextElementSibling.style.display='none';">` : ''}
                              <div class="qs-fallback" style="${it.logo ? 'display: none;' : 'display: flex;'}">${it.fallbackLetter}</div>
                            </div>
                            <h3 class="qs-title">${it.title}</h3>
                          </div>
                          <div class="qs-desc">${it.descriptionHtml}</div>
                          <a href="${it.clickurl}" class="qs-cta" onclick="logOfferClick('${it.title}', '${it.clickurl}')">Get Quote</a>
                        </article>
                      `).join('') : `<div class="qs-skeleton">No offers for this ZIP.</div>`}
                    </div>
                  </div>
                `;

                // Добавляем обработчики для картинок после рендера
                const images = node.querySelectorAll('.qs-logo img');
                images.forEach(img => {
                  img.addEventListener('error', function() {
                    console.log('[QuinstreetWidget] Image failed to load:', this.src);
                    this.style.display = 'none';
                    const fallback = this.nextElementSibling;
                    if (fallback && fallback.classList.contains('qs-fallback')) {
                      fallback.style.display = 'flex';
                    }
                  });

                  img.addEventListener('load', function() {
                    console.log('[QuinstreetWidget] Image loaded successfully:', this.src);
                    const fallback = this.nextElementSibling;
                    if (fallback && fallback.classList.contains('qs-fallback')) {
                      fallback.style.display = 'none';
                    }
                  });
                });
              }

              // параметры из URL
              function qp(n, d = '') {
                const v = new URLSearchParams(location.search).get(n);
                return (v === null ? d : v);
              }
              const initParams = {
                env: qp('env', 'prod'),
                zip: qp('zip', ''),
                insured: qp('insured', 'No'),
                homeowner: qp('homeowner', 'No'),
                carrier: qp('insurance_carrier', ''),
                subid3: qp('subid3', '')
              };

              window.renderQuinstreetWidget = async function(opts) {
                console.log('[QuinstreetWidget] init:', opts);
                const container = document.querySelector(opts.container || '#qs-widget');
                if (!container) {
                  console.error('[QuinstreetWidget] Container not found');
                  return;
                }
                container.innerHTML = '<div class="qs-wrap"><div class="qs-skeleton">Loading offers…</div></div>';

                const ip = await getUserIP();
                let zip = opts.zip || '';
                if (!zip) zip = await getZipFromIpApi(ip);
                if (!zip) console.warn('[QuinstreetWidget] ZIP empty — offers may not return');

                const payload = {
                  env: (opts.env === 'stage' ? 'stage' : 'prod'),
                  zip: String(zip),
                  insured: ynTo01(opts.insured),
                  homeowner: ynTo01(opts.homeowner),
                  carrier: String(opts.carrier || ''),
                  subid3: String(opts.subid3 || '')
                };

                try {
                  const data = await fetchListingsViaSamePage(payload);
                  render(container, zip, normalize(data));
                } catch (e) {
                  console.error('[QuinstreetWidget] Listings error:', e);
                  container.innerHTML = '<div class="qs-wrap"><div class="qs-skeleton">' + (e?.message || e) + '</div></div>';
                }
              };

              // авто-запуск
              window.renderQuinstreetWidget({
                container: '#qs-widget',
                env: initParams.env,
                zip: initParams.zip,
                insured: initParams.insured,
                homeowner: initParams.homeowner,
                carrier: initParams.carrier,
                subid3: initParams.subid3
              });

              // Логируем инициализацию виджета
              console.log('[QuinstreetWidget] Widget initialized with params:', initParams);

              // Функция для логирования кликов по офферам
              window.logOfferClick = function(title, url) {
                // Отправляем данные на сервер для логирования
                fetch('thank-you.php', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                  },
                  body: JSON.stringify({
                    action: 'log_offer_click',
                    title: title,
                    url: url,
                    timestamp: new Date().toISOString(),
                    user_agent: navigator.userAgent,
                    referer: document.referrer
                  })
                }).catch(e => console.log('[QuinstreetWidget] Click logging failed:', e));

                console.log('[QuinstreetWidget] Offer clicked:', title, url);
              };
            })();
          </script>






        </div>
      </div>
    </section>
  </div>

  <!-- Logos de confianza -->
  <div class="text-center mt-3 mb-4">
    <p class="text-muted mb-2" style="font-size: 0.7rem; font-weight: 600;">Seen in the media</p>
    <img src="assets/brands2.png" alt="Medios de confianza" class="img-fluid" style="max-width: 300px; opacity: 0.9;">
  </div>

  <!-- Testimonial 1 (Original) -->
  <div class="testimonial d-flex align-items-center p-4"
    style="max-width: 800px; margin: 0 auto; border-radius: 10px; margin-bottom: 5px;"> <img
      src="assets/testimonial1.png" alt="Foto del cliente" class="rounded-circle me-3"
      style="width: 100px; height: 100px; object-fit: cover;">
    <div>
      <p class="fst-italic mb-2" style="font-size: 1rem;"> "I used to pay $217/month for car insurance. Now I only pay
        $67 with full coverage! I thought switching would be complicated, but the agent was clear, patient, and honest.
        <strong>I recommend calling right away!</strong>"
      </p>
      <!-- Estrellas doradas sobre fondo blanco -->
      <div class="d-flex justify-content-start mb-2" style="gap: 5px;">
        <!-- Estrella 1 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 2 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 3 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 4 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 5 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
      </div>
      <h6 class="fw-bold mb-0">Sarah A. Smythe – Dallas, TX</h6>
    </div>
  </div>
  <!-- Divider -->
  <div style="max-width: 800px; height: 1px; margin: 1px 0; opacity: 0;"></div>
  <!-- Testimonial 2 (Nuevo) -->
  <div class="testimonial d-flex align-items-center p-4"
    style="max-width: 800px; margin: 0 auto; border-radius: 10px; margin-bottom: 5px;"> <img
      src="assets/testimonial.png" alt="Foto de Peter J. Lewis" class="rounded-circle me-3"
      style="width: 100px; height: 100px; object-fit: cover;">
    <div>
      <p class="fst-italic mb-2" style="font-size: 1rem;"> "I was overpaying for years. They helped me save over $150 a
        month without changing my coverage. Everything was quick, easy, and no pressure <strong>I called, and it was the
          best thing I ever did!</strong>"</p>
      <!-- Estrellas doradas sobre fondo blanco -->
      <div class="d-flex justify-content-start mb-2" style="gap: 5px;">
        <!-- Estrella 1 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 2 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 3 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 4 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 5 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
      </div>
      <h6 class="fw-bold mb-0">Peter J. Lewis – Miami, FL</h6>
    </div>
  </div>
  <!-- Divider -->
  <div style="max-width: 800px; height: 1px; margin: 1px 0; opacity: 0;"></div>
  <!-- Testimonial 3 (Nuevo) -->
  <div class="testimonial d-flex align-items-center p-4" style="max-width: 800px; margin: 0 auto; border-radius: 10px;">
    <img src="assets/testimonial2.png" alt="Foto de Flossie R. Brown" class="rounded-circle me-3"
      style="width: 100px; height: 100px; object-fit: cover;">
    <div>
      <p class="fst-italic mb-2" style="font-size: 1rem;">"I waited too long. When I finally called, they explained
        everything and helped me cut my premium nearly in half <strong>If you're thinking about it, do it now.</strong>"
      </p>
      <!-- Estrellas doradas sobre fondo blanco -->
      <div class="d-flex justify-content-start mb-2" style="gap: 5px;">
        <!-- Estrella 1 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 2 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 3 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 4 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
        <!-- Estrella 5 -->
        <div
          style="background-color: #fff; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 5px; border: 1px solid #ddd;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700" width="18" height="18">
            <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.787 
            1.527 8.204-7.463-4.03L4.537 23.3l1.527-8.204-6.064-5.787 
            8.332-1.151z"></path>
          </svg>
        </div>
      </div>
      <h6 class="fw-bold mb-0">Flossie R. Brown – Phoenix, AZ</h6>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <div class="container text-center">
      <ul class="list-inline mb-3">
        <li class="list-inline-item"> <a href="https://luxeeloomm.com/" class="text-white">Start</a> </li>
        <li class="list-inline-item"> <a href="https://luxeeloomm.com/aplica/privacy-policy.html"
            class="text-white">Privacy Policy</a> </li>
        <li class="list-inline-item"> <a href="https://luxeeloomm.com/aplica/terms-of-services.html"
            class="text-white">Terms of Services</a> </li>
        <li class="list-inline-item"> <a href="https://luxeeloomm.com/aplica/contact-us.html" class="text-white">Contact
            us</a> </li>
      </ul>
      <p class="small text-white" style="max-width: 600px; margin: 0 auto;">This website is for informational purposes
        only and does not constitute legal, tax, or financial advice. The information provided does not guarantee
        specific results, and results may vary based on individual circumstances. We are not a law firm or government
        entity. We recommend consulting with a qualified professional before making financial decisions. By using this
        site, you agree that any action taken is at your own risk. </p>
      <p class="text-white">© 2025 All rights reserved.</p>
    </div>
  </footer>
  <!-- Bootstrap 5 JS -->
  <script src="assets/bootstrap.bundle.min.js"></script>
  <script src="assets/scripts.js"></script>
  <!-- Event snippet for Calls conversion page -->
  <script>
    function gtag_report_conversion(url) {
      var callback = function() {
        if (typeof(url) != 'undefined') {
          window.location = url;
        }
      };
      gtag('event', 'conversion', {
        'send_to': 'AW-16674691485/8NGQCPif_PcZEJ2zjo8-',
        'value': 1.0,
        'currency': 'USD',
        'event_callback': callback
      });
      return false;
    }
  </script>


  <script>
    $(document).ready(function() {
      // Скрываем все блоки изначально
      $('#loading1, #loading2, #loading3, #thank-you-block').hide();

      // Показываем первый блок загрузки
      $('#loading1').show().addClass('fade-in');

      // Через 1.3 секунды скрываем первый блок и показываем второй
      setTimeout(function() {
        $('#loading1').addClass('fade-out');
        setTimeout(function() {
          $('#loading1').hide();
          $('#loading2').show().addClass('fade-in');

          // Через 1.3 секунды скрываем второй блок и показываем третий
          setTimeout(function() {
            $('#loading2').addClass('fade-out');
            setTimeout(function() {
              $('#loading2').hide();
              $('#loading3').show().addClass('fade-in');

              // Через 1.3 секунды скрываем третий блок и показываем блок спасибо
              setTimeout(function() {
                $('#loading3').addClass('fade-out');
                setTimeout(function() {
                  $('#loading3').hide();
                  $('#thank-you-block').show().addClass('fade-in');

                  // Прокручиваем к блоку спасибо
                  $('html, body').stop().animate({
                    scrollTop: $('#thank-you-block').offset().top
                  }, 250, 'swing');
                }, 300);
              }, 1300);
            }, 300);
          }, 1300);
        }, 300);
      }, 1300);
    });

    // Таймер обратного отсчета
    var timer2 = '5:01';
    var interval = setInterval(function() {
      var timer = timer2.split(':');
      var minutes = parseInt(timer[0], 10);
      var seconds = parseInt(timer[1], 10);
      --seconds;
      minutes = seconds < 0 ? --minutes : minutes;
      if (minutes < 0) clearInterval(interval);
      seconds = seconds < 0 ? 59 : seconds;
      seconds = seconds < 10 ? '0' + seconds : seconds;
      $('.countdown').html(minutes + ':' + seconds);
      timer2 = minutes + ':' + seconds;
    }, 1000);
  </script>
  <script defer src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script defer src="assets/script/scripts.js"></script>
  <script>
    // Исправляем ошибку в scripts.js
    $(document).ready(function() {
      // Проверяем существование элемента перед обращением к нему
      if (document.getElementById('quiz-progress')) {
        const progress = document.getElementById('quiz-progress');
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (isMobile) {
          progress.style.width = '7%';
          progress.textContent = '0%';
        } else {
          progress.style.width = '4%';
          progress.textContent = '0%';
        }
      }
    });
  </script>




</body>

</html>