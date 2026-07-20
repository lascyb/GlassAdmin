<script setup lang="ts">
import { ApiError, postData } from '@/apis/http'
import feishuIcon from '@/assets/images/feishu.png'
import { useAdmin } from '@/pinia/admin'
import { Lock, Message, Phone, User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const admin = useAdmin()

// 登录方式：password | sms
const loginType = ref<'password' | 'sms'>('password')
const logining = ref<boolean>(false)
const feishuLogining = ref(false)

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

type LoginResult = {
  access_token: string
  refresh_token: string
  expire_at: number
  refresh_expire_at: number
  user: { id: number; username: string; nickname: string; avatar?: string | null; role_ids?: number[] }
}

/** applyLoginResult 写入登录态并跳转 */
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
  await router.push(admin.getReferer())
}

// 获取验证码
const getVerificationCode = () => {
  if (!smsForm.phone) {
    ElMessage.warning('请输入手机号')
    return
  }

  const phoneRegex = /^1[3-9]\d{9}$/
  if (!phoneRegex.test(smsForm.phone)) {
    ElMessage.warning('请输入正确的手机号')
    return
  }

  ElMessage.success('验证码已发送')

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
    const data = await postData<LoginResult>(
      'account.login.Password/login',
      {
        username: passwordForm.username,
        password: passwordForm.password,
        remember: passwordForm.remember,
      },
      { encryptFields: ['username', 'password'] },
    )

    await applyLoginResult(data, passwordForm.remember)
  } catch (e) {
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

  ElMessage.success('登录成功')
  await router.push('/')
}

/** handleFeishuLogin 跳转后端飞书授权入口 */
const handleFeishuLogin = () => {
  const base = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
  if (!base) {
    ElMessage.error('未配置 VITE_API_BASE_URL')
    return
  }
  feishuLogining.value = true
  location.href = `${base}/account.login.FeishuOauth2/authorize`
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
    const data = await postData<LoginResult>('account.login.FeishuOauth2/login', {
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
  void handleFeishuCallback()
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
        <h1 class="logo-title">GlassAdmin</h1>
        <p class="logo-subtitle">透明可视的后台框架</p>
      </div>

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
              </div>
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
                  class="code-button"
                  @click="getVerificationCode"
                >
                  {{ codeButtonText }}
                </el-button>
              </div>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" size="large" class="login-button" @click="handleSmsLogin">
                登录
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>

      <div class="third-party-section">
        <div class="divider">
          <span class="divider-text">其他登录方式</span>
        </div>
        <button
          type="button"
          class="feishu-login-btn"
          :disabled="feishuLogining"
          title="飞书登录"
          @click="handleFeishuLogin"
        >
          <img :src="feishuIcon" alt="" class="feishu-login-btn__icon" />
          <span class="feishu-login-btn__text">{{ feishuLogining ? '正在跳转…' : '飞书登录' }}</span>
        </button>
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
  margin-top: 28px;
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

.feishu-login-btn__icon {
  width: 22px;
  height: 22px;
  object-fit: contain;
  flex-shrink: 0;
}

.feishu-login-btn__text {
  line-height: 1;
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
