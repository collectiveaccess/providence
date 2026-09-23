import htmx from 'htmx.org';
import Alpine from 'alpinejs';
import * as bootstrap from 'bootstrap';

// Make globally accessible to browser
window.htmx = htmx;
window.Alpine = Alpine

// Initialize Alpine
Alpine.start()

// Import our custom CSS
import '/scss/main.scss'

require('bootstrap-icons/font/bootstrap-icons.css');
