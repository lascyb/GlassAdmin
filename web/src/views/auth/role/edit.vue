<script setup lang="ts">
import {mockRoleRequestFn} from "@/apis/auth/mock.ts";
import {get} from "@/apis/auth/role.ts";
import GaSelectRemote from "@components/select-remote/index.vue";
import {onMounted, reactive, ref} from "vue";
import {EditPen} from "@element-plus/icons-vue";

const props = defineProps({
  id: Number,
  pid: Number,
})

const form = reactive<{
  id?: number
  pid?: number
  name: string
  desc: string
}>({
  id: undefined,
  pid: undefined,
  name: "",
  desc: "",
})
const loading = ref(false);
const title = ref("添加")
onMounted( () => {
  console.log("props", props)
  if (props?.id) {
    title.value = "编辑"
    loading.value = true
    get(props?.id).then(response => {
      Object.assign(form, response)
    }).finally(() => {
      loading.value = false
    })

  } else if (props?.pid) {
    title.value = "添加子角色"
    Object.assign(form, {
      pid: props.pid,
    })
  }
})
</script>

<template>
  <el-dialog draggable :title="title" destroy-on-close lock-scroll top="12vh">
    <el-scrollbar class="pr-5" max-height="60vh">
      <el-skeleton :loading="loading" animated :rows="5"       :throttle="{ leading: 300, trailing: 300, initVal: true }"
      >
        <el-form v-model="form"   element-loading-text="正在获取角色数据..." label-position="right"
                 label-width="80px" class="min-h-[40vh]">
          <el-form-item label="上级角色" prop="pid">
            <ga-select-remote v-model="form.pid"
                              remote-url="/mock"
                              :multiple="false"
                              :request-fn="mockRoleRequestFn"
                              :init-options="{value:20,label:'AAA'}"
                              clearable
                              remote-show-suffix
                              placeholder="选择上级角色,或留空作为顶级角色">
              <template #prefix>
                <el-icon><EditPen></EditPen></el-icon>
              </template>
            </ga-select-remote>
          </el-form-item>
          <el-form-item label="角色名" prop="name">
            <el-input v-model.trim="form.name" placeholder="请输入角色名" clearable show-word-limit
                      maxlength="50"></el-input>
          </el-form-item>
          <el-form-item label="描述" prop="desc">
            <el-input v-model.trim="form.desc" type="textarea" placeholder="请输入角色描述"
                      :autosize="{ minRows: 2, maxRows: 10 }" show-word-limit maxlength="200"></el-input>
          </el-form-item>
        </el-form>
      </el-skeleton>
    </el-scrollbar>
    <template #footer>
      <el-button>取消</el-button>
      <el-button type="primary">保存</el-button>
    </template>
  </el-dialog>
</template>

<style scoped>

</style>