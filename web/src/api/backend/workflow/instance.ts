import createAxios from '/@/utils/axios'

export const url = '/admin/workflow.Instance/'

export function detail(id: number) {
    return createAxios({
        url: url + 'detail',
        method: 'get',
        params: { id },
    })
}

export function cancel(id: number, comment: string = '') {
    return createAxios(
        {
            url: url + 'cancel',
            method: 'post',
            data: { id, comment },
        },
        { showSuccessMessage: true }
    )
}
