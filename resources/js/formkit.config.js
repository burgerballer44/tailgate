import {generateClasses} from '@formkit/themes'
import TailgateTailwindTheme from './formkit-tailwind-theme.js'
import {plugin, defaultConfig} from '@formkit/vue';
import "@formkit/themes/genesis";

export default {
    theme: "TailgateTailwindTheme",
    config: {
        classes: generateClasses(TailgateTailwindTheme),
    },
    plugin,
    defaultConfig
}
