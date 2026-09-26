// resources/js/bootstrap.js
import axios from 'axios';
import 'laravel-vite-plugin/inertia-helpers';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';