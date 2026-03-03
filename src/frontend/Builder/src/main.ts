import { mount } from 'svelte';
import App from './App.svelte';

const target = document.getElementById('dg-builder');
if (target) {
	mount(App, { target });
}
