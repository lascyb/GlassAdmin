import { vAuth } from '@/directives/auth'
import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { createPinia } from 'pinia'
import { createPersistedState } from 'pinia-plugin-persistedstate'
import { router } from './router'
import 'element-plus/dist/index.css'

const pinia = createPinia()
pinia.use(
    createPersistedState({
        key: (id) => `${import.meta.env.VITE_PERSIST_STORAGE_PREFIX}${id}`,
    }),
)
const app = createApp(App)

app.use(pinia)
app.use(router)
app.directive('auth', vAuth)

app.mount('#app')
