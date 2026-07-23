/** FieldTypeDef MySQL 列类型项定义 */
type FieldTypeDef = {
    key: string
    value: string
    comment?: string // 用途说明
}

/** MYSQL_FIELD_TYPE_DEFS MySQL 列类型定义列表 {bigint:Unix秒, tinyint:开关枚举, varchar:短文本路径} */
export const MYSQL_FIELD_TYPE_DEFS = [
    { key: 'BIGINT', value: 'bigint', comment: 'Unix秒' },
    { key: 'INT', value: 'int' },
    { key: 'TINYINT', value: 'tinyint', comment: '开关枚举' },
    { key: 'DECIMAL', value: 'decimal' },
    { key: 'FLOAT', value: 'float' },
    { key: 'VARCHAR', value: 'varchar', comment: '短文本路径' },
    { key: 'CHAR', value: 'char' },
    { key: 'TEXT', value: 'text' },
    { key: 'MEDIUMTEXT', value: 'mediumtext' },
    { key: 'LONGTEXT', value: 'longtext' },
    { key: 'DATE', value: 'date' },
    { key: 'DATETIME', value: 'datetime' },
    { key: 'TIMESTAMP', value: 'timestamp' },
    { key: 'JSON', value: 'json' },
] as const satisfies readonly FieldTypeDef[]

/** FIELD_TYPE MySQL 列数据类型 */
export const FIELD_TYPE = Object.fromEntries(MYSQL_FIELD_TYPE_DEFS.map((d) => [d.key, d.value])) as {
    [K in (typeof MYSQL_FIELD_TYPE_DEFS)[number]['key']]: Extract<
        (typeof MYSQL_FIELD_TYPE_DEFS)[number],
        { key: K }
    >['value']
}

/** FIELD_TYPE_LIST MySQL 列类型选项顺序 */
export const FIELD_TYPE_LIST = MYSQL_FIELD_TYPE_DEFS.map((d) => d.value)
