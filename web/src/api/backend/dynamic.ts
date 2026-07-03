import createAxios from '/@/utils/axios'
import type { DynamicTableConfig, TableConfigRecord } from '/@/views/backend/dynamic/types'

/**
 * 获取动态表格配置（前端渲染用）
 * GET /admin/dynamic.Config/getConfig?name=employee
 */
export const getDynamicConfig = (name: string) => {
    return createAxios<DynamicTableConfig>({
        url: '/admin/dynamic.Config/getConfig',
        method: 'get',
        params: { name },
    })
}

/**
 * 按配置 ID 获取表格配置（详情抽屉用）
 * GET /admin/dynamic.Config/getConfigById?id=5
 */
export const getDynamicConfigById = (id: number) => {
    return createAxios<DynamicTableConfig>({
        url: '/admin/dynamic.Config/getConfigById',
        method: 'get',
        params: { id },
    })
}

/**
 * 获取已启用的动态表列表（设计器选择详情表用）
 */
export const getDynamicTableList = () => {
    return createAxios({
        url: '/admin/dynamic.Config/getTableList',
        method: 'get',
    })
}

/**
 * 获取单条配置详情（含字段）
 */
export const getDynamicConfigDetail = (id: number) => {
    return createAxios<TableConfigRecord>({
        url: '/admin/dynamic.Config/edit',
        method: 'get',
        params: { id },
    })
}

/**
 * 新增配置
 */
export const addDynamicConfig = (data: anyObj) => {
    return createAxios({
        url: '/admin/dynamic.Config/add',
        method: 'post',
        data,
    })
}

/**
 * 编辑配置
 */
export const editDynamicConfig = (data: anyObj) => {
    return createAxios({
        url: '/admin/dynamic.Config/edit',
        method: 'post',
        data,
    })
}

/**
 * 删除配置
 */
export const delDynamicConfig = (ids: number[]) => {
    return createAxios({
        url: '/admin/dynamic.Config/del',
        method: 'delete',
        data: { ids },
    })
}

/**
 * 从数据库拉取表字段信息（设计器用）
 */
export const getDbTableFields = (table: string, connection: string = '') => {
    return createAxios({
        url: '/admin/dynamic.Config/getTableFields',
        method: 'get',
        params: { table, connection },
    })
}

/**
 * 获取菜单树（设计器选择父级菜单用）
 */
export const getMenuTree = () => {
    return createAxios({
        url: '/admin/dynamic.Config/getMenuTree',
        method: 'get',
    })
}
