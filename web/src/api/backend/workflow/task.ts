import createAxios from '/@/utils/axios'

export const url = '/admin/workflow.Task/'

export function myTodo(params: anyObj = {}) {
    return createAxios({
        url: url + 'myTodo',
        method: 'get',
        params,
    })
}

export function myDone(params: anyObj = {}) {
    return createAxios({
        url: url + 'myDone',
        method: 'get',
        params,
    })
}

export function myInitiated(params: anyObj = {}) {
    return createAxios({
        url: url + 'myInitiated',
        method: 'get',
        params,
    })
}

export function approve(id: number, comment: string = '') {
    return createAxios(
        {
            url: url + 'approve',
            method: 'post',
            data: { id, comment },
        },
        { showSuccessMessage: true }
    )
}

export function reject(id: number, comment: string = '') {
    return createAxios(
        {
            url: url + 'reject',
            method: 'post',
            data: { id, comment },
        },
        { showSuccessMessage: true }
    )
}

export function back(id: number, comment: string = '') {
    return createAxios(
        {
            url: url + 'back',
            method: 'post',
            data: { id, comment },
        },
        { showSuccessMessage: true }
    )
}

export function transfer(id: number, to_admin_id: number, comment: string = '') {
    return createAxios(
        {
            url: url + 'transfer',
            method: 'post',
            data: { id, to_admin_id, comment },
        },
        { showSuccessMessage: true }
    )
}
