// resources/js/bootstrap.js

import axios from 'axios';

window.axios = axios;

// Automatically include CSRF token on requests
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
