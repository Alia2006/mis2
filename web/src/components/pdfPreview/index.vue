<template>
    <el-dialog
        v-model="showDialog"
        :title="title || t('utils.preview')"
        fullscreen
        destroy-on-close
        append-to-body
        class="pdf-preview-dialog"
    >
        <template #header>
            <div class="pdf-preview-header">
                <span class="pdf-preview-title" :title="title">{{ title || t('utils.preview') }}</span>
                <div class="pdf-preview-toolbar" @click.stop>
                    <el-tooltip :content="t('utils.Zoom out')" placement="bottom">
                        <el-button circle size="small" @click="zoomOut" :disabled="!ready || scale <= minScale">
                            <Icon name="el-icon-ZoomOut" />
                        </el-button>
                    </el-tooltip>
                    <span class="pdf-preview-scale">{{ Math.round(scale * 100) }}%</span>
                    <el-tooltip :content="t('utils.Zoom in')" placement="bottom">
                        <el-button circle size="small" @click="zoomIn" :disabled="!ready || scale >= maxScale">
                            <Icon name="el-icon-ZoomIn" />
                        </el-button>
                    </el-tooltip>
                    <el-divider direction="vertical" />
                    <el-tooltip :content="t('utils.Reset zoom')" placement="bottom">
                        <el-button circle size="small" @click="resetZoom" :disabled="!ready || scale === 1">
                            <Icon name="el-icon-RefreshLeft" />
                        </el-button>
                    </el-tooltip>
                    <el-tooltip :content="t('Download')" placement="bottom">
                        <el-button circle size="small" @click="download" :disabled="!ready">
                            <Icon name="el-icon-Download" />
                        </el-button>
                    </el-tooltip>
                    <el-tooltip :content="t('utils.Open in new tab')" placement="bottom">
                        <el-button circle size="small" @click="openInNewTab">
                            <Icon name="el-icon-FullScreen" />
                        </el-button>
                    </el-tooltip>
                </div>
            </div>
        </template>

        <div class="pdf-preview-body">
            <!-- Loading -->
            <div v-if="loading" class="pdf-preview-status">
                <el-icon class="is-loading" :size="40"><Loading /></el-icon>
                <p>{{ t('utils.PDF loading') }}...</p>
            </div>

            <!-- Error -->
            <el-result v-else-if="error" icon="error" :title="t('utils.PDF load failed')">
                <template #extra>
                    <el-button type="primary" @click="openInNewTab">{{ t('utils.Open in new tab') }}</el-button>
                </template>
            </el-result>

            <!-- PDF Viewer (lazy loaded) -->
            <component
                v-show="!error"
                :is="VueOfficePdf"
                v-if="showViewer"
                ref="pdfRef"
                :src="resolvedSrc"
                :static-file-url="staticFileUrl"
                :request-options="requestOptions"
                @rendered="onRendered"
                @error="onError"
                class="pdf-preview-canvas"
            />
        </div>
    </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, shallowRef, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import { Loading } from '@element-plus/icons-vue'
import { fullUrl } from '/@/utils/common'

/**
 * 懒加载 @vue-office/pdf，避免首屏加载 pdf.js 库（~300KB）
 * 仅在第一次打开预览时才请求
 */
const VueOfficePdf = defineAsyncComponent(() => import('@vue-office/pdf'))

interface Props {
    /** v-model 控制弹窗显示 */
    modelValue: boolean
    /** PDF 地址（支持相对路径，自动拼接 fullUrl） */
    url: string
    /** 弹窗标题 */
    title?: string
    /** 下载时使用的文件名 */
    filename?: string
    /** pdf.js 静态资源地址（cmap 等），默认 unpkg CDN */
    staticFileUrl?: string
    /** 请求选项（如 withCredentials） */
    requestOptions?: Record<string, unknown>
}

const props = withDefaults(defineProps<Props>(), {
    title: '',
    filename: '',
    staticFileUrl: 'https://unpkg.com/pdfjs-dist@3.1.81/',
    requestOptions: () => ({}),
})

const emits = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
}>()

const { t } = useI18n()

/** 缩放范围 */
const minScale = 0.5
const maxScale = 3
const scaleStep = 0.25

const showDialog = computed({
    get: () => props.modelValue,
    set: (val) => emits('update:modelValue', val),
})

const resolvedSrc = computed(() => fullUrl(props.url))

/** 组件状态 */
const loading = ref(false)
const error = ref(false)
const ready = ref(false)
const scale = ref(1)
const showViewer = ref(false)

/** @vue-office/pdf 组件实例 */
const pdfRef = shallowRef<any>(null)

/** 切换弹窗时重置状态 */
watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            // 打开时初始化
            loading.value = true
            error.value = false
            ready.value = false
            scale.value = 1
            showViewer.value = true
        } else {
            // 关闭时清理
            showViewer.value = false
            ready.value = false
            scale.value = 1
        }
    }
)

const onRendered = () => {
    loading.value = false
    ready.value = true
}

const onError = (err: unknown) => {
    console.warn('[PdfPreview] render error:', err)
    loading.value = false
    error.value = true
    ready.value = false
}

const applyScale = (newScale: number) => {
    const clamped = Math.max(minScale, Math.min(maxScale, newScale))
    scale.value = clamped
    pdfRef.value?.setScale?.(clamped)
}

const zoomIn = () => applyScale(scale.value + scaleStep)
const zoomOut = () => applyScale(scale.value - scaleStep)
const resetZoom = () => applyScale(1)

const download = () => {
    const name = props.filename || props.url.split('/').pop() || `pdf-${Date.now()}.pdf`
    pdfRef.value?.save?.(name)
}

const openInNewTab = () => {
    window.open(resolvedSrc.value, '_blank')
}
</script>

<style scoped lang="scss">
.pdf-preview-dialog {
    :deep(.el-dialog__body) {
        padding: 0;
        height: calc(100vh - 55px);
        overflow: hidden;
    }
}

.pdf-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding-right: 40px;
}

.pdf-preview-title {
    font-size: 16px;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 40%;
}

.pdf-preview-toolbar {
    display: flex;
    align-items: center;
    gap: 4px;
}

.pdf-preview-scale {
    display: inline-block;
    min-width: 50px;
    text-align: center;
    font-size: 13px;
    color: var(--el-text-color-regular);
    user-select: none;
}

.pdf-preview-body {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.pdf-preview-status {
    position: absolute;
    inset: 0;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    background: var(--el-bg-color);
    color: var(--el-text-color-secondary);
}

.pdf-preview-canvas {
    width: 100%;
    height: 100%;
}
</style>
