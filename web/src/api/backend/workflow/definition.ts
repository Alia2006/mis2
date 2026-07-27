import createAxios from '/@/utils/axios'

export const url = '/admin/workflow.Definition/'

export const baTableApi = {
    list: url + 'index',
    detail: url + 'edit',
}

export function saveGraph(data: anyObj) {
    return createAxios(
        {
            url: url + 'saveGraph',
            method: 'post',
            data,
        },
        { showSuccessMessage: true }
    )
}

export function publish(id: number) {
    return createAxios(
        {
            url: url + 'publish',
            method: 'post',
            data: { id },
        },
        { showSuccessMessage: true }
    )
}

export function copyDefinition(id: number) {
    return createAxios(
        {
            url: url + 'copy',
            method: 'post',
            data: { id },
        },
        { showSuccessMessage: true }
    )
}

export function getAdmins() {
    return createAxios({
        url: url + 'getAdmins',
        method: 'get',
    })
}

export function getGroups() {
    return createAxios({
        url: url + 'getGroups',
        method: 'get',
    })
}
