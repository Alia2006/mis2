<template>
    <el-aside v-if="!navTabs.state.tabFullScreen" :class="['layout-aside-' + config.layout.layoutMode, config.layout.shrink ? 'shrink' : '']">
        <Logo v-if="config.layout.menuShowTopBar" />

        <MenuVerticalChildren v-if="config.layout.layoutMode == 'Double'" />
        <MenuVertical v-else />

        <div class="aside-footer-toolbar" :class="config.layout.menuCollapse ? 'collapse' : ''">
            <Icon
                @click="onMenuCollapse"
                :name="config.layout.menuCollapse ? 'fa fa-indent' : 'fa fa-dedent'"
                :class="config.layout.menuCollapse ? 'unfold' : ''"
                :color="config.getColorVal('menuColor')"
                size="14"
                class="footer-toolbar-item"
            />
            <Icon @click="onMenuSearch" name="fa fa-search" :color="config.getColorVal('menuColor')" size="16" class="footer-toolbar-item" />
        </div>

        <MenuSearchDialog v-model="menuSearchDialogVisible" />
    </el-aside>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import Logo from '/@/layouts/backend/components/logo.vue'
import MenuVertical from '/@/layouts/backend/components/menus/menuVertical.vue'
import MenuVerticalChildren from '/@/layouts/backend/components/menus/menuVerticalChildren.vue'
import MenuSearchDialog from '/@/layouts/backend/components/menuSearch/dialog.vue'
import { useConfig } from '/@/stores/config'
import { BEFORE_RESIZE_LAYOUT } from '/@/stores/constant/cacheKey'
import { SYSTEM_ZINDEX } from '/@/stores/constant/common'
import { useNavTabs } from '/@/stores/navTabs'
import { setNavTabsWidth } from '/@/utils/layout'
import { closeShade } from '/@/utils/pageShade'
import { Session } from '/@/utils/storage'

defineOptions({
    name: 'layout/aside',
})

const config = useConfig()
const navTabs = useNavTabs()
const menuWidth = computed(() => config.menuWidth())
const menuSearchDialogVisible = ref(false)

const onMenuSearch = function () {
    menuSearchDialogVisible.value = true
}

const onMenuCollapse = function () {
    if (config.layout.shrink && !config.layout.menuCollapse) {
        closeShade()
    }

    config.setLayout('menuCollapse', !config.layout.menuCollapse)

    Session.set(BEFORE_RESIZE_LAYOUT, {
        layoutMode: config.layout.layoutMode,
        menuCollapse: config.layout.menuCollapse,
    })

    // 等待侧边栏动画结束后重新计算导航栏宽度
    setTimeout(() => {
        setNavTabsWidth()
    }, 350)
}
</script>

<style scoped lang="scss">
.aside-footer-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 50px;
    padding: 0 20px;
    .footer-toolbar-item {
        padding: 10px;
        border-radius: 50%;
        cursor: pointer;
        &:hover {
            color: var(--el-menu-active-color) !important;
            background-color: var(--el-menu-hover-bg-color);
        }
    }
}
.aside-footer-toolbar.collapse {
    flex-direction: column-reverse;
    padding: 10px 0;
    height: 100px;
}
.layout-aside-Default:not(.shrink) {
    background: var(--ba-bg-color-overlay);
    margin: 16px 0 16px 16px;
    height: calc(100% - 32px);
    box-shadow: var(--el-box-shadow-light);
    border-radius: var(--el-border-radius-base);
    overflow: hidden;
    transition: width 0.3s ease;
    width: v-bind(menuWidth);
}
.layout-aside-Default.shrink,
.layout-aside-Classic,
.layout-aside-Double {
    background: var(--ba-bg-color-overlay);
    margin: 0;
    height: 100%;
    overflow: hidden;
    transition: width 0.3s ease;
    width: v-bind(menuWidth);
}
.shrink {
    position: fixed;
    top: 0;
    left: 0;
    z-index: v-bind('SYSTEM_ZINDEX');
}
</style>
