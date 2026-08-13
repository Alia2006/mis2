/**
 * 弹窗共享状态（移植自 common.js，适配 BuildAdmin 后端 API）
 */
import { ref } from 'vue'
import { getAdmins, getGroups } from '/@/api/backend/workflow/definition'
import $func from './helpers'

export const searchVal = ref('')
export const departments = ref<any>({
    titleDepartments: [],
    childDepartments: [],
    employees: [],
})
export const roles = ref<any[]>([])

/** 获取角色列表（对接 admin_group） */
export const getRoleList = async () => {
    const res = await getGroups()
    const list = res.data?.data?.list || res.data?.list || []
    roles.value = list.map((g: any) => ({ roleId: g.id, roleName: g.name, description: g.name }))
}

/** 获取部门列表（用 admin_group 模拟，parentId=0 取顶层） */
export const getDepartmentList = async (parentId: number | string = 0) => {
    const res = await getGroups()
    const list = res.data?.data?.list || res.data?.list || []
    const children = parentId === 0 ? list.filter((g: any) => !g.pid || g.pid === 0) : list.filter((g: any) => g.pid == parentId)
    departments.value = {
        titleDepartments: parentId === 0 ? [] : [{ id: parentId, departmentName: '上级' }],
        childDepartments: children.map((g: any) => ({ id: g.id, departmentName: g.name })),
        employees: [],
    }
}

/** 搜索防抖 */
export const getDebounceData = (event: Event, type = 1) => {
    $func.debounce(async () => {
        const target = event.target as HTMLInputElement
        if (target.value) {
            if (type == 1) {
                departments.value.childDepartments = []
                const res = await getAdmins()
                const list = res.data?.data?.list || res.data?.list || []
                departments.value.employees = list
                    .filter((a: any) => (a.nickname || '').includes(target.value))
                    .map((a: any) => ({ id: a.id, employeeName: a.nickname, departmentNames: a.username }))
            } else {
                const res = await getGroups()
                const list = res.data?.data?.list || res.data?.list || []
                roles.value = list
                    .filter((g: any) => (g.name || '').includes(target.value))
                    .map((g: any) => ({ roleId: g.id, roleName: g.name }))
            }
        } else {
            type == 1 ? await getDepartmentList() : await getRoleList()
        }
    })()
}
