<script setup lang="ts">
import { post, showError } from '@/apis/request'
import * as systemConfigApi from '@/apis/system/config'
import GaIcon from '@/components/ga-icon/index.vue'
import { type Admin, useAdmin } from '@/pinia/admin'
import { markTourPending } from '@/composables/useOnboardingTour'
import { applyDocumentTitle } from '@/router/documentTitle'
import { Lock, User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const router = useRouter()
const route = useRoute()
const admin = useAdmin()

const logining = ref<boolean>(false)
const feishuLogining = ref(false)
const siteTitle = ref('')
const siteDescription = ref('')
const faviconOk = ref(true)

// 密码登录表单
const passwordForm = reactive({
  username: '',
  password: '',
  remember: false
})

type LoginResult = {
  access_token: string
  refresh_token: string
  expire_at: number
  refresh_expire_at: number
  show_tour?: boolean
  user: Admin
}

/** openPasswordLogin 切换为密码登录 */
const openPasswordLogin = () => {
  admin.setLoginMethod('password')
}

/** applyLoginResult 写入登录态并跳转（默认工作台首页） */
const applyLoginResult = async (data: LoginResult, remember: boolean) => {
  admin.init(
    data.access_token,
    data.refresh_token,
    data.user,
    data.expire_at,
    data.refresh_expire_at,
    remember,
  )
  if (data.show_tour) {
    markTourPending(data.user.id)
  }
  ElMessage.success('登录成功')
  const target = admin.getReferer('/')
  if (target === '/' || target === '') {
    await router.push({ name: 'root-default' })
  } else {
    await router.push(target)
  }
}

// 密码登录
const handlePasswordLogin = async () => {
  if (!passwordForm.username || !passwordForm.password) {
    ElMessage.warning('请输入用户名和密码')
    return
  }
  logining.value = true
  try {
    const data = await post<LoginResult>(
      'admin/system.account.login.Password/login',
      {
        username: passwordForm.username,
        password: passwordForm.password,
        remember: passwordForm.remember,
      },
      { encryptFields: ['username', 'password'] },
    )
    admin.setLoginMethod('password')
    await applyLoginResult(data, passwordForm.remember)
  } catch (e) {
    showError(e, '登录请求失败')
  } finally {
    logining.value = false
  }
}

/** handleFeishuLogin 跳转后端飞书授权入口 */
const handleFeishuLogin = () => {
  admin.setLoginMethod('feishu')
  const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
  feishuLogining.value = true
  location.href = `${base}/admin/system.account.login.oauth2.Feishu/authorize`
}

/** handleFeishuCallback 处理飞书授权回调（login_from=feishu-web&code=） */
const handleFeishuCallback = async () => {
  const params = new URLSearchParams(window.location.search)
  const loginFrom = params.get('login_from')
  if (loginFrom !== 'feishu-web') {
    return
  }

  const error = params.get('error')
  if (error === 'access_denied') {
    ElMessage.warning('已取消飞书登录授权')
    await router.replace({ path: '/login' })
    return
  }

  const code = params.get('code')
  if (!code) {
    ElMessage.error('飞书授权码缺失')
    await router.replace({ path: '/login' })
    return
  }

  feishuLogining.value = true
  try {
    const data = await post<LoginResult>('admin/system.account.login.oauth2.Feishu/login', {
      code,
      remember: false,
    })
    await applyLoginResult(data, false)
  } catch (e) {
    showError(e, '飞书登录失败')
    await router.replace({ path: '/login' })
  } finally {
    feishuLogining.value = false
  }
}

onMounted(() => {
  void (async () => {
    try {
      const data = await systemConfigApi.fetchPublic()
      const title = String(data.site_title ?? '').trim()
      if (title) {
        siteTitle.value = title
        applyDocumentTitle(route, title)
      }
      const description = String(data.site_description ?? '').trim()
      if (description) {
        siteDescription.value = description
      }
    } catch {
      // 保留默认 GlassAdmin
    }
    await handleFeishuCallback()
  })()
})
</script>

<template>
  <div class="login-container" v-loading="feishuLogining" element-loading-text="正在进行飞书授权登录...">
    <div class="login-box">
      <div class="logo-section">
        <div class="logo-icon" :class="{ 'logo-icon--favicon': faviconOk }">
          <img
            v-if="faviconOk"
            class="logo-favicon"
            src="/favicon.ico"
            :alt="siteTitle"
            @error="faviconOk = false"
          />
          <el-icon v-else :size="48" color="var(--ga-color-on-primary)">
            <User />
          </el-icon>
        </div>
        <h1 class="logo-title">{{ siteTitle }}</h1>
        <p v-if="siteDescription" class="logo-subtitle">{{ siteDescription }}</p>
      </div>

      <el-form v-if="admin.loginMethod !== 'feishu'" :model="passwordForm" class="login-form">
        <el-form-item>
          <el-input
            v-model="passwordForm.username"
            placeholder="请输入用户名"
            size="large"
            :prefix-icon="User"
            clearable
          />
        </el-form-item>
        <el-form-item>
          <el-input
            v-model="passwordForm.password"
            type="password"
            placeholder="请输入密码"
            size="large"
            :prefix-icon="Lock"
            show-password
            clearable
            @keyup.enter="handlePasswordLogin"
          />
        </el-form-item>
        <el-form-item>
          <el-button
            type="primary"
            size="large"
            class="login-button"
            :loading="logining"
            @click="handlePasswordLogin"
          >
            登录
          </el-button>
        </el-form-item>
        <el-form-item>
          <div class="form-options">
            <el-checkbox v-model="passwordForm.remember">记住我</el-checkbox>
          </div>
        </el-form-item>
      </el-form>

      <div v-else class="third-party-section">
        <button
          type="button"
          class="feishu-login-btn feishu-login-btn--primary"
          :disabled="feishuLogining"
          title="飞书登录"
          @click="handleFeishuLogin"
        >
          <GaIcon icon="local:feishu" :url-size="22" class="shrink-0" />
          <span class="feishu-login-btn__text">{{ feishuLogining ? '正在跳转…' : '飞书登录' }}</span>
        </button>
      </div>

      <div class="third-party-section">
        <div class="divider">
          <span class="divider-text">其他登录方式</span>
        </div>
        <template v-if="admin.loginMethod !== 'feishu'">
          <button
            type="button"
            class="feishu-login-btn"
            :disabled="feishuLogining"
            title="飞书登录"
            @click="handleFeishuLogin"
          >
            <GaIcon icon="local:feishu" :url-size="22" class="shrink-0" />
            <span class="feishu-login-btn__text">{{ feishuLogining ? '正在跳转…' : '飞书登录' }}</span>
          </button>
        </template>
        <template v-else>
          <el-button size="large" class="password-switch-btn" @click="openPasswordLogin">
            使用密码登录
          </el-button>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(
      1200px 600px at 50% -10%,
      color-mix(in srgb, var(--ga-color-primary) 22%, var(--ga-color-bg-page)),
      var(--ga-color-bg-page)
    );
  padding: var(--ga-space-5);
}

