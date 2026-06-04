<?php
/**
 * 输入验证工具类
 */
class Validator
{
    /**
     * 验证非空
     *
     * @param mixed  $value
     * @param string $field
     *
     * @return string|null 错误信息，null 表示验证通过
     */
    public static function required($value, $field = '字段')
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return $field . '不能为空';
        }
        return null;
    }

    /**
     * 验证最小长度
     *
     * @param string $value
     * @param int    $min
     * @param string $field
     *
     * @return string|null
     */
    public static function minLength($value, $min, $field = '字段')
    {
        if (strlen($value) < $min) {
            return $field . '长度至少为' . $min;
        }
        return null;
    }

    /**
     * 验证最大长度
     *
     * @param string $value
     * @param int    $max
     * @param string $field
     *
     * @return string|null
     */
    public static function maxLength($value, $max, $field = '字段')
    {
        if (strlen($value) > $max) {
            return $field . '长度不能超过' . $max;
        }
        return null;
    }

    /**
     * 验证邮箱格式
     *
     * @param string $value
     * @param string $field
     *
     * @return string|null
     */
    public static function email($value, $field = '邮箱')
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $field . '格式不正确';
        }
        return null;
    }

    /**
     * 验证 UUID 格式
     *
     * @param string $value
     * @param string $field
     *
     * @return string|null
     */
    public static function uuid($value, $field = 'UUID')
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        if (!preg_match($pattern, $value)) {
            return $field . '格式不正确';
        }
        return null;
    }

    /**
     * 验证整数
     *
     * @param mixed $value
     * @param string $field
     *
     * @return string|null
     */
    public static function integer($value, $field = '字段')
    {
        if (!is_numeric($value) || intval($value) != $value) {
            return $field . '必须是整数';
        }
        return null;
    }

    /**
     * 验证 JSON 格式
     *
     * @param string $value
     *
     * @return bool
     */
    public static function json($value)
    {
        if (!is_string($value)) {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
