import { defineStore } from 'pinia'
import { reactive } from 'vue'
import { STORE_CONFIG } from '/@/stores/constant/cacheKey'
import type { Crud, Lang, Layout } from '/@/stores/interface'
import { useNavTabs } from '/@/stores/navTabs'

export const useConfig = defineStore(
    'config',
    () => {
        const layout: Layout = reactive({
            // 全局
            showDrawer: false,
            shrink: false,
            layoutMode: 'Default',
            mainAnimation: 'slide-right',
            isDark: false,

            // 侧边栏
            menuBackground: ['#ffffff', '#1d1e1f'],
            menuColor: ['#303133', '#CFD3DC'],
            menuActiveBackground: ['#ffffff', '#1d1e1f'],
            menuActiveColor: ['#409eff', '#3375b9'],
            menuHoverBackground: ['#ecf5ff', '#18222c'],
            menuWidth: 260,
            menuWidthLeftSplit: 180,
            menuDefaultIcon: 'fa fa-circle-o',
            menuCollapse: false,
            menuUniqueOpened: false,
            menuShowTopBar: true,
            menuTopBarBackground: ['#fcfcfc', '#1d1e1f'],
            menuTopBarColor: ['#409eff', '#3375b9'],
            menuTopBarCenter: false,
            menuTopBarLogo: false,
            menuToolBarAutoHide: false,
            menuToolBarColor: ['#303133', '#CFD3DC'],

            // 顶栏
            headerBarTabColor: ['#000000', '#CFD3DC'],
            headerBarTabActiveBackground: ['#ffffff', '#1d1e1f'],
            headerBarTabActiveColor: ['#000000', '#409EFF'],
            headerBarBackground: ['#ffffff', '#1d1e1f'],
            headerBarHoverBackground: ['#f5f5f5', '#18222c'],

            // tour
            // 布局漫游式引导
            layoutTour: false,
            layoutTourUnfinished: true,
        })

        const lang: Lang = reactive({
            defaultLang: 'zh-cn',
            fallbackLang: 'zh-cn',
            langArray: [
                { name: 'zh-cn', value: '中文简体' },
                { name: 'en', value: 'English' },
            ],
        })

        const crud: Crud = reactive({
            syncType: 'manual',
            syncedUpdate: 'yes',
            syncAutoPublic: 'no',
        })

        function menuWidth() {
            // 菜单折叠时基本宽度
            const menuCollapseBaseWidth = 64

            // 左分布局特有
            if (layout.layoutMode == 'LeftSplit') {
                const navTabs = useNavTabs()

                // 本布局带来的额外菜单宽度，主菜单宽度 80 + 次级菜单的左右内边距 16
                const modeMenuWidth = 96
                // 最终菜单宽度
                let leftSplitMenuWidth = layout.menuCollapse
                    ? menuCollapseBaseWidth + modeMenuWidth
                    : parseInt(layout.menuWidthLeftSplit.toString()) + modeMenuWidth

                // 无次级菜单，固定宽度
                if (!navTabs.state.childrenMenus.length) {
                    leftSplitMenuWidth = 80
                }

                // 小屏模式
                if (layout.shrink) {
                    return layout.menuCollapse ? 0 : `${leftSplitMenuWidth}px`
                }

                return `${leftSplitMenuWidth}px`
            }

            // 小屏模式
            if (layout.shrink) {
                return layout.menuCollapse ? 0 : `${layout.menuWidth}px`
            }

            // 菜单是否折叠
            return layout.menuCollapse ? `${menuCollapseBaseWidth}px` : `${layout.menuWidth}px`
        }

        function setLang(val: string) {
            lang.defaultLang = val
        }

        function onSetLayoutColor(data = layout.layoutMode) {
            // 切换布局时，如果是为默认配色方案，对菜单激活背景色重新赋值
            const tempValue = layout.isDark ? { idx: 1, color: '#1d1e1f', newColor: '#141414' } : { idx: 0, color: '#ffffff', newColor: '#f5f5f5' }
            if (
                data == 'Classic' &&
                layout.headerBarBackground[tempValue.idx] == tempValue.color &&
                layout.headerBarTabActiveBackground[tempValue.idx] == tempValue.color
            ) {
                layout.headerBarTabActiveBackground[tempValue.idx] = tempValue.newColor
            } else if (
                ['Default', 'LeftSplit'].includes(data) &&
                layout.headerBarBackground[tempValue.idx] == tempValue.color &&
                layout.headerBarTabActiveBackground[tempValue.idx] == tempValue.newColor
            ) {
                layout.headerBarTabActiveBackground[tempValue.idx] = tempValue.color
            }
        }

        function setLayoutMode(data: string) {
            layout.layoutMode = data
            onSetLayoutColor(data)
        }

        const setLayout = (name: keyof Layout, value: any) => {
            ;(layout[name] as any) = value
        }

        const getColorVal = function (name: keyof Layout): string {
            const colors = layout[name] as string[]
            if (layout.isDark) {
                return colors[1]
            } else {
                return colors[0]
            }
        }

        const setCrud = (name: keyof Crud, value: any) => {
            ;(crud[name] as any) = value
        }

        return { layout, lang, crud, menuWidth, setLang, setLayoutMode, setLayout, getColorVal, onSetLayoutColor, setCrud }
    },
    {
        persist: {
            key: STORE_CONFIG,
        },
    }
)
