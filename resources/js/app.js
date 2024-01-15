import './bootstrap';
import { createApp } from 'vue'
import config from "@/formkit.config.js"
import { plugin, defaultConfig } from '@formkit/vue'
import UsersManager from './components/users/UsersManager.vue'
import TeamsManager from './components/teams/TeamsManager.vue'
import SeasonsManager from './components/seasons/SeasonsManager.vue'
import GroupsManager from './components/groups/GroupsManager.vue'

let app = createApp({
    components: {
        UsersManager,
        TeamsManager,
        SeasonsManager,
        GroupsManager
    }
}).use(config.plugin, config.defaultConfig({
    config: config.configs
})).mount('#app');