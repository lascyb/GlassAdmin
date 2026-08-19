<script setup lang="ts">
import { ApiError, postData } from '@/apis/http'
import * as systemConfigApi from '@/apis/system/config'
import GaIcon from '@/components/ga-icon/index.vue'
import { type Admin, useAdmin } from '@/pinia/admin'
import { Lock, User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const admin = useAdmin()

const logining = ref<boolean>(false)
const feishuLogining = ref(false)
const siteTitle = ref('')
const siteDescription = ref('')

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
    const data = await postData<LoginResult>(
      'admin/account.login.Password/login',
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
    ElMessage.error(e instanceof ApiError ? e.message : '登录请求失败' + e)
  } finally {
    logining.value = false
  }
}

/** handleFeishuLogin 跳转后端飞书授权入口 */
const handleFeishuLogin = () => {
  admin.setLoginMethod('feishu')
  const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
  feishuLogining.value = true
  location.href = `${base}/admin/account.login.oauth2.Feishu/authorize`
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
    const data = await postData<LoginResult>('admin/account.login.oauth2.Feishu/login', {
      code,
      remember: false,
    })
    await applyLoginResult(data, false)
  } catch (e) {
    ElMessage.error(e instanceof ApiError ? e.message : '飞书登录失败')
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
        document.title = `${title} - 登录`
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
        <div class="logo-icon">
          <el-icon :size="48" color="#409EFF">
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-box {
  width: 100%;
  max-width: 500px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  padding: 40px;
  animation: slideUp 0.5s ease-out;
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
  margin-bottom: 32px;
}

.logo-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  margin-bottom: 16px;
}

.logo-title {
  font-size: 28px;
  font-weight: 700;
  color: #303133;
  margin: 0 0 8px 0;
}

.logo-subtitle {
  font-size: 14px;
  color: #909399;
  margin: 0;
}

.login-form {
  margin-top: 8px;
}

.login-form :deep(.el-form-item) {
  margin-bottom: 20px;
}

.login-form :deep(.el-input__wrapper) {
  border-radius: 8px;
}

.login-button {
  width: 100%;
  height: 44px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
  margin-top: 8px;
}

.form-options {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.third-party-section {
  margin-top: 8px;
}

.divider {
  position: relative;
  text-align: center;
  margin: 0 0 20px;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #ebeef5;
}

.divider-text {
  position: relative;
  background: white;
  padding: 0 14px;
  color: #a8abb2;
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
  padding: 0 16px;
  border: 1px solid #dcdfe6;
  border-radius: 8px;
  background: #fff;
  color: #303133;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.feishu-login-btn:hover:not(:disabled) {
  border-color: #3370ff;
  background: #f5f8ff;
  box-shadow: 0 2px 8px rgba(51, 112, 255, 0.12);
}

.feishu-login-btn:active:not(:disabled) {
  background: #eef3ff;
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
  border-radius: 8px;
}

@media (max-width: 480px) {
  .login-box {
    padding: 24px;
    border-radius: 12px;
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
