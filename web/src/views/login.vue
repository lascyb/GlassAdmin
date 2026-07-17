<script setup lang="ts">
import { useAdmin } from '@/pinia/admin'
import { useSecurityRsa } from '@/pinia/security/rsa'
import { ApiError, createRsaRebuild, postData } from '@/apis/http'
import { Lock, Message, Phone, User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const securityRsa = useSecurityRsa()
const admin = useAdmin()

// 登录方式：password | sms
const loginType = ref<'password' | 'sms'>('password')
const logining = ref<boolean>(false)
// 密码登录表单
const passwordForm = reactive({
  username: '',
  password: '',
  remember: false
})

// 验证码登录表单
const smsForm = reactive({
  phone: '',
  code: ''
})

// 验证码倒计时
const countdown = ref(0)
const codeButtonText = ref('获取验证码')

// 获取验证码
const getVerificationCode = () => {
  if (!smsForm.phone) {
    ElMessage.warning('请输入手机号')
    return
  }

  // 验证手机号格式
  const phoneRegex = /^1[3-9]\d{9}$/
  if (!phoneRegex.test(smsForm.phone)) {
    ElMessage.warning('请输入正确的手机号')
    return
  }

  // 这里应该调用API发送验证码
  ElMessage.success('验证码已发送')

  // 开始倒计时
  countdown.value = 60
  const timer = setInterval(() => {
    countdown.value--
    codeButtonText.value = `${countdown.value}秒后重试`

    if (countdown.value <= 0) {
      clearInterval(timer)
      codeButtonText.value = '获取验证码'
    }
  }, 1000)
}

// 密码登录
const handlePasswordLogin = async () => {
  if (!passwordForm.username || !passwordForm.password) {
    ElMessage.warning('请输入用户名和密码')
    return
  }
  logining.value = true
  try {
    const buildLoginPayload = async () => {
      const { serial: rsaSerial, texts: [encryptedUsername, encryptedPassword] } =
        await securityRsa.encrypt(passwordForm.username, passwordForm.password)
      if (!rsaSerial || !encryptedUsername || !encryptedPassword) {
        return null
      }
      return {
        params: {
          username: encryptedUsername,
          password: encryptedPassword,
          remember: passwordForm.remember,
        },
        headers: {
          'X-RSA-Serial': rsaSerial,
        },
      }
    }

    const payload = await buildLoginPayload()
    if (!payload) {
      ElMessage.error('获取登录公钥失败，请稍后重试')
      return
    }

    const data = await postData<{
      access_token: string
      refresh_token: string
      expire_at: number
      refresh_expire_at: number
      user: { id: number; username: string; nickname: string; avatar?: string | null; role_ids?: number[] }
    }>('Login/password', payload.params, {
      headers: payload.headers,
      rsaRebuild: createRsaRebuild(buildLoginPayload, securityRsa.forceRefreshPublicKey),
    })

    admin.init(
      data.access_token,
      data.refresh_token,
      data.user,
      data.expire_at,
      data.refresh_expire_at,
      passwordForm.remember,
    )

    ElMessage.success('登录成功')
    await router.push(admin.getReferer())
  } catch(e) {
    ElMessage.error(e instanceof ApiError ? e.message : '登录请求失败' + e)
  } finally {
    logining.value = false
  }
}

// 验证码登录
const handleSmsLogin = async () => {
  if (!smsForm.phone || !smsForm.code) {
    ElMessage.warning('请输入手机号和验证码')
    return
  }
  
  // 这里应该调用登录API
  ElMessage.success('登录成功')
  await router.push('/')
}

// 第三方登录
const handleThirdPartyLogin = (type: 'wechat' | 'qq' | 'github') => {
  // 这里应该调用第三方登录API
  ElMessage.info(`正在使用${type === 'wechat' ? '微信' : type === 'qq' ? 'QQ' : 'GitHub'}登录`)
}
</script>

<template>
  <div class="login-container">
    <div class="login-box">
      <!-- Logo 区域 -->
      <div class="logo-section">
        <div class="logo-icon">
          <el-icon :size="48" color="#409EFF">
            <User />
          </el-icon>
        </div>
        <h1 class="logo-title">GlassAdmin</h1>
        <p class="logo-subtitle">透明可视的后台框架</p>
      </div>

      <!-- 登录方式切换 -->
      <el-tabs v-model="loginType" class="login-tabs">
        <el-tab-pane label="密码登录" name="password">
          <el-form :model="passwordForm" class="login-form">
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
              <div class="form-options">
                <el-checkbox v-model="passwordForm.remember">记住我</el-checkbox>
                <el-link type="primary">忘记密码？</el-link>
              </div>
            </el-form-item>
            <el-form-item>
              <el-button
                type="primary"
                size="large"
                class="login-button"
                @click="handlePasswordLogin"
                :loading="logining"
              >
                登录
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="验证码登录" name="sms">
          <el-form :model="smsForm" class="login-form">
            <el-form-item>
              <el-input
                v-model="smsForm.phone"
                placeholder="请输入手机号"
                size="large"
                :prefix-icon="Phone"
                clearable
                maxlength="11"
              />
            </el-form-item>
            <el-form-item>
              <div class="code-input-group">
                <el-input
                  v-model="smsForm.code"
                  placeholder="请输入验证码"
                  size="large"
                  :prefix-icon="Message"
                  clearable
                  maxlength="6"
                  @keyup.enter="handleSmsLogin"
                />
                <el-button
                  :disabled="countdown > 0"
                  size="large"
                  @click="getVerificationCode"
                  class="code-button"
                >
                  {{ codeButtonText }}
                </el-button>
              </div>
            </el-form-item>
            <el-form-item>
              <el-button
                type="primary"
                size="large"
                class="login-button"
                @click="handleSmsLogin"
              >
                登录
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>

      <!-- 第三方登录 -->
      <div class="third-party-section">
        <div class="divider">
          <span class="divider-text">其他登录方式</span>
        </div>
        <div class="third-party-buttons">
          <el-button
            circle
            class="third-party-btn wechat"
            @click="handleThirdPartyLogin('wechat')"
          >
            <svg viewBox="0 0 24 24" class="third-party-icon">
              <path fill="currentColor" d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.496-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm6.673 3.036c-1.693.104-3.8.675-5.465 2.008-1.92 1.595-2.884 4.097-2.17 6.642.324 1.15 1.014 2.16 1.947 2.9a.495.495 0 0 1 .118.643l-.297 1.104a.515.515 0 0 0-.04.157c0 .12.1.22.223.22a.24.24 0 0 0 .125-.04l1.417-.83a.642.642 0 0 1 .533-.073c.92.24 1.888.365 2.873.365 4.8 0 8.691-3.288 8.691-7.342 0-3.336-2.807-6.11-6.467-7.116zm-3.04 4.318c.52 0 .94.43.94.96a.95.95 0 0 1-.94.96.95.95 0 0 1-.94-.96c0-.53.42-.96.94-.96zm3.567 0c.52 0 .94.43.94.96a.95.95 0 0 1-.94.96.95.95 0 0 1-.94-.96c0-.53.42-.96.94-.96z"/>
            </svg>
          </el-button>
          <el-button
            circle
            class="third-party-btn qq"
            @click="handleThirdPartyLogin('qq')"
          >
            <svg viewBox="0 0 24 24" class="third-party-icon">
              <path fill="currentColor" d="M12 2C6.477 2 2 6.477 2 12c0 1.804.473 3.498 1.3 4.968L2 22l5.315-1.256C8.563 21.688 10.22 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18c-1.214 0-2.38-.22-3.47-.616l-.24-.06-2.53.6.6-2.48-.066-.24C5.45 15.9 5.2 14.5 5.2 13c0-3.75 3.05-6.8 6.8-6.8s6.8 3.05 6.8 6.8-3.05 6.8-6.8 6.8z"/>
            </svg>
          </el-button>
          <el-button
            circle
            class="third-party-btn github"
            @click="handleThirdPartyLogin('github')"
          >
            <svg viewBox="0 0 24 24" class="third-party-icon">
              <path fill="currentColor" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z"/>
            </svg>
          </el-button>
        </div>
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

.login-tabs {
  margin-bottom: 24px;
}

.login-tabs :deep(.el-tabs__header) {
  margin-bottom: 24px;
}

.login-tabs :deep(.el-tabs__item) {
  font-size: 16px;
  font-weight: 500;
}

.login-form {
  margin-top: 20px;
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

.code-input-group {
  display: flex;
  gap: 12px;
  width: 100%;
}

.code-input-group :deep(.el-input) {
  flex: 1;
}

.code-button {
  white-space: nowrap;
  min-width: 120px;
}

.third-party-section {
  margin-top: 32px;
}

.divider {
  position: relative;
  text-align: center;
  margin: 24px 0;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #e4e7ed;
}

.divider-text {
  position: relative;
  background: white;
  padding: 0 16px;
  color: #909399;
  font-size: 14px;
}

.third-party-buttons {
  display: flex;
  justify-content: center;
  gap: 16px;
}

.third-party-btn {
  width: 48px;
  height: 48px;
  padding: 0;
  border: 1px solid #e4e7ed;
  background: white;
  transition: all 0.3s;
}

.third-party-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.third-party-btn.wechat:hover {
  border-color: #07c160;
  color: #07c160;
}

.third-party-btn.qq:hover {
  border-color: #12b7f5;
  color: #12b7f5;
}

.third-party-btn.github:hover {
  border-color: #24292e;
  color: #24292e;
}

.third-party-icon {
  width: 24px;
  height: 24px;
}

/* 响应式设计 */
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