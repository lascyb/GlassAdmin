<script setup lang="ts">
import { router } from "@/router";
import GaIcon from "@components/ga-icon/index.vue";
import { Lock } from "@element-plus/icons-vue";
import type { PropType } from "vue";
import type { Extend, Menu } from "./types.ts";

const props = defineProps({
  menus: {
    type: Array as PropType<Menu[]>,
    default: () => []
  },
  extend: {
    type: Object as PropType<Extend>,
    default: () => ({
      level: 1
    })
  }
})

const click = (index: string, indexPath: string[], active: boolean, menu: Menu) => {
  console.log(index, indexPath, active, menu)
  console.log(router.getRoutes())
}

const isUnauthorized = (menu: Menu) => menu.authorized === false
</script>

<template>
  <template v-for="menu in props.menus">
    <template v-if="menu.type == 'menu' && menu.children && menu.children.length > 0">
      <!-- index 用 id：仅负责展开态，避免 path_full 与父级撞名导致收起子菜单时父级一并收起 -->
      <el-sub-menu :index="String(menu.id)" :key="menu.id">
        <template #title>
          <GaIcon :icon="menu.icon" />
          <span :class="{ 'menu-locked': isUnauthorized(menu) }">{{ menu.name }}</span>
          <el-icon v-if="isUnauthorized(menu)" class="menu-lock-icon"><Lock /></el-icon>
        </template>
        <MenuTree :extend="{ ...props.extend, level: props.extend.level + 1 }" :menus="menu.children"/>
      </el-sub-menu>
    </template>
    <template v-else-if="menu.type == 'view' || menu.type == 'route' || menu.type == 'link' || menu.type == 'iframe'">
      <el-menu-item :index="menu.path_full" :key="menu.id"
                    :class="{ 'is-locked': isUnauthorized(menu) }"
                    @click="(index: string, indexPath: string[], active: boolean)=>click(index, indexPath, active,menu)">
        <GaIcon :icon="menu.icon" />
        <span>{{ menu.name }}</span>
        <el-icon v-if="isUnauthorized(menu)" class="menu-lock-icon"><Lock /></el-icon>
      </el-menu-item>
    </template>
  </template>
</template>

<style scoped>
.menu-locked {
  opacity: 0.75;
}

.menu-lock-icon {
  margin-left: 6px;
  font-size: 14px;
  color: var(--el-text-color-secondary);
}

:deep(.el-menu-item.is-locked) {
  opacity: 0.85;
}
</style>
