import './bootstrap';
import { createApp } from 'vue'
import { plugin, defaultConfig } from '@formkit/vue'
import TeamsManager from './components/teams/TeamsManager.vue'

let app = createApp({
    components: {
        TeamsManager
    }
}).use(plugin, defaultConfig).mount('#app');