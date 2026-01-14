import {createApp} from 'vue'
import './style.css'
import App from './App.vue'
import {createPinia} from "pinia";
import {createPersistedState} from 'pinia-plugin-persistedstate'
import {router} from "./router";

const pinia = createPinia()
pinia.use(createPersistedState({
    key: id => `__GlassAdmin__${id}`,
}))
const app = createApp(App)

app.use(pinia)
app.use(router)

app.mount('#app')
