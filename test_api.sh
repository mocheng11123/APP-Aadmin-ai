# iApp 管家 API - curl 测试脚本
# 
# 使用方法：
# 1. 修改 BASE_URL 为实际部署地址
# 2. 运行：bash test_api.sh
# 3. 或者复制到命令行逐条执行

BASE_URL="http://localhost/iapp_api/api"

echo_title() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
}

TOKEN=""
APP_UUID=""

# ============================================
# 1. 主账号注册
# ============================================
echo_title "1. 主账号注册"
RESPONSE=$(curl -s -X POST "${BASE_URL}/user/register" \
    -H "Content-Type: application/json" \
    -d '{"username":"owner_user","password":"123456"}')
echo "$RESPONSE"
echo ""

# ============================================
# 2. 主账号登录
# ============================================
echo_title "2. 主账号登录"
RESPONSE=$(curl -s -X POST "${BASE_URL}/user/login" \
    -H "Content-Type: application/json" \
    -d '{"username":"owner_user","password":"123456"}')
echo "$RESPONSE"

# 提取 Token
TOKEN=$(echo "$RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
if [ -n "$TOKEN" ]; then
    echo "Token: $TOKEN"
else
    echo "Error: 无法提取 Token"
    exit 1
fi
echo ""

# ============================================
# 3. 创建应用
# ============================================
echo_title "3. 创建应用"
RESPONSE=$(curl -s -X POST "${BASE_URL}/app/create" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d '{"app_name":"测试应用"}')
echo "$RESPONSE"

# 提取 app_uuid
APP_UUID=$(echo "$RESPONSE" | grep -o '"app_uuid":"[^"]*"' | cut -d'"' -f4)
if [ -n "$APP_UUID" ]; then
    echo "App UUID: $APP_UUID"
else
    echo "Error: 无法提取 app_uuid"
    exit 1
fi
echo ""

# ============================================
# 4. 获取我的应用列表
# ============================================
echo_title "4. 获取我的应用列表"
curl -s -X GET "${BASE_URL}/app/list" \
    -H "Authorization: Bearer ${TOKEN}" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 5. 子用户注册
# ============================================
echo_title "5. 子用户注册"
curl -s -X POST "${BASE_URL}/user/register_sub" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
        \"username\":\"sub_user1\",
        \"password\":\"123456\",
        \"app_uuid\":\"${APP_UUID}\"
    }" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 6. 子用户登录
# ============================================
echo_title "6. 子用户登录"
SUB_RESPONSE=$(curl -s -X POST "${BASE_URL}/user/login" \
    -H "Content-Type: application/json" \
    -d '{"username":"sub_user1","password":"123456"}')
echo "$SUB_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "请求失败"

SUB_TOKEN=$(echo "$SUB_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo ""

# ============================================
# 7. 版本更新设置（仅主账号）
# ============================================
echo_title "7. 版本更新设置（仅主账号）"
curl -s -X POST "${BASE_URL}/version/update" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
        \"app_uuid\":\"${APP_UUID}\",
        \"version_code\":101,
        \"version_name\":\"1.0.1\",
        \"download_url\":\"https://example.com/app-v1.0.1.apk\",
        \"update_content\":\"修复已知 bug\",
        \"force_update\":0
    }" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 8. 版本检测（子用户也可访问）
# ============================================
echo_title "8. 版本检测（子用户 token）"
curl -s -X GET "${BASE_URL}/version/check?app_uuid=${APP_UUID}&client_version_code=100" \
    -H "Authorization: Bearer ${SUB_TOKEN}" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 9. 创建公告（仅主账号）
# ============================================
echo_title "9. 创建公告（仅主账号）"
curl -s -X POST "${BASE_URL}/announcement/create" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
        \"app_uuid\":\"${APP_UUID}\",
        \"title\":\"新功能上线\",
        \"content\":\"欢迎使用新功能！\"
    }" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 10. 公告列表（所有人可读）
# ============================================
echo_title "10. 公告列表（子用户 token）"
curl -s -X GET "${BASE_URL}/announcement/list?app_uuid=${APP_UUID}&page=1&limit=10" \
    -H "Authorization: Bearer ${SUB_TOKEN}" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 11. 设置启动图（仅主账号）
# ============================================
echo_title "11. 设置启动图（仅主账号）"
curl -s -X POST "${BASE_URL}/splash/config" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
        \"app_uuid\":\"${APP_UUID}\",
        \"image_url\":\"https://example.com/splash.png\",
        \"duration\":3000
    }" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 12. 获取启动图（所有人可读）
# ============================================
echo_title "12. 获取启动图（子用户 token）"
curl -s -X GET "${BASE_URL}/splash/config?app_uuid=${APP_UUID}" \
    -H "Authorization: Bearer ${SUB_TOKEN}" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 13. 空白文档 - 设置字段（仅主账号）
# ============================================
echo_title "13. 空白文档 - 设置字段（仅主账号）"
curl -s -X POST "${BASE_URL}/doc/set" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
        \"app_uuid\":\"${APP_UUID}\",
        \"data\":{
            \"user_name\":\"张三\",
            \"age\":25,
            \"settings\":{
                \"theme\":\"dark\",
                \"language\":\"zh-CN\"
            }
        }
    }" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 14. 空白文档 - 获取（所有人可读）
# ============================================
echo_title "14. 空白文档 - 获取（子用户 token）"
curl -s -X GET "${BASE_URL}/doc/get?app_uuid=${APP_UUID}" \
    -H "Authorization: Bearer ${SUB_TOKEN}" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 15. 空白文档 - 深度合并设置
# ============================================
echo_title "15. 空白文档 - 深度合并设置"
curl -s -X POST "${BASE_URL}/doc/set" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
        \"app_uuid\":\"${APP_UUID}\",
        \"data\":{
            \"settings\":{
                \"font_size\":16,
                \"notifications\":true
            },
            \"email\":\"test@example.com\"
        }
    }" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 16. 空白文档 - 获取（验证合并）
# ============================================
echo_title "16. 空白文档 - 获取（验证合并结果）"
curl -s -X GET "${BASE_URL}/doc/get?app_uuid=${APP_UUID}" \
    -H "Authorization: Bearer ${SUB_TOKEN}" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 17. 空白文档 - 删除字段
# ============================================
echo_title "17. 空白文档 - 删除字段"
curl -s -X POST "${BASE_URL}/doc/delete" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
        \"app_uuid\":\"${APP_UUID}\",
        \"keys\":[\"age\",\"settings.theme\"]
    }" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 18. 子用户尝试写操作（应该 403）
# ============================================
echo_title "18. 子用户尝试写文档（应该 403）"
curl -s -X POST "${BASE_URL}/doc/set" \
    -H "Authorization: Bearer ${SUB_TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
        \"app_uuid\":\"${APP_UUID}\",
        \"data\":{\"test\":\"value\"}
    }" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 19. 空白文档 - 清空
# ============================================
echo_title "19. 空白文档 - 清空"
curl -s -X POST "${BASE_URL}/doc/clear" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{\"app_uuid\":\"${APP_UUID}\"}" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

# ============================================
# 20. 退出登录
# ============================================
echo_title "20. 退出登录"
curl -s -X POST "${BASE_URL}/user/logout" \
    -H "Authorization: Bearer ${TOKEN}" \
    | python3 -m json.tool 2>/dev/null || echo "请求失败"
echo ""

echo "测试完成！"
