
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dummy CSRF Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-2xl w-full">
        <h1 class="text-2xl font-bold text-center mb-6 text-purple-600">🔍 CSRF Token Comparison</h1>
        
        <div class="space-y-4">
            <div class="bg-blue-50 p-4 rounded">
                <h3 class="font-semibold text-blue-800">Current Page Info:</h3>
                <p><strong>CSRF Token:</strong> <span id="current-csrf" class="font-mono text-sm">{{ csrf_token() }}</span></p>
                <p><strong>Session ID:</strong> <span id="current-session" class="font-mono text-sm">{{ session()->getId() }}</span></p>
                <p><strong>URL:</strong> <span id="current-url" class="font-mono text-sm"></span></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded">
                <h3 class="font-semibold text-green-800">Login Page Info (from localStorage):</h3>
                <p><strong>CSRF Token:</strong> <span id="login-csrf" class="font-mono text-sm"></span></p>
                <p><strong>Session ID:</strong> <span id="login-session" class="font-mono text-sm"></span></p>
            </div>

            <!-- NEW: Fetch with Credentials Test -->
            <div class="bg-yellow-50 p-4 rounded">
                <h3 class="font-semibold text-yellow-800">Fetch with Credentials Test:</h3>
                <button onclick="testFetchWithCredentials()" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 mb-2">
                    🧪 Test Fetch with Credentials
                </button>
                <div id="fetch-results" class="text-sm"></div>
            </div>
            
            <div id="comparison" class="p-4 rounded border-2">
                <h3 class="font-semibold">Comparison Result:</h3>
                <div id="csrf-match" class="mt-2"></div>
                <div id="session-match" class="mt-2"></div>
            </div>

            <!-- Cookie Inspector -->
            <div class="bg-purple-50 p-4 rounded">
                <h3 class="font-semibold text-purple-800">Cookie Inspector:</h3>
                <button onclick="inspectCookies()" class="bg-purple-500 text-white px-2 py-1 rounded text-sm mb-2">
                    🍪 Inspect Cookies
                </button>
                <div id="cookie-info" class="text-xs font-mono"></div>
            </div>
        </div>
        
        <div class="mt-6 flex gap-4 justify-center">
            <a href="{{ route('login') }}" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                ← Back to Login
            </a>
            <button onclick="refreshPage()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                🔄 Refresh
            </button>
        </div>
        
        <div class="mt-6 bg-gray-50 p-4 rounded">
            <h3 class="font-semibold text-gray-800 mb-2">Console Logs:</h3>
            <div id="console-logs" class="text-xs text-gray-600 font-mono max-h-32 overflow-y-auto"></div>
        </div>
    </div>

    <script>
        function logToPage(message) {
            const logsDiv = document.getElementById('console-logs');
            const timestamp = new Date().toLocaleTimeString();
            logsDiv.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            logsDiv.scrollTop = logsDiv.scrollHeight;
            console.log(message);
        }

        // NEW: Test fetch with credentials
        async function testFetchWithCredentials() {
            const resultsDiv = document.getElementById('fetch-results');
            resultsDiv.innerHTML = '<div class="text-blue-600">Testing...</div>';
            
            try {
                // Make a fetch request to get CSRF token with credentials
                const response = await fetch('/dummy-csrf', {
                    method: 'GET',
                    credentials: 'include', // This is the key!
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const html = await response.text();
                    // Try to extract CSRF token from response
                    const csrfMatch = html.match(/name="csrf-token" content="([^"]+)"/);
                    const fetchedCsrf = csrfMatch ? csrfMatch[1] : 'Not found';
                    
                    resultsDiv.innerHTML = `
                        <div class="text-green-600">✅ Fetch successful!</div>
                        <div><strong>Fetched CSRF:</strong> ${fetchedCsrf}</div>
                        <div><strong>Match with current:</strong> ${fetchedCsrf === '{{ csrf_token() }}' ? '✅ YES' : '❌ NO'}</div>
                    `;
                    
                    logToPage('🧪 FETCH TEST - Fetched CSRF: ' + fetchedCsrf);
                } else {
                    resultsDiv.innerHTML = `<div class="text-red-600">❌ Fetch failed: ${response.status}</div>`;
                }
            } catch (error) {
                resultsDiv.innerHTML = `<div class="text-red-600">❌ Error: ${error.message}</div>`;
                logToPage('🧪 FETCH ERROR: ' + error.message);
            }
        }

        // NEW: Inspect cookies
        function inspectCookies() {
            const cookieDiv = document.getElementById('cookie-info');
            const cookies = document.cookie.split(';').map(c => c.trim());
            
            let cookieInfo = '<div class="mb-2"><strong>All Cookies:</strong></div>';
            if (cookies.length === 1 && cookies[0] === '') {
                cookieInfo += '<div class="text-red-600">❌ No cookies found!</div>';
            } else {
                cookies.forEach(cookie => {
                    const [name, value] = cookie.split('=');
                    cookieInfo += `<div>${name}: ${value}</div>`;
                });
            }
            
            cookieDiv.innerHTML = cookieInfo;
            logToPage('🍪 COOKIES: ' + document.cookie);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Get current page info
            const currentCsrf = '{{ csrf_token() }}';
            const currentSession = '{{ session()->getId() }}';
            const currentUrl = window.location.href;
            
            // Get login page info from localStorage
            const loginCsrf = localStorage.getItem('login_csrf_token');
            const loginSession = localStorage.getItem('login_session_id');
            
            // Display current page info
            document.getElementById('current-csrf').textContent = currentCsrf;
            document.getElementById('current-session').textContent = currentSession;
            document.getElementById('current-url').textContent = currentUrl;
            
            // Display login page info
            document.getElementById('login-csrf').textContent = loginCsrf || 'Not found';
            document.getElementById('login-session').textContent = loginSession || 'Not found';
            
            // Compare tokens
            const csrfMatch = currentCsrf === loginCsrf;
            const sessionMatch = currentSession === loginSession;
            
            const comparisonDiv = document.getElementById('comparison');
            const csrfMatchDiv = document.getElementById('csrf-match');
            const sessionMatchDiv = document.getElementById('session-match');
            
            // CSRF comparison
            csrfMatchDiv.innerHTML = csrfMatch 
                ? '<span class="text-green-600 font-semibold">✅ CSRF tokens MATCH</span>'
                : '<span class="text-red-600 font-semibold">❌ CSRF tokens DO NOT MATCH</span>';
                
            // Session comparison
            sessionMatchDiv.innerHTML = sessionMatch 
                ? '<span class="text-green-600 font-semibold">✅ Session IDs MATCH</span>'
                : '<span class="text-red-600 font-semibold">❌ Session IDs DO NOT MATCH</span>';
            
            // Set border color based on results
            if (csrfMatch && sessionMatch) {
                comparisonDiv.className += ' border-green-500 bg-green-50';
            } else {
                comparisonDiv.className += ' border-red-500 bg-red-50';
            }
            
            // Log everything
            logToPage('🔍 DUMMY PAGE - Current CSRF: ' + currentCsrf);
            logToPage('🔍 DUMMY PAGE - Current Session: ' + currentSession);
            logToPage('🔍 DUMMY PAGE - Login CSRF: ' + (loginCsrf || 'Not found'));
            logToPage('🔍 DUMMY PAGE - Login Session: ' + (loginSession || 'Not found'));
            logToPage('🔍 DUMMY PAGE - CSRF Match: ' + csrfMatch);
            logToPage('🔍 DUMMY PAGE - Session Match: ' + sessionMatch);
            
            // Auto-inspect cookies on load
            inspectCookies();
        });
        
        function refreshPage() {
            window.location.reload();
        }
    </script>
</body>
</html>