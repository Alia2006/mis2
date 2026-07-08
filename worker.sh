#!/bin/bash
# ========================================================================
# Workerman 服务管理脚本 (BuildAdmin + ThinkPHP8)
# ========================================================================
# 用法:
#   ./worker.sh start        # 前台启动所有服务
#   ./worker.sh start -d     # 守护进程启动
#   ./worker.sh stop         # 停止所有服务
#   ./worker.sh restart      # 重启所有服务
#   ./worker.sh reload       # 平滑重启（不中断连接）
#   ./worker.sh status       # 查看进程状态
#   ./worker.sh start http   # 仅启动 HTTP 服务
#   ./worker.sh start ws     # 仅启动 WebSocket 服务
# ========================================================================

set -e

# 项目根目录（脚本所在目录）
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

# PHP 路径（可通过环境变量覆盖）
PHP_BIN="${PHP_BIN:-php}"

# 颜色
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

print_header() {
    echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
    echo -e "${CYAN}  Workerman 服务管理 - BuildAdmin${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
}

check_env() {
    # 检查 PHP 是否可用
    if ! command -v "$PHP_BIN" &> /dev/null; then
        echo -e "${RED}[错误] 未找到 PHP，请确保 PHP CLI 已安装${NC}"
        echo -e "  安装: sudo apt-get install -y php${PHP_VERSION:-8.2}-cli"
        exit 1
    fi

    # 检查必需扩展
    local missing=()
    for ext in pcntl posix; do
        if ! "$PHP_BIN" -m 2>/dev/null | grep -iq "^$ext$"; then
            missing+=("$ext")
        fi
    done

    if [ ${#missing[@]} -gt 0 ]; then
        echo -e "${RED}[错误] 缺少必需扩展: ${missing[*]}${NC}"
        echo -e "  安装: sudo apt-get install -y php${PHP_VERSION:-8.2}-{$(IFS=,; echo "${missing[*]}")}"
        exit 1
    fi

    echo -e "${GREEN}[√] PHP 环境检查通过 ($("$PHP_BIN" -v | head -1)${NC})"
}

# 解析参数
ACTION="${1:-start}"
DAEMON=""
MODE="all"

# 解析额外参数
shift || true
while [[ $# -gt 0 ]]; do
    case "$1" in
        -d|--daemon)
            DAEMON="-d"
            ;;
        http|ws)
            MODE="$1"
            ;;
    esac
    shift
done

# 特殊动作不需要环境检查
case "$ACTION" in
    status|connections)
        ;;
    *)
        check_env
        ;;
esac

print_header

# 构建命令
CMD="$PHP_BIN think worker:start $ACTION"
if [ -n "$DAEMON" ]; then
    CMD="$CMD $DAEMON"
fi
CMD="$CMD --mode=$MODE"

echo -e "${YELLOW}  动作: $ACTION${NC}"
echo -e "${YELLOW}  模式: $MODE${NC}"
if [ -n "$DAEMON" ]; then
    echo -e "${YELLOW}  守护: 是${NC}"
fi
echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo ""

# 执行
case "$ACTION" in
    start)
        echo -e "${GREEN}启动 Workerman 服务...${NC}"
        if [ -n "$DAEMON" ]; then
            eval "$CMD"
            sleep 1
            echo -e "${GREEN}服务已在后台启动${NC}"
            "$PHP_BIN" think worker:start status --mode=all 2>/dev/null || true
        else
            eval "$CMD"
        fi
        ;;
    stop)
        echo -e "${YELLOW}停止 Workerman 服务...${NC}"
        eval "$CMD"
        echo -e "${GREEN}服务已停止${NC}"
        ;;
    restart)
        echo -e "${YELLOW}重启 Workerman 服务...${NC}"
        eval "$CMD"
        ;;
    reload)
        echo -e "${YELLOW}平滑重载 Workerman 服务...${NC}"
        eval "$CMD"
        echo -e "${GREEN}服务已平滑重载${NC}"
        ;;
    status)
        eval "$CMD"
        ;;
    *)
        echo -e "${RED}未知操作: $ACTION${NC}"
        echo -e "可用操作: start, stop, restart, reload, status"
        exit 1
        ;;
esac
