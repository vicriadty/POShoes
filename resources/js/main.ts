import { mount } from 'svelte';
import App from './App.svelte';
import { registerSW } from 'virtual:pwa-register';
import '../css/app.css';

const app = mount(App, {
    target: document.getElementById('app')!,
});

registerSW({ immediate: true });

export default app;