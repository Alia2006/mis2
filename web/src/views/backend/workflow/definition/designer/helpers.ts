/**
 * 工作流设计器工具函数（移植自 Workflow-Vue3 utils/index.js）
 */

export function arrToStr(arr: any[] | undefined): string {
    if (arr) return arr.map((item) => item.name).toString()
    return ''
}

export function toggleClass(arr: any[], elem: any, key = 'id'): boolean {
    return arr.some((item) => item[key] == elem[key])
}

export function toChecked(arr: any[], elem: any, key = 'id'): void {
    const isIncludes = toggleClass(arr, elem, key)
    if (!isIncludes) arr.push(elem)
    else removeEle(arr, elem, key)
}

export function removeEle(arr: any[], elem: any, key = 'id'): void {
    let includesIndex = -1
    arr.forEach((item, index) => {
        if (item[key] == elem[key]) includesIndex = index
    })
    if (includesIndex >= 0) arr.splice(includesIndex, 1)
}

export function setApproverStr(nodeConfig: any): string {
    if (nodeConfig.settype == 1) {
        if (nodeConfig.nodeUserList.length == 1) return nodeConfig.nodeUserList[0].name
        else if (nodeConfig.nodeUserList.length > 1) {
            if (nodeConfig.examineMode == 1) return arrToStr(nodeConfig.nodeUserList)
            else if (nodeConfig.examineMode == 2) return nodeConfig.nodeUserList.length + '人会签'
        }
    } else if (nodeConfig.settype == 2) {
        let level = nodeConfig.directorLevel == 1 ? '直接主管' : '第' + nodeConfig.directorLevel + '级主管'
        if (nodeConfig.examineMode == 1) return level
        else if (nodeConfig.examineMode == 2) return level + '会签'
    } else if (nodeConfig.settype == 4) {
        if (nodeConfig.selectRange == 1) return '发起人自选'
        else {
            if (nodeConfig.nodeUserList.length > 0) {
                if (nodeConfig.selectRange == 2) return '发起人自选'
                else return '发起人从' + nodeConfig.nodeUserList[0].name + '中自选'
            } else return ''
        }
    } else if (nodeConfig.settype == 5) {
        return '发起人自己'
    } else if (nodeConfig.settype == 7) {
        return '从直接主管到通讯录中级别最高的第' + nodeConfig.examineEndDirectorLevel + '个层级主管'
    }
    return ''
}

export function dealStr(str: string, obj: Record<string, any>): string {
    let arr: string[] = []
    let list = str.split(',')
    for (let elem in obj) {
        list.map((item) => {
            if (item == elem) arr.push(obj[elem].value)
        })
    }
    return arr.join('或')
}

export function conditionStr(nodeConfig: any, index: number): string {
    let { conditionList, nodeUserList } = nodeConfig.conditionNodes[index]
    if (conditionList.length == 0) {
        return index == nodeConfig.conditionNodes.length - 1 && nodeConfig.conditionNodes[0].conditionList.length != 0
            ? '其他条件进入此流程'
            : '请设置条件'
    } else {
        let str = ''
        for (let i = 0; i < conditionList.length; i++) {
            let { columnId, columnType, showType, showName, optType, zdy1, opt1, zdy2, opt2, fixedDownBoxValue } = conditionList[i]
            if (columnId == 0) {
                if (nodeUserList.length != 0) {
                    str += '发起人属于：'
                    str += nodeUserList.map((item: any) => item.name).join('或') + ' 并且 '
                }
            }
            if (columnType == 'String' && showType == '3') {
                if (zdy1) str += showName + '属于：' + dealStr(zdy1, JSON.parse(fixedDownBoxValue)) + ' 并且 '
            }
            if (columnType == 'Double') {
                if (optType != '6' && zdy1) {
                    let optTypeStr = ['', '<', '>', '≤', '=', '≥'][parseInt(optType)]
                    str += `${showName} ${optTypeStr} ${zdy1} 并且 `
                } else if (optType == '6' && zdy1 && zdy2) {
                    str += `${zdy1} ${opt1} ${showName} ${opt2} ${zdy2} 并且 `
                }
            }
        }
        return str ? str.substring(0, str.length - 4) : '请设置条件'
    }
}

export function copyerStr(nodeConfig: any): string {
    if (nodeConfig.nodeUserList.length != 0) return arrToStr(nodeConfig.nodeUserList)
    else {
        if (nodeConfig.ccSelfSelectFlag == 1) return '发起人自选'
    }
    return ''
}

export function toggleStrClass(item: any, key: string): boolean {
    let a = item.zdy1 ? item.zdy1.split(',') : []
    return a.some((item: string) => item == key)
}

export function debounce(fn: Function, delay = 500) {
    let timer: ReturnType<typeof setTimeout>
    return function (arg?: any) {
        clearTimeout(timer)
        timer = setTimeout(() => fn(arg), delay)
    }
}

export default {
    arrToStr,
    toggleClass,
    toChecked,
    removeEle,
    setApproverStr,
    dealStr,
    conditionStr,
    copyerStr,
    toggleStrClass,
    debounce,
}