.login-box {
  width: 100%;
  max-width: 500px;
  background: var(--ga-color-bg);
  border: 1px solid var(--ga-color-border-lighter);
  border-radius: var(--ga-radius-xl);
  box-shadow: 0 20px 60px color-mix(in srgb, var(--ga-color-text) 16%, transparent);
  padding: var(--ga-space-10);
  animation: slideUp var(--ga-duration-slow) var(--ga-ease-out);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.logo-section {
  text-align: center;
  margin-bottom: var(--ga-space-8);
}

.logo-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  background: var(--ga-color-primary);
  border-radius: var(--ga-radius-lg);
  margin-bottom: var(--ga-space-4);
  color: var(--ga-color-on-primary);
  overflow: hidden;
}

.logo-favicon {
  width: 56px;
  height: 56px;
  object-fit: contain;
}

.logo-icon--favicon {
  background: var(--el-bg-color);
}

.logo-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--ga-color-text);
  margin: 0 0 var(--ga-space-2) 0;
}

.logo-subtitle {
  font-size: 14px;
  color: var(--ga-color-text-secondary);
  margin: 0;
}

.login-form {
  margin-top: var(--ga-space-2);
}

.login-form :deep(.el-form-item) {
  margin-bottom: var(--ga-space-5);
}

.login-form :deep(.el-input__wrapper) {
  border-radius: var(--ga-radius-md);
}

.login-button {
  width: 100%;
  height: 44px;
  border-radius: var(--ga-radius-md);
  font-size: 16px;
  font-weight: 500;
  margin-top: var(--ga-space-2);
}

.form-options {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.third-party-section {
  margin-top: var(--ga-space-2);
}

.divider {
  position: relative;
  text-align: center;
  margin: 0 0 var(--ga-space-5);
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--ga-color-border-lighter);
}

.divider-text {
  position: relative;
  background: var(--ga-color-bg);
  padding: 0 14px;
  color: var(--ga-color-text-placeholder);
  font-size: 13px;
}

.feishu-login-btn {
  width: 100%;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin: 0;
  padding: 0 var(--ga-space-4);
  border: 1px solid var(--ga-color-border);
  border-radius: var(--ga-radius-md);
  background: var(--ga-color-bg);
  color: var(--ga-color-text);
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition:
    border-color var(--ga-duration) var(--ga-ease),
    background-color var(--ga-duration) var(--ga-ease),
    box-shadow var(--ga-duration) var(--ga-ease);
}

.feishu-login-btn:hover:not(:disabled) {
  border-color: var(--ga-color-primary);
  background: color-mix(in srgb, var(--ga-color-primary) 8%, var(--ga-color-bg));
  box-shadow: 0 2px 8px color-mix(in srgb, var(--ga-color-primary) 16%, transparent);
}

.feishu-login-btn:active:not(:disabled) {
  background: color-mix(in srgb, var(--ga-color-primary) 12%, var(--ga-color-bg));
}

.feishu-login-btn:disabled {
  opacity: 0.7;
  cursor: wait;
}

.feishu-login-btn__text {
  line-height: 1;
}

.feishu-login-btn--primary {
  height: 52px;
  font-size: 16px;
}

.password-switch-btn {
  width: 100%;
  height: 44px;
  border-radius: var(--ga-radius-md);
}

@media (max-width: 480px) {
  .login-box {
    padding: var(--ga-space-6);
    border-radius: var(--ga-radius-lg);
  }

  .logo-icon {
    width: 64px;
    height: 64px;
  }

  .logo-title {
    font-size: 24px;
  }
}
</style>
