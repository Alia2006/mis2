/**
 * 弹窗共享状态（适配 BuildAdmin 后端 API）
 *
 * 数据源映射：
 *   部门 ← admin_group（pid 构成层级）
 *   成员 ← admin（通过 admin_group_access 关联到部门）
 *   角色 ← admin_group（所有组都可作为角色）
 */
import { ref } from 'vue'
import createAxios from '/@/utils/axios'
import { getGroups } from '/@/api/backend/workflow/definition'
import $func from './helpers'

export const searchVal = ref('')
export const departments = ref<any>({
    titleDepartments: [],
    childDepartments: [],
    employees: [],
})
export const roles = ref<any[]>([])

/** 所有组缓存（避免重复请求） */
let allGroups: any[] = []

/** 拉取全部组 */
const fetchAllGroups = async () => {
    const res = await getGroups()
    allGroups = res.data?.data?.list || res.data?.list || []
    return allGroups
}

/** 按组获取成员 */
const fetchAdminsByGroup = async (groupId: number | string) => {
    const res = await createAxios({
        url: '/admin/workflow.Definition/getAdmins',
        method: 'get',
        params: { group_id: groupId || '' },
    })
    const list = res.data?.data?.list || res.data?.list || []
    return list.map((a: any) => ({
        id: a.id,
        employeeName: a.nickname,
        departmentNames: a.username,
    }))
}

/** 获取全部成员（无过滤） */
const fetchAllAdmins = async () => {
    const res = await createAxios({
        url: '/admin/workflow.Definition/getAdmins',
        method: 'get',
    })
    const list = res.data?.data?.list || res.data?.list || []
    return list.map((a: any) => ({
        id: a.id,
        employeeName: a.nickname,
        departmentNames: a.username,
    }))
}

/** 获取角色列表 */
export const getRoleList = async () => {
    if (allGroups.length === 0) await fetchAllGroups()
    roles.value = allGroups.map((g: any) => ({
        roleId: g.id,
        roleName: g.name,
        description: g.name,
    }))
}

/**
 * 获取部门列表 + 该部门下的成员
 * parentId=0 时取顶层组，并加载顶层组成员
 */
export const getDepartmentList = async (parentId: number | string = 0) => {
    if (allGroups.length === 0) await fetchAllGroups()

    // 子部门
    const children = parentId === 0
        ? allGroups.filter((g: any) => !g.pid || g.pid === 0)
        : allGroups.filter((g: any) => g.pid == parentId)

    // 成员：加载当前部门的成员
    let employees: any[] = []
    if (parentId !== 0) {
        // 选了具体部门 → 查该部门成员
        employees = await fetchAdminsByGroup(parentId)
    } else {
        // 根目录 → 不显示成员（等用户进入子部门）
        employees = []
    }

    // 面包屑导航
    let titleDepartments: any[] = []
    if (parentId !== 0) {
        // 构建面包屑路径
        const path: any[] = []
        let currentPid: number | string = parentId
        while (currentPid && currentPid !== 0) {
            const g = allGroups.find((item: any) => item.id == currentPid)
            if (g) {
                path.unshift({ id: g.id, departmentName: g.name })
                currentPid = g.pid
            } else break
        }
        titleDepartments = path
    }

    departments.value = {
        titleDepartments,
        childDepartments: children.map((g: any) => ({ id: g.id, departmentName: g.name })),
        employees,
    }
}

/** 搜索防抖 */
export const getDebounceData = (event: Event, type = 1) => {
    $func.debounce(async () => {
        const target = event.target as HTMLInputElement
        if (target.value) {
            if (type == 1) {
                // 搜索成员：全局搜索管理员
                departments.value.childDepartments = []
                const allAdmins = await fetchAllAdmins()
                departments.value.employees = allAdmins.filter(
                    (a: any) => (a.employeeName || '').includes(target.value)
                )
            } else {
                // 搜索角色
                if (allGroups.length === 0) await fetchAllGroups()
                roles.value = allGroups
                    .filter((g: any) => (g.name || '').includes(target.value))
                    .map((g: any) => ({ roleId: g.id, roleName: g.name }))
            }
        } else {
            type == 1 ? await getDepartmentList() : await getRoleList()
        }
    })()
}
