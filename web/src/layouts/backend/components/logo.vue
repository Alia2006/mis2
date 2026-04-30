<template>
    <div class="layout-logo">
        <img v-if="config.layout.menuCollapse" class="logo-img" src="~assets/logo.png" alt="logo" />
        <div v-if="!config.layout.menuCollapse" :style="{ color: config.getColorVal('menuActiveColor') }" class="website-name">
            {{ siteConfig.siteName }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { useConfig } from '/@/stores/config'
import { useSiteConfig } from '/@/stores/siteConfig'

const config = useConfig()
const siteConfig = useSiteConfig()
</script>

<style scoped lang="scss">
.layout-logo {
    width: 100%;
    height: 50px;
    display: flex;
    align-items: center;
    box-sizing: border-box;
    padding: 10px;
    background: v-bind('config.layout.layoutMode != "Streamline" ?  config.getColorVal("menuTopBarBackground"):"transparent"');
}
.logo-img {
    width: 28px;
    margin: 0 auto;
}
.website-name {
    display: block;
    padding-left: 12px;
    font-size: var(--el-font-size-extra-large);
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
