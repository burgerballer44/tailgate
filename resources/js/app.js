import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import { createApp } from 'vue'
import Teams from './components/Teams.vue'

const app = createApp({
    components: {
        Teams,
    }
});

app.mount("#app");