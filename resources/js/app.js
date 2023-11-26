import './bootstrap';
import { createApp } from 'vue'
import config from "@/formkit.config.js"
import { plugin, defaultConfig } from '@formkit/vue'
import UsersManager from './components/users/UsersManager.vue'
import TeamsManager from './components/teams/TeamsManager.vue'
import SeasonsManager from './components/seasons/SeasonsManager.vue'

let app = createApp({
    components: {
        UsersManager,
        TeamsManager,
        SeasonsManager
    }
}).use(config.plugin, config.defaultConfig({
    config: config.configs
})).mount('#app');