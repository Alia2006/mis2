/**
 * 语言包合并工具方法
 * 从 lang/index.ts 中抽出，供 index.ts、utils/dev.ts 等复用
 */

/**
 * 将带命名空间的语言包数据按路径组装为嵌套对象后合并到总消息对象中
 * @param msg       总消息对象
 * @param mList     语言包数据
 * @param pathName  文件命名空间，如 auth/admin
 */
export function handleMsglist(msg: anyObj, mList: anyObj, pathName: string) {
    const pathNameTmp = pathName.split('/')
    let obj: anyObj = {}
    for (let i = pathNameTmp.length - 1; i >= 0; i--) {
        if (i == pathNameTmp.length - 1) {
            obj = {
                [pathNameTmp[i]]: mList,
            }
        } else {
            obj = {
                [pathNameTmp[i]]: obj,
            }
        }
    }
    return mergeMsg(msg, obj)
}

/**
 * 深度合并两个对象，将 obj 合并进 msg
 */
export function mergeMsg(msg: anyObj, obj: anyObj) {
    for (const key in obj) {
        if (typeof msg[key] == 'undefined') {
            msg[key] = obj[key]
        } else if (typeof msg[key] == 'object') {
            msg[key] = mergeMsg(msg[key], obj[key])
        }
    }
    return msg
}
