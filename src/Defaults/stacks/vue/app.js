import { createApp } from 'vue/dist/vue.esm-bundler.js';
import App from './components/App.js';

const mount = document.getElementById('app');
if (mount) {
    createApp(App).mount(mount);
}
