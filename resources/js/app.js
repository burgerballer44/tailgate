import './bootstrap';
import { createApp } from 'vue'
import config from "@/formkit.config.js"
import { plugin, defaultConfig } from '@formkit/vue'
import TeamsManager from './components/teams/TeamsManager.vue'

console.log('<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<')
console.log(plugin)
console.log(defaultConfig())
console.log(config.plugin)
console.log(config.config)

let app = createApp({
    components: {
        TeamsManager
    }
}).use(config.plugin, config.defaultConfig({
    config: config.configs
})).mount('#app');