<!DOCTYPE html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Service Kiosk — Ethio Telecom VMS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/ethiotelecom-logo.png') }}" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root, [data-theme="dark"] {
            --et-green: #4CAF50;
            --et-green-dark: #1B7D3A;
            --et-green-light: #6AAF35;
            --et-lime: #8BC34A;
            --et-blue: #4EB8D4;
            --et-bg: #0a1628;
            --et-surface: rgba(20, 35, 55, 0.8);
            --et-border: rgba(76, 175, 80, 0.15);
            --et-text: #e2e8f0;
            --et-text-heading: #f1f5f9;
            --et-muted: #94a3b8;
            --et-input-bg: rgba(10, 22, 40, 0.6);
            --et-placeholder: #475569;
            --et-grad-1: rgba(76, 175, 80, 0.12);
            --et-grad-2: rgba(78, 184, 212, 0.08);
        }
        [data-theme="light"] {
            --et-bg: #f5f7fa;
            --et-surface: rgba(255, 255, 255, 0.95);
            --et-border: rgba(76, 175, 80, 0.18);
            --et-text: #1e293b;
            --et-text-heading: #0f172a;
            --et-muted: #64748b;
            --et-input-bg: #ffffff;
            --et-placeholder: #9ca3af;
            --et-grad-1: rgba(76, 175, 80, 0.06);
            --et-grad-2: rgba(78, 184, 212, 0.04);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--et-bg); color: var(--et-text);
            transition: background 0.3s, color 0.3s;
            min-height: 100vh;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .bg-pattern {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse at 30% 50%, var(--et-grad-1) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 20%, var(--et-grad-2) 0%, transparent 50%);
            transition: background 0.3s;
        }
        .kiosk { position: relative; z-index: 1; text-align: center; width: 100%; max-width: 540px; padding: 1.5rem; }
        .logo { display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 1.5rem; }
        .logo-img { height: 44px; width: auto; object-fit: contain; }
        .logo-divider { width: 1px; height: 32px; background: rgba(76, 175, 80, 0.25); }
        .logo-text h1 { font-size: 1.1rem; font-weight: 700; color: var(--et-text-heading); }
        .logo-text p { font-size: 0.65rem; color: var(--et-green); text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; }

        /* Theme toggle */
        .theme-toggle {
            position: fixed; top: 1rem; left: 1rem; z-index: 10;
            width: 40px; height: 40px; border-radius: 10px;
            border: 1px solid rgba(76, 175, 80, 0.2); background: rgba(76, 175, 80, 0.1);
            color: var(--et-muted); cursor: pointer; display: flex; align-items: center;
            justify-content: center; transition: all 0.3s; font-size: 1.1rem;
        }
        .theme-toggle:hover { background: rgba(76, 175, 80, 0.15); color: var(--et-green); }
        .theme-toggle .icon-sun { display: none; }
        .theme-toggle .icon-moon { display: block; }
        [data-theme="light"] .theme-toggle .icon-sun { display: block; }
        [data-theme="light"] .theme-toggle .icon-moon { display: none; }
        [data-theme="light"] .card { box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); }

        /* Language switcher */
        .lang-switch {
            position: fixed; top: 1rem; right: 1rem; z-index: 10;
            display: flex; gap: 0.5rem;
        }
        .lang-btn {
            padding: 0.4rem 0.8rem; border-radius: 8px; border: 1px solid var(--et-border);
            background: var(--et-surface); color: var(--et-muted); font-size: 0.75rem;
            cursor: pointer; font-family: 'Inter', sans-serif; transition: all 0.2s;
        }
        .lang-btn.active { background: var(--et-green); color: white; border-color: var(--et-green); }

        .card {
            background: var(--et-surface); border: 1px solid var(--et-border);
            border-radius: 20px; padding: 2rem; backdrop-filter: blur(12px);
            transition: background 0.3s, border-color 0.3s;
        }
        .card h2 { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.4rem; }
        .card p.subtitle { color: var(--et-muted); font-size: 0.85rem; margin-bottom: 1.25rem; }

        .scan-input {
            width: 100%; padding: 0.85rem; font-size: 1.1rem; text-align: center;
            background: var(--et-input-bg); border: 2px solid rgba(76, 175, 80, 0.25);
            border-radius: 14px; color: var(--et-text-heading); font-family: 'Inter', monospace;
            outline: none; transition: all 0.2s; letter-spacing: 0.05em;
        }
        .scan-input:focus { border-color: var(--et-green); box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.15); }
        .scan-input::placeholder { color: var(--et-placeholder); font-size: 0.9rem; }

        .btn-group { display: flex; gap: 0.75rem; margin-top: 1rem; }
        .btn {
            flex: 1; padding: 0.75rem; border: none; border-radius: 12px;
            font-family: 'Inter', sans-serif; font-size: 0.95rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s;
        }
        .btn-checkin { background: linear-gradient(135deg, var(--et-green), var(--et-green-dark)); color: white; }
        .btn-checkin:hover { box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3); transform: translateY(-1px); }
        .btn-checkout { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-checkout:hover { box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3); transform: translateY(-1px); }
        .btn-secondary { background: rgba(100, 116, 139, 0.3); color: var(--et-muted); border: 1px solid rgba(255,255,255,0.1); }
        .btn-secondary:hover { background: rgba(100, 116, 139, 0.5); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        /* Steps */
        .step { display: none; }
        .step.active { display: block; }

        /* Camera */
        .camera-container {
            position: relative; border-radius: 14px; overflow: hidden;
            margin: 1rem auto; width: 240px; height: 240px;
            border: 2px solid rgba(76, 175, 80, 0.3); background: #000;
        }
        .camera-container video, .camera-container canvas {
            width: 100%; height: 100%; object-fit: cover; display: block;
        }
        .camera-container canvas { display: none; }
        .photo-preview { display: none; width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0.5rem auto; border: 3px solid var(--et-green); }

        /* Signature */
        .sig-canvas {
            width: 100%; height: 150px; border: 2px solid rgba(76, 175, 80, 0.25);
            border-radius: 14px; background: rgba(255,255,255,0.05); cursor: crosshair;
            touch-action: none;
        }
        .sig-label { font-size: 0.8rem; color: #64748b; margin-top: 0.5rem; }

        /* Result */
        .result { margin-top: 1.25rem; padding: 1rem; border-radius: 14px; display: none; }
        .result.success { background: rgba(76, 175, 80, 0.12); border: 1px solid rgba(76, 175, 80, 0.25); }
        .result.error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); }
        .result h3 { font-size: 1rem; margin-bottom: 0.4rem; }
        .result .details { text-align: left; font-size: 0.8rem; color: var(--et-muted); line-height: 1.8; }
        .result .details strong { color: var(--et-text); }
        .escort-warning {
            margin-top: 0.5rem; padding: 0.5rem; border-radius: 8px;
            background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.3);
            font-size: 0.8rem; color: #fbbf24;
        }

        .footer { position: fixed; bottom: 0.75rem; font-size: 0.65rem; color: #475569; z-index: 1; }
    </style>
</head>
<body>
    <div class="bg-pattern"></div>

    <!-- Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle dark/light mode">
        <span class="icon-moon">🌙</span>
        <span class="icon-sun">☀️</span>
    </button>

    <!-- Language Switcher (FR-014) -->
    <div class="lang-switch">
        <button class="lang-btn" onclick="toggleVoice()" id="voiceBtn" title="Toggle voice prompts">🔊</button>
        <button class="lang-btn active" onclick="setLang('en')" id="langEn">EN</button>
        <button class="lang-btn" onclick="setLang('am')" id="langAm">አማ</button>
    </div>

    <div class="kiosk">
        <div class="logo">
            <img src="{{ asset('images/ethiotelecom-logo.png') }}" alt="Ethio Telecom" class="logo-img">
            <div class="logo-divider"></div>
            <div class="logo-text">
                <h1>Visitor Management</h1>
                <p data-i18n="kiosk_title">Self-Service Kiosk</p>
            </div>
        </div>

        <!-- Step 1: QR Scan -->
        <div class="card step active" id="step1">
            <h2 data-i18n="scan_title">📱 Scan Your QR Code</h2>
            <p class="subtitle" data-i18n="scan_subtitle">Scan or enter your QR code below to check in or check out.</p>
            <input type="text" id="qrInput" class="scan-input" data-i18n-placeholder="scan_placeholder" placeholder="Scan QR code here…" autofocus autocomplete="off">
            <div class="btn-group">
                <button class="btn btn-checkin" onclick="startCheckIn()" id="btnCheckIn" data-i18n="check_in">✓ Check In</button>
                <button class="btn btn-checkout" onclick="doCheckOut()" id="btnCheckOut" data-i18n="check_out">← Check Out</button>
            </div>
            <div id="result" class="result">
                <h3 id="resultTitle"></h3>
                <div id="resultDetails" class="details"></div>
            </div>
        </div>

        <!-- Step 2: Screening Questionnaire (FR-001) -->
        <div class="card step" id="step2">
            <h2 data-i18n="screening_title">📋 Health & Safety Screening</h2>
            <p class="subtitle" data-i18n="screening_subtitle">Please answer the following questions before proceeding.</p>
            <div id="screeningQuestions" style="text-align:left; margin-bottom:1rem;"></div>
            <div class="btn-group">
                <button class="btn btn-checkin" onclick="submitScreening()" data-i18n="continue">Continue →</button>
            </div>
            <div id="screeningEmpty" style="display:none; text-align:center; color:var(--et-muted); padding:1rem;">
                <p data-i18n="no_screening">No screening questions required. Proceeding...</p>
            </div>
        </div>

        <!-- Step 3: Escort Selection (FR-008) -->
        <div class="card step" id="step3">
            <h2 data-i18n="escort_title">🛡️ Escort Required</h2>
            <p class="subtitle" data-i18n="escort_subtitle">This zone requires an escort. Please select your assigned escort.</p>
            <select id="escortSelect" class="scan-input" style="font-size:0.95rem; text-align:left;">
                <option value="" data-i18n="select_escort">-- Select Escort --</option>
            </select>
            <div class="btn-group">
                <button class="btn btn-checkin" onclick="confirmEscort()" data-i18n="continue">Continue →</button>
            </div>
        </div>

        <!-- Step 4: Photo Capture (FR-005) -->
        <div class="card step" id="step4">
            <h2 data-i18n="photo_title">📷 Photo Capture</h2>
            <p class="subtitle" data-i18n="photo_subtitle">Please look at the camera and take your photo for the visitor badge.</p>
            <div class="camera-container" id="cameraBox">
                <video id="cameraVideo" autoplay playsinline></video>
                <canvas id="cameraCanvas"></canvas>
            </div>
            <img id="photoPreview" class="photo-preview" alt="Preview">
            <div class="btn-group">
                <button class="btn btn-checkin" onclick="capturePhoto()" id="btnCapture" data-i18n="take_photo">📸 Take Photo</button>
                <button class="btn btn-secondary" onclick="retakePhoto()" id="btnRetake" style="display:none" data-i18n="retake">↻ Retake</button>
            </div>
            <div class="btn-group" id="photoNext" style="display:none">
                <button class="btn btn-checkin" onclick="goToStep5()" data-i18n="continue">Continue →</button>
            </div>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="skipPhoto()" data-i18n="skip">Skip</button>
            </div>
        </div>

        <!-- Step 5: Signature + Document Upload (FR-005) -->
        <div class="card step" id="step5">
            <h2 data-i18n="sig_title">✍️ Digital Signature</h2>
            <p class="subtitle" data-i18n="sig_subtitle">Please sign below to acknowledge the visitor terms and safety guidelines.</p>
            <canvas id="sigCanvas" class="sig-canvas"></canvas>
            <p class="sig-label" data-i18n="sig_hint">Draw your signature with mouse or finger</p>
            <div style="margin-top:1rem; text-align:left;">
                <label style="font-size:0.85rem; color:var(--et-muted); display:block; margin-bottom:0.5rem;" data-i18n="doc_label">📎 Upload supporting documents (optional):</label>
                <input type="file" id="docUpload" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="font-size:0.8rem; color:var(--et-text);">
            </div>
            <div class="btn-group" style="margin-top:1rem;">
                <button class="btn btn-secondary" onclick="clearSignature()" data-i18n="clear">Clear</button>
                <button class="btn btn-checkin" onclick="completeCheckIn()" data-i18n="complete_checkin">✓ Complete Check-In</button>
            </div>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="skipSignature()" data-i18n="skip">Skip</button>
            </div>
        </div>
    </div>

    <div class="footer">© {{ date('Y') }} Ethio Telecom — Visitor Management System</div>

    <script>
        // --- i18n (FR-014) ---
        const translations = {
            en: {
                kiosk_title: 'Self-Service Kiosk', scan_title: '📱 Scan Your QR Code',
                scan_subtitle: 'Scan or enter your QR code below to check in or check out.',
                scan_placeholder: 'Scan QR code here…', check_in: '✓ Check In', check_out: '← Check Out',
                screening_title: '📋 Health & Safety Screening',
                screening_subtitle: 'Please answer the following questions before proceeding.',
                no_screening: 'No screening questions required. Proceeding...',
                escort_title: '🛡️ Escort Required',
                escort_subtitle: 'This zone requires an escort. Please select your assigned escort.',
                select_escort: '-- Select Escort --',
                photo_title: '📷 Photo Capture',
                photo_subtitle: 'Please look at the camera and take your photo for the visitor badge.',
                take_photo: '📸 Take Photo', retake: '↻ Retake', continue: 'Continue →', skip: 'Skip',
                sig_title: '✍️ Digital Signature',
                sig_subtitle: 'Please sign below to acknowledge the visitor terms and safety guidelines.',
                sig_hint: 'Draw your signature with mouse or finger', clear: 'Clear',
                complete_checkin: '✓ Complete Check-In',
                doc_label: '📎 Upload supporting documents (optional):',
                voice_on: '🔊', voice_off: '🔇',
            },
            am: {
                kiosk_title: 'የራስ አገልግሎት ኪዮስክ', scan_title: '📱 የQR ኮድ ይቃኙ',
                scan_subtitle: 'ለመግባት ወይም ለመውጣት የQR ኮድዎን ከዚህ ያስገቡ።',
                scan_placeholder: 'የQR ኮድ እዚህ ያስገቡ…', check_in: '✓ ይግቡ', check_out: '← ይውጡ',
                screening_title: '📋 የጤና እና ደህንነት ቅኝት',
                screening_subtitle: 'እባክዎ ከመቀጠልዎ በፊት የሚከተሉትን ጥያቄዎች ይመልሱ።',
                no_screening: 'የቅኝት ጥያቄዎች አያስፈልጉም። በመቀጠል ላይ...',
                escort_title: '🛡️ አጃቢ ያስፈልጋል',
                escort_subtitle: 'ይህ ቦታ አጃቢ ይፈልጋል። እባክዎ አጃቢዎን ይምረጡ።',
                select_escort: '-- አጃቢ ይምረጡ --',
                photo_title: '📷 ፎቶ ማንሣት',
                photo_subtitle: 'እባክዎ ካሜራውን ይመልከቱ እና ለባጅ ፎቶዎን ያንሱ።',
                take_photo: '📸 ፎቶ ያንሱ', retake: '↻ እንደገና ያንሱ', continue: 'ቀጥል →', skip: 'ዝለል',
                sig_title: '✍️ ዲጂታል ፊርማ',
                sig_subtitle: 'እባክዎ የጎብኚ ደንቦችን እና የደህንነት መመሪያዎችን ለማረጋገጥ ከዚህ ይፈርሙ።',
                sig_hint: 'ፊርማዎን በማውስ ወይም በጣት ይሳሉ', clear: 'ያጽዱ',
                complete_checkin: '✓ ግቢን ያጠናቅቁ',
                doc_label: '📎 ሰነዶችን ያስገቡ (አማራጭ):',
                voice_on: '🔊', voice_off: '🔇',
            }
        };
        let currentLang = 'en';

        function setLang(lang) {
            currentLang = lang;
            document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(lang === 'en' ? 'langEn' : 'langAm').classList.add('active');
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (translations[lang][key]) el.textContent = translations[lang][key];
            });
            document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                const key = el.getAttribute('data-i18n-placeholder');
                if (translations[lang][key]) el.placeholder = translations[lang][key];
            });
        }

        // --- State ---
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let currentQr = '';
        let capturedPhoto = null;
        let capturedSignature = null;
        let cameraStream = null;
        let screeningData = [];
        let selectedEscortId = null;
        let visitData = null; // stored from QR lookup
        let voiceEnabled = localStorage.getItem('vms-voice') !== 'off';

        // --- Voice Prompts (FR-014) ---
        function speak(text) {
            if (!voiceEnabled || !window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            utter.lang = currentLang === 'am' ? 'am-ET' : 'en-US';
            utter.rate = 0.9;
            window.speechSynthesis.speak(utter);
        }

        // --- Step 1: QR Scan ---
        document.getElementById('qrInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') { e.preventDefault(); startCheckIn(); }
        });

        async function startCheckIn() {
            currentQr = document.getElementById('qrInput').value.trim();
            if (!currentQr) return;

            // FR-005: First lookup the QR to get visit details (visitor_type, category, zone)
            try {
                const lookupRes = await fetch(`/api/qr/lookup?qr_code=${encodeURIComponent(currentQr)}`);
                const lookupJson = await lookupRes.json();

                if (!lookupJson.success) {
                    showResult(false, lookupJson.message || 'Invalid QR code.');
                    return;
                }

                visitData = lookupJson.data;

                // Early validation
                if (visitData.is_blacklisted) {
                    showResult(false, 'Check-in denied. Please contact security.');
                    return;
                }

                if (visitData.status !== 'approved') {
                    showResult(false, `Visit request is '${visitData.status}'. Only approved requests can be checked in.`);
                    return;
                }

            } catch (e) {
                console.warn('QR lookup error', e);
                showResult(false, 'Connection error. Please try again.');
                return;
            }

            speak(currentLang === 'am' ? 'እባክዎ ጥያቄዎችን ይመልሱ' : 'Please answer the screening questions.');
            showStep(2);
            await loadScreeningQuestions();
        }

        function showResult(success, message, details = '') {
            const resultDiv = document.getElementById('result');
            const resultTitle = document.getElementById('resultTitle');
            const resultDetails = document.getElementById('resultDetails');
            resultDiv.style.display = 'block';
            resultDiv.className = success ? 'result success' : 'result error';
            resultTitle.textContent = (success ? '✅ ' : '❌ ') + message;
            resultDetails.innerHTML = details;
            setTimeout(() => { resultDiv.style.display = 'none'; }, 10000);
        }

        async function doCheckOut() {
            const qr = document.getElementById('qrInput').value.trim();
            if (!qr) return;
            await sendRequest('/api/qr/check-out', qr);
        }

        // --- Step 2: Screening (FR-001) ---
        async function loadScreeningQuestions() {
            try {
                // Use actual visitor_type and category from QR lookup instead of hardcoded 'external'
                const visitorType = visitData ? (visitData.visitor_type || 'external') : 'external';
                const category = visitData ? (visitData.category || '') : '';
                let url = `/api/screening-questions?visitor_type=${encodeURIComponent(visitorType)}`;
                if (category) url += `&category=${encodeURIComponent(category)}`;

                const res = await fetch(url);
                const json = await res.json();
                const container = document.getElementById('screeningQuestions');
                container.innerHTML = '';

                if (!json.data || json.data.length === 0) {
                    document.getElementById('screeningEmpty').style.display = 'block';
                    setTimeout(() => { document.getElementById('screeningEmpty').style.display = 'none'; goToEscortOrPhoto(); }, 1500);
                    return;
                }

                json.data.forEach(q => {
                    const qText = currentLang === 'am' && q.question_text_am ? q.question_text_am : q.question_text;
                    let html = `<div style="margin-bottom:1rem; padding:0.75rem; border-radius:10px; border:1px solid var(--et-border); background:var(--et-input-bg);">`;
                    html += `<p style="font-size:0.9rem; font-weight:600; margin-bottom:0.5rem; color:var(--et-text-heading);">${qText}${q.is_required ? ' <span style="color:#ef4444;">*</span>' : ''}</p>`;

                    if (q.type === 'yes_no') {
                        html += `<label style="margin-right:1rem;cursor:pointer;"><input type="radio" name="sq_${q.id}" value="yes"> Yes</label>`;
                        html += `<label style="cursor:pointer;"><input type="radio" name="sq_${q.id}" value="no"> No</label>`;
                    } else if (q.type === 'text') {
                        html += `<input type="text" class="scan-input" style="font-size:0.85rem;padding:0.5rem;margin-top:0.25rem;" data-sq="${q.id}">`;
                    } else if (q.type === 'select' && q.options) {
                        html += `<select class="scan-input" style="font-size:0.85rem;padding:0.5rem;text-align:left;" data-sq="${q.id}"><option value="">--</option>`;
                        q.options.forEach(o => { html += `<option value="${o}">${o}</option>`; });
                        html += `</select>`;
                    }
                    html += `</div>`;
                    container.innerHTML += html;
                });
                screeningData = json.data;
            } catch (e) { console.warn('Screening fetch error', e); goToEscortOrPhoto(); }
        }

        function submitScreening() {
            const responses = [];
            for (const q of screeningData) {
                let val = '';
                if (q.type === 'yes_no') {
                    const checked = document.querySelector(`input[name="sq_${q.id}"]:checked`);
                    val = checked ? checked.value : '';
                } else {
                    const el = document.querySelector(`[data-sq="${q.id}"]`);
                    val = el ? el.value : '';
                }
                if (q.is_required && !val) {
                    alert(currentLang === 'am' ? 'እባክዎ ሁሉንም ጥያቄዎች ይመልሱ።' : 'Please answer all required questions.');
                    return;
                }
                responses.push({ question_id: q.id, response: val });
            }
            window._screeningResponses = responses;
            goToEscortOrPhoto();
        }

        // --- Step 3: Escort (FR-008) ---
        async function goToEscortOrPhoto() {
            // Only show escort step if the zone actually requires escort
            const escortRequired = visitData && visitData.escort_required;

            if (escortRequired) {
                try {
                    const res = await fetch('/api/escorts');
                    const json = await res.json();
                    if (json.data && json.data.length > 0) {
                        const sel = document.getElementById('escortSelect');
                        sel.innerHTML = '<option value="">-- Select Escort --</option>';
                        json.data.forEach(e => {
                            sel.innerHTML += `<option value="${e.id}">${e.name} (${e.role})</option>`;
                        });
                        speak(currentLang === 'am' ? 'እባክዎ አጃቢዎን ይምረጡ' : 'Please select your escort.');
                        showStep(3);
                        return;
                    }
                } catch (e) { console.warn('Escorts fetch error', e); }
            }
            goToPhoto();
        }

        function confirmEscort() {
            selectedEscortId = document.getElementById('escortSelect').value || null;
            goToPhoto();
        }

        function goToPhoto() {
            speak(currentLang === 'am' ? 'እባክዎ ፎቶዎን ያንሱ' : 'Please take your photo.');
            showStep(4);
            startCamera();
        }

        // --- Step 4: Camera (FR-005) ---
        async function startCamera() {
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 480, height: 480 } });
                document.getElementById('cameraVideo').srcObject = cameraStream;
            } catch (e) { console.warn('Camera not available:', e); }
        }

        function stopCamera() {
            if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
        }

        function capturePhoto() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            canvas.width = 480; canvas.height = 480;
            canvas.getContext('2d').drawImage(video, 0, 0, 480, 480);
            capturedPhoto = canvas.toDataURL('image/jpeg', 0.8);
            document.getElementById('photoPreview').src = capturedPhoto;
            document.getElementById('photoPreview').style.display = 'block';
            document.getElementById('btnCapture').style.display = 'none';
            document.getElementById('btnRetake').style.display = 'block';
            document.getElementById('photoNext').style.display = 'flex';
            stopCamera();
        }

        function retakePhoto() {
            capturedPhoto = null;
            document.getElementById('photoPreview').style.display = 'none';
            document.getElementById('btnCapture').style.display = 'block';
            document.getElementById('btnRetake').style.display = 'none';
            document.getElementById('photoNext').style.display = 'none';
            startCamera();
        }

        function skipPhoto() { capturedPhoto = null; stopCamera(); goToStep5(); }
        function goToStep5() {
            stopCamera();
            speak(currentLang === 'am' ? 'እባክዎ ይፈርሙ' : 'Please sign to complete check-in.');
            showStep(5);
            initSignaturePad();
        }

        // --- Step 5: Signature (FR-005) ---
        let sigCtx, sigDrawing = false;

        function initSignaturePad() {
            const canvas = document.getElementById('sigCanvas');
            canvas.width = canvas.offsetWidth; canvas.height = 150;
            sigCtx = canvas.getContext('2d');
            sigCtx.strokeStyle = '#e2e8f0'; sigCtx.lineWidth = 2.5;
            sigCtx.lineCap = 'round'; sigCtx.lineJoin = 'round';

            const getPos = (e) => {
                const rect = canvas.getBoundingClientRect();
                const touch = e.touches ? e.touches[0] : e;
                return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
            };
            const startDraw = (e) => { sigDrawing = true; const p = getPos(e); sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); };
            const draw = (e) => { if (!sigDrawing) return; e.preventDefault(); const p = getPos(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); };
            const stopDraw = () => { sigDrawing = false; };

            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDraw);
            canvas.addEventListener('mouseleave', stopDraw);
            canvas.addEventListener('touchstart', startDraw, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            canvas.addEventListener('touchend', stopDraw);
        }

        function clearSignature() {
            const canvas = document.getElementById('sigCanvas');
            sigCtx.clearRect(0, 0, canvas.width, canvas.height);
            capturedSignature = null;
        }

        function skipSignature() { capturedSignature = null; completeCheckIn(); }

        // --- Complete Check-In ---
        async function completeCheckIn() {
            capturedSignature = document.getElementById('sigCanvas').toDataURL('image/png');

            const body = {
                qr_code: currentQr,
                screening_responses: window._screeningResponses || [],
            };
            if (capturedPhoto) body.photo = capturedPhoto;
            if (capturedSignature) body.signature = capturedSignature;
            if (selectedEscortId) body.escort_id = selectedEscortId;

            await sendRequest('/api/qr/check-in', currentQr, body);
            showStep(1);
            document.getElementById('qrInput').value = '';
            document.getElementById('qrInput').focus();
            capturedPhoto = null; capturedSignature = null;
            selectedEscortId = null; window._screeningResponses = [];
            visitData = null;
            speak(currentLang === 'am' ? 'ግቢ ተጠናቅቋል' : 'Check-in complete. Thank you.');
        }

        // --- Request handler ---
        async function sendRequest(url, qrCode, extraBody = null) {
            document.getElementById('btnCheckIn').disabled = true;
            document.getElementById('btnCheckOut').disabled = true;
            const body = extraBody || { qr_code: qrCode };
            if (!body.qr_code) body.qr_code = qrCode;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                const resultDiv = document.getElementById('result');
                const resultTitle = document.getElementById('resultTitle');
                const resultDetails = document.getElementById('resultDetails');
                resultDiv.style.display = 'block';

                if (data.success) {
                    resultDiv.className = 'result success';
                    resultTitle.textContent = '✅ ' + data.message;
                    let html = '';
                    if (data.data) {
                        const d = data.data;
                        if (d.visitor) html += `<strong>Visitor:</strong> ${d.visitor}<br>`;
                        if (d.organization) html += `<strong>Organization:</strong> ${d.organization}<br>`;
                        if (d.host) html += `<strong>Host:</strong> ${d.host}<br>`;
                        if (d.site) html += `<strong>Site:</strong> ${d.site}<br>`;
                        if (d.zone) html += `<strong>Zone:</strong> ${d.zone}<br>`;
                        if (d.checked_in_at) html += `<strong>Time:</strong> ${d.checked_in_at}<br>`;
                        if (d.checked_out_at) html += `<strong>Time:</strong> ${d.checked_out_at}<br>`;
                        if (d.duration) html += `<strong>Duration:</strong> ${d.duration}<br>`;
                        if (d.escort_required) html += '<div class="escort-warning">⚠️ Escort Required — Please wait for your host.</div>';
                        if (d.visit_request_id) {
                            html += `<a href="/visit/${d.visit_request_id}/badge" target="_blank" class="btn btn-checkin" style="display:inline-block; text-decoration:none; text-align:center; margin-top:0.75rem; width:100%;">🖨️ Print Badge</a>`;
                        }
                    }
                    resultDetails.innerHTML = html;
                } else {
                    resultDiv.className = 'result error';
                    resultTitle.textContent = '❌ ' + data.message;
                    resultDetails.innerHTML = '';
                }
                setTimeout(() => { resultDiv.style.display = 'none'; }, 10000);
            } catch (err) {
                const resultDiv = document.getElementById('result');
                resultDiv.style.display = 'block'; resultDiv.className = 'result error';
                document.getElementById('resultTitle').textContent = '❌ Connection error';
                document.getElementById('resultDetails').innerHTML = 'Please try again or contact the front desk.';
            }
            document.getElementById('btnCheckIn').disabled = false;
            document.getElementById('btnCheckOut').disabled = false;
        }

        // --- Navigation ---
        function showStep(n) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + n).classList.add('active');
        }
    </script>
    <script>
        // --- Voice Toggle ---
        function toggleVoice() {
            voiceEnabled = !voiceEnabled;
            localStorage.setItem('vms-voice', voiceEnabled ? 'on' : 'off');
            document.getElementById('voiceBtn').textContent = voiceEnabled ? '🔊' : '🔇';
            document.getElementById('voiceBtn').classList.toggle('active', voiceEnabled);
        }
        (function() {
            const v = localStorage.getItem('vms-voice');
            if (v === 'off') { voiceEnabled = false; document.getElementById('voiceBtn').textContent = '🔇'; }
            else { document.getElementById('voiceBtn').classList.add('active'); }
        })();

        // --- Theme ---
        function toggleTheme() {
            const html = document.documentElement;
            const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('vms-theme', next);
        }
        (function() {
            const saved = localStorage.getItem('vms-theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</body>
</html>
