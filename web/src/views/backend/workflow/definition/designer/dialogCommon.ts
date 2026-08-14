/**
 * 弹窗共享状态（适配 BuildAdmin）
 *
 * 数据策略：一次性加载全部组和全部管理员（含 group_ids），
 * 之后部门导航/搜索全部在前端完成，零网络延迟。
 */
import { ref } from 'vue'
import createAxios from '/@/utils/axios'
import $func from './helpers'

export const searchVal = ref('')
export const departments = ref<any>({
    titleDepartments: [],
    childDepartments: [],
    employees: [],
})
export const roles = ref<any[]>([])

/** 缓存 */
let allGroups: any[] = []
let allAdmins: any[] = []
let loaded = false

/** 一次性加载全部数据 */
const ensureLoaded = async () => {
    if (loaded) return
    const [groupRes, adminRes] = await Promise.all([
        createAxios({ url: '/admin/workflow.Definition/getGroups', method: 'get' }),
        createAxios({ url: '/admin/workflow.Definition/getAdmins', method: 'get' }),
    ])
    allGroups = groupRes.data?.data?.list || groupRes.data?.list || []
    allAdmins = (adminRes.data?.data?.list || adminRes.data?.list || []).map((a: any) => ({
        id: a.id,
        employeeName: a.nickname,
        departmentNames: a.username,
        groupIds: a.group_ids || [],
    }))
    loaded = true
}

/** 获取角色列表 */
export const getRoleList = async () => {
    await ensureLoaded()
    roles.value = allGroups.map((g) => ({
        roleId: g.id,
        roleName: g.name,
        description: g.name,
    }))
}

/**
 * 获取部门列表 + 该部门下的成员（纯前端过滤，零延迟）
 */
export const getDepartmentList = async (parentId: number | string = 0) => {
    await ensureLoaded()

    // 子部门
    const children = parentId === 0
        ? allGroups.filter((g) => !g.pid || g.pid === 0)
        : allGroups.filter((g) => g.pid == parentId)

    // 成员：当前部门下的管理员（前端过滤）
    const employees = parentId === 0
        ? []
        : allAdmins.filter((a) => a.groupIds.includes(Number(parentId)))

    // 面包屑路径
    let titleDepartments: any[] = []
    if (parentId !== 0) {
        const path: any[] = []
        let cur: number | string = parentId
        while (cur && cur !== 0) {
            const g = allGroups.find((item) => item.id == cur)
            if (g) { path.unshift({ id: g.id, departmentName: g.name }); cur = g.pid }
            else break
        }
        titleDepartments = path
    }

    departments.value = { titleDepartments, childDepartments: children, employees }
}

/** 搜索防抖 */
export const getDebounceData = (event: Event, type = 1) => {
    $func.debounce(async () => {
        await ensureLoaded()
        const target = event.target as HTMLInputElement
        if (target.value) {
            if (type == 1) {
                departments.value.childDepartments = []
                departments.value.employees = allAdmins.filter(
                    (a) => (a.employeeName || '').includes(target.value)
                )
            } else {
                roles.value = allGroups
                    .filter((g) => (g.name || '').includes(target.value))
                    .map((g) => ({ roleId: g.id, roleName: g.name }))
            }
        } else {
            type == 1 ? await getDepartmentList() : await getRoleList()
        }
    })()
}
